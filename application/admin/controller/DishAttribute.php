<?php
/**
 * 菜品属性类
 * User: qubojie
 * Date: 2018/8/14
 * Time: 下午1:56
 */

namespace app\admin\controller;


use app\admin\model\Dishes;
use app\admin\model\DishesAttribute;
use think\Controller;
use think\Request;
use think\Validate;

class DishAttribute extends Controller
{

    /**
     * 菜品属性列表
     * @param Request $request
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function index(Request $request)
    {
        $dishAttributeModel = new DishesAttribute();

        $list = $dishAttributeModel
            ->where("is_delete","0")
            ->order("sort")
            ->select();

        $list = json_decode(json_encode($list),true);


        return $this->com_return(true,config("params.SUCCESS"),$list);


    }

    //菜品属性添加
    public function add(Request $request)
    {
        $dishAttributeModel = new DishesAttribute();

        $att_name  = $request->param("att_name","");//属性名称
        $sort      = $request->param("sort","");//排序
        $is_enable = $request->param("is_enable","");//是否启用  0否 1是

        $rule = [
            "att_name|属性名称"  => "require|max:50|unique_delete:dishes_attribute",
            "sort|排序"          => "number",
        ];

        $check_res = [
            "att_name"  => $att_name,
            "sort"      => $sort,
        ];

        $validate = new Validate($rule);

        if (!$validate->check($check_res)){
            return $this->com_return(false,$validate->getError());
        }

        $nowTime = time();

        $params = [
            "att_name"   => $att_name,
            "sort"       => $sort,
            "is_enable"  => $is_enable,
            "is_sys"     => 1,
            "created_at" => $nowTime,
            "updated_at" => $nowTime
        ];

        $is_ok = $dishAttributeModel
            ->insert($params);

        if ($is_ok){
            return $this->com_return(true,config("params.SUCCESS"));
        }else{
            return $this->com_return(false,config("params.FAIL"));
        }
    }

    /**
     * 菜品属性编辑
     * @param Request $request
     * @return array
     */
    public function edit(Request $request)
    {
        $dishAttributeModel = new DishesAttribute();

        $att_id    = $request->param("att_id","");//属性id
        $att_name  = $request->param("att_name","");//属性名称
        $sort      = $request->param("sort","");//排序
        $is_enable = $request->param("is_enable","");//是否启用  0否 1是

        $rule = [
            "att_id|属性id"      => "require",
            "att_name|属性名称"  => "require|max:50|unique_delete:dishes_attribute,att_name",
            "sort|排序"          => "number",
        ];

        $check_res = [
            "att_id"   => $att_id,
            "att_name" => $att_name,
            "sort"     => $sort,
        ];

        $validate = new Validate($rule);

        if (!$validate->check($check_res)){
            return $this->com_return(false,$validate->getError());
        }

        //查看当前属性下是否存在菜品
        $is_exist_dishes = $this->attributeHaveDish($att_id);
        if ($is_exist_dishes){
            return $this->com_return(false,config("params.DISHES")['ATTR_EXIST_DISHES']);
        }

        $params = [
            "att_name"   => $att_name,
            "sort"       => $sort,
            "is_enable"  => $is_enable,
            "updated_at" => time()
        ];

        $is_ok = $dishAttributeModel
            ->where("att_id",$att_id)
            ->update($params);

        if ($is_ok !== false){
            return $this->com_return(true,config("params.SUCCESS"));
        }else{
            return $this->com_return(false,config("params.FAIL"));
        }
    }

    /**
     * 菜品属性删除
     * @param Request $request
     * @return array
     */
    public function delete(Request $request)
    {
        $dishAttributeModel = new DishesAttribute();

        $att_id    = $request->param("att_id","");//属性id

        $rule = [
            "att_id|属性id"      => "require",
        ];

        $check_res = [
            "att_id"   => $att_id,
        ];

        $validate = new Validate($rule);

        if (!$validate->check($check_res)){
            return $this->com_return(false,$validate->getError());
        }

        $params = [
            "is_delete"  => 1,
            "updated_at" => time()
        ];

        $is_ok = $dishAttributeModel
            ->where("att_id",$att_id)
            ->update($params);

        if ($is_ok !== false){
            return $this->com_return(true,config("params.SUCCESS"));
        }else{
            return $this->com_return(false,config("params.FAIL"));
        }


    }

    /**
     * 菜品属性排序
     * @param Request $request
     * @return array
     */
    public function sortEdit(Request $request)
    {
        $dishAttributeModel = new DishesAttribute();

        $att_id = $request->param("att_id","");//属性id
        $sort   = $request->param("sort","");//排序

        $rule = [
            "att_id|属性id"  => "require",
            "sort|排序"      => "require|number",
        ];

        $check_res = [
            "att_id" => $att_id,
            "sort"   => $sort,
        ];

        $validate = new Validate($rule);

        if (!$validate->check($check_res)){
            return $this->com_return(false,$validate->getError());
        }

        $params = [
            "sort"       => $sort,
            "updated_at" => time()
        ];

        $is_ok = $dishAttributeModel
            ->where("att_id",$att_id)
            ->update($params);

        if ($is_ok !== false){
            return $this->com_return(true,config("params.SUCCESS"));
        }else{
            return $this->com_return(false,config("params.FAIL"));
        }
    }

    /**
     * 菜品属性是否启用
     * @param Request $request
     * @return array
     */
    public function enable(Request $request)
    {
        $dishAttributeModel = new DishesAttribute();

        $att_id    = $request->param("att_id","");//属性id
        $is_enable = $request->param("is_enable","");//是否启用

        $rule = [
            "att_id|属性id"  => "require",
            "is_enable|排序" => "require|number",
        ];

        $check_res = [
            "att_id"    => $att_id,
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

        $is_ok = $dishAttributeModel
            ->where("att_id",$att_id)
            ->update($params);

        if ($is_ok !== false){
            return $this->com_return(true,config("params.SUCCESS"));
        }else{
            return $this->com_return(false,config("params.FAIL"));
        }
    }

    //查看当前属性下是否存在菜品
    protected function attributeHaveDish($att_id)
    {
        $dishesModel = new Dishes();

        $is_have_dish = $dishesModel
            ->where("att_id",$att_id)
            ->where("is_delete","0")
            ->count();

        if ($is_have_dish > 0){
            return true;
        }else{
            return false;
        }
    }


}