<?php
/**
 * Created by PhpStorm.
 * User: qubojie
 * Date: 2018/7/31
 * Time: 上午11:08
 */
namespace app\wechat\controller;

use app\admin\model\ManageSalesman;
use app\admin\model\MstTable;
use app\admin\model\MstTableAppearance;
use app\admin\model\MstTableArea;
use app\admin\model\MstTableImage;
use app\admin\model\MstTableLocation;
use app\admin\model\MstTableReserveDate;
use app\admin\model\MstTableSize;
use app\admin\model\TableRevenue;
use app\common\controller\UUIDUntil;
use app\wechat\model\BillRefill;
use app\wechat\model\BillSubscription;
use think\Config;
use think\Controller;
use think\Db;
use think\Exception;

class PublicAction extends Controller
{
    /**
     * 预约列表公共部分
     * @param $size_id
     * @param $location_id
     * @param $appointment
     * @param $user_card_id
     * @param $pagesize
     * @param $config
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function reservationPublic($size_id,$location_id,$appointment,$user_card_id,$pagesize,$config)
    {
        $tableModel        = new MstTable();
        $tableImageModel   = new MstTableImage();
        $tableRevenueModel = new TableRevenue();

        $appointment = (int)$appointment;

        $where_card = [];
        if (!empty($user_card_id)){
           $where_card['tac.card_id'] = ["eq",$user_card_id];
        }

        $location_where = [];
        if (!empty($location_id)){
            $location_where['ta.location_id'] = $location_id;
        }

        $res = $tableModel
            ->alias("t")
            ->join("mst_table_area ta","ta.area_id = t.area_id")//区域
            ->join("mst_table_location tl","tl.location_id = ta.location_id")//位置
            ->join("mst_table_size ts","ts.size_id = t.size_id")//人数
            ->join("mst_table_appearance tap","tap.appearance_id = t.appearance_id")//品项
            ->join("mst_table_area_card tac","tac.area_id = ta.area_id","LEFT")//卡
            ->where("t.is_enable",1)
            ->where("t.is_delete",0)
            ->where($location_where)
            ->group("t.table_id")
            ->order('t.sort')
            ->field("t.table_id,t.table_no,t.turnover_limit_l1,t.turnover_limit_l2,t.turnover_limit_l3,t.subscription_l1,t.subscription_l2,t.subscription_l3,t.people_max,t.table_desc")
            ->field("ta.area_id,ta.area_title,ta.area_desc,ta.sid")
            ->field("tl.location_title")
            ->field("ts.size_title")
            ->field("tap.appearance_title")
            ->select();

        $res = json_decode(json_encode($res),true);

        $pending_payment = config("order.table_reserve_status")['pending_payment']['key'];//待付定金或结算
        $reserve_success = config("order.table_reserve_status")['reserve_success']['key'];//预定成功
        $already_open    = config("order.table_reserve_status")['already_open']['key'];//已开台
        $clear_table     = config("order.table_reserve_status")['clear_table']['key'];//已清台
        $cancel          = config("order.table_reserve_status")['cancel']['key'];//取消预约

        $can_not_reserve = $pending_payment.",".$reserve_success.",".$already_open;

        $where_status['status'] = array('IN',"$can_not_reserve");//查询字段的值在此范围之内的做显示

        $appointment_end   = $appointment + 24 * 60 * 60;
        $appointment_start = date("Y-m-d H:i:s",$appointment);
        $appointment_end   = date("Y-m-d H:i:s",$appointment_end);

        foreach ($res as $k => $v){

            $table_id = $v['table_id'];

            $table_reserve_exist = $tableRevenueModel
                ->where('table_id',$table_id)
                ->where($where_status)
                ->whereTime("reserve_time","between",["$appointment_start","$appointment_end"])
                ->count();

            if ($table_reserve_exist){
                unset($res[$k]);
            }
        }

        $res = array_values($res);

        for ($q = 0; $q < count($res); $q ++){
            $area_id = $res[$q]['area_id'];
            $cardInfo = Db::name("mst_table_area_card")
                ->alias("tac")
                ->join("mst_card_vip cv","cv.card_id = tac.card_id")
                ->where('tac.area_id',$area_id)
                ->field("cv.card_id")
                ->select();

            $cardInfo = json_decode(json_encode($cardInfo),true);

            $res[$q]['card_id_group'] = $cardInfo;

        }

        if (!empty($user_card_id)){
            //查找那些区域绑定了该卡
            //获取限制区域的卡信息
            $area_card_info = Db::name('mst_table_area_card')
                ->where("card_id",$user_card_id)
                ->select();

            $area_card_info = json_decode(json_encode($area_card_info),true);

            if (empty($area_card_info)){
                //未有区域绑定该卡
                foreach ($res as $key => $val){

                    if (!empty($val['card_id_group'])){
                        unset($res[$key]);
                    }
                }

            }else{

                $my_card_id[] = $user_card_id;

                //有区域绑定该卡
                foreach ($res as $key => $val){

                    //获取有限制的桌台
                    if ($val['card_id_group'] != ""){

                        $card_id_group = $val['card_id_group'];

                        if (!empty($card_id_group)){

                            foreach ($card_id_group as $k => $v){
                                $card_id_group[$k] = $v['card_id'];
                            }
                            //如果有交集,则返回交集,否则返回空数组
                            $intersection = array_intersect($my_card_id,$card_id_group);

                            if (empty($intersection)){
                                //无交集
                                unset($res[$key]);
                            }
                        }

                    }
                }
            }

        }else{
            foreach ($res as $key => $val){
                if (!empty($val['card_id_group'])){
                    unset($res[$key]);
                }
            }
        }

        $res = array_values($res);

        for ($i = 0; $i < count($res); $i++){
            $table_id = $res[$i]['table_id'];
            //如果有设置,则取设置的强制定金,否则,就是桌子的定金
            /*特殊日期 匹配特殊定金 on*/
            $dateList = $this->isReserveDate($appointment);

