<?php
/**
 * Created by PhpStorm.
 * User: qubojie
 * Date: 2018/6/22
 * Time: 下午1:45
 */
namespace app\admin\model;

use think\Model;

class PageArticles extends Model
{
    /**
     * 关联到模型的数据表
     *
     * @var string
     */
    protected $table = 'EL_PAGE_ARTICLE';

    protected $primaryKey = 'article_id';

    public $timestamps = false;

    public $column = [

    ];
}