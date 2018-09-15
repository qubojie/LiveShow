<?php
/**
 * 前台管理预定
 * User: qubojie
 * Date: 2018/8/10
 * Time: 上午9:48
 */
namespace app\reception\controller;
use app\admin\model\ManageSalesman;
use app\admin\model\MstTable;
use app\admin\model\MstTableCard;
use app\admin\model\TableRevenue;
use app\admin\model\User;
use app\common\controller\UUIDUntil;
use app\services\Sms;
use app\wechat\controller\OpenCard;
use app\wechat\model\BillSubscription;
use think\Db;
use think\Exception;
use think\Log;
use think\Request;
use think\Validate;

class Reservation extends CommonAction
{
    /**
     * 预定列表
     * @param Request $request
     * @return array
     * @throws \think\exception\DbException
     */
    public function index(Request $request)
    {
        $date          = $request->param("date","");//预约日期

        $location_id   = $request->param("location_id","");//大区id
        $area_id       = $request->param("area_id","");//小区id
        $appearance_id = $request->param("appearance_id","");//品项id

        $status        = $request->param("status","");//预约状态 1已预约;2可预约;为空或0是全部

        $keyword       = $request->param("keyword","");//关键字

        if (empty($date)){
            return $this->com_return(false,config("params.REVENUE")['DATE_NOT_EMPTY']);
        }

        $tableModel        = new MstTable();
        $tableRevenueModel = new TableRevenue();

        $begin_time = strtotime(date("Ymd",$date));
        $end_time   = $begin_time + 60 * 60 * 24;

        $begin_time = date("Ymd",$begin_time);
        $end_time   = date("Ymd",$end_time);

        $re_status = "0,1,2";

        $location_where = [];
        if (!empty($location_id)){
            $location_where['ta.location_id'] = $location_id;
        }

        $area_where = [];
        if (!empty($area_id)){
            $area_where['ta.area_id'] = $area_id;
        }

        $appearance_where = [];
        if (!empty($appearance_id)){
            $appearance_where['tap.appearance_id'] = $appearance_id;
        }

        $size_where = [];
        if (!empty($size_id)){
            $size_where['t.size_id'] = $size_id;
        }

        $where = [];
        if (!empty($keyword)){
            $where["t.table_no|ta.area_title|tl.location_title|tap.appearance_title|tr.ssname|u.name|u.nickname"] = ["like","%$keyword%"];
        }

        $tableInfo = $tableModel
            ->alias("t")
            ->join("mst_table_area ta","ta.area_id = t.area_id")//区域
            ->join("mst_table_location tl","tl.location_id = ta.location_id")//位置
            ->join("mst_table_size ts","ts.size_id = t.size_id")//人数
            ->join("mst_table_appearance tap","tap.appearance_id = t.appearance_id")//品项
            ->join("table_revenue tr","tr.table_id = t.table_id","LEFT")
            ->join("user u","u.uid = tr.uid","LEFT")
            ->where('t.is_delete',0)
            ->where('t.is_enable',1)
            ->where('ta.is_enable',1)
            ->where($where)
            ->where($location_where)
            ->where($area_where)
            ->where($appearance_where)
            ->where($size_where)
            ->group("t.table_id")
            ->order('tl.location_id,ta.area_id,t.table_no,tap.appearance_id')
            ->field("t.table_id,t.table_no,t.reserve_type,t.people_max,t.table_desc")
            ->field("ta.area_id,ta.area_title,ta.area_desc")
            ->field("tl.location_title")
            ->field("ts.size_title")
            ->field("tap.appearance_title")
            ->select();

        $tableInfo = json_decode(json_encode($tableInfo),true);

        for ($i = 0; $i < count($tableInfo); $i ++){

            $table_id = $tableInfo[$i]['table_id'];

            $tableStatusRes = $tableRevenueModel
                ->where('table_id',$table_id)
                ->where('status',"IN",$re_status)
                ->whereTime("reserve_time","between",["$begin_time","$end_time"])
                ->find();

            $tableStatusRes = json_decode(json_encode($tableStatusRes),true);

            if (!empty($tableStatusRes)){
                $table_status =  $tableStatusRes['status'];

                $reserve_time = $tableStatusRes['reserve_time'];

                if ($reserve_time < time()){
                    $tableInfo[$i]['is_overtime'] = 1;
                }else{
                    $tableInfo[$i]['is_overtime'] = 0;
                }

                $tableInfo[$i]['reserve_time'] = date("H:i",$reserve_time);

                if ($table_status == 1){
                    $tableInfo[$i]['table_status'] = 1;
                }elseif ($table_status == 2){
                    $tableInfo[$i]['table_status'] = 1;
                }else{
                    $tableInfo[$i]['table_status'] = 0;//空
                }
            }else{
                $tableInfo[$i]['table_status'] = 0;
                $tableInfo[$i]['reserve_time'] = 0;
                $tableInfo[$i]['is_overtime'] = 0;
            }

            /*桌子限制筛选 on*/
            if ($tableInfo[$i]['reserve_type'] == config("table.reserve_type")['0']['key']){
                //无限制
                $tableInfo[$i]['is_limit'] = 0;
            }else{
                $tableInfo[$i]['is_limit'] = 1;
            }
            /*桌子限制筛选 off*/

        }

        if ($status == 1){
            //已预约
            foreach ($tableInfo as $key => $val){
               if ($val['table_status'] == 0){
                   unset($tableInfo[$key]);
               }
            }

            $res = array_values($tableInfo);

        }elseif ($status == 2){
            //可预约
            foreach ($tableInfo as $key => $val){
                if ($val['table_status'] == 1){
                    unset($tableInfo[$key]);
                }
            }

            $res = array_values($tableInfo);

        }else{

            //全部
            $res = $tableInfo;

        }

        return $this->com_return(true,config("params.SUCCESS"),$res);

    }

