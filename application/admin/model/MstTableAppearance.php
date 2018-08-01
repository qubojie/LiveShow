<?php
/**
 * Created by PhpStorm.
 * User: qubojie
 * Date: 2018/6/23
 * Time: 上午10:32
 */
namespace app\admin\model;

use think\Model;

class MstTableAppearance extends Model
{
    /**
     * 关联到模型的数据表
     * 酒桌区域信息
     *
     * @var string
     */
    protected $table = 'EL_MST_TABLE_APPEARANCE';

    protected $primaryKey = 'appearance_id';

    public $timestamps = false;

}