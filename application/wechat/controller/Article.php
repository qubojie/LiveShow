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
use think\Env;
use think\Request;

class Article extends Controller
{
    /**
     * 文章列表
     * @param Request $request
     * @return array
     * @throws \think\exception\DbException
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
            ->order('is_top DESC,sort,created_at DESC')
            ->paginate($pagesize,false,$config);

        $articleInfo = json_decode(json_encode($articleInfo),true);

        $url = Env::get("WEB_ARTICLE_URL").'page/iframe.html?article=';
        for ($i = 0; $i < count($articleInfo['data']); $i ++){
            $link = $articleInfo['data'][$i]['link'];
            $articleInfo['data'][$i]['link'] = $url.$link;
        }

        return $this->com_return(true,config('params.SUCCESS'),$articleInfo);
    }
}