<?php
/**
 * Created by PhpStorm.
 * User: qubojie
 * Date: 2018/6/28
 * Time: 下午3:46
 */
namespace app\wechat\model;

use think\Model;

class UserCard extends Model
{
    /**
     * 关联到模型的数据表s
     *
     * @var string
     */
    protected $table = 'el_user_card';

    protected $primaryKey = 'uid';

    public $timestamps = false;
}
