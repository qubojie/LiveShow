<?php
/**
 * 素材文件信息表.
 * User: qubojie
 * Date: 2018/10/9
 * Time: 下午2:04
 */
namespace app\admin\model;

use think\Model;

class ResourceFile extends Model
{
    /**
     * 关联到模型的数据表
     * 素材分类
     *
     * @var string
     */
    protected $table = 'EL_RESOURCE_FILE';

    protected $primaryKey = 'id';

    public $timestamps = false;
}