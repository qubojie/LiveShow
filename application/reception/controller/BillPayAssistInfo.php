<?php
/**
 * Created by PhpStorm.
 * User: qubojie
 * Date: 2018/9/15
 * Time: 上午10:04
 */
namespace app\reception\controller;

use app\admin\model\User;
use app\wechat\controller\CardCallback;
use app\wechat\model\BillPayAssist;
use app\wechat\model\JobAccount;
use app\wechat\model\JobUser;
use think\Db;
use think\Exception;
use think\Log;
use think\Request;
use think\Validate;

class BillPayAssistInfo extends CommonAction
{
    /**
     * 消费列表
     * @param Request $request
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function index(Request $request)
    {
        $keyword     = $request->param("keyword","");

        $dateTime    = $request->param("dateTime","");//时间

        $sale_status = $request->param("sale_status","");// 0待扣款   1 扣款完成  8 已退款    9交易取消

        $pagesize    = $request->param("pagesize","");

        $nowPage     = $request->param("nowPage","1");

        if (empty($pagesize)){
            $pagesize = 12;
        }

        if (empty($nowPage)){
            $nowPage = 1;
        }

        $config = [
            "page" => $nowPage,
        ];

        $rule = [
            "dateTime|时间"    => "require",
            "sale_status|状态" => "require",
        ];

        $request_res = [
            "dateTime"    => $dateTime,
            "sale_status" => $sale_status,
        ];

        $validate = new Validate($rule);

        if (!$validate->check($request_res)){
            return $this->com_return(false,$validate->getError());
        }

        $billPayAssistModel = new BillPayAssist();

        $r_column = $billPayAssistModel->r_column;

        foreach ($r_column as $key => $val){
            $r_column[$key] = "bpa.".$val;
        }

        $where = [];
        if (!empty($keyword)){
            $where['bpa.phone|bpa.verification_code'] = ['like',"%$keyword%"];
        }

        $sales_status_where = [];
        if (strlen($sale_status)){
            if ($sale_status == 100){
                $sales_status_where["bpa.sale_status"] = ["neq",config("bill_assist.bill_status")['9']['key']];
            }else{
                $sales_status_where["bpa.sale_status"] = ["eq",$sale_status];
            }
        }

        $nowDateTime = strtotime(date("Ymd"));

        if ($dateTime == 1){
            //今天
            $beginTime = date("YmdHis",$nowDateTime - 1);
            $endTime   = date("YmdHis",$nowDateTime + 24 * 60 * 60);

        }elseif ($dateTime == 2){
            //昨天
            $beginTime = date("YmdHis",$nowDateTime - 24 * 60 * 60);
            $endTime   = date("YmdHis",$nowDateTime - 1);

        }else{
            $dateTimeArr = explode(",",$dateTime);
            $beginTime   = date("YmdHis",$dateTimeArr[0]);;
            $endTime     = date("YmdHis",$dateTimeArr[1]);;
        }

        $date_where['bpa.created_at'] = ["between time",["$beginTime","$endTime"]];

//        dump($date_where);die;

        $list = $billPayAssistModel
            ->alias("bpa")
            ->join("user u","u.uid = bpa.uid","LEFT")
            ->join("user_gift_voucher ugv","ugv.gift_vou_code = bpa.gift_vou_code","LEFT")
            ->where($where)
            ->where($sales_status_where)
            ->where($date_where)
            ->order("bpa.created_at DESC")
            ->field($r_column)
            ->field("u.name,u.account_balance as user_account_balance,u.account_cash_gift as user_account_cash_gift")
            ->field("ugv.gift_vou_id,ugv.gift_vou_type,ugv.gift_vou_name,ugv.gift_vou_desc")
            ->paginate($pagesize,false,$config);

        $list = json_decode(json_encode($list),true);

        //账户余额统计
        $account_balance_sum = $billPayAssistModel
            ->alias("bpa")
            ->where($where)
            ->where($sales_status_where)
            ->where($date_where)
            ->sum("bpa.account_balance");

        //账户礼金统计
        $account_cash_gift_sum = $billPayAssistModel
            ->alias("bpa")
            ->where($where)
            ->where($sales_status_where)
            ->where($date_where)
            ->sum("bpa.account_cash_gift");

        //账户现金统计
        $account_cash_sum = $billPayAssistModel
            ->alias("bpa")
            ->where($where)
            ->where($sales_status_where)
            ->where($date_where)
            ->sum("bpa.cash");

        $list["account_balance_sum"]   = $account_balance_sum;
        $list["account_cash_gift_sum"] = $account_cash_gift_sum;
        $list["account_cash_sum"]      = $account_cash_sum;

        return $this->com_return(true,config("params.SUCCESS"),$list);
    }

    /**
     * 确认Or取消使用礼券
     * @param Request $request
     * @return array
     * @throws Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function cancelOrConfirmVoucher(Request $request)
    {
        $action = $request->param("action","");//1:确认; 2:取消
        $pid    = $request->param("pid","");//订单id
        $token  = $request->header("Token");

        if ($action == "1"){
            //确认
            return $this->confirmVoucher($pid,$token);

        }elseif ($action == "2"){
            //取消
            return $this->cancel($pid,$token);

        }else{
            return $this->com_return(false,config("params.PARAM_NOT_EMPTY"));
        }
    }

    /**
     * 确认消费Or取消消费
     * @param Request $request
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function cancelOrConfirm(Request $request)
    {
        $action          = $request->param("action","");//1:确认; 2:取消

        $balance_money   = (int)$request->param("balance_money","");//余额消费金额

        $cash_gift_money = (int)$request->param("cash_gift_money","");//礼金消费金额

        $cash_money      = (int)$request->param("cash_money","");//现金消费金额

        $pid             = $request->param("pid","");

        $token           = $request->header("Token");

        if ($action == "1"){
            //确认
            return $this->confirm($token,$pid,$balance_money,$cash_gift_money,$cash_money);

        }elseif ($action == "2"){
            //取消
            return $this->cancel($pid,$token);

        }else{
            return $this->com_return(false,config("params.PARAM_NOT_EMPTY"));
        }

    }

    /**
     * 确认使用礼券
     * @param $pid
     * @param $token
     * @return array
     * @throws Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    protected function confirmVoucher($pid,$token)
    {
        $pidInfo = $this->checkPidStatus($pid);

        if (!$pidInfo['result']){
            return $pidInfo;
        }

        $pidInfo = $pidInfo["data"];

        $uid = $pidInfo['uid'];

        $gift_vou_code = $pidInfo['gift_vou_code'];//券码

        $userInfo = getUserInfo($uid);

        if (empty($userInfo)){
            return $this->com_return(false,config("params.USER")['USER_NOT_EXIST']);
        }

        $manageInfo = $this->receptionTokenGetManageInfo($token);

        Db::startTrans();
        try{

            /*更新订单 on*/
            $billPayAssistParams = [
                "sale_status"   => config("bill_assist.bill_status")['1']['key'],
                "pay_time"      => time(),
                "check_user"    => $manageInfo['sales_name'],
                "check_time"    => time(),
                "check_reason"  => "确认使用礼券",
                "updated_at"    => time()
            ];

