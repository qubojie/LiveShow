<?php
/**
 * Created by PhpStorm.
 * User: qubojie
 * Date: 2018/8/20
 * Time: 下午6:05
 */
namespace app\wechat\controller;

use app\wechat\model\BillPay;
use think\Db;
use think\Exception;
use think\Request;

class DishOrderPay extends CommonAction
{
    /**
     * 钱包支付点单订单
     * @param Request $request
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function walletPay(Request $request)
    {
        $pid = $request->param("vid", "");//订单id

        if (empty($pid)) {
            return $this->com_return(false, config("params.ABNORMAL_ACTION"));
        }

        //获取用户信息
        $token = $request->header("Token");

        return $this->walletPayPublic($pid,$token);
    }

    /**
     * 扫码钱包支付
     * @param Request $request
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function qrCodeWalletPay(Request $request)
    {
        $token = $request->header("Token","");
        $pid   = $request->param("vid","");

        $billPayModel = new BillPay();

        $orderInfo = $billPayModel
            ->where("pid", $pid)
            ->find();

        $orderInfo = json_decode(json_encode($orderInfo), true);

        if (empty($orderInfo)) {

            return $this->com_return(false, config("params.ORDER")['ORDER_ABNORMAL']);

        }

        return $this->walletPayPublic($pid,$token);

    }

    /**
     * 钱包支付公共部分
     * @param $pid
     * @param $token
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function walletPayPublic($pid,$token)
    {
        $billPayModel = new BillPay();

        $orderInfo = $billPayModel
            ->where("pid", $pid)
            ->find();

        $orderInfo = json_decode(json_encode($orderInfo), true);

        if (empty($orderInfo)) {

            return $this->com_return(false, "订单异常");

        }

        $sale_status = $orderInfo['sale_status'];

        if ($sale_status == config("order.bill_pay_sale_status")['completed']['key']) {
            //订单已支付
            return $this->com_return(false, config("params.ORDER")['completed']);

        }

        if ($sale_status != config("order.bill_pay_sale_status")['pending_payment_return']['key']){

            return $this->com_return(false, config("params.ORDER")['NOW_STATUS_NOT_PAY']);

        }

        $userInfo = $this->tokenGetUserInfo($token);

        if (empty($userInfo)) {

            return $this->com_return(false, config("params.ABNORMAL_ACTION"));

        }

        $uid  = $userInfo['uid'];

        $trid = $orderInfo['trid'];

        $order_amount = $orderInfo['order_amount'];//订单总金额

        $payable_amount = $orderInfo['payable_amount'];//应付且未付金额

        $account_balance = $userInfo['account_balance'];//用户钱包可用余额

        Db::startTrans();
        try {
            /*用户余额付款操作 on*/
            $reduce_after_balance = $account_balance - $payable_amount;

            if ($reduce_after_balance < 0) {
                return $this->com_return(false, config("params.ORDER")['BALANCE_NOT_ENOUGH']);
            }

            //更改用户余额数据(先把余额扣除后,再去做回调)
            $userBalanceParams = [
                "account_balance" => $reduce_after_balance,
                "updated_at"      => time()
            ];

            $cardCallBackObj = new CardCallback();

            //更新用户余额数据
            $updateUserBalanceReturn = $cardCallBackObj->updateUserInfo($userBalanceParams, $uid);

            if ($updateUserBalanceReturn == false) {
                return $this->com_return(false, config("params.ABNORMAL_ACTION") . "PB001");
            }

            //插入用户余额消费明细
            //余额明细参数
            $insertUserAccountParams = [
                "uid"          => $uid,
                "balance"      => "-" . $payable_amount,
                "last_balance" => $reduce_after_balance,
                "change_type"  => '2',
                "action_user"  => 'sys',
                "action_type"  => config('user.account')['consume']['key'],
                "oid"          => $pid,
                "deal_amount"  => $payable_amount,
                "action_desc"  => config('user.account')['consume']['name'],
                "created_at"   => time(),
                "updated_at"   => time()
            ];

            //插入用户余额消费明细
            $insertUserAccountReturn = $cardCallBackObj->updateUserAccount($insertUserAccountParams);

            if ($insertUserAccountReturn == false) {
                return $this->com_return(false, config("params.ABNORMAL_ACTION") . "PB002");
            }
            /*用户余额付款操作 off*/

            //订单支付回调
            $wechatPayObj = new WechatPay();

            $payCallBackParams = [
                "out_trade_no"   => $pid,
                "cash_fee"       => $payable_amount * 100,
                "total_fee"      => $order_amount * 100,
                "transaction_id" => "",
                "pay_type"       => config("order.pay_method")['balance']['key']
            ];

            $payCallBackReturn = $wechatPayObj->pointListNotify($payCallBackParams,"");

