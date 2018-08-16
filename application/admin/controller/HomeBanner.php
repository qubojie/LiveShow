<?php
/**
 * Created by PhpStorm.
 * User: qubojie
 * Date: 2018/8/15
 * Time: 上午10:37
 */
namespace app\admin\controller;

use app\admin\model\PageBanner;
use think\Controller;
use think\Db;
use think\Exception;
use think\Request;
use think\Validate;

class HomeBanner extends Controller
{
    /**
     * Banner列表
     * @return array
     * @throws \think\exception\DbException
     */
    public function index(Request $request)
    {
        $bannerModel = new PageBanner();

        $pagesize   = $request->param("pagesize",config('PAGESIZE'));//显示个数,不传时为10

        if (empty($pagesize)) $pagesize = config('PAGESIZE');

        $nowPage    = $request->param("nowPage","1");

        $config = [
            "page" => $nowPage,
        ];

        $list = $bannerModel
            ->order("sort")
            ->paginate($pagesize,false,$config);

        $list = json_decode(json_encode($list),true);

        return $this->com_return(true,config("params.SUCCESS"),$list);

    }

    /**
     * 添加
     * @param Request $request
     * @return array
     */
    public function add(Request $request)
    {
        $banner_title = $request->param("banner_title","");
        $banner_img   = $request->param("banner_img","");
        $type         = $request->param("type","");
        $link         = $request->param("link","");
        $sort         = $request->param("sort","100");
        $is_show      = $request->param("is_show","0");

        $rule = [
            "banner_title|banner标题"  => "require|max:100|unique:page_banner",
            "banner_img|banner图片"    => "require|max:300",
            "type|类型"                => "require|number",
            "link|链接地址"             => "max:200",
            "sort|排序"                => "number",
            "is_show|是否展示"          => "number",
        ];

        $request_res = [
            "banner_title" => $banner_title,
            "banner_img"   => $banner_img,
            "type"         => $type,
            "link"         => $link,
            "sort"         => $sort,
            "is_show"      => $is_show,
        ];

        $validate = new Validate($rule);

        if (!$validate->check($request_res)){
            return $this->com_return(false,$validate->getError(),null);
        }


        $bannerModel = new PageBanner();

        $nowTime = time();
        $params = [
            "banner_title" => $banner_title,
            "banner_img"   => $banner_img,
            "type"         => $type,
            "link"         => $link,
            "sort"         => $sort,
            "is_show"      => $is_show,
            "created_at"   => $nowTime,
            "updated_at"   => $nowTime,
        ];

        $is_ok = $bannerModel
            ->insert($params);

        if ($is_ok){
            return $this->com_return(true, config("params.SUCCESS"));
        }else{
            return $this->com_return(false, config("params.FAIL"));
        }
    }

    /**
     * 编辑
     * @param Request $request
     * @return array
     */
    public function edit(Request $request)
    {
        $banner_id    = $request->param("banner_id","");
        $banner_title = $request->param("banner_title","");
        $banner_img   = $request->param("banner_img","");
        $type         = $request->param("type","");
        $link         = $request->param("link","");
        $sort         = $request->param("sort","100");
        $is_show      = $request->param("is_show","0");

        $rule = [
            "banner_id|id"             => "require",
            "banner_title|banner标题"  => "require|max:100|unique:page_banner",
            "banner_img|banner图片"    => "require|max:300",
            "type|类型"                => "require|number",
            "link|链接地址"             => "max:200",
            "sort|排序"                => "number",
            "is_show|是否展示"          => "number",
        ];

        $request_res = [
            "banner_id"    => $banner_id,
            "banner_title" => $banner_title,
            "banner_img"   => $banner_img,
            "type"         => $type,
            "link"         => $link,
            "sort"         => $sort,
            "is_show"      => $is_show,
        ];

        $validate = new Validate($rule);

        if (!$validate->check($request_res)){
            return $this->com_return(false,$validate->getError(),null);
        }


        $params = [
            "banner_title" => $banner_title,
            "banner_img"   => $banner_img,
            "type"         => $type,
            "link"         => $link,
            "sort"         => $sort,
            "is_show"      => $is_show,
            "updated_at"   => time(),
        ];

        $bannerModel = new PageBanner();

        $is_ok = $bannerModel
            ->where("banner_id",$banner_id)
            ->update($params);

        if ($is_ok !== false){
            return $this->com_return(true, config("params.SUCCESS"));
        }else{
            return $this->com_return(false, config("params.FAIL"));
        }
    }

    /**
     * 删除
     * @param Request $request
     * @return array
     */
    public function delete(Request $request)
    {
        $banner_ids  = $request->param("banner_id","");

        if (empty($banner_ids)){
            return $this->com_return(false,config("params.ABNORMAL_ACTION"));
        }

        $bannerModel = new PageBanner();

        $id_array = explode(",",$banner_ids);

        Db::startTrans();
        try{
            $is_ok =false;
            foreach ($id_array as $banner_id){
                $is_ok = $bannerModel
                    ->where("banner_id",$banner_id)
                    ->delete();
            }
            if ($is_ok){
                Db::commit();
                return $this->com_return(true, config("params.SUCCESS"));
            }else{
                return $this->com_return(false, config("params.FAIL"));
            }

        }catch (Exception $e){
            Db::rollback();
            return $this->com_return(false,$e->getMessage(),null);
        }
    }

    /**
     * 排序编辑
     * @param Request $request
     * @return array
     */
    public function sortEdit(Request $request)
    {

        $banner_id = $request->param("banner_id","");//菜品id
        $sort      = $request->param("sort","");//排序

        $rule = [
            "banner_id|id"  => "require",
            "sort|排序"      => "require|number",
        ];

        $check_res = [
            "banner_id" => $banner_id,
            "sort"      => $sort,
        ];

        $validate = new Validate($rule);

        if (!$validate->check($check_res)){
            return $this->com_return(false,$validate->getError());
        }

        $params = [
            "sort"       => $sort,
            "updated_at" => time()
        ];

        $bannerModel = new PageBanner();

        $is_ok = $bannerModel
            ->where("banner_id",$banner_id)
            ->update($params);

        if ($is_ok !== false){
            return $this->com_return(true,config("params.SUCCESS"));
        }else{
            return $this->com_return(false,config("params.FAIL"));
        }

    }

    /**
     * 是否显示
     * @param Request $request
     * @return array
     */
    public function isShow(Request $request)
    {
        $banner_id  = $request->param("banner_id","");
        $is_show    = (int)$request->param("is_show","");

        $rule = [
            "banner_id|id"    => "require",
            "is_show|是否显示" => "require|number",
        ];

        $check_res = [
            "banner_id" => $banner_id,
            "is_show"   => $is_show,
        ];

        $validate = new Validate($rule);

        if (!$validate->check($check_res)){
            return $this->com_return(false,$validate->getError());
        }

        $params = [
            "is_show"    => $is_show,
            "updated_at" => time()
        ];

        $bannerModel = new PageBanner();

        $is_ok = $bannerModel
            ->where("banner_id",$banner_id)
            ->update($params);

        if ($is_ok !== false){
            return $this->com_return(true,config("params.SUCCESS"));
        }else{
            return $this->com_return(false,config("params.FAIL"));
        }
    }


}