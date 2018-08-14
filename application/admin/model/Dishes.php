<?php
/**
 * Created by PhpStorm.
 * User: qubojie
 * Date: 2018/8/14
 * Time: 下午3:42
 */
namespace app\admin\model;

use think\Model;

class Dishes extends Model
{
    /**
     * 关联到模型的数据表
     *
     * @var string
     */
    protected $table = 'EL_DISHES';

    protected $primaryKey = 'dis_id';

    public $timestamps = false;

    public $column = [
        "dis_id",
        "dis_type",
        "dis_sn",
        "dis_name",
        "dis_img",
        "dis_desc",
        "cat_id",
        "att_id",
        "is_normal",
        "normal_price",
        "is_gift",
        "gift_price",
        "is_vip",
        "sort",
        "is_enable",
        "created_at",
        "updated_at"
    ];

}