    /**
     * 桌台详情
     * @param Request $request
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function tableDetails(Request $request)
    {
        $date     = $request->param("date","");//日期

        $table_id = $request->param("table_id","");//桌号id

        if (empty($table_id) || empty($date)){
            return $this->com_return(false,config("params.PARAM_NOT_EMPTY"));
        }

        $begin_time = strtotime(date("Ymd",$date));

        $end_time   = $begin_time + 60 * 60 * 24;

        $begin_time = date("Ymd",$begin_time);

        $end_time   = date("Ymd",$end_time);

        $tableModel = new MstTable();

        $table_column = [
            "t.table_no",
            "t.is_enable",
            "t.table_id",
            "t.area_id",
            "t.reserve_type",
            "t.turnover_limit_l1",
            "t.turnover_limit_l2",
            "t.turnover_limit_l3",
            "t.subscription_l1",
            "t.subscription_l2",
            "t.subscription_l3",
        ];

        $tableInfo = $tableModel
            ->alias("t")
            ->join("mst_table_area ta","ta.area_id = t.area_id")
            ->join("manage_salesman ms","ms.sid = ta.sid")
            ->where("table_id",$table_id)
            ->field("ms.sales_name as service_name,ms.phone service_phone")
            ->field($table_column)
            ->find();

        $tableInfo = json_decode(json_encode($tableInfo),true);


        /*会员限定 on*/
        $reserve_type = $tableInfo['reserve_type'];

        if ($reserve_type == config("table.reserve_type")['1']['key']){

            $cardInfo = Db::name("mst_table_card")
                ->alias("tc")
                ->join("mst_card_vip cv","cv.card_id = tc.card_id")
                ->where('tc.table_id',$table_id)
                ->field("cv.card_id,cv.card_name")
                ->select();

            $cardInfo = json_decode(json_encode($cardInfo),true);

            $tableInfo['card_vip'] = $cardInfo;
        }else{
            $tableInfo['card_vip'] = [];
        }

        /*会员限定 off*/


        $publicObj = new PublicAction();

        /*特殊日期 匹配特殊定金 on*/
        $dateList = $publicObj->isReserveDate($date);

