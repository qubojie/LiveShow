<?php
/**
 * Created by PhpStorm.
 * User: qubojie
 * Date: 2018/9/21
 * Time: 下午1:32
 */
namespace app\reception\controller;

use app\common\controller\UUIDUntil;
use app\reception\model\BillSettlement;
use app\wechat\model\BillPayAssist;
use think\Db;
use think\Exception;
use think\Request;
use think\Validate;

class CountMoney extends CommonAction
{
    /**
     * 桌消费统计列表
     * @param Request $request
     * @return array
     * @throws \think\exception\DbException
     */
    public function tableConsumer(Request $request)
    {
        $dateTime       = $request->param("dateTime","");//日期
        $pagesize       = $request->param("pagesize","");
        $nowPage        = $request->param("nowPage","1");

        if (empty($pagesize)){
            $pagesize = config("PAGESIZE");
        }

        if (empty($nowPage)){
            $nowPage = 1;
        }

        $config = [
            "page" => $nowPage,
        ];
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

        $nowTime = time();
        $sys_account_day_time = getSysSetting("sys_account_day_time");//获取系统设置自然日
        $now_h = date("H",$nowTime);

        if ($now_h >= $sys_account_day_time){
            //大于,新的一天
            $nowDateTime = strtotime(date("Ymd",$nowTime));
        }else{
            //小于,还是昨天
            $nowDateTime = strtotime(date("Ymd",$nowTime - 24 * 60 * 60));
        }

        $six_s       = 60 * 60 * $sys_account_day_time;
        $nowDateTime = $nowDateTime + $six_s;

        if ($dateTime == 1){
            //今天
            $beginTime = date("YmdHis",$nowDateTime);
            $endTime   = date("YmdHis",$nowDateTime + 24 * 60 * 60 - 1);

        }elseif ($dateTime == 2){
            //昨天
            $beginTime = date("YmdHis",$nowDateTime - 24 * 60 * 60);
            $endTime   = date("YmdHis",$nowDateTime - 1);

        }else{
            $dateTimeArr = explode(",",$dateTime);
            $beginTime   = date("YmdHis",$dateTimeArr[0] + $six_s);;
            $endTime     = date("YmdHis",$dateTimeArr[1] + $six_s);;
        }

        $date_where['bpa.created_at'] = ["between time",["$beginTime","$endTime"]];

        $billPayAssistModel = new BillPayAssist();

        $one = config("bill_assist.bill_status")['1']['key'];
        $seven = config("bill_assist.bill_status")['7']['key'];
        $sale_status_str = "$one,$seven";

        $list = $billPayAssistModel
            ->alias("bpa")
            ->join("user_gift_voucher ugv","ugv.gift_vou_code = bpa.gift_vou_code","LEFT")
            ->where("bpa.sale_status","IN",$sale_status_str)
            ->where($date_where)
            ->group("bpa.table_id,bpa.table_no")
            ->field("bpa.table_id,bpa.table_no")
            ->field("sum(bpa.account_balance) account_balance,sum(bpa.account_cash_gift) account_cash_gift,sum(bpa.cash) cash")
            ->field("sum(ugv.gift_vou_amount) gift_vou_amount")
            ->paginate($pagesize,false,$config);

        $list = json_decode(json_encode($list),true);

        //储值消费
        $all_balance_money = $billPayAssistModel
            ->alias("bpa")
            ->where("bpa.sale_status","IN",$sale_status_str)
            ->where($date_where)
            ->sum("bpa.account_balance");

        $list['all_balance_money'] = $all_balance_money;

        //现金消费
        $all_cash_money = $billPayAssistModel
            ->alias("bpa")
            ->where("bpa.sale_status","IN",$sale_status_str)
            ->where($date_where)
            ->sum("bpa.cash");

        $list['all_cash_money'] = $all_cash_money;

        //礼金消费
        $all_gift_money = $billPayAssistModel
            ->alias("bpa")
            ->where("bpa.sale_status","IN",$sale_status_str)
            ->where($date_where)
            ->sum("bpa.account_cash_gift");

        $list['all_gift_money'] = $all_gift_money;

        //礼券消费
        $all_voucher_money = $billPayAssistModel
            ->alias("bpa")
            ->join("user_gift_voucher ugv","ugv.gift_vou_code = bpa.gift_vou_code","LEFT")
            ->where("bpa.sale_status","IN",$sale_status_str)
            ->where($date_where)
            ->sum("ugv.gift_vou_amount");

        $list['all_voucher_money'] = $all_voucher_money;

        return $this->com_return(true,config("params.SUCCESS"),$list);
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
        return $this->settlementCountPublic($dateTime);
    }


