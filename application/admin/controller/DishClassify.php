<?php
/**
 * 菜品分类
 * User: qubojie
 * Date: 2018/8/14
 * Time: 下午1:56
 */

namespace app\admin\controller;


use app\admin\model\Dishes;
use app\admin\model\DishesCategory;
use think\Controller;
use think\Request;
use think\Validate;

class DishClassify extends Controller
{
    /**
     * 菜品分类列表
     * @param Request $request
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function index(Request $request)
    {
        $dishCateGoryModel = new DishesCategory();

        $list = $dishCateGoryModel
            ->where("is_delete","0")
            ->order("sort")
            ->select();

        $list = json_decode(json_encode($list),true);

        return $this->com_return(true,config("params.SUCCESS"),$list);
    }

    /**
     * 菜品分类添加
     * @param Request $request
     * @return array
     */
    public function add(Request $request)
    {
        $dishCateGoryModel = new DishesCategory();

        $cat_name  = $request->param("cat_name","");//分类名称
        $cat_img   = $request->param("cat_img","");//分类图片
        $sort      = $request->param("sort","");//排序
        $is_enable = $request->param("is_enable","");//是否启用  0否 1是

        $rule = [
            "cat_name|分类名称"  => "require|max:50|unique_delete:dishes_category,cat_name",
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

        $nowTime = time();

        $params = [
            "cat_name"   => $cat_name,
            "cat_img"   => $cat_img,
            "sort"       => $sort,
            "is_enable"  => $is_enable,
            "created_at" => $nowTime,
            "updated_at" => $nowTime
        ];

        $is_ok = $dishCateGoryModel
            ->insert($params);

        if ($is_ok){
            return $this->com_return(true,config("params.SUCCESS"));
        }else{
            return $this->com_return(false,config("params.FAIL"));
        }

    }

    /**
     * 菜品分类编辑
     * @param Request $request
     * @return array
     */
    public function edit(Request $request)
    {
        $dishCateGoryModel = new DishesCategory();

        $cat_id    = $request->param("cat_id","");//属性id
        $cat_name  = $request->param("cat_name","");//分类名称
        $cat_img   = $request->param("cat_img","");//分类图片
        $sort      = $request->param("sort","");//排序
        $is_enable = $request->param("is_enable","");//是否启用  0否 1是

        $rule = [
            "cat_id|属性id"    => "require",
            "cat_name|分类名称" => "require|max:50|unique_me:dishes_category,cat_name",
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

        $params = [
            "cat_name"   => $cat_name,
            "cat_img"    => $cat_img,
            "sort"       => $sort,
            "is_enable"  => $is_enable,
            "updated_at" => time()
        ];

        $is_ok = $dishCateGoryModel
            ->where("cat_id",$cat_id)
            ->update($params);

        if ($is_ok !== false){
            return $this->com_return(true,config("params.SUCCESS"));
        }else{
            return $this->com_return(false,config("params.FAIL"));
        }

    }

    /**
     * 菜品分类删除
     * @param Request $request
     * @return array
     */
    public function delete(Request $request)
    {
        $dishCateGoryModel = new DishesCategory();

        $cat_id = $request->param("cat_id","");//分类id

        $rule = [
            "cat_id|分类id"      => "require",
        ];

        $check_res = [
            "cat_id" => $cat_id,
        ];

        $validate = new Validate($rule);

        if (!$validate->check($check_res)){
            return $this->com_return(false,$validate->getError());
        }

        //查看当前分类下是否存在菜品
        $is_exist_dishes = $this->classifyHaveDish($cat_id);
        if ($is_exist_dishes){
            return $this->com_return(false,config("params.DISHES")['CLASS_EXIST_DISHES']);
        }

        $params = [
            "is_delete"  => 1,
            "updated_at" => time()
        ];

        $is_ok = $dishCateGoryModel
            ->where("cat_id",$cat_id)
            ->update($params);

        if ($is_ok !== false){
            return $this->com_return(true,config("params.SUCCESS"));
        }else{
            return $this->com_return(false,config("params.FAIL"));
        }

    }

    /**
     * 菜品分类排序
     * @param Request $request
     * @return array
     */
    public function sortEdit(Request $request)
    {
        $dishCateGoryModel = new DishesCategory();

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

        $is_ok = $dishCateGoryModel
            ->where("cat_id",$cat_id)
            ->update($params);

        if ($is_ok !== false){
            return $this->com_return(true,config("params.SUCCESS"));
        }else{
            return $this->com_return(false,config("params.FAIL"));
        }

    }

    /**
     * 菜品分类是否启用
     * @param Request $request
     * @return array
     */
    public function enable(Request $request)
    {
        $dishCateGoryModel = new DishesCategory();

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

        $is_ok = $dishCateGoryModel
            ->where("cat_id",$cat_id)
            ->update($params);

        if ($is_ok !== false){
            return $this->com_return(true,config("params.SUCCESS"));
        }else{
            return $this->com_return(false,config("params.FAIL"));
        }
    }


    //查看当前分类下是否存在菜品
    protected function classifyHaveDish($cat_id)
    {
        $dishesModel = new Dishes();

        $is_have_dish = $dishesModel
            ->where("cat_id",$cat_id)
            ->where("is_delete","0")
            ->count();

        if ($is_have_dish > 0){
            return true;
        }else{
            return false;
        }
    }


}