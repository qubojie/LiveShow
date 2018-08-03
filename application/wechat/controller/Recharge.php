<?php
/**
 * Created by PhpStorm.
 * User: qubojie
 * Date: 2018/8/3
 * Time: 下午2:23
 */
namespace app\wechat\controller;

use app\admin\model\ManageSalesman;
use app\admin\model\MstRefillAmount;
use app\common\controller\UUIDUntil;
use app\wechat\model\BillRefill;
use think\Request;
use think\Validate;

class Recharge extends CommonAction
{
    /**
     * 充值金额列表获取
     * @param Request $request
     * @return array
     * @throws \think\exception\DbException
     */
    public function rechargeList(Request $request)
    {
        $refillAmountModel = new MstRefillAmount();

        $pagesize = $request->param("pagesize",config('PAGESIZE'));//显示个数,不传时为10

        if (empty($pagesize)) $pagesize = config('PAGESIZE');

        $nowPage = $request->param("nowPage","1");

        $count = $refillAmountModel->count();//总记录数

        $pageNum = ceil($count/$pagesize); //总页数

        $config = [
            "page" => $nowPage,
        ];

        $xcx_column = $refillAmountModel->xcx_column;

        $list = $refillAmountModel
            ->order("sort")
            ->field($xcx_column)
            ->where("is_enable",1)
            ->paginate($pagesize,false,$config);

        return $this->com_return(true,config("params.SUCCESS"),$list);
    }


    /**
     * 充值确认
     * @param Request $request
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function rechargeConfirm(Request $request)
    {
        //生成充值订单
        $token          = $request->header("Token","");

        $amount         = $request->param("amount","");//充值金额

        $cash_gift      = $request->param("cash_gift","");//赠送礼金数

        $referrer_phone = $request->param("referrer_phone","");//营销电话

        $rule = [
            "amount|充值金额"      => "require|number|max:20|gt:0",
            "cash_gift|赠送礼金数" => "require|number|max:20|egt:0",
        ];

        $request_res = [
            "amount"    => $amount,
            "cash_gift" => $cash_gift,
        ];

        $validate = new Validate($rule);

        if (!$validate->check($request_res)){
            return $this->com_return(false,$validate->getError(),null);
        }

        $manageSalesmanModel = new ManageSalesman();

        $referrer_id   = config("salesman.salesman_type")[3]['key'];
        $referrer_type = config("salesman.salesman_type")[3]['name'];

        if (!empty($referrer_phone)){
            //根据电话号码获取推荐营销信息
            $manageInfo = $manageSalesmanModel
                ->alias('ms')
                ->join('mst_salesman_type mst','mst.stype_id = ms.stype_id')
                ->where('ms.phone',$referrer_phone)
                ->where('ms.statue',config("salesman.salesman_status")['working']['key'])
                ->field('ms.sid,mst.stype_key')
                ->find();

            $manageInfo = json_decode(json_encode($manageInfo),true);

            if (!empty($manageInfo)){

                //只给营销记录,其他都算平台
                if ($manageInfo['stype_key'] == config("salesman.salesman_type")[0]['key'] ||$manageInfo['stype_key'] == config("salesman.salesman_type")[0]['key'] ) {
                    $referrer_id   = $manageInfo['sid'];
                    $referrer_type = $manageInfo['stype_key'];
                }
            }
        }

        $userInfo = $this->tokenGetUserInfo($token);

        if (empty($userInfo)){
            return $this->com_return(false,config("params.FAIL"));
        }

        $uid = $userInfo['uid'];

        $pay_type = config("order.pay_method")['wxpay']['key'];

        $status = config("order.recharge_status")['pending_payment']['key'];

        $time = time();

        $UUID = new UUIDUntil();

        //插入用户充值单据表
        $rfid = $UUID->generateReadableUUID("RF");

        $billRefillParams = [
            "rfid"          => $rfid,
            "referrer_type" => $referrer_type,
            "referrer_id"   => $referrer_id,
            "uid"           => $uid,
            "pay_type"      => $pay_type,
            "amount"        => $amount,
            "cash_gift"     => $cash_gift,
            "status"        => $status,
            "created_at"    => $time,
            "updated_at"    => $time
        ];

        $billRefillModel = new BillRefill();

        $res = $billRefillModel
            ->insert($billRefillParams);

        $return_data = [
            "rfid"   => $rfid,
            "amount" => $amount
        ];

        if ($res){
            return $this->com_return(true,config("params.SUCCESS"),$return_data);

        }else{
            return $this->com_return(false,config("params.FAIL"));
        }
    }
}