    /**
     * 结算统计公共
     * @param $dateTime
     * @return array
     */
    public function settlementCountPublic($dateTime)
    {
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

    /**
     * 结算操作
     * @param Request $request
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function settlementAction(Request $request)
    {
        $ettlement_at       = $request->param("ettlement_at","");//结算时间
        $account_balance    = $request->param("account_balance","");//储值收款
        $account_cash_gift  = $request->param("account_cash_gift","");//礼金收款
        $cash               = $request->param("cash","");//现金收款
        $bank               = $request->param("bank","");//银行收款
        $wx_ali             = $request->param("wx_ali","");//微信+支付宝收款
        $wx_online          = $request->param("wx_online","");//微信线上收款
        $check_reason       = $request->param("check_reason","");//审核原因

        $rule = [
            "ettlement_at|结算时间"      => "require",
            "account_balance|储值收款"   => "require",
            "account_cash_gift|礼金收款" => "require",
            "cash|现金收款"              => "require",
            "bank|银行收款"              => "require",
            "wx_ali|微信+支付宝收款"      => "require",
            "wx_online|微信线上收款"      => "require",
        ];

        $request_res = [
            "ettlement_at"      => $ettlement_at,
            "account_balance"   => $account_balance,
            "account_cash_gift" => $account_cash_gift,
            "cash"              => $cash,
            "bank"              => $bank,
            "wx_ali"            => $wx_ali,
            "wx_online"         => $wx_online,
        ];

        $validate = new Validate($rule);

        if (!$validate->check($request_res)){
            return $this->com_return(false,$validate->getError());
        }

        /*检测是否有订单未处理 On*/
        $is_can_settlement = $this->checkBillPayAssistCanSettlement($ettlement_at);
        if ($is_can_settlement !== true) {
            return $is_can_settlement;
        }
        /*检测是否有订单未处理 Off*/

        $uuid = new UUIDUntil();
        $settlement_id = $uuid->generateReadableUUID("J");

        $token = $request->header("Token");

        $manageInfo = $this->receptionTokenGetManageInfo($token);
        $check_user = $manageInfo['sales_name'];

        $params = [
            "settlement_id"     => $settlement_id,
            "ettlement_at"      => $ettlement_at,
            "account_balance"   => $account_balance,
            "account_cash_gift" => $account_cash_gift,
            "cash"              => $cash,
            "bank"              => $bank,
            "wx_ali"            => $wx_ali,
            "wx_online"         => $wx_online,
            "check_user"        => $check_user,
            "check_time"        => time(),
            "check_reason"      => $check_reason,
            "created_at"        => time(),
            "updated_at"        => time()
        ];
        Db::startTrans();
        try{
            /*创建结算单据表 On*/
            $billSettlementModel = new BillSettlement();

            $insertBillSettlementReturn = $billSettlementModel
                ->insert($params);

            if ($insertBillSettlementReturn == false){
                return $this->com_return(false,config("params.ABNORMAL_ACTION"));
            }
            /*创建结算单据表 Off*/

            $countPublicObj = new CountPublic();

            /*消费结算返还礼金,佣金,积分等操作 On*/
            $returnMoneyR= $countPublicObj->returnMoneyR($ettlement_at,$check_user);
            /*消费结算返还礼金,佣金,积分等操作 Off*/
            if (!isset($returnMoneyR["result"]) || !$returnMoneyR["result"]){
                return $returnMoneyR;
            }

            /*消费结算单号回填 On*/
            $balanceMonetR = $countPublicObj->balanceMoneyR($ettlement_at,$settlement_id,$check_user);
            if (!$balanceMonetR){
                return $this->com_return(false,config("params.ABNORMAL_ACTION")." - 002");
            }
            /*消费结算单号回填 Off*/

            /*储值结算单号回填 On*/
            $rechargeR = $countPublicObj->rechargeR($ettlement_at,$settlement_id,$check_user);
            if (!$rechargeR){
                return $this->com_return(false,config("params.ABNORMAL_ACTION")." - 003");
            }
            /*储值结算单号回填 Off*/

            /*会员开卡结算单号回填 On*/
            $vipCardR = $countPublicObj->vipCardR($ettlement_at,$settlement_id,$check_user);
            if (!$vipCardR){
                return $this->com_return(false,config("params.ABNORMAL_ACTION")." - 003");
            }
            /*会员开卡结算单号回填 Off*/

            Db::commit();
            return $this->com_return(true,config("params.SUCCESS"));

        }catch (Exception $e){
            Db::rollback();
            return $this->com_return(false,$e->getMessage());
        }
    }

