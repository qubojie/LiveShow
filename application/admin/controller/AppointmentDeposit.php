<?php
/**
 * Created by PhpStorm.
 * User: qubojie
 * Date: 2018/8/13
 * Time: 下午5:30
 */
namespace app\admin\controller;

use app\wechat\model\BillSubscription;
use think\Controller;
use think\Request;
use think\Validate;

class AppointmentDeposit extends Controller
{
    /**
     * 预约定金订单状态分组
     * @return array
     */
    public function orderStatus()
    {
        $typeList = config("order.reservation_subscription_status");

        $billSubscriptionModel = new BillSubscription();

        $res = [];

        foreach ($typeList as $key => $val){
            if ($val["key"] == config("order.reservation_subscription_status")['pending_payment']['key']){
                $count = $billSubscriptionModel
                    ->where("status",config("order.reservation_subscription_status")['pending_payment']['key'])
                    ->count();//未付款总记录数

                $val["count"] = $count;
            }else{
                $val["count"] = 0;
            }
            $res[] = $val;

        }


        return $this->com_return(true,config("params.SUCCESS"),$res);
    }

    /**
     * 预约定金列表
     * @param Request $request
     * @return array
     * @throws \think\exception\DbException
     */
    public function index(Request $request)
    {

        $billSubscriptionModel = new BillSubscription();

        $status     = $request->param("status","");

        $keyword    = $request->param("keyword","");

        $pay_type   = $request->param("pay_type","");//支付方式

        $begin_time = $request->param('begin_time',"");//开始时间

        $end_time   = $request->param('end_time',"");//结束时间

        $pagesize   = $request->param("pagesize",config('PAGESIZE'));//显示个数,不传时为10

        if (empty($pagesize)) $pagesize = config('PAGESIZE');

        $nowPage = $request->param("nowPage","1");

        $config = [
            "page" => $nowPage,
        ];

        $status_where = [];
        if (!empty($status)){

            $status_where['bs.status'] = $status;

        }

        $time_where = [];
        if (!empty($begin_time) && empty($end_time)){
            $time_where['bs.created_at'] = ['EGT',$begin_time];//大于
        }

        if (empty($begin_time) && !empty($end_time)){
            $time_where['bs.created_at'] = ['ELT',$end_time];//小于等于
        }

        if (!empty($begin_time) && !empty($end_time)){
            $time_where['bs.created_at'] = ['BETWEEN',"$begin_time,$end_time"];//时间区间
        }


        $pay_type_where = [];
        if (!empty($pay_type)){
            $pay_type_where['br.pay_type'] = ['eq',$pay_type];
        }


        $where = [];
        if (!empty($keyword)){
            $where['bs.suid'] = ["like","%$keyword%"];
        }

        $list = $billSubscriptionModel
            ->alias("bs")
            ->where($where)
            ->where($status_where)
            ->where($time_where)
            ->where($pay_type_where)
            ->order("bs.created_at DESC")
            ->paginate($pagesize,false,$config);

        $list = json_decode(json_encode($list),true);

        $list = _unsetNull($list);

        return $this->com_return(true,config("params.SUCCESS"),$list);
    }


