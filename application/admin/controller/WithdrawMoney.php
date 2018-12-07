<?php
/**
 * Created by PhpStorm.
 * User: qubojie
 * Date: 2018/10/11
 * Time: 下午12:19
 */
namespace app\admin\controller;

use think\Controller;
use think\Request;

import("wxpay.WxPayCompanyToMember");

class WithdrawMoney extends Controller
{
    /**
     * 提现至零钱测试
     * @param Request $request
     */
    public function txTest(Request $request)
    {
        $openid     = "oDgH15XItjhNxUBH6DdnnVHEcKdM";
        $order_sn   = "P2018091009100222";
        $amount     = "1";
        $desc       = "提现至零钱测试";
        $wxPayObj   = new \WxPayCompanyToMember();
        $res        = $wxPayObj->wxPayToPocket("$openid","$order_sn","$amount","$desc");
        dump($res);die;
    }

    /**
     * 提现至银行卡
     * @param Request $request
     */
    public function txToBank(Request $request)
    {
        $enc_bank_no   = [
            'enc_bank_no'   => '6210985131008316031',//收款方银行卡账号
            'enc_true_name' => '屈博杰',//收款方用户名
            'bank_code'     => '1003'//收款方开户行
        ];
        $enc_true_name = "建设银行";
        $order_sn      = "P2018091009100333";
        $amount        = "1";
        $desc          = "提现至银行卡测试";
        $wxPayObj      = new \WxPayCompanyToMember();
        $res           = $wxPayObj->wxPayToBank($enc_bank_no,"$enc_true_name","$order_sn","$amount","$desc");
        dump($res);die;
    }
}