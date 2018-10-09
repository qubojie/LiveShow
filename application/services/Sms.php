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
use think\Log;

class Sms extends Controller
{
    /**
     * 发送验证码
     * @param $phone
     * @param $scene '场景'
     * @return array
     */
    public function sendVerifyCode($phone,$scene = "")
    {

        $common = new Common();

        if (empty($phone)){
            return $common->com_return(false,config("params.PARAM_NOT_EMPTY"));
        }

        /*$cache_code = Cache::get("sms_verify_code_" . $phone);

        if ($cache_code !== false){
            return $common->com_return(false,config("sms.send_repeat"));
        }*/

        //获取随机验证码
        $code = getRandCode(4);

        if ($scene == "managePointList"){
            //管理端点单
            $message = config('sms.point_list').config('sms.sign');

        }else{
            $message = config('sms.sms_verify_code').config('sms.sign');
        }


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

        if ($cache_code == "old"){
            return $common->com_return(true, config("sms.verify_success"));
        }

        if ($cache_code == $code) {

            //如果验证成功,则删除缓存
            Cache::rm("sms_verify_code_" . $phone);

            return $common->com_return(true, config("sms.verify_success"));

        }else{

            return $common->com_return(false, config("sms.verify_fail"));

        }
    }


    /**
     * 发送短信信息
     * @param $name
     * @param $phone
     * @param $sales_name
     * @param $sales_phone
     * @param $type
     * @param $date_time
     * @param $table_info
     * @param string $reserve_way
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function sendMsg($name,$phone,$sales_name,$sales_phone,$type,$date_time,$table_info,$reserve_way = "")
    {
        if (empty($phone)){
            return $this->com_return(false,config("params.PARAM_NOT_EMPTY"));
        }

        $sms = new LuoSiMaoSms();

        if ($type == "revenue"){

            if ($reserve_way == config("order.reserve_way")['manage']['key']){
                //如果是营销预约
                $message = config('sms.manage_revenue_send').config('sms.sign');
//                $message = str_replace('%name%',$name,$message);
//                $message = str_replace('%phone%',$phone,$message);
                $message = str_replace('%date_time%',$date_time,$message);
                $message = str_replace('%table_info%',$table_info,$message);
                $message = str_replace('%sales_name%',$sales_name,$message);
                $message = str_replace('%sales_phone%',$sales_phone,$message);

            }elseif ($reserve_way == config("order.reserve_way")['client']['key']){
                //如果是客户预约
                $service_phone = getSysSetting("service_phone");
                $message = config('sms.web_revenue_send').config('sms.sign');
                $message = str_replace('%date_time%',$date_time,$message);
                $message = str_replace('%table_info%',$table_info,$message);
                $message = str_replace('%service_phone%',$service_phone,$message);

                $phone = $sales_phone;

            }elseif ($reserve_way == config("user.register_way")['web']['key']) {
                $service_phone = getSysSetting("service_phone");
                //如果是前台预约
                $message = config('sms.web_revenue_send').config('sms.sign');
//                $message = str_replace('%name%',$name,$message);
//                $message = str_replace('%phone%',$phone,$message);
                $message = str_replace('%date_time%',$date_time,$message);
                $message = str_replace('%table_info%',$table_info,$message);
                $message = str_replace('%service_phone%',$service_phone,$message);

                $sms->send($phone,$message);

                if (!empty($sales_phone)){
                    $message = config('sms.web_manage_send').config('sms.sign');

                    //预约成功!时间%date_time% 桌号%table_info% 客户%user_name%%service_phone%。

                    $message = str_replace('%date_time%',$date_time,$message);
                    $message = str_replace('%table_info%',$table_info,$message);
                    $message = str_replace('%user_name%',$name,$message);
                    $message = str_replace('%service_phone%',$phone,$message);

                    $sms->send($sales_phone,$message);
                }

            }else{
                $message = "天津五大道民园体育场 LiveShow酒吧 欢迎您".config('sms.sign');
            }

        }elseif ($type == "cancel"){

            if ($reserve_way == config("order.reserve_way")['manage']['key']){
                //如果是营销取消预约
                $message = config('sms.manage_cancel_send').config('sms.sign');
                //取消预约成功!时间%date_time% 桌号%table_info%

                $message = str_replace('%date_time%',$date_time,$message);
                $message = str_replace('%table_info%',$table_info,$message);

            }elseif ($reserve_way == config("order.reserve_way")['client']['key']){
                //如果是客户取消预约
                $message = config('sms.client_cancel_send').config('sms.sign');
                $message = str_replace('%date_time%',$date_time,$message);
                $message = str_replace('%table_info%',$table_info,$message);

                $phone = $sales_phone;

            }elseif ($reserve_way == config("user.register_way")['web']['key']) {

                //如果是前台取消预约
                $message = config('sms.web_cancel_send').config('sms.sign');
                $message = str_replace('%date_time%',$date_time,$message);
                $message = str_replace('%table_info%',$table_info,$message);


            }else{
                $message = "天津五大道民园体育场 LiveShow酒吧 欢迎您".config('sms.sign');
            }

        }else{
            $message = "天津五大道民园体育场 LiveShow酒吧 欢迎您".config('sms.sign');
        }

        Log::info("短信内容".$message);

        $res = $sms->send($phone,$message);

        Log::info("短信发送返回 --- ".var_export($res,true));

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