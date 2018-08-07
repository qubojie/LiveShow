<?php

namespace app\wechat\controller;
header('Content-Type:text/html;charset=utf-8');/*设置php编码为utf-8*/

header('Access-Control-Allow-Origin:*');
use app\admin\controller\CommandAction;
use app\admin\controller\Common;
use app\admin\model\MstCardVip;
use app\common\controller\UUIDUntil;
use app\services\Sms;
use app\wechat\model\BillCardFees;
use app\wechat\model\BillRefill;
use app\wechat\model\BillSubscription;
use think\Controller;
use think\Db;
use think\Env;
use think\Exception;
use think\Log;
use think\Request;
use wxpay\JsapiPay;
use wxpay\MicroPay;
use wxpay\Notify;
use wxpay\Refund;
use wxpay\WapPay;

class WechatPay extends Controller
{
    /**
     * H5支付
     * @param Request $request
     * @return array|string
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function wappay(Request $request)
    {
        $common = new Common();
        $vid = $request->param("vid","");
        if (empty($vid)){
            return $common->com_return(false,'订单号不能为空');
        }

        //获取订单金额
        $payable_amount = $this->getOrderPayableAmount($vid);

        if ($payable_amount === false){
            return $common->com_return(false,'订单有误');
        }

        $params = [
            'body'          => Env::get("PAY_BODY"),
            'out_trade_no'  => $vid,
            'total_fee'     => $payable_amount * 100
        ];

        $redirect_url = Env::get("WEB_DOMAIN_NAME").'page/orderspay.html';


        $result = WapPay::getPayUrl($params,$redirect_url);

        return $result;
    }

    /**
     * 公众号支付
     * @param Request $request
     * @return array|\json数据，可直接填入js函数作为参数
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function jspay(Request $request)
    {

        $common = new Common();
        $vid = $request->param("vid","");

        if (empty($vid)){
            return $common->com_return(false,'订单号不能为空');
        }

        //获取订单金额
        $payable_amount = $this->getOrderPayableAmount($vid);

        if ($payable_amount == false){
            return $common->com_return(false,'订单有误');
        }

        $params = [
            'body'          => Env::get("PAY_BODY"),
            'out_trade_no'  => $vid,
            'total_fee'     => $payable_amount * 100
        ];

        if (isset($_GET['code'])){
            $code = $_GET['code'];
        }else{
            $code = '';
        }

        $result = JsapiPay::getPayParams($params,$code);
        return $result;
    }

    /**
     * 小程序支付
     * @param Request $request
     * @return array|\json数据，可直接填入js函数作为参数
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function smallapp(Request $request)
    {
        $vid    = $request->param("vid","");

        $openId = $request->param('openid','');

        $scene  = $request->param("scene","");//支付场景

        if (empty($vid)){
            return $this->com_return(false,'订单号不能为空');
        }


        /*if ($scene == config("order.pay_scene")['open_card']['key']){

            //获取开卡订单金额
            $payable_amount = $this->getOrderPayableAmount($vid);

        }

        if ($scene == config("order.pay_scene")['reserve']['key']){

            //这里去处理预约定金回调逻辑
            //获取订台金额
            $payable_amount = $this->getSubscriptionPayableAmount($vid);

        }


        if ($scene == config("order.pay_scene")['recharge']['key']){

            //这里去处理预约定金回调逻辑
            //获取订台金额
            //$payable_amount = $this->getSubscriptionPayableAmount($vid);

        }*/


        $headL= substr($vid,0,2);


        if ($headL == "SU"){
            //这里去处理预约定金回调逻辑
            //获取订台金额
            $payable_amount = $this->getSubscriptionPayableAmount($vid);


        }elseif ($headL == "RF"){

            //获取充值金额
            $payable_amount = $this->getBillRefillAmount($vid);
            Log::info("充值金额 --- ".$payable_amount);

        }else{
            //这里去处理开卡回调逻辑

            //获取开卡订单金额
            $payable_amount = $this->getOrderPayableAmount($vid);

        }

        if ($payable_amount == false){
            return $this->com_return(false,'订单有误');
        }

        $params = [
            'body'         => Env::get("PAY_BODY"),
            'out_trade_no' => $vid,
            'total_fee'    => $payable_amount * 100,
        ];

        Log::info("充值组装参数 --- ".var_export($params,true));

        $result = JsapiPay::getParams2($params,$openId,$scene);

