<?php
/**
 * 首页文章管理
 * User: qubojie
 * Date: 2018/6/22
 * Time: 下午1:43
 */
namespace app\admin\controller;

use app\admin\model\PageArticles;
use think\Db;
use think\Env;
use think\Exception;
use think\Request;
use think\Validate;

class HomeArticles extends CommandAction
{
    /**
     * 文章列表
     * @param Request $request
     * @return array
     */
    public function index(Request $request)
    {
        $common = new Common();

        $pageArticleModel = new PageArticles();

        $sort     = $request->param("sort","desc");//排序方式
        $orderBy  = $request->param("orderBy","created_at");//排序依据
        $keyword  = $request->param("keyword","");//关键字搜索
        $nowPage  = $request->param("nowPage","1");//当前页,不传时为1
        $pagesize = $request->param("pagesize",config('PAGESIZE'));//当前页,不传时为10

        $order = "is_top desc".",".$orderBy." ".$sort; //排序方式

        $where = [];
        if (!empty($keyword)){
            $where['article_title'] = ['like', "%$keyword%"];;
        }
        $query = $pageArticleModel
            ->where($where);

        $count   = $query -> count();//统计记录数

        $pageNum = ceil($count/$pagesize); //总页数

        $config = [
            "page" => $nowPage,
            "total" => $pageNum
        ];
        $res = $query
            ->order($order)
            ->where($where)
            ->paginate($pagesize,'',$config);

        $res = json_decode(json_encode($res),true);

        $data = $res['data'];

        $url = Env::get("WEB_ARTICLE_URL").'page/iframe.html?article=';

        for ($i = 0; $i < count($data); $i++){
            $link = $data[$i]["link"];
            $data[$i]["link"] = str_replace("$url","",$link);
        }

        $res['data'] = arrIntToString($data);

        return $common->com_return(true,"获取成功",$res);

    }

    /**
     * 是否置顶
     * @param Request $request
     * @return array
     */
    public function is_top(Request $request)
    {
        $common = new Common();
        $article_id = $request->param("article_id","");
        $is_top     = (int)$request->param("is_top","");

        if ($is_top == "1"){
            $success_message = "置顶成功";
            $fail_message = "置顶失败";
        }else{
            $success_message = "取消置顶成功";
            $fail_message = "取消置顶失败";
        }

        if (!empty($article_id)){
            $update_data = [
                "is_top" => $is_top
            ];
            $sysPageModel = new PageArticles();

            Db::startTrans();
            try{
                $is_ok = $sysPageModel
                    ->where("article_id",$article_id)
                    ->update($update_data);
                if ($is_ok !== false){
                    Db::commit();
                    return $common->com_return(true, $success_message);
                }else{
                    return $common->com_return(false, $fail_message);
                }
            }catch (Exception $e){
                Db::rollback();
                return $common->com_return(false, $e->getMessage());
            }
        }else {
            return $common->com_return(false, "缺少参数");
        }
    }

    /**
     * 是否显示
     * @param Request $request
     * @return array
     */
    public function is_show(Request $request)
    {
        $common      = new Common();
        $article_id  = $request->param("article_id","");
        $is_show     = (int)$request->param("is_show","");
        if ($is_show == "1"){
            $success_message = "已显示";
            $fail_message = "操作失败";
        }else{
            $success_message = "已隐藏";
            $fail_message = "隐藏失败";
        }
        if (!empty($article_id)){
            $update_data = [
                "is_show" => $is_show
            ];
            $sysPageModel = new PageArticles();
            Db::startTrans();
            try{
                $is_ok = $sysPageModel
                    ->where("article_id",$article_id)
                    ->update($update_data);
                if ($is_ok !== false){
                    Db::commit();
                    return $common->com_return(true, $success_message);
                }else{
                    return $common->com_return(false, $fail_message);
                }
            }catch (Exception $e){
                Db::rollback();
                return $common->com_return(false, $e->getMessage());
            }
        }else {
            return $common->com_return(false, "缺少参数");
        }
    }

    /**
     * 文章排序
     * @param Request $request
     * @return array
     */
    public function sortEdit(Request $request)
    {
        $article_id    = $request->param("article_id","");
        $sort          = $request->param("sort","100");         //文章排序

        if (empty($article_id)){
            return $this->com_return(false,\config('params.PARAM_NOT_EMPTY'));
        }

        if (empty($sort)){
            $sort = 100;
        }

        $params = [
            'sort'       => $sort,
            'updated_at' => time()
        ];

        $sysPageModel = new PageArticles();

        $is_ok = $sysPageModel
            ->where('article_id',$article_id)
            ->update($params);

        if ($is_ok !== false){

            return $this->com_return(true,\config('params.SUCCESS'));

        }else{

            return $this->com_return(false,\config('params.FAIL'));

        }
    }

