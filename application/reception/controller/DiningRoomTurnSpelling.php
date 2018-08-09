<?php
/**
 * 转拼,开拼类
 * User: qubojie
 * Date: 2018/8/8
 * Time: 下午6:08
 */
namespace app\reception\controller;

use app\admin\model\MstTable;
use app\admin\model\MstTableAreaCard;
use app\admin\model\TableRevenue;
use app\admin\model\User;
use app\common\controller\UUIDUntil;
use app\wechat\controller\OpenCard;
use think\Db;
use think\Exception;
use think\Request;
use think\Validate;

class DiningRoomTurnSpelling extends CommonAction
{
    /**
     * 开拼
     * @param Request $request
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function openSpelling(Request $request)
    {
        $table_id    = $request->param("table_id","");//当前桌id

        $user_phone  = $request->param("user_phone","");//用户电话

        $user_name   = $request->param("user_name","");//用户姓名

        $sales_phone = $request->param("sales_phone","");//营销电话

        $time = time();

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

        $tableRevenueModel = new TableRevenue();

        //查看当前桌台是否是开台状态,只有开台状态的桌子才能被开拼
        $table_is_open = $tableRevenueModel
            ->where('table_id',$table_id)
            ->where("status",config("order.table_reserve_status")['already_open']['key'])
            ->where(function ($query){
                $query->where('tr.parent_trid',Null);
                $query->whereOr('tr.parent_trid','');
            })
            ->field("trid")
            ->find();
        $table_is_open = json_decode(json_encode($table_is_open),true);

        if (empty($table_is_open)){
            //未开台,不可进行开拼操作
            return $this->com_return(false,config("params.REVENUE")['NO_OPEN_SPELLING']);
        }

        $parent_trid = $table_is_open['trid'];

        /*营销信息 on*/
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
        /*营销信息 off*/

        /*用户信息 on*/
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
        /*用户信息 off*/

        //查看当前用户是否有已开台
        //查看当前桌台是否是开台状态,只有开台状态的桌子才能被开拼
        $table_is_open = $tableRevenueModel
            ->where('uid',$uid)
            ->where("status",config("order.table_reserve_status")['already_open']['key'])
            ->count();

        if ($table_is_open > 0){
            //当前用户已开台,不可进行开拼操作
            return $this->com_return(false,config("params.REVENUE")['USER_HAVE_TABLE']);
        }

        $publicObj = new PublicAction();

        $insertTableRevenueReturn = $publicObj->insertSpellingTable("$parent_trid","$table_id","$uid","$time","$referrer_id","$referrer_name");

