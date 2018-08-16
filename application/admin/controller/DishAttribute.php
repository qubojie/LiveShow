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
use think\Db;
use think\Exception;
use think\Request;
use think\Validate;

class DishAttribute extends Controller
{
    /**菜品属性列表 无分页
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function dishAttr()
    {
        $dishAttributeModel = new DishesAttribute();

        $list = $dishAttributeModel
            ->where("is_delete","0")
            ->order("sort")
            ->select();

        $list = json_decode(json_encode($list),true);

        for ($i = 0; $i < count($list); $i ++){
            $att_id = $list[$i]["att_id"];

            $printer_info = Db::name("dishes_attribute_printer")
                ->where("att_id",$att_id)
                ->select();

            $printer_info = json_decode(json_encode($printer_info),true);

            $list[$i]["printer_info"] = $printer_info;
        }

        return $this->com_return(true,config("params.SUCCESS"),$list);
    }

    /**
     * 菜品属性列表
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

        $dishAttributeModel = new DishesAttribute();

        $list = $dishAttributeModel
            ->where("is_delete","0")
            ->order("sort")
            ->paginate($pagesize,false,$config);

        $list = json_decode(json_encode($list),true);

        for ($i = 0; $i < count($list["data"]); $i ++){
            $att_id = $list["data"][$i]["att_id"];

            $printer_info = Db::name("dishes_attribute_printer")
                ->where("att_id",$att_id)
                ->select();

            $printer_info = json_decode(json_encode($printer_info),true);

            $list["data"][$i]["printer_info"] = $printer_info;
        }

        return $this->com_return(true,config("params.SUCCESS"),$list);

    }

    /**
     * 菜品属性添加
     * @param Request $request
     * @return array
     */
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

        $att_ids    = $request->param("att_id","");//属性id

        $rule = [
            "att_id|属性id"      => "require",
        ];

        $check_res = [
            "att_id"   => $att_ids,
        ];

        $validate = new Validate($rule);

        if (!$validate->check($check_res)){
            return $this->com_return(false,$validate->getError());
        }

        $params = [
            "is_delete"  => 1,
            "updated_at" => time()
        ];

        $id_array = explode(",",$att_ids);

        Db::startTrans();
        try{
            $is_ok = false;
            foreach ($id_array as $att_id){
                $is_ok = $dishAttributeModel
                    ->where("att_id",$att_id)
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


    /**
     * 属性绑定打印机添加
     * @param Request $request
     * @return array
     * @throws Exception
     * @throws \think\exception\PDOException
     */
    public function attrBindPrinterAdd(Request $request)
    {
        $att_id         = $request->param("att_id","");//属性id

        $printer_info = $request->param("printer_info","");//打印机参数

        \think\Log::info("打印机参数 --- ".$printer_info);

        $rule = [
            "att_id|属性id"            => "require",
            "printer_info|打印机参数" => "require",
        ];

        $check_res = [
            "att_id"     => $att_id,
            "printer_info" => $printer_info,
        ];

        $validate = new Validate($rule);

        if (!$validate->check($check_res)){
            return $this->com_return(false,$validate->getError());
        }

        if (empty($printer_info)){
            return $this->com_return(false,config("params.ABNORMAL_ACTION"));
        }

        $printer_info = json_decode($printer_info,true);


        \think\Log::info("打印机参数数组结构 --- ".var_export($printer_info,true));

        //删除当前属性下的打印机参数

        Db::startTrans();

        try{

            $is_delete = Db::name("dishes_attribute_printer")
                ->where("att_id",$att_id)
                ->delete();

            \think\Log::info("是否删除成功".$is_delete);

            $is_ok = false;
            for ($i = 0; $i < count($printer_info); $i ++){
                $printer_sn = $printer_info[$i]['printer_sn'];
                $print_num  = $printer_info[$i]['print_num'];

                $params = [
                    "att_id"     => $att_id,
                    "printer_sn" => $printer_sn,
                    "print_num"  => $print_num
                ];

                $is_ok = Db::name("dishes_attribute_printer")
                    ->insert($params);
                \think\Log::info("写入打印记属性".$is_ok);
            }

            if ($is_ok){
                \think\Log::info("是否操作成功----".$is_ok);
                Db::commit();
                return $this->com_return(true,config("params.SUCCESS"));
            }else{
                return $this->com_return(false,config("params.FAIL"));
            }


        }catch (Exception $e){
            Db::rollback();
            $this->com_return(false,$e->getMessage());
        }
    }

    /**
     * 属性绑定打印机删除
     * @param Request $request
     * @return array
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public function attrBindPrinterDelete(Request $request)
    {
        $att_id     = $request->param("att_id","");//属性id

        $rule = [
            "att_id|属性id"        => "require",
        ];

        $check_res = [
            "att_id"     => $att_id,
        ];

        $validate = new Validate($rule);

        if (!$validate->check($check_res)){
            return $this->com_return(false,$validate->getError());
        }

        $is_ok = Db::name("dishes_attribute_printer")
            ->where("att_id",$att_id)
            ->delete();

        if ($is_ok){
            return $this->com_return(true,config("params.SUCCESS"));
        }else{
            return $this->com_return(false,config("params.FAIL"));
        }
    }
}