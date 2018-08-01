<?php
/**
 * Created by PhpStorm.
 * User: qubojie
 * Date: 2018/6/25
 * Time: 上午11:18
 */
namespace app\common\controller;

use think\Controller;

class UUIDUntil extends Controller
{
    public function generateTimeUUID()
    {
        $uuid = $this->uuid();

        return strtoupper(str_replace("-","",$uuid));
    }

    public function generateReadableUUID($prefix = null)
    {
        $generateReadableUUID = $prefix . date("ymdHis") . sprintf('%03d',rand(0,999)) . substr($this->generateTimeUUID(),4,4);

        return $generateReadableUUID;
    }

    public function uuid()
    {

        if (function_exists('com_create_guid')) {
            return com_create_guid();
        }else{
            mt_srand((double)microtime()*10000);
            $charid = strtoupper(md5(uniqid(rand(),true)));
            $hyphen = chr(45);//"-"
            $uuid = chr(123)//"{"
                    .substr($charid,0,8).$hyphen
                    .substr($charid,8,4).$hyphen
                    .substr($charid,12,4).$hyphen
                    .substr($charid,16,4).$hyphen
                    .substr($charid,20,12)
                    .chr(125);//"}"
            return $uuid;
        }
    }
}