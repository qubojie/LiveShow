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
use think\Db;
use think\Exception;
use think\Request;
use think\Validate;

class BillPayAssistInfo extends CommonAction
{
    /**
     * 消息列表
     * @param Request $request
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function index(Request $request)
    {
        $keyword = $request->param("keyword","");

        $billPayAssistModel = new BillPayAssist();

        $r_column = $billPayAssistModel->r_column;

        foreach ($r_column as $key => $val){
            $r_column[$key] = "bpa.".$val;
        }

        $where = [];
        if (!empty($keyword)){
            $where['bpa.phone|bpa.verification_code'] = ['like',"%$keyword%"];
        }

        $list = $billPayAssistModel
            ->alias("bpa")
            ->join("user u","u.uid = bpa.uid","LEFT")
            ->join("user_gift_voucher ugv","ugv.gift_vou_code = bpa.gift_vou_code","LEFT")
            ->where($where)
            ->where("bpa.sale_status",config("bill_assist.bill_status")['0']['key'])
            ->order("bpa.created_at DESC")
            ->field($r_column)
            ->field("u.name,u.account_balance,u.account_cash_gift")
            ->field("ugv.gift_vou_id,ugv.gift_vou_type,ugv.gift_vou_name,ugv.gift_vou_desc")
            ->select();

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
        $action          = $request->param("action","");//1:确认; 2:取消
        $pid             = $request->param("pid","");//订单id
        $token           = $request->header("Token");

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

        $manageInfo = $this->tokenGetManageInfo($token);

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

        $manageInfo        = $this->tokenGetManageInfo($token);

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

            $consumption_money = $balance_money + $cash_money;

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

            $return_point = intval($consumption_money * ($returnUserPoint/100));//获取返还用户积分数

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

                if ($userAccountPointReturn == false){
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

            $updateUserInfo = Db::name("user")
                ->where("uid",$uid)
                ->update($userParams);

            if ($updateUserInfo == false){
                return $this->com_return(false,config("params.ABNORMAL_ACTION"). "- 012");
            }

            /*更新用户信息 Off*/

            /*订单状态变更 on*/
            $billPayAssistParams = [
                "sale_status"       => config("bill_assist.bill_status")['1']['key'],
                "pay_time"          => time(),
                "check_user"        => $action_user,
                "check_time"        => time(),
                "check_reason"      => "确认消费",
                "account_balance"   => $balance_money,
                "account_cash_gift" => $cash_gift_money,
                "cash"              => $cash_money,
                "return_point"      => $return_point,//返还用户积分
                "return_cash_gift"  => $return_cash_gift,//返给推荐人的礼金
                "return_commission" => $return_commission,//返佣金
                "updated_at"        => time()
            ];

            $is_ok = Db::name("bill_pay_assist")
                ->where("pid",$pid)
                ->update($billPayAssistParams);

            if ($is_ok == false){
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
            'action_type'    => config('user.gift_cash')['consumption_give']['key'],
            'action_desc'    => config('user.gift_cash')['consumption_give']['name'],
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

        $manageInfo = $this->tokenGetManageInfo($token);

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
}