<?php
/**
 * 统计公共类.
 * User: qubojie
 * Date: 2018/9/21
 * Time: 下午3:16
 */
namespace app\reception\controller;

use app\wechat\model\BillCardFees;
use app\wechat\model\BillPayAssist;
use app\wechat\model\BillRefill;
use think\Controller;

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

        //储值消费
        $all_balance_money = $billPayAssistModel
            ->alias("bpa")
            ->where("bpa.sale_status",config("bill_assist.bill_status")['1']['key'])
            ->where("bpa.created_at","elt",$dateTime)
            ->sum("bpa.account_balance");

        $consumerClass['all_balance_money'] = $all_balance_money;

        //现金消费
        $all_cash_money = $billPayAssistModel
            ->alias("bpa")
            ->where("bpa.sale_status",config("bill_assist.bill_status")['1']['key'])
            ->where("bpa.created_at","elt",$dateTime)
            ->sum("bpa.cash");

        $consumerClass['all_cash_money'] = $all_cash_money;

        //礼金消费
        $all_gift_money = $billPayAssistModel
            ->alias("bpa")
            ->where("bpa.sale_status",config("bill_assist.bill_status")['1']['key'])
            ->where("bpa.created_at","elt",$dateTime)
            ->sum("bpa.account_cash_gift");

        $consumerClass['all_gift_money'] = $all_gift_money;

        //礼券消费
        $all_voucher_money = $billPayAssistModel
            ->alias("bpa")
            ->join("user_gift_voucher ugv","ugv.gift_vou_code = bpa.gift_vou_code","LEFT")
            ->where("bpa.sale_status",config("bill_assist.bill_status")['1']['key'])
            ->where("bpa.created_at","elt",$dateTime)
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
            ->where("created_at","elt",$dateTime)
            ->sum("amount");

        $rechargeClass['cash_pay'] = (int)$cash_pay;

        //银行卡统计
        $bank_pay = $billRefillModel
            ->where("pay_type",\config("order.pay_method")['bank']['key'])
            ->where("status",\config("order.recharge_status")['completed']['key'])
            ->where("created_at","elt",$dateTime)
            ->sum("amount");

        $rechargeClass['bank_pay'] = (int)$bank_pay;

        //微信(现)统计
        $wxpay_c_pay = $billRefillModel
            ->where("pay_type",\config("order.pay_method")['wxpay_c']['key'])
            ->where("status",\config("order.recharge_status")['completed']['key'])
            ->where("created_at","elt",$dateTime)
            ->sum("amount");

        $rechargeClass['wxpay_c_pay'] = (int)$wxpay_c_pay;

        //支付宝(现)统计
        $alipay_c_pay = $billRefillModel
            ->where("pay_type",\config("order.pay_method")['alipay_c']['key'])
            ->where("status",\config("order.recharge_status")['completed']['key'])
            ->where("created_at","elt",$dateTime)
            ->sum("amount");

        $rechargeClass['alipay_c_pay'] = (int)$alipay_c_pay;

        //微信统计
        $wxpay_pay = $billRefillModel
            ->where("pay_type",\config("order.pay_method")['wxpay']['key'])
            ->where("status",\config("order.recharge_status")['completed']['key'])
            ->where("created_at","elt",$dateTime)
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
            ->where("created_at","elt",$dateTime)
            ->sum("deal_price");

        $vipCardClass['cash_pay'] = (int)$cash_pay;

        //银行卡
        $bank_pay = $billCardFeesModel
            ->where("sale_status","IN",$sale_status)
            ->where("pay_type",\config("order.pay_method")['bank']['key'])
            ->where("created_at","elt",$dateTime)
            ->sum("deal_price");

        $vipCardClass['bank_pay'] = (int)$bank_pay;

        //微信(现)
        $wxpay_c_pay = $billCardFeesModel
            ->where("sale_status","IN",$sale_status)
            ->where("pay_type",\config("order.pay_method")['wxpay_c']['key'])
            ->where("created_at","elt",$dateTime)
            ->sum("deal_price");

        $vipCardClass['wxpay_c_pay'] = (int)$wxpay_c_pay;

        //支付宝(现)
        $alipay_c_pay = $billCardFeesModel
            ->where("sale_status","IN",$sale_status)
            ->where("pay_type",\config("order.pay_method")['alipay_c']['key'])
            ->where("created_at","elt",$dateTime)
            ->sum("deal_price");

        $vipCardClass['alipay_c_pay'] = (int)$alipay_c_pay;

        //微信
        $wxpay_pay = $billCardFeesModel
            ->where("sale_status","IN",$sale_status)
            ->where("pay_type",\config("order.pay_method")['wxpay']['key'])
            ->where("created_at","elt",$dateTime)
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

}