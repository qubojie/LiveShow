<?php
/**
 * 素材分类.
 * User: qubojie
 * Date: 2018/10/9
 * Time: 下午1:36
 */
namespace app\admin\model;

use think\Model;

class ResourceCategory extends Model
{
    /**
     * 关联到模型的数据表
     * 素材分类
     *
     * @var string
     */
    protected $table = 'EL_RESOURCE_CATEGORY';

    protected $primaryKey = 'cat_id';

    public $timestamps = false;
}