            if (!empty($dateList)){
                //是特殊日期

                $type          = $dateList['type'];//日期类型   0普通日  1周末假日  2节假日

                if ($type == "0"){
                    $turnover_limit = $res[$i]['turnover_limit_l1'];//平时预约最低消费
                    $subscription   = $res[$i]['subscription_l1'];//平时预约定金
                }elseif ($type == "1"){
                    $turnover_limit = $res[$i]['turnover_limit_l2'];//周末日期预约最低消费
                    $subscription   = $res[$i]['subscription_l2'];//周末日期预约定金
                }elseif ($type == "2"){
                    $turnover_limit = $res[$i]['turnover_limit_l3'];//特殊日期预约最低消费
                    $subscription   = $res[$i]['subscription_l3'];//特殊日期预约定金
                }else{
                    $turnover_limit = $res[$i]['turnover_limit_l1'];//平时预约最低消费
                    $subscription   = $res[$i]['subscription_l1'];//平时预约定金
                }

            }else{
                //不是特殊日期

                //查看预约日期是否是周末日期
                $today_week = getTimeWeek($appointment);

                $openCardObj = new OpenCard();

                $reserve_subscription_week = $openCardObj->getSysSettingInfo("reserve_subscription_week");

                $is_bh = strpos("$reserve_subscription_week","$today_week");

                if ($is_bh !== false){
                    //如果包含,则获取特殊星期的押金和低消
                    $turnover_limit = $res[$i]['turnover_limit_l2'];//周末日期预约最低消费
                    $subscription   = $res[$i]['subscription_l2'];//周末日期预约定金

                }else{
                    //如果不包含
                    $turnover_limit = $res[$i]['turnover_limit_l1'];//平时预约最低消费
                    $subscription   = $res[$i]['subscription_l1'];//平时预约定金
                }
            }
            /*特殊日期 匹配特殊定金 off*/


            $res[$i]['turnover_limit']   = $turnover_limit;

            $res[$i]['subscription']   = $subscription;

            $res[$i]['image_group']    = [];

            $image_res = $tableImageModel
                ->where('table_id',$table_id)
                ->select();

            $image_res = json_decode(json_encode($image_res),true);

