<?php
/**
 * 统计公共类.
 * User: qubojie
 * Date: 2018/9/21
 * Time: 下午3:16
 */
namespace app\reception\controller;

use app\admin\model\ManageSalesman;
use app\admin\model\User;
use app\wechat\controller\CardCallback;
use app\wechat\model\BillCardFees;
use app\wechat\model\BillPayAssist;
use app\wechat\model\BillRefill;
use think\Controller;
use think\Db;
use think\Exception;

class CountPublic extends Controller
{
    /**
     * 消费类统计
     * @param $dateTime
     * @return array
     */
    public function consumerClass($dateTime)
    {
        $consumerClass = [];

        $billPayAssistModel = new BillPayAssist();

        $one   = config("bill_assist.bill_status")['1']['key'];
        $seven = config("bill_assist.bill_status")['7']['key'];
        $sale_status_str = "$one,$seven";

        //储值消费
        $all_balance_money = $billPayAssistModel
            ->alias("bpa")
            ->where("bpa.sale_status","IN",$sale_status_str)
            ->where("bpa.is_settlement",0)
            ->where("bpa.created_at","elt",$dateTime)
            ->sum("bpa.account_balance");

        $consumerClass['all_balance_money'] = (int)$all_balance_money;

        //现金消费
        $all_cash_money = $billPayAssistModel
            ->alias("bpa")
            ->where("bpa.sale_status","IN",$sale_status_str)
            ->where("bpa.is_settlement",0)
            ->where("bpa.created_at","elt",$dateTime)
            ->sum("bpa.cash");

        $consumerClass['all_cash_money'] = (int)$all_cash_money;

        //礼金消费
        $all_gift_money = $billPayAssistModel
            ->alias("bpa")
            ->where("bpa.sale_status","IN",$sale_status_str)
            ->where("bpa.is_settlement",0)
            ->where("bpa.created_at","elt",$dateTime)
            ->sum("bpa.account_cash_gift");

        $consumerClass['all_gift_money'] = (int)$all_gift_money;

        //礼券消费
        $all_voucher_money = $billPayAssistModel
            ->alias("bpa")
            ->join("user_gift_voucher ugv","ugv.gift_vou_code = bpa.gift_vou_code","LEFT")
            ->where("bpa.sale_status","IN",$sale_status_str)
            ->where("bpa.is_settlement",0)
            ->where("bpa.created_at","elt",$dateTime)
            ->sum("ugv.gift_vou_amount");

        $consumerClass['all_voucher_money'] = (int)$all_voucher_money;

        //储值退款金额
        $refund_balance_money = $billPayAssistModel
            ->alias("bpa")
            ->where("bpa.sale_status","IN",$sale_status_str)
            ->where("bpa.is_settlement",0)
            ->where("bpa.created_at","elt",$dateTime)
            ->sum("bpa.re_account_balance");
        $consumerClass['refund_balance_money'] = (int)$refund_balance_money;

        //礼金退款金额
        $refund_cash_gift_money = $billPayAssistModel
            ->alias("bpa")
            ->where("bpa.sale_status","IN",$sale_status_str)
            ->where("bpa.is_settlement",0)
            ->where("bpa.created_at","elt",$dateTime)
            ->sum("bpa.re_account_cash_gift");
        $consumerClass['refund_cash_gift_money'] = (int)$refund_cash_gift_money;

        //现金退款金额
        $refund_cash_money = $billPayAssistModel
            ->alias("bpa")
            ->where("bpa.sale_status","IN",$sale_status_str)
            ->where("bpa.is_settlement",0)
            ->where("bpa.created_at","elt",$dateTime)
            ->sum("bpa.re_cash");
        $consumerClass['refund_cash_money'] = (int)$refund_cash_money;

        return $consumerClass;
    }

