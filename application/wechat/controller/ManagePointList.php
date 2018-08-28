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
use app\wechat\model\BillPay;
use app\wechat\model\BillPayDetail;
use think\Db;
use think\Request;
use think\Validate;

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
    public function canActionOrderList(Request $request)
    {
        $trid = $request->param("trid","");

        $billPayDetailModel = new BillPayDetail();

        /*$billPayDetailList = Db::name("bill_pay_detail")
            ->alias("bpd")
            ->join("dishes d","d.dis_id = bpd.dis_id")
            ->join("bill_pay bp","bp.pid = bpd.pid")
            ->where("bpd.trid",$trid)
//            ->where("bp.sale_status","neq",config("order.bill_pay_sale_status")['cancel']['key'])
            ->where("bp.sale_status","eq",config("order.bill_pay_sale_status")['completed']['key'])
            ->field("d.dis_img")
            ->field('bpd.dis_id,sum(bpd.quantity) quantity')
            ->group("bpd.dis_id")
            ->having('sum(quantity)>0')
            ->select();*/

        $billPayModel = new BillPay();

        $dishInfo = $billPayModel
            ->alias("bp")
            ->join("bill_pay_detail bpd","bpd.pid = bp.pid")
            ->join("dishes d","d.dis_id = bpd.dis_id")
            ->where("bp.trid",$trid)
            ->where("bp.sale_status","eq",config("order.bill_pay_sale_status")['completed']['key'])
            ->where("bpd.parent_id","0")
            ->field("d.dis_img")
            ->field('bpd.dis_id,sum(bpd.quantity) quantity')
            ->group("bpd.dis_id")
            ->having('sum(quantity)>0')
            ->select();

        $dishInfo = json_decode(json_encode($dishInfo),true);

        dump($dishInfo);die;

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
     * @return array
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
            ->field("location_id,location_title")
            ->select();

        $info = json_decode(json_encode($table_location),true);

        for ($i = 0; $i < count($info); $i ++){
            $location_id = $info[$i]['location_id'];

            $table_area = $tableAreaModel
                ->where("location_id",$location_id)
                ->where("is_enable","1")
                ->where("is_delete","0")
                ->order("sort")
                ->field("area_id,area_title")
                ->select();

            $area_info = json_decode(json_encode($table_area),true);

            $info[$i]['area_info'] = $area_info;

            for ($n = 0; $n < count($area_info); $n ++){
                $area_id = $area_info[$n]['area_id'];
                $table_info = $tableModel
                    ->join("table_revenue tr","tr.table_id = t.table_id")
                    ->join("manage_salesman ms","ms.sid = tr.ssid","LEFT")
                    ->alias("t")
                    ->where("t.area_id",$area_id)
                    ->where("t.is_delete","0")
                    ->where("tr.status",config("order.table_reserve_status")['already_open']['key'])
                    ->order("t.sort")
                    ->field("tr.trid,t.table_id,tr.table_no,tr.ssid,tr.ssname,ms.phone")
                    ->select();
                $table_info = json_decode(json_encode($table_info),true);

                $info[$i]['area_info'][$n]['table_info'] = $table_info;
            }
        }

      return $this->com_return(true,config("params.SUCCESS"),$info);

    }

    /**
     * 工作人员点单
     * @param Request $request
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function createPointList(Request $request)
    {
        $trid         = $request->param("trid","");

        $order_amount = $request->param('order_amount','');//订单总额

        $dish_group   = $request->param("dish_group",'');//菜品集合

        $rule = [
            "trid|订台id"          => "require",
            "order_amount|订单总额" => "require",
            "dish_group|菜品集合"   => "require",
        ];

        $check_data = [
            "trid"         => $trid,
            "order_amount" => $order_amount,
            "dish_group"   => $dish_group
        ];

        $validate = new Validate($rule);

        if (!$validate->check($check_data)){
            return $this->com_return(false,$validate->getError());
        }

        $remember_token = $request->header("Token",'');

        //获取点单用户信息
        $manageInfo = $this->tokenGetManageInfo($remember_token);

        $sid  = $manageInfo['sid'];

        $pointListPublicObj = new PointListPublicAction();

        $pointListRes = $pointListPublicObj->pointListPublicAction("$trid","$sid","$order_amount","$dish_group","");

        /*生成支付二维码 on*/
        if ($pointListRes['result'] == true){
            $pid = $pointListRes['data']['pid'];



        }
        /*生成支付二维码 off*/


    }

    /**
     * 手动取消未支付订单
     * @param Request $request
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function cancelDishOrder(Request $request)
    {
        $pid = $request->param("pid","");//订单id

        $token = $request->header("Token");

        $manageInfo = $this->tokenGetManageInfo($token);

        $acton_user = $manageInfo["sales_name"];

        $pointListPublicObj = new PointListPublicAction();

        return $pointListPublicObj->cancelPointListPublicAction($acton_user,$pid);
    }
}