    /**
     * 预约定金收款操作
     * @param Request $request
     */
    public function receipt(Request $request)
    {
        $billSubscriptionModel = new BillSubscription();

        $Authorization = $request->header("Authorization");

        $notifyType    = $request->param('notifyType','adminCallback');//后台支付回调类型参数

        $suid          = $request->param('suid','');

        $payable_amount   = $request->param('payable_amount','');//线上应付且未付金额

        $payable_amount   = $payable_amount * 100;//(以分为单位)

        $pay_type         = $request->param('pay_type','');//支付方式 微信‘wxpay’ 支付宝 ‘alipay’ 线下银行转账 ‘bank’ 现金‘cash’

        $pay_no           = $request->param('pay_no','');//支付回单号


        $pay_name        = $request->param("pay_name","");//付款人或公司名称
        $pay_bank        = $request->param("pay_bank","");//付款方开户行
        $pay_account     = $request->param("pay_account","");//付款方账号
        $pay_bank_time   = $request->param("pay_bank_time",time());//银行转账付款时间或现金支付时间
        $receipt_name    = $request->param("receipt_name","");//收款账户或收款人
        $receipt_bank    = $request->param("receipt_bank","");//收款银行
        $receipt_account = $request->param("receipt_account","");//收款账号

        $pay_user    = $request->param("pay_user","");//代收付款人       有代收人时填写

        $review_desc = $request->param("review_desc","");//审核备注         微信   “微信系统收款”


        $public_rule = [
            'suid|订单号'                      => 'require',
            'payable_amount|付款金额'         => 'require',
            'pay_type|支付方式'                => 'require',
        ];

        $check_public_params = [
            "suid"              => $suid,
            "payable_amount"    => $payable_amount,
            "pay_type"          => $pay_type,
        ];

        $validate = new Validate($public_rule);

        if (!$validate->check($check_public_params)){
            return $this->com_return(false,$validate->getError(),null);
        }

        $time = time();

        if (empty($pay_bank_time)){
            $pay_bank_time = $time;
        }

    }


    /**
     * 预约定金退款操作
     * @param Request $request
     */
    public function refund(Request $request)
    {
        $billSubscriptionModel = new BillSubscription();
    }






    /**
     * 统一模拟支付,组装参数
     * @param $Authorization
     * @param $notifyType
     * @param $vid
     * @param $total_fee
     * @param $cash_fee
     * @param $reason
     * @param string $transaction_id
     * @return mixed
     */
    protected function callBackPay($Authorization,$notifyType,$vid,$total_fee,$cash_fee,$reason,$transaction_id= '')
    {
        $attach = config("order.pay_scene")['reserve']['key'];//充值支付场景

        $values = [
            'attach'         => $attach,
            'notifyType'     => $notifyType,
            'total_fee'      => $total_fee,
            'cash_fee'       => $cash_fee,
            'out_trade_no'   => $vid,
            'transaction_id' => $transaction_id,
            'time_end'       => date("YmdHi",time()),
            'reason'         => $reason
        ];

        $type = "receipt";

        $res = $this->requestPost($type,$Authorization,$values);

        return $res;

    }


    protected function refundPay($Authorization)
    {
        $values = [
            'time_end'       => date("YmdHi",time()),
        ];

        $type = "refund";

        $res = $this->requestPost($type,$Authorization,$values);

        return $res;
    }


    /**
     * 模拟post支付回调接口请求
     * @param $type
     * @param $Authorization
     * @param array $postParams
     * @return bool|mixed
     */
    protected function requestPost($type,$Authorization,$postParams = array())
    {
        $request = Request::instance();

        if ($type == "receipt"){
            $url = $request->domain()."/wechat/notify";
        }elseif($type == "refund"){
            $url = $request->domain()."/wechat/reFund";
        }else{
            return false;
        }

        if (empty($url) || empty($postParams)) {
            return false;
        }

        $o = "";
        foreach ( $postParams as $k => $v )
        {
            $o.= "$k=" . urlencode( $v ). "&" ;
        }

        $postParams = substr($o,0,-1);

        $postUrl  = $url;
        $curlPost = $postParams;

        $header = array();
        $header[] = 'Authorization:'.$Authorization;

        $ch = curl_init();//初始化curl
        curl_setopt($ch, CURLOPT_URL,$postUrl);//抓取指定网页
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);//设置header
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);//要求结果为字符串且输出到屏幕上
        curl_setopt($ch, CURLOPT_POST, 1);//post提交方式
        curl_setopt($ch, CURLOPT_POSTFIELDS, $curlPost);
        $data = curl_exec($ch);//运行curl
        curl_close($ch);

        return $data;
    }
}