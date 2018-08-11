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
 * 递归方式把数组或字符串 null转换为空''字符串
 * @param $arr
 * @return array|string
 */
function _unsetNull($arr){
    if ($arr !== null){
        if (is_array($arr)){
            if (!empty($arr)){
                foreach ($arr as $key => $val){
                   if ($val === null)  $arr[$key] = '';
                   else  $arr[$key] = _unsetNull($val);//递归,再去执行
                }
            }else $arr = '';

        }else if ($arr === null)  $arr = '';

    }else $arr = '';

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


/**
 * 删除数组中指定的key
 * @param $arr
 * @param $keys '多个以逗号隔开'
 * @return mixed
 */
function array_remove($arr, $keys){

    $key_arr = explode(",",$keys);

    for ($i = 0; $i < count($key_arr); $i ++){
        $key = $key_arr[$i];
        if (!array_key_exists($key, $arr)) {
            return $arr;
        }
        $keys = array_keys($arr);
        $index = array_search($key, $keys);
        if ($index !== FALSE) {
            array_splice($arr, $index, 1);
        }
    }
    return $arr;
}

 /**
 * 获取默认头像
 * @param $key
 * @return mixed
 * @throws \think\db\exception\DataNotFoundException
 * @throws \think\db\exception\ModelNotFoundException
 * @throws \think\exception\DbException
 */
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


/**
 * 酒桌操作记录日志(预约,取消预约,转台,转拼,开拼,开台等操作)
 * @param $log_time
 * @param $type
 * @param $table_id
 * @param $table_no
 * @param $action_user
 * @param $desc
 * @param string $table_o_id
 * @param string $table_o_no
 * @return bool
 */

function insertTableActionLog($log_time,$type,$table_id,$table_no,$action_user,$desc,$table_o_id = "",$table_o_no = "")
{
    $params = [
        "log_time"     => $log_time,
        "type"         => $type,
        "table_id"     => $table_id,
        "table_no"     => $table_no,
        "action_user"  => $action_user,
        "desc"         => $desc,
        "table_o_id"   => $table_o_id,
        "table_o_no"   => $table_o_no,
    ];

    $is_ok = \think\Db::name("table_log")
        ->insert($params);

    if ($is_ok){
        return true;
    }else{
        return false;
    }

}

/**
 * 根据uid获取用户信息
 * @param $uid
 * @return array|false|PDOStatement|string|\think\Model
 * @throws \think\db\exception\DataNotFoundException
 * @throws \think\db\exception\ModelNotFoundException
 * @throws \think\exception\DbException
 */
function getUserInfo($uid)
{
    $userModel = new \app\admin\model\User();

    $column = $userModel->column;

    $user_info = $userModel
        ->where('uid',$uid)
        ->field($column)
        ->find();

    $user_info = json_decode(json_encode($user_info),true);

    return $user_info;
}
