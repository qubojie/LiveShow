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

        Db::startTrans();
        try{
            /*创建预约吧台订单信息 On*/
            $subscription = 0;
            $turnover     = $order_amount;
            $trid = $this->reservationOrderPublic("$sales_phone","$table_id","$date","$time","$subscription","$turnover_limit","$reserve_way","$uid","$turnover");

            if ($trid == false){
                return $this->com_return(false,\config("params.ABNORMAL_ACTION")."001");
            }
            /*创建预约吧台订单信息 Off*/

            /*创建消费单缴费单 On*/
            $sid = NULL;

            $type = \config("order.bill_pay_type")['consumption']['key'];

            $pay_offline_type = "";

            $pay_type = "wxpay";

            $pid = $this->createBillPay("$trid","$uid","$sid","$type","$order_amount","$order_amount","$pay_type","$pay_offline_type");

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
                $billPayDetailReturn = $this->createBillPayDetail($z_params);

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

                            $czBillPayDetailReturn = $this->createBillPayDetail($cz_params);
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

                                $lBillPayDetailReturn = $this->createBillPayDetail($little_params);

                                if ($lBillPayDetailReturn == false){
                                    return $this->com_return(false,\config("params.ABNORMAL_ACTION")."008");
                                }

                            }
                        }

                    }
                }

            }

            Db::commit();
            $orderInfo = $this->pidGetOrderInfo($pid);
            return $this->com_return(true,\config("params.SUCCESS"),$orderInfo);
            /*创建菜品订单付款详情 Off*/

        }catch (Exception $e){
            Db::rollback();
            return $this->com_return(false,$e->getMessage());
        }
    }

    /**
     * 预约点单(创建预约吧台订单信息)
     * @param $sales_phone
     * @param $table_id
     * @param $date
     * @param $time
     * @param $subscription
     * @param $turnover_limit
     * @param $reserve_way
     * @param $uid
     * @param $turnover '订单金额'
     * @return array|bool|string
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function reservationOrderPublic($sales_phone,$table_id,$date,$time,$subscription,$turnover_limit,$reserve_way,$uid,$turnover = 0)
    {
        //如果报名营销验证营销是否存在
        if (!empty($sales_phone)){
            $salesmanInfo  = $this->phoneGetSalesmanInfo($sales_phone);

            if (empty($salesmanInfo)){
                //如果未查到,提示用户核对手机号码
                return $this->com_return(false,config("params.SALESMAN_PHONE_ERROR"));
            }

            $ssid   = $salesmanInfo['sid'];
            $ssname = $salesmanInfo['sales_name'];

        }else{
            //如果营销电话为空,则隶属平台数据
            $ssid   = \config("salesman.salesman_type")['3']['key'];
            $ssname = \config("salesman.salesman_type")['3']['name'];
        }

        //判断当前桌子是否可预约
        $publicObj = new PublicAction();
        $is_can_reserve = $publicObj->tableStatusCan("$table_id","$date");

        if (!$is_can_reserve){
            //false时 该吧台当天已被其他顾客预约
            return $this->com_return(false,\config("params.TABLE_IS_RESERVE"));
        }

        $date = date("Y-m-d",$date);

        $reserve_date = $date." ".$time;

        //预约时间
        $reserve_time = strtotime($reserve_date);

        $UUID = new UUIDUntil();

        $trid = $UUID->generateReadableUUID("T");

        $status            = Config::get("order.table_reserve_status")['pending_payment']['key'];
        $is_subscription   = 1;
        $subscription_type = Config::get("order.subscription_type")['order']['key'];
        //去创建预约吧台订单信息
        $createRevenueReturn = $publicObj->createRevenueOrder("$trid","$uid","$ssid","$ssname","$table_id","$status","$turnover_limit","$reserve_way","$reserve_time","$is_subscription","$subscription_type","$subscription","$turnover");

        if ($createRevenueReturn){
            return $trid;
        }else{
            return false;
        }
    }

    //创建消费单缴费单
    public function createBillPay($trid,$uid,$sid,$type,$order_amount,$payable_amount,$pay_type,$pay_offline_type)
    {
        $UUID         = new UUIDUntil();

        $pid          = $UUID->generateReadableUUID("P");

        $sale_status  = \config("order.bill_pay_sale_status")['pending_payment_return']['key'];

        $nowTime      = time();

        $deal_time    = $nowTime;

        $return_point = $order_amount * 0.1;

        $params = [
            "pid"               => $pid,
            "trid"              => $trid,
            "uid"               => $uid,
            "sid"               => $sid,
            "type"              => $type,
            "sale_status"       => $sale_status,
            "deal_time"         => $deal_time,
            "order_amount"      => $order_amount,
            "payable_amount"    => $payable_amount,
            "return_point"      => $return_point,
            "pay_type"          => $pay_type,
            "pay_offline_type"  => $pay_offline_type,
            "created_at"        => $nowTime,
            "updated_at"        => $nowTime
        ];

        $billPayModel = new BillPay();

        $is_ok = $billPayModel
            ->insert($params);

        if ($is_ok){
            return $pid;
        }else{
            return false;
        }

    }

    //创建菜品订单付款详情
    public function createBillPayDetail($params)
    {
        $billPayDetailModel = new BillPayDetail();

        /*$params = [
            "parent_id" => $parent_id,//当菜品为套餐时 记录套餐内菜品信息
            "pid"       => $pid,//缴费单id
            "trid"      => $trid,//台位预定id  前缀T
            "is_refund" => $is_refund,//是否是退货数据   0否   1是
            "is_give"   => $is_give,//是否赠送 0否  1是
            "dis_id"    => $dis_id,//菜品id
            "dis_type"  => $dis_type,//菜品类型  0 单品    1 套餐
            "dis_sn"    => $dis_sn,//菜品唯一编码
            "dis_name"  => $dis_name,//菜品名称
            "dis_desc"  => $dis_desc,//菜品规格属性描述
            "quantity"  => $quantity,//数量
            "price"     => $price,
            "amount"    => $amount,//销售金额
        ];*/

        $id = $billPayDetailModel
            ->insertGetId($params);

        if ($id){
            return $id;
        }else{
            return false;
        }
    }

    /**
     * 根据订单id获取点单信息
     * @param $pid
     * @return array|false|mixed|\PDOStatement|string|\think\Model
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function pidGetOrderInfo($pid)
    {
        $billPayModel = new BillPay();

        $list = $billPayModel
            ->where("pid",$pid)
            ->field("pid,type,sale_status,deal_time,return_point,order_amount")
            ->find();

        $list = json_decode(json_encode($list),true);

        $billPayDetailModel = new BillPayDetail();

        $bpd_column = [
            "bpd.id",
            "bpd.parent_id",
            "bpd.pid",
            "bpd.trid",
            "bpd.is_refund",
            "bpd.is_give",
            "bpd.dis_id",
            "bpd.dis_type",
            "bpd.dis_sn",
            "bpd.dis_name",
            "bpd.dis_desc",
            "bpd.quantity",
            "bpd.price",
            "bpd.amount"
        ];

        $info = $billPayDetailModel
            ->alias("bpd")
            ->join("dishes d","d.dis_id = bpd.dis_id")
            ->where("bpd.pid",$pid)
            ->field("d.dis_img")
            ->field($bpd_column)
            ->select();

        $info = json_decode(json_encode($info),true);

        $commonObj = new Common();

        $info = $commonObj->make_tree($info,"id","parent_id");

        $list['dish_info'] = $info;


        return $list;

    }
}
