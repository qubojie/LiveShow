<?php
/**
 * Created by PhpStorm.
 * User: qubojie
 * Date: 2018/8/11
 * Time: 下午6:16
 */
namespace app\wechat\controller;

use app\admin\model\TableRevenue;
use think\Db;
use think\Request;

class TableAction extends HomeAction
{
    /**
     * 管理人员开台
     * @param Request $request
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function openTable(Request $request)
    {
        $trid = $request->param("trid","");

        $tableRevenueModel = new TableRevenue();

        $column = $tableRevenueModel->column;

        $info = $tableRevenueModel
            ->where("trid",$trid)
            ->field($column)
            ->find();

        $info = json_decode(json_encode($info),true);

        $status = $info["status"];

        if ($status == config("order.table_reserve_status")['cancel']['key']){
            return $this->com_return(false,config("params.OPEN_TABLE_STATUS")['CANCELED']);
        }

        if ($status == config("order.table_reserve_status")['pending_payment']['key']){
            return $this->com_return(false,config("params.OPEN_TABLE_STATUS")['UNPAY']);
        }

        if ($status == config("order.table_reserve_status")['already_open']['key']){
            return $this->com_return(false,config("params.OPEN_TABLE_STATUS")['ALREADYOPEN']);
        }

        if ($status == config("order.table_reserve_status")['clear_table']['key']){
            return $this->com_return(false,config("params.OPEN_TABLE_STATUS")['ALREADYOPEN']);
        }




    }


    /**
     * 清台
     * @param Request $request
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function cleanTable(Request $request)
    {
        $trid = $request->param("trid","");//开台单据id

        if (empty($trid)){
            return $this->com_return(false,config("params.ABNORMAL_ACTION"));
        }

        $tableInfo = $this->lookUserInfoComplete($trid);

        $uid = $tableInfo["uid"];

        if (empty($uid)){
            return $this->com_return(false,config("params.REVENUE")['CLEAN_BEFORE_USER']);
        }

        $tableRevenueModel = new TableRevenue();

        $updateParams = [
            "status"     => config("order.table_reserve_status")['clear_table']['key'],
            "updated_at" => time()
        ];

        $is_ok = $tableRevenueModel
            ->where("trid",$trid)
            ->whereOr("parent_trid",$trid)
            ->update($updateParams);


        if ($is_ok !== false){

            /*登陆前台人员信息 on*/
            $token      = $request->header("Token",'');
            $manageInfo = $this->tokenGetManageInfo($token);

            $stype_name = $manageInfo["stype_name"];
            $sales_name = $manageInfo["sales_name"];

            $action_user = $stype_name . " ". $sales_name;
            /*登陆前台人员信息 off*/
            $desc        = $action_user. " 清台";
            $type        = config("order.table_action_type")['clean_table']['key'];

            $tableInfo = Db::name("table_revenue")
                ->where("trid",$trid)
                ->field("table_id,table_no")
                ->find();

            $tableInfo = json_decode(json_encode($tableInfo),true);

            $table_id = $tableInfo['table_id'];
            $table_no = $tableInfo['table_no'];

            insertTableActionLog(microtime(true) * 10000,"$type","$table_id","$table_no","$action_user","$desc","","");

            return $this->com_return(true,config("params.SUCCESS"));
        }else{
            return $this->com_return(false,config("params.FAIL"));
        }
    }

    /**
     * 查看指定trid的预约订台信息
     * @param $trid
     * @return array|false|mixed|\PDOStatement|string|\think\Model
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    protected function lookUserInfoComplete($trid)
    {

        $tableRevenueModel = new TableRevenue();

        $column = $tableRevenueModel->column;

        foreach ($column as $key => $val){
            $column[$key] = "tr.".$val;
        }

        $info = $tableRevenueModel
            ->alias("tr")
            ->join("user u","u.uid = tr.uid","LEFT")
            ->where("tr.trid",$trid)
            ->field($column)
            ->find();

        $info = json_decode(json_encode($info),true);

        return $info;

    }
}