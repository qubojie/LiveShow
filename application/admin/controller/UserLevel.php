<?php
/**
 * 用户等级设置.
 * User: qubojie
 * Date: 2018/6/22
 * Time: 上午11:18
 */
namespace app\admin\controller;

use app\admin\model\MstUserLevel;
use app\admin\model\User;
use think\Db;
use think\Exception;
use think\Request;
use think\Validate;

class UserLevel extends CommandAction
{
    /*
     * 会员等级列表
     * */
    public function index(Request $request)
    {
        $common = new Common();

        $mstUserLevelModel = new MstUserLevel();

        $query = $mstUserLevelModel;

        $count = $query->count();//总记录数

        $pagesize = $request->param("pagesize",config('PAGESIZE'));//当前页,不传时为10

        $nowPage = $request->param("nowPage","1");

        if (empty($pagesize)){
            $pagesize = config('PAGESIZE');
        }

        $pageNum = ceil($count/$pagesize); //总页数


        $config = [
            "page" => $nowPage,

        ];
        $level_list = $query
        ->paginate($pagesize,false,$config);

        return $common->com_return(true,config("GET_SUCCESS"),$level_list);
    }

    /*
     * 会员等级添加
     * */
    public function add(Request $request)
    {
        $common = new Common();

        $level_name = $request->param("level_name",""); //等级名称
        $level_desc = $request->param("level_desc",""); //等级描述
        $level_img  = $request->param("level_img","");  //等级图片
        $sort       = $request->param("sort","100");    //等级排序
        $point_min  = $request->param("point_min","");  //等级积分最小值
        $point_max  = $request->param("point_max","");  //等级积分最大值

        $rule = [
            "level_name|等级名称"     => "require|max:20|unique:mst_user_level",
            "level_img|等级图片"      => "require",
            "point_min|等级积分最小值" => "require|number",
            "point_max|等级积分最大值" => "require|number",
        ];

        $request_res = [
            "level_name" => $level_name,
            "level_img"  => $level_img,
            "point_min"  => $point_min,
            "point_max"  => $point_max
        ];

        $validate = new Validate($rule);

        if (!$validate->check($request_res)){
            return $this->com_return(false,$validate->getError(),null);
        }

        if ($point_max < $point_min){
            return $this->com_return(false,config("params.POINT_POST_RETURN"));
        }

        $mstUserLevelModel = new MstUserLevel();

        $time = time();
        $insert_data = [
            "level_name" => $level_name,
            "level_desc" => $level_desc,
            "level_img"  => $level_img,
            "point_min"  => $point_min,
            "point_max"  => $point_max,
            "sort"       => $sort,
            "created_at" => $time,
            "updated_at" => $time
        ];

        Db::startTrans();
        try{
            $is_ok = $mstUserLevelModel
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
     * 会员等级编辑
     * @param Request $request
     * @return array
     */
    public function edit(Request $request)
    {
        $common = new Common();
        $level_id   = $request->param("level_id",""); //等级id
        $level_name = $request->param("level_name",""); //等级名称
        $level_desc = $request->param("level_desc",""); //等级描述
        $level_img  = $request->param("level_img","");  //等级图片
        $sort       = $request->param("sort","100");    //等级排序
        $point_min  = $request->param("point_min","");  //等级积分最小值
        $point_max  = $request->param("point_max","");  //等级积分最大值

        $rule = [
            "level_id|等级id"         => "require",
            "level_name|等级名称"     => "require|max:20|unique:mst_user_level",
            "level_img|等级图片"      => "require",
            "point_min|等级积分最小值" => "require|number",
            "point_max|等级积分最大值" => "require|number",
        ];

        $request_res = [
            "level_id"   => $level_id,
            "level_name" => $level_name,
            "level_img"  => $level_img,
            "point_min"  => $point_min,
            "point_max"  => $point_max,
        ];

        $validate = new Validate($rule);

        if (!$validate->check($request_res)){
            return $common->com_return(false,$validate->getError(),null);
        }

        if ($point_max < $point_min){
            return $this->com_return(false,config("params.POINT_POST_RETURN"));
        }

        $mstUserLevelModel = new MstUserLevel();

        $time = time();
        $update_data = [
            "level_name" => $level_name,
            "level_desc" => $level_desc,
            "level_img"  => $level_img,
            "point_min"  => $point_min,
            "point_max"  => $point_max,
            "sort"       => $sort,
            "updated_at" => $time
        ];

        try{

            $is_ok = $mstUserLevelModel
                ->where("level_id",$level_id)
                ->update($update_data);

            if ($is_ok){
                Db::commit();
                return $common->com_return(true, config("EDIT_SUCCESS"));
            }else{
                return $common->com_return(false, config("EDIT_FAIL"));
            }
        }catch (Exception $e){
            Db::rollback();
            return $common->com_return(false,$e->getMessage(),null);
        }
    }

    /*
     * 会员等级删除
     * */
    public function delete(Request $request)
    {
        $common = new Common();
        $level_ids = $request->param("level_id","");

        $id_array = explode(",",$level_ids);

        $userModel = new User();

        $is_ok = false;
        foreach ($id_array as $id_l){
            $is_exist = $userModel->where("level_id",$id_l)->count();
            if ($is_exist){
                return $common->com_return(false,config("LEVEL_USER_EXIST"));
            }
        }

        $mstUserLevelModel = new MstUserLevel();

        Db::startTrans();
        try{
            foreach ($id_array as $level_id){
                $is_ok = $mstUserLevelModel
                    ->where("level_id",$level_id)
                    ->delete();
            }
            if ($is_ok !== false){
                Db::commit();
                return $common->com_return(true, config("DELETE_SUCCESS"));
            }else{
                return $common->com_return(false, config("DELETE_FAIL"));
            }

        }catch (Exception $e){
            Db::rollback();
            return $common->com_return(false,$e->getMessage());
        }
    }
}