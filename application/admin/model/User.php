<?php
/**
 * Created by PhpStorm.
 * User: qubojie
 * Date: 2018/6/23
 * Time: 上午9:43
 */
namespace app\admin\model;

use think\Model;

class User extends Model
{
    /**
     * 关联到模型的数据表
     *
     * @var string
     */
    protected $table = 'el_user';

    protected $primaryKey = 'uid';

    public $timestamps = false;

    public $column = [
        'uid',
        'phone',
        'wxid',
        'wx_number',
        'email',
        'name',
        'nickname',
        'avatar',
        'sex',
        'province',
        'city',
        'country',
        'account_balance',
        'account_freeze',
        'account_cash',
        'account_deposit',
        'account_cash_gift',
        'status',
        'user_status',
        'level_id',
        'credit_point',
        'account_point',
        'referrer_type',
        'referrer_id',
        'remember_token',
        'created_at'
    ];

    public $u_column = [
        'u.uid',
        'u.phone',
        'u.wxid',
        'u.wx_number',
        'u.email',
        'u.name',
        'u.nickname',
        'u.avatar',
        'u.sex',
        'u.province',
        'u.city',
        'u.country',
        'u.account_balance',
        'u.account_freeze',
        'u.account_cash',
        'u.account_deposit',
        'u.account_cash_gift',
        'u.register_way',
        'u.lastlogin_time',
        'u.status',
        'u.user_status',
        'u.level_id',
        'u.credit_point',
        'u.account_point',
        'u.referrer_type',
        'u.referrer_id',
        'u.remember_token',
        'u.created_at'
    ];
}