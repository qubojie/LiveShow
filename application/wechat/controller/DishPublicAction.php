<?php
/**
 * Created by PhpStorm.
 * User: qubojie
 * Date: 2018/8/20
 * Time: 下午2:04
 */
namespace app\wechat\controller;

use app\admin\controller\Common;
use app\wechat\model\BillPayDetail;
use think\Controller;

class DishPublicAction extends Controller
{
    /**
     * 根据pid获取当前订单的菜品信息
     * @param $pid
     * @return false|\PDOStatement|string|\think\Collection
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function pidGetOrderDishInfo($pid)
    {
        $billPayDetailModel = new BillPayDetail();

        $tableInfo = $billPayDetailModel
            ->alias("bpd")
            ->join("table_revenue tr","tr.trid = bpd.trid")
            ->join("mst_table_area ta","ta.area_id = tr.area_id")
            ->join("mst_table_location tl","tl.location_id = ta.location_id")
            ->where("bpd.pid",$pid)
            ->field("tl.location_title")
            ->field("ta.area_title")
            ->field("tr.table_no")
            ->find();

        $tableInfo = json_decode(json_encode($tableInfo),true);

        $list = $billPayDetailModel
            ->alias("bpd")
            ->field("bpd.id,bpd.parent_id,bpd.pid,bpd.trid,bpd.is_refund,bpd.is_give,bpd.dis_id,bpd.dis_type,bpd.dis_sn,bpd.dis_name,bpd.dis_desc,bpd.quantity,bpd.price,bpd.amount")
            ->select();

        $list = json_decode(json_encode($list),true);

        $commonObj = new Common();

        $list = $commonObj->make_tree($list,"id","parent_id");

        $tableInfo['dish_info'] = $list;

        return $tableInfo;
    }
}