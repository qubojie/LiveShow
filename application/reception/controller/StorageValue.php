<?php
/**
 * 储值操作.
 * User: qubojie
 * Date: 2018/9/19
 * Time: 上午10:44
 */
namespace app\reception\controller;

use app\admin\model\User;
use app\common\controller\UUIDUntil;
use app\wechat\controller\CardCallback;
use app\wechat\model\BillRefill;
use think\Db;
use think\Exception;
use think\Request;
use think\Validate;

class StorageValue extends CommonAction
{
    /**
     * 储值列表
     * @param Request $request
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function index(Request $request)
    {
        $dateTime = $request->param("dateTime","");//时间
        $payType  = $request->param("payType","");//付款方式

        $rule = [
            "dateTime|时间"    => "require",
        ];

        $request_res = [
            "dateTime"    => $dateTime,
        ];

        $validate = new Validate($rule);

        if (!$validate->check($request_res)){
            return $this->com_return(false,$validate->getError());
        }

        $nowDateTime = strtotime(date("Ymd"));

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

        $date_where['br.created_at'] = ["between time",["$beginTime","$endTime"]];

        $pay_type_where = [];
        if (!empty($payType)){
            if ($payType == "all"){
                $pay_type_where = [];
            }else{
                $pay_type_where["pay_type"] = ["eq",$payType];
            }
        }

        $billRefillModel = new BillRefill();

        $column = $billRefillModel->admin_column;

        foreach ($column as $key => $val){
            $column[$key] = "br.".$val;
        }

        $list = $billRefillModel
            ->alias("br")
            ->join("user u","u.uid = br.uid","LEFT")
            ->where("br.status",config("order.recharge_status")['completed']['key'])
            ->where($date_where)
            ->where($pay_type_where)
            ->field("u.name,u.phone")
            ->field($column)
            ->order("br.created_at DESC")
            ->select();

        $list = json_decode(json_encode($list),true);

        /*现金储值统计 on*/
        $cash_sum = $billRefillModel
            ->alias("br")
            ->where("br.status",config("order.recharge_status")['completed']['key'])
            ->where($date_where)
            ->where($pay_type_where)
            ->sum("br.amount");

        $res['cash_sum'] = $cash_sum;
        /*现金储值统计 off*/

        /*礼金储值统计 on*/
        $cash_gift_sum = $billRefillModel
            ->alias("br")
            ->where("br.status",config("order.recharge_status")['completed']['key'])
            ->where($date_where)
            ->where($pay_type_where)
            ->sum("br.cash_gift");

        $res['cash_gift_sum'] = $cash_gift_sum;

        /*礼金储值统计 off*/

        $res["data"] = $list;

