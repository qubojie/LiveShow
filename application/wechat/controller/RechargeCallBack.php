<?php
/**
 * 充值成功回调类
 * User: qubojie
 * Date: 2018/8/3
 * Time: 上午10:47
 */
namespace app\wechat\controller;

use app\wechat\model\BillRefill;
use think\Controller;

class RechargeCallBack extends Controller
{
    /**
     * 更新支付单据状态
     * @param array $params
     * @param $rfid
     * @return bool
     */
    public function updateBillRefill($params = array(),$rfid)
    {
        $billRefillModel = new BillRefill();

        $is_ok = $billRefillModel
            ->where("rfid",$rfid)
            ->update($params);

        if ($is_ok){
            return true;
        }else{
            return false;
        }
    }

    /**
     * 更新用户礼金账户
     */
    public function updateUserGiftAccount()
    {

    }

    /**
     * 插入用户余额明细
     */
    public function insertUserBalanceDetail()
    {

    }

    /**
     * 插入用户礼金明细
     */
    public function insertUserGiftDetail()
    {

    }
}