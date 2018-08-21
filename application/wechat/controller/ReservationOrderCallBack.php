<?php
/**
 * Created by PhpStorm.
 * User: qubojie
 * Date: 2018/8/20
 * Time: 下午1:37
 */
namespace app\wechat\controller;

use app\wechat\model\BillPay;
use think\Controller;

class ReservationOrderCallBack extends Controller
{
    /**
     * 更新预约点单信息
     * @param $updateBillPayParams
     * @param $pid
     * @return bool
     */
    public function updateBillPay($updateBillPayParams,$pid)
    {
        $billPayModel = new BillPay();

        $is_ok = $billPayModel
            ->where("pid",$pid)
            ->update($updateBillPayParams);

        if ($is_ok !== false){
            return true;
        }else{
            return false;
        }
    }

}