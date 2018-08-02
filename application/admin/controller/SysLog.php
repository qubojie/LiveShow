<?php
/**
 * Created by PhpStorm.
 * User: qubojie
 * Date: 2018/8/2
 * Time: 上午10:34
 */
namespace app\admin\controller;

use think\Request;

class SysLog extends CommandAction
{
    /**
     * 系统日志列表
     * @param Request $request
     * @return \think\Paginator
     * @throws \think\exception\DbException
     */
    public function sysLogList(Request $request)
    {
        $pagesize   = $request->param("pagesize",config('PAGESIZE'));//当前页,不传时为10
        $nowPage    = $request->param("nowPage","1");

        if (empty($pagesize)){
            $pagesize = config('PAGESIZE');
        }

        $config = [
            "page" => $nowPage,
        ];

        $sysLogModel = new \app\admin\model\SysLog();

        $log_list = $sysLogModel
            ->order("log_time DESC")
            ->paginate($pagesize,false,$config);

        return $log_list;
    }
}