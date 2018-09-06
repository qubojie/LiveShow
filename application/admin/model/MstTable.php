<?php
/**
 * Created by PhpStorm.
 * User: qubojie
 * Date: 2018/6/23
 * Time: 上午10:32
 */
namespace app\admin\model;

use think\Model;

class MstTable extends Model
{
    /**
     * 关联到模型的数据表
     * 酒桌区域信息
     *
     * @var string
     */
    protected $table = 'EL_MST_TABLE';

    protected $primaryKey = 'table_id';

    public $timestamps = false;

    public $column = [
        'table_id',
        'table_no',
        'appearance_id',
        'size_id',
        'area_id',
        'reserve_type',//台位预定类型   all全部无限制  vip 会员用户  normal  普通用户   keep  保留
        'turnover_limit_l1',
        'turnover_limit_l2',
        'turnover_limit_l3',
        'subscription_l1',
        'subscription_l2',
        'subscription_l3',
        'people_max',
        'table_desc',
        'sort',
        'is_enable',
        'is_delete',
        'created_at',
        'updated_at',
    ];
}