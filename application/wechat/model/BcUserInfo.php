<?php
/**
 * Created by PhpStorm.
 * User: qubojie
 * Date: 2018/6/28
 * Time: 上午9:44
 */
namespace app\wechat\model;

use think\Model;

class BcUserInfo extends Model
{
    /**
     * 关联到模型的数据表s
     *
     * @var string
     */
    protected $table = 'el_user_info';

    protected $primaryKey = 'uid';

    public $timestamps = false;
}