            $billPayAssistUpdate = Db::name("bill_pay_assist")
                ->where("pid",$pid)
                ->update($billPayAssistParams);

            if ($billPayAssistUpdate == false){
                return $this->com_return(false,config("params.ABNORMAL_ACTION")." - 001");
            }
            /*更新订单 off*/

            /*更新礼券 on*/
            $voucherInfo = Db::name("user_gift_voucher")
                ->where("gift_vou_code",$gift_vou_code)
                ->find();

            $voucherInfo = json_decode(json_encode($voucherInfo),true);

            if (empty($voucherInfo)){
                return $this->com_return(false,config("params.VOUCHER")['VOUCHER_NOT_EXIST']);
            }

            if ($voucherInfo['status'] != config("voucher.status")['0']['key']){
                return $this->com_return(false,config("params.VOUCHER")['VOUCHER_NOT_EXIST']);
            }

            if ($voucherInfo['gift_vou_type'] == config("voucher.type")['0']['key']){
                //单次使用
                $status = config("voucher.status")['1']['key'];

            }else{
                $status = config("voucher.status")['0']['key'];
            }

            $voucherParams = [
                "status"      => $status,
                "use_time"    => time(),
                "review_user" => $manageInfo['sales_name'],
                "updated_at"  => time()
            ];


            $voucherUpdate = Db::name("user_gift_voucher")
                ->where("gift_vou_code",$gift_vou_code)
                ->update($voucherParams);

            if ($voucherUpdate == false){
                return $this->com_return(false,config("params.ABNORMAL_ACTION")." - 002");
            }
            /*更新礼券 off*/
            Db::commit();
            return $this->com_return(true,config("params.SUCCESS"));

        }catch (Exception $e){
            Db::rollback();
            return $this->com_return(false,$e->getMessage());
        }
    }


    /**
     * 确认消费
     * @param $token
     * @param $pid
     * @param $balance_money
     * @param $cash_gift_money
     * @param $cash_money
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    protected function confirm($token,$pid,$balance_money,$cash_gift_money,$cash_money)
    {
        $rule = [
            "pid|订单id"              => "require",
            "balance_money|余额金额"   => "require|number",
            "cash_gift_money|礼金金额" => "require|number",
        ];

        $request_res = [
            "pid"             => $pid,
            "balance_money"   => $balance_money,
            "cash_gift_money" => $cash_gift_money,
        ];

        $validate = new Validate($rule);

        if (!$validate->check($request_res)){
            return $this->com_return(false,$validate->getError(),null);
        }

        $pidInfo = $this->checkPidStatus($pid);

        if (!$pidInfo['result']){
            return $pidInfo;
        }

        $uid = $pidInfo["data"]['uid'];

        $userInfo = getUserInfo($uid);

        if (empty($userInfo)){
            return $this->com_return(false,config("params.USER")['USER_NOT_EXIST']);
        }

        $manageInfo        = $this->receptionTokenGetManageInfo($token);

        $action_user       = $manageInfo['sales_name'];

        $account_balance   = $userInfo['account_balance'];//余额账户

        $account_cash_gift = $userInfo['account_cash_gift'];//礼金账户

        $referrer_type     = $userInfo['referrer_type'];//推荐人类型

        $referrer_id       = $userInfo['referrer_id'];//推荐人id

        Db::startTrans();

        try{

            if ($cash_gift_money > $account_cash_gift){
                //礼金账户余额不足
                return $this->com_return(false,config("params.ORDER")['GIFT_NOT_ENOUGH']);
            }

            //消费后礼金余额
            $new_cash_gift = $account_cash_gift - $cash_gift_money;

            /*获取开卡用户返还比例 on*/
            $publicObj = new PublicAction();

            $returnMoney = $publicObj->uidGetCardReturnMoney($uid);

