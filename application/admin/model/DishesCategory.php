<?php
/**
 * Created by PhpStorm.
 * User: qubojie
 * Date: 2018/8/14
 * Time: 下午1:57
 */
namespace app\admin\model;

use think\Model;

class DishesCategory extends Model
{
    /**
     * 关联到模型的数据表
     *
     * @var string
     */
    protected $table = 'EL_DISHES_CATEGORY';

    protected $primaryKey = 'cat_id';

    public $timestamps = false;
}