    /**
     * 消费类统计详情
     * @param $settlement_id
     * @return array
     */
    public function consumerClassDetails($settlement_id)
    {
        $consumerClass = [];

        $billPayAssistModel = new BillPayAssist();

        //储值消费
        $all_balance_money = $billPayAssistModel
            ->alias("bpa")
            ->where("bpa.is_settlement",1)
            ->where("bpa.settlement_id",$settlement_id)
            ->sum("bpa.account_balance");

        $consumerClass['all_balance_money'] = $all_balance_money;

        //现金消费
        $all_cash_money = $billPayAssistModel
            ->alias("bpa")
            ->where("bpa.is_settlement",1)
            ->where("bpa.settlement_id",$settlement_id)
            ->sum("bpa.cash");

        $consumerClass['all_cash_money'] = $all_cash_money;

        //礼金消费
        $all_gift_money = $billPayAssistModel
            ->alias("bpa")
            ->where("bpa.is_settlement",1)
            ->where("bpa.settlement_id",$settlement_id)
            ->sum("bpa.account_cash_gift");

        $consumerClass['all_gift_money'] = $all_gift_money;

        //礼券消费
        $all_voucher_money = $billPayAssistModel
            ->alias("bpa")
            ->join("user_gift_voucher ugv","ugv.gift_vou_code = bpa.gift_vou_code","LEFT")
            ->where("bpa.is_settlement",1)
            ->where("bpa.settlement_id",$settlement_id)
            ->sum("ugv.gift_vou_amount");

        $consumerClass['all_voucher_money'] = $all_voucher_money;

        return $consumerClass;
    }

    /**
     * 储值类统计
     * @param $dateTime
     * @return mixed
     */
    public function rechargeClass($dateTime)
    {
        $billRefillModel = new BillRefill();

        //现金统计
        $cash_pay = $billRefillModel
            ->where("pay_type",\config("order.pay_method")['cash']['key'])
            ->where("status",\config("order.recharge_status")['completed']['key'])
            ->where("is_settlement",0)
            ->where("created_at","elt",$dateTime)
            ->sum("amount");

        $rechargeClass['cash_pay'] = (int)$cash_pay;

        //银行卡统计
        $bank_pay = $billRefillModel
            ->where("pay_type",\config("order.pay_method")['bank']['key'])
            ->where("status",\config("order.recharge_status")['completed']['key'])
            ->where("is_settlement",0)
            ->where("created_at","elt",$dateTime)
            ->sum("amount");

        $rechargeClass['bank_pay'] = (int)$bank_pay;

        //微信(现)统计
        $wxpay_c_pay = $billRefillModel
            ->where("pay_type",\config("order.pay_method")['wxpay_c']['key'])
            ->where("status",\config("order.recharge_status")['completed']['key'])
            ->where("is_settlement",0)
            ->where("created_at","elt",$dateTime)
            ->sum("amount");

        $rechargeClass['wxpay_c_pay'] = (int)$wxpay_c_pay;

        //支付宝(现)统计
        $alipay_c_pay = $billRefillModel
            ->where("pay_type",\config("order.pay_method")['alipay_c']['key'])
            ->where("status",\config("order.recharge_status")['completed']['key'])
            ->where("is_settlement",0)
            ->where("created_at","elt",$dateTime)
            ->sum("amount");

        $rechargeClass['alipay_c_pay'] = (int)$alipay_c_pay;

        //微信统计
        $wxpay_pay = $billRefillModel
            ->where("pay_type",\config("order.pay_method")['wxpay']['key'])
            ->where("status",\config("order.recharge_status")['completed']['key'])
            ->where("is_settlement",0)
            ->where("created_at","elt",$dateTime)
            ->sum("amount");

        $rechargeClass['wxpay_pay'] = (int)$wxpay_pay;

        $rechargeClass['sum'] = $cash_pay + $bank_pay + $wxpay_c_pay + $alipay_c_pay + $wxpay_pay;

        return $rechargeClass;
    }

