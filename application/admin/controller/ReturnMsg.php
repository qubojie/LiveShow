<?php
/**
 * Created by PhpStorm.
 * User: qubojie
 * Date: 2018/6/20
 * Time: 下午1:58
 */
namespace app\admin\controller;

use think\Controller;

abstract class ReturnMsg extends Controller
{
    public function response($data = '', $message = 'ok', $code = 200, $result = true)
    {
        return [
            "data"      => $data,
            "message"   => $message,
            "code"      => $code,
            "result"    => $result
        ];
    }
}