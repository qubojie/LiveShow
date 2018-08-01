<?php
/**
 * Created by PhpStorm.
 * User: qubojie
 * Date: 2018/6/26
 * Time: 下午1:40
 */
namespace app\services;

use app\admin\controller\Common;
use think\Cache;
use think\Controller;

class Sms extends Controller
{
    /**
     * 发送验证码
     */
    public function sendVerifyCode($phone)
    {

        $common = new Common();

        if (empty($phone)){
            return $common->com_return(false,config("params.PARAM_NOT_EMPTY"));
        }

        $cache_code = Cache::get("sms_verify_code_" . $phone);

        if ($cache_code !== false){
            return $common->com_return(false,config("sms.send_repeat"));
        }

        //获取随机验证码
        $code = getRandCode(4);

        $message = config('sms.sms_verify_code').config('sms.sign');

        $sms = new LuoSiMaoSms();

        $res = $sms->send($phone, str_replace('%code%', $code, $message));

        if ($res){
            if (isset($res['error']) && $res['error'] == 0){
                //缓存验证码
                Cache::set("sms_verify_code_" . $phone, $code, 300);
                return $common->com_return(true, config("sms.send_success"));
            }else{
                return $common->com_return(false, $res['msg']);
            }
        }else{
            return $common->com_return(false, config("sms.send_fail"));
        }
    }

    /*
     * 验证验证码
     * */
    public function checkVerifyCode($phone,$code)
    {
        $common = new Common();

        if (empty($phone) || empty($code)){

            return $common->com_return(false, config("PARAM_NOT_EMPTY"));

        }

        $cache_code = Cache::get("sms_verify_code_" . $phone);

        if ($cache_code == $code) {

            //如果验证成功,则删除缓存
            Cache::rm("sms_verify_code_" . $phone);

            return $common->com_return(true, config("sms.verify_success"));

        }else{

            return $common->com_return(false, config("sms.verify_fail"));

        }
    }
}