    /**
     * 储值类统计详情
     * @param $settlement_id
     * @return mixed
     */
    public function rechargeClassDetails($settlement_id)
    {
        $billRefillModel = new BillRefill();

        //现金统计
        $cash_pay = $billRefillModel
            ->where("pay_type",\config("order.pay_method")['cash']['key'])
            ->where("is_settlement",1)
            ->where("settlement_id",$settlement_id)
            ->sum("amount");

        $rechargeClass['cash_pay'] = (int)$cash_pay;

        //银行卡统计
        $bank_pay = $billRefillModel
            ->where("pay_type",\config("order.pay_method")['bank']['key'])
            ->where("is_settlement",1)
            ->where("settlement_id",$settlement_id)
            ->sum("amount");

        $rechargeClass['bank_pay'] = (int)$bank_pay;

        //微信(现)统计
        $wxpay_c_pay = $billRefillModel
            ->where("pay_type",\config("order.pay_method")['wxpay_c']['key'])
            ->where("is_settlement",1)
            ->where("settlement_id",$settlement_id)
            ->sum("amount");

        $rechargeClass['wxpay_c_pay'] = (int)$wxpay_c_pay;

        //支付宝(现)统计
        $alipay_c_pay = $billRefillModel
            ->where("pay_type",\config("order.pay_method")['alipay_c']['key'])
            ->where("is_settlement",1)
            ->where("settlement_id",$settlement_id)
            ->sum("amount");

        $rechargeClass['alipay_c_pay'] = (int)$alipay_c_pay;

        //微信统计
        $wxpay_pay = $billRefillModel
            ->where("pay_type",\config("order.pay_method")['wxpay']['key'])
            ->where("is_settlement",1)
            ->where("settlement_id",$settlement_id)
            ->sum("amount");

        $rechargeClass['wxpay_pay'] = (int)$wxpay_pay;

        $rechargeClass['sum'] = $cash_pay + $bank_pay + $wxpay_c_pay + $alipay_c_pay + $wxpay_pay;

        return $rechargeClass;
    }

    /**
     * 会员开卡收款统计
     * @param $dateTime
     * @return array
     */
    public function vipCardClass($dateTime)
    {
        $billCardFeesModel = new BillCardFees();
        $vipCardClass = [];

        $pending_ship    = \config("order.open_card_status")['pending_ship']['key'];//待发货
        $pending_receipt = \config("order.open_card_status")['pending_receipt']['key'];//待收货
        $completed       = \config("order.open_card_status")['completed']['key'];//完成

        $sale_status = "$pending_ship,$pending_receipt,$completed";

        //现金
        $cash_pay = $billCardFeesModel
            ->where("sale_status","IN",$sale_status)
            ->where("pay_type",\config("order.pay_method")['cash']['key'])
            ->where("is_settlement",0)
            ->where("created_at","elt",$dateTime)
            ->sum("deal_price");

        $vipCardClass['cash_pay'] = (int)$cash_pay;

        //银行卡
        $bank_pay = $billCardFeesModel
            ->where("sale_status","IN",$sale_status)
            ->where("pay_type",\config("order.pay_method")['bank']['key'])
            ->where("is_settlement",0)
            ->where("created_at","elt",$dateTime)
            ->sum("deal_price");

        $vipCardClass['bank_pay'] = (int)$bank_pay;

        //微信(现)
        $wxpay_c_pay = $billCardFeesModel
            ->where("sale_status","IN",$sale_status)
            ->where("pay_type",\config("order.pay_method")['wxpay_c']['key'])
            ->where("is_settlement",0)
            ->where("created_at","elt",$dateTime)
            ->sum("deal_price");

        $vipCardClass['wxpay_c_pay'] = (int)$wxpay_c_pay;

        //支付宝(现)
        $alipay_c_pay = $billCardFeesModel
            ->where("sale_status","IN",$sale_status)
            ->where("pay_type",\config("order.pay_method")['alipay_c']['key'])
            ->where("is_settlement",0)
            ->where("created_at","elt",$dateTime)
            ->sum("deal_price");

        $vipCardClass['alipay_c_pay'] = (int)$alipay_c_pay;

        //微信
        $wxpay_pay = $billCardFeesModel
            ->where("sale_status","IN",$sale_status)
            ->where("pay_type",\config("order.pay_method")['wxpay']['key'])
            ->where("is_settlement",0)
            ->where("created_at","elt",$dateTime)
            ->sum("deal_price");

        $vipCardClass['wxpay_pay'] = (int)$wxpay_pay;

        $vipCardClass['sum'] = $cash_pay + $bank_pay + $wxpay_c_pay + $alipay_c_pay + $wxpay_pay;

        return $vipCardClass;
    }

