<?php
/**
 * Created by PhpStorm.
 * User: qubojie
 * Date: 2018/6/23
 * Time: 下午3:40
 */
namespace app\admin\model;

use think\Model;

class ManageDepartment extends Model
{
    /**
     * 关联到模型的数据表
     *
     * @var string
     */
    protected $table = 'EL_MANAGE_DEPARTMENT';

    protected $primaryKey = 'department_id';

    public $timestamps = false;
}