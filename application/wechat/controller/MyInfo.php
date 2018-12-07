<?php
/**
 * Created by PhpStorm.
 * User: qubojie
 * Date: 2018/7/6
 * Time: 下午5:04
 */
namespace app\wechat\controller;

use app\admin\controller\Common;
use app\admin\model\MstMerchant;
use app\admin\model\MstTableImage;
use app\admin\model\MstUserLevel;
use app\admin\model\TableRevenue;
use app\admin\model\User;
use app\services\Sms;
use app\wechat\model\BcUserInfo;
use app\wechat\model\BillCardFees;
use app\wechat\model\BillPay;
use app\wechat\model\BillPayDetail;
use app\wechat\model\JobUser;
use app\wechat\model\UserAccount;
use app\wechat\model\UserAccountCashGift;
use app\wechat\model\UserCard;
use app\wechat\model\UserGiftVoucher;
use think\Db;
use think\Exception;
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
        $column = $userModel->column;
        foreach ($column as $key => $val){
            $column[$key] = "u.".$val;
        }
        $user_info = $userModel
            ->alias("u")
            ->join("user_info ui","ui.uid = u.uid","LEFT")
            ->where('u.remember_token',$token)
            ->field('ui.birthday')
            ->field($column)
            ->find();

        /*对生日进行处理 On*/
        $birthday    = $user_info['birthday'];
        if (!empty($birthday)){
            $by = substr($birthday,0,2);
            if ($by <100 && $by >= 40){
                $birthday = "19".$birthday;
            }else{
                $birthday = "20".$birthday;
            }
            $user_info['birthday'] = $birthday;
        }
        /*对生日进行处理 Off*/

        $uid         = $user_info['uid'];
        $user_status = $user_info['user_status'];

        $jobUserModel = new JobUser();
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

        $levelModel = new MstUserLevel();

        $level_info =$levelModel
            ->where('level_id', $user_info['level_id'])
            ->field('level_name,level_desc,level_img,point_min')
            ->find();

        $user_info['level_name'] = $level_info['level_name'];
        $user_info['level_desc'] = $level_info['level_desc'];
        $user_info['level_img']  = $level_info['level_img'];
        $user_info['point_min']  = $level_info['point_min'];

        //获取用户礼券数量
        $giftVoucherModel = new UserGiftVoucher();
        $gift_voucher_num = $giftVoucherModel
            ->where('uid',$uid)
            ->where("status",config("voucher.status")['0']['key'])
            ->count();

        $user_info['gift_voucher_num'] = $gift_voucher_num;

        //获取用户预约数量
        $tableRevenueModel = new TableRevenue();
        $user_revenue_num = $tableRevenueModel
            ->where("uid",$uid)
            ->where("status",config("order.table_reserve_status")['reserve_success']['key'])
            ->count();

        $user_info['revenue_num'] = $user_revenue_num;

        //获取商户数量
        $merchantModel = new MstMerchant();
        $merchant_num = $merchantModel->count();
        $user_info['merchant_num'] = $merchant_num;


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
            ->order("created_at DESC")
            ->select();

        $account = $userAccountModel
            ->where('uid',$uid)
            ->order("created_at DESC")
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

        $token    =  $request->header('Token','');

        $status   = $request->param("status",'');//  0待付定金或结算   1 预定成功   2已开台  3已清台   9取消预约

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
            ->join("mst_table t","t.table_id = tr.table_id")
            ->join("mst_table_area ta","ta.area_id = tr.area_id")
            ->join("mst_table_location tl","ta.location_id = tl.location_id")
            ->join("mst_table_appearance tap","tap.appearance_id = t.appearance_id")
            ->join("manage_salesman ms","ms.sid = tr.ssid","LEFT")
            ->where('tr.uid',$uid)
            ->where($where_status)
            ->field("tr.trid,tr.table_id,tr.table_no,tr.status,tr.turnover_limit,tr.reserve_time,tr.subscription,tr.subscription_type,tr.reserve_way,tr.ssid,tr.ssname")
            ->field("ms.phone")
            ->field("tl.location_title")
            ->field("ta.area_title")
            ->field("tap.appearance_title")
            ->paginate($pagesize,false,$config);

        $list = json_decode(json_encode($list),true);

        $data = $list["data"];

        $tableImageModel = new MstTableImage();

        for ($i = 0; $i <count($data); $i++){

            $table_id = $data[$i]['table_id'];

            $trid     = $data[$i]['trid'];

            $is_refund_sub_res = Db::name("bill_subscription")
                ->where("trid",$trid)
                ->field("is_refund_sub")
                ->find();

            if (!empty($is_refund_sub_res)){
                $is_refund_sub = $is_refund_sub_res['is_refund_sub'];
            }else{
                $is_refund_sub = 0;
            }

            if ($is_refund_sub){
                //不退
                $list["data"][$i]['is_refund_sub_msg'] = getSysSetting("reserve_warning_no");

            }else{
                //退
                $list["data"][$i]['is_refund_sub_msg'] = getSysSetting("reserve_warning");

            }

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

    /**
     * 我的订单列表
     * @param Request $request
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function dishOrder(Request $request)
    {
        $token =  $request->header('Token','');

        $uid   = $this->tokenGetUserInfo($token)['uid'];//获取uid

        $pagesize = $request->param("pagesize",config('PAGESIZE'));//显示个数,不传时为10

        $nowPage  = $request->param("nowPage","1");

        $config = [
            "page" => $nowPage,
        ];

        $billPayModel = new BillPay();

        $column = $billPayModel->column;

        foreach ($column as $k => $v){
            $column[$k] = "bp.".$v;
        }

        $list = $billPayModel
            ->alias("bp")
            ->join("table_revenue tr","tr.trid = bp.trid")
            ->where("bp.uid",$uid)
            ->order("bp.created_at DESC")
            ->field("tr.table_no")
            ->field($column)
            ->paginate($pagesize,false,$config);

        $list = json_decode(json_encode($list),true);

        $billPayDetail = new BillPayDetail();

        $bill_pay_column = $billPayDetail->column;

        foreach ($bill_pay_column as $k => $v){
            $bill_pay_column[$k] = "bp.".$v;
        }

        $commonObj = new Common();

        for ($i = 0; $i < count($list['data']); $i ++){

            $pid = $list['data'][$i]['pid'];

            $bill_pay_detail = $billPayDetail
                ->alias("bp")
                ->join("dishes d","d.dis_id = bp.dis_id")
                ->where("bp.pid",$pid)
                ->field("d.dis_img,d.dis_desc")
                ->field($bill_pay_column)
                ->select();

            $bill_pay_detail = json_decode(json_encode($bill_pay_detail),true);

            $bill_pay_detail = $commonObj->make_tree($bill_pay_detail,"id","parent_id");

            $list['data'][$i]['bill_pay_count'] = count($bill_pay_detail);
            $list['data'][$i]['bill_pay_detail'] = $bill_pay_detail;

        }
        return $this->com_return(true,config("params.SUCCESS"),$list);
    }

    /**
     * 修改个人信息
     * @param Request $request
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function changeInfo(Request $request)
    {
        $params = $request->param();
        if (empty($params)){
            return $this->com_return(false,config("params.PARAM_NOT_EMPTY"));
        }

        $token = $request->header("Token");

        $userInfo = $this->tokenGetUserInfo($token);

        if (empty($userInfo)){
            return $this->com_return(false,config("params.ABNORMAL_ACTION"));
        }

        $uid = $userInfo['uid'];

        Db::startTrans();
        try{

            /*根据生日处理生肖  On*/
            if (isset($params['birthday']) && !empty($params['birthday'])){
                $birthday = $params['birthday'];
                $birthday = substr($birthday,2,6);

                $constellationObj             = new AgeConstellation();
                $nxs                          = $constellationObj->getInfo($birthday);
                $astro                        = $nxs['constellation'];//星座
                $userInfoParams['birthday']   = $birthday;
                $userInfoParams['astro']      = $astro;
                $userInfoParams['updated_at'] = time();

                $userInfoModel = new BcUserInfo();

                $is_ok = $userInfoModel
                    ->where("uid",$uid)
                    ->update($userInfoParams);

                if ($is_ok === false){
                    return $this->com_return(false,config("params.ABNORMAL_ACTION"));
                }
            }
            /*根据生日处理生肖  Off*/

            if (isset($params['name']) || isset($params['sex'])){
                if (isset($params['birthday'])){
                    unset($params['birthday']);
                }
                $params['updated_at'] = time();
                $userModel = new User();
                $is_true = $userModel
                    ->where("uid",$uid)
                    ->update($params);
                if ($is_true === false){
                    return $this->com_return(false,config("params.ABNORMAL_ACTION"));
                }
            }

            Db::commit();
            return $this->com_return(true,config("params.SUCCESS"));

        }catch (Exception $e){
            Db::rollback();
            return $this->com_return(false,$e->getMessage());
        }
    }

    /**
     * 我的积分以及排行
     * @param Request $request
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function myPointDetails(Request $request)
    {
        $token = $request->header("Token");

        $userModel = new User();

        /*我的积分 On*/
        $userPointInfo = $userModel
            ->alias("u")
            ->join("mst_user_level ul","ul.level_id = u.level_id","LEFT")
            ->where("u.remember_token",$token)
            ->field("u.account_point")
            ->field("ul.level_name")
            ->find();

        $userPointInfo = json_decode(json_encode($userPointInfo),true);

        if (empty($userPointInfo)){
            return $this->com_return(false,config("params.ABNORMAL_ACTION")." - 001");
        }

        $account_point = $userPointInfo['account_point'];
        $level_name    = $userPointInfo['level_name'];
        /*我的积分 Off*/

        /*用户积分排行前10位 On*/
        $userPointInfo = $userModel
//            ->where("user_status",config("user.user_status")['2']['key'])
            ->where("account_point",">",0)
            ->order("account_point DESC")
            ->field("uid,name,nickname,avatar,sex,account_point")
            ->limit(10)
            ->select();
        $userPointInfo = json_decode(json_encode($userPointInfo),true);
        /*用户积分排行前10位 Off*/

        /*获取比例 On*/
        $all_num = $userModel
            ->count();
        $lt_num = $userModel
            ->where("account_point","<",$account_point)
            ->count();

        $percentage = round($lt_num / ($all_num - 1) * 100,2);
        /*获取比例 Off*/

        $res['account_point']   = $account_point;
        $res['level_name']      = $level_name;
        $res['percentage']      = $percentage;
        $res['user_point_list'] = $userPointInfo;

        return $this->com_return(true,config("params.SUCCESS"),$res);
    }

}