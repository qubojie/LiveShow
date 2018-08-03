<?php
/**
 * 酒桌位置信息.
 * User: qubojie
 * Date: 2018/7/26
 * Time: 下午6:39
 */
namespace app\admin\controller;

use app\admin\model\MstTableLocation;
use think\Request;
use think\Validate;

class TablePosition extends CommandAction
{
    /**
     * 位置列表
     * @param Request $request
     * @return array
     * @throws \think\exception\DbException
     */
    public function index(Request $request)
    {
        $tableLocationModel = new MstTableLocation();

        $pagesize = $request->param("pagesize",config('PAGESIZE'));//显示个数,不传时为10

        if (empty($pagesize)) $pagesize = config('PAGESIZE');

        $nowPage = $request->param("nowPage","1");

        $config = [
            "page" => $nowPage,
        ];

        $list = $tableLocationModel
            ->where('is_delete',0)
            ->order("sort")
            ->paginate($pagesize,false,$config);

        return $this->com_return(true,config("params.SUCCESS"),$list);

    }


    /**
     * 位置添加
     * @param Request $request
     * @return array
     */
    public function add(Request $request)
    {
        $tableLocationModel = new MstTableLocation();

        $location_title = $request->param("location_title","");
        $location_desc  = $request->param("location_desc","");
        $sort           = $request->param("sort","100");

        $rule = [
            "location_title|位置名称"  => "require|max:30|unique:mst_table_location",
            "location_desc|位置描述"   => "max:200",
            "sort|排序"               => "number",
        ];
        $check_data = [
            "location_title" => $location_title,
            "location_desc"  => $location_desc,
            "sort"           => $sort,
        ];

        $validate = new Validate($rule);

        if (!$validate->check($check_data)){
            return $this->com_return(false,$validate->getError());
        }

        $time = time();

        $insert_data = [
            "location_title" => $location_title,
            "location_desc"  => $location_desc,
            "sort"           => $sort,
            "created_at"     => $time,
            "updated_at"     => $time
        ];

        $is_ok = $tableLocationModel
            ->insert($insert_data);

        if ($is_ok){
            return $this->com_return(true,config("SUCCESS"));
        }else{
            return $this->com_return(false,config("FAIL"));
        }
    }

    /**
     * 位置编辑
     * @param Request $request
     * @return array
     */
    public function edit(Request $request)
    {
        $tableLocationModel = new MstTableLocation();

        $location_id    = $request->param("location_id","");
        $location_title = $request->param("location_title","");
        $location_desc  = $request->param("location_desc","");
        $sort           = $request->param("sort","100");

        $rule = [
            "location_id|位置id"      => "require",
            "location_title|位置名称"  => "require|max:30|unique:mst_table_location",
            "location_desc|位置描述"   => "max:200",
            "sort|排序"               => "number",
        ];
        $check_data = [
            "location_id"    => $location_id,
            "location_title" => $location_title,
            "location_desc"  => $location_desc,
            "sort"           => $sort,
        ];

        $validate = new Validate($rule);

        if (!$validate->check($check_data)){
            return $this->com_return(false,$validate->getError());
        }

        $time = time();

        $update_data = [
            "location_title" => $location_title,
            "location_desc"  => $location_desc,
            "sort"           => $sort,
            "updated_at"     => $time
        ];

        $is_ok = $tableLocationModel
            ->where("location_id",$location_id)
            ->update($update_data);

        if ($is_ok !== false){

            return $this->com_return(true,config("SUCCESS"));

        }else{
            return $this->com_return(false,config("FAIL"));
        }
    }

    /**
     * 位置删除
     * @param Request $request
     * @return array
     */
    public function delete(Request $request)
    {
        $tableLocationModel = new MstTableLocation();

        $location_id    = $request->param("location_id","");

        if(empty($location_id)) return $this->com_return(false,config("params.PARAM_NOT_EMPTY"));

        $delete_date = [
            "is_delete" => 1,
            "updated_at" => time()
        ];

        $is_ok = $tableLocationModel
            ->where('location_id',$location_id)
            ->update($delete_date);

        if ($is_ok !== false){

            return $this->com_return(true,config("params.SUCCESS"));

        }else{

            return $this->com_return(false,config("params.FAIL"));
        }
    }

    /**
     * 排序编辑
     * @param Request $request
     * @return array
     */
    public function sortEdit(Request $request)
    {
        $tableLocationModel = new MstTableLocation();

        $location_id  = $request->param("location_id","");
        $sort         = $request->param("sort","");

        $rule = [
            "location_id|位置id"      => "require",
            "sort|排序"               => "require|number",
        ];
        $check_data = [
            "location_id"    => $location_id,
            "sort"           => $sort,
        ];

        $validate = new Validate($rule);

        if (!$validate->check($check_data)){
            return $this->com_return(false,$validate->getError());
        }

        $update_data = [
            "sort" => $sort,
            "updated_at" => time()
        ];

        $is_ok = $tableLocationModel
            ->where('location_id',$location_id)
            ->update($update_data);

        if ($is_ok !== false){

            return $this->com_return(true,config("params.SUCCESS"));

        }else{

            return $this->com_return(false,config("params.FAIL"));
        }

    }
}