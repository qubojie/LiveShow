<?php
/**
 * Created by PhpStorm.
 * User: qubojie
 * Date: 2018/8/20
 * Time: 下午2:04
 */
namespace app\wechat\controller;

use app\admin\controller\Common;
use app\wechat\model\BillPay;
use app\wechat\model\BillPayDetail;
use think\Controller;
use think\Db;

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
        $tableInfo = $this->pidGetTableInfo($pid);

        $billPayDetailModel = new BillPayDetail();

        $list = $billPayDetailModel
            ->alias("bpd")
            ->where("bpd.is_refund","0")
            ->where("bpd.pid",$pid)
            ->field("bpd.id,bpd.parent_id,bpd.pid,bpd.trid,bpd.is_refund,bpd.is_give,bpd.dis_id,bpd.dis_type,bpd.dis_sn,bpd.dis_name,bpd.dis_desc,bpd.quantity,bpd.price,bpd.amount")
            ->select();

        $list = json_decode(json_encode($list),true);

        $commonObj = new Common();

        $list = $commonObj->make_tree($list,"id","parent_id");

        $tableInfo['dish_info'] = $list;

        return $tableInfo;
    }

    /**
     * 厨房打印根据pid获取当前订单的菜品信息
     * @param $pid
     * @return false|\PDOStatement|string|\think\Collection
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function pidGetOrderDishInfo2($pid)
    {
        $tableInfo = $this->pidGetTableInfo($pid);

        $billPayDetailModel = new BillPayDetail();

        $list = $billPayDetailModel
            ->alias("bpd")
            ->join("dishes d","d.dis_id = bpd.dis_id")
            ->join("dishes_attribute da","da.att_id = d.att_id")
            ->where("bpd.is_refund","0")
            ->where("bpd.pid",$pid)
            ->group("bpd.dis_id")
            ->field("bpd.pid")
            ->field("d.dis_id,d.dis_name,d.dis_type")
            ->field("sum(bpd.quantity) quantity")
            ->field("da.att_name,da.att_id")
            ->select();

        $list = json_decode(json_encode($list),true);

        foreach ($list as $key => $val){

            $att_id = $val['att_id'];

            $att_print_info = Db::name("dishes_attribute_printer")
                ->where("att_id",$att_id)
                ->field("printer_sn,print_num")
                ->find();

            $printer_sn = $att_print_info['printer_sn'];
            $print_num  = $att_print_info['print_num'];

            $list[$key]['printer_sn'] = $printer_sn;
            $list[$key]['print_num']  = $print_num;

            if ($val['dis_type']){
                unset($list[$key]);
            }
        }

        $list = array_values($list);

        $tableInfo['dish_info'] = $list;

        return $tableInfo;
    }

    /**
     * 获取桌位信息
     * @param $pid
     * @return array|false|mixed|\PDOStatement|string|\think\Model
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function pidGetTableInfo($pid)
    {
        $billPayModel = new BillPay();

        $tableInfo = $billPayModel
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

        return $tableInfo;
    }
}