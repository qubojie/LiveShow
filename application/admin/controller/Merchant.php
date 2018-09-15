<?php
/**
 * 联盟商家
 * User: guojing
 * Date: 2018/9/13
 * Time: 14:28
 */

namespace app\admin\controller;


use app\admin\model\MstMerchant;
use app\admin\controller\CommandAction;
use think\Exception;
use think\Request;
use think\Validate;

class Merchant extends CommandAction
{

    /**
     * 联盟商家列表
     * @param Request $request
     * @return array
     * @throws \think\exception\DbException
     */
    public function index(Request $request)
    {
        $pagesize   = $request->param("pagesize",config('PAGESIZE'));//显示个数,不传时为10

        if (empty($pagesize)) $pagesize = config('PAGESIZE');

        $nowPage    = $request->param("nowPage","1");

        $keyword    = $request->param("keyword","");

        $cat_id     = $request->param("cat_id","");//联盟商家分类id

        $config = [
            "page" => $nowPage,
        ];

        $where = [];
        if (!empty($keyword)){
            $where["m.merchant"] = ["like","%$keyword%"];
        }

        $cat_where = [];
        if (!empty($cat_id)){
            $cat_where['m.cat_id'] = ['eq',$cat_id];
        }

        $merchantModel = new MstMerchant();

        $column = $merchantModel->column;

        foreach ($column as $key => $val){
            $column[$key] = "m.".$val;
        }

        $list = $merchantModel
            ->alias("m")
            ->join("mst_merchant_category mc","mc.cat_id = m.cat_id")
            ->where($where)
            ->where($cat_where)
            ->order("m.sort")
            ->field($column)
            ->field("mc.cat_name")
            ->paginate($pagesize,false,$config);

        $list = json_decode(json_encode($list),true);
        return $this->com_return(true,config("params.SUCCESS"),$list);
    }

    /**
     * 联盟商家添加
     * @param Request $request
     * @return array
     */
    public function add(Request $request)
    {
        $cat_id       = $request->param("cat_id","");//联盟商家分类id
        $merchant     = $request->param("merchant","");//联盟商家名称
        $merchant_desc     = $request->param("merchant_desc","");//联盟商家描述
        $address     = $request->param("address","");//联盟商家地址
        $sort         = $request->param("sort","");//排序
        $is_enable    = $request->param("is_enable","");//是否启用  0否 1是

        $rule = [
            "cat_id|联盟商家分类id"         => "require",
            "merchant|联盟商家名称"         => "require|max:30",
            "merchant_desc|联盟商家描述"         => "max:500",
            "address|联盟商家地址"         => "max:200",
            "sort|排序"                => "number",
            "is_enable|是否启用"        => "require|number",
        ];

        $check_res = [
            "cat_id"       => $cat_id,
            "merchant"     => $merchant,
            "merchant_desc"     => $merchant_desc,
            "address"     => $address,
            "sort"         => $sort,
            "is_enable"    => $is_enable,
        ];

        $validate = new Validate($rule);

        if (!$validate->check($check_res)){
            return $this->com_return(false,$validate->getError());
        }

        $nowTime = time();

        //此时添加单品
        $params = [
            "cat_id"       => $cat_id,
            "merchant"     => $merchant,
            "merchant_desc" => $merchant_desc,
            "address"      =>  $address,
            "sort"         => $sort,
            "is_enable"    => $is_enable,
            "created_at"   => $nowTime,
            "updated_at"   => $nowTime,
        ];

        $merchantModel = new MstMerchant();

        try{
            $is_ok = $merchantModel
                ->insertGetId($params);

            if ($is_ok){
                return $this->com_return(true,config("params.SUCCESS"));
            }else{
                return $this->com_return(false,config("params.FAIL"));
            }
        }catch (Exception $e){
            return $this->com_return(false,$e->getMessage());
        }

    }


