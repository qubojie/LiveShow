<?php
/**
 * Created by PhpStorm.
 * User: qubojie
 * Date: 2018/8/27
 * Time: 下午1:59
 */
namespace app\wechat\controller;

use app\admin\controller\Common;
use app\admin\model\Dishes;
use app\admin\model\DishesCategory;
use app\admin\model\TableRevenue;
use app\common\controller\UUIDUntil;
use app\wechat\model\BillPay;
use app\wechat\model\BillPayDetail;
use think\Config;
use think\Controller;
use think\Db;
use think\Exception;

class PointListPublicAction extends Controller
{
    /**
     * 菜品分类获取公共部分
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function dishTypePublic()
    {
        $dishCateGateModel = new DishesCategory();

        $list = $dishCateGateModel
            ->where("is_enable",1)
            ->where("is_delete",0)
            ->order("sort")
            ->field("cat_id,cat_name,cat_img")
            ->select();

        return $this->com_return(true,config("params.SUCCESS"),$list);
    }


    /**
     * 赠品下单公共部分
     * @param $trid
     * @param $sid
     * @param $order_amount
     * @param $dish_group
     * @param $type
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function giveDishPublicAction($trid,$sid,$order_amount,$dish_group,$type)
    {
        $tableRevenueModel = new TableRevenue();

        $revenue_column    = $tableRevenueModel->revenue_column;

        $table_revenue_info = $tableRevenueModel
            ->alias("tr")
            ->where("tr.trid",$trid)
            ->field($revenue_column)
            ->find();

        $table_revenue_info = json_decode(json_encode($table_revenue_info),true);

        if (empty($table_revenue_info)){

            return $this->com_return(false,config("params.ABNORMAL_ACTION")."(HOME-DD001)");

        }

        if ($table_revenue_info['status'] != config("order.table_reserve_status")['already_open']['key']){
            //如果不是开台状态,是不能点单
            return $this->com_return(false,config("params.ORDER")['NOW_STATUS_ERROR']."(HOME-DD002)");

        }

        Db::startTrans();
        try{

            /*创建消费单缴费单 On*/

            $pay_offline_type = "";

            $pay_type = "";

            $pointListPublicObj = new  PointListPublicAction();

            $uid = NULL;

            $pid = $pointListPublicObj->createBillPay("$trid",$uid,"$sid","$type","$order_amount","$order_amount","$pay_type","$pay_offline_type");

            if ($pid == false){
                return $this->com_return(false,\config("params.ABNORMAL_ACTION")."(HOME-DD003)");
            }
            /*创建消费单缴费单 Off*/

            /*创建菜品订单付款详情 On*/
            $this->createBillPayDetailAction($dish_group,$pid,$trid);
            /*创建菜品订单付款详情 Off*/

            Db::commit();
            $orderInfo = $pointListPublicObj->pidGetOrderInfo($pid);
            return $this->com_return(true,\config("params.SUCCESS"),$orderInfo);
        }catch (Exception $e){
            Db::rollback();
            return $this->com_return(false,$e->getMessage());
        }


    }


    /**
     * 点单公共部分
     * @param $trid
     * @param $sid
     * @param $order_amount
     * @param $dish_group
     * @param $pay_type
     * @param $type
     * @param $uid
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function pointListPublicAction($trid,$sid,$order_amount,$dish_group,$pay_type,$type = 0,$uid = NULL)
    {
        $tableRevenueModel = new TableRevenue();

        $revenue_column    = $tableRevenueModel->revenue_column;

        $table_revenue_info = $tableRevenueModel
            ->alias("tr")
            ->where("tr.trid",$trid)
            ->field($revenue_column)
            ->find();

        $table_revenue_info = json_decode(json_encode($table_revenue_info),true);

        if (empty($table_revenue_info)){

            return $this->com_return(false,config("params.ABNORMAL_ACTION")."(HOME-DD001)");

        }

        if ($table_revenue_info['status'] != config("order.table_reserve_status")['already_open']['key']){
            //如果不是开台状态,是不能点单
            return $this->com_return(false,config("params.ORDER")['NOW_STATUS_ERROR']."(HOME-DD002)");

        }

        Db::startTrans();
        try{
            /*创建消费单缴费单 On*/

//            $type = \config("order.bill_pay_type")['consumption']['key'];
            $pay_offline_type = "";

