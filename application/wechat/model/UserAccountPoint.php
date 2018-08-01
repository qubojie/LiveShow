<?php
/**
 * 用户积分明细.
 * User: qubojie
 * Date: 2018/7/14
 * Time: 上午11:25
 */
namespace app\wechat\model;

use think\Model;

class UserAccountPoint extends Model
{
    /**
     * 关联到模型的数据表s
     *
     * @var string
     */
    protected $table = 'el_user_account_point';

    protected $primaryKey = 'point_id';

    public $timestamps = false;
}