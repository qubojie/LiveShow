<?php
/**
 * Created by PhpStorm.
 * User: qubojie
 * Date: 2018/9/19
 * Time: 下午4:03
 */
namespace app\reception\controller;

use app\admin\controller\CommandAction;
use app\admin\model\ManageSalesman;
use app\admin\model\MstCardVip;
use app\admin\model\User;
use app\common\controller\UUIDUntil;
use app\wechat\controller\CardCallback;
use app\wechat\controller\WechatPay;
use app\wechat\model\BillCardFees;
use app\wechat\model\UserCard;
use think\Db;
use think\Exception;
use think\Request;
use think\Validate;

class OpenCard extends CommonAction
{
    /**
     * 获取所有的有效卡种
     * @param Request $request
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getAllCardInfo(Request $request)
    {
        $cardModel = new MstCardVip();
        $cardInfo = $cardModel
            ->where('is_enable',1)
            ->where('is_delete',0)
            ->order('sort')
            ->field('card_id,card_name,card_amount')
            ->select();
        $list = json_decode(json_encode($cardInfo),true);

        $cardInfo = [];

        foreach ($list as $key => $val){
            foreach ($val as $k => $v){

                if ($k == "card_id"){
                    $k = "key";
                }else{
                    $k = "name";
                }

                $cardInfo[$key][$k] = $v;
            }

        }

        return $this->com_return(true,config("params.SUCCESS"),$list);
    }

    /**
     * 开卡订单列表
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

        $date_where['bcf.created_at'] = ["between time",["$beginTime","$endTime"]];

        $pay_type_where = [];
        if (!empty($payType)){
            if ($payType == "all"){
                $pay_type_where = [];
            }else{
                $pay_type_where["pay_type"] = ["eq",$payType];
            }
        }

        $billCardFeesModel = new BillCardFees();

        $completed       = config("order.open_card_status")['completed']['key'];
        $pending_ship    = config("order.open_card_status")['pending_ship']['key'];
        $pending_receipt = config("order.open_card_status")['pending_receipt']['key'];

        $sale_status_str = "$completed,$pending_ship,$pending_receipt";

        $list = $billCardFeesModel
            ->alias("bcf")
            ->join("bill_card_fees_detail bcfd","bcfd.vid = bcf.vid")
            ->join("mst_card_vip mcv","mcv.card_id = bcfd.card_id")
            ->join("user u","u.uid = bcf.uid")
            ->where("bcf.sale_status","IN",$sale_status_str)
            ->where($date_where)
            ->where($pay_type_where)
            ->order("bcf.created_at DESC")
            ->field("u.name,u.phone")
            ->field("mcv.card_name,mcv.card_type")
            ->field("bcf.created_at,bcf.pay_type,bcf.order_amount,bcf.deal_price,bcf.review_user")
            ->select();

        $list = json_decode(json_encode($list),true);

        $money_sum = $billCardFeesModel
            ->alias("bcf")
            ->where("bcf.sale_status","IN",$sale_status_str)
            ->where($date_where)
            ->where($pay_type_where)
            ->sum("bcf.deal_price");

        $res['money_sum'] = $money_sum;

        $res["data"] = $list;

        return $this->com_return(true,config("params.SUCCESS"),$res);
    }


    /**
     * 确认开卡
     * @param Request $request
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function confirmOpenCard(Request $request)
    {
        $referrer_phone = $request->param("referrer_phone","");//推荐人电话
        $user_phone     = $request->param("user_phone","");//用户电话
        $user_name      = $request->param("user_name","");//用户姓名
        $user_sex       = $request->param("user_sex","");//用户性别
        $card_id        = $request->param("card_id","");//卡id
        $review_desc    = $request->param("review_desc","");//备注
        $pay_type       = $request->param("pay_type","");//支付方式

        $rule = [
            "user_phone|客户电话"    => "require|regex:1[3-8]{1}[0-9]{9}",
            "user_name|客户姓名"     => "require",
            "card_id|卡种"           => "require",
            "pay_type|支付方式"         => "require",
        ];

        $request_res = [
            "user_phone" => $user_phone,
            "user_name"  => $user_name,
            "card_id"    => $card_id,
            "pay_type"    => $pay_type,
        ];

        $validate = new Validate($rule);

        if (!$validate->check($request_res)){
            return $this->com_return(false,$validate->getError());
        }

        $token = $request->header("Token");

        $manageInfo = $this->receptionTokenGetManageInfo($token);

        $review_user = $manageInfo["sales_name"];

        $userModel = new User();
        Db::startTrans();
        try{
            /*处理推荐人信息 On*/
            if ($referrer_phone == "8888"){
                $referrer_type = config("salesman.salesman_type")['3']['name'];
                $referrer_id = config("salesman.salesman_type")['3']['key'];
            }else{
                //查询是否是营销
                $vip_type    = config("salesman.salesman_type")['0']['key'];
                $sales_type  = config("salesman.salesman_type")['1']['key'];
                $boss_type   = config("salesman.salesman_type")['4']['key'];

                $stype_key_str = "$vip_type,$sales_type,$boss_type";
                $manageModel = new ManageSalesman();
                $referrerInfo = $manageModel
                    ->alias("ms")
                    ->join("mst_salesman_type mst","mst.stype_id = ms.stype_id")
                    ->where("ms.phone",$referrer_phone)
                    ->where("mst.stype_key","IN",$stype_key_str)
                    ->field("ms.sid,mst.stype_key")
                    ->find();

                $referrerInfo = json_decode(json_encode($referrerInfo),true);

                if (!empty($referrerInfo)){
                    //内部人员推荐
                    $referrer_id = $referrerInfo['sid'];
                    $referrer_type = $referrerInfo['stype_key'];
                }else{
                    //查询是否是用户推荐
                    $userReferrerInfo = $userModel
                        ->where("phone",$referrer_phone)
                        ->field("uid")
                        ->find();

                    $userReferrerInfo = json_decode(json_encode($userReferrerInfo),true);

                    if (!empty($userReferrerInfo)){
                        //用户推荐
                        $referrer_id   = $userReferrerInfo["uid"];
                        $referrer_type = config("salesman.salesman_type")['2']['key'];
                    }else{
                        //推荐人不存在,返回false
                        return $this->com_return(false,config("params.SALESMAN_NOT_EXIST"));
                    }
                }

            }
            /*处理推荐人信息 Off*/

