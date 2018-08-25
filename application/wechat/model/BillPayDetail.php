<?php
/**
 * Created by PhpStorm.
 * User: qubojie
 * Date: 2018/8/19
 * Time: 上午11:44
 */
namespace app\wechat\model;

use think\Model;

class BillPayDetail extends Model
{
    /**
     * 关联到模型的数据表s
     *
     * @var string
     */
    protected $table      = 'el_bill_pay_detail';

    protected $primaryKey = 'id';

    public $timestamps    = false;

    public $column = [
        "id",
        "parent_id",
        "pid",
        "trid",
        "is_refund",
        "is_give",
        "dis_id",
        "dis_type",
        "dis_sn",
        "dis_name",
        "dis_desc",
        "quantity",
        "price",
        "amount"
    ];
}