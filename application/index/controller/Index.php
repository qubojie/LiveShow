<?php
namespace app\index\controller;

use app\admin\controller\Common;
use app\admin\model\MstCardVip;
use app\admin\model\MstCardVipGiftRelation;
use app\admin\model\MstGift;
use app\admin\model\User;
use app\wechat\model\BillCardFees;
use app\wechat\model\BillCardFeesDetail;
use app\wechat\model\BillPayDetail;
use think\Config;
use think\Controller;
use think\Db;
use think\Request;

class Index extends Controller
{
    public function index()
    {
        return $this->fetch();
    }

    public function test(Request $request)
    {
        $pid = "P180819183125682237D";
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


        dump($tableInfo);

    }

}
