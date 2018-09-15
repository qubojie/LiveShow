<?php
/**
 * 联盟商家
 * User: guojing
 * Date: 2018/9/13
 * Time: 14:27
 */

namespace app\admin\model;


use think\Model;

class MstMerchant extends Model
{
    /**
     * 关联到模型的数据表
     *
     * @var string
     */
    protected $table = 'EL_MST_MERCHANT';

    protected $primaryKey = 'merchant_id';

    public $timestamps = false;

    public $column = [
        "merchant_id",
        "cat_id",
        "merchant",
        "merchant_desc",
        "address",
        "sort",
        "is_enable",
        "created_at",
        "updated_at"
    ];
}