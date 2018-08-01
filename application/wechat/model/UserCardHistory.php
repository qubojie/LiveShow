<?php
/**
 * Created by PhpStorm.
 * User: qubojie
 * Date: 2018/6/28
 * Time: 下午4:05
 */
namespace app\wechat\model;

use think\Model;

class UserCardHistory extends Model
{
    /**
     * 关联到模型的数据表s
     *
     * @var string
     */
    protected $table = 'el_user_card_history';

    protected $primaryKey = 'uid';

    public $timestamps = false;
}