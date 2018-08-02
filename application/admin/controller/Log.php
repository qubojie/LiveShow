<?php
/**
 * 日志操作类.
 * User: qubojie
 * Date: 2018/6/20
 * Time: 下午6:16
 */
namespace app\admin\controller;

use app\admin\model\SysLog;
use think\Controller;
use think\Db;
use think\Request;

class Log extends Controller
{
    /*
     * 写入日志
     * */
    public function log_insert($params)
    {
        Db::name("sys_log")
            ->insert($params);
    }

    /**
     * 用户操作日志列表
     * @param $type
     * @param $val
     * @return false|\PDOStatement|string|\think\Collection
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function log_list($type,$val)
    {
        $actionLogModel = Db::name('sys_adminaction_log');

        $res = $actionLogModel
            ->where($type,$val)
            ->select();

        return $res;
    }

}