    /**
     * 联盟商家编辑提交
     * @param Request $request
     * @return array
     */
    public function edit(Request $request)
    {
        $merchant_id  = $request->param("merchant_id","");//联盟商家id
        $cat_id       = $request->param("cat_id","");//联盟商家分类id
        $merchant     = $request->param("merchant","");//联盟商家名称
        $merchant_desc     = $request->param("merchant_desc","");//联盟商家描述
        $address     = $request->param("address","");//联盟商家地址
        $sort         = $request->param("sort","");//排序
        $is_enable    = $request->param("is_enable","");//是否启用  0否 1是


        $rule = [
            "merchant_id|联盟商家id"            => "require",
            "cat_id|联盟商家分类id"         => "require",
            "merchant|联盟商家名称"         => "require|max:30",
            "merchant_desc|联盟商家描述"         => "max:500",
            "address|联盟商家地址"         => "max:200",
            "sort|排序"                => "number",
            "is_enable|是否启用"        => "require|number",
        ];

        $check_res = [
            "merchant_id"       => $merchant_id,
            "cat_id"       => $cat_id,
            "merchant"     => $merchant,
            "merchant_desc"     => $merchant_desc,
            "address"     => $address,
            "sort"         => $sort,
            "is_enable"    => $is_enable,
        ];

        $validate = new Validate($rule);

        if (!$validate->check($check_res)){
            return $this->com_return(false,$validate->getError());
        }

        $nowTime = time();

        $params = [
            "cat_id"       => $cat_id,
            "merchant"     => $merchant,
            "merchant_desc" => $merchant_desc,
            "address"      =>  $address,
            "sort"         => $sort,
            "is_enable"    => $is_enable,
            "created_at"   => $nowTime,
            "updated_at"   => $nowTime,
        ];

        $merchantModel = new MstMerchant();

        try{

            $is_ok = $merchantModel
                ->where('merchant_id',$merchant_id)
                ->update($params);

            if ($is_ok !== false){
                return $this->com_return(true,config("params.SUCCESS"));
            }else{
                return $this->com_return(false,config("params.FAIL"));
            }

        }catch (Exception $e){
            return $this->com_return(false,$e->getMessage());
        }

    }


    /**
     * 联盟商家删除
     * @param Request $request
     * @return array
     */
    public function delete(Request $request)
    {
        $merchant_ids = $request->param("merchant_id","");//联盟商家id

        $rule = [
            "merchant_id|联盟商家id"      => "require",
        ];

        $check_res = [
            "merchant_id" => $merchant_ids,
        ];

        $validate = new Validate($rule);

        if (!$validate->check($check_res)){
            return $this->com_return(false,$validate->getError());
        }

        $merchantModel = new MstMerchant();

        $id_array = explode(",",$merchant_ids);

        try{
            $where['merchant_id'] = array('in',$id_array);
            $result = $merchantModel->where($where)->delete();
            if ($result){
                return $this->com_return(true,config("params.SUCCESS"));
            }else{
                return $this->com_return(false,config("params.FAIL"));
            }

        }catch (Exception $e){
            return $this->com_return(false,$e->getMessage());
        }
    }

    /**
     * 联盟商家排序
     * @param Request $request
     * @return array
     */
    public function sortEdit(Request $request)
    {
        $merchant_id = $request->param("merchant_id","");//联盟商家id
        $sort   = $request->param("sort","");//排序

        $rule = [
            "merchant_id|联盟商家id"  => "require",
            "sort|排序"      => "require|number",
        ];

        $check_res = [
            "merchant_id"  => $merchant_id,
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

        $merchantModel = new MstMerchant();

        $is_ok = $merchantModel
            ->where("merchant_id",$merchant_id)
            ->update($params);

        if ($is_ok !== false){
            return $this->com_return(true,config("params.SUCCESS"));
        }else{
            return $this->com_return(false,config("params.FAIL"));
        }

    }

    /**
     * 联盟商家是否启用
     * @param Request $request
     * @return array
     */
    public function enable(Request $request)
    {

        $merchant_id    = $request->param("merchant_id","");//联盟商家id
        $is_enable = $request->param("is_enable","");//是否启用

        $rule = [
            "merchant_id|联盟商家id"     => "require",
            "is_enable|是否启用" => "require|number",
        ];

        $check_res = [
            "merchant_id"    => $merchant_id,
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

        $merchantModel = new MstMerchant();

        $is_ok = $merchantModel
            ->where("merchant_id",$merchant_id)
            ->update($params);

        if ($is_ok !== false){
            return $this->com_return(true,config("params.SUCCESS"));
        }else{
            return $this->com_return(false,config("params.FAIL"));
        }
    }

}