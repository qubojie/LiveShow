<?php
/**
 * Created by PhpStorm.
 * User: qubojie
 * Date: 2018/7/6
 * Time: 上午10:40
 */
namespace app\wechat\controller;

use app\admin\model\PageArticles;
use think\Controller;
use think\Request;

class Article extends Controller
{
    /**
     * 文章列表
     * @param Request $request
     * @return array
     */
    public function getArticle(Request $request)
    {
        $pagesize = $request->param("pagesize",config('PAGESIZE'));//显示个数,不传时为10

        if (empty($pagesize)){
            $pagesize = config('PAGESIZE');
        }

        $nowPage = $request->param("nowPage","1");

        $config = [
            "page" => $nowPage,
        ];

        $articleModel = new PageArticles();

        $articleInfo = $articleModel
            ->where('is_show',1)
            ->paginate($pagesize,false,$config);

        return $this->com_return(true,config('params.SUCCESS'),$articleInfo);
    }
}