<?php
/**
 * Created by PhpStorm.
 * User: qubojie
 * Date: 2018/7/26
 * Time: 下午12:26
 */
namespace app\wechat\model;

use think\Model;

class BillSubscription extends Model
{
    /**
     * 关联到模型的数据表s
     *
     * @var string
     */
    protected $table = 'el_bill_subscription';

    protected $primaryKey = 'suid';

    public $timestamps = false;
}