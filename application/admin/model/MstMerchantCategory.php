<?php
/**
 * 联盟商家分类
 * User: guojing
 * Date: 2018/9/13
 * Time: 14:01
 */

namespace app\admin\model;


use think\Model;

class MstMerchantCategory extends Model
{
    /**
     * 关联到模型的数据表
     *
     * @var string
     */
    protected $table = 'EL_MST_MERCHANT_CATEGORY';

    protected $primaryKey = 'cat_id';

    public $timestamps = false;
}