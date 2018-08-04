<?php
/**
 * Created by PhpStorm.
 * User: qubojie
 * Date: 2018/6/19
 * Time: 下午4:00
 */

// 应用公共文件


/**
 * 生成验证码
 * @param int $length
 * @param int $numeric
 * @return string
 */
function getRandCode($length = 6 , $numeric = 0)
{
    PHP_VERSION < '4.2.0' && mt_srand((double)microtime() * 1000000);

    if ($numeric){
        $hash = sprintf('%0'.$length.'d',mt_rand(0,pow(10,$length) - 1));
    } else {
        $hash = '';
        $chars = '0123456789';
        $max = strlen($chars) - 1;
        for ($i = 0; $i < $length; $i++) {
            $hash .= $chars[mt_rand(0,$max)];
        }
    }
    return $hash;
}


/**
 * 将数组中值为Int转换为string
 * @param $arr
 * @return mixed
 */
function arrIntToString($arr){
    foreach ($arr as $k => $v){
        if (is_numeric($v)){
            $arr[$k] = (string)$v;
        }
    }
    return $arr;
}


/**
 * 获取某个时间戳的周几，以及未来几天以后的周几
 * @param $time
 * @param int $i
 * @return mixed
 */
function getTimeWeek($time, $i = 0){
    $weekArray = ["7", "1", "2", "3", "4", "5", "6"];
    $oneD = 24 * 60 * 60;

    return $weekArray[date("w", $time + $oneD * $i)];
}


/*获取默认头像*/
function getSysSetting($key)
{
    $sysSettingModel = new \app\admin\model\SysSetting();

    $value_res = $sysSettingModel
        ->where('key',$key)
        ->field("value")
        ->find();

    $value_res = json_decode(json_encode($value_res),true);

    $value = $value_res['value'];

    return $value;

}