        if (!empty($dateList)){
            //是特殊日期
            $turnover_limit = $tableInfo['turnover_limit_l3'];//特殊日期预约最低消费
            $subscription   = $tableInfo['subscription_l3'];//特殊日期预约定金


        }else{
            //不是特殊日期

            //查看预约日期是否是周末日期
            $today_week = getTimeWeek($date);

            $openCardObj = new OpenCard();

            $reserve_subscription_week = $openCardObj->getSysSettingInfo("reserve_subscription_week");

            $is_bh = strpos("$reserve_subscription_week","$today_week");

            if ($is_bh !== false){
                //如果包含,则获取特殊星期的押金和低消
                $turnover_limit = $tableInfo['turnover_limit_l2'];//周末日期预约最低消费
                $subscription   = $tableInfo['subscription_l2'];//周末日期预约定金

            }else{
                //如果不包含
                $turnover_limit = $tableInfo['turnover_limit_l1'];//平时预约最低消费
                $subscription   = $tableInfo['subscription_l1'];//平时预约定金
            }
        }
        $tableInfo['turnover_limit'] = $turnover_limit;
        $tableInfo['subscription']   = $subscription;

        //移除数组指定的key, 多个以逗号隔开
        $tableInfo = array_remove($tableInfo,"turnover_limit_l1,turnover_limit_l2,turnover_limit_l3,subscription_l1,subscription_l2,subscription_l3");
        /*特殊日期 匹配特殊定金 off*/


        $tableRevenueModel = new TableRevenue();

        $status_str = "0,1,2";

        $revenue_column = $tableRevenueModel->revenue_column;

        /*预约基本信息 On*/
        $revenueInfo = $tableRevenueModel
            ->alias("tr")
            ->join("user u","u.uid = tr.uid","LEFT")
            ->join("user_card uc","uc.uid = tr.uid","LEFT")
            ->join("mst_user_level ul","ul.level_id = u.level_id","LEFT")
            ->join("manage_salesman ms","ms.sid = tr.ssid","LEFT")
            ->where('tr.table_id',$table_id)
            ->whereTime("tr.reserve_time","between",["$begin_time","$end_time"])
            ->where("tr.status","IN",$status_str)
            ->where(function ($query){
                $query->where('tr.parent_trid',Null);
                $query->whereOr('tr.parent_trid','');
            })
            ->field("u.name,u.phone user_phone,u.nickname,u.level_id,u.credit_point")
            ->field("ul.level_name")
            ->field("uc.card_name,uc.card_type")
            ->field("ms.phone sales_phone")
            ->field($revenue_column)
            ->find();

        $revenueInfo = json_decode(json_encode($revenueInfo),true);

        if (!empty($revenueInfo)){
            $revenueInfo["reserve_time"] = date("H:i",$revenueInfo["reserve_time"]);
        }

        $tableInfo["revenueInfo"] = $revenueInfo;

//        $tableInfo = _unsetNull($tableInfo);

