<?php
/**
 * 银台操作.
 * User: qubojie
 * Date: 2018/9/10
 * Time: 下午7:10
 */

namespace app\admin\controller;

use app\wechat\controller\WechatPay;
use app\wechat\model\BillPay;
use app\wechat\model\BillPayDetail;
use think\Db;
use think\Exception;
use think\Request;

class SilverAction extends CommandAction
{
    /**
     * 单据列表
     * @param Request $request
     * @return array
     * @throws \think\exception\DbException
     */
    public function index(Request $request)
    {
        $pagesize    = $request->param("pagesize",config('PAGESIZE'));//显示个数,不传时为10
        if (empty($pagesize)) $pagesize = config('PAGESIZE');

        $nowPage     = $request->param("nowPage","1");

        $keyword     = $request->param("keyword","");

        $sale_status = $request->param("sale_status","");

        $sale_status_where = [];
        if ($sale_status != ""){
            $sale_status_where['bp.sale_status'] = ["eq",$sale_status];
        }

        $billPayModel = new BillPay();

        $pageConfig = [
            "page" => $nowPage,
        ];

        $list_column = $billPayModel->list_column;

        foreach ($list_column as $key => $val){
            $list_column[$key] = "bp.".$val;
        }

        $list = $billPayModel
            ->alias("bp")
            ->join("user u","u.uid = bp.uid","LEFT")
            ->join("table_revenue tr","tr.trid = bp.trid")
            ->where($sale_status_where)
            ->where("bp.sale_status","neq",config("order.bill_pay_sale_status")['wait_audit']['key'])
            ->field("u.name user_name")
            ->field($list_column)
            ->field("tr.table_id,tr.table_no,tr.open_time")
            ->order("bp.created_at DESC")
            ->paginate($pagesize,false,$pageConfig);

        $list = json_decode(json_encode($list),true);

        return $this->com_return(true,config("params.SUCCESS"),$list);
    }

    /**
     * 订单详情
     * @param Request $request
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function orderDetails(Request $request)
    {
        $pid = $request->param("pid","");

        if (empty($pid)){
            return $this->com_return(false,config("params.ABNORMAL_ACTION"));
        }

        $billPayModel = new BillPay();

        $details_column = $billPayModel->column;

        foreach ($details_column as $key => $val){
            $details_column[$key] = "bp.".$val;
        }

        $billInfo = $billPayModel
            ->alias("bp")
            ->where("bp.pid",$pid)
            ->field($details_column)
            ->find();

        $billInfo = json_decode(json_encode($billInfo),true);

        if (empty($billInfo)){
            return $this->com_return(false,config("params.ORDER")['ORDER_ABNORMAL']);
        }

        $billPayDetailsModel = new BillPayDetail();

        $column = $billPayDetailsModel->column;

        foreach ($column as $key => $val){
            $column[$key] = "bpd.".$val;
        }

        $dishInfo =$billPayDetailsModel
            ->alias("bpd")
            ->join("dishes d","d.dis_id = bpd.dis_id")
            ->where("bpd.pid",$pid)
            ->field($column)
            ->field("d.dis_sn")
            ->select();

        $dishInfo = json_decode(json_encode($dishInfo),true);

        if (empty($dishInfo)){
            return $this->com_return(false,config("params.ORDER")['ORDER_ABNORMAL']);
        }

        $common = new Common();

        $dishInfo = $common->make_tree($dishInfo,"id","parent_id");

        $billInfo['dish_info'] = $dishInfo;

        return $this->com_return(true,config("params.SUCCESS"),$billInfo);
    }

    /**
     * 银台操作 线下支付审核操作
     * @param Request $request
     * @return array
     * @throws \think\exception\DbException
     */
    public function examineReceivables(Request $request)
    {
        $pid               = $request->param("pid","");
        $check_reason      = $request->param("check_reason","");//审核原因
        $pay_user          = $request->param("pay_user","");//付款人
        $deal_amount       = $request->param("deal_amount","");//付款总额
        $pay_offline_type  = $request->param("pay_offline_type","");//现金支付 途径   ‘weixin’微信  ‘alipay’ 支付宝  ‘bank’ 刷卡  ‘cash’现金
        $pay_no            = $request->param("pay_no","");//支付回单号（对方流水单号）
        $receipt_account   = $request->param("receipt_account","");//银行收款账号

        $token = $request->header("Authorization");

        return $this->receivablesPublicAction($token,"$pid","$pay_offline_type","$check_reason","$pay_user","$deal_amount","$pay_no","$receipt_account");
    }

