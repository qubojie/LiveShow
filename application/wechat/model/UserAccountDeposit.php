<?php
/**
 * Created by PhpStorm.
 * User: qubojie
 * Date: 2018/6/28
 * Time: 下午5:47
 */
namespace app\wechat\model;

use think\Model;

class UserAccountDeposit extends Model
{
    /**
     * 关联到模型的数据表s
     *
     * @var string
     */
    protected $table = 'el_user_account_deposit';

    protected $primaryKey = 'deposit_id';

    public $timestamps = false;
}