<?php
/**
 * Created by PhpStorm.
 * User: qubojie
 * Date: 2018/6/23
 * Time: 上午10:32
 */
namespace app\admin\model;

use think\Model;

class MstTableArea extends Model
{
    /**
     * 关联到模型的数据表
     * 酒桌区域信息
     *
     * @var string
     */
    protected $table = 'EL_MST_TABLE_AREA';

    protected $primaryKey = 'area_id';

    public $timestamps = false;

    public $column = [
        'area_id',
        'area_title',
        'area_desc',
        'turnover_limit',
        'order_rules',
        'sort',
        'is_enable',
        'is_delete',
        'created_at',
        'updated_at',
    ];
}