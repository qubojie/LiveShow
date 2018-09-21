<?php
/**
 * Created by PhpStorm.
 * User: qubojie
 * Date: 2018/9/21
 * Time: 下午1:32
 */
namespace app\reception\controller;

use app\wechat\model\BillPayAssist;
use think\Request;
use think\Validate;

class CountMoney extends CommonAction
{
    /**
     * 桌消费统计列表
     * @param Request $request
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function tableConsumer(Request $request)
    {
        $dateTime = $request->param("dateTime","");//日期

        $timeDate = $request->param("timeDate","");//时间

        $rule = [
            "dateTime|日期"    => "require",
        ];

        $request_res = [
            "dateTime"    => $dateTime,
        ];

        $validate = new Validate($rule);

        if (!$validate->check($request_res)){
            return $this->com_return(false,$validate->getError());
        }

        if (!empty($timeDate)){
            $timeArr = explode(":",$timeDate);

            $hours  = $timeArr[0];
            $minute = $timeArr[1];

            $hours_s  = $hours * 60 * 60;
            $minute_s = $minute * 60;

            $s_sum = $hours_s + $minute_s;
        }else{
            $s_sum = 0;
        }

        $nowDateTime = strtotime(date("Ymd")) + $s_sum;

        if ($dateTime == 1){
            //今天
            $beginTime = date("YmdHis",$nowDateTime);
            $endTime   = date("YmdHis",$nowDateTime + 24 * 60 * 60);

        }elseif ($dateTime == 2){
            //昨天
            $beginTime = date("YmdHis",$nowDateTime - 24 * 60 * 60);
            $endTime   = date("YmdHis",$nowDateTime);

        }else{
            $dateTimeArr = explode(",",$dateTime);
            $beginTime   = date("YmdHis",$dateTimeArr[0]);;
            $endTime     = date("YmdHis",$dateTimeArr[1]);;
        }

        $date_where['bpa.created_at'] = ["between time",["$beginTime","$endTime"]];


        $billPayAssistModel = new BillPayAssist();

        $list = $billPayAssistModel
            ->alias("bpa")
            ->join("user_gift_voucher ugv","ugv.gift_vou_code = bpa.gift_vou_code","LEFT")
            ->where("bpa.sale_status",config("bill_assist.bill_status")['1']['key'])
            ->where($date_where)
            ->group("bpa.table_id,bpa.table_no")
            ->field("bpa.table_id,bpa.table_no")
            ->field("sum(bpa.account_balance) account_balance,sum(bpa.account_cash_gift) account_cash_gift,sum(bpa.cash) cash")
            ->field("sum(ugv.gift_vou_amount) gift_vou_amount")
            ->select();

        $list = json_decode(json_encode($list),true);

        //储值消费
        $all_balance_money = $billPayAssistModel
            ->alias("bpa")
            ->where("bpa.sale_status",config("bill_assist.bill_status")['1']['key'])
            ->where($date_where)
            ->sum("bpa.account_balance");

        $res['all_balance_money'] = $all_balance_money;

        //现金消费
        $all_cash_money = $billPayAssistModel
            ->alias("bpa")
            ->where("bpa.sale_status",config("bill_assist.bill_status")['1']['key'])
            ->where($date_where)
            ->sum("bpa.cash");

        $res['all_cash_money'] = $all_cash_money;

        //礼金消费
        $all_gift_money = $billPayAssistModel
            ->alias("bpa")
            ->where("bpa.sale_status",config("bill_assist.bill_status")['1']['key'])
            ->where($date_where)
            ->sum("bpa.account_cash_gift");

        $res['all_gift_money'] = $all_gift_money;

        //礼券消费
        $all_voucher_money = $billPayAssistModel
            ->alias("bpa")
            ->join("user_gift_voucher ugv","ugv.gift_vou_code = bpa.gift_vou_code","LEFT")
            ->where("bpa.sale_status",config("bill_assist.bill_status")['1']['key'])
            ->where($date_where)
            ->sum("ugv.gift_vou_amount");

        $res['all_voucher_money'] = $all_voucher_money;

        $res['data'] = $list;

        return $this->com_return(true,config("params.SUCCESS"),$res);
    }


    /**
     * 结算统计
     * @param Request $request
     * @return array
     */
    public function settlementCount(Request $request)
    {
        $dateTime = $request->param("dateTime","");//截止时间

        $rule = [
            "dateTime|截止时间" => "require",
        ];

        $request_res = [
            "dateTime"    => $dateTime,
        ];

        $validate = new Validate($rule);

        if (!$validate->check($request_res)){
            return $this->com_return(false,$validate->getError());
        }

        $countObj = new CountPublic();

        /*消费类 on*/
        $consumerClass = $countObj->consumerClass($dateTime);
        $res['consumerClass'] = $consumerClass;
        /*消费类 off*/

        /*会员卡储值类 On*/
        $rechargeClass = $countObj->rechargeClass($dateTime);
        $res['rechargeClass'] = $rechargeClass;
        /*会员卡储值类 Off*/


        /*会员开卡类 On*/
        $vipCardClass = $countObj->vipCardClass($dateTime);
        $res['vipCardClass'] = $vipCardClass;
        /*会员开卡类 Off*/

        /*收款小计 On*/
        $receivablesLClass = $countObj->receivablesLClass($dateTime);
        $res['receivablesLClass'] = $receivablesLClass;
        /*收款小计 Off*/

        /*收款总金额 On*/
        $allClass = $countObj->allClass($dateTime);
        $res['allClass'] = $allClass;
        /*收款总金额 Off*/

        return $this->com_return(true,config("params.SUCCESS"),$res);
    }



}