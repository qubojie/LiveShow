<?php
/**
 * Created by PhpStorm.
 * User: qubojie
 * Date: 2018/6/19
 * Time: 下午4:00
 */
namespace app\admin\model;

use think\Model;

class SysSetting extends Model
{
    /**
     * 关联到模型的数据表
     *
     * @var string
     */
    protected $table = 'el_sys_setting';

    protected $primaryKey = 'key';

    public $timestamps = false;
}