    /**
     * 会员开卡收款统计详情
     * @param $settlement_id
     * @return array
     */
    public function vipCardClassDetails($settlement_id)
    {
        $billCardFeesModel = new BillCardFees();
        $vipCardClass = [];

        //现金
        $cash_pay = $billCardFeesModel
            ->where("pay_type",\config("order.pay_method")['cash']['key'])
            ->where("is_settlement",1)
            ->where("settlement_id",$settlement_id)
            ->sum("deal_price");

        $vipCardClass['cash_pay'] = (int)$cash_pay;

        //银行卡
        $bank_pay = $billCardFeesModel
            ->where("pay_type",\config("order.pay_method")['bank']['key'])
            ->where("is_settlement",1)
            ->where("settlement_id",$settlement_id)
            ->sum("deal_price");

        $vipCardClass['bank_pay'] = (int)$bank_pay;

        //微信(现)
        $wxpay_c_pay = $billCardFeesModel
            ->where("pay_type",\config("order.pay_method")['wxpay_c']['key'])
            ->where("is_settlement",1)
            ->where("settlement_id",$settlement_id)
            ->sum("deal_price");

        $vipCardClass['wxpay_c_pay'] = (int)$wxpay_c_pay;

        //支付宝(现)
        $alipay_c_pay = $billCardFeesModel
            ->where("pay_type",\config("order.pay_method")['alipay_c']['key'])
            ->where("is_settlement",1)
            ->where("settlement_id",$settlement_id)
            ->sum("deal_price");

        $vipCardClass['alipay_c_pay'] = (int)$alipay_c_pay;

        //微信
        $wxpay_pay = $billCardFeesModel
            ->where("pay_type",\config("order.pay_method")['wxpay']['key'])
            ->where("is_settlement",1)
            ->where("settlement_id",$settlement_id)
            ->sum("deal_price");

        $vipCardClass['wxpay_pay'] = (int)$wxpay_pay;

        $vipCardClass['sum'] = $cash_pay + $bank_pay + $wxpay_c_pay + $alipay_c_pay + $wxpay_pay;

        return $vipCardClass;
    }

    /**
     * 收款小计
     * @param $dateTime
     * @return array
     */
    public function receivablesLClass($dateTime)
    {
        $rechargeClass = $this->rechargeClass($dateTime);

        $vipCardClass  = $this->vipCardClass($dateTime);

        $arr = [
            $rechargeClass,
            $vipCardClass
        ];

        $item = array();
        //将数组相同key的值相加, 并处理成新数组
        foreach ($arr as $key => $value) {
            foreach ($value as $k => $v){
                if (isset($item[$k])){
                    $item[$k] = $item[$k] + $v;
                }else{
                    $item[$k] = $v;
                }
            }
        }

        return $item;
    }

    /**
     * 收款小计详情
     * @param $settlement_id
     * @return array
     */
    public function receivablesLClassDetails($settlement_id)
    {
        $rechargeClass = $this->rechargeClassDetails($settlement_id);

        $vipCardClass  = $this->vipCardClassDetails($settlement_id);

        $arr = [
            $rechargeClass,
            $vipCardClass
        ];

        $item = array();
        //将数组相同key的值相加, 并处理成新数组
        foreach ($arr as $key => $value) {
            foreach ($value as $k => $v){
                if (isset($item[$k])){
                    $item[$k] = $item[$k] + $v;
                }else{
                    $item[$k] = $v;
                }
            }
        }

        return $item;
    }

    /**
     * 收款总计
     */
    public function allClass($dateTime)
    {
        $consumerClass = $this->consumerClass($dateTime);

        $allClass['balance_money'] = $consumerClass['all_balance_money'];

        $receivablesLClass = $this->receivablesLClass($dateTime);

        $allClass['sum_money'] = $receivablesLClass['sum'];

        return $allClass;
    }

    /**
     * 收款总计详情
     */
    public function allClassDetails($settlement_id)
    {
        $consumerClass = $this->consumerClassDetails($settlement_id);

        $allClass['balance_money'] = $consumerClass['all_balance_money'];

        $receivablesLClass = $this->receivablesLClassDetails($settlement_id);

        $allClass['sum_money'] = $receivablesLClass['sum'];

        return $allClass;
    }


    /**
     * 消费结算回填单号
     * @param $ettlement_at
     * @param $settlement_id
     * @param $check_user
     * @return bool
     */
    public function balanceMoneyR($ettlement_at,$settlement_id,$check_user)
    {
        $params = [
            "is_settlement"   => 1,
            "settlement_user" => $check_user,
            "settlement_id"   => $settlement_id,
            "updated_at"      => time()
        ];

        $billPayAssistModel = new BillPayAssist();

        $one   = config("bill_assist.bill_status")['1']['key'];
        $seven = config("bill_assist.bill_status")['7']['key'];
        $sale_status_str = "$one,$seven";

        //储值消费
        $res = $billPayAssistModel
            ->alias("bpa")
//            ->where("bpa.sale_status","IN",$sale_status_str)
            ->where("bpa.is_settlement",0)
            ->where("bpa.created_at","elt",$ettlement_at)
            ->update($params);

        if ($res !== false){
            return true;
        }else{
            return false;
        }
    }

