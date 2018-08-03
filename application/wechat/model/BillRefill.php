<?php
/**
 * Created by PhpStorm.
 * User: qubojie
 * Date: 2018/8/3
 * Time: 下午3:23
 */
namespace app\wechat\model;

use think\Model;

class BillRefill extends Model
{
    /**
     * 关联到模型的数据表s
     *
     * @var string
     */
    protected $table = 'el_bill_refill';

    protected $primaryKey = 'rfid';

    public $timestamps = false;
}