//            $pay_type = "wxpay";

            $pointListPublicObj = new  PointListPublicAction();

            $pid = $pointListPublicObj->createBillPay("$trid","$uid","$sid","$type","$order_amount","$order_amount","$pay_type","$pay_offline_type");

            if ($pid == false){
                return $this->com_return(false,\config("params.ABNORMAL_ACTION")."(HOME-DD003)");
            }
            /*创建消费单缴费单 Off*/

            /*创建菜品订单付款详情 On*/
            $this->createBillPayDetailAction($dish_group,$pid,$trid);
            /*创建菜品订单付款详情 Off*/

            Db::commit();
            $orderInfo = $pointListPublicObj->pidGetOrderInfo($pid);
            return $this->com_return(true,\config("params.SUCCESS"),$orderInfo);
        }catch (Exception $e){
            Db::rollback();
            return $this->com_return(false,$e->getMessage());
        }
    }

    /**
     * 创建菜品订单详情
     * @param $dish_group
     * @param $pid
     * @param $trid
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function createBillPayDetailAction($dish_group,$pid,$trid)
    {
        /*创建菜品订单付款详情 On*/
        $dish_group = json_decode($dish_group,true);

        for ($i = 0; $i < count($dish_group); $i ++){
            $dis_id   = $dish_group[$i]['dis_id'];

            $dis_type = $dish_group[$i]['dis_type'];

            $price    = $dish_group[$i]['price'];

            $quantity = $dish_group[$i]['quantity'];

            $dishInfo = $this->disIdGetDisInfo($dis_id);

            if (empty($dishInfo)){
                return $this->com_return(false,\config("params.ABNORMAL_ACTION")."(HOME-DD004)");
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
            $pointListPublicObj = new  PointListPublicAction();
            $billPayDetailReturn = $pointListPublicObj->createBillPayDetail($z_params);
            if ($billPayDetailReturn == false){
                return $this->com_return(false,\config("params.ABNORMAL_ACTION")."(HOME-DD005)");
            }

            if ($dis_type){
                //如果是套餐
                $dishes_combo = $dish_group[$i]['dishes_combo'];

                if (empty($dishes_combo)){
                    return $this->com_return(false,\config("params.DISHES")['COMBO_DIST_EMPTY']."(HOME-DD006)");
                }

                for ($m = 0; $m <count($dishes_combo); $m ++){
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
                        return $this->com_return(false,\config("params.ABNORMAL_ACTION")."(HOME-DD007)");
                    }

                    if ($sc_type){
                        //如果是套餐内换品组
                        $children = $dishes_combo[$m]['children'];
                        if (empty($children)){
                            return $this->com_return(false,\config("params.DISHES")['COMBO_ID_NOT_EMPTY']."(HOME-DD008)");
                        }

                        for ($n = 0; $n <count($children); $n ++){
                            $children_dis_id       = $children[$n]['dis_id'];
                            $children_quantity     = $children[$n]['quantity'];

                            $childrenDishInfo = $this->disIdGetDisInfo($children_dis_id);

                            if (empty($childrenDishInfo)){
                                return $this->com_return(false,\config("params.ABNORMAL_ACTION")."(HOME-DD009)");
                            }


                            $children_is_give  = $childrenDishInfo['is_give'];
                            $children_dis_sn   = $childrenDishInfo['dis_sn'];
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
                                return $this->com_return(false,\config("params.ABNORMAL_ACTION")."(HOME-DD010)");
                            }
                        }
                    }
                }
            }
        }

        /*创建菜品订单付款详情 Off*/
    }


    //创建消费单缴费单
    public function createBillPay($trid,$uid,$sid,$type,$order_amount,$payable_amount,$pay_type,$pay_offline_type)
    {
        $UUID         = new UUIDUntil();

        $pid          = $UUID->generateReadableUUID("P");

        if ($type == \config("order.bill_pay_type")['give']['key']){

            //如果是赠品 -> 待审核
            $sale_status  = \config("order.bill_pay_sale_status")['wait_audit']['key'];

        }else{

            $sale_status  = \config("order.bill_pay_sale_status")['pending_payment_return']['key'];

        }

        if ($pay_type == \config("order.pay_method")['offline']['key']){

            $sale_status = \config("order.bill_pay_sale_status")['wait_audit']['key'];

        }

        $nowTime      = time();

        $deal_time    = $nowTime;

        $return_point = intval($order_amount * 0.1);

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


    /**
     * 取消未支付点单公共部分
     * @param $pid
     * @param $acton_user
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function cancelPointListPublicAction($acton_user,$pid)
    {
        if (empty($pid)){
            return $this->com_return(false, config("params.ABNORMAL_ACTION")."QXDD001");
        }

        $billPayModel = new BillPay();

        $orderInfo = $billPayModel
            ->where("pid",$pid)
            ->find();

        $orderInfo = json_decode(json_encode($orderInfo),true);

        if (empty($orderInfo)){

            return $this->com_return(false,config("params.ORDER")['ORDER_ABNORMAL']."(HOME-QXDD002)");

        }

        $sale_status = $orderInfo['sale_status'];

        if ($sale_status == config("order.bill_pay_sale_status")['completed']['key']){
            //订单已支付不可取消
            return $this->com_return(false,config("params.ORDER")['STATUS_NO_CANCEL']."(HOME-QXDD003)");

        }

        //将订单改为交易取消状态
        $params= [
            "sale_status" => config("order.bill_pay_sale_status")['cancel']['key'],
            "cancel_user" => "$acton_user",
            "cancel_time" => time(),
            "auto_cancel" => 0,
            "cancel_reason" => "未支付,".$acton_user."手动取消",
            "updated_at" => time()
        ];

        $is_ok = $billPayModel
            ->where("pid",$pid)
            ->update($params);

        if ($is_ok !== false){

            return $this->com_return(true,config("params.SUCCESS"));

        }else{

            return $this->com_return(false,config("params.FAIL")."(HOME-QXDD004)");

        }
    }

    /**
     * 根据菜品id获取菜品信息
     * @param $dis_id
     * @return array|false|mixed|\PDOStatement|string|\think\Model
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function disIdGetDisInfo($dis_id)
    {
        $dishesModel = new Dishes();

        $dishesInfo = $dishesModel
            ->where('dis_id',$dis_id)
            ->field("dis_id,dis_type,dis_sn,dis_name,dis_img,dis_desc,cat_id,att_id,is_normal,normal_price,is_gift,gift_price,is_vip,is_give")
            ->find();

        $dishesInfo = json_decode(json_encode($dishesInfo),true);

        return $dishesInfo;
    }

}