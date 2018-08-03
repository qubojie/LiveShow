<?php
/**
 * 酒桌容量信息.
 * User: qubojie
 * Date: 2018/7/26
 * Time: 下午6:39
 */
namespace app\admin\controller;

use app\admin\model\MstTableSize;
use think\Request;
use think\Validate;

class TableSize extends CommandAction
{
    /**
     * 容量列表
     * @param Request $request
     * @return array
     * @throws \think\exception\DbException
     */
    public function index(Request $request)
    {
        $tableSizeModel = new MstTableSize();

        $pagesize = $request->param("pagesize",config('PAGESIZE'));//显示个数,不传时为10

        if (empty($pagesize)) $pagesize = config('PAGESIZE');

        $nowPage = $request->param("nowPage","1");

        $config = [
            "page" => $nowPage,
        ];

        $list = $tableSizeModel
            ->where('is_delete',0)
            ->order("sort")
            ->paginate($pagesize,false,$config);

        return $this->com_return(true,config("params.SUCCESS"),$list);
    }


    /**
     * 容量添加
     * @param Request $request
     * @return array
     */
    public function add(Request $request)
    {
        $tableSizeModel = new MstTableSize();

        $size_title = $request->param("size_title","");//容量标题
        $size_desc  = $request->param("size_desc","");//容量描述
        $sort       = $request->param("sort",100);

        $rule = [
            "size_title|容量标题"  => "require|max:30|unique:mst_table_size",
            "size_desc|容量描述"   => "max:200",
            "sort|排序"           => "number",
        ];
        $check_data = [
            "size_title" => $size_title,
            "size_desc"  => $size_desc,
            "sort"       => $sort,
        ];

        $validate = new Validate($rule);

        if (!$validate->check($check_data)){
            return $this->com_return(false,$validate->getError());
        }

        $time = time();

        $insert_data = [
            "size_title" => $size_title,
            "size_desc"  => $size_desc,
            "sort"       => $sort,
            "created_at" => $time,
            "updated_at" => $time
        ];

        $is_ok = $tableSizeModel
            ->insert($insert_data);

        if ($is_ok){
            return $this->com_return(true,config("SUCCESS"));
        }else{
            return $this->com_return(false,config("FAIL"));
        }
    }

    /**
     * 容量编辑
     * @param Request $request
     * @return array
     */
    public function edit(Request $request)
    {
        $tableSizeModel = new MstTableSize();

        $size_id    = $request->param("size_id","");//容量id
        $size_title = $request->param("size_title","");//容量标题
        $size_desc  = $request->param("size_desc","");//容量描述
        $sort       = $request->param("sort",100);

        $rule = [
            "size_id|容量id"  => "require",
            "size_title|容量标题"  => "require|max:30|unique:mst_table_size",
            "size_desc|容量描述"   => "max:200",
            "sort|排序"           => "number",
        ];
        $check_data = [
            "size_id"    => $size_id,
            "size_title" => $size_title,
            "size_desc"  => $size_desc,
            "sort"       => $sort,
        ];

        $validate = new Validate($rule);

        if (!$validate->check($check_data)){
            return $this->com_return(false,$validate->getError());
        }

        $time = time();

        $update_data = [
            "size_title" => $size_title,
            "size_desc"  => $size_desc,
            "sort"       => $sort,
            "updated_at" => $time
        ];

        $is_ok = $tableSizeModel
            ->where('size_id',$size_id)
            ->update($update_data);

        if ($is_ok !== false){
            return $this->com_return(true,config("SUCCESS"));
        }else{
            return $this->com_return(false,config("FAIL"));
        }
    }

    /**
     * 容量删除
     * @param Request $request
     * @return array
     */
    public function delete(Request $request)
    {
        $tableSizeModel = new MstTableSize();

        $size_id    = $request->param("size_id","");//容量id

        if(empty($size_id)) return $this->com_return(false,config("params.PARAM_NOT_EMPTY"));

        $delete_date = [
            "is_delete" => 1,
            "updated_at" => time()
        ];

        $is_ok = $tableSizeModel
            ->where('size_id',$size_id)
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
        $tableSizeModel = new MstTableSize();

        $size_id    = $request->param("size_id","");//容量id
        $sort       = $request->param("sort","");

        $rule = [
            "size_id|容量id"  => "require",
            "sort|排序"       => "require|number",
        ];
        $check_data = [
            "size_id" => $size_id,
            "sort"    => $sort,
        ];

        $validate = new Validate($rule);

        if (!$validate->check($check_data)){
            return $this->com_return(false,$validate->getError());
        }

        $update_data = [
            "sort" => $sort,
            "updated_at" => time()
        ];

        $is_ok = $tableSizeModel
            ->where('size_id',$size_id)
            ->update($update_data);

        if ($is_ok !== false){

            return $this->com_return(true,config("params.SUCCESS"));

        }else{

            return $this->com_return(false,config("params.FAIL"));
        }

    }
}