        return $result;
    }

    /**
     * 订单查询
     * @param Request $request
     * @return array
     * @throws \WxPayException
     */
    public function query(Request $request)
    {
        if ($request->isOptions()){
            return $this->com_return(true,'预请求');
        }
        $vid = $request->param('vid','');
        $result = \wxpay\Query::exec($vid);
        return $result;
    }

    /**
     * 微信退款
     */
    public function reFund(Request $request)
    {
        if ($request->isOptions()){
            return $this->com_return(true,'预请求');
        }
        $vid           = $request->param('vid','');
        $total_fee     = $request->param('total_fee','');
        $refund_fee    = $request->param('refund_fee','');
        $out_refund_no = $request->param('out_refund_no','');

        $total_fee = $total_fee * 100;
        $refund_fee = $refund_fee * 100;

        $params = [
            "out_trade_no"  => $vid,
            "total_fee"     => $total_fee,
            "refund_fee"    => $refund_fee,
            "out_refund_no" => $out_refund_no,
        ];

        $result = \wxpay\Refund::exec($params);

        //如果退款成功返回
        /*array(18) {
              ["appid"] => string(18) "wx946331ee6f54ddf8"
              ["cash_fee"] => string(3) "200"
              ["cash_refund_fee"] => string(3) "200"
              ["coupon_refund_count"] => string(1) "0"
              ["coupon_refund_fee"] => string(1) "0"
              ["mch_id"] => string(10) "1507786841"
              ["nonce_str"] => string(16) "EDnxDxfNVZTNmv7B"
              ["out_refund_no"] => string(28) "4200000137201807252214503179"
              ["out_trade_no"] => string(20) "V18072515142485277C6"
              ["refund_channel"] => array(0) {
              }
              ["refund_fee"] => string(3) "200"
              ["refund_id"] => string(29) "50000307492018072605818207474"
              ["result_code"] => string(7) "SUCCESS"
              ["return_code"] => string(7) "SUCCESS"
              ["return_msg"] => string(2) "OK"
              ["sign"] => string(32) "A0D0D23A4C82780E47C0BABFB2752EEE"
              ["total_fee"] => string(3) "200"
              ["transaction_id"] => string(28) "4200000137201807252214503179"
        }*/

        if (isset($result['return_code']) && $result['return_msg'] == "OK"){
            $cash_fee        = $result['cash_fee'];
            $cash_refund_fee = $result['cash_refund_fee'];
            $refund_fee      = $result['refund_fee'];
            $refund_id       = $result['refund_id'];
            $transaction_id  = $result['transaction_id'];
            return $this->com_return(true,config("params.SUCCESS"));
        }else{
            return $this->com_return(false,config("params.FAIL"));
        }
    }

    /**
     * 下载对账单
     * @param Request $request
     * @return array
     */
    public function download(Request $request)
    {
        $common = new Common();
        $date = $request->param('date',date("Ymd"));//格式为 20080808,当天的不可查询
        if ($date == date("Ymd")){
            return $common->com_return(false,'当天账单不可查');
        }
        $result = \wxpay\DownloadBill::exec($date);
        return ($result);
    }

    /**
     * 支付回调
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function notify()
    {
//        $notify = new Notify();
//        $notify->Handle();

        $notifyType = $this->request->param('notifyType',"");

        $attach     = $this->request->param('attach',"");//支付场景包名

        if ($notifyType == 'adminCallback'){

            $values = $this->request->param();

        }else{

            $xml = file_get_contents("php://input");
            libxml_disable_entity_loader(true);
            $values= json_decode(json_encode(simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA)), true);
        }


        $order_id = $values['out_trade_no'];

        /*$attach   = $values['attach'];//获取回调支付包名

        if ($attach == config("order.pay_scene")['open_card']['key']){

            //这里去处理开卡回调逻辑
            $res = $this->openCardNotify($values,$notifyType);
            echo $res;die;

        }

        if ($attach == config("order.pay_scene")['reserve']['key']){

            //这里去处理预约定金回调逻辑
            //获取订台金额
            $res = $this->payDeposit($values,$notifyType);
            echo $res;die;

        }


        if ($attach == config("order.pay_scene")['recharge']['key']){

            //这里去处理预约定金回调逻辑
            //获取订台金额
        }*/




        $headL= substr($order_id,0,2);

        if ($headL == "SU"){
            //这里去处理预约定金回调逻辑

            $res = $this->payDeposit($values,$notifyType);
            echo $res;die;

        }else{
            if ($headL == "RF"){
                //这里去处理充值回调逻辑
                $res = $this->recharge($values,$notifyType);
                echo $res;die;

            }else{
                //这里去处理开卡回调逻辑
                $res = $this->openCardNotify($values,$notifyType);
                echo $res;die;
            }

        }
    }

    /**
     * 充值回调
     * @param array $values
     * @param string $notyfyType
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    protected function recharge($values = array(),$notyfyType = "")
    {
        $rfid = $values['out_trade_no'];

        /*'appid' => 'wxf23099114472fbe6',
          'attach' => '公众号支付',
          'bank_type' => 'COMM_CREDIT',
          'cash_fee' => '100',
          'fee_type' => 'CNY',
          'is_subscribe' => 'N',
          'mch_id' => '1507786841',
          'nonce_str' => 'jqdzarqu48pmlmfa24qpom6nn0s5oyol',
          'openid' => 'oDgH15SkR5bOqfoG2CS4iKJXndN0',
          'out_trade_no' => 'V1807161054462077A6F',
          'result_code' => 'SUCCESS',
          'return_code' => 'SUCCESS',
          'sign' => '51B15A80BDA18F37FD1C32D3D72EFE2A',
          'time_end' => '20180716105502',
          'total_fee' => '100',
          'trade_type' => 'JSAPI',
          'transaction_id' => '4200000122201807160565649815',*/

        //获取订单信息
        $order_info = Db::name("bill_refill")
            ->where('rfid',$rfid)
            ->find();

        if (empty($order_info)){
            echo '<xml> <return_code><![CDATA[FAIL]]></return_code> <return_msg><![CDATA[订单不存在!!!]]></return_msg> </xml>';
            die;
        }

        $status = $order_info['status'];

        if ($status == '1'){
            echo '<xml> <return_code><![CDATA[FAIL]]></return_code> <return_msg><![CDATA[订单已支付]]></return_msg> </xml>';
            die;
        }

        $uid       = $order_info['uid'];
        $cash_gift = $order_info['cash_gift'];//赠送礼金数

        $time = time();


        $userOldMoneyInfo = Db::name('user')
            ->where('uid',$uid)
            ->field('account_balance,account_deposit,account_cash_gift,account_point')
            ->find();
