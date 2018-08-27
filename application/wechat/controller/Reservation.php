<?php
/**
 * 预约
 * User: qubojie
 * Date: 2018/7/25
 * Time: 下午6:11
 */
namespace app\wechat\controller;

use app\admin\model\TableRevenue;
use app\wechat\model\BillSubscription;
use think\Config;
use think\Db;
use think\Exception;
use think\Request;
use think\Validate;

class Reservation extends CommonAction
{
    /**
     * 可预约吧台列表
     * @param Request $request
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function tableList(Request $request)
    {

        $pagesize = $request->param("pagesize",config('XCXPAGESIZE'));//显示个数,不传时为10

        if (empty($pagesize)) $pagesize = config('XCXPAGESIZE');

        $nowPage = $request->param("nowPage","1");

        $config = [
            "page" => $nowPage,
        ];

        $token       = $request->header("Token","");

        $location_id = $request->param("location_id","");//位置id

        $size_id     = $request->param('size_id',"");//人数范围

        $appointment = $request->param("appointment","");//预约时间

        $rule = [
            "appointment|预约时间"  => "require",
        ];

        $request_res = [
            "appointment"   => $appointment,
        ];

        $validate = new Validate($rule);

        if (!$validate->check($request_res)){
            return $this->com_return(false,$validate->getError(),null);
        }

        //查看当前用户办的卡id
        $uid            = $this->tokenGetUserInfo($token)['uid'];//获取uid
        $user_card_info = $this->uidGetCardInfo($uid);//根据uid获取卡id

        /*if (empty($user_card_info)){
            return $this->com_return(false,config("params.NOT_OPEN_CARD"));//卡已失效,或者未开卡
        }*/

        if (!empty($user_card_info)){
            $user_card_id = $user_card_info['card_id'];
        }else{
            $user_card_id = "";
        }

        $publicActionObj = new PublicAction();

        $res_data = $publicActionObj->reservationPublic($size_id,$location_id,$appointment,$user_card_id,$pagesize,$config);

