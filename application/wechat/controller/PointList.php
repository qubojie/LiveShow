<?php
/**
 * 点单操作
 * User: qubojie
 * Date: 2018/8/22
 * Time: 下午2:53
 */

namespace app\wechat\controller;

use app\admin\model\TableRevenue;
use think\Request;
use think\Validate;

class PointList extends CommonAction
{
    /**
     * 获取扫码点单对应的 trid
     * @param Request $request
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getTableRevenueInfo(Request $request)
    {
        $table_id = $request->param("table_id","");//桌id

        if (empty($table_id)){
            return $this->com_return(false,config("params.PARAM_NOT_EMPTY"));
        }

        $tableRevenueModel = new TableRevenue();

        $info = $tableRevenueModel
            ->alias("tr")
            ->join("manage_salesman ms","ms.sid = tr.ssid","LEFT")
            ->join("user u",'u.uid = tr.uid','LEFT')
            ->where("tr.status",config("order.table_reserve_status")['already_open']['key'])
            ->where("tr.table_id",$table_id)
            ->field("tr.trid,tr.table_id,tr.table_no,tr.parent_trid,tr.turnover_limit,tr.turnover_num,tr.ssid,tr.ssname,ms.phone sphone")
            ->field("u.name,u.phone")
            ->select();

        $info = json_decode(json_encode($info),true);

        if (empty($info)){
            return $this->com_return(false,config("params.REVENUE")['NOT_OPEN_NOT_DISH']);
        }

        return $this->com_return(true,config("params.SUCCESS"),$info);
    }

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

        $pay_type     = $request->param("pay_type",'');//支付方式

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

        $type = config("order.bill_pay_type")['consumption']['key'];

        $sid = NULL;

        $pointListPublicObj = new PointListPublicAction();

        return $pointListPublicObj->pointListPublicAction("$trid","$sid","$order_amount","$dish_group","$pay_type","$type",$uid);
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