            //查看会员信息
            $userInfo = $userModel
                ->where("phone",$user_phone)
                ->field("uid,user_status,referrer_type,referrer_id")
                ->find();

            $userInfo = json_decode(json_encode($userInfo),true);

            $UUID = new UUIDUntil();
            if (!empty($userInfo)){
                $uid         = $userInfo["uid"];
                $user_status = $userInfo['user_status'];

                if ($user_status == config("user.user_register_status")['open_card']['key']){
                    //用户已开卡,请勿重复开卡
                    return $this->com_return(false,config("params.USER")['USER_OPENED_CARD']);
                }

                //更新用户推荐人信息
                $updateUserReferrerParams = [
                    "referrer_type" => $referrer_type,
                    "referrer_id"   => $referrer_id,
                    "updated_at"    => time()
                ];
                $updateUserReferrerReturn = $userModel
                    ->where("uid",$uid)
                    ->update($updateUserReferrerParams);

                if ($updateUserReferrerReturn == false){
                    return $this->com_return(false,config("params.ABNORMAL_ACTION")." - 002");
                }
            }else{
                //新用户,首先注册用户信息
                $uid = $UUID->generateReadableUUID("U");
                $newUserParams = [
                    "uid"           => $uid,
                    "phone"         => $user_phone,
                    "password"      => sha1(config("DEFAULT_PASSWORD")),
                    "name"          => $user_name,
                    "sex"           => $user_sex,
                    "register_way"  => config("user.register_way")['web']['key'],
                    "user_status"   => config("user.user_status")['0']['key'],
                    "info_status"   => config("user.user_info")['interest']['key'],
                    "referrer_type" => $referrer_type,
                    "referrer_id"   => $referrer_id,
                    "created_at"    => time(),
                    "updated_at"    => time()
                ];

                $insertNewUserReturn = $userModel
                    ->insert($newUserParams);

                if ($insertNewUserReturn == false){
                    return $this->com_return(false,config("params.ABNORMAL_ACTION")." - 001");
                }
            }

