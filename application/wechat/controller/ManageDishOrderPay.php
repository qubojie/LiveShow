<?php
/**
 * Created by PhpStorm.
 * User: qubojie
 * Date: 2018/8/31
 * Time: 上午11:04
 */
namespace app\wechat\controller;

use app\wechat\model\BillPay;
use think\Db;
use think\Exception;
use think\Request;

class ManageDishOrderPay extends HomeAction
{
    /**
     * 线下支付
     * @param Request $request
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function offlinePay(Request $request)
    {
        $pid = $request->param("vid", "");//订单id

        if (empty($pid)) {
            return $this->com_return(false, config("params.ABNORMAL_ACTION"));
        }

        $billPayModel = new BillPay();

        $orderInfo = $billPayModel
            ->where("pid", $pid)
            ->find();

        $orderInfo = json_decode(json_encode($orderInfo), true);

        if (empty($orderInfo)) {
            return $this->com_return(false, "订单异常");
        }

        $sale_status = $orderInfo['sale_status'];

        if ($sale_status != config("order.bill_pay_sale_status")['pending_payment_return']['key']) {

            return $this->com_return(false, config("params.ORDER")['NOW_STATUS_NOT_PAY']);

        }

        $uid  = $orderInfo['uid'];
        $trid = $orderInfo['trid'];

        $order_amount   = $orderInfo['order_amount'];//订单总金额
        $payable_amount = $orderInfo['payable_amount'];//应付且未付金额

        Db::startTrans();
        try {
            //订单支付回调
            $wechatPayObj = new WechatPay();

            $payCallBackParams = [
                "out_trade_no"   => $pid,
                "cash_fee"       => $payable_amount * 100,
                "total_fee"      => $order_amount * 100,
                "transaction_id" => "",
                "pay_type"       => config("order.pay_method")['offline']['key']
            ];

            $payCallBackReturn = $wechatPayObj->pointListNotify($payCallBackParams,"");

            $payCallBackReturn = json_decode(json_encode(simplexml_load_string($payCallBackReturn, 'SimpleXMLElement', LIBXML_NOCDATA)), true);

            if ($payCallBackReturn["return_code"] != "SUCCESS" || $payCallBackReturn["return_msg"] != "OK"){

                //回调失败
                return $this->com_return(false,$payCallBackReturn["return_msg"]);

            }

            Db::commit();
            return $this->com_return(true,config("params.SUCCESS"));

        }catch (Exception $e){
            Db::rollback();
            return $this->com_return(false,$e->getMessage());
        }
    }
}