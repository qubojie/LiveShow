<?php
/**
 * 联盟商家分类
 * User: guojing
 * Date: 2018/9/13
 * Time: 下午1:56
 */

namespace app\admin\controller;


use app\admin\model\MstMerchant;
use app\admin\model\MstMerchantCategory;
use think\Controller;
use think\Db;
use think\Exception;
use think\Request;
use think\Validate;

class MerchantCategory extends CommandAction
{
    /**
     * 获取联盟商家分类无分页
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function merchantType()
    {
        $merchantCategoryModel = new MstMerchantCategory();

        $list = $merchantCategoryModel
            ->where("is_delete","0")
            ->order("sort")
            ->select();

        $list = json_decode(json_encode($list),true);

        return $this->com_return(true,config("params.SUCCESS"),$list);
    }

    /**
     * 联盟商家分类列表
     * @param Request $request
     * @return array
     * @throws \think\exception\DbException
     */
    public function index(Request $request)
    {
        $pagesize   = $request->param("pagesize",config('PAGESIZE'));//显示个数,不传时为10

        if (empty($pagesize)) $pagesize = config('PAGESIZE');

        $nowPage    = $request->param("nowPage","1");

        $config = [
            "page" => $nowPage,
        ];

        $merchantCategoryModel = new MstMerchantCategory();

        $list = $merchantCategoryModel
            ->where("is_delete","0")
            ->order("sort")
            ->paginate($pagesize,false,$config);

        $list = json_decode(json_encode($list),true);

        return $this->com_return(true,config("params.SUCCESS"),$list);
    }

    /**
     * 联盟商家分类添加
     * @param Request $request
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function add(Request $request)
    {
        $merchantCategoryModel = new MstMerchantCategory();

        $cat_name  = $request->param("cat_name","");//分类名称
        $sort      = $request->param("sort","");//排序
        $is_enable = $request->param("is_enable","");//是否启用  0否 1是

        $rule = [
            "cat_name|分类名称"  => "require|max:50|unique_delete:mst_merchant_category",
            "sort|排序"         => "number",
        ];

        $check_res = [
            "cat_name" => $cat_name,
            "sort"     => $sort,
        ];

        $validate = new Validate($rule);

        if (!$validate->check($check_res)){
            return $this->com_return(false,$validate->getError());
        }

        if (empty($cat_img)){
            $cat_img = getSysSetting("sys_logo");
        }

        $nowTime = time();

        $params = [
            "cat_name"   => $cat_name,
            "sort"       => $sort,
            "is_enable"  => $is_enable,
            "created_at" => $nowTime,
            "updated_at" => $nowTime
        ];

        $is_ok = $merchantCategoryModel
            ->insert($params);

        if ($is_ok){
            return $this->com_return(true,config("params.SUCCESS"));
        }else{
            return $this->com_return(false,config("params.FAIL"));
        }

    }

    /**
     * 联盟商家分类编辑
     * @param Request $request
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function edit(Request $request)
    {
        $merchantCategoryModel = new MstMerchantCategory();

        $cat_id    = $request->param("cat_id","");//分类id
        $cat_name  = $request->param("cat_name","");//分类名称
        $sort      = $request->param("sort","");//排序
        $is_enable = $request->param("is_enable","");//是否启用  0否 1是

        $rule = [
            "cat_id|属性id"    => "require",
            "cat_name|分类名称" => "require|max:50|unique_me:mst_merchant_category,cat_id",
            "sort|排序"        => "number",
        ];

        $check_res = [
            "cat_id"   => $cat_id,
            "cat_name" => $cat_name,
            "sort"     => $sort,
        ];

        $validate = new Validate($rule);

        if (!$validate->check($check_res)){
            return $this->com_return(false,$validate->getError());
        }

        if (empty($cat_img)){
            $cat_img = getSysSetting("sys_logo");
        }

        $params = [
            "cat_name"   => $cat_name,
            "sort"       => $sort,
            "is_enable"  => $is_enable,
            "updated_at" => time()
        ];

        $is_ok = $merchantCategoryModel
            ->where("cat_id",$cat_id)
            ->update($params);

        if ($is_ok !== false){
            return $this->com_return(true,config("params.SUCCESS"));
        }else{
            return $this->com_return(false,config("params.FAIL"));
        }

    }

    /**
     * 联盟商家分类删除
     * @param Request $request
     * @return array
     */
    public function delete(Request $request)
    {
        $merchantCategoryModel = new MstMerchantCategory();

        $cat_ids = $request->param("cat_id","");//分类id

        $rule = [
            "cat_id|分类id"      => "require",
        ];

        $check_res = [
            "cat_id" => $cat_ids,
        ];

        $validate = new Validate($rule);

        if (!$validate->check($check_res)){
            return $this->com_return(false,$validate->getError());
        }

        $id_array = explode(",",$cat_ids);

        Db::startTrans();
        try{

            $is_ok = false;
            foreach ($id_array as $cat_id){
                //查看当前分类下是否存在菜品
                $is_exist_merchant = $this->categoryHaveMerchant($cat_id);
                if ($is_exist_merchant){
                    return $this->com_return(false,config("params.MERCHANT")['CLASS_EXIST_MERCHANT']);
                }
                $params = [
                    "is_delete"  => 1,
                    "updated_at" => time()
                ];

                $is_ok = $merchantCategoryModel
                    ->where("cat_id",$cat_id)
                    ->update($params);
            }

            if ($is_ok !== false){
                Db::commit();
                return $this->com_return(true,config("params.SUCCESS"));
            }else{
                return $this->com_return(false,config("params.FAIL"));
            }

        }catch (Exception $e){
            return $this->com_return(false,$e->getMessage());
        }
    }