        if ($insertTableRevenueReturn){
            //开拼成功
            return $this->com_return(true,config("params.SUCCESS"));
        }else{
            return $this->com_return(false,config("params.FAIL"));
        }
    }

    /**
     * 获取今日已开台或者空台的桌
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function alreadyOpenTable(Request $request)
    {
        $tableRevenueModel = new TableRevenue();
        $tableModel = new MstTable();

        $status = $request->param("status","");

        $res = [];

        if (empty($status)){

            $status_str = "0,1,2";

            $tableInfo = $tableModel
                ->where("is_delete",0)
                ->order('sort')
                ->field("table_id,area_id,table_no,table_desc,is_enable,turnover_limit_l1,turnover_limit_l2,turnover_limit_l3,subscription_l1,subscription_l2,subscription_l3")
                ->select();

            $tableInfo = json_decode(json_encode($tableInfo),true);

            $publicObj = new PublicAction();

            for ($i =0; $i < count($tableInfo); $i++){

                $table_id = $tableInfo[$i]['table_id'];

                $table_is_re = $tableRevenueModel
                    ->whereTime("reserve_time","today")
                    ->where('status',"IN",$status_str)
                    ->where("table_id",$table_id)
                    ->count();

                $table_is_re = json_decode(json_encode($table_is_re),true);

                if ($table_is_re > 0){
                   //移除当前桌位
                    unset($tableInfo[$i]);
                }
            }

            $res = array_values($tableInfo);

            for ($m = 0; $m <count($res); $m ++){


                /*特殊日期 匹配特殊定金 on*/
                $appointment = time();
                /*特殊日期 匹配特殊定金 on*/
                $dateList = $publicObj->isReserveDate($appointment);

                if (!empty($dateList)){
                    //是特殊日期
                    $turnover_limit = $res[$m]['turnover_limit_l3'];//特殊日期预约最低消费
                    $subscription   = $res[$m]['subscription_l3'];//特殊日期预约定金


                }else{
                    //不是特殊日期

                    //查看预约日期是否是周末日期
                    $today_week = getTimeWeek($appointment);

                    $openCardObj = new OpenCard();

                    $reserve_subscription_week = $openCardObj->getSysSettingInfo("reserve_subscription_week");

                    $is_bh = strpos("$reserve_subscription_week","$today_week");

                    if ($is_bh !== false){
                        //如果包含,则获取特殊星期的押金和低消
                        $turnover_limit = $res[$m]['turnover_limit_l2'];//周末日期预约最低消费
                        $subscription   = $res[$m]['subscription_l2'];//周末日期预约定金

                    }else{
                        //如果不包含
                        $turnover_limit = $res[$m]['turnover_limit_l1'];//平时预约最低消费
                        $subscription   = $res[$m]['subscription_l1'];//平时预约定金
                    }
                }
                $res[$m]['turnover_limit'] = $turnover_limit;
                $res[$m]['subscription']   = $subscription;
                /*特殊日期 匹配特殊定金 off*/

                //移除数组指定的key, 多个以逗号隔开
                $res[$m] = array_remove($res[$m],"turnover_limit_l1,turnover_limit_l2,turnover_limit_l3,subscription_l1,subscription_l2,subscription_l3");
            }
        }

        if ($status == '1'){
            $status = config("order.table_reserve_status")['already_open']['key'];
            $res = $tableRevenueModel
                ->alias("tr")
                ->join("user u","u.uid = tr.uid")
                ->whereTime("tr.reserve_time","today")
                ->where("tr.status",$status)
                ->where(function ($query){
                    $query->where('tr.parent_trid',Null);
                    $query->whereOr('tr.parent_trid','');
                })
                ->field("tr.trid,tr.area_id,tr.is_join,tr.table_no")
                ->field("u.name parent_name,u.phone parent_phone")
                ->select();
        }

        $res = json_decode(json_encode($res),true);

        $tableCardModel = new MstTableAreaCard();

        for ($i = 0; $i < count($res); $i++){

            $area_id = $res[$i]['area_id'];

            $cardInfo = $tableCardModel
                ->alias("tac")
                ->join("mst_card_vip cv","cv.card_id = tac.card_id")
                ->where("tac.area_id",$area_id)
                ->field("cv.card_name,cv.card_type")
                ->select();
            $cardInfo = json_decode(json_encode($cardInfo),true);

            $res[$i]["card_info"] = $cardInfo;
        }

        $res =  _unsetNull($res);

        return $this->com_return(true,config("params.SUCCESS"),$res);


    }


    /**
     * 转拼
     * @param Request $request
     * @return array
     */
    public function turnSpelling(Request $request)
    {
        $publicObj = new PublicAction();


        $now_trid = $request->param("now_trid","");//当桌位预约单id

        $to_trid  = $request->param("to_trid","");//转至桌预约单id

        $time = time();

        $rule = [
            "now_trid|当桌位预约单id"  => "require",
            "to_trid|转至桌预约单id"   => "require",
        ];

        $request_res = [
            "now_trid" => $now_trid,
            "to_trid"  => $to_trid,
        ];

        $validate = new Validate($rule);

        if (!$validate->check($request_res)){
            return $this->com_return(false,$validate->getError());
        }

        /*当前桌台的主要信息 on*/
        $nowTableInfo = $publicObj->tridGetInfo($now_trid);

        if (empty($nowTableInfo)){
            return $this->com_return(false,config("params.ABNORMAL_ACTION"));
        }

        $status = $nowTableInfo['status'];

        //如果状态不是开台状态,则不可拼台
        if ($status != config("order.table_reserve_status")['already_open']['key']){
            return $this->com_return(false,config("params.REVENUE")['NO_OPEN_SPELLING']);
        }
        /*当前桌台的主要信息 off*/


        /*转至桌台的主要信息 on*/

        $parentTableInfo = $publicObj->tridGetInfo($to_trid);

        if (empty($parentTableInfo)){
            return $this->com_return(false,config("params.ABNORMAL_ACTION"));
        }

        $status = $parentTableInfo['status'];

        //如果状态不是开台状态,则不可拼台
        if ($status != config("order.table_reserve_status")['already_open']['key']){
            return $this->com_return(false,config("params.REVENUE")['NO_OPEN_SPELLING']);
        }

        $table_id = $parentTableInfo['table_id'];
        $table_no = $parentTableInfo['table_no'];
        $area_id  = $parentTableInfo['area_id'];
        $sid      = $parentTableInfo['sid'];
        $sname    = $parentTableInfo['sname'];

        /*转至桌台的主要信息 off*/


        $tableRevenueModel = new TableRevenue();

        $spelling_num = $tableRevenueModel
            ->where("parent_trid",$to_trid)
            ->count();
        $new_table_no = $table_no." - ".($spelling_num + 1);

        //dump($new_table_no);die;

        //变更当前台位信息至转台信息
        $now_params = [
            "is_join"     => 1,
            "parent_trid" => $to_trid,
            "table_id"    => $table_id,
            "table_no"    => $new_table_no,
            "area_id"     => $area_id,
            "sid"         => $sid,
            "sname"       => $sname,
            "updated_at"  => $time
        ];

        $parent_params = [
            "is_join"    => 1,
            "updated_at" => $time
        ];

        Db::startTrans();
        try{
            $updateNowTridReturn = $tableRevenueModel
                ->where("trid",$now_trid)
                ->update($now_params);

            $updateParentTridReturn = $tableRevenueModel
                ->where("trid",$to_trid)
                ->update($parent_params);

            if ($updateNowTridReturn !== false && $updateParentTridReturn !== false){
                Db::commit();
                return $this->com_return(true,config("params.SUCCESS"));
            }else{
                return $this->com_return(false,config("params.FAIL"));
            }

        }catch (Exception $e){
            Db::rollback();
            $this->com_return(false,$e->getMessage());
        }
    }

    /**
     * 转台
     * @param Request $request
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function turnTable(Request $request)
    {
        $now_trid    = $request->param("now_trid","");//当前预约台位订单id

        $to_table_id = $request->param("to_table_id","");//转至空闲台位id

        $time = time();

        $rule = [
            "now_trid|当前预约台位订单id" => "require",
            "to_table_id|转至空闲台位id" => "require",
        ];

        $request_res = [
            "now_trid"    => $now_trid,
            "to_table_id" => $to_table_id,
        ];

        $validate = new Validate($rule);

        if (!$validate->check($request_res)){
            return $this->com_return(false,$validate->getError());
        }

        $tableRevenueModel = new TableRevenue();

        //查看当前桌台是否是开台状态,只有开台状态的桌子才能转台
        $table_is_open = $tableRevenueModel
            ->where('trid',$now_trid)
            ->where("status",config("order.table_reserve_status")['already_open']['key'])
            ->count();

        if ($table_is_open <= 0){
            return $this->com_return(false,config("params.REVENUE")['NOT_OPEN_NO_TURN']);
        }

        $status = "0,1,2";

        //插卡被转台位是否是空台
        $table_is_ldle = $tableRevenueModel
            ->where("table_id",$to_table_id)
            ->where("status","IN",$status)
            ->whereTime("reserve_time","today")
            ->count();

        if ($table_is_ldle > 0){
            return $this->com_return(false,config("params.REVENUE")['TABLE_NOT_LDLE']);
        }

        $tableModel = new MstTable();

        $toTableInfo = $tableModel
            ->alias("t")
            ->join("mst_table_area mta","mta.area_id = t.area_id")
            ->join("manage_salesman ms","ms.sid = mta.sid")
            ->where("t.table_id",$to_table_id)
            ->field("t.table_no,t.area_id")
            ->field("ms.sid,ms.sales_name")
            ->find();
        $toTableInfo = json_decode(json_encode($toTableInfo),true);

        if (empty($toTableInfo)){
            $this->com_return(false,config("params.ABNORMAL_ACTION"));
        }

        $to_table_no = $toTableInfo['table_no'];
        $to_area_id  = $toTableInfo['area_id'];
        $to_sid      = $toTableInfo['sid'];
        $to_sname    = $toTableInfo['sales_name'];

        //获取转至台位的信息
        $to_params = [
            "table_id"   => $to_table_id,
            "table_no"   => $to_table_no,
            "area_id"    => $to_area_id,
            "sid"        => $to_sid,
            "sname"      => $to_sname,
            "updated_at" => $time
        ];

        $res = $tableRevenueModel
            ->where("trid",$now_trid)
            ->update($to_params);

        if ($res !== false){
            return $this->com_return(true,config("params.SUCCESS"));
        }else{
            return $this->com_return(false,config("params.FAIL"));
        }
    }
}