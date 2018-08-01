<?php
/**
 * Created by PhpStorm.
 * User: qubojie
 * Date: 2018/6/19
 * Time: 下午5:49
 */
namespace app\admin\behavior;

use think\Controller;
use traits\controller\Jump;

/**
 *
 * 判断Admin登陆状态
 *
 */
class AdminCheck
{
    public function run(&$data)
    {
        if (!$data){
            return [
                "return_code" => "FAIL",
                "return_msg"  => "请登录",
                "return_body" => null
            ];
        }else{
            return [
                "return_code" => "SUCCESS",
                "return_msg"  => "成功",
                "return_body" => null
            ];
        }
    }
}