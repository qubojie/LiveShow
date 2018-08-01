<?php
/**
 * Created by PhpStorm.
 * User: qubojie
 * Date: 2018/7/18
 * Time: 下午3:36
 */
namespace app\wechat\model;

use think\Model;

class JobUser extends Model
{
    /**
     * 关联到模型的数据表s
     *
     * @var string
     */
    protected $table = 'el_job_user';

    protected $primaryKey = 'uid';

    public $timestamps = false;
}