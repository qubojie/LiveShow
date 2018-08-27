<?php
/**
 * Created by PhpStorm.
 * User: qubojie
 * Date: 2018/8/26
 * Time: 下午3:34
 */
namespace app\wechat\controller;

use app\common\controller\UUIDUntil;
use app\wechat\model\BillPayDetail;
use app\wechat\model\BillPay;
use think\Db;
use think\Exception;
use think\Request;
use think\Validate;

class ManageRefundList extends HomeAction
{
    /**
     * 退单
     * @param Request $request
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function refundOrder(Request $request)
    {
        $type          = $request->param("type","");//退单类型 为1时则是整单退
        $pid           = $request->param("pid","");//退单id
        $cancel_reason = $request->param("cancel_reason","");//退单原因
        $trid          = $request->param("trid","");//桌台预约id

        $detail_dis_info     = $request->param("detail_dis_info","");//

        $rule = [
            "pid|退单id"          => "require",
        ];

        $check_data = [
            "pid"     => $pid,
        ];

        $validate = new Validate($rule);

        if (!$validate->check($check_data)){
            return $this->com_return(false,$validate->getError());
        }


        $token         = $request->header("Token","");

        $manageInfo    = $this->tokenGetManageInfo("$token");

        $cancel_user   = $manageInfo['sales_name'];

        if ($type){
            //整单都退
            return $this->allRefundOrder($pid,$cancel_user,$cancel_reason,$trid);

        }else{
            //单品退单
            if (empty($detail_dis_info)){
                return $this->com_return(false,config("params.PARAM_NOT_EMPTY"));
            }

            return $this->oneRefundOrder($pid,$detail_dis_info,$cancel_user,$cancel_reason,$trid);
        }


    }


    /**
     * 单品退菜操作
     * @param $pid
     * @param $detail_dis_info
     * @param $cancel_user
     * @param $cancel_reason
     * @param $trid
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    protected function oneRefundOrder($pid,$detail_dis_info,$cancel_user,$cancel_reason,$trid)
    {

        $billPayModel = new BillPay();

        $billPayInfo = $billPayModel
            ->where("pid",$pid)
            ->find();

        $billPayInfo = json_decode(json_encode($billPayInfo),true);


        if (empty($billPayInfo)){

            return $this->com_return(false,config("params.ORDER")['REFUND_ABNORMAL']);

        }

        $billDetailModel = new BillPayDetail();

        $id_arr = json_decode($detail_dis_info,true);

        $order_amount = 0;

        foreach ($id_arr as $ids){

            $id = $ids['detail_id'];

            $quantity_r = $ids['quantity'];

            $dis_detail = $billDetailModel
                ->where("id",$id)
                ->where('pid',$pid)
                ->find();

            $dis_detail = json_decode(json_encode($dis_detail),true);

            $is_refund = $dis_detail['is_refund'];

            if ($is_refund){
                return $this->com_return(false,config("params.ORDER")['REFUND_ABNORMAL']);
            }

            $order_amount += $dis_detail['price'] * $quantity_r;
        }

        $return_point = intval($order_amount *  0.1);

        $uuid = new UUIDUntil();

        $new_pid = $uuid->generateReadableUUID("P");

        $billPayParams = [
            "pid"               => $new_pid,
            "trid"              => $billPayInfo['trid'],
            "uid"               => $billPayInfo['uid'],
            "sid"               => $billPayInfo['sid'],
            "type"              => config("order.bill_pay_type")['retire_dish']['key'],
            "sale_status"       => config("order.bill_pay_sale_status")['wait_audit']['key'],
            "deal_time"         => 0,
            "cancel_user"       => $cancel_user,
            "cancel_time"       => time(),
            "cancel_reason"     => $cancel_reason,
            "order_amount"      => '-'.$order_amount,
            "deal_amount"       => '-'.$order_amount,
            "return_point"      => '-'.$return_point,
            "pay_type"          => $billPayInfo['pay_type'],
            "pay_offline_type"  => $billPayInfo['pay_offline_type'],
            "pay_no"            => $billPayInfo['pay_no'],
            "receipt_account"   => $billPayInfo['receipt_account'],
            "created_at"        => time(),
            "updated_at"        => time()
        ];

        Db::startTrans();
        try{
            //将数据写入订单表 bill_pay
            $billPayModel = new BillPay();

            $is_ok = $billPayModel
                ->insert($billPayParams);

            if (!$is_ok){
                return $this->com_return(false.config("params.ABNORMAL_ACTION")."QX001");
            }

            //插入明细 bill_pay_detail
            foreach ($id_arr as $ids){
                $id       = $ids['detail_id'];
                $quantity = '-'.$ids['quantity'];

                $dis_detail = $billDetailModel
                    ->where("id",$id)
                    ->where('pid',$pid)
                    ->find();

                $dis_detail = json_decode(json_encode($dis_detail),true);

                $dis_type  = $dis_detail['dis_type'];

                $is_refund = $dis_detail['is_refund'];

                if ($is_refund){
                    return $this->com_return(false,config("params.ORDER")['REFUND_ABNORMAL']);
                }

                $disParams = [
                    "pid"       => $new_pid,
                    "parent_id" => 0,
                    "trid"      => $dis_detail["trid"],
                    "is_refund" => 1,
                    "is_give"   => $dis_detail['is_give'],
                    "dis_id"    => $dis_detail['dis_id'],
                    "dis_type"  => $dis_type,
                    "dis_sn"    => $dis_detail['dis_sn'],
                    "dis_name"  => $dis_detail['dis_name'],
                    "dis_desc"  => $dis_detail['dis_desc'],
                    "quantity"  => $quantity,
                    "price"     => $dis_detail['price'],
                    "amount"    => $dis_detail['price'] * $quantity,
                ];

                $bill_detail_insert_id = $billDetailModel
                    ->insertGetId($disParams);

                if (!$bill_detail_insert_id){
                    return $this->com_return(false,config("params.ABNORMAL_ACTION")."QX002");
                }
                if ($dis_type){
                    //此时为套餐,获取当前套餐内单品信息
                    $dis_child_detail = $billDetailModel
                        ->where("parent_id",$id)
                        ->select();

                    $dis_child_detail = json_decode(json_encode($dis_child_detail),true);

                    foreach ($dis_child_detail as $k => $v){

                        $v_quantity = '-'.$v['quantity'];

                        $disParams_l = [
                            "pid"       => $new_pid,
                            "parent_id" => $bill_detail_insert_id,
                            "trid"      => $dis_detail["trid"],
                            "is_refund" => 1,
                            "is_give"   => $v['is_give'],
                            "dis_id"    => $v['dis_id'],
                            "dis_type"  => $v['dis_type'],
                            "dis_sn"    => $v['dis_sn'],
                            "dis_name"  => $v['dis_name'],
                            "dis_desc"  => $v['dis_desc'],
                            "quantity"  => $v_quantity,
                            "price"     => $v['price'],
                            "amount"    => $v['price'] * $v_quantity,
                        ];

                        $bill_detail_insert = $billDetailModel
                            ->insertGetId($disParams_l);

                        if (!$bill_detail_insert){

                            return $this->com_return(false,config("params.ABNORMAL_ACTION")."QX003");

                        }
                    }
                }
            }

            //此时数据插入成功,调起打印机 开始落单,提示菜品已退
            $is_print = $this->refundToPrintYly($pid,$detail_dis_info);

            $dateTimeFile = APP_PATH."index/PrintOrderYly/".date("Ym")."/";

            if (!is_dir($dateTimeFile)){
                $res = mkdir($dateTimeFile,0777,true);
            }

            //打印结果日志
            error_log(date('Y-m-d H:i:s').var_export($is_print,true),3,$dateTimeFile.date("d").".log");

            Db::commit();

            return $this->com_return(true,config("params.SUCCESS"));

        }catch (Exception $e) {
            Db::rollback();
            return $this->com_return(false, $e->getMessage());
        }
    }

    //整单全退操作
    protected function allRefundOrder($pid,$cancel_user,$cancel_reason,$trid)
    {
        $billPayModel = new BillPay();

        $orderInfo = $billPayModel
            ->where("pid",$pid)
            ->find();

        $orderInfo = json_decode(json_encode($orderInfo),true);

        if (empty($orderInfo)){

            return $this->com_return(false,config("params.ORDER")['ORDER_ABNORMAL']);

        }

        $type = $orderInfo['type'];

        if ($type == config("order.bill_pay_type")['retire_order']['key'] || $type == config("order.bill_pay_type")['give']['key']) {
            //如果是已退单 或者 赠送单 则不允许退单
            return $this->com_return(false,config("params.ORDER")['ORDER_NOT_REFUND']);
        }

        $sale_status = $orderInfo['sale_status'];

        if ($sale_status != config("order.bill_pay_sale_status")['completed']['key']){
            //如果退单时,订单不是已支付落单状态,是不允许退单操作的
            return $this->com_return(false,config("params.ORDER")['NOW_STATUS_ERROR']);

        }

        $uuid = new UUIDUntil();

        $new_pid = $uuid->generateReadableUUID("P");


        //可以执行退单操作
        $params = [
            "pid"               => $new_pid,
            "trid"              => $orderInfo['trid'],
            "uid"               => $orderInfo['uid'],
            "sid"               => $orderInfo["sid"],
            "type"              => config("order.bill_pay_type")['retire_order']['key'],
            "sale_status"       => config("order.bill_pay_sale_status")['wait_audit']['key'],
            "cancel_user"       => $cancel_user,
            "cancel_time"       => time(),
            "auto_cancel"       => 0,
            "cancel_reason"     => $cancel_reason,
            "order_amount"      => '-'.$orderInfo['order_amount'],
            "payable_amount"    => '-'.$orderInfo['payable_amount'],
            "account_balance"   => '-'.$orderInfo['account_balance'],
            "account_cash_gift" => '-'.$orderInfo['account_cash_gift'],
            "discount"          => '-'.$orderInfo['discount'],
            "deal_amount"       => '-'.$orderInfo['deal_amount'],
            "gift_vou_code"     => $orderInfo['gift_vou_code'],
            "return_point"      => $orderInfo['return_point'],
            "pay_type"          => $orderInfo['pay_type'],
            "pay_offline_type"  => $orderInfo['pay_offline_type'],
            "pay_no"            => $orderInfo['pay_no'],
            "receipt_account"   => $orderInfo['receipt_account'],
            "is_settlement"     => $orderInfo['is_settlement'],
            "settlement_time"   => $orderInfo['settlement_time'],
            "settlement_id"     => $orderInfo['settlement_id'],
            "created_at"        => time(),
            "updated_at"        => time()
        ];

        Db::startTrans();
        try{

            //更新订单状态为退单待审核状态
            $updateBillPayReturn = $billPayModel
                ->insert($params);

            if ($updateBillPayReturn == false){
                return $this->com_return(false,config("params.FAIL"));
            }

            $billDetailModel = new BillPayDetail();
            //获取当前订单下菜品
            $dishDetailInfo = $billDetailModel
                ->where("pid",$pid)
                ->where("is_refund","0")
                ->select();

            $dishDetailInfo = json_decode(json_encode($dishDetailInfo),true);


            for ($i = 0; $i < count($dishDetailInfo); $i ++){

                $quantity = $dishDetailInfo[$i]['quantity'];
                $price    = $dishDetailInfo[$i]['price'];
                $amount   = $dishDetailInfo[$i]['amount'];

                if ($price > 0){
                    $price = '-'.$price;
                }
                if ($amount > 0){
                    $amount = '-'.$amount;
                }

                $dishParams = [
                    "parent_id" => $dishDetailInfo[$i]['parent_id'],
                    "pid"       => $new_pid,
                    "trid"      => $dishDetailInfo[$i]['trid'],
                    "is_refund" => 1,
                    "is_give"   => $dishDetailInfo[$i]['is_give'],
                    "dis_id"    => $dishDetailInfo[$i]['dis_id'],
                    "dis_type"  => $dishDetailInfo[$i]['dis_type'],
                    "dis_sn"    => $dishDetailInfo[$i]['dis_sn'],
                    "dis_name"  => $dishDetailInfo[$i]['dis_name'],
                    "dis_desc"  => $dishDetailInfo[$i]['dis_desc'],
                    "quantity"  => $quantity,
                    "price"     => $price,
                    "amount"    => $amount,
                ];

                $res = $billDetailModel
                    ->insert($dishParams);

                if ($res == false){
                    return $this->com_return(false,config("params.SUCCESS"));
                }
            }

            Db::commit();
            return $this->com_return(true,config("params.ORDER")['REFUND_WAIT_AUDIT']);

        }catch (Exception $e){
            Db::rollback();
            return $this->com_return(false,$e->getMessage());
        }

    }
}