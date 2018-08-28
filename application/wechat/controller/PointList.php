<?php
/**
 * 点单操作
 * User: qubojie
 * Date: 2018/8/22
 * Time: 下午2:53
 */

namespace app\wechat\controller;

use app\admin\model\TableRevenue;
use app\wechat\model\BillPay;
use app\wechat\model\BillPayDetail;
use think\Controller;
use think\Db;
use think\Exception;
use think\Request;
use think\Validate;

class PointList extends CommonAction
{
    /**
     * 用户点单
     * @param Request $request
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function createPointList(Request $request)
    {
        $trid         = $request->param("trid","");//订台id

        $order_amount = $request->param('order_amount','');//订单总额

        $dish_group   = $request->param("dish_group",'');//菜品集合

        $rule = [
            "trid|订台id"          => "require",
            "order_amount|订单总额" => "require",
            "dish_group|菜品集合"   => "require",
        ];

        $check_data = [
            "trid"           => $trid,
            "order_amount"   => $order_amount,
            "dish_group"     => $dish_group
        ];

        $validate = new Validate($rule);

        if (!$validate->check($check_data)){
            return $this->com_return(false,$validate->getError());
        }

        $remember_token = $request->header("Token",'');

        //获取当前用户信息
        $userInfo = $this->tokenGetUserInfo($remember_token);

        $uid = $userInfo['uid'];

        $sid = NULL;

        $pointListPublicObj = new PointListPublicAction();

        return $pointListPublicObj->pointListPublicAction("$trid","$sid","$order_amount","$dish_group",$uid);

    }


    /**
     * 用户手动取消未支付订单
     * @param Request $request
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function cancelDishOrder(Request $request)
    {

        $pid = $request->param("pid","");//订单id

        $action_user = "user";

        $pointListPublicObj = new PointListPublicAction();

        return $pointListPublicObj->cancelPointListPublicAction($action_user,$pid);
    }

}