            /*开卡 on*/
            $this->createdOpenCardOrder($card_id,$uid,$referrer_type,$referrer_id,$review_user,$review_desc,$pay_type);
            Db::commit();
            return $this->com_return(true,config("params.SUCCESS"));
            /*开卡 off*/

        }catch (Exception $e){
            Db::rollback();
            return $this->com_return(false,$e->getMessage());
        }
    }

    /**
     * 开卡
     * @param $card_id
     * @param $uid
     * @param $referrer_type
     * @param $referrer_id
     * @param $review_user
     * @param $review_desc
     * @param $pay_type
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    protected function createdOpenCardOrder($card_id,$uid,$referrer_type,$referrer_id,$review_user,$review_desc,$pay_type)
    {
        $card_info  = $this->getCardInfo($card_id);

        if (empty($card_info)){
            return $this->com_return(false,config("params.USER")['CARD_VALID_NO']);
        }

        $cardInfo_amount  = $card_info["card_amount"];//获取卡的基本金额

        $UUIDObj = new UUIDUntil();

        $vid = $UUIDObj->generateReadableUUID("V");//充值缴费单 V前缀

        //获取自动取消分钟数
        $cardAutoCancelMinutes = getSysSetting("card_auto_cancel_time");
        //将分钟数转换为秒
        $cardAutoCancelTime = $cardAutoCancelMinutes * 60;

        $commission_ratio = 0;

        $discount = 0;//折扣金额
        //③生成缴费订单,创建发货订单
        $billCardFeesParams = [
            'vid'             => $vid,
            'uid'             => $uid,//用户id
            'referrer_type'   => $referrer_type,//推荐人类型
            'referrer_id'     => $referrer_id,//推荐人id
            'sale_status'     => config("order.open_card_status")['completed']['key'],//单据状态
            'deal_time'       => time(),//成交时间
            'pay_time'        => time(),//付款时间
            'finish_time'     => time(),//完成时间
            'auto_cancel_time'=> time()+$cardAutoCancelTime,//单据自动取消的时间
            'order_amount'    => $cardInfo_amount,//订单金额
            'discount'        => $discount,//折扣,暂且为0
            'payable_amount'  => 0,//线上应付且未付金额
            'deal_price'      => $cardInfo_amount - $discount,
            'pay_type'        => $pay_type,
            'is_settlement'   => $referrer_type == "empty" ? 1 : 0 ,//是否结算佣金
            'commission_ratio'=> $commission_ratio,//下单时的佣金比例   百分比整数     没有推荐人的自动为0
            'commission'      => ($cardInfo_amount - $discount) * $commission_ratio / 100,
            'review_time'     => time(),
            'review_user'     => $review_user,
            'review_desc'     => $review_desc,
            'created_at'      => time(),//创建时间
            'updated_at'      => time(),//更新时间
        ];

        $cardCallbackObj = new CardCallback();

        //返回订单id
        $billCardFeesReturn = $cardCallbackObj->billCardFees($billCardFeesParams);

        if ($billCardFeesReturn == false){
            return $this->com_return(false,config("params.ORDER")['CREATE_CARD_ORDER_FAIL']);
        }

        //获取开卡赠送礼金数(百分比)
        $card_cash_gift     = $card_info['card_cash_gift'];
        //获取开卡赠送积分
        $card_point         = $card_info['card_point'];
        //获取开卡赠送推荐用户礼金(百分比)
        $card_job_cash_gif  = $card_info['card_job_cash_gif'];
        //获取开卡赠送推荐用户佣金(百分比)
        $card_job_commission= $card_info['card_job_commission'];

        /*创建 bill_card_fees_details On*/
        $billCardFeesDetailParams = [
            'vid'           => $billCardFeesReturn,
            'card_id'       => $card_id,
            'card_type'     => $card_info["card_type"],//卡片类型   ‘vip’会籍卡      ‘value’ 储值卡
            'card_name'     => $card_info["card_name"],//VIP卡名称
            'card_level'    => $card_info["card_level"],//vip卡级别名称
            'card_image'    => $card_info["card_image"],//VIP卡背景图
            'card_no_prefix'=> $card_info["card_no_prefix"],//卡号前缀（两位数字）
            'card_desc'     => $card_info["card_desc"],//VIP卡使用说明及其他描述
            'card_equities' => $card_info["card_equities"],//卡片享受权益详情
            'card_deposit'  => $card_info["card_deposit"],//卡片权益保证金额

            'card_amount'         => $cardInfo_amount,//充值金额
            'card_point'          => $card_point,//开卡赠送积分
            'card_cash_gift'      => $card_cash_gift,//开卡赠送礼金数
            'card_job_cash_gif'   => $card_job_cash_gif,//推荐人返佣礼金
            'card_job_commission' => $card_job_commission,//推荐人返佣金
        ];

        $billCardFeesDetailReturn = $cardCallbackObj->billCardFeesDetail($billCardFeesDetailParams);

        if ($billCardFeesDetailReturn == false){
            return $this->com_return(false,config("params.ORDER")['CREATE_CARD_ORDER_FAIL']);
        }
        /*创建 bill_card_fees_details Off*/

        /*给用户写入开卡信息 on*/
        $userCardModel = new UserCard();

        $userCardParams = [
            "uid"          => $uid,
            "card_no"      => $UUIDObj->generateReadableUUID($card_info["card_no_prefix"]),
            "card_id"      => $card_id,
            "card_type"    => $card_info['card_type'],
            "card_name"    => $card_info['card_name'],
            "card_image"   => $card_info['card_image'],
            "card_o_amount"=> $card_info['card_amount'],
            "card_amount"  => $cardInfo_amount,
            "card_deposit" => $card_info['card_deposit'],
            "card_desc"    => $card_info['card_desc'],
            "card_equities"=> $card_info['card_equities'],
            "is_valid"     => 1,
            "valid_time"   => 0,
            "created_at"   => time(),
            "updated_at"   => time()
        ];

        $userCardInfoReturn = $cardCallbackObj->updateCardInfo($userCardParams);
        /*给用户写入开卡信息 off*/

        $userOldMoneyInfo = Db::name('user')
            ->where('uid',$uid)
            ->field('account_balance,account_deposit,account_cash_gift,account_point')
            ->find();

        //用户钱包可用余额
        $account_balance = $userOldMoneyInfo['account_balance'];

        //用户钱包押金余额
        $account_deposit = $userOldMoneyInfo['account_deposit'];

        //用户礼金余额
        $account_cash_gift = $userOldMoneyInfo['account_cash_gift'];

        //用户积分可用余额
        $account_point = $userOldMoneyInfo['account_point'];

        $userInfoObj = new \app\wechat\controller\UserInfo();

        if ($referrer_type == 'user'){
            //如果推荐人是用户,给推荐人用户更新礼金信息

            //账户可用礼金变动  正加 负减  直接取整,舍弃小数

            $cash_gift = intval(($card_job_cash_gif / 100) * $cardInfo_amount);
            if ($cash_gift > 0){
                //如果赠送礼金大于0
                //首先获取推荐人的礼金余额
                $referrer_user_gift_cash_old = $userInfoObj->getUserFieldValue("$referrer_id","account_cash_gift");

                //变动后的礼金总余额
                $last_cash_gift = $cash_gift + $referrer_user_gift_cash_old;

                $userAccountCashGiftParams = [
                    'uid'            => $referrer_id,
                    'cash_gift'      => $cash_gift,
                    'last_cash_gift' => $last_cash_gift,
                    'change_type'    => '2',
                    'action_user'    => 'sys',
                    'action_type'    => config('user.gift_cash')['recommend_reward']['key'],
                    'action_desc'    => config('user.gift_cash')['recommend_reward']['name'],
                    'oid'            => $vid,
                    'created_at'     => time(),
                    'updated_at'     => time()
                ];

                //给推荐用户添加礼金明细
                $userAccountCashGiftReturn = $cardCallbackObj->updateUserAccountCashGift($userAccountCashGiftParams);

                if ($userAccountCashGiftReturn == false){
                    return $this->com_return(false,config("params.ABNORMAL_ACTION")." - 001");
                }

                //给推荐用户添加礼金余额
                $updatedAccountCashGiftReturn = $userInfoObj->updatedAccountCashGift("$referrer_id","$cash_gift","inc");

                if ($updatedAccountCashGiftReturn == false){
                    return $this->com_return(false,config("params.ABNORMAL_ACTION")." - 002");
                }
            }

            /*给推荐用户添加佣金*/
            if ($card_job_commission > 0){
                //首先获取推荐人的佣金余额
                $old_last_balance_res = Db::name("job_user")
                    ->where('uid',$referrer_id)
                    ->field('job_balance')
                    ->find();
                $old_last_balance_res = json_decode(json_encode($old_last_balance_res),true);

                if (!empty($old_last_balance_res)){
                    $job_balance = $old_last_balance_res['job_balance'];
                }else{
                    $job_balance = 0;
                }
                $plus_card_job_commission = intval(($card_job_commission / 100) * $cardInfo_amount);

                //添加或更新推荐用户佣金表
                $jobUserReturn = $cardCallbackObj->updateJobUser($referrer_id,$plus_card_job_commission);

                if ($jobUserReturn == false){
                    return $this->com_return(false,config("params.ABNORMAL_ACTION")." - 003");
                }

                //添加推荐用户佣金明细表
                $jobAccountParams = [
                    "uid"          => $referrer_id,
                    "balance"      => $plus_card_job_commission,
                    "last_balance" => $job_balance + $plus_card_job_commission,
                    "change_type"  => 2,
                    "action_user"  => 'sys',
                    "action_type"  => config('user.job_account')['recommend_reward']['key'],
                    "oid"          => $vid,
                    "deal_amount"  => $cardInfo_amount,
                    "action_desc"  => config('user.job_account')['recommend_reward']['name'],
                    "created_at"   => time(),
                    "updated_at"   => time()
                ];

                $jobAccountReturn = $cardCallbackObj->insertJobAccount($jobAccountParams);

                if ($jobAccountReturn == false){
                    return $this->com_return(false,config("params.ABNORMAL_ACTION")." - 004");
                }
            }
        }

        //获取当前用户旧的礼金余额
        $user_gift_cash_old = $userInfoObj->getUserFieldValue("$uid","account_cash_gift");

        if ($card_cash_gift > 0){
            //如果赠送开卡用户礼金大于0
            $card_cash_gift_money = intval(($card_cash_gift / 100) * $cardInfo_amount);
            $user_gift_cash_new   = $user_gift_cash_old + $card_cash_gift_money;

            $updatedOpenCardCashGiftReturn = $userInfoObj->updatedAccountCashGift("$uid","$card_cash_gift_money","inc");

            if ($updatedOpenCardCashGiftReturn == false){
                return $this->com_return(false,config("params.ABNORMAL_ACTION")." - 005");
            }

            //更新用户礼金明细
            $updatedUserCashGiftParams = [
                'uid'            => $uid,
                'cash_gift'      => $card_cash_gift_money,
                'last_cash_gift' => $user_gift_cash_new,
                'change_type'    => '2',
                'action_user'    => 'sys',
                'action_type'    => config("user.gift_cash")['open_card_reward']['key'],
                'action_desc'    => config("user.gift_cash")['open_card_reward']['name'],
                'oid'            => $vid,
                'created_at'     => time(),
                'updated_at'     => time()
            ];

            //增加开卡用户礼金明细
            $openCardUserAccountCashGiftReturn = $cardCallbackObj->updateUserAccountCashGift($updatedUserCashGiftParams);

            if ($openCardUserAccountCashGiftReturn == false){
                return $this->com_return(false,config("params.ABNORMAL_ACTION")." - 006");
            }
        }

        $card_type = $card_info['card_type'];

        if ($card_type == config("card.type")['0']['key']){
            //押金卡
            //更新用户押金账户以及押金明细 vip
            $userCardParams = [
                "uid"             => $uid,
                "account_deposit" => $cardInfo_amount + $account_deposit,
                "user_status"     => config("user.user_status")['2']['key'],
                "updated_at"      => time()
            ];

            $userUpdateReturn = $cardCallbackObj->updateUserInfo($userCardParams,$uid);

            if ($userUpdateReturn == false){
                return $this->com_return(false,config("params.ABNORMAL_ACTION")." - 008");
            }

            //用户押金明细参数
            $userDepositParams = [
                "uid"           => $uid,
                "deposit"       => $cardInfo_amount,
                "last_deposit"  => $cardInfo_amount + $account_deposit,
                "change_type"   => '2',
                "action_user"   => 'sys',
                "action_type"   => config('user.deposit')['pay']['key'],
                "action_desc"   => config('user.deposit')['pay']['name'],
                "oid"           => $vid,
                "created_at"    => time(),
                "updated_at"    => time()
            ];

            $userInsertReturn = $cardCallbackObj->updateUserAccountDeposit($userDepositParams);

            if ($userInsertReturn == false){
                return $this->com_return(false,config("params.ABNORMAL_ACTION")." - 009");
            }
        }elseif ($card_type == config("card.type")['1']['key']){
            //储值卡
            //更新用户余额账户以及余额明细
            //获取用户旧的余额
            //用户余额参数
            $userCardParams = [
                "uid"               => $uid,
                "account_balance"   => $cardInfo_amount + $account_balance,
                "user_status"       => config("user.user_status")['2']['key'],
                "updated_at"        => time()
            ];

            $userUpdateReturn = $cardCallbackObj->updateUserInfo($userCardParams,$uid);

            if ($userUpdateReturn == false){
                return $this->com_return(false,config("params.ABNORMAL_ACTION")." - 010");
            }

            //余额明细参数
            $userAccountParams = [
                "uid"          => $uid,
                "balance"      => $cardInfo_amount,
                "last_balance" => $cardInfo_amount + $account_balance,
                "change_type"  => '2',
                "action_user"  => 'sys',
                "action_type"  => config('user.account')['card_recharge']['key'],
                "oid"          => $vid,
                "deal_amount"  => $cardInfo_amount,
                "action_desc"  => config('user.account')['card_recharge']['name'],
                "created_at"   => time(),
                "updated_at"   => time()
            ];

            $userInsertReturn = $cardCallbackObj->updateUserAccount($userAccountParams);

            if ($userInsertReturn == false){
                return $this->com_return(false,config("params.ABNORMAL_ACTION")." - 011");
            }
        }elseif ($card_type == config("card.type")['2']['key']){
            //年费卡
            $userCardParams = [
                "uid"               => $uid,
                "user_status"       => config("user.user_status")['2']['key'],
                "updated_at"        => time()
            ];
            $userUpdateReturn = $cardCallbackObj->updateUserInfo($userCardParams,$uid);

            if ($userUpdateReturn == false){
                return $this->com_return(false,config("params.ABNORMAL_ACTION")." - 007");
            }

        }else{
            return $this->com_return(false,config("params.USER")['CARD_TYPE_ERROR']);
        }

        //⑩更新用户积分账户以及积分明细
        //$account_point用户积分可用余额
        //$card_point 开卡赠送积分
        if ($card_point > 0){
            //如果赠送积分大于0 则更新

            $new_account_point = $account_point + $card_point;
            //获取用户新的等级id
            $level_id = getUserNewLevelId($new_account_point);

            //1.更新用户积分余额
            $updateUserPointParams = [
                'level_id'      => $level_id,
                'account_point' => $new_account_point,
                'updated_at'    => time()
            ];
            $userUserPointReturn = $cardCallbackObj->updateUserInfo($updateUserPointParams,$uid);

            if ($userUserPointReturn == false){
                return $this->com_return(false,config("params.ABNORMAL_ACTION")." - 012");
            }

            //2.更新用户积分明细
            $updateAccountPointParams = [
                'uid'         => $uid,
                'point'       => $card_point,
                'last_point'  => $new_account_point,
                'change_type' => 2,
                'action_user' => 'sys',
                'action_type' => config("user.point")['open_card_reward']['key'],
                'action_desc' => config("user.point")['open_card_reward']['name'],
                'oid'         => $vid,
                'created_at'  => time(),
                'updated_at'  => time()
            ];

            $userAccountPointReturn = $cardCallbackObj->updateUserAccountPoint($updateAccountPointParams);

            if ($userAccountPointReturn == false){
                return $this->com_return(false,config("params.ABNORMAL_ACTION")." - 013");
            }
        }

        $wechatPayObj = new WechatPay();

        //下发赠送的券
        $giftVouReturn = $wechatPayObj->putVoucher("$card_id","$uid");

        if ($giftVouReturn == false){
            return $this->com_return(false,config("params.ABNORMAL_ACTION")." - 014");
        }

        $adminCommonAction = new CommonAction();

        $action  = config("useraction.open_card")['key'];

        $adminCommonAction->addSysAdminLog("$uid","","$vid","$action","$review_desc","$review_user",time());
    }

    /**
     * 获取卡信息
     * @param $card_id
     * @return array|false|\PDOStatement|string|\think\Model
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getCardInfo($card_id)
    {
        $cardVipModel = new MstCardVip();

        $column = $cardVipModel->column;

        $card_info = $cardVipModel
            ->where('card_id',$card_id)
            ->field($column)
            ->find();

        $card_info      = json_decode($card_info,true);

        return $card_info;
    }
}