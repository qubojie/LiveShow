<?php
/**
 * Created by PhpStorm.
 * User: qubojie
 * Date: 2018/6/23
 * Time: 上午10:32
 */
namespace app\admin\model;

use think\Model;

class MstGift extends Model
{
    /**
     * 关联到模型的数据表
     *
     * @var string
     */
    protected $table = 'EL_MST_GIFT';

    protected $primaryKey = 'gift_id';

    public $timestamps = false;

    public $column = [
        'gift_id',
        'gift_img',
        'gift_name',
        'gift_desc',
        'gift_amount',
        'created_at',
        'updated_at'
    ];
}