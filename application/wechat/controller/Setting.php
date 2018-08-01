<?php
/**
 * Created by PhpStorm.
 * User: qubojie
 * Date: 2018/6/19
 * Time: 下午4:00
 */

namespace app\wechat\controller;

use app\wechat\model\SysSetting;
use think\Controller;
use think\Db;
use think\Env;

class Setting extends Controller
{
    public function index()
    {

       $sys_setting = new SysSetting();

       $res = $sys_setting->select();

       return $res;

    }
}
