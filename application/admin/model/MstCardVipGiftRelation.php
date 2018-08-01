<?php
/**
 * VIP开卡赠送礼品关系表.
 * User: qubojie
 * Date: 2018/6/27
 * Time: 下午2:07
 */
namespace app\admin\model;

use think\Model;

class MstCardVipGiftRelation extends Model
{
    /**
     * 关联到模型的数据表
     *
     * @var string
     */
    protected $table = 'EL_MST_CARD_VIP_GIFT_RELATION';

    public $timestamps = false;
}