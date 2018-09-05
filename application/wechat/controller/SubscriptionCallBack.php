<?php
/**
 * Created by PhpStorm.
 * User: qubojie
 * Date: 2018/7/30
 * Time: 上午11:15
 */

namespace app\wechat\controller;

use app\admin\model\TableRevenue;
use app\wechat\model\BillSubscription;
use think\Controller;

class SubscriptionCallBack extends Controller
{

    /**
     * 台位预定定金缴费单更新
     * @param array $params
     * @param $suid
     * @return bool
     */
    public function changeBillSubscriptionInfo($params = array(),$suid)
    {
       $billSubscriptionModel = new BillSubscription();

       $is_ok = $billSubscriptionModel
           ->where('suid',$suid)
           ->update($params);

       if ($is_ok !== false) {
           return true;
       }else{
           return false;
       }

    }

    /**
     * 台位预定及营收状况信息表更新
     * @param array $params
     * @param $trid
     * @param $uid
     * @return bool
     */
    public function changeTableRevenueInfo($params = array(),$trid,$uid)
    {
        $tableRevenueModel = new TableRevenue();

        $is_ok = $tableRevenueModel
            ->where("trid",$trid)
//            ->where("uid",$uid)
            ->update($params);
        if ($is_ok !== false) {
            return true;
        }else{
            return false;
        }
    }
}