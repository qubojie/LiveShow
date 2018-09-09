<?php
/**
 * 服务人员点单.
 * User: qubojie
 * Date: 2018/8/24
 * Time: 下午3:52
 */

namespace app\wechat\controller;

use app\admin\controller\Common;
use app\admin\model\MstTable;
use app\admin\model\MstTableArea;
use app\admin\model\MstTableLocation;
use app\wechat\model\BillPay;
use app\wechat\model\BillPayDetail;
use think\Db;
use think\Exception;
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
            ->group("bpd.dis_id")
            ->field("d.dis_img")
            ->field('bpd.dis_id,sum(bpd.quantity) quantity')
            ->having('sum(quantity)>0')
            ->select();

        $dishInfo = json_decode(json_encode($dishInfo),true);

        for ($i = 0; $i < count($dishInfo); $i ++){

            $dis_id = $dishInfo[$i]['dis_id'];

            $dis_info = $billPayDetailModel
                ->where("trid",$trid)
                ->where("dis_id",$dis_id)
                ->where("parent_id",'0')
                ->field("dis_name,price")
                ->find();

            $dis_info = json_decode(json_encode($dis_info),true);

            $dishInfo[$i]['dis_name'] = $dis_info['dis_name'];

            $dishInfo[$i]['price']    = $dis_info['price'];

        }

        return $this->com_return(true,config("params.SUCCESS"),$dishInfo);

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
            ->alias("tl")
            ->join("mst_table_area ta","ta.location_id = tl.location_id")
            ->join("table_revenue tr","tr.area_id = ta.area_id")
            ->where("tl.is_delete","0")
            ->where("tr.status",config("order.table_reserve_status")['already_open']['key'])
            ->order("tl.sort")
            ->group("tl.location_id")
            ->field("tl.location_id,tl.location_title")
            ->select();

        $info = json_decode(json_encode($table_location),true);

        for ($i = 0; $i < count($info); $i ++){

            $location_id = $info[$i]['location_id'];

            $table_area = $tableAreaModel
                ->alias("ta")
                ->join("table_revenue tr","tr.area_id = ta.area_id")
                ->where("ta.location_id",$location_id)
                ->where("ta.is_enable","1")
                ->where("ta.is_delete","0")
                ->where("tr.status",config("order.table_reserve_status")['already_open']['key'])
                ->order("ta.sort")
                ->group("ta.area_id")
                ->field("ta.area_id,ta.area_title")
                ->select();

            $area_info = json_decode(json_encode($table_area),true);

            $info[$i]['area_info'] = $area_info;

            for ($n = 0; $n < count($area_info); $n ++){
                $area_id = $area_info[$n]['area_id'];
                $table_info = $tableModel
                    ->join("table_revenue tr","tr.table_id = t.table_id")
                    ->join("manage_salesman ms","ms.sid = tr.ssid","LEFT")
                    ->join("user u",'u.uid = tr.uid','LEFT')
                    ->alias("t")
                    ->where("t.area_id",$area_id)
                    ->where("t.is_delete","0")
                    ->where("tr.status",config("order.table_reserve_status")['already_open']['key'])
                    ->whereNull("tr.parent_trid")
                   /* ->where(function ($query){
                        $query->where('parent_trid',['eq',''],['eq',NULL],'or');
                    })*/
                    ->order("t.sort")
                    ->field("tr.trid,tr.table_id,tr.table_no,tr.parent_trid,tr.ssid,tr.ssname,ms.phone sphone")
                    ->field("u.name,u.phone")
                    ->select();

                $table_info = json_decode(json_encode($table_info),true);

                if (!empty($table_info)){
                    for ($m = 0; $m < count($table_info); $m++){
                        $trid = $table_info[$m]['trid'];

                        $children = Db::name("table_revenue")
                            ->alias("tr")
                            ->join("manage_salesman ms","ms.sid = tr.ssid","LEFT")
                            ->join("user u",'u.uid = tr.uid','LEFT')
                            ->where("tr.parent_trid",$trid)
                            ->whereOr("tr.trid",$trid)
                            ->field("tr.trid,tr.table_id,tr.table_no,tr.parent_trid,tr.ssid,tr.ssname,ms.phone sphone")
                            ->field("u.name,u.phone")
                            ->select();

                        $children = json_decode(json_encode($children),true);

                        $table_info[$m]['children'] = $children;

                    }
                    $info[$i]['area_info'][$n]['table_info'] = $table_info;
                }



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

        $pay_type     = $request->param("pay_type",'');//支付方式

        $rule = [
            "trid|订台id"           => "require",
            "order_amount|订单总额" => "require",
            "dish_group|菜品集合"   => "require",
            "pay_type|支付方式"     => "require",
        ];

        $check_data = [
            "trid"         => $trid,
            "order_amount" => $order_amount,
            "dish_group"   => $dish_group,
            "pay_type"     => $pay_type
        ];

        $validate = new Validate($rule);

        if (!$validate->check($check_data)){
            return $this->com_return(false,$validate->getError());
        }

        $remember_token = $request->header("Token",'');

        //获取点单人员信息
        $manageInfo = $this->tokenGetManageInfo($remember_token);

        $sid  = $manageInfo['sid'];

        $sales_name = $manageInfo['sales_name'];
        $phone      = $manageInfo['phone'];

        $sales_name = $sales_name.",".$phone;

        $type = config("order.bill_pay_type")['consumption']['key'];

        $pointListPublicObj = new PointListPublicAction();

        Db::startTrans();
        try{

            $pointListRes = $pointListPublicObj->pointListPublicAction("$trid","$sid","$sales_name","$order_amount","$dish_group","$pay_type","$type","");

            if (isset($pointListRes["result"]) && $pointListRes["result"] == true){

                /*生成支付二维码 on*/
                $pid = $pointListRes['data']['pid'];

                Db::commit();
                return $this->com_return(true,config("params.SUCCESS"),$pointListRes);


                /*生成支付二维码 off*/
            }else{
                return $pointListRes;
            }


        }catch (Exception $e){
            Db::rollback();
            return $this->com_return(false,$e->getMessage());
        }
    }

    /**
     * 赠品点单
     * @param Request $request
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function giveDishOrder(Request $request)
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

        //获取点单人员信息
        $manageInfo = $this->tokenGetManageInfo($remember_token);

        $sid         = $manageInfo['sid'];
        $sales_name  = $manageInfo['sales_name'];
        $sphone      = $manageInfo['phone'];
        $sales_name = $sales_name.",".$sphone;

        $pointListPublicObj = new PointListPublicAction();

        $type = config("order.bill_pay_type")['give']['key'];//赠送单

        $pointListRes = $pointListPublicObj->giveDishPublicAction($trid,$sid,$sales_name,$order_amount,$dish_group,$type);

        if (isset($pointListRes["result"]) && $pointListRes["result"] == true){

            Db::commit();
            return $this->com_return(true,config("params.SUCCESS"),$pointListRes);

        }else{
            return $pointListRes;
        }
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

    /**
     * 检测订单支付状态
     * @param Request $request
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function checkOrderStatus(Request $request)
    {
        $pid = $request->param("vid","");//订单id

        if (empty($pid)){
            return $this->com_return(false,config("params.PARAM_NOT_EMPTY"));
        }

        $billPayModel = new BillPay();

        $billInfo = $billPayModel
            ->where("pid",$pid)
            ->field("sale_status,deal_amount")
            ->find();

        $billInfo = json_decode(json_encode($billInfo),true);

        if (empty($billInfo)){
            return $this->com_return(false,config("params.ORDER")['ORDER_ABNORMAL']);
        }

        return $this->com_return(true,config("params.SUCCESS"),$billInfo);

        /*if ($sale_status == config("order.bill_pay_sale_status")['completed']['key']){
            return $this->com_return(true,config("params.ORDER")['PAY_SUCCESS']);
        }

        if ($sale_status == config("order.bill_pay_sale_status")['cancel']['key']){
            return $this->com_return(false,config("params.ORDER")['ORDER_CANCEL']);
        }

        return $this->com_return(true,config("params.ORDER")['WAIT_RESULT']);*/
    }
}