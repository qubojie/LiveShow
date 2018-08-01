<?php
/**
 * 潜在用户信息表.
 * User: qubojie
 * Date: 2018/6/25
 * Time: 下午2:09
 */
namespace app\admin\model;

use think\Model;

class ManagePotentialUser extends Model
{
    /**
     * 关联到模型的数据表
     *
     * @var string
     */
    protected $table = 'EL_MANAGE_POTENTIAL_USER';

    protected $primaryKey = 'puid';

    public $timestamps = false;
}