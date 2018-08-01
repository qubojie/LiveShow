<?php
/**
 * Created by PhpStorm.
 * User: qubojie
 * Date: 2017/12/25
 * Time: 上午10:01
 */

namespace app\admin\controller;

use think\Controller;
use think\Db;
use think\Exception;

class Test extends Controller
{

    /*
     * 邮箱加密token
     * 算法 : md5(sha1($email.time()));
     *
     * */
    public function email_token($email)
    {
        return md5(sha1($email).time());
    }

    /*
     * 订单号生成
     * */
    public function build_order_no()
    {
        return date('Ymd').substr(implode(NULL,array_map('ord',str_split(substr(uniqid(),7,13),1))),0,8);
    }

    /*
     * 随机数生成
     * */
    public function create_sms_code($length = 6)
    {
        $min = pow(10,($length - 1));
        $max = pow(10,$length) - 1;
        return rand($min,$max);
    }

    /*
     * 移除数组中指定Key的元素
     * */
    public function bykey_reitem($arr,$key)
    {
        if (!array_key_exists($key,$arr)){
            return $arr;
        }
        $keys = array_keys($arr);
        $index = array_search($key,$keys);
        if ($index !== false){
            array_splice($arr,$index,1);
        }
        return $arr;
    }

    /**
     * @api {POST} /public_com/layout 退出登录操作
     * @apiGroup Com
     * @apiVersion 1.0.0
     *
     * @apiSampleRequest http://www.meitianpaotui.com/api/public_com/layout
     *
     * @apiParam {String} access_type 请求类型 //1:用户;2:商家;3:骑手
     * @apiParam {String} access_token //token
     *
     * @apiErrorExample {json} 错误返回:
     *     {
     *       "return_code": "FAIL" ,
     *       "return_msg" : "【非法访问】或【缺少参数】或【退出登录失败】" ,
     *       "return_body": null
     *     }
     * @apiSuccessExample {json} 成功返回:
     *     {
     *       "return_code": "SUCCESS" ,
     *       "return_msg" : "退出成功" ,
     *       "return_body": null
     *     }
     */
    public function layout()
    {
        $access_type = $this->request->param("access_type","");//1:用户;2:商家;3:骑手
        $access_token = $this->request->param("access_token","");
        if (!empty($access_type) && !empty($access_token)){
            if ($access_type == "1"){
                $token_name = "member_token";
                $table_name = "me_member";
                $res = $this->com_layout($token_name,$table_name,$access_token);
                return $res;

            }elseif ($access_type == "2"){
                //商家退出登录
                $token_name = "shop_token";
                $table_name = "me_shop";
                $res = $this->com_layout($token_name,$table_name,$access_token);
                return $res;
            }elseif ($access_type == "3"){
                //骑手退出登录
                $token_name = "rider_token";
                $table_name = "me_rider";
                $res = $this->com_layout($token_name,$table_name,$access_token);
                return $res;
            }else{
                return [
                    "return_code" => "FAIL",
                    "return_msg" => "非法访问",
                    "return_body" => null
                ];
            }
        }else{
            return [
                "return_code" => "FAIL",
                "return_msg" => "缺少参数",
                "return_body" => null
            ];
        }
    }

    /*
     * 退出公用
     * */
    public function com_layout($token_name,$table_name,$access_token)
    {
        $data = [
            $token_name => md5($access_token)
        ];
        Db::startTrans();
        try{
            //用户退出登录
            $is_ok = Db::name($table_name)
                ->where($token_name,$access_token)
                ->update($data);
            if ($is_ok){
                Db::commit();
                return [
                    "return_code" => "SUCCESS",
                    "return_msg" => "退出成功",
                    "return_body" => null
                ];
            }else{
                return [
                    "return_code" => "FAIL",
                    "return_msg" => "退出登录失败",
                    "return_body" => null
                ];

            }
        }catch (Exception $e){
            Db::rollback();
            return [
                "return_code" => "FAIL",
                "return_msg" => "退出登录失败".$e->getMessage(),
                "return_body" => null
            ];
        }
    }

    /*
     * 将一串字符插入到另一串字符串的指定位置
     * */
    public function str_insert($str,$i,$sub_str)
    {
        $start_str = "";
        $last_str = "";
        for ($j=0;$j<$i;$j++){
            $start_str .= $str[$j];
        }
        for ($j=$i; $j<strlen($str); $j++){
            $last_str .= $str[$j];
        }
        $str = ($start_str . $sub_str . $last_str);
        return $str;

    }

    /*
    * 过滤微信昵称的方法
    * */
    public function removeEmoji($nickname) {

        $clean_text = "";

        // Match Emoticons
        $regexEmoticons = '/[\x{1F600}-\x{1F64F}]/u';
        $clean_text = preg_replace($regexEmoticons, '', $nickname);

        // Match Miscellaneous Symbols and Pictographs
        $regexSymbols = '/[\x{1F300}-\x{1F5FF}]/u';
        $clean_text = preg_replace($regexSymbols, '', $clean_text);
        // Match Transport And Map Symbols
        $regexTransport = '/[\x{1F680}-\x{1F6FF}]/u';
        $clean_text = preg_replace($regexTransport, '', $clean_text);

        // Match Miscellaneous Symbols
        $regexMisc = '/[\x{2600}-\x{26FF}]/u';
        $clean_text = preg_replace($regexMisc, '', $clean_text);

        // Match Dingbats
        $regexDingbats = '/[\x{2700}-\x{27BF}]/u';
        $clean_text = preg_replace($regexDingbats, '', $clean_text);
        return $clean_text;
    }

    /**
     * 通过URL获取页面信息
     * @param string $url 地址
     * @return string 返回页面信息
     */
    public function get_url($url)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL,$url);  //设置访问的url地址
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);//不输出内容
        $result =  curl_exec($ch);
        curl_close ($ch);
        return $result;
    }

}