<?php
/**
 * Created by PhpStorm.
 * User: qubojie
 * Date: 2018/6/27
 * Time: 下午3:17
 */

namespace app\admin\model;

use think\Model;

class MstCardVipVoucherRelation extends Model
{
    /**
     * 关联到模型的数据表
     *
     * @var string
     */
    protected $table = 'EL_MST_CARD_VIP_VOUCHER_RELATION';

    public $timestamps = false;
}