<?php
/**
 * Created by PhpStorm.
 * User: qubojie
 * Date: 2018/7/24
 * Time: 下午3:05
 */
namespace app\admin\model;

use think\Model;

class MstTableReserveDate extends Model
{
    /**
     * 关联到模型的数据表
     * 特殊指定预定日期信息设置表
     *
     * @var string
     */
    protected $table = 'EL_MST_TABLE_RESERVE_DATE';

    protected $primaryKey = 'appointment';

    public $timestamps = false;

    public $column = [
        'appointment',
//        'subscription',
//        'turnover_limit',
        'desc',
        'is_expiry',
        'created_at',
        'updated_at',
    ];
}