//            $consumption_money = $balance_money + $cash_money;
            $consumption_money = $balance_money;

            if (!empty($returnMoney)){

                $consume_cash_gift      = $returnMoney['consume_cash_gift'];     //消费持卡人返礼金
                $consume_commission     = $returnMoney['consume_commission'];    //消费持卡人返佣金
                $consume_job_cash_gift  = $returnMoney['consume_job_cash_gift']; //消费推荐人返礼金
                $consume_job_commission = $returnMoney['consume_job_commission'];//消费推荐人返佣金

                $consumptionReturnMoney = $publicObj->consumptionReturnMoney($uid,$referrer_id,$referrer_type,$consume_cash_gift,$consume_commission,$consume_job_cash_gift,$consume_job_commission,$consumption_money);

                $return_cash_gift        = $consumptionReturnMoney['job_cash_gift_return_money'];//返还推荐人礼金
                $return_commission       = $consumptionReturnMoney['job_commission_return_money'];//返给推荐人佣金
                $cash_gift_return_money  = $consumptionReturnMoney['cash_gift_return_money'];//返给持卡用户礼金
                $commission_return_money = $consumptionReturnMoney['commission_return_money'];//返给持卡用户的佣金
            }else{
                $return_cash_gift        = 0;
                $return_commission       = 0;
                $cash_gift_return_money  = 0;
                $commission_return_money = 0;
            }

            $cardCallBackObj = new CardCallback();

            /*获取开卡用户返还比例 off*/

            if ($return_cash_gift > 0){
                //返还推荐人礼金

                //获取推荐人礼金账户余额
                $referrerUserInfo = Db::name("user")
                    ->where("uid",$referrer_id)
                    ->field("account_cash_gift")
                    ->find();
                $referrerUserInfo = json_decode(json_encode($referrerUserInfo),true);

                $new_account_cash_gift = $referrerUserInfo['account_cash_gift'] + $return_cash_gift;


                $referrerUserParams = [
                    "account_cash_gift" => $new_account_cash_gift,
                    "updated_at"        => time()
                ];

                $referrerUserReturn = Db::name("user")
                    ->where("uid",$referrer_id)
                    ->update($referrerUserParams);

                if ($referrerUserReturn == false){
                    return $this->com_return(false,config("params.ABNORMAL_ACTION")." - 007");
                }

                /*推荐人礼金明细 on*/
                $referrerUserDParams = [
                    'uid'            => $referrer_id,
                    'cash_gift'      => $return_cash_gift,
                    'last_cash_gift' => $new_account_cash_gift,
                    'change_type'    => '2',
                    'action_user'    => "sys",
                    'action_type'    => config('user.gift_cash')['consumption_give']['key'],
                    'action_desc'    => config('user.gift_cash')['consumption_give']['name'],
                    'oid'            => $pid,
                    'created_at'     => time(),
                    'updated_at'     => time()
                ];

                //给推荐用户添加礼金明细
                $userAccountCashGiftReturn = $cardCallBackObj->updateUserAccountCashGift($referrerUserDParams);

                if ($userAccountCashGiftReturn == false) {
                    return $this->com_return(false,config("params.ABNORMAL_ACTION")." - 008");
                }
                /*推荐人礼金明细 off*/

            }

            if ($return_commission > 0){
                //返给推荐人佣金
                $referrerUserJobInfo = Db::name("job_user")
                    ->where("uid",$referrer_id)
                    ->find();

                $referrerUserJobInfo = json_decode(json_encode($referrerUserJobInfo),true);

                if (empty($referrerUserJobInfo)){
                    //新增
                    $newJobParams = [
                        "uid"         => $referrer_id,
                        "job_balance" => $return_commission,
                        "created_at"  => time(),
                        "updated_at"  => time()
                    ];

                    $jobUserInsert = Db::name("job_user")
                        ->insert($newJobParams);

                    if ($jobUserInsert == false){
                        return $this->com_return(false,config("params.ABNORMAL_ACTION")." - 009");
                    }

                    $referrer_last_balance = $return_commission;

                }else{

                    $referrer_new_job_balance = $referrerUserJobInfo['job_balance'] + $return_commission;

                    //更新
                    $newJobParams = [
                        "job_balance" => $referrer_new_job_balance,
                        "updated_at"  => time()
                    ];

                    $jobUserUpdate = Db::name("job_user")
                        ->where("uid",$referrer_id)
                        ->update($newJobParams);

                    if ($jobUserUpdate == false){
                        return $this->com_return(false,config("params.ABNORMAL_ACTION")." - 010");
                    }

                    $referrer_last_balance = $referrer_new_job_balance;
                }

                /*佣金明细 on*/

                //添加推荐用户佣金明细表
                $jobAccountParams = [
                    "uid"          => $referrer_id,
                    "balance"      => $commission_return_money,
                    "last_balance" => $referrer_last_balance,
                    "change_type"  => 2,
                    "action_user"  => 'sys',
                    "action_type"  => config('user.job_account')['consume']['key'],
                    "oid"          => $pid,
                    "deal_amount"  => $consumption_money,
                    "action_desc"  => config('user.job_account')['consume']['name'],
                    "created_at"   => time(),
                    "updated_at"   => time()
                ];

                $jobAccountReturn = $cardCallBackObj->insertJobAccount($jobAccountParams);



                if ($jobAccountReturn == false){
                    return $this->com_return(false,config("params.ABNORMAL_ACTION"). "- 006");
                }
                /*佣金明细 off*/
            }

            //用户减去消费后的礼金数 + 加上 消费赠送礼金数  = 最终所剩礼金余额
            $user_returned_cash_gift = $new_cash_gift + $cash_gift_return_money; //用户最终所剩礼金数

            if ($cash_gift_return_money > 0){
                //返给持卡用户礼金
                $cashGiftParams = [
                    'uid'            => $uid,
                    'cash_gift'      => $cash_gift_return_money,
                    'last_cash_gift' => $user_returned_cash_gift,
                    'change_type'    => '2',
                    'action_user'    => 'sys',
                    'action_type'    => config('user.gift_cash')['consumption_give']['key'],
                    'action_desc'    => config('user.gift_cash')['consumption_give']['name'],
                    'oid'            => $pid,
                    'created_at'     => time(),
                    'updated_at'     => time()
                ];

                //给用户添加礼金明细
                $userAccountCashGiftReturn = $cardCallBackObj->updateUserAccountCashGift($cashGiftParams);

                if ($userAccountCashGiftReturn == false) {
                    return $this->com_return(false,config("params.ABNORMAL_ACTION")." - 003");
                }

                $return_own_cash_gift = $cash_gift_return_money;//返还本人礼金数
            }else{
                $return_own_cash_gift = 0;//返还本人礼金数

            }

            if ($commission_return_money > 0){
                //返给持卡用户的佣金
                $userJobInfo = Db::name("job_user")
                    ->where("uid",$uid)
                    ->find();

                $userJobInfo = json_decode(json_encode($userJobInfo),true);
                if (empty($userJobInfo)){
                    //新增
                    $newJobParams = [
                        "uid"         => $uid,
                        "job_balance" => $commission_return_money,
                        "created_at"  => time(),
                        "updated_at"  => time()
                    ];

                    $jobUserInsert = Db::name("job_user")
                        ->insert($newJobParams);

                    if ($jobUserInsert == false){
                        return $this->com_return(false,config("params.ABNORMAL_ACTION")." - 004");
                    }

                    $last_balance = $commission_return_money;


                }else{

                    $new_job_balance = $userJobInfo['job_balance'] + $commission_return_money;

                    //更新
                    $newJobParams = [
                        "job_balance" => $new_job_balance,
                        "updated_at"  => time()
                    ];

                    $jobUserUpdate = Db::name("job_user")
                        ->where("uid",$uid)
                        ->update($newJobParams);

                    if ($jobUserUpdate == false){
                        return $this->com_return(false,config("params.ABNORMAL_ACTION")." - 005");
                    }

                    $last_balance = $new_job_balance;

                }

                $return_own_commission = $commission_return_money;//返本人佣金

                /*佣金明细 on*/

                //添加用户佣金明细表
                $jobAccountParams = [
                    "uid"          => $uid,
                    "balance"      => $commission_return_money,
                    "last_balance" => $last_balance,
                    "change_type"  => 2,
                    "action_user"  => 'sys',
                    "action_type"  => config('user.job_account')['consume']['key'],
                    "oid"          => $pid,
                    "deal_amount"  => $consumption_money,
                    "action_desc"  => config('user.job_account')['consume']['name'],
                    "created_at"   => time(),
                    "updated_at"   => time()
                ];

                $jobAccountReturn = $cardCallBackObj->insertJobAccount($jobAccountParams);

                if ($jobAccountReturn == false){
                    return $this->com_return(false,config("params.ABNORMAL_ACTION"). "- 006");
                }
                /*佣金明细 off*/
            }else{
                $return_own_commission = 0;//返本人佣金
            }

            /*余额消费 on*/

            if ($balance_money > $account_balance){
                //钱包余额不足
                return $this->com_return(false,config("params.ORDER")['BALANCE_NOT_ENOUGH']);
            }

            $user_new_account_balance = $account_balance - $balance_money;

            if ($balance_money > 0){
                //余额消费
                $balanceActionReturn = $this->balanceAction($uid,$pid,$balance_money,$user_new_account_balance,$action_user);

                if (!$balanceActionReturn){
                    return $this->com_return(false,config("params.ABNORMAL_ACTION")." - 001");
                }
            }
            /*余额消费 off*/


            /*礼金消费 on*/
            if ($cash_gift_money > 0){
                //礼金消费
                $cashGiftActionReturn = $this->cashGiftAction($uid,$pid,$cash_gift_money,$new_cash_gift,$action_user);

                if (!$cashGiftActionReturn){
                    return $this->com_return(false,config("params.ABNORMAL_ACTION")." - 002");
                }
            }
            /*礼金消费 off*/

            $returnUserPoint = getSysSetting("card_consume_point_ratio");

            $return_point = intval($balance_money * ($returnUserPoint/100));//获取返还用户积分数

            /*用户积分更新 On*/
            if ($return_point > 0){
                $user_old_account_point = $userInfo["account_point"];

                $user_new_account_point = $user_old_account_point + $return_point;

                $user_new_level_id = getUserNewLevelId($user_new_account_point);//用户新的积分等级

                /*积分明细 On*/
                //2.更新用户积分明细
                $updateAccountPointParams = [
                    'uid'         => $uid,
                    'point'       => $return_point,
                    'last_point'  => $user_new_account_point,
                    'change_type' => 2,
                    'action_user' => 'sys',
                    'action_type' => config("user.point")['consume_reward']['key'],
                    'action_desc' => config("user.point")['consume_reward']['name'],
                    'oid'         => $pid,
                    'created_at'  => time(),
                    'updated_at'  => time()
                ];

                $userAccountPointReturn = $cardCallBackObj->updateUserAccountPoint($updateAccountPointParams);

                if ($userAccountPointReturn === false){
                    return $this->com_return(false,config("params.ABNORMAL_ACTION"). "- 011");
                }
                /*积分明细 Off*/
            }else{
                $user_new_account_point = $userInfo['account_point'];//用户积分信息
                $user_new_level_id      = $userInfo['level_id'];//用户等级信息
            }
            /*用户积分更新 Off*/

            /*更新用户信息 On*/
            $userParams = [
                "account_balance"   => $user_new_account_balance,
                "account_point"     => $user_new_account_point,
                "account_cash_gift" => $user_returned_cash_gift,
                "level_id"          => $user_new_level_id,
                "updated_at"        => time()
            ];

            $userModel = new User();

            $updateUserInfo = $userModel
                ->where("uid",$uid)
                ->update($userParams);

            if ($updateUserInfo === false){
                return $this->com_return(false,config("params.ABNORMAL_ACTION"). "- 012");
            }

            /*更新用户信息 Off*/

            /*订单状态变更 on*/
            $billPayAssistParams = [
                "sale_status"           => config("bill_assist.bill_status")['1']['key'],
                "pay_time"              => time(),
                "check_user"            => $action_user,
                "check_time"            => time(),
                "check_reason"          => "确认消费",
                "account_balance"       => $balance_money,
                "account_cash_gift"     => $cash_gift_money,
                "cash"                  => $cash_money,
                "return_point"          => $return_point,//返还用户积分
                "return_own_commission" => $return_own_commission,//返还本人佣金
                "return_own_cash_gift"  => $return_own_cash_gift,//返本人礼金
                "return_cash_gift"      => $return_cash_gift,//返给推荐人的礼金
                "return_commission"     => $return_commission,//返佣金
                "updated_at"            => time()
            ];

            $is_ok = Db::name("bill_pay_assist")
                ->where("pid",$pid)
                ->update($billPayAssistParams);

            if ($is_ok === false){
                return $this->com_return(false,config("params.ABNORMAL_ACTION"). "- 013");
            }
            /*订单状态变更 off*/
            Db::commit();
            return $this->com_return(true,config("params.SUCCESS"));

        }catch (Exception $e){
            Db::rollback();
            return $this->com_return(false,$e->getMessage());
        }
    }


    /**
     * 礼金消费明细操作
     * @param $uid
     * @param $pid
     * @param $cash_gift_money
     * @param $new_cash_gift
     * @param $action_user
     * @return array|bool
     */
    protected function cashGiftAction($uid,$pid,$cash_gift_money,$new_cash_gift,$action_user)
    {
        /*礼金消费明细 on*/
        $userAccountCashGiftParams = [
            'uid'            => $uid,
            'cash_gift'      => '-'.$cash_gift_money,
            'last_cash_gift' => $new_cash_gift,
            'change_type'    => '1',
            'action_user'    => $action_user,
            'action_type'    => config('user.gift_cash')['consume']['key'],
            'action_desc'    => config('user.gift_cash')['consume']['name'],
            'oid'            => $pid,
            'created_at'     => time(),
            'updated_at'     => time()
        ];

        $cardCallBackObj = new CardCallback();

        //给用户添加礼金明细
        $userAccountCashGiftReturn = $cardCallBackObj->updateUserAccountCashGift($userAccountCashGiftParams);

        if ($userAccountCashGiftReturn == false) {
            return false;
        }
        /*礼金消费明细 off*/

        /*用户礼金账户信息更新 on*/

        $userParams = [
            "account_cash_gift" => $new_cash_gift,
            "updated_at"        => time()
        ];

        $updateUserInfoReturn = $cardCallBackObj->updateUserInfo($userParams,$uid);

        if ($updateUserInfoReturn == false){
            return false;
        }

        /*用户礼金账户信息更新 off*/

        return true;
    }

    /**
     * 余额明细操作
     * @param $uid
     * @param $pid
     * @param $balance_money
     * @param $new_account_balance
     * @param $action_user
     * @return array|bool
     */
    protected function balanceAction($uid,$pid,$balance_money,$new_account_balance,$action_user)
    {
        /*余额消费明细 on*/
        //插入用户余额消费明细
        //余额明细参数
        $insertUserAccountParams = [
            "uid"          => $uid,
            "balance"      => "-" . $balance_money,
            "last_balance" => $new_account_balance,
            "change_type"  => '1',
            "action_user"  => $action_user,
            "action_type"  => config('user.account')['consume']['key'],
            "oid"          => $pid,
            "deal_amount"  => $balance_money,
            "action_desc"  => config('user.account')['consume']['name'],
            "created_at"   => time(),
            "updated_at"   => time()
        ];

        $cardCallBackObj = new CardCallback();

        //插入用户余额消费明细
        $insertUserAccountReturn = $cardCallBackObj->updateUserAccount($insertUserAccountParams);

        if ($insertUserAccountReturn == false) {
            return false;
        }
        /*余额消费明细 off*/

        /*用户余额信息更新 on*/

        $userParams = [
            "account_balance" => $new_account_balance,
            "updated_at"      => time()
        ];

        $updateUserInfoReturn = $cardCallBackObj->updateUserInfo($userParams,$uid);

        if ($updateUserInfoReturn == false){
            return false;
        }
        /*用户余额信息更新 off*/

        return true;
    }


    /**
     * 取消消费
     * @param $pid
     * @param $token
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    protected function cancel($pid,$token)
    {
        if (empty($pid)){
            return $this->com_return(false,config("params.PARAM_NOT_EMPTY")." - 001");
        }

        $pidInfo = $this->checkPidStatus($pid);

        if (!$pidInfo['result']){
            return $pidInfo;
        }

        $manageInfo = $this->receptionTokenGetManageInfo($token);

        $cancel_user = $manageInfo['sales_name'];


        $params = [
            "sale_status"   => config("bill_assist.bill_status")['9']['key'],
            "cancel_user"   => $cancel_user,
            "cancel_time"   => time(),
            "auto_cancel"   => 0,
            "cancel_reason" => "手动取消",
            "check_user"    => $cancel_user,
            "check_time"    => time(),
            "check_reason"  => "手动取消",
            "updated_at"    => time(),
        ];

        $billPayAssistModel = new BillPayAssist();

        $is_ok = $billPayAssistModel
            ->where("pid",$pid)
            ->update($params);

        if ($is_ok !== false){
            return $this->com_return(true,config("params.SUCCESS"));
        }else{
            return $this->com_return(false,config("params.FAIL"));
        }
    }

    /**
     * 检查订单状态
     * @param $pid
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    protected function checkPidStatus($pid)
    {
        $billPayAssistModel = new BillPayAssist();

        $info = $billPayAssistModel
            ->where("pid",$pid)
            ->find();

        $info = json_decode(json_encode($info),true);

        if (empty($info)){
            return $this->com_return(false,config("params.ORDER")['ORDER_NOT_EXIST']);
        }

        $sale_status = $info['sale_status'];

        if ($sale_status == config("bill_assist.bill_status")['9']['key']) {
            return $this->com_return(false,config("params.ORDER")['ORDER_CANCEL']);
        }

        if ($sale_status == config("bill_assist.bill_status")['1']['key']){
            return $this->com_return(false,config("params.ORDER")['completed']);
        }

        return $this->com_return(true,config("params.SUCCESS"),$info);
    }

    /**
     * 全额退款
     * @param Request $request
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function fullRefund(Request $request)
    {
        $pid          = $request->param("pid","");
        $check_reason = $request->param("check_reason","");

        if (empty($check_reason)){
            $check_reason = "全额退款,手动操作";
        }

        if (empty($pid)){
            return $this->com_return(false,config("params.PARAM_NOT_EMPTY"));
        }

        $billPayAssistModel = new BillPayAssist();

        $r_column = $billPayAssistModel->r_column;
        foreach ($r_column as $key => $val){
            $r_column[$key] = "bpa.".$val;
        }

        $billInfo = $billPayAssistModel
            ->alias("bpa")
            ->join("user u","u.uid = bpa.uid","LEFT")
            ->join("job_user ju","ju.uid = u.uid","LEFT")
            ->where("bpa.pid",$pid)
            ->field($r_column)
            ->field("ju.job_balance")
            ->field("u.name,u.account_point,u.level_id,u.account_balance as user_account_balance,u.account_cash_gift as user_account_cash_gift")
            ->find();

        $billInfo = json_decode(json_encode($billInfo),true);

        if (empty($billInfo)){
            return $this->com_return(false,config("params.ORDER")['ORDER_NOT_EXIST']);
        }

        $sale_status = $billInfo['sale_status'];//订单状态

        if ($sale_status != config("bill_assist.bill_status")['1']['key']){
            //如果不是已完成状态,不可进行全额退款操作
            return $this->com_return(false,config("params.ORDER")['REFUND_DISH_ABNORMAL']);
        }

        $type = $billInfo['type'];

        if ($type == config("bill_assist.bill_type")['6']['key']){
            //礼券消费不可取消
            return $this->com_return(false,config("params.ORDER")['VOUCHER_NOT_REFUND']);
        }

        $uid                    = $billInfo['uid'];//用户id
        $level_id               = $billInfo['level_id'];//用户等级id
        $job_balance            = $billInfo['job_balance'];//用户佣金
        $account_balance        = $billInfo['account_balance'];//消费储值金额
        $account_cash_gift      = $billInfo['account_cash_gift'];//消费礼金余额
        $return_point           = $billInfo['return_point'];//返还积分
        $return_own_commission  = $billInfo['return_own_commission'];//返还自己佣金
        $return_own_cash_gift   = $billInfo['return_own_cash_gift'];//返还自己礼金
        $referrer_id            = $billInfo['referrer_id'];//推荐人id
        $return_cash_gift       = $billInfo['return_cash_gift'];//推荐人返还礼金
        $return_commission      = $billInfo['return_commission'];//推荐人返还佣金
        $user_account_balance   = $billInfo['user_account_balance'];//用户所剩储值金额
        $user_account_cash_gift = $billInfo['user_account_cash_gift'];//用户所剩礼金金额
        $user_account_point     = $billInfo['account_point'];//用户积分余额

        $cardCallBackObj = new CardCallback();

        $token = $request->header("Token","");

        $manageInfo = $this->receptionTokenGetManageInfo($token);

        $action_user = $manageInfo['sales_name'];

        Db::startTrans();

        try{
            /*用户返款操作 on*/
            if ($account_balance > 0){
                //有储值消费
                $new_account_balance = $user_account_balance + $account_balance;

                //插入储值消费明细
                //余额明细参数
                $insertUserAccountParams = [
                    "uid"          => $uid,
                    "balance"      => $account_balance,
                    "last_balance" => $new_account_balance,
                    "change_type"  => 1,
                    "action_user"  => $action_user,
                    "action_type"  => config('user.account')['hand_refund']['key'],
                    "oid"          => $pid,
                    "deal_amount"  => $account_balance,
                    "action_desc"  => config("user.account")['hand_refund']['name'],
                    "created_at"   => time(),
                    "updated_at"   => time()
                ];

                //插入用户充值明细
                $insertUserAccountReturn = $cardCallBackObj->updateUserAccount($insertUserAccountParams);

                if (!$insertUserAccountReturn){
                    return $this->com_return(false,config("params.ABNORMAL_ACTION"). " - 002");
                }

            }else{
                $new_account_balance = $user_account_balance;
            }

            if ($account_cash_gift > 0){
                //有礼金消费
                $new_account_cash_gift = $user_account_cash_gift + $account_cash_gift;

                $userAccountCashGiftParams = [
                    'uid'            => $uid,
                    'cash_gift'      => $account_cash_gift,
                    'last_cash_gift' => $new_account_cash_gift,
                    'change_type'    => 1,
                    'action_user'    => $action_user,
                    'action_type'    => config('user.gift_cash')['hand_refund']['key'],
                    'action_desc'    => config('user.gift_cash')['hand_refund']['name'],
                    'oid'            => $pid,
                    'created_at'     => time(),
                    'updated_at'     => time()
                ];

                //给用户添加礼金明细
                $userAccountCashGiftReturn = $cardCallBackObj->updateUserAccountCashGift($userAccountCashGiftParams);

                if (!$userAccountCashGiftReturn){
                    return $this->com_return(false,config("params.ABNORMAL_ACTION"). " - 003");
                }

            }else{
                $new_account_cash_gift = $user_account_cash_gift;
            }

            if ($return_point > 0){
                //有积分返还
                $new_account_point = $user_account_point - $return_point;

                $level_id = getUserNewLevelId($new_account_point);

                //2.更新用户积分明细
                $updateAccountPointParams = [
                    'uid'         => $uid,
                    'point'       => '-'.$return_point,
                    'last_point'  => $new_account_point,
                    'change_type' => 1,
                    'action_user' => $action_user,
                    'action_type' => config("user.point")['refund_consume']['key'],
                    'action_desc' => config("user.point")['refund_consume']['name'],
                    'oid'         => $pid,
                    'created_at'  => time(),
                    'updated_at'  => time()
                ];

                $userAccountPointReturn = $cardCallBackObj->updateUserAccountPoint($updateAccountPointParams);

                if (!$userAccountPointReturn){
                    return $this->com_return(false,config("params.ABNORMAL_ACTION"). " - 004");
                }

            }else{
                $new_account_point = $user_account_point;
            }

            if ($return_own_commission > 0){
                //自己有佣金返还
                $new_job_balance = $job_balance - $return_own_commission;

                /*佣金明细 on*/

                //添加推荐用户佣金明细表
                $jobAccountParams = [
                    "uid"          => $uid,
                    "balance"      => "-".$return_own_commission,
                    "last_balance" => $new_job_balance,
                    "change_type"  => 1,
                    "action_user"  => $action_user,
                    "action_type"  => config('user.job_account')['return']['key'],
                    "oid"          => $pid,
                    "deal_amount"  => $account_balance,
                    "action_desc"  => config('user.job_account')['return']['name'],
                    "created_at"   => time(),
                    "updated_at"   => time()
                ];

                $jobAccountReturn = $cardCallBackObj->insertJobAccount($jobAccountParams);

                if ($jobAccountReturn == false){
                    return $this->com_return(false,config("params.ABNORMAL_ACTION"). " - 005");
                }

                $jobUserParams = [
                    "job_balance" => $new_job_balance,
                    "updated_at"  => time()
                ];

                $jobUserModel = new JobUser();

                $jobUserReturn = $jobUserModel
                    ->where("uid",$uid)
                    ->update($jobUserParams);

                if ($jobUserReturn == false){
                    return $this->com_return(false,config("params.ABNORMAL_ACTION"). " - 006");
                }

            }

            if ($return_own_cash_gift > 0){
                //自己有礼金返还
                $new_account_cash_gift_f = $new_account_cash_gift - $return_own_cash_gift;

                $userAccountCashGiftParams = [
                    'uid'            => $uid,
                    'cash_gift'      => $return_own_cash_gift,
                    'last_cash_gift' => $new_account_cash_gift_f,
                    'change_type'    => 1,
                    'action_user'    => $action_user,
                    'action_type'    => config('user.gift_cash')['hand_refund']['key'],
                    'action_desc'    => config('user.gift_cash')['hand_refund']['name'],
                    'oid'            => $pid,
                    'created_at'     => time(),
                    'updated_at'     => time()
                ];

                //给用户添加礼金明细
                $userAccountCashGiftReturn = $cardCallBackObj->updateUserAccountCashGift($userAccountCashGiftParams);

                if ($userAccountCashGiftReturn == false){
                    return $this->com_return(false,config("params.ABNORMAL_ACTION"). " - 007");
                }

            }else{
                $new_account_cash_gift_f = $new_account_cash_gift;
            }

            $userModel = new User();

            $userUpdateParams = [
                "level_id"          => $level_id,
                "account_balance"   => $new_account_balance,
                "account_point"     => $new_account_point,
                "account_cash_gift" => $new_account_cash_gift_f,
                "updated_at"        => time()
            ];

            $updateUserReturn = $userModel
                ->where("uid",$uid)
                ->update($userUpdateParams);

            if ($updateUserReturn == false){
                return $this->com_return(false,config("params.ABNORMAL_ACTION")." - 001");
            }

            //更新用户信息

            /*用户返款操作 off*/

            /*推荐人操作部分 on*/
            if ($return_cash_gift > 0){

                //获取推荐人礼金信息
                $referrerInfo = $userModel
                    ->alias("u")
                    ->where("u.uid",$referrer_id)
                    ->field("u.account_cash_gift")
                    ->find();

                $referrerInfo = json_decode(json_encode($referrerInfo),true);

                if (!empty($referrerInfo)){

                    $referrer_account_cash_gift = (int)$referrerInfo['account_cash_gift'];//推荐人现有礼金

                    //推荐人有返还礼金
                    $new_referrer_account_cash_gift = $referrer_account_cash_gift - $return_cash_gift;

                    /*推荐人礼金明细 on*/
                    $referrerUserDParams = [
                        'uid'            => $referrer_id,
                        'cash_gift'      => "-".$return_cash_gift,
                        'last_cash_gift' => $new_referrer_account_cash_gift,
                        'change_type'    => 1,
                        'action_user'    => $action_user,
                        'action_type'    => config('user.gift_cash')['hand_consume']['key'],
                        'action_desc'    => config('user.gift_cash')['hand_consume']['name'],
                        'oid'            => $pid,
                        'created_at'     => time(),
                        'updated_at'     => time()
                    ];

                    //给推荐用户添加礼金明细
                    $userAccountCashGiftReturn = $cardCallBackObj->updateUserAccountCashGift($referrerUserDParams);

                    if ($userAccountCashGiftReturn == false){
                        return $this->com_return(false,config("params.ABNORMAL_ACTION")." - 008");
                    }

                    /*更新推荐用户礼金信息 on*/
                    $referrerUserCashParams = [
                        "account_cash_gift" => $new_referrer_account_cash_gift,
                        "updated_at"        => time()
                    ];

                    $referrerUserCashReturn = $userModel
                        ->where("uid",$referrer_id)
                        ->update($referrerUserCashParams);

                    if ($referrerUserCashReturn == false){
                        return $this->com_return(false,config("params.ABNORMAL_ACTION")." - 009");
                    }
                    /*更新推荐用户礼金信息 off*/
                }
            }

            if ($return_commission > 0){
                //推荐人有返还佣金


                //返给推荐人佣金
                $referrerUserJobInfo = Db::name("job_user")
                    ->where("uid",$referrer_id)
                    ->find();

                $referrerUserJobInfo = json_decode(json_encode($referrerUserJobInfo),true);

                if (empty($referrerUserJobInfo)){
                    //新增
                    $newJobParams = [
                        "uid"         => $referrer_id,
                        "job_balance" => "-".$return_commission,
                        "created_at"  => time(),
                        "updated_at"  => time()
                    ];

                    $jobUserInsert = Db::name("job_user")
                        ->insert($newJobParams);

                    if ($jobUserInsert == false){
                        return $this->com_return(false,config("params.ABNORMAL_ACTION")." - 010");
                    }

                    $referrer_last_balance = "-".$return_commission;

                }else{

                    $referrer_new_job_balance = $referrerUserJobInfo['job_balance'] - $return_commission;

                    //更新
                    $newJobParams = [
                        "job_balance" => $referrer_new_job_balance,
                        "updated_at"  => time()
                    ];

                    $jobUserUpdate = Db::name("job_user")
                        ->where("uid",$referrer_id)
                        ->update($newJobParams);

                    if ($jobUserUpdate == false){
                        return $this->com_return(false,config("params.ABNORMAL_ACTION")." - 011");
                    }

                    $referrer_last_balance = $referrer_new_job_balance;
                }

                /*佣金明细 on*/
                //添加推荐用户佣金明细表
                $jobAccountParams = [
                    "uid"          => $referrer_id,
                    "balance"      => "-".$return_commission,
                    "last_balance" => $referrer_last_balance,
                    "change_type"  => 1,
                    "action_user"  => $action_user,
                    "action_type"  => config('user.job_account')['return']['key'],
                    "oid"          => $pid,
                    "deal_amount"  => $account_balance,
                    "action_desc"  => config('user.job_account')['return']['name'],
                    "created_at"   => time(),
                    "updated_at"   => time()
                ];

                $jobAccountReturn = $cardCallBackObj->insertJobAccount($jobAccountParams);

                if ($jobAccountReturn == false){
                    return $this->com_return(false,config("params.ABNORMAL_ACTION")." - 012");
                }
                /*佣金明细 off*/
            }
            /*推荐人操作部分 off*/


            /*更新订单状态  on*/
            $billPayAssistParams = [
                "sale_status" => config("bill_assist.bill_status")['8']['key'],
                "check_user"  => $action_user,
                "check_time"  => time(),
                "check_reason"=> $check_reason,
                "updated_at"  => time()
            ];

            $billPayStatusReturn = $billPayAssistModel
                ->where("pid",$pid)
                ->update($billPayAssistParams);

            if ($billPayStatusReturn == false){
                return $this->com_return(false,config("params.ABNORMAL_ACTION")." - 013");
            }
            /*更新订单状态  off*/

            Db::commit();
            return $this->com_return(true,config("params.SUCCESS"));
        }catch (Exception $e){
            Db::rollback();
            return $this->com_return(false,$e->getMessage());
        }
    }
}