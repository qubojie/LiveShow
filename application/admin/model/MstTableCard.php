<?php
/**
 * Created by PhpStorm.
 * User: qubojie
 * Date: 2018/9/5
 * Time: 下午2:58
 */
namespace app\admin\model;

use think\Model;

class MstTableCard extends Model
{
    /**
     * 关联到模型的数据表
     *
     * 台位区域与卡的关联信息
     *
     * @var string
     */
    protected $table = 'EL_MST_TABLE_CARD';

    public $timestamps = false;
}