<?php
/**
 * Created by PhpStorm.
 * User: qubojie
 * Date: 2018/6/23
 * Time: 上午11:34
 */

namespace app\admin\model;

use think\Model;

class MstGiftVoucher extends Model
{
    /**
     * 关联到模型的数据表
     *
     * @var string
     */
    protected $table = 'EL_MST_GIFT_VOUCHER';

    protected $primaryKey = 'gift_vou_id';

    public $timestamps = false;

}