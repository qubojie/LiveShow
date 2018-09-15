<?php
/**
 * Created by PhpStorm.
 * User: qubojie
 * Date: 2018/8/30
 * Time: 下午7:10
 */
namespace app\admin\controller;

use app\wechat\controller\WechatPay;
use app\wechat\model\BillPay;
use think\Controller;
use think\Db;
use think\Exception;
use think\Request;

class PointListOrder extends CommandAction
{
    /**
     * 点单列表
     * @param Request $request
     * @return array
     * @throws \think\exception\DbException
     */
    public function index(Request $request)
    {
        $pagesize    = $request->param("pagesize",config('PAGESIZE'));//显示个数,不传时为10

        if (empty($pagesize)) $pagesize = config('PAGESIZE');

        $nowPage     = $request->param("nowPage","1");

        $keyword     = $request->param("keyword","");

        $type        = $request->param("type","");//类型 退菜 OR 增单

        $sale_status = $request->param("sale_status","");

        $where = [];
        if (!empty($keyword)){
            $where["bp.pid|bp.sname|tr.table_no|u.name|u.phone"] = ["like","%$keyword%"];
        }

        $sale_status_where = [];
        if ($sale_status != ""){
            $sale_status_where['bp.sale_status'] = ["eq",$sale_status];
        }

        $billPayModel = new BillPay();

        $pageConfig = [
            "page" => $nowPage,
        ];

        $retire_dish  = config("order.bill_pay_type")['retire_dish']['key'];//退菜
        $retire_order = config("order.bill_pay_type")['retire_order']['key'];//退单
        $give         = config("order.bill_pay_type")['give']['key'];//赠送

        $type_str = "$retire_dish,$retire_order,$give";

        $type_where = [];
        if (!empty($type)){
            $type_where["bp.type"] = ["eq",$retire_dish];
        }else{
            $type_where["bp.type"] = ["IN",$type_str];
        }

        $list_column = $billPayModel->list_column;

        foreach ($list_column as $key => $val){
            $list_column[$key] = "bp.".$val;
        }

        $list = $billPayModel
            ->alias("bp")
            ->join("user u","u.uid = bp.uid","LEFT")
            ->join("table_revenue tr","tr.trid = bp.trid")
            ->where($where)
            ->where($type_where)
            ->where($sale_status_where)
            ->field("u.name,u.phone user_phone")
            ->field($list_column)
            ->field("tr.table_id,tr.table_no,tr.open_time,tr.turnover")
            ->order("bp.created_at DESC")
            ->paginate($pagesize,false,$pageConfig);

        return $this->com_return(true,config("params.SUCCESS"),$list);
    }

    /**
     * 审核
     * @param Request $request
     * @return array
     * @throws \think\exception\DbException
     */
    public function examineOrder(Request $request)
    {
        $type            = $request->param("type","");//单据类型 0 消费    1换单   2退菜   3退单   4赠送 5礼金单

        $pid             = $request->param("pid","");//单据id

        $agree_or_reject = $request->param("agree_or_reject","");//同意或者拒绝 1 同意; 空 为 拒绝

        $check_reason    = $request->param("check_reason","");//审核原因

        $token = $request->header("Authorization");

        $mangeInfo = $this->getLoginAdminId($token);

        if ($type == config("order.bill_pay_type")['give']['key']){
            //赠送单据审核
            return $this->giveExamine($pid,$agree_or_reject,$check_reason,$mangeInfo);

        }elseif ($type == config("order.bill_pay_type")['']['key']){
            //退菜单据审核
            //TODO 退菜审核待完善

        }elseif ($type == config("order.bill_pay_type")['']['key']){
            //退单单据审核

        }else{
            return $this->com_return(false,config("params.PARAM_NOT_EMPTY"));
        }
    }


    /**
     * 赠单审核操作
     * @param $pid
     * @param $agree_or_reject
     * @param $check_reason
     * @param $mangeInfo
     * @return array
     */
    public function giveExamine($pid,$agree_or_reject,$check_reason,$mangeInfo)
    {
        if ($agree_or_reject == 1){
            //同意
            $sale_status = config("order.bill_pay_sale_status")['completed']['key'];//审核完成已落单

        }else{
            //拒绝
            $sale_status = config("order.bill_pay_sale_status")['audit_failed']['key'];//审核未通过
        }

        $params = [
            "sale_status"  => $sale_status,
            "finish_time"  => time(),
            "check_user"   => $mangeInfo['user_name'],
            "check_time"   => time(),
            "check_reason" => $check_reason,
            "updated_at"   => time()
        ];

        $billPayModel = new BillPay();

        Db::startTrans();
        try{

            $updateIsOk = $billPayModel
                ->where("pid",$pid)
                ->update($params);

            if ($updateIsOk == false){
                //更新失败
                return $this->com_return(false,config("params.FAIL"));
            }

            //更新成功
            if ($agree_or_reject == 1){
                //同意时,调起打印
                $is_print = $this->openTableToPrintYly($pid);

                $dateTimeFile = APP_PATH."index/PrintOrderYly/".date("Ym")."/";

                if (!is_dir($dateTimeFile)){
                    $res = mkdir($dateTimeFile,0777,true);
                }

                //打印结果日志
                error_log(date('Y-m-d H:i:s').var_export($is_print,true),3,$dateTimeFile.date("d").".log");
            }

            Db::commit();
            return $this->com_return(true,config("params.SUCCESS"));

        }catch (Exception $e){
            Db::rollback();
            return $this->com_return(false,$e->getMessage());
        }
    }
}