//        dump($userOldMoneyInfo);die;

        //用户钱包可用余额
        $account_balance = $userOldMoneyInfo['account_balance'];

        //用户钱包押金余额
        $account_deposit = $userOldMoneyInfo['account_deposit'];

        //用户礼金余额
        $account_cash_gift = $userOldMoneyInfo['account_cash_gift'];

        $rechargeCallBackObj  = new RechargeCallBack();

        $cardCallBackObj      = new CardCallback();

        Db::startTrans();

        try{
            $cash_fee       = $values['cash_fee'] / 100;
            $total_fee      = $values['total_fee'] / 100;
            $out_trade_no   = $values['out_trade_no'];
            $transaction_id = $values['transaction_id'];

            //更新充值单据状态
            $updateBillRefillParams = [
                "pay_type"   => config("order.pay_method")['wxpay']['key'],
                "pay_time"   => $time,
                "pay_no"     => $transaction_id,
                "amount"     => $cash_fee,
                "status"     => config("order.recharge_status")['completed']['key'],
                "review_time"=> $time,
                "review_user"=> "系统自动",
                "review_desc"=>"微信系统收款",
                "updated_at" => $time
            ];

            //更新用户充值单据状态
            $updateRechargeReturn  = $rechargeCallBackObj->updateBillRefill($updateBillRefillParams,"$out_trade_no");

            //更新用户余额参数
            $updateUserParams = [
                "account_balance"   => $cash_fee + $account_balance,
                "account_cash_gift" => $account_cash_gift + $cash_gift,
                "updated_at"        =>  $time
            ];

            //更新用户余额数据
            $updateUserReturn        = $cardCallBackObj->updateUserInfo($updateUserParams,$uid);

            //余额明细参数
            $insertUserAccountParams = [
                "uid"          => $uid,
                "balance"      => $cash_fee,
                "last_balance" => $cash_fee + $account_balance,
                "change_type"  => '2',
                "action_user"  => 'sys',
                "action_type"  => config('user.account')['recharge']['key'],
                "oid"          => $rfid,
                "deal_amount"  => $cash_fee,
                "action_desc"  => "余额充值",
                "created_at"   => $time,
                "updated_at"   => $time
            ];

            //插入用户充值明细
            $insertUserAccountReturn = $cardCallBackObj->updateUserAccount($insertUserAccountParams);

            if ($cash_gift > 0){
                //如果礼金数额大于0 则插入用户充值明细

                //变动后的礼金总余额
                $last_cash_gift = $cash_gift + $account_cash_gift;

                $userAccountCashGiftParams = [
                    'uid'            => $uid,
                    'cash_gift'      => $cash_gift,
                    'last_cash_gift' => $last_cash_gift,
                    'change_type'    => '2',
                    'action_user'    => 'sys',
                    'action_type'    => config('user.gift_cash')['recharge_give']['key'],
                    'action_desc'    => config('user.gift_cash')['recharge_give']['name'],
                    'oid'            => $rfid,
                    'created_at'     => $time,
                    'updated_at'     => $time
                ];

                //给用户添加礼金明细
                $userAccountCashGiftReturn = $cardCallBackObj->updateUserAccountCashGift($userAccountCashGiftParams);
            }else{
                $userAccountCashGiftReturn = true;
            }

            if ($updateRechargeReturn && $updateUserReturn && $insertUserAccountReturn && $userAccountCashGiftReturn){
                Db::commit();
                Log::info("充值支付回调成功");

                //如果是后台操作订单支付成功,记录相关操作日志
                $adminCommonAction = new CommandAction();

                $action  = config("useraction.deal_pay")['key'];

                $reason = $values['reason'];//操作原因描述

                $adminToken = $this->request->header("Authorization","");

                //获取当前登录管理员
                $action_user = $this->getLoginAdminId($adminToken)['user_name'];

                $adminCommonAction->addSysAdminLog("$uid","","$rfid","$action","$reason","$action_user","$time");


                echo '<xml> <return_code><![CDATA[SUCCESS]]></return_code> <return_msg><![CDATA[OK]]></return_msg> </xml>';
                die;
            }else{
                echo '<xml> <return_code><![CDATA[FAIL]]></return_code> <return_msg><![CDATA[出错了]]></return_msg> </xml>';
                die;
            }
        }catch (Exception $e){
            Log::info("充值支付回调出错----- ".$e->getMessage());
            Db::rollback();
            echo '<xml> <return_code><![CDATA[FAIL]]></return_code> <return_msg><![CDATA['.$e->getMessage().']]></return_msg> </xml>';
            die;
        }
    }

    /**
     * 预约定金缴纳回调
     * @param array $values
     * @param string $notyfyType
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    protected function payDeposit($values = array(),$notyfyType = "")
    {
        $suid = $values['out_trade_no'];

        //订单信息
        $order_info = Db::name("bill_subscription")
            ->where('suid',$suid)
            ->find();

        if (empty($order_info)){
            echo '<xml> <return_code><![CDATA[FAIL]]></return_code> <return_msg><![CDATA[订单不存在!!!]]></return_msg> </xml>';
            die;
        }

        $status = $order_info['status'];

        if ($status == '1'){
            echo '<xml> <return_code><![CDATA[FAIL]]></return_code> <return_msg><![CDATA[订单已支付]]></return_msg> </xml>';
            die;
        }

        $uid  = $order_info['uid'];
        $trid = $order_info['trid'];

        $pay_no = $values['transaction_id'];//支付回单号
        $time = time();

        $subscriptionCallBackObj = new SubscriptionCallBack();

        Db::startTrans();
        try{
            //更新定金缴费单状态
            $updateBillSubscriptionParams = [
                "status"     => config("order.reservation_subscription_status")['Paid']['key'],
                "pay_time"   => $time,
                "pay_type"   => config("order.pay_method")['wxpay']['key'],
                "pay_no"     => $pay_no,
                "updated_at" => $time
            ];
            $changeBillSubscriptionReturn = $subscriptionCallBackObj->changeBillSubscriptionInfo($updateBillSubscriptionParams,$suid);


            //更新台位预定信息表中台位状态
            $updateTableRevenueParams = [
                "status"            => config("order.table_reserve_status")['reserve_success']['key'],
                "subscription_time" => $time,
                "updated_at"        => $time
            ];
            $changeTableRevenueReturn = $subscriptionCallBackObj->changeTableRevenueInfo($updateTableRevenueParams,$trid,$uid);

            if ($changeBillSubscriptionReturn && $changeTableRevenueReturn){

                //根据订单,查看是否是服务人员预定
                $reserve_way_res = Db::name("table_revenue")
                    ->where("trid",$trid)
                    ->field("reserve_way")
                    ->find();
                $reserve_way_res = json_decode(json_encode($reserve_way_res),true);

                $reserve_way = $reserve_way_res['reserve_way'];

                if ($reserve_way == config("order.reserve_way")['service']['key'] || $reserve_way == config("order.reserve_way")['manage']['key']){
                    //调起短信推送
                    //获取用户电话
                    $authObj = new Auth();
                    $userInfo = $authObj->getUserInfo($uid);
                    $userInfo = json_decode(json_encode($userInfo),true);
                    $phone = $userInfo['phone'];

                    $smsObj = new Sms();

                    $smsObj->sendMsg($phone);
                }

                Db::commit();
                Log::info("定金支付回调成功");
                echo '<xml> <return_code><![CDATA[SUCCESS]]></return_code> <return_msg><![CDATA[OK]]></return_msg> </xml>';
                die;
            }else{
                echo '<xml> <return_code><![CDATA[FAIL]]></return_code> <return_msg><![CDATA[出错了]]></return_msg> </xml>';
                die;
            }

        }catch (Exception $e){
            Log::info("定金支付回调出错----- ".$e->getMessage());
            Db::rollback();
            echo '<xml> <return_code><![CDATA[FAIL]]></return_code> <return_msg><![CDATA['.$e->getMessage().']]></return_msg> </xml>';
            die;
        }
    }


    /**
     * 开卡支付回调
     * @param array $values
     * @param string $notifyType
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    protected function openCardNotify($values = array(),$notifyType = "")
    {

        $vid = $values['out_trade_no'];

        //根据订单号获取订单信息
        $order_info = Db::name('bill_card_fees')
            ->where('vid',$vid)
            ->field('uid,sale_status,delivery_name')
            ->find();

        if (!empty($order_info)){

            if ($order_info['sale_status'] == '1'){
                echo '<xml> <return_code><![CDATA[FAIL]]></return_code> <return_msg><![CDATA[订单已支付]]></return_msg> </xml>';
                die;
//                return $this->com_return(false,'订单已支付');
            }

            $uid             = $order_info['uid'];//用户id

            $delivery_name   = $order_info['delivery_name'];//收货人

            $time           = time();

            //如果收货人为空,直接为交易完成,如果不为空,则为待发货状态
            if (empty($delivery_name)){
                $sale_status = config("order.open_card_status")['completed']['key'];
                $finish_time = $time;
            }else{
                $sale_status = config("order.open_card_status")['pending_ship']['key'];
                $finish_time = NULL;
            }

            Log::info("回调参数 -----  ".var_export($values,true));

            $common          = new Common();
            $cardCallbackObj = new CardCallback();
            $UUIDObj         = new UUIDUntil();

            Db::startTrans();
            try{
                $payable_amount = $values['cash_fee'] / 100;//订单实际需要支付金额

                $vid            = $values['out_trade_no'];//获取来订单id

                $pay_no         = $values['transaction_id'];;//微信流水号

                $pay_time       = $values['time_end'];//支付时间 格式为 201809100524

                $pay_money      = $values['total_fee'] / 100;//实付金额

                $pay_type       = config("order.pay_method")['wxpay']['key'];


                //⑥更新订单状态,
                $billCardFeesParams = [
                    'sale_status'    => $sale_status,
                    'pay_time'       => strtotime($pay_time),
                    'payable_amount' => $payable_amount - $pay_money,
                    'deal_price'     => $pay_money,
                    'pay_type'       => $pay_type,
                    'pay_no'         => $pay_no,
                    'updated_at'     => $time,
                    'finish_time'    => $finish_time
                ];

//                dump($billCardFeesParams);die;

                Log::info("更新订单状态参数 ---- ".var_export($billCardFeesParams,true));

                $billCardFeesReturn = $cardCallbackObj->updateOrderStatus($billCardFeesParams,$vid);

                Log::info("更新订单状态返回值 --- ".$billCardFeesReturn);

                //⑦添加开卡信息

                //获取卡的信息
                $billCardFeesDetail = $cardCallbackObj->getBillCardFeesDetail($vid);

                $card_id = $billCardFeesDetail['card_id'];

                //获取开卡赠送礼金数(百分比)
                $card_cash_gift     = $billCardFeesDetail['card_cash_gift'];
                //获取开卡赠送积分
                $card_point         = $billCardFeesDetail['card_point'];
                //获取开卡赠送推荐用户礼金(百分比)
                $card_job_cash_gif  = $billCardFeesDetail['card_job_cash_gif'];
                //获取开卡赠送推荐用户佣金(百分比)
                $card_job_commission= $billCardFeesDetail['card_job_commission'];

                Log::info("开卡赠送礼金数".$card_cash_gift);

                $cardInfoParams = [
                    "uid"          => $uid,
                    "card_no"      => $UUIDObj->generateReadableUUID($billCardFeesDetail["card_no_prefix"]),
                    "card_id"      => $card_id,
                    "card_type"    => $billCardFeesDetail['card_type'],
                    "card_name"    => $billCardFeesDetail['card_name'],
                    "card_image"   => $billCardFeesDetail['card_image'],
                    "card_o_amount"=> $billCardFeesDetail['card_amount'],
                    "card_amount"  => $pay_money,
                    "card_deposit" => $billCardFeesDetail['card_deposit'],
                    "card_desc"    => $billCardFeesDetail['card_desc'],
                    "card_equities"=> $billCardFeesDetail['card_equities'],
                    "is_valid"     => 1,
                    "valid_time"   => 0,
                    "created_at"   => $time,
                    "updated_at"   => $time
                ];

                Log::info("添加开卡信息参数".var_export($cardInfoParams,true));

                $cardInfoReturn = $cardCallbackObj->updateCardInfo($cardInfoParams);

                Log::info("添加开卡信息返回--- ".$cardInfoReturn);

                $userOldMoneyInfo = Db::name('user')
                    ->where('uid',$uid)
                    ->field('account_balance,account_deposit,account_cash_gift,account_point')
                    ->find();
//                dump($userOldMoneyInfo);die;

                //用户钱包可用余额
                $account_balance = $userOldMoneyInfo['account_balance'];

                //用户钱包押金余额
                $account_deposit = $userOldMoneyInfo['account_deposit'];

                //用户礼金余额
                $account_cash_gift = $userOldMoneyInfo['account_cash_gift'];

                //用户积分可用余额
                $account_point = $userOldMoneyInfo['account_point'];


                //⑩更新用户礼金账户以及礼金明细

                $openCardObj = new OpenCard();

                $referrer_info = $openCardObj->getSalesmanId($uid);

                $referrer_info = json_decode($referrer_info,true);

                $userInfoObj = new UserInfo();


                if (!empty($referrer_info)){
                    $referrer_id   = $referrer_info['referrer_id'];
                    $referrer_type = $referrer_info['referrer_type'];

                    /*//获取奖励比例
                    if ($referrer_type == 'user'){
                        //获取系统设置中设置的奖励用户推荐返回礼金比例
                        $commission_ratio = $openCardObj->getSysSettingInfo("card_user_commission_ratio");
                    }else{
                        //获取销售员类型表中,给销售员奖励的礼金比例
                        $commission_ratio = $openCardObj->getCommissionRatio($referrer_type);
                    }*/
                }else{
                    $referrer_id = "";
                    $referrer_type = "empty";
                }

                if ($referrer_type == 'user'){
                    //如果推荐人是用户,给推荐人用户更新礼金信息

                    //账户可用礼金变动  正加 负减  直接取整,舍弃小数

                    $cash_gift = intval(($card_job_cash_gif / 100) * $pay_money);

                    Log::info("赠送推荐用户礼金数 --- ". $cash_gift);

                    if ($cash_gift > 0){
                        //如果奖励推荐用户的礼金数 大于 0  则执行 更新

                        //首先获取推荐人的礼金余额
                        $referrer_user_gift_cash_old = $userInfoObj->getUserFieldValue("$referrer_id","account_cash_gift");
                        Log::info("推荐用户的旧的礼金数 --- ".$referrer_user_gift_cash_old);


                        //变动后的礼金总余额
                        $last_cash_gift = $cash_gift + $referrer_user_gift_cash_old;
                        Log::info("变动后的礼金总数 --- ".$last_cash_gift);

                        $userAccountCashGiftParams = [
                            'uid'            => $referrer_id,
                            'cash_gift'      => $cash_gift,
                            'last_cash_gift' => $last_cash_gift,
                            'change_type'    => '2',
                            'action_user'    => 'sys',
                            'action_type'    => config('user.gift_cash')['recommend_reward']['key'],
                            'action_desc'    => config('user.gift_cash')['recommend_reward']['name'],
                            'oid'            => $vid,
                            'created_at'     => $time,
                            'updated_at'     => $time
                        ];

                        Log::info("礼金明细参数 ---- ".var_export($userAccountCashGiftParams,true));

                        //给用户添加礼金明细
                        $userAccountCashGiftReturn = $cardCallbackObj->updateUserAccountCashGift($userAccountCashGiftParams);


                        //给用户添加礼金余额
                        $updatedAccountCashGiftReturn = $userInfoObj->updatedAccountCashGift("$referrer_id","$cash_gift","inc");



                    }else{
                        //如果奖励推荐用户的礼金数 小于 0  则不执行礼金更新操作
                        $userAccountCashGiftReturn = true;
                        $updatedAccountCashGiftReturn = true;

                    }

                    /*给推荐用户添加佣金*/
                    if ($card_job_commission > 0){

                        //首先获取推荐人的佣金余额
                        $old_last_balance_res = Db::name("job_user")
                            ->where('uid',$referrer_id)
                            ->field('job_balance')
                            ->find();
                        if (!empty($old_last_balance_res)){
                            $old_last_balance_res = json_decode(json_encode($old_last_balance_res),true);
                            $job_balance = $old_last_balance_res['job_balance'];
                        }else{
                            $job_balance = 0;
                        }

                        $plus_card_job_commission = intval(($card_job_commission / 100) * $pay_money);



                        //添加或更新推荐用户佣金表
                        $jobUserReturn = $cardCallbackObj->updateJobUser($referrer_id,$plus_card_job_commission);

                        //添加推荐用户佣金明细表
                        $jobAccountParams = [
                            "uid"          => $referrer_id,
                            "balance"      => $plus_card_job_commission,
                            "last_balance" => $job_balance + $plus_card_job_commission,
                            "change_type"  => 2,
                            "action_user"  => 'sys',
                            "action_type"  => config('user.job_account')['recommend_reward']['key'],
                            "oid"          => $vid,
                            "deal_amount"  => $payable_amount,
                            "action_desc"  => config('user.job_account')['recommend_reward']['name'],
                            "created_at"   => $time,
                            "updated_at"   => $time
                        ];

                        Log::info("添加推荐用户佣金明细表 -- 参数".var_export($jobAccountParams,true));

//                        dump($jobAccountParams);die;

                        $jobAccountReturn = $cardCallbackObj->insertJobAccount($jobAccountParams);

                    }else{

                        $jobUserReturn    = true;
                        $jobAccountReturn = true;
                    }

                    Log::info("添加或更新推荐用户佣金表 操作返回 ---- ".$jobUserReturn);
                    Log::info("添加推荐用户佣金明细表   操作返回 ---- ".$jobAccountReturn);


                }else{
                    //如果推荐人不是用户,则直接返回 true
                    $userAccountCashGiftReturn    = true;
                    $updatedAccountCashGiftReturn = true;
                    $jobUserReturn                = true;
                    $jobAccountReturn             = true;
                }

                Log::info("礼金明细返回".$userAccountCashGiftReturn."-----"."$updatedAccountCashGiftReturn");

                //获取当前用户旧的礼金余额
                $user_gift_cash_old = $userInfoObj->getUserFieldValue("$uid","account_cash_gift");
                Log::info("旧的礼金余额".$user_gift_cash_old);


                if ($card_cash_gift > 0){
                    //如果开卡赠送礼金数 大于 0,则变更礼金数,并增加 礼金明细
                    $card_cash_gift_money = intval(($card_cash_gift / 100) * $pay_money);
                    $user_gift_cash_new = $user_gift_cash_old + $card_cash_gift_money;
                    Log::info("新的礼金余额".$user_gift_cash_new);

                    //⑩更新办卡用户的返还礼金数额
                    $updatedOpenCardCashGiftReturn = $userInfoObj->updatedAccountCashGift("$uid","$card_cash_gift_money","inc");
                    Log::info("更新办卡用户的返还礼金数额".$updatedOpenCardCashGiftReturn);



                    //⑩ - ① 更新用户礼金明细
                    $updatedUserCashGiftParams = [
                        'uid'            => $uid,
                        'cash_gift'      => $card_cash_gift_money,
                        'last_cash_gift' => $user_gift_cash_new,
                        'change_type'    => '2',
                        'action_user'    => 'sys',
                        'action_type'    => config("user.gift_cash")['open_card_reward']['key'],
                        'action_desc'    => config("user.gift_cash")['open_card_reward']['name'],
                        'oid'            => $vid,
                        'created_at'     => $time,
                        'updated_at'     => $time
                    ];

                    Log::info("更新用户礼金明细参数".var_export($updatedUserCashGiftParams,true));



                    //增加开卡用户礼金明细
                    $openCardUserAccountCashGiftReturn = $cardCallbackObj->updateUserAccountCashGift($updatedUserCashGiftParams);

                    Log::info("增加开卡用户礼金明细返回值".$openCardUserAccountCashGiftReturn);
                }else{
                    //如果赠送礼金数小于 0 则不更新礼金余额以及礼金明细
                    $updatedOpenCardCashGiftReturn = true;
                    $openCardUserAccountCashGiftReturn = true;
                }




                if ($billCardFeesDetail['card_type'] == "value"){

                    //⑧更新用户余额账户以及余额明细
                    //获取用户旧的余额
                    //用户余额参数
                    $userCardParams = [
                        "uid"               => $uid,
                        "account_balance"   => $pay_money + $account_balance,

                        "user_status"       => config("user.user_status")['2']['key'],
                        "updated_at"        => $time
                    ];

                    $userUpdateReturn = $cardCallbackObj->updateUserInfo($userCardParams,$uid);

                    //余额明细参数
                    $userAccountParams = [
                        "uid"          => $uid,
                        "balance"      => $pay_money,
                        "last_balance" => $pay_money + $account_balance,
                        "change_type"  => '2',
                        "action_user"  => 'sys',
                        "action_type"  => config('user.account')['recharge']['key'],
                        "oid"          => $vid,
                        "deal_amount"  => $pay_money,
                        "action_desc"  => "购卡充值",
                        "created_at"   => $time,
                        "updated_at"   => $time
                    ];

                    $userInsertReturn = $cardCallbackObj->updateUserAccount($userAccountParams);

                }elseif ($billCardFeesDetail['card_type'] == "vip"){
                    //⑨更新用户押金账户以及押金明细 vip
                    $userCardParams = [
                        "uid"             => $uid,
                        "account_deposit" => $pay_money + $account_deposit,

                        "user_status"     => config("user.user_status")['2']['key'],
                        "updated_at"      => $time
                    ];

                    $userUpdateReturn = $cardCallbackObj->updateUserInfo($userCardParams,$uid);

                    $arr = [];
                    //用户押金明细参数
                    $userDepositParams = [
                        "uid"           => $uid,
                        "deposit"       => $pay_money,
                        "last_deposit"  => $pay_money + $account_deposit,
                        "change_type"   => '2',
                        "action_user"   => 'sys',
                        "action_type"   => config('user.deposit')['pay']['key'],
                        "action_desc"   => config('user.deposit')['pay']['name'],
                        "oid"           => $vid,
                        "created_at"    => $time,
                        "updated_at"    => $time
                    ];

                    $userInsertReturn = $cardCallbackObj->updateUserAccountDeposit($userDepositParams);
                }else{
                    //属于年费卡

                    $userCardParams = [
                        "uid"               => $uid,
                        "user_status"       => config("user.user_status")['2']['key'],
                        "updated_at"        => $time
                    ];
                    $userUpdateReturn = $cardCallbackObj->updateUserInfo($userCardParams,$uid);

                    $userInsertReturn = true;
                }

                Log::info("用户押金,储值更新".$userUpdateReturn."------".$userInsertReturn);


                //⑩更新用户积分账户以及积分明细
                //$account_point用户积分可用余额
                //$card_point 开卡赠送积分
                if ($card_point > 0){
                    //如果赠送积分大于0 则更新

                    //1.更新用户积分余额
                    $updateUserPointParams = [
                        'account_point' => $account_point + $card_point,
                        'updated_at'    => $time
                    ];
                    $userUserPointReturn = $cardCallbackObj->updateUserInfo($updateUserPointParams,$uid);

                    //2.更新用户积分明细
                    $updateAccountPointParams = [
                        'uid' => $uid,
                        'point' => $card_point,
                        'last_point' => $account_point + $card_point,
                        'change_type' => 2,
                        'action_user' => 'sys',
                        'action_type' => config("user.point")['open_card_reward']['key'],
                        'action_desc' => config("user.point")['open_card_reward']['name'],
                        'oid' => $vid,
                        'created_at' => $time,
                        'updated_at' => $time
                    ];

                    $userAccountPointReturn = $cardCallbackObj->updateUserAccountPoint($updateAccountPointParams);

                }else{
                    //如果赠送积分小于0 则不更新
                    $userUserPointReturn = true;
                    $userAccountPointReturn = true;
                }



                //⑩①下发赠送的券

                $giftVouReturn = $this->putVoucher("$card_id","$uid");
                Log::info("下发赠券 结果 ---- ".$giftVouReturn);

                if ($billCardFeesReturn && $cardInfoReturn && $userUpdateReturn && $userInsertReturn && $userAccountCashGiftReturn && $updatedAccountCashGiftReturn && $giftVouReturn && $openCardUserAccountCashGiftReturn && $updatedOpenCardCashGiftReturn &&  $jobUserReturn && $jobAccountReturn && $userUserPointReturn && $userAccountPointReturn){
                    Db::commit();
                    Log::info("支付回调成功");

                    if ($notifyType == 'adminCallback'){
                        $updateBillParams = [
                            "cancel_user" => null,
                            "cancel_time" => null,
                            "auto_cancel" => null,
                            "cancel_reason" => null,
                        ];

                        Db::name("bill_card_fees")
                            ->where("vid",$vid)
                            ->update($updateBillParams);

                        //如果是后台操作订单支付成功,记录相关操作日志
                        $adminCommonAction = new CommandAction();

                        $action  = config("useraction.deal_pay")['key'];

                        $reason = $values['reason'];//操作原因描述

                        $adminToken = $this->request->header("Authorization","");

                        //获取当前登录管理员
                        $action_user = $this->getLoginAdminId($adminToken)['user_name'];

                        $adminCommonAction->addSysAdminLog("$uid","","$vid","$action","$reason","$action_user","$time");

                    }

                    echo '<xml> <return_code><![CDATA[SUCCESS]]></return_code> <return_msg><![CDATA[OK]]></return_msg> </xml>';
                    die;
                }else{
                    echo '<xml> <return_code><![CDATA[FAIL]]></return_code> <return_msg><![CDATA[出错了]]></return_msg> </xml>';

                    die;
                }
            }catch (Exception $e){
                Log::info("支付回调出错----- ".$e->getMessage());
                Db::rollback();
                echo '<xml> <return_code><![CDATA[FAIL]]></return_code> <return_msg><![CDATA[出错了!!!]]></return_msg> </xml>';
                die;
            }

        }else{
            echo '<xml> <return_code><![CDATA[FAIL]]></return_code> <return_msg><![CDATA[订单不存在!!!]]></return_msg> </xml>';
            die;
        }

    }




    /**
     * 下发赠送的券
     * @param $card_id
     * @param $uid
     * @return bool
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function putVoucher($card_id,$uid)
    {
        $common = new Common();

        $cardCallbackObj = new CardCallback();

        //获取礼券信息

        $time = time();

        $gift_vou_info = Db::name('mst_card_vip_voucher_relation')
            ->alias('vvr')
            ->join('mst_gift_voucher mgv','mgv.gift_vou_id = vvr.gift_vou_id')
            ->where('vvr.card_id',$card_id)
            ->where('mgv.is_enable','1')
            ->where('mgv.is_delete','0')
            ->field('vvr.gift_vou_type,vvr.qty,vvr.gift_vou_id')
            ->field('mgv.gift_vou_name,mgv.gift_vou_desc,mgv.gift_vou_amount,mgv.gift_validity_type,mgv.gift_vou_validity_day,mgv.gift_start_day,mgv.gift_end_day,mgv.gift_vou_exchange,mgv.qty_max')
            ->select();

        Log::info("礼券信息展示 ---- ".var_export($gift_vou_info,true));

        $giftVouReturn = false;

        if (empty($gift_vou_info)){
            $giftVouReturn = true;
        }


        for ($i=0;$i<count($gift_vou_info);$i++){

            $gift_validity_type    = $gift_vou_info[$i]['gift_validity_type'];

            $gift_start_day        = $gift_vou_info[$i]['gift_start_day'];//有效开始时间
            $gift_end_day          = $gift_vou_info[$i]['gift_end_day'];//有效结束时间
            $gift_vou_validity_day = $gift_vou_info[$i]['gift_vou_validity_day'];//有效天数

            if ($gift_validity_type == '1'){
                //如果有效期类型为 按天数生效
                if (empty($gift_start_day) || $gift_start_day == '0'){
                    //这时未设置有效开始时间
                    $gift_vou_validity_start = $time;
                    $gift_vou_validity_end   = $time + $gift_vou_validity_day * 24 * 60 * 60;
                }else{
                    //这里设置了有效开始时间
                    $gift_vou_validity_start = $gift_start_day;
                    $gift_vou_validity_end   = $gift_end_day;
                }
            }elseif ($gift_validity_type == '2'){
                //如果类型为 指定了有效日期段
                $gift_vou_validity_start = $gift_start_day;
                $gift_vou_validity_end   = $gift_end_day;

            }else{
                //如果类型为 0 无限期
                if (empty($gift_start_day) || $gift_start_day == '0'){
                    //这时未设置有效开始时间
                    $gift_vou_validity_start = $time;

                }else{
                    //这里设置了有效开始时间
                    $gift_vou_validity_start = $gift_start_day;
                }
                $gift_vou_validity_end = '0';
            }

            $gift_vou_code = $common->uniqueCode(8); //礼品券兑换码
            Log::info("礼券兑换码".$gift_vou_code);

            $giftVouParams = [
                'gift_vou_code'           => $gift_vou_code,
                'uid'                     => $uid,
                'gift_vou_id'             => $gift_vou_info[$i]['gift_vou_id'],
                'gift_vou_type'           => $gift_vou_info[$i]['gift_vou_type'],
                'gift_vou_name'           => $gift_vou_info[$i]['gift_vou_name'],
                'gift_vou_desc'           => $gift_vou_info[$i]['gift_vou_desc'],
                'gift_vou_amount'         => $gift_vou_info[$i]['gift_vou_amount'],
                'gift_vou_validity_start' => $gift_vou_validity_start,
                'gift_vou_validity_end'   => $gift_vou_validity_end,
                'gift_vou_exchange'       => $gift_vou_info[$i]['gift_vou_exchange'],
                'use_qty'                 => $gift_vou_info[$i]['qty'],
                'qty_max'                 => $gift_vou_info[$i]['qty_max'],
                'use_time'                => 0,
                'review_user'             => 'sys',
                'created_at'              => $time,
                'updated_at'              => $time,
            ];

            Log::info("添加礼券参数".$i.var_export($giftVouParams,true));


            $giftVouReturn = $cardCallbackObj->updateUserGiftVoucher($giftVouParams);

            Log::info("添加礼券返回结果".$i." --- ".$giftVouReturn);
        }

        Log::info("添加礼券最终返回结果".$giftVouReturn);

        return $giftVouReturn;
    }


    /**
     * 下发赠送的券备份
     *
     */
    /*public function bei()
    {

        //获取礼券信息
        $card_id = $billCardFeesDetail['card_id'];

        $gift_vou_info = Db::name('mst_card_vip_voucher_relation')
            ->alias('vvr')
            ->join('mst_gift_voucher mgv','mgv.gift_vou_id = vvr.gift_vou_id')
            ->where('vvr.card_id',$card_id)
            ->field('vvr.gift_vou_type,vvr.qty,vvr.gift_vou_id')
            ->field('mgv.gift_vou_name,mgv.gift_vou_desc,mgv.gift_vou_amount,mgv.gift_vou_validity_day,mgv.gift_vou_exchange')
            ->select();

        for ($i=0;$i<count($gift_vou_info);$i++){
            $gift_vou_validity_day = $gift_vou_info[$i]['gift_vou_validity_day'];
            if ($gift_vou_validity_day == 0){

                $gift_vou_validity_end = 0;

            }else{

                $gift_vou_validity_end = $time + $gift_vou_validity_day * 24 * 60 * 60;

            }
            $gift_vou_code = $common->uniqueCode(8); //礼品券兑换码

            $giftVouParams = [
                'gift_vou_code'           => $gift_vou_code,
                'uid'                     => $uid,
                'gift_vou_id'             => $gift_vou_info[$i]['gift_vou_id'],
                'gift_vou_type'           => $gift_vou_info[$i]['gift_vou_type'],
                'gift_vou_name'           => $gift_vou_info[$i]['gift_vou_name'],
                'gift_vou_desc'           => $gift_vou_info[$i]['gift_vou_desc'],
                'gift_vou_amount'         => $gift_vou_info[$i]['gift_vou_amount'],
                'gift_vou_validity_start' => $time,
                'gift_vou_validity_end'   => $gift_vou_validity_end,
                'gift_vou_exchange'       => $gift_vou_info[$i]['gift_vou_exchange'],
                'qty'                     => $gift_vou_info[$i]['qty'],
                'use_time'                => 0,
                'review_user'             => 'sys',
                'created_at'              => $time,
                'updated_at'              => $time,
            ];

            $giftVouReturn = $cardCallbackObj->updateUserGiftVoucher($giftVouParams);

        }
    }*/

    /**
     * 获取订单实际支付金额
     * @param $vid
     * @return bool|mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getOrderPayableAmount($vid)
    {
        $billCardFeesModel = new BillCardFees();

        $bill_info = $billCardFeesModel
            ->where('vid',$vid)
            ->where('sale_status','0')
            ->field('payable_amount')
            ->find();
        if (!empty($bill_info)) {
           return  $bill_info['payable_amount'];
        }else{
            return false;
        }
    }

    /**
     * 获取订台押金支付金额
     * @param $suid
     * @return bool|mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getSubscriptionPayableAmount($suid)
    {
        $billSubscriptionModel = new BillSubscription();

        $info = $billSubscriptionModel
            ->where('suid',$suid)
            ->where('status',0)
            ->field('subscription')
            ->find();

        if (!empty($info)) {
            return  $info['subscription'];
        }else{
            return false;
        }
    }

    /**
     * 获取充值金额
     * @param $rfid
     * @return bool|mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getBillRefillAmount($rfid)
    {
        $billRefillModel = new BillRefill();

        $info = $billRefillModel
            ->where('rfid',$rfid)
            ->where('status',0)
            ->field("amount")
            ->find();

        if (!empty($info)) {
            return  $info['amount'];
        }else{
            return false;
        }

    }

}