        return $this->com_return(true,config("params.SUCCESS"),$res_data);
    }

    /**
     * 预约确认
     * @param Request $request
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function reservationConfirm(Request $request)
    {
        $remember_token = $request->header("Token",'');

        //获取到预约人信息
        $userInfo = $this->tokenGetUserInfo($remember_token);

        $uid = $userInfo['uid'];

        $table_id       = $request->param('table_id','');//桌位id

        $turnover_limit = $request->param('turnover_limit',0);//最低消费  0表示无最低消费

        $subscription   = $request->param('subscription',0);//预约定金

        $date           = $request->param('date','');//日期

        $time           = $request->param('time','');//时间
        
        $sales_phone    = $request->param('sales_phone','');//营销电话


        $rule = [
            "table_id|桌位"          => "require",
            "turnover_limit|最低消费" => "require",
            "subscription|预约定金"   => "require",
            "date|日期"              => "require",
            "time|时间"              => "require",
            "sales_phone|营销电话"    => "regex:1[3-8]{1}[0-9]{9}",
        ];

        $check_data = [
            "table_id"       => $table_id,
            "turnover_limit" => $turnover_limit,
            "subscription"   => $subscription,
            "date"           => $date,
            "time"           => $time,
            "sales_phone"    => $sales_phone
        ];

        $validate = new Validate($rule);

        if (!$validate->check($check_data)){
            return $this->com_return(false,$validate->getError());
        }

        $reserve_way = Config::get("order.reserve_way")['client']['key'];//预定途径  client  客户预订（小程序）    service 服务端预定（内部小程序）   manage 管理端预定（pc管理端暂保留）

        $publicActionObj = new PublicAction();

        $res = $publicActionObj->confirmReservationPublic($sales_phone,$table_id,$date,$time,$subscription,$turnover_limit,$reserve_way,$uid);

        return $res;
    }

    /**
     * 取消预约
     * @param Request $request
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function cancelReservation(Request $request)
    {
        $remember_token = $request->header("Token",'');

        $trid           = $request->param("trid","");//台位id

        if (empty($trid)){
            return $this->com_return(false,\config("params.PARAM_NOT_EMPTY"));
        }

        //获取当前台位信息
        $tableInfo = $this->getTableInfo($trid);

        $status = $tableInfo['status'];//获取当前台位状态

        if ($status == \config("order.table_reserve_status")['already_open']['key']){
            return $this->com_return(false,"已开台,不可取消预约");
        }

        if ($status == \config("order.table_reserve_status")['clear_table']['key']){
            return $this->com_return(false,"已消费,不可取消预约");
        }

        if ($status == \config("order.table_reserve_status")['cancel']['key']){
            return $this->com_return(false,"已取消,不可重复操作");
        }

        $is_subscription = $tableInfo['is_subscription'];//是否收取定金1 是  0否

        $time = time();

        Db::startTrans();
        try{

            if ($status == \config("order.table_reserve_status")['pending_payment']['key']){
                //如果是待付款状态,
                if ($is_subscription){
                    //如果收取定金
                    $table_params = [
                        "status"        => \config("order.table_reserve_status")['cancel']['key'],
                        "cancel_user"   => "user",
                        "cancel_time"   => $time,
                        "cancel_reason" => "未付款时,用户手动取消",
                        "updated_at"    => $time
                    ];

                    $bill_params = [
                        "status"        => \config("order.reservation_subscription_status")['cancel']['key'],
                        "cancel_user"   => "user",
                        "cancel_time"   => $time,
                        "auto_cancel"   => 0,
                        "cancel_reason" => "未付款时,用户手动取消",
                        "updated_at"    => $time

                    ];

                    $tableReturn = $this->updatedTableRevenueInfo($table_params,$trid);
                    $billReturn  = $this->updatedBillSubscription($bill_params,$trid);
                }else{

                    $table_params = [
                        "status"        => \config("order.table_reserve_status")['cancel']['key'],
                        "cancel_user"   => "user",
                        "cancel_time"   => $time,
                        "cancel_reason" => "无需缴纳定金,用户手动取消",
                        "updated_at"    => $time
                    ];

                    $tableReturn = $this->updatedTableRevenueInfo($table_params,$trid);
                    $billReturn  = true;
                }

            }else{
                //如果是已付款状态
                //获取系统设置的最晚取消时间
                $cardObj = new OpenCard();

                $reserve_cancel_time = $cardObj->getSysSettingInfo("reserve_cancel_time");

                $kc_date = date("Y-m-d");

                $kc_time = $kc_date." ".$reserve_cancel_time;//开餐时间

                $kc_time = strtotime($kc_time);

                $now_time = time();

                if ($is_subscription){
                    //如果收取定金
                    if ($now_time > $kc_time){
//                        dump("已付款,收取定金,且超时");die;
                        //如果退款时,已超时,则不退定金
                        $table_params = [
                            "status"        => \config("order.table_reserve_status")['cancel']['key'],
                            "cancel_user"   => "user",
                            "cancel_time"   => $time,
                            "cancel_reason" => "已付款,超出取消时间范围内,用户手动取消",
                            "updated_at"    => $time
                        ];

                        $bill_params = [
                            "status"        => \config("order.reservation_subscription_status")['cancel_revenue']['key'],
                            "cancel_user"   => "user",
                            "cancel_time"   => $time,
                            "auto_cancel"   => 0,
                            "cancel_reason" => "已付款,超出取消时间范围内,用户手动取消",
                            "updated_at"    => $time
                        ];

                        $tableReturn = $this->updatedTableRevenueInfo($table_params,$trid);
                        $billReturn  = $this->updatedBillSubscription($bill_params,$trid);

                    }else{
                        //如果退款时,未超时,则退还定金
//                        dump("已付款,收取定金,未超时");die;

                        $billInfo = $this->getBillSubscriptionInfo($trid);
                        $subscription = $billInfo['subscription'];
                        $suid         = $billInfo['suid'];
                        $pay_type     = $billInfo['pay_type'];
                        $is_refund_sub= $billInfo['is_refund_sub'];

                        if ($is_refund_sub == 1){
                            //不退款
                            $table_params = [
                                "status"        => \config("order.table_reserve_status")['cancel']['key'],
                                "cancel_user"   => "user",
                                "cancel_time"   => $time,
                                "cancel_reason" => "已付款,未超出取消时间范围内,特殊日期不退款,用户手动取消",
                                "updated_at"    => $time
                            ];

                            $bill_params = [
                                "status"        => \config("order.reservation_subscription_status")['cancel_revenue']['key'],
                                "cancel_user"   => "user",
                                "cancel_time"   => $time,
                                "auto_cancel"   => 0,
                                "cancel_reason" => "已付款,未超出取消时间范围内,特殊日期不退款,用户手动取消",
                                "is_refund"     => 1,
                                "refund_amount" => $subscription,
                                "updated_at"    => $time
                            ];

                            $tableReturn = $this->updatedTableRevenueInfo($table_params,$trid);

                            $billReturn  = $this->updatedBillSubscription($bill_params,$trid);

                        }else{

                            //可以退款
                            //微信支付时,走退款接口
                            if ($pay_type == \config("order.pay_method")['wxpay']['key']){
                                $payRes = $this->callBackPay($suid,$subscription,$subscription);
                            }else{
                                $payRes = true;
                            }

                            if (!empty($payRes)){

                                $table_params = [
                                    "status"        => \config("order.table_reserve_status")['cancel']['key'],
                                    "cancel_user"   => "user",
                                    "cancel_time"   => $time,
                                    "cancel_reason" => "已付款,未超出取消时间范围内,用户手动取消",
                                    "updated_at"    => $time
                                ];

                                $bill_params = [
                                    "status"        => \config("order.reservation_subscription_status")['cancel_revenue']['key'],
                                    "cancel_user"   => "user",
                                    "cancel_time"   => $time,
                                    "auto_cancel"   => 0,
                                    "cancel_reason" => "已付款,未超出取消时间范围内,用户手动取消",
                                    "is_refund"     => 1,
                                    "refund_amount" => $subscription,
                                    "updated_at"    => $time
                                ];

                                $tableReturn = $this->updatedTableRevenueInfo($table_params,$trid);
                                $billReturn = $this->updatedBillSubscription($bill_params,$trid);

                            }else{
                                $tableReturn = false;
                                $billReturn  = false;
                            }

                        }
                    }
                }else{
                    //如果没收取定金
//                    dump("已预约,不用支付定金");die;
                    $table_params = [
                        "status"        => \config("order.table_reserve_status")['cancel']['key'],
                        "cancel_user"   => "user",
                        "cancel_time"   => $time,
                        "cancel_reason" => "已预约,不用支付定金,用户手动取消",
                        "updated_at"    => $time
                    ];
                    $tableReturn = $this->updatedTableRevenueInfo($table_params,$trid);
                    $billReturn = true;
                }
            }

            if ($tableReturn && $billReturn){
                /*记录日志 on*/

                $uid = $tableInfo['uid'];

                $userInfo = getUserInfo($uid);

                $userName = $userInfo["name"];

                $userPhone = $userInfo["phone"];

                $table_id = $tableInfo['table_id'];

                $table_no = $tableInfo['table_no'];

                $reserve_time = $tableInfo['reserve_time'];//预约时间

                $reserve_date = date("Y-m-d H:i:s",$reserve_time);

                $type = config("order.table_action_type")['cancel_revenue']['key'];

                $desc = " 用户 ".$userName."($userPhone)"." 手动取消 $reserve_date ".$table_no."桌的预约";

                //取消预约记录日志
                insertTableActionLog(microtime(true) * 10000,"$type","$table_id","$table_no","$userName",$desc,"","");
                /*记录日志 off*/

                Db::commit();
                return $this->com_return(true,\config("params.SUCCESS"));
            }else{
                return $this->com_return(false,\config("params.FAIL"));
            }

        }catch (Exception $e){
            Db::rollback();
            return $this->com_return(false,$e->getMessage());
        }
    }

    //用户主动取消支付,释放桌台
    public function releaseTable(Request $request)
    {
        $suid = $request->param("vid","");

        if (empty($suid)){
            return $this->com_return(false,config("params.ABNORMAL_ACTION"));
        }

        $billSubscriptionModel = new BillSubscription();

        $billInfo = $billSubscriptionModel
            ->where("suid",$suid)
            ->where("status",\config("order.reservation_subscription_status")['pending_payment']['key'])
            ->find();

        $billInfo = json_decode(json_encode($billInfo),true);

        if (empty($billInfo)){
            return $this->com_return(true,\config("params.SUCCESS"));
        }

        $trid = $billInfo['trid'];

        Db::startTrans();
        try{
            //更新预约订台状态为交易取消
            $table_params = [
                "status"        => \config("order.table_reserve_status")['cancel']['key'],
                "cancel_user"   => "user",
                "cancel_time"   => time(),
                "cancel_reason" => "取消支付",
                "updated_at"    => time()
            ];

            $this->updatedTableRevenueInfo($table_params,$trid);

            $bill_params = [
                "status"        => \config("order.reservation_subscription_status")['cancel']['key'],
                "cancel_user"   => "user",
                "cancel_time"   => time(),
                "auto_cancel"   => 0,
                "cancel_reason" => "取消支付",
                "updated_at"    => time()
            ];

            $this->updatedBillSubscription($bill_params,$trid);

            Db::commit();

            return $this->com_return(true,\config("params.SUCCESS"));

        }catch (Exception $e){
            Db::rollback();
            return $this->com_return(false,$e->getMessage());
        }
    }


    /**
     * 获取当前台位信息
     * @param $trid
     * @return array|false|\PDOStatement|string|\think\Model
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getTableInfo($trid)
    {
        $tableModel = new TableRevenue();

        $column = $tableModel->column;

        $tableInfo = $tableModel
            ->where('trid',$trid)
            ->field($column)
            ->find();

        $tableInfo = json_decode(json_encode($tableInfo),true);
        return $tableInfo;
    }

    //更新预约台位信息(取消预约)
    public function updatedTableRevenueInfo($params = array(),$trid)
    {
        $tableModel = new TableRevenue();

        $is_ok = $tableModel
            ->where("trid",$trid)
            ->update($params);

        if ($is_ok !== false){
            return true;
        }else{
            return false;
        }
    }

    //更新定金信息
    public function updatedBillSubscription($params = array(),$trid)
    {
        $billSubscriptionModel = new BillSubscription();

        $is_ok = $billSubscriptionModel
            ->where('trid',$trid)
            ->update($params);

        if ($is_ok){
            return true;
        }else{
            return false;
        }
    }

    //获取当前定金订单信息
    public function getBillSubscriptionInfo($trid)
    {
        $billSubscriptionModel = new BillSubscription();

        $info = $billSubscriptionModel
            ->where('trid',$trid)
            ->find();

        $info = json_decode(json_encode($info),true);

        return $info;
    }


    /**
     * 统一模拟退款,组装参数
     * @param $order_id
     * @param $total_fee
     * @param $refund_fee
     * @return bool|mixed
     */
    protected function callBackPay($order_id,$total_fee,$refund_fee)
    {
        $values = [
            'vid'          => $order_id,
            'total_fee'    => $total_fee,
            'refund_fee'   => $refund_fee,
            'out_refund_no' => $order_id,
        ];

        $res = $this->requestPost($values);

        return $res;

    }


    /**
     * 模拟post支付回调接口请求
     *
     * @param array $postParams
     * @return bool|mixed
     */
    protected function requestPost($postParams = array())
    {
        $request = Request::instance();

        $url = $request->domain()."/wechat/reFund";

        if (empty($url) || empty($postParams)) {
            return false;
        }

        $o = "";
        foreach ( $postParams as $k => $v )
        {
            $o.= "$k=" . urlencode( $v ). "&" ;
        }

        $postParams = substr($o,0,-1);


        $postUrl = $url;
        $curlPost = $postParams;

        $ch = curl_init();//初始化curl
        curl_setopt($ch, CURLOPT_URL,$postUrl);//抓取指定网页
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);//要求结果为字符串且输出到屏幕上
        curl_setopt($ch, CURLOPT_POST, 1);//post提交方式
        curl_setopt($ch, CURLOPT_POSTFIELDS, $curlPost);
        $data = curl_exec($ch);//运行curl
        curl_close($ch);

        $data = json_decode($data,true);

        return $data;
    }
}