<?php
/**
 * 预约操作
 * User: qubojie
 * Date: 2018/7/30
 * Time: 下午2:02
 */
namespace app\wechat\controller;

use app\admin\model\TableRevenue;
use think\Controller;
use think\Request;

class QrCodeAction extends Controller
{
    /**
     * 二维码使用
     * @param Request $request
     * @return array|void
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function useQrCode(Request $request)
    {
        $qrCodeParam = $request->param("qrCodeParam", "");

        if (empty($qrCodeParam)) {
            return $this->com_return(false, config("params.PARAM_NOT_EMPTY"));
        }

        $prefix_arr = config("qrcode.prefix");//前缀配置数组
        $delimiter  = config("qrcode.delimiter")['key'];//分隔符

        $qrCodeParams = explode("$delimiter","$qrCodeParam");

        if ($qrCodeParams[0] == $prefix_arr[0]['key']) {
            //开台
            $trid = $qrCodeParams[1];

            $res  = $this->checkTableStatus($trid);//

            return $res;

        } else {
            //使用礼券
            $res = $this->giftVoucherUse();

            return $res;

        }

    }

    /**
     * 开台之前,信息验证
     * @param $trid
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    protected function checkTableStatus($trid){

        //获取开台信息
        $res  = $this->getOpenTableInfo($trid);

        if (empty($res)){
            return $this->com_return(false,config("params.OPEN_TABLE_STATUS")['QrCodeINVALID']);
        }

        $status = $res['status'];

        if ($status == config("order.table_reserve_status")['cancel']['key']){
            return $this->com_return(false,config("params.OPEN_TABLE_STATUS")['CANCELED']);
        }

        if ($status == config("order.table_reserve_status")['pending_payment']['key']){
            return $this->com_return(false,config("params.OPEN_TABLE_STATUS")['UNPAY']);
        }

        if ($status == config("order.table_reserve_status")['already_open']['key']){
            return $this->com_return(false,config("params.OPEN_TABLE_STATUS")['ALREADYOPEN']);
        }

        if ($status == config("order.table_reserve_status")['clear_table']['key']){
            return $this->com_return(false,config("params.OPEN_TABLE_STATUS")['ALREADYOPEN']);
        }

        return $this->com_return(true,config("SUCCESS"),$res);
    }


    /**
     * 获取当前台位信息
     * @param $trid
     * @return array|false|mixed|\PDOStatement|string|\think\Model
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getOpenTableInfo($trid)
    {
        $tableRevenueModel = new TableRevenue();

        $res = $tableRevenueModel
            ->alias("tr")
            ->join("user u","u.uid = tr.uid")
            ->join("manage_salesman ms","ms.sid = tr.ssid","LEFT")
            ->join("mst_table_area ta","ta.area_id = tr.area_id")
            ->join("mst_table_location tl","tl.location_id = ta.area_id")
            ->join("mst_table t","t.table_id = tr.table_id")
            ->join("mst_table_appearance tap","tap.appearance_id = t.appearance_id")
            ->field("tl.location_title")
            ->field("ta.area_title")
            ->field("tap.appearance_title")
            ->field("u.name user_name,u.phone user_phone")
            ->field("ms.sales_name,ms.phone sales_phone")
            ->field("tr.trid,tr.table_no,tr.status,tr.reserve_way,tr.reserve_time,tr.is_subscription,tr.subscription_type,tr.subscription,tr.created_at")
            ->where('tr.trid',$trid)
            ->find();

        $res = json_decode(json_encode($res),true);

        $res = _unsetNull($res);

        return $res;

    }


    //礼券使用
    protected function giftVoucherUse()
    {

    }
}