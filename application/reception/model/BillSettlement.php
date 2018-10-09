<?php
/**
 * Created by PhpStorm.
 * User: qubojie
 * Date: 2018/9/27
 * Time: 上午10:59
 */
namespace app\reception\model;

use think\Model;

class BillSettlement extends Model
{
    /**
     * 关联到模型的数据表
     *
     * @var string
     */
    protected $table = 'el_bill_settlement';

    public $timestamps = false;
}