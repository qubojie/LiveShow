<?php
/**
 * Created by PhpStorm.
 * User: guojing
 * Date: 2018/9/14
 * Time: 12:00
 */

namespace app\wechat\model;


use think\Model;

class TableMessage extends Model
{
    /**
     * 关联到模型的数据表
     *
     * @var string
     */
    protected $table = 'el_table_message';

    protected $primaryKey = 'message_id';

    public $timestamps = false;
}