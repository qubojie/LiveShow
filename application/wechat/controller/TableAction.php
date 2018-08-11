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
}