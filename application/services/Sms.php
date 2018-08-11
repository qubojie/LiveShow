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
     * @param $phone
     * @return array
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


    /**
     *"尊敬的LiveShow用户 %phone% 您好,您已成功预订 %date_time% 的 %table_info%,如有定金,提前三十分钟取消预约,定金原路退回.感谢您的信任",
     * 发送短信信息
     * @param $phone
     * @return array
     */
    public function sendMsg($phone,$type,$date_time,$table_info)
    {
        if (empty($phone)){
            return $this->com_return(false,config("params.PARAM_NOT_EMPTY"));
        }

        if ($type == "revenue"){
            $message = config('sms.revenue_send').config('sms.sign');
        }elseif ($type == "cancel"){
            $message = config('sms.cancel_send').config('sms.sign');
        }else{
            $message = "天津五大道民园体育场 LiveShow酒吧 欢迎您".config('sms.sign');
        }

        $sms = new LuoSiMaoSms();

        $message = str_replace('%phone%',$phone,$message);
        $message = str_replace('%date_time%',$date_time,$message);
        $message = str_replace('%table_info%',$table_info,$message);

        $res = $sms->send($phone,$message);

        if ($res){
            if (isset($res['error']) && $res['error'] == 0){
                return $this->com_return(true, config("sms.send_success"));
            }else{
                return $this->com_return(false, $res['msg']);
            }
        }else{
            return $this->com_return(false, config("sms.send_fail"));
        }

    }

}