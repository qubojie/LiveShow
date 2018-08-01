<?php
/**
 * VIP会员卡信息设置 验证器类.
 * User: qubojie
 * Date: 2018/6/26
 * Time: 下午5:36
 */
namespace app\admin\validate;

use think\Validate;

class MstCardVip extends Validate
{
    //规则
    protected $rule = [
        'card_id'        =>  'require',  //VIP卡名称id
        'card_type'      =>  'require|max:10',  //卡片类型   ‘vip’会籍卡      ‘value’ 储值卡
        'card_name'      =>  'require|max:30|unique:mst_card_vip',  //VIP卡名称
        'card_level'     =>  'require|max:30', //vip卡级别名称
        'card_image'     =>  'require', //VIP卡背景图
        'card_no_prefix' =>  'require|max:2', //卡号前缀（两位数字）
        'card_desc'      =>  'require', //VIP卡使用说明及其他描述
        'card_equities'  =>  'require', //卡片享受权益详情
        'card_amount'    =>  'require', //充值金额   类型为VIP时  金额默认为0
        'card_deposit'   =>  'require', //卡片权益保证金额 金额默认为0
        'card_point'     =>  'require', //开卡赠送积分
        'is_enable'      =>  'require', //是否启用  0否 1是
    ];

    //提示信息
    protected $message = [
        'card_id.require'        =>  '卡片id必须',
        'card_type.require'      =>  '卡片类型必须',
        'card_type.max'          =>  '卡片类型最大长度10',
        'card_name.require'      =>  'VIP卡名称必须',
        'card_name.max'          =>  'VIP卡名称最大长度10',
        'card_name.unique'       =>  'VIP卡名称已存在',
        'card_level.require'     =>  'VIP卡级别名称必须',
        'card_level.max'         =>  'VIP卡级别名称最大长度10',
        'card_image.require'     =>  'VIP卡背景图必须',
        'card_no_prefix.require' =>  '卡号前缀必须',
        'card_no_prefix.max'     =>  '卡号前缀最大长度2',
        'card_desc.require'      =>  'VIP卡使用说明及其他描述必须',
        'card_equities.require'  =>  '卡片享受权益详情必须',
        'card_amount.require'    =>  '充值金额必须',
        'card_deposit.require'   =>  '卡片权益保证金额必须',
        'card_point.require'     =>  '开卡赠送积分必须',
        'is_enable.require'      =>  '是否启用必须'
    ];

    //验证场景限制
    protected $scene = [
        'add'    =>  ['card_type','card_name','card_level','card_image','card_no_prefix','card_desc','card_equities','card_amount','card_deposit','card_point','is_enable'],
        'edit'   =>  ['card_id','card_type','card_name','card_level','card_image','card_no_prefix','card_desc','card_equities','card_amount','card_deposit','card_point','is_enable'],
        'delete' =>  ['card_id'],
    ];
}