        return $this->com_return(true,config("params.SUCCESS"),$tableInfo);
    }

    /**
     * 预约
     * @param Request $request
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function createReservation(Request $request)
    {
        $table_id       = $request->param("table_id","");//预定桌id

        $subscription   = $request->param("subscription","");//预约定金

        $turnover_limit = $request->param("turnover_limit","");//低消

        $user_phone     = $request->param("user_phone","");//用户电话

        $user_name      = $request->param("user_name","");//用户姓名

        $sales_phone    = $request->param("sales_phone","");//营销电话

        $date           = $request->param("date","");//到店日期

        $go_time        = $request->param("go_time","");//到店时间

        $rule = [
            "subscription|预约定金"  => "require",
            "turnover_limit|低消"   => "require",
            "user_phone|用户电话"    => "require|regex:1[3-8]{1}[0-9]{9}",
            "sales_phone|营销电话"   => "regex:1[3-8]{1}[0-9]{9}",
            "date|到店日期"          => "require",
            "table_id|桌id"         => "require",
            "go_time|到店时间"       => "require",
        ];

        $request_res = [
            "subscription"      => $subscription,
            "turnover_limit"    => $turnover_limit,
            "table_id"          => $table_id,
            "user_phone"        => $user_phone,
            "sales_phone"       => $sales_phone,
            "date"              => $date,
            "go_time"           => $go_time,
        ];

        $validate = new Validate($rule);

        if (!$validate->check($request_res)){
            return $this->com_return(false,$validate->getError(),null);
        }

        /*登陆前台人员信息 on*/
        $token = $request->header("Token",'');
        $manageInfo = $this->tokenGetManageInfo($token);

        $stype_name = $manageInfo["stype_name"];
        $sales_name = $manageInfo["sales_name"];

        $adminUser = $stype_name . " ". $sales_name;
        /*登陆前台人员信息 off*/


        $nowTime = time();
        //根据营销电话获取营销信息
        $salesModel = new ManageSalesman();
        $salesStatusStr = config("salesman.salesman_type")[0]['key'].",".config("salesman.salesman_type")[1]['key'].",".config("salesman.salesman_type")[4]['key'];

        $salesInfo = $salesModel
            ->alias("ms")
            ->join("mst_salesman_type st","st.stype_id = ms.stype_id")
            ->where("ms.phone",$sales_phone)
            ->where("ms.statue",config("salesman.salesman_status")['working']['key'])
            ->where("st.stype_key","IN",$salesStatusStr)
            ->field("ms.sid,ms.sales_name")
            ->field("st.stype_key")
            ->find();

        $salesInfo = json_decode(json_encode($salesInfo),true);

        if (!empty($salesInfo)){
            $sid        = $salesInfo["sid"];
            $sales_name = $salesInfo["sales_name"];
            $stype_key  = $salesInfo["stype_key"];

        }else{
            $sid        = config("salesman.salesman_type")[3]['key'];
            $sales_name = config("salesman.salesman_type")[3]['name'];
            $stype_key  = config("salesman.salesman_type")[3]['key'];
        }

        //查询是否存在此用户
        $userModel = new User();

        $userInfo = $userModel
            ->alias("u")
            ->join("user_card uc","uc.uid = u.uid","LEFT")
            ->where("u.phone",$user_phone)
            ->field("u.uid,u.phone,u.name,u.nickname,u.user_status")
            ->field("uc.card_id,uc.card_name,uc.card_type")
            ->find();

        $userInfo = json_decode(json_encode($userInfo),true);

        if (!empty($userInfo)){
            $uid      = $userInfo["uid"];
            $card_id  = $userInfo["card_id"];
        }else{
            //新建用户
            $UUID = new UUIDUntil();

            $uid = $UUID->generateReadableUUID("U");
            $user_params = [
                "uid"           => $uid,
                "phone"         => $user_phone,
                "name"          => $user_name,
                "avatar"        => getSysSetting("sys_default_avatar"),
                "sex"           => config("user.default_sex"),
                "password"      => sha1(config("DEFAULT_PASSWORD")),
                "register_way"  => config("user.register_way")['web']['key'],
                "user_status"   => config("user.user_register_status")['register']['key'],
                "referrer_type" => $stype_key,
                "referrer_id"   => $sid,
                "created_at"    => $nowTime,
                "updated_at"    => $nowTime
            ];

            //插入新的用户信息
            $userModel->insert($user_params);

            $card_id  = "";
        }

        //查看当前桌子是否是限定桌
        $tableModel        = new \app\Reception\model\MstTable();
        $tableCardModel    = new MstTableCard();
        $tableRevenueModel = new TableRevenue();

        $tableInfo = $tableModel
            ->where("table_id",$table_id)
            ->field("area_id,table_no")
            ->find();
        $tableInfo = json_decode(json_encode($tableInfo),true);

        $area_id = $tableInfo['area_id'];

        $table_no = $tableInfo['table_no'];

        $tableCardInfo = $tableCardModel
            ->where("table_id",$table_id)
            ->select();

        $tableCardInfo = json_decode(json_encode($tableCardInfo),true);

        //判断桌台是否限定
        $is_xd = false;
        if (!empty($tableCardInfo)){

            //限制预定
            if (!empty($card_id)){

                $card_id_arr = [];
                foreach ($tableCardInfo as $key => $val){
                    $card_id_arr[$key] = $val['card_id'];
                }

                $in_array_res = in_array($card_id,$card_id_arr);

                if (!$in_array_res){
                    $is_xd = true;
                }
            }

        }

        if ($is_xd){
            return $this->com_return(false,config("params.REVENUE")['XD_TABLE_FALL']);
        }

        $reserve_way     = config("user.register_way")['web']['key'];

        $publicActionObj = new \app\wechat\controller\PublicAction();

        $is_reception = "reception";//前台预约参数

        $revenueReturn   = $publicActionObj->confirmReservationPublic("$sales_phone","$table_id","$date","$go_time","$subscription","$turnover_limit","$reserve_way","$uid","$is_reception");

        if ($revenueReturn["result"] && ($subscription > 0)){

            $suid = $revenueReturn["data"];

            $billModel = new BillSubscription();

            $updateParams = [
                "status"        => config("order.reservation_subscription_status")['Paid']['key'],
                "pay_time"      => $nowTime,
                "pay_type"      => config("order.pay_method")['cash']['key'],
                "updated_at"    => $nowTime
            ];

            $updateBillReturn = $billModel
                ->where("suid",$suid)
                ->update($updateParams);

            $updateRevenueParams = [
                "status" => config("order.table_reserve_status")['reserve_success']['key'],
                "updated_at" => $nowTime
            ];

            $trid_info = $billModel
                ->where("suid",$suid)
                ->field("trid")
                ->find();
            $trid_info = json_decode(json_encode($trid_info),true);

            $trid = $trid_info["trid"];

            $updateRevenueReturn = $tableRevenueModel
                ->where("trid",$trid)
                ->update($updateRevenueParams);

            if ($updateBillReturn !== false && $updateRevenueReturn !== false){
                Db::commit();

                $date = date("Y-m-d",$date);

                $reserve_date = $date." ".$go_time;

                $desc = " 为用户 ".$user_name."($user_phone)"." 预约 $reserve_date 的".$table_no."桌";

                $type = config("order.table_action_type")['revenue_table']['key'];
                //记录日志
                insertTableActionLog(microtime(true) * 10000,"$type","$table_id","$table_no","$sales_name",$desc,"","");

                /*发送短信 On*/
                $smsObj = new Sms();
                $smsObj->sendMsg("$user_phone","revenue","$reserve_date","$table_no");
                /*发送短信 Off*/

                return $this->com_return(true,config("params.SUCCESS"));
            }else{
                return $this->com_return(false,config("params.FAIL"));
            }

        }else{
            return $revenueReturn;
        }
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
        $trid = $request->param("trid","");//台位id

        if (empty($trid)){
            return $this->com_return(false,config("params.PARAM_NOT_EMPTY"));
        }

        //获取当前台位信息
        $tableInfo = $this->getTableInfo($trid);

        $status = $tableInfo['status'];//获取当前台位状态

        if ($status == config("order.table_reserve_status")['already_open']['key']){
            return $this->com_return(false,"已开台,不可取消预约");
        }

        if ($status == config("order.table_reserve_status")['clear_table']['key']){
            return $this->com_return(false,"已消费,不可取消预约");
        }

        if ($status == config("order.table_reserve_status")['cancel']['key']){
            return $this->com_return(false,"已取消,不可重复操作");
        }

        $is_subscription = $tableInfo['is_subscription'];//是否收取定金1 是  0否

        $time = time();

        /*登陆管理人员信息 on*/
        $token = $request->header("Token",'');

        $manageInfo = $this->tokenGetManageInfo($token);

        $stype_name = $manageInfo["stype_name"];
        $sales_name = $manageInfo["sales_name"];

        $adminUser = $stype_name . " ". $sales_name;
        /*登陆管理人员信息 off*/

        Db::startTrans();
        try{
            if ($status == config("order.table_reserve_status")['pending_payment']['key']){
                //如果是待支付状态
                if ($is_subscription) {
                    //如果是收取定金
                    $table_params = [
                        "status"        => config("order.table_reserve_status")['cancel']['key'],
                        "cancel_user"   => $adminUser,
                        "cancel_time"   => $time,
                        "cancel_reason" => "未付款时, ".$sales_name." 手动取消",
                        "updated_at"    => $time
                    ];

                    $bill_params = [
                        "status"        => config("order.reservation_subscription_status")['cancel']['key'],
                        "cancel_user"   => $adminUser,
                        "cancel_time"   => $time,
                        "auto_cancel"   => 0,
                        "cancel_reason" => "未付款时, ".$sales_name." 手动取消",
                        "updated_at"    => $time

                    ];

                    $tableReturn = $this->updatedTableRevenueInfo($table_params,$trid);
                    $billReturn  = $this->updatedBillSubscription($bill_params,$trid);


                }else{
                    //如果是不收取定金
                    $table_params = [
                        "status"        => config("order.table_reserve_status")['cancel']['key'],
                        "cancel_user"   => $adminUser,
                        "cancel_time"   => $time,
                        "cancel_reason" => "无需缴纳定金, ".$sales_name." 手动取消",
                        "updated_at"    => $time
                    ];

                    $tableReturn = $this->updatedTableRevenueInfo($table_params,$trid);
                    $billReturn  = true;

                }

            }else{
                //如果已支付
                //获取系统设置的最晚取消时间
                $cardObj = new OpenCard();
                $reserve_cancel_time = $cardObj->getSysSettingInfo("reserve_cancel_time");

                $reserve_time = $tableInfo['reserve_time'];//预约时间

                $kc_date = date("Y-m-d",$reserve_time);

                $kc_time = $kc_date." ".$reserve_cancel_time;//最晚取消时间

                $kc_time = strtotime($kc_time);

                $now_time = time();

                if ($is_subscription){
                    //如果收取定金
                    if ($now_time > $kc_time){

                        //如果退款时,已超时,则不退定金
                        $table_params = [
                            "status"        => config("order.table_reserve_status")['cancel']['key'],
                            "cancel_user"   => $adminUser,
                            "cancel_time"   => $time,
                            "cancel_reason" => "已付款,超出取消时间范围内, ".$sales_name." 手动取消",
                            "updated_at"    => $time
                        ];

                        $bill_params = [
                            "status"        => config("order.reservation_subscription_status")['cancel_revenue']['key'],
                            "cancel_user"   => $adminUser,
                            "cancel_time"   => $time,
                            "auto_cancel"   => 0,
                            "cancel_reason" => "已付款,超出取消时间范围内, ".$sales_name." 手动取消",
                            "updated_at"    => $time
                        ];

                        $tableReturn = $this->updatedTableRevenueInfo($table_params,$trid);
                        $billReturn  = $this->updatedBillSubscription($bill_params,$trid);
                    }else{
                        //如果退款时,未超时,则退还定金
                        $billInfo     = $this->getBillSubscriptionInfo($trid);
                        Log::info("取消用户预约 参数 ---- ".var_export($billInfo,true));
                        $subscription = $billInfo['subscription'];
                        $suid         = $billInfo['suid'];
                        $pay_type     = $billInfo['pay_type'];

                        //微信支付时,走退款接口
                        if ($pay_type == config("order.pay_method")['wxpay']['key']){
                            $payRes = $this->callBackPay($suid,$subscription,$subscription);
                        }else{
                            $payRes = "其他支付";
                        }

                        Log::info("取消预约,退款流程返回 ----- ".var_export($payRes,true));

                        if (!empty($payRes)){

                            $table_params = [
                                "status"        => config("order.table_reserve_status")['cancel']['key'],
                                "cancel_user"   => $adminUser,
                                "cancel_time"   => $time,
                                "cancel_reason" => "已付款,未超出取消时间范围内, ".$sales_name." 手动取消",
                                "updated_at"    => $time
                            ];

                            $bill_params = [
                                "status"        => config("order.reservation_subscription_status")['cancel_revenue']['key'],
                                "cancel_user"   => $adminUser,
                                "cancel_time"   => $time,
                                "auto_cancel"   => 0,
                                "cancel_reason" => "已付款,超出取消时间范围内, ".$sales_name." 手动取消",
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

                }else{
                    //不收取定金
                    //如果没收取定金
                    $table_params = [
                        "status"        => config("order.table_reserve_status")['cancel']['key'],
                        "cancel_user"   => $adminUser,
                        "cancel_time"   => $time,
                        "cancel_reason" => "已预约,不用支付定金, ".$sales_name." 手动取消",
                        "updated_at"    => $time
                    ];
                    $tableReturn = $this->updatedTableRevenueInfo($table_params,$trid);
                    $billReturn = true;
                }
            }
            if ($tableReturn && $billReturn){
                Db::commit();

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

                $desc = " 为用户 ".$userName."($userPhone)"." 取消 $reserve_date ".$table_no."桌的预约";

                //取消预约记录日志
                insertTableActionLog(microtime(true) * 10000,"$type","$table_id","$table_no","$sales_name",$desc,"","");
                /*记录日志 off*/

                /*发送短信 On*/
                $smsObj = new Sms();
                $smsObj->sendMsg("$userPhone","cancel","$reserve_date","$table_no");
                /*发送短信 Off*/

                return $this->com_return(true,config("params.SUCCESS"));
            }else{
                return $this->com_return(false,config("params.FAIL"));
            }
        }catch (Exception $e){
            Db::startTrans();
            $this->com_return(false,$e->getMessage());
        }
    }

    /**
     * 到店
     * @param Request $request
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function goToShop(Request $request)
    {
        $trid = $request->param("trid","");

        $trInfo = Db::name("table_revenue")
            ->where("trid",$trid)
            ->find();

        $trInfo = json_decode(json_encode($trInfo),true);

        if (empty($trInfo)){
            return $this->com_return(false,config("params.ABNORMAL_ACTION"));
        }

        $subscription_type = $trInfo['subscription_type'];
        $subscription      = $trInfo['subscription'];

        $status_now = config("order.table_reserve_status")['go_to_table']['key'];

        $publicObj  = new PublicAction();

        Db::startTrans();
        try{

            $changeTableStatus = $publicObj->changeRevenueTableStatus($trid,$status_now);

            if (!$changeTableStatus){
                return $this->com_return(false,config("params.ABNORMAL_ACTION"));
            }

            if ($subscription_type == config("order.subscription_type")['subscription']['key']){
                //如果预约定金类型为定金 1
                if ($subscription > 0){
                    //此时执行开台成功,定金退还操作
                    $suid_info = Db::name("bill_subscription")
                        ->where("trid",$trid)
                        ->field("suid")
                        ->find();
                    $suid_info = json_decode(json_encode($suid_info),true);

                    $suid = $suid_info["suid"];

                    $diningRoomObj = new DiningRoom();

                    $refund_return = $diningRoomObj->refundDeposit($suid,$subscription);

                    $res = json_decode($refund_return,true);

                    if (isset($res["result"])){
                        if ($res["result"]){
                            //退款成功则变更定金状态
                            $status = config("order.reservation_subscription_status")['open_table_refund']['key'];
                            $params = [
                                "status"        => $status,
                                "is_refund"     => 1,
                                "refund_amount" => $subscription,
                                "updated_at"    => time()
                            ];

                            Db::name("bill_subscription")
                                ->where("suid",$suid)
                                ->update($params);

                        }else{
                            return $res;
                        }
                    }else{
                        return $res;
                    }
                    Log::info("到店退押金 ---- ".var_export($refund_return,true));
                }
            }

            Db::commit();
            return $this->com_return(true,config("params.SUCCESS"));

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

        if ($is_ok){
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