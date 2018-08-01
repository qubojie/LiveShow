<?php
/**
 * 酒桌品相管理.
 * User: qubojie
 * Date: 2018/7/26
 * Time: 下午6:39
 */
namespace app\admin\controller;

use app\admin\model\MstTableAppearance;
use think\Request;
use think\Validate;

class TableAppearance extends CommandAction
{
    /**
     * 品相列表
     * @param Request $request
     * @return array
     * @throws \think\exception\DbException
     */
    public function index(Request $request)
    {
        $tableAppearanceModel = new MstTableAppearance();

        $pagesize = $request->param("pagesize",config('PAGESIZE'));//显示个数,不传时为10

        if (empty($pagesize)) $pagesize = config('PAGESIZE');

        $nowPage = $request->param("nowPage","1");

        $config = [
            "page" => $nowPage,
        ];

        $list = $tableAppearanceModel
            ->where('is_delete',0)
            ->order("sort")
            ->paginate($pagesize,false,$config);

        return $this->com_return(true,config("params.SUCCESS"),$list);

    }

    /**
     * 品相添加
     * @param Request $request
     * @return array
     */
    public function add(Request $request)
    {
        $tableAppearanceModel = new MstTableAppearance();

        $appearance_title = $request->param("appearance_title","");//品相标题
        $appearance_desc  = $request->param("appearance_desc","");//品相描述
        $sort             = $request->param("sort","");//排序

        $rule = [
            "appearance_title|品相标题"  => "require|max:30|unique:mst_table_appearance",
            "appearance_desc|品相描述"   => "require|max:200",
            "sort|排序"                 => "number",
        ];
        $check_data = [
            "appearance_title" => $appearance_title,
            "appearance_desc"  => $appearance_desc,
            "sort"             => $sort,
        ];

        $validate = new Validate($rule);

        if (!$validate->check($check_data)){
            return $this->com_return(false,$validate->getError());
        }

        $time = time();

        $insert_data = [
            "appearance_title" => $appearance_title,
            "appearance_desc"  => $appearance_desc,
            "sort"             => $sort,
            "created_at"       => $time,
            "updated_at"       => $time
        ];

        $is_ok = $tableAppearanceModel
            ->insert($insert_data);

        if ($is_ok){
            return $this->com_return(true,config("SUCCESS"));
        }else{
            return $this->com_return(false,config("FAIL"));
        }
    }


    /**
     * 品相编辑
     * @param Request $request
     * @return array
     */
    public function edit(Request $request)
    {
        $tableAppearanceModel = new MstTableAppearance();

        $appearance_id    = $request->param("appearance_id","");//品相id
        $appearance_title = $request->param("appearance_title","");//品相标题
        $appearance_desc  = $request->param("appearance_desc","");//品相描述
        $sort             = $request->param("sort","");//排序

        $rule = [
            "appearance_id|品相id"      => "require",
            "appearance_title|品相标题"  => "require|max:30|unique:mst_table_appearance",
            "appearance_desc|品相描述"   => "require|max:200",
            "sort|排序"                 => "number",
        ];
        $check_data = [
            "appearance_id"    => $appearance_id,
            "appearance_title" => $appearance_title,
            "appearance_desc"  => $appearance_desc,
            "sort"             => $sort,
        ];

        $validate = new Validate($rule);

        if (!$validate->check($check_data)){
            return $this->com_return(false,$validate->getError());
        }

        $time = time();

        $update_data = [
            "appearance_title" => $appearance_title,
            "appearance_desc"  => $appearance_desc,
            "sort"             => $sort,
            "updated_at"       => $time
        ];

        $is_ok = $tableAppearanceModel
            ->where('appearance_id',$appearance_id)
            ->update($update_data);

        if ($is_ok !== false){
            return $this->com_return(true,config("SUCCESS"));
        }else{
            return $this->com_return(false,config("FAIL"));
        }
    }

    /**
     * 品相删除
     * @param Request $request
     * @return array
     */
    public function delete(Request $request)
    {
        $tableAppearanceModel = new MstTableAppearance();

        $appearance_id    = $request->param("appearance_id","");//品相id

        if(empty($appearance_id)) return $this->com_return(false,config("params.PARAM_NOT_EMPTY"));

        $delete_date = [
            "is_delete" => 1,
            "updated_at" => time()
        ];

        $is_ok = $tableAppearanceModel
            ->where('appearance_id',$appearance_id)
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
        $tableAppearanceModel = new MstTableAppearance();

        $appearance_id = $request->param("appearance_id","");//品相id
        $sort          = $request->param("sort","");

        $rule = [
            "appearance_id|品相id" => "require",
            "sort|排序"            => "require|number",
        ];
        $check_data = [
            "appearance_id" => $appearance_id,
            "sort"          => $sort,
        ];

        $validate = new Validate($rule);

        if (!$validate->check($check_data)){
            return $this->com_return(false,$validate->getError());
        }

        $update_data = [
            "sort" => $sort,
            "updated_at" => time()
        ];

        $is_ok = $tableAppearanceModel
            ->where('appearance_id',$appearance_id)
            ->update($update_data);

        if ($is_ok !== false){

            return $this->com_return(true,config("params.SUCCESS"));

        }else{

            return $this->com_return(false,config("params.FAIL"));
        }

    }
}