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


        if (empty($trid)){

            return $this->com_return(false, config("params.ABNORMAL_ACTION")."DD001");

        }

        $tableRevenueModel = new TableRevenue();

        $revenue_column    = $tableRevenueModel->revenue_column;

        $table_revenue_info = $tableRevenueModel
            ->alias("tr")
            ->where("tr.trid",$trid)
            ->field($revenue_column)
            ->find();

        $table_revenue_info = json_decode(json_encode($table_revenue_info),true);

        if (empty($table_revenue_info)){

            return $this->com_return(false,config("params.ABNORMAL_ACTION")."DD002");

        }


        if ($table_revenue_info['status'] != config("order.table_reserve_status")['already_open']['key']){
            //如果不是开台状态,是不能点单
            return $this->com_return(false,config("params.ORDER")['NOW_STATUS_ERROR']);

        }

        $remember_token = $request->header("Token",'');

        //获取当前用户信息
        $userInfo = $this->tokenGetUserInfo($remember_token);

        $uid = $userInfo['uid'];

        Db::startTrans();

        try{
            /*创建消费单缴费单 On*/
            $sid = NULL;

            $type = \config("order.bill_pay_type")['consumption']['key'];

            $pay_offline_type = "";

            $pay_type = "wxpay";

            $reservationOrderObj = new ReservationOrder();

            $pid = $reservationOrderObj->createBillPay("$trid","$uid","$sid","$type","$order_amount","$order_amount","$pay_type","$pay_offline_type");

            if ($pid == false){
                return $this->com_return(false,\config("params.ABNORMAL_ACTION")."002");
            }
            /*创建消费单缴费单 Off*/

            /*创建菜品订单付款详情 On*/

            $dish_group = json_decode($dish_group,true);

            for ($i = 0; $i < count($dish_group); $i ++){

                $dis_id   = $dish_group[$i]['dis_id'];

                $dis_type = $dish_group[$i]['dis_type'];

                $price    = $dish_group[$i]['price'];

                $quantity = $dish_group[$i]['quantity'];

                $dishInfo = $this->disIdGetDisInfo($dis_id);

                if (empty($dishInfo)){
                    return $this->com_return(false,\config("params.ABNORMAL_ACTION")."003");
                }

                $dis_name  = $dishInfo['dis_name'];
                $dis_sn    = $dishInfo['dis_sn'];
                $dis_desc  = $dishInfo['dis_desc'];
                $is_give   = $dishInfo['is_give'];
                $parent_id = 0;

                $z_params = [
                    "parent_id" => $parent_id,
                    "pid"       => $pid,
                    "trid"      => $trid,
                    "is_give"   => $is_give,
                    "dis_id"    => $dis_id,
                    "dis_type"  => $dis_type,
                    "dis_sn"    => $dis_sn,
                    "dis_name"  => $dis_name,
                    "dis_desc"  => $dis_desc,
                    "quantity"  => $quantity,
                    "price"     => $price
                ];

                //创建主信息
                $billPayDetailReturn = $reservationOrderObj->createBillPayDetail($z_params);

                if ($billPayDetailReturn == false){
                    return $this->com_return(false,\config("params.ABNORMAL_ACTION")."004");
                }

                if ($dis_type){
                    $dishes_combo = $dish_group[$i]['dishes_combo'];

                    if (empty($dishes_combo)){
                        return $this->com_return(false,\config("params.DISHES")['COMBO_DIST_EMPTY']);
                    }

                    for ($m = 0; $m <count($dishes_combo); $m ++){
//                        $combo_id    = $dishes_combo[$m]['combo_id'];
//                        $main_dis_id = $dishes_combo[$m]['main_dis_id'];
                        $sc_dis_id   = $dishes_combo[$m]['dis_id'];
                        $sc_type     = $dishes_combo[$m]['type'];
                        $sc_quantity = $dishes_combo[$m]['quantity'];

                        $scDisInfo = $this->disIdGetDisInfo($sc_dis_id);

                        if (!empty($scDisInfo)){
                            $sc_dis_name = $scDisInfo['dis_name'];
                            $sc_dis_sn   = $scDisInfo['dis_sn'];
                            $sc_is_give  = $scDisInfo['is_give'];
                            $sc_dis_desc = $scDisInfo['dis_desc'];

                            $cz_params = [
                                "parent_id" => $billPayDetailReturn,
                                "pid"       => $pid,
                                "trid"      => $trid,
                                "is_give"   => $sc_is_give,
                                "dis_id"    => $sc_dis_id,
                                "dis_type"  => $sc_type,
                                "dis_sn"    => $sc_dis_sn,
                                "dis_name"  => $sc_dis_name,
                                "dis_desc"  => $sc_dis_desc,
                                "quantity"  => $sc_quantity,
                                "price"     => 0
                            ];

                            $czBillPayDetailReturn = $reservationOrderObj->createBillPayDetail($cz_params);
                        }else{
                            $czBillPayDetailReturn = 0;
                        }

                        if ($czBillPayDetailReturn === false){
                            return $this->com_return(false,\config("params.ABNORMAL_ACTION")."006");
                        }

                        if ($sc_type){

                            $children = $dishes_combo[$m]['children'];

                            if (empty($children)){
                                return $this->com_return(false,\config("params.DISHES")['COMBO_ID_NOT_EMPTY']);
                            }

                            for ($n = 0; $n <count($children); $n ++){
//                                $children_combo_id     = $children[$n]['combo_id'];
//                                $children_main_dis_id  = $children[$n]['main_dis_id'];
//                                $children_parent_id    = $children[$n]['parent_id'];
                                $children_dis_id       = $children[$n]['dis_id'];
                                $children_quantity     = $children[$n]['quantity'];

                                $childrenDishInfo = $this->disIdGetDisInfo($children_dis_id);

                                if (empty($childrenDishInfo)){
                                    return $this->com_return(false,\config("params.ABNORMAL_ACTION")."007");
                                }

                                $children_is_give = $childrenDishInfo['is_give'];
                                $children_dis_sn = $childrenDishInfo['dis_sn'];
                                $children_dis_name = $childrenDishInfo['dis_name'];
                                $children_dis_desc = $childrenDishInfo['dis_desc'];

                                $little_params = [
                                    "parent_id" => $billPayDetailReturn,
                                    "pid"       => $pid,
                                    "trid"      => $trid,
                                    "is_give"   => $children_is_give,
                                    "dis_id"    => $children_dis_id,
                                    "dis_sn"    => $children_dis_sn,
                                    "dis_name"  => $children_dis_name,
                                    "dis_desc"  => $children_dis_desc,
                                    "quantity"  => $children_quantity,
                                    "price"     => 0
                                ];

                                $lBillPayDetailReturn = $reservationOrderObj->createBillPayDetail($little_params);

                                if ($lBillPayDetailReturn == false){
                                    return $this->com_return(false,\config("params.ABNORMAL_ACTION")."008");
                                }

                            }
                        }

                    }
                }

            }
            /*创建菜品订单付款详情 On*/
            Db::commit();
            $orderInfo = $reservationOrderObj->pidGetOrderInfo($pid);
            return $this->com_return(true,\config("params.SUCCESS"),$orderInfo);

        }catch (Exception $e){
            Db::rollback();
            return $this->com_return(false,$e->getMessage());
        }
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

        if (empty($pid)){
            return $this->com_return(false, config("params.ABNORMAL_ACTION")."QXDD001");
        }

        $billPayModel = new BillPay();

        $orderInfo = $billPayModel
            ->where("pid",$pid)
            ->find();

        $orderInfo = json_decode(json_encode($orderInfo),true);

        if (empty($orderInfo)){

            return $this->com_return(false,config("params.ORDER")['ORDER_ABNORMAL']);

        }

        $sale_status = $orderInfo['sale_status'];

        if ($sale_status == config("order.bill_pay_sale_status")['completed']['key']){
            //订单已支付不可取消
            return $this->com_return(false,config("params.ORDER")['STATUS_NO_CANCEL']);

        }

        //将订单改为交易取消状态
        $params= [
            "sale_status" => config("order.bill_pay_sale_status")['cancel']['key'],
            "cancel_user" => "user",
            "cancel_time" => time(),
            "auto_cancel" => 0,
            "cancel_reason" => "未支付,用户自己手动取消",
            "updated_at" => time()
        ];

        $is_ok = $billPayModel
            ->where("pid",$pid)
            ->update($params);

        if ($is_ok !== false){

            return $this->com_return(true,config("params.SUCCESS"));

        }else{

            return $this->com_return(false,config("params.FAIL"));

        }
    }

}