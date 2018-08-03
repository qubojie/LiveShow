<?php

namespace app\wechat\controller;

use app\admin\controller\Card;
use app\admin\controller\Common;
use app\admin\model\ManageSalesman;
use app\admin\model\MstCardVip;
use app\admin\model\MstSalesmanType;
use app\admin\model\User;
use app\common\controller\UUIDUntil;
use app\services\Sms;
use app\wechat\model\BcUserInfo;
use think\Controller;
use think\Db;
use think\Env;
use think\Exception;
use think\Request;

class Auth extends Controller
{
    /**
     * 发送验证码
     * @param Request $request
     * @return array
     */
    public function sendVerifyCode(Request $request)
    {
        $phone = $request->param("phone");

        $sms = new Sms();

        $res = $sms ->sendVerifyCode($phone);

        return $res;

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
     * 手机号+验证码注册和登陆
     * @param Request $request
     * @return array
     */
    public function phoneRegister(Request $request)
    {
        $common    = new Common();
        $UUIDUntil = new UUIDUntil();
        $userModel = new User();

        $phone = $request->param("phone","");
        $code  = $request->param("code","");

        $register_way   = $request->param("register_way","");

        $openid     = $request->param('openid',"");     //openid
        $nickname   = $request->param('nickname',"");   //昵称
        $headimgurl = $request->param('headimgurl',""); //头像
        $sex        = $request->param('sex',"1");        //性别
        $province   = $request->param('province',"");   //省份
        $city       = $request->param('city',"");       //城市
        $country    = $request->param('country',"");    //国家

        //$nickname = $this->filterEmoji($nickname);

        if ($sex == 1){
            $sex = "先生";
        }else{
            $sex = "女士";
        }

        if (empty($register_way)){
            return $this->com_return(false,'注册途径不能为空');
        }

        if (empty($headimgurl)){
            $headimgurl = Env::get("DEFAULT_AVATAR_URL")."avatar.jpg";
        }

        $is_pp = $this->checkVerifyCode($phone,$code);

        $time = time();

        //验证验证码
        if (!$is_pp['result']) return $is_pp;

        //查询当前手机号码是否已经绑定用户
        $is_exist = $userModel
            ->where('phone',$phone)
            ->count();

        $manageSalesmanModel = new ManageSalesman();
        //查看当前用户是否是内部人员
        $is_salesman = $manageSalesmanModel
            ->where('phone',$phone)
            ->count();
        if ($is_salesman){
            //是内部人员
            $return_msg = "内部人员";
        }else{
            //不是内部人员
            $return_msg = config("ADD_SUCCESS");
        }



        if ($is_exist <= 0){
            //未注册用户
            $UUIDUntil = new UUIDUntil();

            $uid  = $UUIDUntil->generateReadableUUID("U");

            $time = time();
            //不存在,则写入
            $insert_data = [
                'uid'            => $uid,
                'phone'          => $phone,
                'password'       => sha1('000000'),
                'register_way'   => $register_way,
                'wxid'           => $openid,
                'nickname'       => $nickname,
                'avatar'         => $headimgurl,
                'sex'            => $sex,
                'province'       => $province,
                'city'           => $city,
                'country'        => $country,
                'user_status'    => '0',
                'info_status'    => '1',
                'lastlogin_time' => $time,
                'token_lastime'  => $time,
                'remember_token' => $common->jm_token($UUIDUntil->uuid().time()),
                'created_at'     => $time,
                'updated_at'     => $time
            ];
            Db::startTrans();
            try{
                $is_ok = $userModel
                    ->insert($insert_data);
                if ($is_ok){
                    Db::commit();
                    $user_info = $this->getUserInfo($uid);
                    return $common->com_return(true, $return_msg,$user_info);
                }else{
                    return $common->com_return(false, config("ADD_FAIL"));
                }

            }catch (Exception $e){
                Db::rollback();
                return $common->com_return(false,$e->getMessage(),null);
            }
        }else{
            //已经注册过,返回用户的注册状态,已注册|提交订单|开卡成功 | 兴趣标签
            //更新用户信息
            /*if (!empty($openid)){

            }*/
            $update_params = [
                'wxid'           => $openid,
                'nickname'       => $nickname,
                'avatar'         => $headimgurl,
                'sex'            => $sex,
                'province'       => $province,
                'city'           => $city,
                'country'        => $country,
                'lastlogin_time' => $time,
                'remember_token' => $common->jm_token($UUIDUntil->uuid().time()),
                'token_lastime'  => $time,
                'updated_at'     => $time
            ];

            $userModel->where('phone',$phone)->update($update_params);

            return $this->check_user_status($phone);
        }
    }

    /**
     * 推荐人信息录入
     * @param Request $request
     * @return array
     */
    public function referrerUser(Request $request)
    {
        $phone          = $request->param('phone',"");

        $referrer_phone = $request->param("referrer_phone","");

        $manageSalesmanModel = new ManageSalesman();

        if (empty($referrer_phone)){
            return $this->com_return(false,'推荐人不能为空');
        }

        $userModel = new User();

        //排除推荐人是8888的情况
        if ($referrer_phone != '8888'){

            //根据电话号码获取平台推荐人sid
            $sid_res = $manageSalesmanModel
                ->alias('ms')
                ->join('mst_salesman_type mst','mst.stype_id = ms.stype_id')
                ->where('ms.phone',$referrer_phone)
                ->where('ms.statue',config("salesman.salesman_status")['working']['key'])
                ->field('ms.sid,mst.stype_key')
                ->find();

            if(!empty($sid_res)){
                //销售推荐时,推荐人可以是自己
                $referrer_id = $sid_res['sid'];
                $referrer_type = $sid_res['stype_key'];//vip sales

            }else{
                //如果是用户推荐,则推荐人不能是自己
                if ($phone == $referrer_phone){
                    return $this->com_return(false,'推荐人不能是自己');
                }

                //获取推荐用户的uid
                $uid_res = $userModel
                    ->where('phone',$referrer_phone)
                    ->field('uid')
                    ->find();
                if (!empty($uid_res)){
                    $referrer_id = $uid_res['uid'];
                    $referrer_type = 'user';//用户推荐
                }else{
                    return $this->com_return(false,'请输入正确的推荐人号码');
                }
            }
        }else{
            //平台默认推荐人信息
            $referrer_id    = config("user.platform_recommend")['referrer_id']['name'];
            $referrer_type  = config("user.platform_recommend")['referrer_type']['name'];
        }

        $params = [
            'referrer_type' => $referrer_type,
            'referrer_id'   => $referrer_id,
            'info_status'   => config("user.user_info")['interest']['key']//更改用户状态为 待填写兴趣标签
         ];

        $res = $userModel
            ->where('phone',$phone)
            ->update($params);
        if ($res){
            return $this->com_return(true,config("params.SUCCESS"));
        }else{
            return $this->com_return(true,config("params.FAIL"));
        }
    }


    /**
     * 手机账号密码登陆
     * @param Request $request
     * @return array
     */
    public function phonePassLogin(Request $request)
    {
        $common = new Common();
        $UUID = new UUIDUntil();

        $phone    = $request->param("phone","");
        $password = $request->param("password","");

        if (empty($phone) || empty($password)){
            return $common->com_return(true,config("PARAM_NOT_EMPTY"));
        }

        $password = sha1($password);

        $userModel = new User();

        $column = $userModel->column;

        $user_info = $userModel
            ->where("phone",$phone)
            ->where("password",$password)
            ->field($column)
            ->find();

        if (!empty($user_info)){

            $token = $common->jm_token($UUID->uuid().time());

            $time = time();

            $update_data = [
                'lastlogin_time' => $time,
                'updated_at'     => $time,
                'remember_token' => $token,
                'token_lastime'  => $time
            ];

            $userModel->where('phone',$phone)->update($update_data);


            $status = $user_info['status'];

            $user_info['remember_token'] = $token;


            if ($status){

                return $common->com_return(false,"禁止登陆",$user_info);

            }else{

                return $common->com_return(true,config("SUCCESS"),$user_info);

            }

        }else{

            return $common->com_return(false,config("NOT_MATCH"));

        }
    }

    /**
     * 手机号+验证码登陆
     * @param Request $request
     * @return array
     */
    public function phoneVerifyLogin(Request $request)
    {
        $phone = $request->param("phone","");

        $code = $request->param("code","");

        $common = new Common();

        $is_pp = $this->checkVerifyCode($phone,$code);

        if ($is_pp['result']){
            //查询当前手机号码是否已经绑定用户
            $userModel = new User();

            $column = $userModel->column;

            $user_info = $userModel
                ->where('phone',$phone)
                ->field($column)
                ->find();

            if (!empty($user_info)){

                return $common->com_return(true,config("SUCCESS"),$user_info);

            }else{

                return $common->com_return(false,"登陆失败");

            }

        }else{
            return $is_pp;
        }
    }

    /**
     * 小程序微信授权信息绑定
     * @param Request $request
     * @return array
     */
    public function phoneBindWechat(Request $request)
    {
        $phone   = $request->param('phone','');

        $wx_info = $request->param('wx_info','');

        $wx_info = json_decode($wx_info,true);

        $params = [
            'wxid'       => $wx_info['openid'],
            'avatar'     => $wx_info['headimgurl'],
            'sex'        => $wx_info['sex'] ? '男士':'女士',
            'province'   => $wx_info['province'],
            'city'       => $wx_info['city'],
            'country'    => $wx_info['country'],
            'updated_at' => time()
        ];

        $userModel = new User();

        $is_ok = $userModel
            ->where('phone',$phone)
            ->update($params);

        if ($is_ok){
            $this->com_return(true,'绑定成功');
        }else{
            $this->com_return(false,'绑定失败');
        }
    }

    /**
     * 已经是会员,判断用户状态,返回值
     * @param $phone
     * @return array
     */
    public function check_user_status($phone)
    {
        $userModel      = new User();
        $common         = new Common();
        $userInfoModel  = new BcUserInfo();

        $column = $userModel->column;

        $user_info = $userModel
            ->where('phone',$phone)
            ->field($column)
            ->find();

        //用户信息状态
//        $info_status = $user_info['info_status'];
        $referrer_id = $user_info['referrer_id'];


        if (empty($referrer_id)){
            //如果推荐人为空,即为待写推荐人,提示跳转至填写推荐人页面
            return $common->com_return(true,config("user.user_info")['referrer']['name'],$user_info);
        }

        //用户注册状态
        $user_status = $user_info['user_status'];

        if ($user_status == config("user.user_register_status")['register']['key']){

            //仅注册
            return $common->com_return(true,config("user.user_register_status")['register']['name'],$user_info);

        }elseif ($user_status == config("user.user_register_status")['post_order']['key']){

            //提交订单
            $cardCallBackObj = new CardCallback();
            $referrer_info = $cardCallBackObj->getUserCardInfo($user_info['uid'],'0');
            return $common->com_return(true,config("user.user_register_status")['post_order']['name'],$referrer_info);

        }elseif ($user_status == config("user.user_register_status")['open_card']['key']){

            return $common->com_return(true,config("user.user_register_status")['open_card']['name'],$user_info);

            /*//已开卡
            $user_blood = $userInfoModel
                ->where('uid',$user_info['uid'])
                ->field('blood')
                ->find();
            if (!empty($user_blood['blood'])){
                return $common->com_return(true,config("user.user_register_status")['open_card']['name'],$user_info);
            }else{
                //丰富兴趣标签
                return $common->com_return(true,config("user.user_info")['interest']['name'],$user_info);
            }*/

        }
    }

    /**
     * 根据 uid获取用户信息
     * @param $uid
     * @return array|false|\PDOStatement|string|\think\Model
     */
    public function getUserInfo($uid)
    {
        $userModel = new User();

        $column = $userModel->column;

        $user_info = $userModel
            ->where('uid',$uid)
            ->field($column)
            ->find();

        return $user_info;
    }


    /**
     * Vip卡列表
     * @param Request $request
     * @return array
     */
    public function cardList(Request $request)
    {
        $common       = new Common();
        $cardVipModel = new MstCardVip();
        $userModel    = new User();

        $uid = $request->param('uid',"");
        if (empty($uid)){
            return $common->com_return(false,'缺少参数');
        }
        $user_info = $userModel
            ->where('uid',$uid)
            ->field('referrer_id,referrer_type')
            ->find();


        $user_info = json_decode(json_encode($user_info),true);

        $referrer_type = $user_info['referrer_type'];

        /*if ($user_info['referrer_type'] == 'user'){
            $where = [];
        }else{
            //如果是销售推荐,展示相应的卡类
            if ($user_info['referrer_type'] == 'vip'){
                $where = [
                    'card_type' => 'vip'
                ];
            }else if ($user_info['referrer_type'] == 'sales'){
                $where = [
                    'card_type' => 'value'
                ];
            }else{
                $where = [];
            }
        }*/

        $where['salesman'] = ["like","%$referrer_type%"];

        $gift_list = $cardVipModel
            ->where($where)
            ->where('is_delete','0')
            ->where('is_enable','1')
            ->select();
        $gift_list = json_decode(json_encode($gift_list),true);

        for ($i=0;$i<count($gift_list);$i++){
            $card_id = $gift_list[$i]['card_id'];

            $card_type = $gift_list[$i]['card_type'];

            $cardConfig = config("card.type");

            for ($m = 0; $m < count($cardConfig); $m++){
                if ($cardConfig[$m]['key'] == $card_type){
                    $gift_list[$i]['card_type'] = $cardConfig[$m]['name'];
                }
            }
            $gift_list[$i]['gift_info'] = $this->gift_list($card_id);
            $gift_list[$i]['voucher_info'] = $this->voucher_list($card_id);
        }
        return $common->com_return(true,config("GET_SUCCESS"),$gift_list);
    }

    /**
     * 礼品与卡关联信息列表
     * @param $card_id
     * @return false|\PDOStatement|string|\think\Collection
     */
    public function gift_list($card_id)
    {
        $res = Db::name('mst_card_vip_gift_relation')
            ->alias('gr')
            ->join('mst_gift mg','mg.gift_id = gr.gift_id')
            ->where('gr.card_id',$card_id)
            ->select();

        return $res;
    }

    /**
     * 礼券与卡关联信息列表
     * @param $card_id
     * @return false|\PDOStatement|string|\think\Collection
     */
    public function voucher_list($card_id)
    {
        $res = Db::name('mst_card_vip_voucher_relation')
            ->alias('vr')
            ->join('mst_gift_voucher mgv','mgv.gift_vou_id = vr.gift_vou_id')
            ->where('vr.card_id',$card_id)
            ->select();

        return $res;
    }


    /**
     * 变更手机号码
     * @param Request $request
     * @return array
     */
    public function changePhone(Request $request)
    {
        $phone = $request->param('phone','');
        $code  = $request->param("code","");
        $type  = $request->param("type","");

        if (empty($phone) || empty($code) || empty($type)) return $this->com_return(false,config("params.PARAM_NOT_EMPTY"));

        $remember_token =  $request->header('Token','');

        $is_pp = $this->checkVerifyCode($phone,$code);

        //验证验证码
        if (!$is_pp['result']) return $is_pp;

        if ($type == "user"){
            $is_ok = $this->userChangePhone($remember_token,$phone);
        }else{
            $is_ok = $this->serverChangePhone($remember_token,$phone);
        }

        return $is_ok;

    }

    /**
     * 服务人员变成手机号码
     */
    protected function serverChangePhone($remember_token,$phone)
    {
        $salesmanModel = new ManageSalesman();

        $isExist = $salesmanModel
            ->where('remember_token',$remember_token)
            ->count();

        if ($isExist != 1){
            return $this->com_return(false,config("params.PARAM_NOT_EMPTY"));
        }

        //查询此手机号码是否已绑定其他账户
        $phoneExist = $salesmanModel
            ->where('phone',$phone)
            ->count();
        if ($phoneExist > 0){
            return $this->com_return(false,config("params.PHONE_BIND_OTHER"));
        }

        $update_data = [
            'phone'      => $phone,
            'updated_at' => time()
        ];

        $is_ok = $salesmanModel
            ->where('remember_token',$remember_token)
            ->update($update_data);

        if ($is_ok !== false){

            return $this->com_return(true,config("params.SUCCESS"));

        }else{

            return $this->com_return(false,config("params.FAIL"));
        }


    }

    /**
     * 用户变更手机号码
     */
    protected function userChangePhone($remember_token,$phone)
    {
        $userModel = new User();
        //查询此用户是否存在
        $userExist = $userModel
            ->where('remember_token',$remember_token)
            ->count();
        if ($userExist != 1){
            return $this->com_return(false,config("params.PARAM_NOT_EMPTY"));
        }

        //查询此手机号码是否已绑定其他账户
        $phoneExist = $userModel
            ->where('phone',$phone)
            ->count();
        if ($phoneExist > 0){
            return $this->com_return(false,config("params.PHONE_BIND_OTHER"));
        }

        $update_data = [
            'phone'      => $phone,
            'updated_at' => time()
        ];

        $is_ok = $userModel
            ->where('remember_token',$remember_token)
            ->update($update_data);

        if ($is_ok !== false){

            return $this->com_return(true,config("params.SUCCESS"));

        }else{

            return $this->com_return(false,config("params.FAIL"));
        }
    }


}