    /**
     * 联盟商家分类排序
     * @param Request $request
     * @return array
     */
    public function sortEdit(Request $request)
    {
        $merchantCategoryModel = new MstMerchantCategory();

        $cat_id = $request->param("cat_id","");//分类id
        $sort   = $request->param("sort","");//排序

        $rule = [
            "cat_id|分类id"  => "require",
            "sort|排序"      => "require|number",
        ];

        $check_res = [
            "cat_id"  => $cat_id,
            "sort"    => $sort,
        ];

        $validate = new Validate($rule);

        if (!$validate->check($check_res)){
            return $this->com_return(false,$validate->getError());
        }

        $params = [
            "sort"       => $sort,
            "updated_at" => time()
        ];

        $is_ok = $merchantCategoryModel
            ->where("cat_id",$cat_id)
            ->update($params);

        if ($is_ok !== false){
            return $this->com_return(true,config("params.SUCCESS"));
        }else{
            return $this->com_return(false,config("params.FAIL"));
        }

    }

    /**
     * 联盟商家分类是否启用
     * @param Request $request
     * @return array
     */
    public function enable(Request $request)
    {
        $merchantCategoryModel = new MstMerchantCategory();

        $cat_id    = $request->param("cat_id","");//分类id
        $is_enable = $request->param("is_enable","");//是否启用

        $rule = [
            "cat_id|分类id"     => "require",
            "is_enable|是否启用" => "require|number",
        ];

        $check_res = [
            "cat_id"    => $cat_id,
            "is_enable" => $is_enable,
        ];

        $validate = new Validate($rule);

        if (!$validate->check($check_res)){
            return $this->com_return(false,$validate->getError());
        }

        $params = [
            "is_enable"  => $is_enable,
            "updated_at" => time()
        ];

        $is_ok = $merchantCategoryModel
            ->where("cat_id",$cat_id)
            ->update($params);

        if ($is_ok !== false){
            return $this->com_return(true,config("params.SUCCESS"));
        }else{
            return $this->com_return(false,config("params.FAIL"));
        }
    }


    //查看当前分类下是否存在联盟商家
    protected function categoryHaveMerchant($cat_id)
    {
        $merchantModel = new MstMerchant();

        $is_have_merchant = $merchantModel
            ->where("cat_id",$cat_id)
            ->count();

        if ($is_have_merchant > 0){
            return true;
        }else{
            return false;
        }
    }


    /**
     * 分类列表 ———— 键值对
     * @param Request $request
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function cateList(Request $request)
    {
        $merchantCategoryModel = new MstMerchantCategory();

        $res = $merchantCategoryModel
            ->where('is_delete',0)
            ->field("cat_id,cat_name")
            ->select();

        $res = json_decode(json_encode($res),true);

        $list = [];
        foreach ($res as $key => $val){

            foreach ($val as $k => $v){

                if ($k == "cat_id"){
                    $k = "key";
                }else{
                    $k = "name";
                }

                $list[$key][$k] = $v;
            }
        }
        return $this->com_return(true,config("params.SUCCESS"),$list);
    }


}