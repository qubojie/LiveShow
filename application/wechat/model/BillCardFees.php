<?php
/**
 * Created by PhpStorm.
 * User: qubojie
 * Date: 2018/6/30
 * Time: 下午2:29
 */
namespace app\wechat\model;

use think\Model;

class BillCardFees extends Model
{
    /**
     * 关联到模型的数据表s
     *
     * @var string
     */
    protected $table = 'el_bill_card_fees';

    protected $primaryKey = 'vid';

    public $timestamps = false;

    public $get_column = [
        'bc.vid',               //充值缴费单
        'bc.uid',               //用户id
        'bc.referrer_type',     //推荐人类型    ‘vip’ 会籍  ‘sales’营销  ‘user’ 会员  ‘empty’ 无
        'bc.referrer_id',       //推荐人id
        'bc.cus_remark',        //买家留言 暂时保留
        'bc.sale_status',       //单据状态  0待付款    1 付款完开卡赠品待发货   2赠品已发货待收货  3 赠品收货确认   9交易取消
        'bc.deal_time',         //成交时间
        'bc.pay_time',          //付款时间
        'bc.delivery_time',     //发货时间
        'bc.cancel_user',       //取消人，系统‘sys’ ，货主‘self’ ，回收人‘recycle’ ，悦商客服 ‘管理员用户名’
        'bc.cancel_time',       //单据取消时间
        'bc.auto_cancel',       //是否自动取消  0 手工取消  1自动取消
        'bc.auto_cancel_time',  //单据自动取消的时间
        'bc.is_refund',         //取消单据时是否产生退款  0未产生  1产生
        'bc.refund_amount',     //实际退款金额
        'bc.cancel_reason',     //订单取消原因   系统自动取消（“时限内未付款”）
        'bc.finish_time',       //订单完成时间
        'bc.auto_finish',       //是否自动完成 0 客户确认 1系统自动
        'bc.auto_finish_time',  //自动订单完成时间  默认 按 delivery_time+ 系统设置的自动收货天数
        'bc.order_amount',      //订单金额（计算应等于 order_amount=payable_amount+discount）
        'bc.payable_amount',    //线上应付且未付金额
        'bc.deal_price',        //实付金额
        'bc.discount',          //折扣 暂保留
        'bc.pay_type',          //支付方式   微信‘wxpay’    支付宝 ‘alipay’    线下银行转账 ‘bank’   现金‘cash’
        'bc.pay_no',            //支付回单号（对方流水单号）
        'bc.pay_name',          //付款人或公司名称
        'bc.pay_bank',          //付款方开户行
        'bc.pay_account',       //付款方账号
        'bc.pay_bank_time',     //银行转账付款时间或现金支付时间
        'bc.receipt_name',      //收款账户或收款人
        'bc.receipt_bank',      //收款银行
        'bc.receipt_account',   //收款账号
        'bc.is_settlement',     //是否结算佣金  0未结算   1已结算   没有邀请人的默认1已结算
        'bc.settlement_time',   //佣金结算时间
        'bc.settlement_id',     //结算单号
        'bc.commission_ratio',  //下单时的佣金比例   百分比整数     没有推荐人的自动为0
        'bc.commission',        //支付给 推荐人佣金金额  没推荐人的自动为0
        'bc.send_type',         //赠品发货类型   ‘express’ 快递     ‘salesman’销售
        'bc.delivery_name',     //收货人姓名
        'bc.delivery_phone',    //收货人电话
        'bc.delivery_area',     //收货人区域
        'bc.delivery_address',  //收货人详细地址
        'bc.express_name',      //代收货人姓名
        'bc.express_company',   //收货人物流公司
        'bc.express_number',    //物流单号
        'bc.created_at',        //数据创建时间
        'bc.updated_at',        //最后更新时间
    ];

    public $card_gift_column = [
        "bcf.card_id",              //vip卡id
        "bcf.card_type",            //卡片类型   ‘vip’会籍卡      ‘value’ 储值卡
        "bcf.card_name",            //VIP卡名称
        "bcf.card_level",           //vip卡级别名称
        "bcf.card_image",           //VIP卡背景图
        "bcf.card_desc",            //VIP卡使用说明及其他描述
        "bcf.card_equities",        //卡片享受权益详情
        "bcf.card_deposit",         //卡片权益保证金额
        "bcf.card_amount",          //充值金额   类型为VIP时  金额默认为0
        "bcf.card_point",           //开卡赠送积分
        "bcf.card_cash_gift",       //开卡赠送礼金
        "bcf.card_job_cash_gif",    //推荐人返佣礼金
        "bcf.card_job_commission",  //推荐人返佣金
        "bcf.gift_id",              //礼品id
        "bcf.gift_img",             //礼品图片
        "bcf.gift_name",            //礼品名称标题
        "bcf.gift_desc",            //礼品详细描述
        "bcf.gift_amount"           //礼品金额
    ];

    public $user_column = [
        "u.phone",      //电话号码
        "u.name",       //真实姓名
        "u.nickname",   //昵称
        "u.avatar",     //头像
    ];

}