<?php
/**
 * Created by PhpStorm.
 * User: qubojie
 * Date: 2018/8/3
 * Time: 下午3:23
 */
namespace app\wechat\model;

use think\Model;

class BillRefill extends Model
{
    /**
     * 关联到模型的数据表s
     *
     * @var string
     */
    protected $table = 'el_bill_refill';

    protected $primaryKey = 'rfid';

    public $timestamps = false;

    public $admin_column = [
        "rfid",//
        "referrer_type",
        "referrer_id",
        "uid",
        "cus_remark",
        "pay_type",
        "pay_time",
        "pay_no",
        "amount",
        "cash_gift",
        "status",
        "pay_name",
        "pay_bank",
        "pay_account",
        "pay_bank_time",
        "receipt_name",
        "receipt_bank",
        "receipt_account",
        "pay_user",
        "review_time",
        "review_user",
        "review_desc",
        "created_at",
        "updated_at"
    ];


}