            $payCallBackReturn = json_decode(json_encode(simplexml_load_string($payCallBackReturn, 'SimpleXMLElement', LIBXML_NOCDATA)), true);

            if ($payCallBackReturn["return_code"] != "SUCCESS" || $payCallBackReturn["return_msg"] != "OK"){
                //回调失败
                return $this->com_return(false,$payCallBackReturn["return_msg"]);
            }

            Db::commit();
            return $this->com_return(true,config("params.SUCCESS"));

        } catch (Exception $e) {
            Db::rollback();
            return $this->com_return(false, $e->getMessage());
        }
    }

    /**
     * 礼金支付
     * @param Request $request
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function cashGiftPay(Request $request)
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

        //获取用户信息
        $token = $request->header("Token");

        $userInfo = $this->tokenGetUserInfo($token);

        if (empty($userInfo)) {

            return $this->com_return(false, config("params.ABNORMAL_ACTION"));

        }

        $uid  = $userInfo['uid'];

        $trid = $orderInfo['trid'];

        $order_amount = $orderInfo['order_amount'];//订单总金额

        $payable_amount = $orderInfo['payable_amount'];//应付且未付金额

        $account_cash_gift = $userInfo['account_cash_gift'];//用户礼金余额

        Db::startTrans();
        try{
            /*用户余额付款操作 on*/
            $reduce_after_cash_gift = $account_cash_gift - $payable_amount;

            if ($reduce_after_cash_gift < 0) {

                return $this->com_return(false, config("params.ORDER")['GIFT_NOT_ENOUGH']);

            }

            //更改用户余额数据(先把余额扣除后,再去做回调)
            $userBalanceParams = [
                "account_cash_gift" => $reduce_after_cash_gift,
                "updated_at"      => time()
            ];

            $cardCallBackObj = new CardCallback();

            //更新用户礼金账户数据
            $updateUserBalanceReturn = $cardCallBackObj->updateUserInfo($userBalanceParams, $uid);

            if ($updateUserBalanceReturn == false) {
                return $this->com_return(false, config("params.ABNORMAL_ACTION") . "PC001");
            }

            //插入用户礼金消费明细
            //礼金明细参数
            $insertUserAccountCashGiftParams = [
                "uid"          => $uid,
                "cash_gift"    => "-" . $payable_amount,
                "last_cash_gift" => $reduce_after_cash_gift,
                "change_type"  => '2',
                "action_user"  => 'sys',
                "action_type"  => config('user.gift_cash')['consume']['key'],
                "oid"          => $pid,
                "action_desc"  => config('user.gift_cash')['consume']['name'],
                "created_at"   => time(),
                "updated_at"   => time()
            ];

            //插入用户礼金消费明细
            $insertUserAccountReturn = $cardCallBackObj->updateUserAccountCashGift($insertUserAccountCashGiftParams);

            if ($insertUserAccountReturn == false) {
                return $this->com_return(false, config("params.ABNORMAL_ACTION") . "PC002");
            }

            /*用户余额付款操作 off*/

            //订单支付回调
            $wechatPayObj = new WechatPay();

            $payCallBackParams = [
                "out_trade_no"   => $pid,
                "cash_fee"       => $payable_amount * 100,
                "total_fee"      => $order_amount * 100,
                "transaction_id" => "",
                "pay_type"       => config("order.pay_method")['cash_gift']['key']
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

    /**
     * 我的订单列表支付,更改支付方式
     * @param Request $request
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function changePayType(Request $request)
    {
        $pid      = $request->param("pid","");
        $pay_type = $request->param("pay_type","");

        if (empty($pid)){
            return $this->com_return(false,config("params.ABNORMAL_ACTION"));
        }

        if (empty($pay_type)){
            return $this->com_return(false,config("params.ORDER")['PAY_TYPE_EMPTY']);
        }

        $billPayModel = new BillPay();

        $is_exist = $billPayModel
            ->where("pid",$pid)
            ->where("sale_status",config("order.bill_pay_sale_status")['pending_payment_return']['key'])
            ->find();

        $is_exist = json_decode(json_encode($is_exist),true);

        if (empty($is_exist)){
            return $this->com_return(false,config("params.ORDER")['NOW_STATUS_NOT_PAY']);
        }

        if ($pay_type == config("order.pay_method")['offline']['key']){
            $params = [
                "pay_type"    => $pay_type,
                "sale_status" => config("order.bill_pay_sale_status")['wait_audit']['key'],
            ];
        }else{
            $params = [
                "pay_type" => $pay_type
            ];
        }

        $is_ok = $billPayModel
            ->where("pid",$pid)
            ->update($params);

        if ($is_ok !== false){
            return $this->com_return(true,config("params.SUCCESS"),$is_exist);
        }else{
            return $this->com_return(false,config("params.FAIL"));
        }
    }
}
