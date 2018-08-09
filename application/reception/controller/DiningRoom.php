<?php
namespace app\reception\controller;

use app\admin\model\ManageSalesman;
use app\admin\model\MstTable;
use app\admin\model\MstTableAreaCard;
use app\admin\model\TableRevenue;
use app\admin\model\User;
use app\common\controller\UUIDUntil;
use app\wechat\controller\OpenCard;
use think\Db;
use think\Request;
use think\Response;
use think\Validate;

class DiningRoom extends CommonAction
{
    /**
     * 获取今日订台信息
     * @param Request $request
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function todayTableInfo(Request $request)
    {
        $keyword       = $request->param("keyword","");//关键字搜索
        $location_id   = $request->param("location_id","");//位置id
        $area_id       = $request->param("area_id","");//区域id;
        $appearance_id = $request->param("appearance_id","");//品相id

        $location_where = [];
        if (!empty($location_id)){
            $location_where['ta.location_id'] = ['eq',$location_id];
        }

        $area_where = [];
        if (!empty($area_id)){
            $area_where['t.area_id'] = ['eq',$area_id];
        }

        $appearance_where = [];
        if (!empty($appearance_where)){
            $appearance_where['t.appearance_id'] = ['eq',$appearance_id];
        }

        $where = [];
        if (!empty($keyword)){
            $where["t.table_no|ta.area_title|tl.location_title|tap.appearance_title|u.phone"] = ["like","%$keyword%"];
        }

        $tableModel = new MstTable();
        $tableInfo = $tableModel
            ->alias("t")
            ->join("mst_table_area ta","ta.area_id = t.area_id")//区域
            ->join("mst_table_location tl","tl.location_id = ta.location_id")//位置
            ->join("mst_table_size ts","ts.size_id = t.size_id")//人数
            ->join("mst_table_appearance tap","tap.appearance_id = t.appearance_id")//品相
            ->join("table_revenue tr","tr.table_id = t.table_id","LEFT")//预约表
            ->join("user u","u.uid = tr.uid","LEFT")
            ->where('t.is_delete',0)
            ->where($where)
            ->where($location_where)
            ->where($area_where)
            ->where($appearance_where)
            ->group("t.table_id")
            ->order('t.sort')
//            ->field("t.table_id,t.table_no,t.turnover_limit_l1,t.turnover_limit_l2,t.turnover_limit_l3,t.subscription_l1,t.subscription_l2,t.subscription_l3,t.people_max,t.table_desc")
            ->field("t.table_id,t.table_no,t.people_max,t.table_desc,t.sort,t.is_enable")
            ->field("ta.area_id,ta.area_title,ta.area_desc,ta.sid")
            ->field("tl.location_title")
            ->field("ts.size_title")
            ->field("tap.appearance_title")
            ->select();

        $tableInfo = json_decode(json_encode($tableInfo),true);

        $tableRevenueModel = new TableRevenue();
        $tableAreaCardModel = new MstTableAreaCard();

        $status_str = "0,1,2";

        for ($i = 0; $i <count($tableInfo); $i ++){

            /*桌子限制筛选 on*/
            $area_id_s = $tableInfo[$i]['area_id'];//区域id
            $is_limit = $tableAreaCardModel
                ->where("area_id",$area_id_s)
                ->count();
            if ($is_limit > 0){
                $tableInfo[$i]['is_limit'] = 1;
            }else{
                $tableInfo[$i]['is_limit'] = 0;
            }
            /*桌子限制筛选 off*/


            /*桌子状态筛选 on*/
            $table_id = $tableInfo[$i]['table_id'];//桌号id
            $tableStatusRes = $tableRevenueModel
                ->where('table_id',$table_id)
                ->where('status',"IN",$status_str)
                ->whereTime("reserve_time","today")
                ->find();
            $tableStatusRes = json_decode(json_encode($tableStatusRes),true);

