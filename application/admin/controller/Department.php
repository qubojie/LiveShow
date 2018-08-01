<?php
/**
 * 会籍部门设置.
 * User: qubojie
 * Date: 2018/6/23
 * Time: 下午2:41
 */
namespace app\admin\controller;

use app\admin\model\ManageDepartment;
use think\Db;
use think\Exception;
use think\Request;
use think\Validate;

class Department extends CommandAction
{
    /**
     * 列表
     * @param Request $request
     * @return array
     */
    public function index(Request $request)
    {
        $common = new Common();

        $manageDepartmentModel = new ManageDepartment();

        $where = [];
        if (!empty($keyword)){
            $where['department_title|department_manager|phone'] = ["like","%$keyword%"];
        }

        $list = $manageDepartmentModel
            ->where($where)
            ->select();

        $list = json_decode(json_encode($list),true);

        //将数据转换成树状结构
        $res = $common->make_tree($list,'department_id','parent_id');

        $department_list['data'] = $res;


        return $common->com_return(true,config("GET_SUCCESS"),$department_list);
    }

    /**
     * 添加
     * @param Request $request
     * @return array
     */
    public function add(Request $request)
    {
        $common = new Common();

        $manageDepartmentModel = new ManageDepartment();

        $parent_id          = $request->param("parent_id",0);//父id
        $department_title   = $request->param("department_title","");//部门名称
        $department_manager = $request->param("department_manager","");//部门负责人
        $phone              = $request->param("phone","");//联系电话

        $rule = [
            "department_title|部门名称"     => "require|max:50|unique:manage_department",
            "phone|联系电话"                => "number|regex:1[3-8]{1}[0-9]{9}",
        ];

        $request_res = [
            "department_title"   => $department_title,
            "phone"              => $phone,
        ];

        $validate = new Validate($rule);

        if (!$validate->check($request_res)){
            return $common->com_return(false,$validate->getError(),null);
        }

        $time = time();

        $insert_data = [
            "parent_id"          => $parent_id,
            "department_title"   => $department_title,
            "department_manager" => $department_manager,
            "phone"              => $phone,
            "created_at"         => $time,
            "updated_at"         => $time
        ];
        Db::startTrans();
        try{
            $is_ok = $manageDepartmentModel
                ->insert($insert_data);
            if ($is_ok){
                Db::commit();
                return $common->com_return(true, config("ADD_SUCCESS"));
            }else{
                return $common->com_return(false, config("ADD_FAIL"));
            }
        }catch (Exception $e){
            Db::rollback();
            return $common->com_return(false,$e->getMessage(),null);
        }
    }

    /**
     * 编辑
     * @param Request $request
     * @return array
     */
    public function edit(Request $request)
    {
        $common = new Common();

        $manageDepartmentModel = new ManageDepartment();

        $department_id      = $request->param("department_id",""); //部门id
        $parent_id          = $request->param("parent_id",0);//父类id
        $department_title   = $request->param("department_title","");//部门名称
        $department_manager = $request->param("department_manager","");//部门负责人
        $phone              = $request->param("phone","");//联系电话

        $rule = [
            "department_id|部门id"         => "require",
            "parent_id|父类id"             => "require",
            "department_title|部门名称"     => "require|max:50|unique:manage_department",
            "phone|联系电话"                => "number|regex:1[3-8]{1}[0-9]{9}",
        ];

        $request_res = [
            "department_id"      => $department_id,
            "parent_id"          => $parent_id,
            "department_title"   => $department_title,
            "phone"              => $phone,
        ];

        $validate = new Validate($rule);

        if (!$validate->check($request_res)){
            return $common->com_return(false,$validate->getError(),null);
        }

        $time = time();

        $update_data = [
            "parent_id"          => $parent_id,
            "department_title"   => $department_title,
            "department_manager" => $department_manager,
            "phone"              => $phone,
            "updated_at"         => $time
        ];

        Db::startTrans();
        try {

            $is_ok = $manageDepartmentModel
                ->where('department_id', $department_id)
                ->update($update_data);

            if ($is_ok !== false) {
                Db::commit();
                return $common->com_return(true, config("EDIT_SUCCESS"));
            } else {
                return $common->com_return(false, config("EDIT_FAIL"));
            }
        } catch (Exception $e) {
            Db::rollback();
            return $common->com_return(false, $e->getMessage(), null);
        }
    }

    /**
     * 删除
     * @param Request $request
     * @return array
     */
    public function delete(Request $request)
    {
        $common = new Common();

        $manageDepartmentModel = new ManageDepartment();

        $department_id      = $request->param("department_id",""); //部门id

        if (empty($department_id)){

            return $common->com_return(false, config("PARAM_NOT_EMPTY"));

        }

        //查询表中是否存在以此部门为父类的子类
        $is_exist = $manageDepartmentModel
            ->where("parent_id",$department_id)
            ->count();

        if (!$is_exist){
            Db::startTrans();
            try{

                $is_ok = $manageDepartmentModel
                    ->where("department_id",$department_id)
                    ->delete();

                if ($is_ok !== false) {

                    Db::commit();
                    return $common->com_return(true, config("DELETE_SUCCESS"));

                } else {

                    return $common->com_return(false, config("DELETE_FAIL"));

                }
            }catch (Exception $e){

                Db::rollback();
                return $common->com_return(false, $e->getMessage(), null);

            }

        }else{

            return $common->com_return(false, config("EXIST_SUBCLASS"));

        }
    }

}