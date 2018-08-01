<?php
/**
 * Created by PhpStorm.
 * User: qubojie
 * Date: 2018/6/20
 * Time: 上午9:30
 */
namespace app\admin\model;

use think\Model;

class SysRole extends Model
{
    /**
     * 关联到模型的数据表
     *
     * @var string
     */
    protected $table = 'EL_SYS_ROLE';

    protected $primaryKey = 'role_id';

    public $timestamps = false;
}