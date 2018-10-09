<?php
/**
 * Created by PhpStorm.
 * User: qubojie
 * Date: 2018/9/14
 * Time: 下午7:42
 */
namespace app\wechat\controller;

use app\common\controller\UUIDUntil;
use app\services\LuoSiMaoSms;
use app\wechat\model\BillPayAssist;
use think\Db;
use think\Log;
use think\Request;
use think\Validate;

class ManagePointListTemporary extends HomeAction
{
    /**
     * 确认点单 - 临时
     * @param Request $request
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function confirmPointList(Request $request)
    {
        $table_id          = $request->param("table_id","");

        $table_no          = $request->param("table_no","");

        $phone             = $request->param("phone","");

        $verification_code = $request->param("code","");

        $rule = [
            "table_id|桌id" => "require",
            "table_no|桌号" => "require",
            "phone|电话号码" => "require|regex:1[3-8]{1}[0-9]{9}",
            "code|验证码"    => "require",
        ];

        $request_res = [
            "table_id" => $table_id,
            "table_no" => $table_no,
            "phone"    => $phone,
            "code"     => $verification_code,
        ];

        $validate = new Validate($rule);

        if (!$validate->check($request_res)){
            return $this->com_return(false,$validate->getError(),null);
        }

        if ($verification_code == "old"){
            $verification_code = "0000";
        }

        $token      = $request->header("Token");

        $manageInfo = $this->tokenGetManageInfo($token);

        $sid   = $manageInfo['sid'];
        $sname = $manageInfo['sales_name'];

        $userInfo = Db::name("user")
            ->alias("u")
            ->join("user_card uc","uc.uid = u.uid","LEFT")
            ->join("mst_card_vip cv","cv.card_id = uc.card_id","LEFT")
            ->field("u.uid,u.referrer_id")
            ->field("cv.card_name")
            ->where("u.phone",$phone)
            ->find();

        $uid       = $userInfo['uid'];
        $card_name = $userInfo['card_name'];

        if (empty($card_name)){
            $card_name = "非会员";
        }

        $referrer_id = $userInfo['referrer_id'];

        /*发送验证码 on*/
       /* $verification_code = getRandCode(4);

        $message = config('sms.point_list').config('sms.sign');

        $sms = new LuoSiMaoSms();

        $res = $sms->send($phone, str_replace('%code%', $verification_code, $message));

        if ($res){
            if (isset($res['error']) && $res['error'] == 0){

            }else{
                return $this->com_return(false, $res['msg']);
            }
        }else{
            return $this->com_return(false, config("sms.send_fail"));
        }*/
        /*发送验证码 off*/

        $UUID = new UUIDUntil();

        $pid  = $UUID->generateReadableUUID("P");

        $params = [
            "pid"               => $pid,
            "uid"               => $uid,
            "card_name"         => $card_name,
            "phone"             => $phone,
            "verification_code" => $verification_code,
            "table_id"          => $table_id,
            "table_no"          => $table_no,
            "sid"               => $sid,
            "sname"             => $sname,
            "type"              => config("bill_assist.bill_type")['0']['key'],
            "sale_status"       => config("bill_assist.bill_status")['0']['key'],
            "referrer_id"       => $referrer_id,
            "created_at"        => time(),
            "updated_at"        => time()
        ];

        $billAssistModel = new BillPayAssist();

        $is_ok = $billAssistModel
            ->insert($params);

        if ($is_ok){
            return $this->com_return(true,config("params.SUCCESS"),$params);
        }else{
            return $this->com_return(false,config("params.FAIL"));
        }
    }

    /**
     * 根据桌子获取用户信息
     * @param Request $request
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function accordingTableFindUserInfo(Request $request)
    {
        $table_id = $request->param("table_id","");//桌子id

        $rule = [
            "table_id|桌id" => "require",
        ];

        $request_res = [
            "table_id" => $table_id,
        ];

        $validate = new Validate($rule);

        if (!$validate->check($request_res)){
            return $this->com_return(false,$validate->getError(),null);
        }

        $nowTime = time();
        $sys_account_day_time = getSysSetting("sys_account_day_time");
        $now_h = date("H",$nowTime);

        if ($now_h >= $sys_account_day_time){
            //大于,新的一天
            $nowDateTime = strtotime(date("Ymd",$nowTime));
        }else{
           //小于,还是昨天
            $nowDateTime = strtotime(date("Ymd",$nowTime - 24 * 60 * 60));
        }

        $six_s                = 60 * 60 * $sys_account_day_time;
        $nowDateTime          = $nowDateTime + $six_s;
        $beginTime            = date("YmdHis",$nowDateTime);
        $endTime              = date("YmdHis",$nowDateTime + 24 * 60 * 60 - 1);

        $date_where['bp.created_at'] = ["between time",["$beginTime","$endTime"]];

        $billPayAssistModel = new BillPayAssist();

        $list = $billPayAssistModel
            ->alias("bp")
            ->join("user u","u.uid = bp.uid")
            ->where("bp.table_id",$table_id)
            ->where($date_where)
            ->group("bp.uid")
            ->field("u.uid,u.phone,u.name")
//            ->field("sum(bp.account_balance) account_balance_sum,sum(bp.account_cash_gift) account_cash_gift_sum")
            ->select();

        $list = json_decode(json_encode($list),true);

        return $this->com_return(true,config("params.SUCCESS"),$list);
    }

    /**
     * 根据用户电话号码获取今晚用户消费金额
     * @param Request $request
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getUserConsumeMoney(Request $request)
    {
        $phone = $request->param("phone","");//电话
        $rule = [
            "phone|电话号码" => "require|regex:1[3-8]{1}[0-9]{9}",
        ];

        $request_res = [
            "phone" => $phone,
        ];

        $validate = new Validate($rule);

        if (!$validate->check($request_res)){
            return $this->com_return(false,$validate->getError(),null);
        }

        $nowDateTime          = strtotime(date("Ymd"));
        $sys_account_day_time = getSysSetting("sys_account_day_time");
        $six_s                = 60 * 60 * $sys_account_day_time;
        $nowDateTime          = $nowDateTime + $six_s;
        $beginTime            = date("YmdHis",$nowDateTime);
        $endTime              = date("YmdHis",$nowDateTime + 24 * 60 * 60 - 1);

        $date_where['bp.created_at'] = ["between time",["$beginTime","$endTime"]];

        $one                = config("bill_assist.bill_status")['1']['key'];
        $seven              = config("bill_assist.bill_status")['7']['key'];
        $sale_status_str    = "$one,$seven";
        $billPayAssistModel = new BillPayAssist();

        $list = $billPayAssistModel
            ->alias("bp")
            ->where("bp.phone",$phone)
            ->where($date_where)
            ->where("bp.sale_status","IN",$sale_status_str)
            ->group("bp.phone")
            ->field("sum(bp.account_balance) account_balance_sum,sum(bp.account_cash_gift) account_cash_gift_sum")
            ->find();

        $list = json_decode(json_encode($list),true);

        return $this->com_return(true,config("params.SUCCESS"),$list);
    }
}