<?php
/**
 * 服务人员点单.
 * User: qubojie
 * Date: 2018/8/24
 * Time: 下午3:52
 */

namespace app\wechat\controller;

use app\admin\model\MstTable;
use app\admin\model\MstTableArea;
use app\admin\model\MstTableLocation;
use app\wechat\model\BillPayDetail;
use think\Db;
use think\Request;

class ManagePointList extends HomeAction
{
    /**
     * 订台可操作订单列表详情
     * @param Request $request
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function canRefundOrderList(Request $request)
    {
        $trid = $request->param("trid","");

        $billPayDetailModel = new BillPayDetail();

        $billPayDetailList = Db::name("bill_pay_detail")
            ->alias("bpd")
            ->join("dishes d","d.dis_id = bpd.dis_id")
            ->join("bill_pay bp","bp.pid = bpd.pid")
            ->where("bpd.trid",$trid)
            ->where("bp.sale_status","neq",config("order.bill_pay_sale_status")['cancel']['key'])
            ->field("d.dis_img")
            ->field('bpd.dis_id,sum(bpd.quantity) quantity')
            ->group("bpd.dis_id")
            ->having('sum(quantity)>0')
            ->select();

        for ($i = 0; $i < count($billPayDetailList); $i ++){

            $dis_id = $billPayDetailList[$i]['dis_id'];

            $dis_info = $billPayDetailModel
                ->where("dis_id",$dis_id)
                ->field("dis_name,price")
                ->find();

            $dis_info = json_decode(json_encode($dis_info),true);

            $billPayDetailList[$i]['dis_name'] = $dis_info['dis_name'];

            $billPayDetailList[$i]['price']    = $dis_info['price'];

        }

        return $this->com_return(true,config("params.SUCCESS"),$billPayDetailList);

    }

    /**
     * 选台列表
     * @param Request $request
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function selectionTableList(Request $request)
    {
        $tableModel = new MstTable();

        $tableLocationModel = new MstTableLocation();

        $tableAreaModel     = new MstTableArea();


        $table_location = $tableLocationModel
            ->where("is_delete","0")
            ->order("sort")
            ->select();

        $table_location = json_decode(json_encode($table_location),true);

        dump($table_location);die;

        $table_list = $tableModel
            ->alias("t")
            ->join("mst_table_area ta","ta.area_id = t.area_id")
            ->join("mst_table_location tl","tl.location_id = ta.location_id")
            ->where("t.is_delete","0")
            ->order('tl.location_id,ta.area_id,t.table_no')
            ->field("t.table_id,t.table_no,t.people_max,t.table_desc,t.sort,t.is_enable")
            ->field("ta.area_id,ta.area_title,ta.area_desc,ta.sid")
            ->field("tl.location_title")
            ->select();

        $table_list = json_decode(json_encode($table_list),true);
        dump($table_list);die;

    }
}