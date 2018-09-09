<?php
/**
 * 预约点单.
 * User: qubojie
 * Date: 2018/8/19
 * Time: 上午10:38
 */
namespace app\wechat\controller;

use app\admin\controller\Common;
use app\common\controller\UUIDUntil;
use app\wechat\model\BillPay;
use app\wechat\model\BillPayDetail;
use think\Config;
use think\Db;
use think\Exception;
use think\Request;
use think\Validate;

class ReservationOrder extends CommonAction
{
    /**
     * 用户预约点单结算
     * @param Request $request
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function settlementOrder(Request $request)
    {
        $remember_token = $request->header("Token",'');

        //获取到预约人信息
        $userInfo = $this->tokenGetUserInfo($remember_token);

        $uid = $userInfo['uid'];

        $table_id       = $request->param('table_id','');//桌位id

        $turnover_limit = $request->param('turnover_limit',0);//最低消费  0表示无最低消费

        $date           = $request->param('date','');//日期

        $time           = $request->param('time','');//时间

        $sales_phone    = $request->param('sales_phone','');//营销电话

        $order_amount   = $request->param('order_amount','');//订单总额

        $dish_group     = $request->param("dish_group",'');//菜品集合

        $rule = [
            "table_id|桌位"          => "require",
            "turnover_limit|最低消费" => "require",
            "date|日期"              => "require",
            "time|时间"              => "require",
            "sales_phone|营销电话"    => "regex:1[3-8]{1}[0-9]{9}",
            "order_amount|订单总额"   => "require",
            "dish_group|菜品集合"     => "require",
        ];

        $check_data = [
            "table_id"       => $table_id,
            "turnover_limit" => $turnover_limit,
            "date"           => $date,
            "time"           => $time,
            "sales_phone"    => $sales_phone,
            "order_amount"   => $order_amount,
            "dish_group"     => $dish_group
        ];

        $validate = new Validate($rule);

        if (!$validate->check($check_data)){
            return $this->com_return(false,$validate->getError());
        }

        if ($turnover_limit > $order_amount){
            return $this->com_return(false,\config("params.DISHES")['LOW_ELIMINATION']);
        }

        $reserve_way = Config::get("order.reserve_way")['client']['key'];//预定途径  client  客户预订（小程序）    service 服务端预定（内部小程序）   manage 管理端预定（pc管理端暂保留）

        $pointListPublicObj = new PointListPublicAction();

        Db::startTrans();
        try{
            /*创建预约吧台订单信息 On*/
            $subscription = 0;
            $turnover     = 0;
            $trid = $pointListPublicObj->reservationOrderPublic("$sales_phone","$table_id","$date","$time","$subscription","$turnover_limit","$reserve_way","$uid","$turnover");

            if ($trid == false){
                return $this->com_return(false,\config("params.ABNORMAL_ACTION")."001");
            }
            /*创建预约吧台订单信息 Off*/

            /*创建消费单缴费单 On*/
            $sid = NULL;

            $type = \config("order.bill_pay_type")['consumption']['key'];

            $pay_offline_type = "";

            $pay_type = "wxpay";

            $sales_name = "";

            $pid = $pointListPublicObj->createBillPay("$trid","$uid","$sid","$sales_name","$type","$order_amount","$order_amount","$pay_type","$pay_offline_type");

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
                $billPayDetailReturn = $pointListPublicObj->createBillPayDetail($z_params);

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

                            $czBillPayDetailReturn = $pointListPublicObj->createBillPayDetail($cz_params);
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

                                $lBillPayDetailReturn = $pointListPublicObj->createBillPayDetail($little_params);

                                if ($lBillPayDetailReturn == false){
                                    return $this->com_return(false,\config("params.ABNORMAL_ACTION")."008");
                                }

                            }
                        }

                    }
                }

            }

            Db::commit();
            $orderInfo = $pointListPublicObj->pidGetOrderInfo($pid);
            return $this->com_return(true,\config("params.SUCCESS"),$orderInfo);
            /*创建菜品订单付款详情 Off*/

        }catch (Exception $e){
            Db::rollback();
            return $this->com_return(false,$e->getMessage());
        }
    }
}
