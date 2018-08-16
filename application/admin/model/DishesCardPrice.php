<?php
/**
 * Created by PhpStorm.
 * User: qubojie
 * Date: 2018/8/16
 * Time: 下午2:34
 */
namespace app\admin\model;

use think\Model;

class DishesCardPrice extends Model
{
    /**
     * 关联到模型的数据表
     *
     * @var string
     */
    protected $table = 'EL_DISHES_CARD_PRICE';

    public $timestamps = false;
}