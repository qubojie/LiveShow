<?php
/**
 * Created by PhpStorm.
 * User: qubojie
 * Date: 2018/7/10
 * Time: 下午5:40
 */
namespace app\admin\controller;

use app\admin\model\User;
use app\wechat\model\UserAccount;
use app\wechat\model\UserAccountCashGift;
use app\wechat\model\UserAccountDeposit;
use think\Request;

class Count extends CommandAction
{
    /**
     * 数据统计
     * @param Request $request
     * @return array
     */
    public function index(Request $request)
    {
        //注册会员总数
        $userNum = $this->userNumCount();

        //开卡会员数量
        $openCardNum = $this->userNumCount('2');

        $keyType = $request->param('keyType','');

        $dateType = $request->param('dateType','today');//筛选类型 today:日;month:月;year:年

        //活跃用户
        $activeUserNum = $this->activeUserCount($dateType);

        //充值金额总数
        $rechargeMoneyNum = $this->rechargeMoneyCount($dateType);

        //押金总额总数
        $depositMoneyNum = $this->depositMoneyCount($dateType);

        //礼金总额统计
        $cashGiftMoneyNum = $this->cashGiftMoneyCount($dateType);

        $res = [
            'userNum'           => $userNum,
            'openCardNum'       => $openCardNum,
            'activeUserNum'     => $activeUserNum,
            'rechargeMoneyNum'  => $rechargeMoneyNum,
            'depositMoneyNum'   => $depositMoneyNum,
            'cashGiftMoneyNum'   => $cashGiftMoneyNum,
        ];

        //将数组中的value为 int 或者 float 转换为 string型
        $res = arrIntToString($res);

        return $this->com_return(true,config("params.SUCCESS"),$res);

    }

    /**
     * 会员数统计
     * @param string $user_status
     * @return int|string
     */
    public function userNumCount($user_status = "")
    {
        $userModel = new User();
        $where = [];
        if (!empty($user_status)){
            $where['user_status'] = ['eq',$user_status];
        }

        $num = $userModel
            ->where($where)
            ->count();
        return $num;
    }

    /**
     * 活跃用户
     * @param $dateType
     * @return int|string
     */
    public function activeUserCount($dateType)
    {
        $userModel = new User();

        $num = $userModel
            ->whereTime('lastlogin_time',$dateType)
            ->count();

        return $num;
    }

    /**
     * 充值总额统计
     * @param $dateType
     * @return int|string
     */
    public function rechargeMoneyCount($dateType)
    {
        $userAccountModel = new UserAccount();

        $sum = $userAccountModel
            ->whereTime('created_at',$dateType)
            ->where('action_type',config('user.account')['recharge']['key'])
            ->sum('balance');
        if (empty($sum)) $sum = 0;

        return $sum;
    }


    /**
     * 押金总额统计
     * @param $dateType
     * @return float|int
     */
    public function depositMoneyCount($dateType)
    {
        $userAccountDepositModel = new UserAccountDeposit();

        $sum = $userAccountDepositModel
            ->whereTime('created_at',$dateType)
            ->where('action_type',config('user.deposit')['pay']['key'])
            ->sum('deposit');

        if (empty($sum)) $sum = 0;

        return $sum;
    }


    /**
     * 会员礼金总额统计
     * @param $dateType
     * @return int
     */
    public function cashGiftMoneyCount($dateType)
    {
        $userAccountCashGiftModel = new UserAccountCashGift();

        $sum = $userAccountCashGiftModel
            ->whereTime('created_at',$dateType)
            ->where('action_type',config('user.gift_cash')['recommend_reward']['key'])
            ->whereOr('action_type',config('user.gift_cash')['exchange_plus']['key'])
            ->sum('cash_gift');

        if (empty($sum)) $sum = 0;

        return $sum;
    }
}