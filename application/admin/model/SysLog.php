<?php
/**
 * Created by PhpStorm.
 * User: qubojie
 * Date: 2018/6/20
 * Time: 下午6:21
 */
namespace app\admin\model;

use think\Controller;

class SysLog extends Controller
{
    /**
     * 关联到模型的数据表
     *
     * @var string
     */
    protected $table = 'el_sys_log';

    protected $primaryKey = 'log_id';

    public $timestamps = false;

}