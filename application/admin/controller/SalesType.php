<?php
/**
 * 营销人员类型设置.
 * User: qubojie
 * Date: 2018/6/23
 * Time: 下午2:38
 */
namespace app\admin\controller;

use app\admin\model\MstSalesmanType;
use think\Db;
use think\Exception;
use think\Request;
use think\Validate;

/*
 * 营销人员类型设置
 * */
class SalesType extends CommandAction
{
    /*
     * 列表
     * */
    public function index(Request $request)
    {
        $common = new Common();

        $salesmanTypeModel = new MstSalesmanType();

        $pagesize = $request->param("pagesize",config('PAGESIZE'));//当前页,不传时为10

        $nowPage = $request->param("nowPage","1");

        $keyword = $request->param("keyword","");

        if (empty($pagesize)){
            $pagesize = config('PAGESIZE');
        }

        $where = [];
        if (!empty($keyword)){
            $where['stype_name'] = ["like","%$keyword%"];
        }

        $count = $salesmanTypeModel->count();//总记录数

        $pageNum = ceil($count/$pagesize); //总页数

        $config = [
            "page" => $nowPage,
        ];

        $list = $salesmanTypeModel
            ->where($where)
            ->where('is_enable',1)
            ->paginate($pagesize,false,$config);

        return $common->com_return(true,config("GET_SUCCESS"),$list);
    }

    /*
     * 添加
     * */
    public function add(Request $request)
    {
        $common = new Common();
        $salesmanTypeModel = new MstSalesmanType();

        $stype_key        = $request->param("stype_key","vip");//销售员类型key
        $stype_name       = $request->param("stype_name","");//销售员等级名称
        $stype_desc       = $request->param("stype_desc","");//销售员等级描述
        $commission_ratio = $request->param("commission_ratio",0);//佣金比例 （百分比整数，6代表 6‰）

        $rule = [
            "stype_name|销售员等级名称"   => "require|max:20|unique:mst_salesman_type",
            "stype_desc|销售员等级描述"   => "require|max:400",
            "commission_ratio|佣金比例"  => "require|number",
        ];

        $request_res = [
            "stype_name"       => $stype_name,
            "stype_desc"       => $stype_desc,
            "commission_ratio" => $commission_ratio,
        ];

        $validate = new Validate($rule);

        if (!$validate->check($request_res)){
            return $common->com_return(false,$validate->getError(),null);
        }

        $time = time();

        $insert_data = [
            "stype_name"       => $stype_name,
            "stype_desc"       => $stype_desc,
            "commission_ratio" => $commission_ratio,
            "created_at"       => $time,
            "updated_at"       => $time
        ];
        Db::startTrans();
        try{
            $is_ok = $salesmanTypeModel
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

    /*
     * 编辑
     * */
    public function edit(Request $request)
    {
        $common = new Common();
        $salesmanTypeModel = new MstSalesmanType();

        $stype_id         = $request->param("stype_id","");
        $stype_name       = $request->param("stype_name","");//销售员等级名称
        $stype_desc       = $request->param("stype_desc","");//销售员等级描述
        $commission_ratio = $request->param("commission_ratio","");//佣金比例 （千分比整数，6代表 6‰）

        $rule = [
            "stype_id|销售员id"          => "require",
            "stype_name|销售员等级名称"   => "require|max:20|unique:mst_salesman_type",
            "stype_desc|销售员等级描述"   => "require|max:400",
            "commission_ratio|佣金比例"  => "require|number",
        ];

        $request_res = [
            "stype_id"         => $stype_id,
            "stype_name"       => $stype_name,
            "stype_desc"       => $stype_desc,
            "commission_ratio" => $commission_ratio,
        ];

        $validate = new Validate($rule);

        if (!$validate->check($request_res)){
            return $common->com_return(false,$validate->getError(),null);
        }

        $update_data = [
            "stype_name"       => $stype_name,
            "stype_desc"       => $stype_desc,
            "commission_ratio" => $commission_ratio,
            "updated_at"       => time()
        ];

        try{
            $is_ok = $salesmanTypeModel
                ->where("stype_id",$stype_id)
                ->update($update_data);

            if ($is_ok !== false){
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
     * 删除
     * */
    public function delete(Request $request)
    {
        $common = new Common();

        $salesmanTypeModel = new MstSalesmanType();

        $stype_ids         = $request->param("stype_id","");

        if (!empty($stype_ids)){

            $id_array = explode(",",$stype_ids);

            Db::startTrans();
            try{

                $is_ok =false;

                foreach ($id_array as $stype_id){
                    $is_ok = $salesmanTypeModel
                        ->where("stype_id",$stype_id)
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
                return $common->com_return(false,$e->getMessage(),null);

            }
        }else{

            return $common->com_return(false, config("PARAM_NOT_EMPTY"));

        }
    }
}