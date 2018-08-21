<?php
/**
 * Created by PhpStorm.
 * User: qubojie
 * Date: 2018/7/6
 * Time: 下午5:04
 */
namespace app\wechat\controller;

use app\admin\model\MstTableImage;
use app\admin\model\MstUserLevel;
use app\admin\model\TableRevenue;
use app\admin\model\User;
use app\services\Sms;
use app\wechat\model\BillCardFees;
use app\wechat\model\JobUser;
use app\wechat\model\UserAccount;
use app\wechat\model\UserAccountCashGift;
use app\wechat\model\UserCard;
use app\wechat\model\UserGiftVoucher;
use think\Db;
use think\Request;
use wxpay\Refund;

class MyInfo extends CommonAction
{
    /**
     * 用户中心
     * @param Request $request
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function index(Request $request)
    {
        $token = $request->header('Token','');

        $userModel = new User();
        $giftVoucherModel = new UserGiftVoucher();
        $levelModel = new MstUserLevel();
        $jobUserModel = new JobUser();


        $user_info = $userModel
            ->where('remember_token',$token)
            ->field($userModel->column)
            ->field('created_at',true)
            ->find();

        $uid         = $user_info['uid'];
        $user_status = $user_info['user_status'];

        $userJobInfo = $jobUserModel
            ->where('uid',$uid)
            ->field("job_balance,job_freeze,job_cash,consume_amount,referrer_num")
            ->find();

        $userJobInfo = json_decode(json_encode($userJobInfo),true);

        if (!empty($userJobInfo)){
            foreach ($userJobInfo as $key => $val){
                $user_info["$key"] = $val;
            }
        }else{
            $user_info["job_balance"] = 0;
            $user_info["job_freeze"] = 0;
            $user_info["job_cash"] = 0;
            $user_info["consume_amount"] = 0;
            $user_info["referrer_num"] = 0;
        }

        if ($user_status == 2){
            //获取用户的开卡信息
            $userCardModel = new UserCard();
            $cardInfo = $userCardModel
                ->alias("uc")
                ->join("mst_card_vip cv","cv.card_id = uc.card_id")
                ->field("cv.card_id,cv.card_type,cv.card_name,cv.card_image,cv.card_amount,cv.card_deposit,cv.card_desc,cv.card_equities,uc.created_at open_card_time")
                ->where('uc.uid',$uid)
                ->find();
            $cardInfo = json_decode(json_encode($cardInfo),true);

            if (!empty($cardInfo)){
                foreach ($cardInfo as $key => $val){
                    $user_info["$key"] = $val;
                }
            }
        }

        $level_info =$levelModel
            ->where('level_id', $user_info['level_id'])
            ->field('level_name,level_desc,level_img,point_min')
            ->find();

        $user_info['level_name'] = $level_info['level_name'];
        $user_info['level_desc'] = $level_info['level_desc'];
        $user_info['level_img']  = $level_info['level_img'];
        $user_info['point_min']  = $level_info['point_min'];

        //获取用户礼券数量
        $gift_voucher_num = $giftVoucherModel
            ->where('uid',$uid)
            ->count();

        $user_info['gift_voucher_num'] = $gift_voucher_num;

        return $this->com_return(true,config("SUCCESS"),$user_info);
    }

    /**
     * 我的钱包明细
     * @param Request $request
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function wallet(Request $request)
    {
        $token            = $request->header('Token','');
        $userModel        = new User();
        $cashGiftModel    = new UserAccountCashGift();
        $userAccountModel = new UserAccount();

        $uid_res = $userModel
            ->where('remember_token',$token)
            ->field('uid')
            ->find();

        $uid = $uid_res['uid'];

        $cash_gift = $cashGiftModel
            ->where('uid',$uid)
            ->select();

        $account = $userAccountModel
            ->where('uid',$uid)
            ->select();

        $res['account']   = $account;
        $res['cash_gift'] = $cash_gift;

        return $this->com_return(true,config("SUCCESS"),$res);
    }


    /**
     * 礼品券列表
     * @param Request $request
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function giftVoucher(Request $request)
    {
        $token  = $request->header('Token','');
        $status = $request->param('status','0');//礼券状态  0有效待使用  1 已使用  9已过期

        $userModel = new User();

        $userGiftVoucherModel = new UserGiftVoucher();

        $uid_res = $userModel
            ->where('remember_token',$token)
            ->field('uid')
            ->find();

        $uid = $uid_res['uid'];

        $res = $userGiftVoucherModel
            ->where('uid',$uid)
            ->where('status',$status)
            ->select();

        return $this->com_return(true,config("SUCCESS"),$res);
    }

    /**
     * 用户获取开卡未支付订单信息
     * @param Request $request
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getUserOpenCardInfo(Request $request)
    {
        $cardCallbackObj = new CardCallback();

        $token  = $request->header('Token','');
        $status = 0;

        //获取用户信息
        $user_info = $this->tokenGetUserInfo($token);

        if (empty($user_info)){
            return $this->com_return(false,config("FAIL"));
        }

        $uid = $user_info['uid'];

        $res = $cardCallbackObj->getUserCardInfo($uid,$status);

        return $this->com_return(true,config("SUCCESS"),$res);

    }

    /**
     * 验证验证码
     * @param $phone
     * @param $code
     * @return array
     */
    public function checkVerifyCode($phone,$code)
    {
        $sms = new Sms();

        $res = $sms->checkVerifyCode($phone,$code);

        return $res;
    }

    /**
     * 我的预约列表
     * @param Request $request
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function reservationOrder(Request $request)
    {

        $pagesize = $request->param("pagesize",config('PAGESIZE'));//显示个数,不传时为10

        $nowPage  = $request->param("nowPage","1");

        $token =  $request->header('Token','');

        $status = $request->param("status",'');//  0待付定金或结算   1 预定成功   2已开台  3已清台   9取消预约

        if (empty($status)){
            $status = 0;
        }

        $where_status['status'] = ["eq",$status];

        $uid   = $this->tokenGetUserInfo($token)['uid'];//获取uid

        $tableRevenueModel = new TableRevenue();

        $config = [
            "page" => $nowPage,
        ];

        $list = $tableRevenueModel
            ->alias("tr")
            ->join("mst_table_area ta","ta.area_id = tr.area_id")
            ->join("mst_table_location tl","ta.location_id = tl.location_id")
            ->join("manage_salesman ms","ms.sid = tr.ssid","LEFT")
            ->where('tr.uid',$uid)
            ->where($where_status)
            ->field("tr.trid,tr.table_id,tr.table_no,tr.status,tr.reserve_time,tr.subscription,tr.subscription_type,tr.reserve_way,tr.ssid,tr.ssname")
            ->field("ms.phone")
            ->field("tl.location_title")
            ->field("ta.area_title")
            ->paginate($pagesize,false,$config);

        $list = json_decode(json_encode($list),true);

        $data = $list["data"];

        $tableImageModel = new MstTableImage();

        for ($i = 0; $i <count($data); $i++){

            $table_id = $data[$i]['table_id'];

            $tableImage = $tableImageModel
                ->where('table_id',$table_id)
                ->field("image")
                ->select();

            $tableImage = json_decode(json_encode($tableImage),true);

            for ($m = 0; $m < count($tableImage); $m++){
                $list["data"][$i]['image_group'][] = $tableImage[$m]['image'];
            }
        }

        return $this->com_return(true,config("params.SUCCESS"),$list);
    }


    //我的订单列表
    public function dishOrder(Request $request)
    {

    }


}