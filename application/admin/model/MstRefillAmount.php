<?php
/**
 * Created by PhpStorm.
 * User: qubojie
 * Date: 2018/6/23
 * Time: 上午11:34
 */

namespace app\admin\model;

use think\Model;

class MstRefillAmount extends Model
{
    /**
     * 关联到模型的数据表
     *
     * 充值金额设置表
     *
     * @var string
     */
    protected $table = 'EL_MST_REFILL_AMOUNT';

    protected $primaryKey = 'id';

    public $timestamps = false;

    public $admin_column = [
        "id",
        "amount",
        "cash_gift",
        "desc",
        "sort",
        "is_enable",
        "created_at",
        "updated_at"
    ];

    public $xcx_column = [
        "id",
        "amount",
        "cash_gift",
        "sort",
    ];

}