            if (!empty($tableStatusRes)){

                $reserve_time = $tableStatusRes['reserve_time'];
                $status = $tableStatusRes["status"];
                if ($status == config("order.table_reserve_status")['reserve_success']['key']){
                    /*超时判断 on*/
                    if ($reserve_time < time()){
                        $tableInfo[$i]['is_overtime'] = 1;
                    }else{
                        $tableInfo[$i]['is_overtime'] = 0;
                    }
                    /*超时判断 off*/
                }else{
                    $tableInfo[$i]['is_overtime'] = 0;
                }


                $reserve_time = date("H:i",$reserve_time);

                $table_status =  $tableStatusRes['status'];

                if ($table_status == 1){
                    $tableInfo[$i]['table_status'] = 1;
                }elseif ($table_status == 2){
                    $tableInfo[$i]['table_status'] = 2;
                }else{
                    $tableInfo[$i]['table_status'] = 0;//空
                }


                $tableInfo[$i]['is_join']      = $tableStatusRes['is_join'];
                $tableInfo[$i]['reserve_time'] = $reserve_time;
            }else{
                $tableInfo[$i]['is_overtime'] = 0;
                $tableInfo[$i]['table_status'] = 0;
                $tableInfo[$i]['is_join']      = 0;
                $tableInfo[$i]['reserve_time'] = 0;
            }
            /*桌子状态筛选 off*/

        }

        return $this->com_return(true,config("params.SUCCESS"),$tableInfo);
    }

    /**
     * 查看桌位详情
     * @param Request $request
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function tableInfo(Request $request)
    {
        $table_id = $request->param("table_id","");//桌台id

        if (empty($table_id)){
            return $this->com_return(false,config("params.PARAM_NOT_EMPTY"));
        }

        $tableModel = new MstTable();

        $table_column = [
            "t.table_no",
            "t.is_enable",
            "t.table_id",
            "t.area_id",
            "t.turnover_limit_l1",
            "t.turnover_limit_l2",
            "t.turnover_limit_l3",
            "t.subscription_l1",
            "t.subscription_l2",
            "t.subscription_l3",
        ];

        $tableRevenueModel = new TableRevenue();

        $tableInfo = $tableModel
            ->alias("t")
            ->join("mst_table_area ta","ta.area_id = t.area_id")
            ->join("manage_salesman ms","ms.sid = ta.sid")
            ->where("table_id",$table_id)
            ->field("ms.sales_name as service_name,ms.phone service_phone")
            ->field($table_column)
            ->find();

        $tableInfo = json_decode(json_encode($tableInfo),true);

        $appointment = time();
        $publicObj = new PublicAction();
        /*特殊日期 匹配特殊定金 on*/
        $dateList = $publicObj->isReserveDate($appointment);

        if (!empty($dateList)){
            //是特殊日期
            $turnover_limit = $tableInfo['turnover_limit_l3'];//特殊日期预约最低消费
            $subscription   = $tableInfo['subscription_l3'];//特殊日期预约定金


        }else{
            //不是特殊日期

            //查看预约日期是否是周末日期
            $today_week = getTimeWeek($appointment);

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


        $area_id = $tableInfo['area_id'];

        $cardInfo = Db::name("mst_table_area_card")
            ->alias("tac")
            ->join("mst_card_vip cv","cv.card_id = tac.card_id")
            ->where('tac.area_id',$area_id)
            ->field("cv.card_name")
            ->select();

        $cardInfo = json_decode(json_encode($cardInfo),true);

        $tableInfo['card_vip'] = $cardInfo;

        $status_str = "0,1,2";

        $revenue_column = $tableRevenueModel->revenue_column;

        $emptyStr = "";
        /*主桌基本信息 On*/
        $mainRevenueInfo = $tableRevenueModel
            ->alias("tr")
            ->join("user u","u.uid = tr.uid","LEFT")
            ->join("user_card uc","uc.uid = tr.uid","LEFT")
            ->join("mst_user_level ul","ul.level_id = u.level_id","LEFT")
            ->join("manage_salesman ms","ms.sid = tr.ssid","LEFT")
//            ->whereNull("tr.parent_trid")
//            ->where("tr.parent_trid","")
            ->where('tr.table_id',$table_id)
            ->whereTime("tr.reserve_time","today")
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

//        dump($tableRevenueModel->getLastSql());die;

        $mainRevenueInfo = json_decode(json_encode($mainRevenueInfo),true);

        $tableInfo['mainRevenueInfo'] = $mainRevenueInfo;
        /*主桌基本信息 Off*/


        $mainTrid = $mainRevenueInfo['trid'];

        /*拼桌基本信息 On*/

        $spellingRevenueInfo = $tableRevenueModel
            ->alias("tr")
            ->join("user u","u.uid = tr.uid")
            ->join("user_card uc","uc.uid = tr.uid","LEFT")
            ->join("mst_user_level ul","ul.level_id = u.level_id")
            ->join("manage_salesman ms","ms.sid = tr.ssid","LEFT")
            ->where('tr.table_id',$table_id)
            ->where("tr.status","IN",$status_str)
            ->whereTime("tr.reserve_time","today")
            ->where("tr.parent_trid",$mainTrid)
            ->field("u.name,u.phone user_phone,u.nickname,u.level_id,u.credit_point")
            ->field("ul.level_name")
            ->field("uc.card_name,uc.card_type")
            ->field("ms.phone sales_phone")
            ->field($revenue_column)
            ->select();

        $spellingRevenueInfo = json_decode(json_encode($spellingRevenueInfo),true);

        $tableInfo['spellingRevenueInfo'] = $spellingRevenueInfo;

        /*拼桌基本信息 Off*/


        $oldInfo = $tableRevenueModel
            ->alias("tr")
            ->join("user u","u.uid = tr.uid")
            ->join("user_card uc","uc.uid = tr.uid","LEFT")
            ->join("mst_user_level ul","ul.level_id = u.level_id")
            ->where('tr.table_id',$table_id)
            ->where("tr.status","NOT IN",$status_str)
            ->whereTime("tr.reserve_time","today")
            ->field("u.name,u.phone user_phone,u.nickname,u.level_id,u.credit_point")
            ->field("ul.level_name")
            ->field("uc.card_name,uc.card_type")
            ->field($revenue_column)
            ->select();

        $oldInfo = json_decode(json_encode($oldInfo),true);

        $tableInfo['oldInfo'] = $oldInfo;

        $tableInfo = _unsetNull($tableInfo);

        return $this->com_return(true,config("params.SUCCESS"),$tableInfo);
    }

    /**
     * 开台
     * @param Request $request
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function openTable(Request $request)
    {

        $table_id    = $request->param("table_id","");//桌id
        $user_phone  = $request->param("user_phone","");//客户电话
        $user_name   = $request->param("user_name","");//客户姓名
        $sales_phone = $request->param("sales_phone","");//营销电话

        $time = time();
        $open_time = $time;

        $rule = [
            "table_id|桌台"       => "require",
            "user_phone|客户电话"  => "regex:1[3-8]{1}[0-9]{9}",
            "sales_phone|营销电话" => "regex:1[3-8]{1}[0-9]{9}",
        ];

        $request_res = [
            "table_id"    => $table_id,
            "user_phone"  => $user_phone,
            "sales_phone" => $sales_phone,
        ];

        $validate = new Validate($rule);

        if (!$validate->check($request_res)){
            return $this->com_return(false,$validate->getError());
        }

        $referrer_id   = config("salesman.salesman_type")['3']['key'];
        $referrer_type = config("salesman.salesman_type")['3']['key'];
        $referrer_name = config("salesman.salesman_type")['3']['key'];
        if (!empty($sales_phone)){
            //获取营销信息
            $manageInfo = $this->phoneGetSalesmanInfo($sales_phone);
            if (!empty($manageInfo)){
                $referrer_id   = $manageInfo["sid"];
                $referrer_type = $manageInfo["stype_key"];
                $referrer_name = $manageInfo["sales_name"];
            }
        }

        $UUID = new UUIDUntil();

        $uid = "";
        if (!empty($user_phone)){
            //根据用户电话获取用户信息
            $userInfo = $this->userPhoneGetInfo($user_phone);
            if (empty($userInfo)){
                //如果没有当前用户信息,则创建新用户
                $userModel = new User();
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
                    "referrer_type" => $referrer_type,
                    "referrer_id"   => $referrer_id,
                    "created_at"    => $time,
                    "updated_at"    => $time

                ];
                //插入新的用户信息
                $userModel->insert($user_params);
            }else{
                $uid = $userInfo['uid'];
            }
        }

        $publicObj = new PublicAction();

        if (!empty($uid)){
            //查看当前用户是否预约当前桌,并且是未开台状态
            $is_revenue = $publicObj->userTableStatus($uid,$table_id);

            if (!empty($is_revenue)){
                $status = $is_revenue['status'];

                if ($status != config("order.table_reserve_status")['reserve_success']['key']){
                    return $this->com_return(false,config("params.REVENUE")['STATUS_NO_OPEN']);
                }

                //是当前用户的预约桌台,更改当前桌台为 开台状态
                $trid = $is_revenue['trid'];
                $status = config("order.table_reserve_status")['already_open']['key'];

                $openTable = $publicObj->changeRevenueTableStatus($trid,$status);

                if ($openTable){
                    //预约用户开台成功
                    return $this->com_return(true,config("params.SUCCESS"));
                }else{
                    return $this->com_return(false,config("params.FAIL"));
                }

            }else{
                //不是当前用户的预约
                //查看当前桌,并且是可开台状态
                $status_str = "0,1,2";
                $tableRevenueModel = new TableRevenue();
                $can_open = $tableRevenueModel
                    ->where("table_id",$table_id)
                    ->where("status","IN",$status_str)
                    ->count();
                if ($can_open > 0){
                    //此时不可开台
                    return $this->com_return(false,config("params.REVENUE")['DO_NOT_OPEN']);
                }

                $insertTableRevenueReturn = $publicObj->insertTableRevenue("$table_id","$uid","$open_time","$referrer_id","$referrer_name");

                if ($insertTableRevenueReturn){
                    //非预约用户开台成功
                    return $this->com_return(true,config("params.SUCCESS"));
                }else{
                    return $this->com_return(false,config("params.FAIL"));
                }

            }

        }else{
            //未录入用户信息
            //查看当前桌,并且是可开台状态
            $status_str = "0,1,2";
            $tableRevenueModel = new TableRevenue();
            $can_open = $tableRevenueModel
                ->where("table_id",$table_id)
                ->where("status","IN",$status_str)
                ->count();
            if ($can_open > 0){
                //此时不可开台
                return $this->com_return(false,config("params.REVENUE")['DO_NOT_OPEN']);
            }

            //此时直接开台

            $insertRevenueReturn = $publicObj->insertTableRevenue("$table_id","","$open_time","","");

            if ($insertRevenueReturn){
                //未录入任何信息直接开台成功
                return $this->com_return(true,config("params.SUCCESS"));
            }else{
                return $this->com_return(false,config("params.FAIL"));
            }

        }
    }

    /**
     * 补充已开台基本信息
     * @param Request $request
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function supplementRevenueInfo(Request $request)
    {
        $trid        = $request->param("trid","");

        $user_phone  = $request->param("user_phone","");

        $user_name   = $request->param("user_name","");

        $sales_phone = $request->param("sales_phone","");

        $time = time();

        $rule = [
            "trid|订台id"         => "require",
            "user_phone|客户电话"  => "regex:1[3-8]{1}[0-9]{9}",
            "sales_phone|营销电话" => "regex:1[3-8]{1}[0-9]{9}",
        ];

        $request_res = [
            "trid"        => $trid,
            "user_phone"  => $user_phone,
            "sales_phone" => $sales_phone
        ];

        $validate = new Validate($rule);

        if (!$validate->check($request_res)){
            return $this->com_return(false,$validate->getError());
        }

        $tableRevenueModel = new TableRevenue();

        $tableRevenueInfo = $tableRevenueModel
            ->where("trid",$trid)
            ->field("status")
            ->find();
        $tableRevenueInfo = json_decode(json_encode($tableRevenueInfo),true);

        if ($tableRevenueInfo['status'] != 2 ){
            return $this->com_return(false,config("params.REVENUE")['STATUS_NO_EDIT']);
        }

       /*营销信息获取 on*/
        $referrer_id   = config("salesman.salesman_type")['3']['key'];
        $referrer_type = config("salesman.salesman_type")['3']['key'];
        $referrer_name = config("salesman.salesman_type")['3']['key'];
        if (!empty($sales_phone)){
            //获取营销信息
            $manageInfo = $this->phoneGetSalesmanInfo($sales_phone);
            if (!empty($manageInfo)){
                $referrer_id   = $manageInfo["sid"];
                $referrer_type = $manageInfo["stype_key"];
                $referrer_name = $manageInfo["sales_name"];
            }
        }
        /*营销信息获取 off*/

        /*客户信息获取 on*/
        $UUID = new UUIDUntil();
        $uid  = "";
        if (!empty($user_phone)){
            //根据用户电话获取用户信息
            $userInfo = $this->userPhoneGetInfo($user_phone);
            if (empty($userInfo)){
                //如果没有当前用户信息,则创建新用户
                $userModel = new User();
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
                    "referrer_type" => $referrer_type,
                    "referrer_id"   => $referrer_id,
                    "created_at"    => $time,
                    "updated_at"    => $time

                ];
                //插入新的用户信息
                $userModel->insert($user_params);
            }else{
                $uid = $userInfo['uid'];
            }
        }
        /*客户信息获取 off*/

        $update_params = [
            "uid"        => $uid,
            "ssid"       => $referrer_id,
            "ssname"     => $referrer_name,
            "updated_at" => $time
        ];

        $res = $this->updateTableRevenueInfo($update_params,$trid);

        if ($res){
            return $this->com_return(true,config("params.SUCCESS"));
        }else{
            return $this->com_return(true,config("params.FAIL"));
        }
    }


    /**
     * 手机号码检索
     * @param Request $request
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function phoneRetrieval(Request $request)
    {
        $type  = $request->param("type","");//类型,user为用户;sales为员工
        $phone = $request->param("phone","");//电话号码

        if ($type == "user"){
            //用户检索
            $res =  $this->userPhoneRetrieval($phone);

        }elseif($type == "sales"){
            //员工检索
            $res = $this->salesPhoneRetrieval($phone);

        }else{
            return $this->com_return(false,config("params.PARAM_NOT_EMPTY"));
        }

        //将数组中的 Null  转换为 "" 空字符串
        $res = _unsetNull($res);

        return $this->com_return(true,config("params.SUCCESS"),$res);

    }

    /**
     * 用户手机号码检索
     * @param $phone
     * @return false|mixed|\PDOStatement|string|\think\Collection
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    protected function userPhoneRetrieval($phone)
    {
        $userModel = new User();

        $where["u.phone"] = ["like","%$phone%"];

        $res = $userModel
            ->alias("u")
            ->join("user_card uc","uc.uid = u.uid","LEFT")
            ->join("mst_user_level ul","ul.level_id = u.level_id")
            ->where($where)
            ->field("u.uid,u.name,u.nickname,u.phone")
            ->field("uc.card_name,uc.card_type")
            ->field("ul.level_name,u.credit_point")
            ->select();

        $res = json_decode(json_encode($res),true);

        return $res;
    }

    /**
     * 员工手机号码检索
     * @param $phone
     * @return false|mixed|\PDOStatement|string|\think\Collection
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    protected function salesPhoneRetrieval($phone)
    {
        $salesModel = new ManageSalesman();

        $where = [];
        if (!empty($phone)){
            $where["phone"] = ["like","%$phone%"];
        }


        $res = $salesModel
            ->alias("ms")
            ->join("mst_salesman_type mst","mst.stype_id = ms.stype_id")
            ->where($where)
            ->where("mst.stype_key","IN","vip,sales,boss")
            ->field("ms.sid,ms.sales_name,ms.phone")
            ->select();

        $res = json_decode(json_encode($res),true);

        return $res;
    }
}