        return $this->com_return(true,config("params.SUCCESS"),$res);
    }

    /**
     * 确认充值
     * @param Request $request
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function rechargeConfirm(Request $request)
    {
        $phone = $request->param("phone","");//用户电话

        $recharge_amount = $request->param("recharge_amount","");//储值金额
        $cash_amount     = $request->param("cash_amount","");//赠送礼金
        $review_desc     = $request->param("review_desc","");//备注
        $pay_type        = $request->param("pay_type","");//支付方式

        $rule = [
            "phone|电话"              => "require|regex:1[3-8]{1}[0-9]{9}",
            "recharge_amount|储值金额" => "require|number|gt:0",
            "cash_amount|赠送礼金"     => "require|number|egt:0",
            "pay_type|支付方式"        => "require",
        ];

        $request_res = [
            "phone"           => $phone,
            "recharge_amount" => $recharge_amount,
            "cash_amount"     => $cash_amount,
            "pay_type"        => $pay_type,
        ];

        $validate = new Validate($rule);

        if (!$validate->check($request_res)){
            return $this->com_return(false,$validate->getError());
        }

        $token = $request->header("Token");

        $manageInfo = $this->receptionTokenGetManageInfo($token);

        $review_user = $manageInfo['sales_name'];

        //获取用户信息
        $userModel = new User();

        $userInfo = $userModel
            ->alias("u")
            ->where("u.phone",$phone)
            ->field("u.uid,u.account_balance,u.account_cash_gift,u.referrer_type,u.referrer_id")
            ->find();

        $userInfo = json_decode(json_encode($userInfo),true);

        $cardCallBackObj = new CardCallback();
        $UUID = new UUIDUntil();
        $rfid = $UUID->generateReadableUUID("RF");

        Db::startTrans();
        try{
            if (!empty($userInfo)){
                $uid                        = $userInfo['uid'];
                $referrer_type              = $userInfo['referrer_type'];//推荐类型
                $referrer_id                = $userInfo['referrer_id'];//推荐人 id
                $account_balance            = $userInfo['account_balance'];//账户储值余额
                $account_cash_gift          = $userInfo['account_cash_gift'];//账户礼金余额
                $user_new_account_balance   = $account_balance + $recharge_amount;//用户新的储值金额
                $user_new_account_cash_gift = $account_cash_gift + $cash_amount;//用户新的礼金余额

                $last_cash_gift = $user_new_account_cash_gift;
                $last_account_balance = $user_new_account_balance;

                $updatedUserParams = [
                    "account_balance"   => $user_new_account_balance,
                    "account_cash_gift" => $user_new_account_cash_gift,
                    "updated_at"        => time()
                ];

                $updateUserReturn = $userModel
                    ->where("uid",$uid)
                    ->update($updatedUserParams);

                if ($updateUserReturn == false){
                    return $this->com_return(false,config("params.CREATED_NEW_USER_FAIL")." - 001");
                }

                if ($referrer_type == config("salesman.salesman_type")['2']['key']){
                    //如果是用户推荐

                    $publicActionObj = new \app\reception\controller\PublicAction();

                    $returnMoneyRes = $publicActionObj->uidGetCardReturnMoney("$uid");

                    $consumption_money = $recharge_amount;

                    if (!empty($returnMoneyRes)){
                        $refill_job_cash_gift      = $returnMoneyRes['refill_job_cash_gift'];     //充值推荐人返礼金
                        $refill_job_commission     = $returnMoneyRes['refill_job_commission'];    //充值推荐人返佣金

                        $consumptionReturnMoney = $publicActionObj->rechargeReturnMoney("$uid","$referrer_type","$consumption_money","$refill_job_cash_gift","$refill_job_commission");

                        $job_cash_gift_return_money  = $consumptionReturnMoney['job_cash_gift_return_money'];//返还推荐人礼金
                        $job_commission_return_money = $consumptionReturnMoney['job_commission_return_money'];//返给推荐人佣金

                    }else{

                        $job_cash_gift_return_money  = 0;//返还推荐人礼金
                        $job_commission_return_money = 0;//返给推荐人佣金

                    }

                    if ($job_cash_gift_return_money > 0){
                        //返还推荐人礼金
                        //获取推荐人账户信息
                        $referrerInfo  =$userModel
                            ->where("uid",$referrer_id)
                            ->field("account_balance,account_cash_gift")
                            ->find();

                        $referrerInfo = json_decode(json_encode($referrerInfo),true);

                        if (!empty($referrerInfo)){
                            //如果推荐人存在
                            $referrer_cash_gift = $referrerInfo['account_cash_gift'];

                            $referrer_new_cash_gift = $referrer_cash_gift + $job_cash_gift_return_money;

                            $updateReferrerParams = [
                                "account_cash_gift" => $referrer_new_cash_gift,
                                "updated_at"        => time()
                            ];

                            $updateReferrerReturn = $userModel
                                ->where("uid",$referrer_id)
                                ->update($updateReferrerParams);
                            //更新推荐人礼金账户信息
                            if ($updateReferrerReturn == false){
                                return $this->com_return(false,config("params.CREATED_NEW_USER_FAIL")." - 003");
                            }

                            /*推荐人礼金明细 on*/
                            $referrerUserDParams = [
                                'uid'            => $referrer_id,
                                'cash_gift'      => $job_cash_gift_return_money,
                                'last_cash_gift' => $referrer_new_cash_gift,
                                'change_type'    => '2',
                                'action_user'    => "sys",
                                'action_type'    => config('user.gift_cash')['recharge_give']['key'],
                                'action_desc'    => config('user.gift_cash')['recharge_give']['name'],
                                'oid'            => $rfid,
                                'created_at'     => time(),
                                'updated_at'     => time()
                            ];

                            //给推荐用户添加礼金明细
                            $userAccountCashGiftReturn = $cardCallBackObj->updateUserAccountCashGift($referrerUserDParams);

                            if ($userAccountCashGiftReturn == false){
                                return $this->com_return(false,config("params.CREATED_NEW_USER_FAIL")." - 004");
                            }
                        }
                    }

                    if ($job_commission_return_money > 0){
                        //返还推荐人佣金

                        //返给推荐人佣金
                        $referrerUserJobInfo = Db::name("job_user")
                            ->where("uid",$referrer_id)
                            ->find();

                        $referrerUserJobInfo = json_decode(json_encode($referrerUserJobInfo),true);

                        if (empty($referrerUserJobInfo)){
                            //新增
                            $newJobParams = [
                                "uid"         => $referrer_id,
                                "job_balance" => $job_commission_return_money,
                                "created_at"  => time(),
                                "updated_at"  => time()
                            ];

                            $jobUserInsert = Db::name("job_user")
                                ->insert($newJobParams);

                            if ($jobUserInsert == false){
                                return $this->com_return(false,config("params.CREATED_NEW_USER_FAIL")." - 006");
                            }

                            $referrer_last_balance = $job_commission_return_money;

                        }else{

                            $referrer_new_job_balance = $referrerUserJobInfo['job_balance'] + $job_commission_return_money;

                            //更新
                            $newJobParams = [
                                "job_balance" => $referrer_new_job_balance,
                                "updated_at"  => time()
                            ];

                            $jobUserUpdate = Db::name("job_user")
                                ->where("uid",$referrer_id)
                                ->update($newJobParams);

                            if ($jobUserUpdate == false){
                                return $this->com_return(false,config("params.CREATED_NEW_USER_FAIL")." - 007");
                            }

                            $referrer_last_balance = $referrer_new_job_balance;
                        }

                        /*佣金明细 on*/

                        //添加推荐用户佣金明细表
                        $jobAccountParams = [
                            "uid"          => $referrer_id,
                            "balance"      => $job_commission_return_money,
                            "last_balance" => $referrer_last_balance,
                            "change_type"  => 2,
                            "action_user"  => 'sys',
                            "action_type"  => config('user.job_account')['recharge']['key'],
                            "oid"          => $rfid,
                            "deal_amount"  => $consumption_money,
                            "action_desc"  => config('user.job_account')['recharge']['name'],
                            "created_at"   => time(),
                            "updated_at"   => time()
                        ];

                        $jobAccountReturn = $cardCallBackObj->insertJobAccount($jobAccountParams);

                        if ($jobAccountReturn == false){
                            return $this->com_return(false,config("params.CREATED_NEW_USER_FAIL")." - 008");
                        }
                        /*佣金明细 off*/
                    }
                }

            }else{
                return $this->com_return(false,config("params.USER")['USER_NOT_EXIST']);
                /*//用户不存在,插入新用户
                $UUID     = new UUIDUntil();
                $uid      = $UUID->generateReadableUUID("U");
                $password = sha1(config("DEFAULT_PASSWORD"));
                $newUserParams = [
                    "uid"               => $uid,
                    "phone"             => $phone,
                    "password"          => $password,
                    "account_balance"   => $recharge_amount,
                    "account_cash_gift" => $cash_amount,
                    "register_way"      => config("user.register_way")['web']['key'],
                    "user_status"       => config("user.user_status")['0']['key'],
                    "info_status"       => config("user.user_info")['empty_info']['key'],
                    "created_at"        => time(),
                    "updated_at"        => time()
                ];

                $insertNewUserReturn = $userModel
                    ->insert($newUserParams);

                if ($insertNewUserReturn == false){
                    return $this->com_return(false,config("params.CREATED_NEW_USER_FAIL")." - 002");
                }

                $last_cash_gift       = $cash_amount;
                $last_account_balance = $recharge_amount;*/
            }

            /*更新用户储值账户明细 on*/
            //余额明细参数
            $insertUserAccountParams = [
                "uid"          => $uid,
                "balance"      => $recharge_amount,
                "last_balance" => $last_account_balance,
                "change_type"  => 1,
                "action_user"  => $review_user,
                "action_type"  => config('user.account')['recharge']['key'],
                "oid"          => $rfid,
                "deal_amount"  => $recharge_amount,
                "action_desc"  => config("user.account")['recharge']['name'],
                "created_at"   => time(),
                "updated_at"   => time()
            ];

            //插入用户充值明细
            $insertUserAccountReturn = $cardCallBackObj->updateUserAccount($insertUserAccountParams);

            if ($insertUserAccountReturn == false){
                return $this->com_return(false,config("params.CREATED_NEW_USER_FAIL")." - 009");
            }
            /*更新用户储值账户明细 off*/


            /*更新用户礼金账户明细 on*/
            if ($cash_amount > 0){
                //如果礼金数额大于0 则插入用户礼金明细

                //变动后的礼金总余额
                $userAccountCashGiftParams = [
                    'uid'            => $uid,
                    'cash_gift'      => $cash_amount,
                    'last_cash_gift' => $last_cash_gift,
                    'change_type'    => 1,
                    'action_user'    => $review_user,
                    'action_type'    => config('user.gift_cash')['recharge_give']['key'],
                    'action_desc'    => config('user.gift_cash')['recharge_give']['name'],
                    'oid'            => $rfid,
                    'created_at'     => time(),
                    'updated_at'     => time()
                ];

                //给用户添加礼金明细
                $userAccountCashGiftReturn = $cardCallBackObj->updateUserAccountCashGift($userAccountCashGiftParams);

                if ($userAccountCashGiftReturn == false){
                    return $this->com_return(false,config("params.CREATED_NEW_USER_FAIL")." - 006");
                }

            }
            /*更新用户礼金账户 off*/

            /*插入新的充值信息 on*/
            //插入用户充值单据表
            $billRefillParams = [
                "rfid"          => $rfid,
                "referrer_type" => config("salesman.salesman_type")['3']['name'],
                "referrer_id"   => config("salesman.salesman_type")['3']['key'],
                "uid"           => $uid,
                "pay_type"      => $pay_type,
                "pay_time"      => time(),
                "amount"        => $recharge_amount,
                "cash_gift"     => $cash_amount,
                "status"        => config("order.recharge_status")['completed']['key'],
                "review_time"   => time(),
                "review_user"   => $review_user,
                "review_desc"   => $review_desc,
                "created_at"    => time(),
                "updated_at"    => time()
            ];

            $billRefillModel = new BillRefill();

            $billRefillReturn = $billRefillModel
                ->insert($billRefillParams);

            if ($billRefillReturn == false){
                return $this->com_return(false,config("params.CREATED_NEW_USER_FAIL")." - 005");
            }
            /*插入新的充值信息 off*/

            //记录充值日志
            $action = config("useraction.recharge")['key'];
            $reason = config("useraction.recharge")['name'];
            addSysAdminLog("$uid","","$rfid","$action","$reason","$review_user",time());
            Db::commit();
            return $this->com_return(true,config("params.SUCCESS"));
        }catch (Exception $e){
            Db::rollback();
            return $this->com_return(false,$e->getMessage());
        }

    }
}