<?php
/**
 * Created by PhpStorm.
 * User: qubojie
 * Date: 2018/6/22
 * Time: 上午11:28
 */
namespace app\admin\model;

use think\Model;

class MstUserLevel extends Model
{
    /**
     * 关联到模型的数据表
     *
     * @var string
     */
    protected $table = 'EL_MST_USER_LEVEL';

    protected $primaryKey = 'level_id';

    public $timestamps = false;

}