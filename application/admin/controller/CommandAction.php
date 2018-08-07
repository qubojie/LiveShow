<?php
/**
 * Created by PhpStorm.
 * User: qubojie
 * Date: 2018/6/19
 * Time: 下午5:35
 */
/**
 * +-------------------------------------------------------
 * |公共控制器
 * |此后的模块，如果需要判断是否登录的化，直接继承此控制器
 * |此控制器直接继承核心控制器
 * +-------------------------------------------------------
 */

namespace app\admin\controller;

use app\admin\model\SysAdminUser;
use app\admin\model\SysLog;
use app\admin\model\User;
use think\Controller;
use think\Db;
use think\exception\HttpException;
use think\Hook;
use think\Request;
use think\Response;

class CommandAction extends Controller
{
    /**
     * 初始化方法,其余控制器继承此方法，进行判断登录
     * @
     */
    public function _initialize()
    {
        parent::_initialize();
        $method = Request::instance()->method();

        if ( $method != "OPTIONS"){

            $Authorization = Request::instance()->header("authorization","");

            if (!empty($Authorization)){

                $sysModel = new SysAdminUser();
                $is_exist = $sysModel
                    ->where("token",$Authorization)
                    ->count();

                if ($is_exist){

                }else{
                    throw new HttpException(403,'登陆失效');
                }

            }else{
                throw new HttpException(403,'登陆失效');
            }
        }
    }

    /**
     * 记录禁止登陆解禁登陆 单据操作等日志
     * @param string $uid           '被操作用户id'
     * @param string $gid           '被操作的的商品id'
     * @param string $oid           '相关单据id'
     * @param string $action        '操作内容'
     * @param string $reason        '操作原因描述'
     * @param string $action_user   '操作管理员id'
     * @param string $action_time   '操作时间'
     */
    public function addSysAdminLog($uid = '',$gid = '',$oid = '',$action = 'empty',$reason = '',$action_user = '',$action_time = '')
    {
        $params  = [
            'uid'         => $uid,
            'gid'         => $gid,
            'oid'         => $oid,
            'action'      => $action,
            'reason'      => $reason,
            'action_user' => $action_user,
            'action_time' => $action_time,
        ];

        Db::name('sys_adminaction_log')
            ->insert($params);
    }

    /**
     * 记录系统操作日志
     * @param $log_time     '记录时间'
     * @param $action_user  '操作管理员名'
     * @param $log_info     '操作描述'
     * @param $ip_address   '操作登录的地址'
     * @return void
     */
    public function addSysLog($log_time,$action_user,$log_info,$ip_address)
    {
        if (empty($log_time)){
            $log_time = time();
        }

        if (empty($log_info)){
            $log_info =  '未记录到';
        }

        if (empty($action_user)){
            $action_user =  0;
        }

        if (empty($ip_address)){
            $ip_address =  '0.0.0.0';
        }

        $params = [
            'log_time'   => $log_time,
            'action_user'=> $action_user,
            'log_info'   => $log_info,
            'ip_address' => $ip_address
        ];

        $log = new Log();

        $log->log_insert($params);

    }

    /**
     * 获取登陆管理人员信息
     * @param $token
     * @return mixed
     */
    public function getLoginAdminId($token)
    {
        $id_res = SysAdminUser::get(['token' => $token],false)->toArray();
        return $id_res;
    }

    /*
     * 加密token
     * */
    public function jm_token($str)
    {
        return md5(sha1($str).time());
    }

    /**
     * 根据电话号码获取用户信息
     * @param $phone
     * @return array|false|mixed|\PDOStatement|string|\think\Model
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function userPhoneGetInfo($phone)
    {
        $userModel = new User();

        $column = $userModel->column;

        $userInfo = $userModel
            ->where("phone",$phone)
            ->field($column)->find();
        $userInfo = json_decode(json_encode($userInfo),true);

        return $userInfo;
    }
}