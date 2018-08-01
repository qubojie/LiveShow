<?php
/**
 * Created by PhpStorm.
 * User: qubojie
 * Date: 2018/7/5
 * Time: 上午10:22
 */
namespace app\wechat\controller;

use app\admin\controller\Common;
use app\admin\model\User;
use app\common\controller\UUIDUntil;
use think\Controller;
use think\Env;
use think\Loader;
use think\Log;
use think\Request;

Loader::import('wxpay.lib.WxPayJsPay');
Loader::import('wxpay.lib.WxPayConfig');
Loader::import('wxpay.lib.WxPayException');

class ThirdLogin extends Controller
{
    /**
     * 微信小程序三方授权,判断用户是否是注册用户
     * @param Request $request
     * @return array
     */
    public function wechatLogin(Request $request)
    {
        $UUID       = new UUIDUntil();
        $code       = $request->param('code','');

        $nickname   = $request->param('nickname','');

        //$nickname   = $this->filterEmoji($nickname);//过滤昵称中的表情

        $headimgurl = $request->param('headimgurl','');

        $userInfo   = $this->getOpenId($code);

        $openid     = $userInfo['openid'];

//        $user_wx_info = $this->getWxInfo($openid);

//        Log::info("用户微信信息".explode($user_wx_info,true));

        $userModel = new User();

        $is_exist = $userModel
            ->where('wxid',$openid)
            ->find();

        $time = time();
        if (!empty($is_exist)){
            //变更token,并返回token
            $Common = new Common();

            $token = $Common->jm_token($UUID->generateReadableUUID("token"));

            //如果存在,更新
            $params = [
                'nickname'       => $nickname,
                'avatar'         => $headimgurl,
                'lastlogin_time' => $time,
                'updated_at'     => $time,
                'remember_token' => $token,
                'token_lastime'  => $time
            ];


            $is_ok = $userModel
                ->where('wxid',$openid)
                ->update($params);

            $user_info = $userModel
                ->where('wxid',$openid)
                ->find();
            return $this->com_return(true,config('SUCCESS'),$user_info);
        }else{
            //如果不存在,新增
            return $this->com_return(false,'请注册登陆',$userInfo);
        }
    }

    /**
     * 获取微信用户信息
     * @param $openid
     * @return mixed
     */
    /*public function getWxInfo($openid)
    {
        $appid  = Env::get("WECHAT_PUBLIC_APPID");
        $secret = Env::get("WECHAT_PUBLIC_APPSECRET");
        $access_token_url = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=".$appid."&secret=".$secret."";

        $access_token_info = $this->vget($access_token_url);
        $access_token = $access_token_info['access_token'];

        $user_info_url = "https://api.weixin.qq.com/cgi-bin/user/info?access_token=".$access_token."&openid=".$openid."&lang=zh_CN";//获取用户全部信息

        $user_info = $this->vget($user_info_url);
        return $user_info;
    }*/


    public function thirdBind(Request $request)
    {
        $phone = $request->param('phone','');

    }


    /**
     * 获取openid,根据code
     * @param $code
     * @return mixed
     */
    public function getOpenId($code)
    {
        $Appid  = Env::get("WECHAT_XCX_APPID");
        $Secret = Env::get("WECHAT_XCX_APPSECRET");
        $url    = 'https://api.weixin.qq.com/sns/jscode2session?appid='.$Appid.'&secret='.$Secret.'&js_code=' . $code . '&grant_type=authorization_code';
        $info   = $this->vget($url);
        $info   = json_decode($info,true);//对json数据解码
        return $info;
    }

    public function vget($url)
    {
        $info=curl_init();
        curl_setopt($info,CURLOPT_RETURNTRANSFER,true);
        curl_setopt($info,CURLOPT_HEADER,0);
        curl_setopt($info,CURLOPT_NOBODY,0);
        curl_setopt($info,CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($info,CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($info,CURLOPT_URL,$url);
        $output= curl_exec($info);
        curl_close($info);
        return $output;
    }
}