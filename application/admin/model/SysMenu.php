<?php
/**
 * Created by PhpStorm.
 * User: qubojie
 * Date: 2018/6/20
 * Time: 上午10:30
 */
namespace app\admin\model;

use think\Model;

class SysMenu extends Model
{
    /**
     * 关联到模型的数据表
     *
     * @var string
     */
    protected $table = 'EL_SYS_MENU';

    public $timestamps = false;
}