<?php
/**
 * Created by PhpStorm.
 * User: guojing
 * Date: 2018/9/14
 * Time: 16:12
 */

namespace app\wechat\controller;

use app\admin\controller\Log;
use think\Controller;
use think\Env;
use think\Loader;
use think\Request;


class SendTemplateMessage extends Controller
{
    public function index($access_token, $touser, $page, $form_id, $data)
    {
        //获取用户全部信息
        $url = "https://api.weixin.qq.com/cgi-bin/message/wxopen/template/send?access_token=" . $access_token;
        $arr = array(
            "touser" => $touser,
            "template_id" => Env::get("SERVER_MESSAGE_TEMPLATE_ID"),
            "page" => $page,
            "form_id" => $form_id,
            "data" => $data
        );
        $json = json_encode($arr);

        $ch = curl_init();
        $timeout = 500;
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $json);
        $result = curl_exec($ch);
        $r = json_decode($result, true);//解析信息
        curl_close($ch);
        return $r;
    }

    /**
     * 绑定微信错误代码
     * @param  $code       服务器输出的错误代码
     * return string
     */
    public function error_code($code)
    {
        $errList = array(
            '-1' => '系统繁忙',
            '40003' => 'invalid openid hint',
            '40037' => 'template_id不正确',
            '41028' => 'form_id不正确，或者过期',
            '41029' => 'form_id已被使用',
            '41030' => 'page不正确',
            '45009' => '接口调用超过限额（目前默认每个帐号日调用限额为100万）',
        );
        if (array_key_exists($code, $errList)) {
            return $errList[$code];
        }
    }
}