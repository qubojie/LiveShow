<?php
/**
 * Created by PhpStorm.
 * User: qubojie
 * Date: 2018/8/8
 * Time: 上午11:29
 */
namespace app\reception\controller;

use app\admin\model\MstTable;
use app\admin\model\MstTableReserveDate;
use app\admin\model\TableRevenue;
use app\admin\model\User;
use app\common\controller\UUIDUntil;
use think\Controller;
use think\Db;
use think\Exception;
use think\Log;

class PublicAction extends Controller
{
    //查看当前用户是否预约当前桌,并且是未开台状态
    public function userTableStatus($uid,$table_id)
    {
        $tableRevenueModel = new TableRevenue();
        $is_revenue = $tableRevenueModel
            ->where("uid",$uid)
            ->where("table_id",$table_id)
            ->where("status",config("order.table_reserve_status")['reserve_success']['key'])
            ->field("trid,status,subscription_type,subscription")
            ->find();

        $is_revenue = json_decode(json_encode($is_revenue),true);

        return $is_revenue;
    }

    //变更桌台状态
    public function changeRevenueTableStatus($trid,$status)
    {
        $tableRevenueModel = new TableRevenue();

        $params = [
            "status" => $status,
            "updated_at" => time()
        ];

        $is_ok = $tableRevenueModel
            ->where("trid",$trid)
            ->update($params);
        if ($is_ok !== false){
            return true;
        }else{
            return false;
        }
    }

    //插入新的开台信息
    public function insertTableRevenue($table_id,$uid,$reserve_time,$ssid = "",$ssname = "")
    {

        $UUID = new UUIDUntil();

        $trid = $UUID->generateReadableUUID("T");

        $time = time();

        $tableInfo = $this->tableIdGetInfo($table_id);

        $table_no = $tableInfo['table_no'];
        $area_id  = $tableInfo['area_id'];
        $sid      = $tableInfo['sid'];
        $sname    = $tableInfo['sales_name'];

        $tableRevenueModel = new TableRevenue();
        //当前可开台,直接插入开台预约信息
        $tableRevenueParams = [
            "trid"              => $trid,
            "uid"               => $uid,
            "table_id"          => $table_id,
            "table_no"          => $table_no,
            "area_id"           => $area_id,
            "status"            => config("order.table_reserve_status")['already_open']['key'],
            "reserve_way"       => config("order.reserve_way")['manage']['key'],
            "reserve_time"      => $reserve_time,
            "ssid"              => $ssid,
            "ssname"            => $ssname,
            "sid"               => $sid,
            "sname"             => $sname,
            "subscription_type" => config("order.subscription_type")['null_subscription']['key'],
            "created_at"        => $time,
            "updated_at"        => $time
        ];


        $res = $this->RevenueOrderC($tableRevenueParams);
        return $res;
    }

    //插入新的开拼信息
    public function insertSpellingTable($parent_trid,$table_id,$uid,$reserve_time,$ssid = "",$ssname = "")
    {
        $UUID = new UUIDUntil();

        $new_trid = $UUID->generateReadableUUID("T");

        $time = time();

        $tableInfo = $this->tableIdGetInfo($table_id);

        $table_no = $tableInfo['table_no'];
        $area_id  = $tableInfo['area_id'];
        $sid      = $tableInfo['sid'];
        $sname    = $tableInfo['sales_name'];

        $tableRevenueModel = new TableRevenue();


        //查询当前桌台是否有拼台信息
        $spelling_num = $tableRevenueModel
            ->where("parent_trid",$parent_trid)
            ->count();

        $table_no = $table_no." - ".($spelling_num + 1);

        //当前可开台,直接插入开台预约信息
        $tableRevenueParams = [
            "trid"              => $new_trid,
            "uid"               => $uid,
            "is_join"           => 1,
            "parent_trid"       => $parent_trid,
            "table_id"          => $table_id,
            "table_no"          => $table_no,
            "area_id"           => $area_id,
            "status"            => config("order.table_reserve_status")['already_open']['key'],
            "reserve_way"       => config("order.reserve_way")['manage']['key'],
            "reserve_time"      => $reserve_time,
            "ssid"              => $ssid,
            "ssname"            => $ssname,
            "sid"               => $sid,
            "sname"             => $sname,
            "subscription_type" => config("order.subscription_type")['null_subscription']['key'],
            "created_at"        => $time,
            "updated_at"        => $time
        ];


        Db::startTrans();
        try{
            $res = $this->RevenueOrderC($tableRevenueParams);

            //更新旧的桌台拼桌状态
            $oldParams = [
                "is_join"     =>  1,
                "updated_at"  => $time
            ];

            $is_ok = $tableRevenueModel
                ->where("trid",$parent_trid)
                ->update($oldParams);

            if ($is_ok && $res){
                Db::commit();
                return true;
            }else{
                Log::info("开拼报错 ----- 插入或者更新失败");
                return false;
            }
        }catch (Exception $e){
            Db::rollback();
            Log::info("开拼报错 ----- ".$e->getMessage());
            return false;
        }
    }

    //根据桌id获取桌信息
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
            ->where('t.is_delete',0)
            ->field($column)
            ->field('ta.sid')
            ->field('s.sales_name')
            ->find();
        $tableInfo = json_decode(json_encode($tableInfo),true);

        return $tableInfo;
    }

    //更新或插入预约订单操作
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
     * 判断当前是否在特殊日期内,如果是,则返回低消和定金
     * @param $appointment
     * @return array|false|mixed|\PDOStatement|string|\think\Model
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function isReserveDate($appointment)
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

    //根据预约桌台trid获取该预约信息
    public function tridGetInfo($trid)
    {
        $tableRevenue = new TableRevenue();
        $userModel = new User();

        $res = $tableRevenue
            ->where("trid",$trid)
//            ->field("trid,is_join,parent_trid,table_id,table_no,sid,sname")
            ->find();

        $res = json_decode(json_encode($res),true);

        return $res;
    }
}