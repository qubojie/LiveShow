<?php
/**
 * Created by PhpStorm.
 * User: qubojie
 * Date: 2018/7/2
 * Time: 下午5:33
 */
namespace app\index\controller;

use app\admin\model\TableRevenue;
use app\wechat\controller\OpenCard;
use app\wechat\model\BillCardFees;
use app\wechat\model\BillPay;
use app\wechat\model\BillRefill;
use app\wechat\model\BillSubscription;
use think\Controller;
use think\Db;
use think\Exception;
use think\Log;

class ChangeStatus extends Controller
{
    /**
     * 系统自动取消开卡订单
     * @return BillCardFees|bool|string
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public static function changeOrderStatus()
    {
        $sale_status = config("order.open_card_status")['pending_payment']['key'];
        $pay_type    = config("order.pay_method")['cash']['key'];

        $bill_list = Db::name('bill_card_fees')
            ->where('sale_status',$sale_status)
            ->where('pay_type','neq',$pay_type)
            ->select();

        try{
            $is_ok = false;
            if (empty($bill_list)){
                $is_ok =  "什么都没做";
            }
            for ($i=0;$i<count($bill_list);$i++) {

                $auto_cancel_time = $bill_list[$i]['auto_cancel_time'];
                $uid =  $bill_list[$i]['uid'];

                $time = time();
                if ($auto_cancel_time < $time){
                    //时间超出
                    $params = [
                        'sale_status' => 9,
                        'cancel_user' => 'sys',
                        'cancel_time' => $time,
                        'auto_cancel' => 1,
                        'auto_cancel_time' => $time,
                        'cancel_reason' => '时限内未付款',
                        'updated_at' => $time
                    ];

                    $billCardFeesModel = new BillCardFees();

                    $is_ok = $billCardFeesModel
                        ->where('auto_cancel_time',$auto_cancel_time)
                        ->update($params);

                    $user_params = [
                        'user_status' => 0,
                        'updated_at'  => $time
                    ];
                    $user_ok = Db::name('user')
                        ->where('uid',$uid)
                        ->update($user_params);

                    if ($is_ok && $user_ok){
                        $is_ok = 1;
                    }else{
                        $is_ok = 2;
                    }

                }else{
                    $is_ok =  "什么都没做";
                }
            }

            return $is_ok;

        }catch (Exception $e){
            return $e->getMessage();
        }
    }

    /**
     * 系统自动确认收货
     * @return bool|string
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public static function AutoFinishTime()
    {
        //查找已发货订单
        $bill_list = Db::name('bill_card_fees')
            ->where('sale_status',config("order.open_card_status")['pending_receipt']['key'])
            ->select();

        try{
            if (empty($bill_list)){
                return "什么都没做";
            }


            for ($i=0;$i<count($bill_list);$i++){
                $auto_finish_time = $bill_list[$i]['auto_finish_time'];
                $time = time();
                if ($auto_finish_time < $time ){
                    $params = [
                        'sale_status' => config("order.open_card_status")['completed']['key'],
                        'finish_time' => $time,
                        'auto_finish' => 1,
                        'updated_at'  => $time,
                    ];
                    $billCardFeesModel = new BillCardFees();

                    $is_ok = $billCardFeesModel
                        ->where('auto_finish_time',$auto_finish_time)
                        ->where('sale_status',config("order.open_card_status")['pending_receipt']['key'])
                        ->update($params);

                }else{
                    return "什么都没做";
                }
            }

            return 1;

        }catch (Exception $e){
            return $e->getMessage();
        }
    }

    /**
     * 系统自动取消未支付预约订单
     * @return bool|string
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public static function AutoCancelTableRevenue()
    {
        //查找未支付的订单
        $list = Db::name('table_revenue')
            ->where('status',config("order.table_reserve_status")['pending_payment']['key'])
            ->select();

        try{

            if (empty($list)){
                return "什么都没做";
            }

            //获取系统设置自动取消时间
            $cardObj = new OpenCard();

            $reserve_subscription_auto_time = $cardObj->getSysSettingInfo("reserve_subscription_auto_time");

            $reserve_subscription_auto_time_s = $reserve_subscription_auto_time * 60;//自动取消等待秒数


            $now_time = time();//当前时间

            $table_params = [
                "status"        => config("order.table_reserve_status")['cancel']['key'],
                "cancel_user"   => "sys",
                "cancel_time"   => $now_time,
                "cancel_reason" => "时限内未付款",
                "updated_at"    => $now_time
            ];

            $bill_params = [
                "status" => config("order.reservation_subscription_status")['cancel']['key'],
                "cancel_user" => "sys",
                "cancel_time" => $now_time,
                "auto_cancel" => 1,
                "auto_cancel_time" => $now_time,
                "cancel_reason" => "时限内未付款",
                "updated_at" => $now_time
            ];

            $tableRevenueModel = new TableRevenue();
            $billSubscriptionModel = new BillSubscription();

            for ($i = 0; $i < count($list); $i++){
                $subscription_type = $list[$i]['subscription_type'];//定金类型   0无订金   1订金   2订单
                $subscription      = $list[$i]['subscription'];//订金金额
                $trid              = $list[$i]['trid'];//台位预定id

                $created_at         = $list[$i]['created_at'];
                $auto_cancel_time_s = $created_at + $reserve_subscription_auto_time_s;

                $tableRevenueReturn = true;
                $billSubscriptionReturn = true;

                if ($now_time > $auto_cancel_time_s){
                    $tableRevenueReturn = $tableRevenueModel
                        ->where('trid',$trid)
                        ->update($table_params);

                    if ($subscription_type == config("order.subscription_type")['subscription']['key']){
                        //如果为定金,执行定金状态变更
                        $billSubscriptionReturn = $billSubscriptionModel
                            ->where('trid',$trid)
                            ->update($bill_params);

                    }else{
                        //如果为订单,执行订单状态变更
                        $billSubscriptionReturn = true;
                    }
                }

                if ($tableRevenueReturn !== false && $billSubscriptionReturn !== false){
                }

            }

            return 1;

        }catch (Exception $e){
            return $e->getMessage();
        }

    }

    /**
     * 系统自动取消未支付充值单据
     * @return int|string
     */
    public static function AutoCancelBillRefill()
    {
        $now_time = time();//当前时间

        //获取系统设置自动取消时间
        $cardObj = new OpenCard();

        $reserve_subscription_auto_time   = $cardObj->getSysSettingInfo("reserve_subscription_auto_time");

        $reserve_subscription_auto_time_s = $reserve_subscription_auto_time * 60;//自动取消等待秒数

        $billRefillParams = [
            "status"     => config("order.recharge_status")['cancel']['key'],
            "updated_at" => $now_time
        ];

        $billRefillModel = new BillRefill();

        $list = $billRefillModel
            ->where('status',config("order.recharge_status")['pending_payment']['key'])
            ->where("created_at","lt",$now_time - $reserve_subscription_auto_time_s )
            ->update($billRefillParams);

        if ($list){
            return 1;
        }else{
            return "什么都没做";
        }

    }

    public static function autoCancelRevenueListOrder()
    {
        $now_time = time();

        //获取系统设置自动取消时间
        $cardObj = new OpenCard();
        $reserve_subscription_auto_time   = $cardObj->getSysSettingInfo("reserve_subscription_auto_time");

        $reserve_subscription_auto_time_s = $reserve_subscription_auto_time * 60;//自动取消等待秒数

        $billPayParams = [
            "sale_status"      => config("order.bill_pay_sale_status")['cancel']['key'],
            "cancel_user"      => "sys",
            "cancel_time"      => $now_time,
            "auto_cancel"      => 1,
            "auto_cancel_time" => $now_time,
            "cancel_reason"    => "时限内未付款",
            "updated_at"       => $now_time
        ];

        $billPayModel = new BillPay();

        $list = $billPayModel
            ->where('sale_status',config("order.bill_pay_sale_status")['pending_payment_return']['key'])
            ->where('type',config("order.bill_pay_type")['consumption']['key'])
            ->where("created_at","lt",$now_time - $reserve_subscription_auto_time_s )
            ->update($billPayParams);

        if ($list){
            return 1;
        }else{
            return "什么都没做";
        }
    }



















}