    /**
     * 审核公共部分
     * @param $token
     * @param $pid
     * @param $pay_offline_type
     * @param $check_reason
     * @param $pay_user
     * @param $deal_amount
     * @param $pay_no
     * @param $receipt_account
     * @return array
     * @throws \think\exception\DbException
     */
    public function receivablesPublicAction($token,$pid,$pay_offline_type,$check_reason,$pay_user,$deal_amount,$pay_no,$receipt_account)
    {
        $sale_status   = config("order.bill_pay_sale_status")['completed']['key'];

        $adminUserInfo = $this->getLoginAdminId($token);

        $check_user    = $adminUserInfo['user_name'];

        if ($pay_offline_type == config("order.pay_method")['wxpay']['key']){
            //线下微信收款

        }elseif ($pay_offline_type == config("order.pay_method")['alipay']['key']){
            //线下阿里收款

        }elseif ($pay_offline_type == config("order.pay_method")['bank']['key']){
            //线下银行收款

        }elseif ($pay_offline_type == config("order.pay_method")['cash']['key']){
            //线下现金收款

        }else{
            return $this->com_return(false,config("params.ORDER")['NOW_STATUS_NOT_PAY']);
        }

        $orderInfo = $this->getBillOrderInfo($pid);

        $order_amount = $orderInfo['order_amount'];

        $discount = $order_amount - $deal_amount;//订单总额 - 实付金额 = 折扣金额

        $updateParams = [
            "sale_status"       => $sale_status,
            "finish_time"       => time(),
            "check_user"        => $check_user,
            "check_time"        => time(),
            "check_reason"      => $check_reason,
            "pay_user"          => $pay_user,
            "discount"          => $discount,
            "deal_amount"       => $deal_amount,
            "pay_offline_type"  => $pay_offline_type,
            "pay_no"            => $pay_no,
            "receipt_account"   => $receipt_account,
            "updated_at"        => time()
        ];

        Db::startTrans();
        try{
            /*订单支付回调 on*/
            $wechatPayObj = new WechatPay();

            $payCallBackParams = [
                "out_trade_no"   => $pid,
                "cash_fee"       => $deal_amount * 100,
                "total_fee"      => $deal_amount * 100,
                "transaction_id" => "",
                "pay_type"       => config("order.pay_method")['offline']['key']
            ];

            $payCallBackReturn = $wechatPayObj->pointListNotify($payCallBackParams,"");

            $payCallBackReturn = json_decode(json_encode(simplexml_load_string($payCallBackReturn, 'SimpleXMLElement', LIBXML_NOCDATA)), true);

            if ($payCallBackReturn["return_code"] != "SUCCESS" || $payCallBackReturn["return_msg"] != "OK"){

                //回调失败
                return $this->com_return(false,$payCallBackReturn["return_msg"]);

            }
            /*订单支付回调 off*/


            /*回调成功,进行订单改变 on*/
            $billPayModel = new BillPay();

            $updateOrderInfo = $billPayModel
                ->where("pid",$pid)
                ->update($updateParams);

            if ($updateOrderInfo == false){
                return $this->com_return(false,config("params.FAIL"));
            }
            /*回调成功,进行订单改变 off*/


            /*状态改变后,调起打印机,落单 on*/
            $is_print = $this->openTableToPrintYly($pid);

            $dateTimeFile = APP_PATH."index/PrintOrderYly/".date("Ym")."/";

            if (!is_dir($dateTimeFile)){

                @mkdir($dateTimeFile,0777,true);

            }

            //打印结果日志
            error_log(date('Y-m-d H:i:s').var_export($is_print,true),3,$dateTimeFile.date("d").".log");

            /*状态改变后,调起打印机,落单 off*/

            Db::commit();

            return $this->com_return(true,config("params.SUCCESS"));

        }catch (Exception $e){

            Db::rollback();
            return $this->com_return(false,$e->getMessage());
        }
    }

    /**
     * 获取订单信息
     * @param $pid
     * @return array|false|mixed|\PDOStatement|string|\think\Model
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    protected function getBillOrderInfo($pid)
    {
        $billPayModel = new BillPay();

        $orderAmount = $billPayModel
            ->where("pid",$pid)
            ->field("order_amount")
            ->find();

        $orderAmount = json_decode(json_encode($orderAmount),true);

        return $orderAmount;
    }
}