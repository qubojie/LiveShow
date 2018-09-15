<?php
/**
 * Created by PhpStorm.
 * User: qubojie
 * Date: 2018/7/27
 * Time: 上午10:45
 */
namespace app\admin\model;

use think\Model;

class MstTableAreaCard extends Model
{
    /**
     * 关联到模型的数据表
     *
     * 台位区域与卡的关联信息
     *
     * @var string
     */
    protected $table = 'EL_MST_TABLE_AREA_CARD';

    protected $primaryKey = 'area_id';

    public $timestamps = false;

}