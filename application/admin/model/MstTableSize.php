<?php
/**
 * Created by PhpStorm.
 * User: qubojie
 * Date: 2018/6/23
 * Time: 上午10:32
 */
namespace app\admin\model;

use think\Model;

class MstTableSize extends Model
{
    /**
     * 关联到模型的数据表
     * 酒桌容量
     *
     * @var string
     */
    protected $table = 'EL_MST_TABLE_SIZE';

    protected $primaryKey = 'size_id';

    public $timestamps = false;

}