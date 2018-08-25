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
        'type', //日期类型   0普通日  1周末假日  2节假日
//        'subscription',
//        'turnover_limit',
        'desc',
        'is_revenue',//是否允许预定  0否  1是
        'is_refund_sub',//是否可退押金 0不退  1退
        'is_expiry',//是否启用  0否 1是
        'created_at',
        'updated_at',
    ];
}