    /**
     * 添加文章
     * @param Request $request
     * @return array
     */
    public function add(Request $request)
    {
        $common = new Common();

        $article_title = $request->param("article_title","");   //文章标题
        $article_image = $request->param("article_image","http://ceshiurl.com");   //文章图片
        $link          = $request->param("link","");            //文章链接
        $sort          = $request->param("sort","100");         //文章排序
        $is_show       = $request->param("is_show","");         //是否显示 0:false;1:true
        $is_top        = $request->param("is_top","");           //是否置顶 0:false;1:true

        $url = Env::get("WEB_ARTICLE_URL").'page/iframe.html';
//        $url = 'http://ls.wap.nana.cn/page/iframe.html';

        $rule = [
            "article_title|文章标题"  => "require|max:50|unique:page_article",
            "article_image|文章图片"  => "require",
            "link|文章链接"           => "require",
            "sort|文章排序"           => "require|number",
            "is_show|是否显示"        => "require",
            "is_top|是否置顶"         => "require",
        ];

        $request_res = [
            "article_title" => $article_title,
            "article_image" => $article_image,
            "link"          => $link,
            "sort"          => $sort,
            "is_show"       => $is_show,
            "is_top"       => $is_top,
        ];

        $validate = new Validate($rule);

        if (!$validate->check($request_res)){
            return $common->com_return(false,$validate->getError(),null);
        }

        $time = time();

        $links = $url."?article=".$link;

        $update_data = [
            "article_title" => $article_title,
            "article_image" => $article_image,
            "link"          => $links,
            "sort"          => $sort,
            "is_show"       => $is_show,
            "is_top"        => $is_top,
            "created_at"    => $time,
            "updated_at"    => $time,
        ];

        Db::startTrans();
        try{
            $sysPageModel = new PageArticles();

            $is_ok = $sysPageModel
                ->insert($update_data);
            if ($is_ok){
                Db::commit();
                return $common->com_return(true, "添加成功");
            }else{
                return $common->com_return(false, "添加失败");
            }

        }catch (Exception $e){
            Db::rollback();
            return $common->com_return(false, $e->getMessage());
        }
    }

    /**
     * 文章编辑
     * @param Request $request
     * @return array
     */
    public function edit(Request $request)
    {
        $common = new Common();

        $article_id    = $request->param("article_id","");
        $article_title = $request->param("article_title","");   //文章标题
        $article_image = $request->param("article_image","http://ceshiurl.com");   //文章图片
        $link          = $request->param("link","");            //文章链接
        $sort          = $request->param("sort","100");         //文章排序


        $url = Env::get("WEB_ARTICLE_URL").'page/iframe.html';
//        $url = 'http://ls.wap.nana.cn/page/iframe.html';

        $rule = [
            "article_id|文章id"      => "require",
            "article_title|文章标题"  => "require|max:50|unique:page_article",
            "article_image|文章图片"  => "require",
            "link|文章链接"           => "require",
            "sort|文章排序"           => "require|number",
        ];

        $request_res = [
            "article_id"    => $article_id,
            "article_title" => $article_title,
            "article_image" => $article_image,
            "link"          => $link,
            "sort"          => $sort,
        ];

        $validate = new Validate($rule);

        if (!$validate->check($request_res)){
            return $common->com_return(false,$validate->getError(),null);
        }

        $links = $url."?article=".$link;
//        $links = $link;

        $update_data = [
            "article_id"    => $article_id,
            "article_title" => $article_title,
            "article_image" => $article_image,
            "link"          => $links,
            "sort"          => $sort,
            "updated_at"    => time(),
        ];

        Db::startTrans();
        try{
            $pageArticlesModel = new PageArticles();

            $is_ok = $pageArticlesModel
                ->where("article_id",$article_id)
                ->update($update_data);

            if ($is_ok){
                Db::commit();
                return $common->com_return(true, "编辑成功");
            }else{
                return $common->com_return(false, "编辑失败");
            }
        }catch (Exception $e){
            Db::rollback();
            return $common->com_return(false, $e->getMessage());
        }
    }

    /**
     * 文章删除
     * @param Request $request
     * @return array
     */
    public function delete(Request $request)
    {
        $common = new Common();
        $article_ids= $request->param("article_id","");//文章id

        if (!empty($article_ids)){
            $pageArticlesModel = new PageArticles();

            $id_array = explode(",",$article_ids);
            $is_ok = false;
            Db::startTrans();
            try{
                foreach ($id_array as $article_id){
                    $is_ok = $pageArticlesModel
                        ->where('article_id',$article_id)
                        ->delete();
                }
                if ($is_ok){
                    Db::commit();
                    return $common->com_return(true, config("DELETE_SUCCESS"));
                }else{
                    return $common->com_return(false, config("DELETE_FAIL"));
                }

            }catch (Exception $e){
                Db::rollback();
                return $common->com_return(false, $e->getMessage());
            }
        }else{
           return $common->com_return(false,config("PARAM_NOT_EMPTY"));
        }
    }
}