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
        $tableModel = new MstTable();
        $tableImageModel = new MstTableImage();

        /*$where_card = [];
        if (!empty($user_card_id)){
           $where_card['tac.card_id'] = ["eq",$user_card_id];
        }*/

        $size_where = [];
        if (!empty($size_id)){
            $size_where['t.size_id'] = $size_id;
        }

        $location_where = [];
        if (!empty($location_id)){
            $location_where['ta.location_id'] = $location_id;
        }

        $res = $tableModel
            ->alias("t")
            ->join("mst_table_area ta","ta.area_id = t.area_id")//区域
            ->join("mst_table_area_card tac","tac.area_id = ta.area_id","LEFT")//卡
            ->join("mst_table_location tl","tl.location_id = ta.location_id")//位置
            ->join("mst_table_size ts","ts.size_id = t.size_id")//人数
            ->join("mst_table_appearance tap","tap.appearance_id = t.appearance_id")//品相
            ->where('t.is_enable',1)
            ->where('t.is_delete',0)
            ->where($size_where)
            ->where($location_where)
//            ->where($where_card)
            ->group("t.table_id")
            ->order('t.sort')
            ->field("t.table_id,t.table_no,t.turnover_limit_l1,t.turnover_limit_l2,t.turnover_limit_l3,t.subscription_l1,t.subscription_l2,t.subscription_l3,t.people_max,t.table_desc")
            ->field("ta.area_id,ta.area_title,ta.area_desc,ta.sid")
            ->field("tl.location_title")
            ->field("ts.size_title")
            ->field("tap.appearance_title")
            ->paginate($pagesize,false,$config);

        $res_data = json_decode(json_encode($res),true);
        $res = $res_data["data"];

        $pending_payment = config("order.table_reserve_status")['pending_payment']['key'];//待付定金或结算
        $reserve_success = config("order.table_reserve_status")['reserve_success']['key'];//预定成功
        $already_open    = config("order.table_reserve_status")['already_open']['key'];//已开台
        $clear_table     = config("order.table_reserve_status")['clear_table']['key'];//已清台
        $cancel          = config("order.table_reserve_status")['cancel']['key'];//取消预约

        $can_not_reserve = $pending_payment.",".$reserve_success.",".$already_open;

        $where_status['status'] = array('IN',"$can_not_reserve");//查询字段的值在此范围之内的做显示

        $tableRevenueModel = new TableRevenue();

        for ($n = 0; $n <count($res); $n++){

            $area_id = $res[$n]['area_id'];

            if (empty($user_card_id)){
                $tableAreaArr = Db::name('mst_table_area_card')
                    ->where('area_id',$area_id)
                    ->select();

                if (!empty($tableAreaArr)){
                    unset($res[$n]);
                }

            }else{
                //如果用户已办卡,移除数组中
            }


        }

        $res = array_values($res);//重排索引

        for ($i = 0; $i < count($res); $i++){
            $table_id = $res[$i]['table_id'];
            //如果有设置,则取设置的强制定金,否则,就是桌子的定金
            /*特殊日期 匹配特殊定金 on*/
            $dateList = $this->isReserveDate($appointment);

            if (!empty($dateList)){
                //是特殊日期
                $turnover_limit = $res[$i]['turnover_limit_l3'];//特殊日期预约最低消费
                $subscription   = $res[$i]['subscription_l3'];//特殊日期预约定金


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

//            dump($image_res);die;

            for ($m = 0; $m < count($image_res); $m++){
                $res[$i]['image_group'][] = $image_res[$m]['image'];
            }

        }

        foreach ($res as $k => $v){
            $table_id = $v['table_id'];

            $table_reserve_exist = $tableRevenueModel
                ->where('table_id',$table_id)
                ->whereTime("reserve_time","between",["$appointment",$appointment + 24 * 60 * 60])
                ->where($where_status)
                ->count();
            if ($table_reserve_exist){
                unset($res[$k]);
            }
        }

        $res = array_values($res);

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

        if (!empty($dateList)){
            $dateList = json_decode(json_encode($dateList),true);
        }else{
            $dateList = [];
        }
        return $dateList;
    }


    /**
     * 预约确认公共部分
     * @param $sales_phone
     * @param $table_id
     * @param $date
     * @param $time
     * @param $subscription
     * @param $turnover_limit
     * @param $reserve_way
     * @param $uid
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function confirmReservationPublic($sales_phone,$table_id,$date,$time,$subscription,$turnover_limit,$reserve_way,$uid)
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

                //待付定金或结算状态 0
                $status            = Config::get("order.table_reserve_status")['pending_payment']['key'];
                $is_subscription   = 1;
                $subscription_type = Config::get("order.subscription_type")['subscription']['key'];

                //创建缴押金订单,返回相应数据
                $billSubscriptionReturn = $this->billSubscriptionCreate("$suid","$trid","$uid","$subscription");
            }

            //去创建预约吧台订单信息
            $createRevenueReturn = $this->createRevenueOrder("$trid","$uid","$ssid","$ssname","$table_id","$status","$turnover_limit","$reserve_way","$reserve_time","$is_subscription","$subscription_type","$subscription");


            if ($billSubscriptionReturn && $createRevenueReturn){
                Db::commit();
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
    protected function tableStatusCan($table_id,$date)
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
     * @param string $pay_type
     * @return bool
     */
    protected function billSubscriptionCreate($suid,$trid,$uid,$subscription,$pay_type = "wxpay")
    {
        $billSubscriptionModel = new BillSubscription();

        $time = time();

        $params = [
            'suid'          => $suid,
            'trid'          => $trid,
            'uid'           => $uid,
            'status'        => config("order.reservation_subscription_status")['pending_payment']['key'],
            'subscription'  => $subscription,
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
     * @param $subscription
     * @return array|bool
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    protected function createRevenueOrder($trid,$uid,$ssid,$ssname,$table_id,$status,$turnover_limit,$reserve_way,$reserve_time,$is_subscription,$subscription_type,$subscription)
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
    protected function tableIdGetInfo($table_id)
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
        $tableLocationModel = new MstTableLocation();
        //获取位置选项
        $table_location = $tableLocationModel
            ->where("is_delete",0)
            ->order("sort")
            ->field('location_id,location_title,location_desc')
            ->select();

        $table_location = json_decode(json_encode($table_location),true);

        $tableSizeModel = new MstTableSize();
        //获取容量选项
        $table_size = $tableSizeModel
            ->where('is_delete',0)
            ->order("sort")
            ->field('size_id,size_title,size_desc')
            ->select();

        $table_size = json_decode(json_encode($table_size),true);

        //获取日期选项
        $openCardObj = new OpenCard();

        $reserve_before_day = $openCardObj->getSysSettingInfo("reserve_before_day");

        $now_time = time();

        //计算出指定日期的天数
        $today = strtotime(date("Ymd",$now_time));

        $date_s = 24 * 60 * 60;

        $date_select = [];

        for ($i = 0; $i < $reserve_before_day; $i++){
            $date_time = $today + $date_s * $i;

            $date_select[$i]["date"] = $date_time;
        }

        //获取可预约时间选项
        $reserve_time_frame = $openCardObj->getSysSettingInfo("reserve_time_frame");

        $reserve_time_frame_arr = explode("|",$reserve_time_frame);

        $time_arr = $this->timeToPart($reserve_time_frame_arr[0],$reserve_time_frame_arr[1]);

        $res['table_location'] = $table_location;
        $res['table_size'] = $table_size;
        $res['date_select'] = $date_select;
        $res['time_select'] = $time_arr;

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