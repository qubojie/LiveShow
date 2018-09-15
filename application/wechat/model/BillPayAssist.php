<?php
/**
 * Created by PhpStorm.
 * User: qubojie
 * Date: 2018/8/19
 * Time: 上午11:44
 */
namespace app\wechat\model;

use think\Model;

class BillPayAssist extends Model
{
    /**
     * 关联到模型的数据表s
     *
     * @var string
     */
    protected $table = 'el_bill_pay_assist';

    public $timestamps = false;

    public $r_column = [
        "pid",
        "uid",
        "card_name",
        "phone",
        "verification_code",
        "table_id",
        "table_no",
        "sid",
        "sname",
        "type",
        "sale_status",
        "gift_vou_code",
        "referrer_id",
        "updated_at"
    ];

}