            for ($m = 0; $m < count($image_res); $m++){
                $res[$i]['image_group'][] = $image_res[$m]['image'];
            }

        }

        $res_data["data"] = $res;

        return $res_data;

    }

    /**
     * 判断当前是否在特殊日期内,如果是,则返回低消和定金
     * @param $appointment
     * @return array|false|mixed|\PDOStatement|string|\think\Model
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    protected function isReserveDate($appointment)
    {
        $reserveDateModel = new MstTableReserveDate();

        //将用户约定时间 转化为当日零点的时间戳
        $appointment = strtotime(date("Ymd",$appointment));

        $dateList = $reserveDateModel
            ->where("is_expiry","1")
            ->where("appointment",$appointment)
            ->find();

        $dateList = json_decode(json_encode($dateList),true);

        return $dateList;
    }


    /**
     * 预约定金确认公共部分
     * @param $sales_phone
     * @param $table_id
     * @param $date
     * @param $time
     * @param $subscription
     * @param $turnover_limit
     * @param $reserve_way
     * @param $uid
     * @param string $is_reception
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function confirmReservationPublic($sales_phone,$table_id,$date,$time,$subscription,$turnover_limit,$reserve_way,$uid,$is_reception = "")
    {
        //如果报名营销验证营销是否存在
        if (!empty($sales_phone)){
            $salesmanInfo  = $this->phoneGetSalesmanInfo($sales_phone);

            if (empty($salesmanInfo)){
                //如果未查到,提示用户核对手机号码
                return $this->com_return(false,config("params.SALESMAN_PHONE_ERROR"));
            }

            $ssid   = $salesmanInfo['sid'];
            $ssname = $salesmanInfo['sales_name'];

        }else{
            //如果营销电话为空,则隶属平台数据
            $ssid   = \config("salesman.salesman_type")['3']['key'];
            $ssname = \config("salesman.salesman_type")['3']['name'];
        }

        //判断当前桌子是否可预约
        $is_can_reserve = $this->tableStatusCan("$table_id","$date");

        if (!$is_can_reserve){
            //false时 该吧台当天已被其他顾客预约
            return $this->com_return(false,\config("params.TABLE_IS_RESERVE"));
        }

        $date = date("Y-m-d",$date);

        $reserve_date = $date." ".$time;

        //预约时间
        $reserve_time = strtotime($reserve_date);

        //获取是否是特殊日期,是否退换预约押金
        $is_refund_sub = $this->revenueDateRefundSub($reserve_time);

        Db::startTrans();
        try{

            $UUID = new UUIDUntil();

            //首先生成trid,押金订单使用
            $trid = $UUID->generateReadableUUID("T");

            //订单押金id
            $suid = $UUID->generateReadableUUID("SU");

            //如果没有押金
            if ($subscription <= 0){
                //预定成功状态1
                $status            = Config::get("order.table_reserve_status")['reserve_success']['key'];
                $is_subscription   = 0;
                $subscription_type = Config::get("order.subscription_type")['null_subscription']['key'];

                //不创建缴押金订单,返回true
                $billSubscriptionReturn = true;


            }else{
                //如果有押金生成待缴费定金订单

                if (!empty($is_reception)){
                    //如果为前台预约,则不创建定金订单,直接预约成功
                    //预定成功状态1
                    $status            = Config::get("order.table_reserve_status")['reserve_success']['key'];
                    $is_subscription   = 0;
                    $subscription_type = Config::get("order.subscription_type")['null_subscription']['key'];

                    //不创建缴押金订单,返回true
                    $billSubscriptionReturn = true;
                }else{

                    //待付定金或结算状态 0
                    $status            = Config::get("order.table_reserve_status")['pending_payment']['key'];
                    $is_subscription   = 1;
                    $subscription_type = Config::get("order.subscription_type")['subscription']['key'];

                    //创建缴押金订单,返回相应数据
                    $billSubscriptionReturn = $this->billSubscriptionCreate("$suid","$trid","$uid","$subscription","$is_refund_sub");
                }
            }

            //去创建预约吧台订单信息
            $createRevenueReturn = $this->createRevenueOrder("$trid","$uid","$ssid","$ssname","$table_id","$status","$turnover_limit","$reserve_way","$reserve_time","$is_subscription","$subscription_type","$subscription");


            if ($billSubscriptionReturn && $createRevenueReturn){
                Db::commit();
                /*记录预约日志 on*/
                if ($reserve_way == \config("order.reserve_way")['client']['key']){
                    //如果是客户预定
                    $userInfo = getUserInfo($uid);
                    $action_user = $userInfo["name"];

                    $tableInfo = $this->tableIdGetInfo($table_id);

                    $table_no = $tableInfo['table_no'];

                    $desc = $action_user." 预约 $reserve_date 的".$table_no."桌";

                    $type = config("order.table_action_type")['revenue_table']['key'];
                    //记录日志
                    insertTableActionLog(microtime(true) * 10000,"$type","$table_id","$table_no","$action_user",$desc,"","");


                }else{
                    //如果是服务人员预定
                    $action_user = $ssname;
                }


                /*记录预约日志 off*/

                return $this->com_return(true,\config("params.SUCCESS"),$suid);
            }else{
                return $this->com_return(false,\config("params.FAIL"));

            }
        }catch (Exception $e){
            Db::rollback();
            return $this->com_return(false,$e->getMessage());
        }
    }


    /**
     * 判断预约时 是否可退押金
     * @param $reserve_time
     * @return int
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function revenueDateRefundSub($reserve_time)
    {
        $begin_time = strtotime(date("Ymd",$reserve_time));

        $openCardObj = new OpenCard();

        //获取系统开关 0退  1不退
        $is_refund_sys = $openCardObj->getSysSettingInfo("reserve_refund_flag");

        if ($is_refund_sys == "1"){

            $is_refund_sub = 1;//系统设置,不退押金

            return $is_refund_sub;

        }

        $reserveDateModel = new MstTableReserveDate();

        $is_exist = $reserveDateModel
            ->where("appointment",$begin_time)
            ->where("is_expiry","1")
            ->find();

        $is_exist = json_decode(json_encode($is_exist),true);

        if (empty($is_exist)){

            $is_refund_sub = 0;//未设置特殊日期,退换押金

            return $is_refund_sub;

        }

        $is_refund_sub = $is_exist['is_refund_sub'];//是否可退押金 0退  1不退

        if ($is_refund_sub == "1"){

            $is_refund_sub = 1;//特殊日期,设置不退押金

        }else{
            $is_refund_sub = 0;//特殊日期,设置退押金
        }

        return $is_refund_sub;
    }



    /**
     * 根据营销手机号码获取营销人员信息
     * @param $phone
     * @return array|false|mixed|\PDOStatement|string|\think\Model
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function phoneGetSalesmanInfo($phone)
    {
        $salesModel = new ManageSalesman();

        $salesmanInfo = $salesModel
            ->where("phone",$phone)
            ->field("sid,department_id,stype_id,sales_name,statue,phone,nickname,avatar,sex")
            ->find();

        $salesmanInfo = json_decode(json_encode($salesmanInfo),true);

        return $salesmanInfo;

    }

    /**
     * 查看当前台位是否可以被预定
     * @param $table_id
     * @param $date
     * @return bool
     */
    public function tableStatusCan($table_id,$date)
    {
        $tableRevenueModel = new TableRevenue();

        $pending_payment = config("order.table_reserve_status")['pending_payment']['key'];//待付定金或结算
        $reserve_success = config("order.table_reserve_status")['reserve_success']['key'];//预定成功
        $already_open    = config("order.table_reserve_status")['already_open']['key'];//已开台
        $clear_table     = config("order.table_reserve_status")['clear_table']['key'];//已清台
        $cancel          = config("order.table_reserve_status")['cancel']['key'];//取消预约

        $can_not_reserve = $pending_payment.",".$reserve_success.",".$already_open;

        $where_status['status'] = array('IN',"$can_not_reserve");//查询字段的值在此范围之内的做显示

        //获取当天的24点的时间戳
        $date_end = $date + 24 * 60 * 60;

        $is_exist = $tableRevenueModel
            ->where('table_id',$table_id)
            ->where($where_status)
            ->whereTime('reserve_time','between',["$date","$date_end"])
            ->count();

        if ($is_exist > 0){
            return false;
        }else{
            return true;
        }
    }

    /**
     * 创建预定定金缴费单
     * @param $suid
     * @param $trid
     * @param $uid
     * @param $subscription
     * @param $is_refund_sub
     * @param string $pay_type
     * @return bool
     */
    protected function billSubscriptionCreate($suid,$trid,$uid,$subscription,$is_refund_sub,$pay_type = "wxpay")
    {
        $billSubscriptionModel = new BillSubscription();

        $time = time();

        $params = [
            'suid'          => $suid,
            'trid'          => $trid,
            'uid'           => $uid,
            'status'        => config("order.reservation_subscription_status")['pending_payment']['key'],
            'subscription'  => $subscription,
            'is_refund_sub' => $is_refund_sub,
            'pay_type'      => $pay_type,
            'created_at'    => $time,
            'updated_at'    => $time
        ];

        $is_ok = $billSubscriptionModel
            ->insert($params);

        if ($is_ok){
            return true;
        }else{
            return false;
        }

    }

    /**
     * 台位预定订单生成
     * @param $trid
     * @param $uid
     * @param $ssid
     * @param $ssname
     * @param $table_id
     * @param $status
     * @param $turnover_limit
     * @param $reserve_way
     * @param $reserve_time
     * @param $is_subscription
     * @param $subscription_type
     * @param int $subscription
     * @param int $turnover
     * @return array|bool
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function createRevenueOrder($trid,$uid,$ssid,$ssname,$table_id,$status,$turnover_limit,$reserve_way,$reserve_time,$is_subscription,$subscription_type,$subscription = 0,$turnover = 0)
    {
        $time = time();

        //根据桌id获取桌信息
        $tableInfo = $this->tableIdGetInfo("$table_id");

        if (empty($tableInfo)){
            return $this->com_return(false,\config("params.TABLE_INVALID"));
        }

        $table_no = $tableInfo['table_no'];
        $area_id  = $tableInfo['area_id'];
        $sid      = $tableInfo['sid'];
        $sname    = $tableInfo['sales_name'];

        $turnover_num = 0;

        /*if ($turnover > 0){
            $turnover_num = 1;
        }*/

        $params = [
            'trid'              => $trid,               //台位预定id  前缀T
            'uid'               => $uid,                //用户id
            'is_join'           => 0,                   //是否拼桌 0否  1是
            'table_id'          => $table_id,           //酒桌id
            'table_no'          => $table_no,           //台号
            'area_id'           => $area_id,            //区域id
            'status'            => $status,             //订台状态   0待付定金或结算   1 预定成功   2已开台  3已清台   9取消预约
            'turnover_limit'    => $turnover_limit,     //台位最低消费 0表示无最低消费（保留）
            'reserve_way'       => $reserve_way,        //预定途径  client  客户预订（小程序）    service 服务端预定（内部小程序）   manage 管理端预定（pc管理端暂保留）
            'reserve_time'      => $reserve_time,       //预约时间
            'ssid'              => $ssid,               //订台服务人员id
            'ssname'            => $ssname,             //订台服务人员姓名
            'sid'               => $sid,                //服务人员id
            'sname'             => $sname,              //服务人员姓名
            'is_subscription'   => $is_subscription,    //是否收取定金或订单  0 是  1 否
            'subscription_type' => $subscription_type,  //定金类型   0无订金   1订金   2订单
            'subscription'      => $subscription,       //订金金额
            'turnover_num'      => $turnover_num,       //台位订单数量
            'turnover'          => $turnover,           //订单金额
            'created_at'        => $time,
            'updated_at'        => $time
        ];

        $is_ok = $this->RevenueOrderC($params);

        return $is_ok;
    }


    /**
     * 根据桌id获取桌信息
     * @param $table_id
     * @return array|false|\PDOStatement|string|\think\Model
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function tableIdGetInfo($table_id)
    {
        $tableModel = new MstTable();

        $column = $tableModel->column;

        for ($i = 0; $i < count($column); $i++){
            $column[$i] = "t.".$column[$i];
        }


        $tableInfo = $tableModel
            ->alias('t')
            ->join('mst_table_area ta','ta.area_id = t.area_id')
            ->join('manage_salesman s','s.sid = ta.sid')
            ->where('t.table_id',$table_id)
            ->where('t.is_enable',1)
            ->where('t.is_delete',0)
            ->field($column)
            ->field('ta.sid')
            ->field('s.sales_name')
            ->find();
        $tableInfo = json_decode(json_encode($tableInfo),true);

        return $tableInfo;
    }

    /**
     * 更新或插入预约订单操作
     * @param array $params
     * @param null $trid
     * @return bool
     */
    protected function RevenueOrderC($params = array(),$trid = null)
    {
        $tableRevenueModel = new TableRevenue();

        if (empty($trid)){
            $is_ok = $tableRevenueModel
                ->insert($params);
        }else{
            $is_ok = $tableRevenueModel
                ->where('trid',$trid)
                ->update($params);
        }

        if ($is_ok){
            return true;
        }else{
            return false;
        }
    }


    /**
     * 筛选条件获取
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function reserveCondition()
    {
        /*获取位置和小区选项 on*/

        $tableLocationModel = new MstTableLocation();
        $table_location = $tableLocationModel
            ->where("is_delete",0)
            ->order("sort")
            ->field('location_id,location_title,location_desc')
            ->select();

        $table_location = json_decode(json_encode($table_location),true);

        $tableAreaModel = new MstTableArea();

        for ($i = 0; $i <count($table_location); $i++){
            $location_id = $table_location[$i]['location_id'];

            $table_area_info = $tableAreaModel
                ->where("location_id",$location_id)
                ->where("is_enable",1)
                ->where("is_delete",0)
                ->field("area_id,area_title,area_desc")
                ->order("sort")
                ->select();
            $table_area_info = json_decode(json_encode($table_area_info),true);

            $table_location[$i]["area_group"] = $table_area_info;

        }

        /*获取位置和小区选项 off*/


        $tableSizeModel = new MstTableSize();
        //获取容量选项
        $table_size = $tableSizeModel
            ->where('is_delete',0)
            ->order("sort")
            ->field('size_id,size_title,size_desc')
            ->select();

        $table_size = json_decode(json_encode($table_size),true);

        //获取品项选项
        $tableAppearanceModel = new MstTableAppearance();
        $table_appearance = $tableAppearanceModel
            ->where("is_delete",0)
            ->order("sort")
            ->field('appearance_id,appearance_title,appearance_desc')
            ->select();

        $table_appearance = json_decode(json_encode($table_appearance),true);

        //获取日期选项
        $openCardObj = new OpenCard();

        $reserve_before_day = $openCardObj->getSysSettingInfo("reserve_before_day");

        $date_select = [];

        if ($reserve_before_day){
            $now_time = time();

            //计算出指定日期的天数
            $today = strtotime(date("Ymd",$now_time));

            $date_s = 24 * 60 * 60;

            for ($i = 0; $i < ($reserve_before_day); $i++){
                $date_time = $today + $date_s * $i;

                $weekday = ["星期日","星期一","星期二","星期三","星期四","星期五","星期六",];

                $week = $weekday[date("w",$date_time)];

                $date_select[$i]["date"] = $date_time;

                $date_select[$i]["week"] = $week;
            }
        }

        $reserveDateModel = new MstTableReserveDate();

        foreach ($date_select as $key => $val){

            $can_date = $val["date"];

            $is_exist = $reserveDateModel
                ->where("appointment",$can_date)
                ->where("is_expiry","1")
                ->where("is_revenue","0")
                ->count();

            if ($is_exist){
                unset($date_select[$key]);
            }

        }

        $date_select = array_values($date_select);

        //获取可预约时间选项
        $reserve_time_frame = $openCardObj->getSysSettingInfo("reserve_time_frame");

        $reserve_time_frame_arr = explode("|",$reserve_time_frame);

        $time_arr = $this->timeToPart($reserve_time_frame_arr[0],$reserve_time_frame_arr[1]);

        $res['table_location']   = $table_location;
        $res['table_size']       = $table_size;
        $res['table_appearance'] = $table_appearance;
        $res['date_select']      = $date_select;
        $res['time_select']      = $time_arr;

        return $this->com_return(true,config("params.SUCCESS"),$res);
    }


    /**
     * 把指定时间段切份 - N份
     * -----------------------------------
     * @param string $start 开始时间
     * @param string $end 结束时间
     * @param int $nums 切分数目

     * @param boolean 是否格式化

     * @return array 时间段数组

     */
    protected function timeToPart($start,$end,$nums = 6, $format=true)
    {
        $start = strtotime($start);
        $end   = strtotime($end);
        $parts = ($end - $start)/$nums;
        $last  = ($end - $start)%$nums;
        if ( $last > 0) {
            $parts = ($end - $start - $last)/$nums;
        }
        for ($i=1; $i <= $nums+1; $i++) {
            $_end  = $start + $parts * $i;
            $arr[] = array($start + $parts * ($i-1), $_end);
        }
        $len = count($arr)-1;
        $arr[$len][1] = $arr[$len][1] + $last;
        if ($format) {
            foreach ($arr as $key => $value) {
                $arr[$key]['time'] = date("H:i", $value[0]);
//                $arr[$key][0] = date("H:i", $value[0]);
//                $arr[$key][1] = date("H:i", $value[1]);
                unset($arr[$key][0]);
                unset($arr[$key][1]);
            }
        }
        return $arr;
    }



    /**
     * 充值公共部分
     * @param $uid
     * @param $amount
     * @param $cash_gift
     * @param $status
     * @param $pay_type
     * @param string $referrer_phone
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function rechargePublicAction($uid,$amount,$cash_gift,$pay_type,$referrer_phone = '')
    {
        $manageSalesmanModel = new ManageSalesman();

        $referrer_id   = config("salesman.salesman_type")[3]['key'];
        $referrer_type = config("salesman.salesman_type")[3]['name'];

        if (!empty($referrer_phone)){
            //根据电话号码获取推荐营销信息
            $manageInfo = $manageSalesmanModel
                ->alias('ms')
                ->join('mst_salesman_type mst','mst.stype_id = ms.stype_id')
                ->where('ms.phone',$referrer_phone)
                ->where('ms.statue',config("salesman.salesman_status")['working']['key'])
                ->field('ms.sid,mst.stype_key')
                ->find();

            $manageInfo = json_decode(json_encode($manageInfo),true);

            if (!empty($manageInfo)){

                //只给营销记录,其他都算平台
                if ($manageInfo['stype_key'] == config("salesman.salesman_type")[0]['key'] ||$manageInfo['stype_key'] == config("salesman.salesman_type")[0]['key'] ) {
                    $referrer_id   = $manageInfo['sid'];
                    $referrer_type = $manageInfo['stype_key'];
                }
            }else{
                return $this->com_return(false,\config("params.SALESMAN_NOT_EXIST"));
            }
        }

        $time = time();

        $UUID = new UUIDUntil();

        //插入用户充值单据表
        $rfid = $UUID->generateReadableUUID("RF");

        $billRefillParams = [
            "rfid"          => $rfid,
            "referrer_type" => $referrer_type,
            "referrer_id"   => $referrer_id,
            "uid"           => $uid,
            "pay_type"      => $pay_type,
            "amount"        => $amount,
            "cash_gift"     => $cash_gift,
            "status"        => config("order.recharge_status")['pending_payment']['key'],
            "created_at"    => $time,
            "updated_at"    => $time
        ];

        $billRefillModel = new BillRefill();

        $res = $billRefillModel
            ->insert($billRefillParams);

        $return_data = [
            "rfid"   => $rfid,
            "amount" => $amount
        ];

        if ($res){
            return $this->com_return(true,config("params.SUCCESS"),$return_data);

        }else{
            return $this->com_return(false,config("params.FAIL"));
        }
    }


    /**
     * 获取预约时,温馨提示信息
     */
    public function getReserveWaringInfo()
    {
        //获取当前退款和不退款的设置信息
        $openCardObj = new OpenCard();

        $reserve_refund_flag = $openCardObj->getSysSettingInfo("reserve_refund_flag");

        if ($reserve_refund_flag){
            $info = $openCardObj->getSysSettingInfo("reserve_warning_no");
        }else{
            $info = $openCardObj->getSysSettingInfo("reserve_warning");
        }

        return $this->com_return(true,\config("params.SUCCESS"),$info);
    }


}