    /**
     * 检测结算时是否存在未处理订单
     * @param $ettlement_at
     * @return array|bool
     */
    protected function checkBillPayAssistCanSettlement($ettlement_at)
    {
        $billPayAssistModel = new BillPayAssist();

        $is_can = $billPayAssistModel
            ->alias("bpa")
            ->where("bpa.sale_status",config("bill_assist.bill_status")['0']['key'])
            ->where("bpa.created_at","elt",$ettlement_at)
            ->count();
        if ($is_can > 0){
            return $this->com_return(false,config("params.ORDER")['DON_NOT_ETTLEMENT']);
        }else{
            return true;
        }
    }

    /**
     * 结算历史筛选列表
     * @param Request $request
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function settlementHistory(Request $request)
    {
        $nowTime  = time();
        $lastWeek = $nowTime - 24 * 60 * 60 * 7;

        $beginTime   = date("Y-m-d H:i:s",$lastWeek);
        $endTime     = date("Y-m-d H:i:s",$nowTime);

        $billSettlementModel = new BillSettlement();

        $list = $billSettlementModel
            ->whereTime("created_at","between",["$beginTime","$endTime"])
            ->field("settlement_id,ettlement_at")
            ->order("created_at DESC")
            ->select();

        $list = json_decode(json_encode($list),true);

        return $this->com_return(true,config("params.SUCCESS"),$list);
    }

    /**
     * 结算历史详情
     * @param Request $request
     * @return array|mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function settlementHistoryDetails(Request $request)
    {
        $settlement_id = $request->param("settlement_id","");//结算id

        $rule = [
            "settlement_id|结算单据id" => "require",
        ];

        $request_res = [
            "settlement_id"    => $settlement_id,
        ];

        $validate = new Validate($rule);

        if (!$validate->check($request_res)){
            return $this->com_return(false,$validate->getError());
        }

        return $this->settlementHistoryDetailsPublic($settlement_id);
    }

    /**
     * 结算历史详情公共部分
     * @param $settlement_id
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function settlementHistoryDetailsPublic($settlement_id)
    {
        $billSettlementModel = new BillSettlement();

        $settlementInfo = $billSettlementModel
            ->where("settlement_id",$settlement_id)
            ->find();

        $settlementInfo = json_decode(json_encode($settlementInfo),true);

        $countObj = new CountPublic();

        /*消费类 on*/
        $consumerClass = $countObj->consumerClassDetails($settlement_id);
        $res['consumerClass'] = $consumerClass;
        /*消费类 off*/

        /*会员卡储值类 On*/
        $rechargeClass = $countObj->rechargeClassDetails($settlement_id);
        $res['rechargeClass'] = $rechargeClass;
        /*会员卡储值类 Off*/

        /*会员开卡类 On*/
        $vipCardClass = $countObj->vipCardClassDetails($settlement_id);
        $res['vipCardClass'] = $vipCardClass;
        /*会员开卡类 Off*/

        /*收款小计 On*/
        $receivablesLClass = $countObj->receivablesLClassDetails($settlement_id);
        $res['receivablesLClass'] = $receivablesLClass;
        /*收款小计 Off*/

        /*收款总金额 On*/
        $allClass = $countObj->allClassDetails($settlement_id);
        $res['allClass'] = $allClass;
        /*收款总金额 Off*/

        $settlementInfo["details_info"] = $res;

        return $this->com_return(true,config("params.SUCCESS"),$settlementInfo);
    }
}