<?php
/**
 * Created by PhpStorm.
 * User: qubojie
 * Date: 2018/6/30
 * Time: 下午2:29
 */
namespace app\wechat\model;

use think\Model;

class BillCardFeesDetail extends Model
{
    /**
     * 关联到模型的数据表s
     *
     * @var string
     */
    protected $table = 'el_bill_card_fees_detail';

    protected $primaryKey = 'vid';

    public $timestamps = false;
}