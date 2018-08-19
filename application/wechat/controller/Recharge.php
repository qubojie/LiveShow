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

        $refill_lower_limit = getSysSetting("user_refill_lower_limit");

        if (empty($refill_lower_limit)){
            $refill_lower_limit = 200;
        }

        if ($amount < $refill_lower_limit){
            return $this->com_return(false,"充值金额不能低于".$refill_lower_limit);
        }

        $userInfo = $this->tokenGetUserInfo($token);

        if (empty($userInfo)){
            return $this->com_return(false,config("params.FAIL"));
        }

        $uid = $userInfo['uid'];

        $pay_type = config("order.pay_method")['wxpay']['key'];

        $publicAction = new PublicAction();

        $res = $publicAction->rechargePublicAction($uid,$amount,$cash_gift,$pay_type,$referrer_phone);

        return $res;

    }
}