    /**
     * 消费结算返还礼金佣金积分等操作
     * @param $ettlement_at
     * @param $check_user
     * @return array
     * @throws Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @throws \think\exception\PDOException
     */
    public function returnMoneyR($ettlement_at,$check_user)
    {
        $one   = config("bill_assist.bill_status")['1']['key'];
        $seven = config("bill_assist.bill_status")['7']['key'];
        $sale_status_str = "$one,$seven";

        $billPayAssistModel = new BillPayAssist();
        //储值消费
        $res = $billPayAssistModel
            ->alias("bpa")
            ->where("bpa.sale_status","IN",$sale_status_str)
            ->where("bpa.is_settlement",0)
            ->where("bpa.created_at","elt",$ettlement_at)
            ->select();
        $res = json_decode(json_encode($res),true);

        $cardCallBackObj = new CardCallback();

        for ($i = 0; $i < count($res); $i ++){
            $pid = $res[$i]['pid'];

            $pidInfo = $billPayAssistModel
                ->where("pid",$pid)
                ->find();

            $pidInfo = json_decode(json_encode($pidInfo),true);

            if (empty($pidInfo)){
                return $this->com_return(false,config("params.ORDER")['ORDER_NOT_EXIST']);
            }
            $uid                     = $pidInfo['uid'];
            $spend_account_balance   = $pidInfo['account_balance'];//储值消费金额
            $spend_account_cash_gift = $pidInfo['account_cash_gift'];//礼金消费金额
            $spend_cash              = $pidInfo['cash'];//现金消费金额

            $userInfo = getUserInfo($uid);

            if (empty($userInfo)){
                return $this->com_return(false,config("params.USER")['USER_NOT_EXIST']);
            }

            $account_balance   = $userInfo['account_balance'];//余额账户

            $account_cash_gift = $userInfo['account_cash_gift'];//礼金账户

            $referrer_type     = $userInfo['referrer_type'];//推荐人类型

            $referrer_id       = $userInfo['referrer_id'];//推荐人id

            /*如果推荐人是内部人员 查看是否注册用户信息 On*/
            if ($referrer_id != config("salesman.salesman_type")['3']['key']){
                //如果不是平台推荐
                if ($referrer_type != 'user'){
                    //如果是内部人员推荐,给人员用户端账号返还礼金,佣金
                    $manageSalesModel = new ManageSalesman();

                    $salesInfo = $manageSalesModel
                        ->where("sid",$referrer_id)
                        ->field("phone")
                        ->find();
                    $salesInfo = json_decode(json_encode($salesInfo),true);

                    if (empty($salesInfo)){
                        //推荐人不存在
                        return $this->com_return(false,config("params.SALESMAN_NOT_EXIST"));
                    }
                    $sales_phone = $salesInfo['phone'];

                    $userModel = new User();

                    $salesUserInfo = $userModel
                        ->where("phone",$sales_phone)
                        ->field("uid,account_balance,account_point,account_cash_gift")
                        ->find();

                    $salesUserInfo = json_decode(json_encode($salesUserInfo),true);

                    if (empty($salesUserInfo)){
//                    return $this->com_return(false,config("params.USER")['SALES_NOT_REGISTER_USER']);
                        $referrer_id   = "";
                        $referrer_type = "";
                    }else{
                        $referrer_id   = $salesUserInfo['uid'];
                        $referrer_type = config("salesman.salesman_type")['2']['key'];
                    }
                }
            }
            /*如果推荐人是内部人员 查看是否注册用户信息 Off*/

            $consumption_money = $spend_account_balance;

            /*获取开卡用户返还比例 On*/
            $publicObj   = new PublicAction();
            $returnMoney = $publicObj->uidGetCardReturnMoney($uid);

            if (!empty($returnMoney)){

                $consume_cash_gift      = $returnMoney['consume_cash_gift'];     //消费持卡人返礼金比例
                $consume_commission     = $returnMoney['consume_commission'];    //消费持卡人返佣金比例
                $consume_job_cash_gift  = $returnMoney['consume_job_cash_gift']; //消费推荐人返礼金比例
                $consume_job_commission = $returnMoney['consume_job_commission'];//消费推荐人返佣金比例

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
            /*获取开卡用户返还比例 Off*/

            /*返还推荐人礼金 On*/
            if ($return_cash_gift > 0){
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

                /*推荐人礼金明细 On*/
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
                /*推荐人礼金明细 Off*/
            }
            /*返还推荐人礼金 Off*/

            /*返给推荐人佣金 On*/
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

                /*佣金明细 On*/
                //添加推荐用户佣金明细表
                $jobAccountParams = [
                    "uid"          => $referrer_id,
                    "balance"      => $return_commission,
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
                /*佣金明细 Off*/

            }
            /*返给推荐人佣金 Off*/

            $user_returned_cash_gift = $account_cash_gift + $cash_gift_return_money; //用户最终所剩礼金数

            /*返给持卡用户礼金 On*/
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
            /*返给持卡用户礼金 Off*/


            /*返给持卡用户的佣金 On*/
            if ($commission_return_money > 0) {
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
            /*返给持卡用户的佣金 Off*/

            /*用户积分更新 On*/
            $returnUserPoint = getSysSetting("card_consume_point_ratio");

            $return_point = intval($spend_account_balance * ($returnUserPoint/100));//获取返还用户积分数

            if ($return_point > 0){
                $user_old_account_point = $userInfo["account_point"];
                $user_new_account_point = $user_old_account_point + $return_point;
                $user_new_level_id = getUserNewLevelId($user_new_account_point);//用户新的积分等级
                /*积分明细 On*/
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

            /*将返还积分 返还佣金,返还礼金 回填至消费单据 On*/
            $billPayAssistRParams = [
                "return_point"          => $return_point,
                "return_own_commission" => $return_own_commission,
                "return_own_cash_gift"  => $return_own_cash_gift,
                "return_cash_gift"      => $return_cash_gift,
                "return_commission"     => $return_commission
            ];

            $updateBillPayAssistRReturn = $billPayAssistModel
                ->where("pid",$pid)
                ->update($billPayAssistRParams);

            if ($updateBillPayAssistRReturn === false){
                return $this->com_return(false,config("params.ABNORMAL_ACTION"). "- 012");
            }
            /*将返还积分 返还佣金,返还礼金 回填至消费单据 Off*/
        }

        return $this->com_return(true,config("params.SUCCESS"));

    }

    /**
     * 储值结算单号回填
     * @param $ettlement_at
     * @param $settlement_id
     * @param $check_user
     * @return bool
     */
    public function rechargeR($ettlement_at,$settlement_id,$check_user)
    {
        $params = [
            "is_settlement"   => 1,
            "settlement_user" => $check_user,
            "settlement_id"   => $settlement_id,
            "updated_at"      => time()
        ];

        $billRefillModel = new BillRefill();

        $res = $billRefillModel
            ->where("status",\config("order.recharge_status")['completed']['key'])
            ->where("is_settlement",0)
            ->where("created_at","elt",$ettlement_at)
            ->update($params);

        if ($res !== false){
            return true;
        }else{
            return false;
        }
    }

    /**
     * 会员开卡结算单号回填
     * @param $ettlement_at
     * @param $settlement_id
     * @param $check_user
     * @return bool
     */
    public function vipCardR($ettlement_at,$settlement_id,$check_user)
    {
        $pending_ship    = \config("order.open_card_status")['pending_ship']['key'];//待发货
        $pending_receipt = \config("order.open_card_status")['pending_receipt']['key'];//待收货
        $completed       = \config("order.open_card_status")['completed']['key'];//完成

        $sale_status = "$pending_ship,$pending_receipt,$completed";

        $params = [
            "is_settlement"   => 1,
            "settlement_user" => $check_user,
            "settlement_id"   => $settlement_id
        ];

        $billCardFeesModel = new BillCardFees();

        $res = $billCardFeesModel
            ->where("sale_status","IN",$sale_status)
            ->where("is_settlement",0)
            ->where("created_at","elt",$ettlement_at)
            ->update($params);

        if ($res !== false){
            return true;
        }else{
            return false;
        }
    }
}