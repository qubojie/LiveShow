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
     * @return array
     */
    public function useQrCode(Request $request)
    {
        $qrCodeParam = $request->param("qrCodeParam", "");

        if (empty($qrCodeParam)) {
            return $this->com_return(false, config("params.PARAM_NOT_EMPTY"));
        }

        $prefix_arr = config("qrcode.prefix");
        $delimiter = config("qrcode.delimiter")['key'];

        $qrCodeParams = explode("$delimiter", $qrCodeParam);

        if ($qrCodeParams[0] == $prefix_arr[0]['key']) {
            //开台
            $trid = $qrCodeParams[1];

            $res  = $this->openTableAction($trid);


        } else {
            //使用礼券
            $res = $this->giftVoucherUse();

        }

        if ($res){

            return $this->com_return(true,config("params.SUCCESS"));

        }else{

            return $this->com_return(false,config("params.FAIL"));

        }

    }

    /**
     * 开台
     * @param $trid
     * @return bool
     */
    protected function openTableAction($trid)
    {
        $tableRevenueModel = new TableRevenue();

        $params = [
            "status"     => config("order.table_reserve_status")['already_open']['key'],
            "updated_at" => time()
        ];

        $is_ok = $tableRevenueModel
            ->where('trid',$trid)
            ->update($params);
        if ($is_ok !== false){
            return true;
        }else{
            return false;
        }
    }

    //礼券使用
    protected function giftVoucherUse()
    {

    }
}