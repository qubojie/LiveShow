<?php
/**
 * Created by PhpStorm.
 * User: qubojie
 * Date: 2018/8/19
 * Time: 上午11:44
 */
namespace app\wechat\model;

use think\Model;

class BillPay extends Model
{
    /**
     * 关联到模型的数据表s
     *
     * @var string
     */
    protected $table = 'el_bill_pay';

    protected $primaryKey = 'pid';

    public $timestamps = false;

    public $column = [
        "pid",
        "trid",
        "uid",
        "sid",
        "sname",
        "type",
        "sale_status",
        "cus_remark",
        "deal_time",
        "pay_time",
        "finish_time",
        "cancel_user",
        "cancel_time",
        "auto_cancel",
        "auto_cancel_time",
        "cancel_reason",
        "check_user",
        "check_time",
        "check_reason",
        "pay_user",
        "order_amount",
        "payable_amount",
        "account_balance",
        "account_cash_gift",
        "discount",
        "deal_amount",
        "gift_vou_code",
        "return_point",
        "pay_type",
        "pay_offline_type",
        "pay_no",
        "receipt_account",
        "is_settlement",
        "settlement_time",
        "settlement_id",
        "created_at",
        "updated_at"
    ];

    public $list_column = [
        "pid",
        "trid",
        "uid",
        "sid",
        "sname",
        "type",
        "sale_status",
        "deal_time",
        "order_amount",
        "payable_amount",
        "is_settlement",
        "settlement_id",
    ];
}