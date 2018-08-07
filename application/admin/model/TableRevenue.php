<?php
/**
 * Created by PhpStorm.
 * User: qubojie
 * Date: 2018/6/23
 * Time: 上午10:32
 */
namespace app\admin\model;

use think\Model;

class TableRevenue extends Model
{
    /**
     * 关联到模型的数据表
     * 酒桌区域信息
     *
     * @var string
     */
    protected $table = 'EL_TABLE_REVENUE';

    protected $primaryKey = 'trid';

    public $timestamps = false;

    public $column = [
        'trid',                 //台位预定id  前缀T
        'uid',                  //用户id
        'is_join',              //是否拼桌 0否  1是
        'parent_trid',          //拼桌的主桌ID
        'table_id',             //酒桌id
        'table_no',             //台号
        'area_id',              //区域id
        'status',               //订台状态   0待付定金或结算   1 预定成功   2已开台  3已清台   9取消预约
        'turnover_limit',       //台位最低消费 0表示无最低消费（保留）
        'reserve_way',          //预定途径  client  客户预订（小程序）    service 服务端预定（内部小程序）   manage 管理端预定（pc管理端暂保留）
        'reserve_period',       //预约时段   0早场 8:00-10:00      1晚场 10点以后
        'reserve_time',         //预约时间
        'reserve_people',       //预约人数
        'ssid',                 //订台服务人员id
        'ssname',               //订台服务人员姓名
        'sid',                  //服务人员id
        'sname',                //服务人员姓名
        'is_subscription',      //是否收取定金或订单  0 是  1 否
        'subscription_time',    //订金支付时间
        'subscription_type',    //定金类型   0无订金   1订金   2订单
        'subscription',         //订金或订单金额
        'turnover_num',         //台位订单数量
        'turnover',             //订单金额
        'is_refund',            //产生退款  0未产生  1产生
        'refund_num',           //退款单数
        'refund_amount',        //实际退款金额
        'cancel_user',          //取消人，系统‘sys’ ，会员‘user’ ，管理员‘后台管理员名称’
        'cancel_time',          //取消时间
        'cancel_reason',        //订单取消原因   系统自动取消（“时限内未付款”）
        'created_at',           //数据创建时间
        'updated_at',           //最后更新时间
    ];

    public $revenue_column = [
        "tr.trid",
        "tr.uid",
        "tr.is_join",
        "tr.parent_trid",
        "tr.table_id",
        "tr.table_no",
        "tr.area_id",
        "tr.status",
        "tr.reserve_time",
        "tr.ssid",
        "tr.ssname",
        "tr.is_subscription",
        "tr.subscription_type",
        "tr.subscription",
        "tr.turnover_num",
        "tr.turnover",
        "tr.is_refund",
        "tr.refund_num",
        "tr.refund_amount",
        "tr.refund_amount"
    ];
}