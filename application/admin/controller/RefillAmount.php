<?php
/**
 * Created by PhpStorm.
 * User: qubojie
 * Date: 2018/8/3
 * Time: 下午1:44
 */
namespace app\admin\controller;

use app\admin\model\MstRefillAmount;
use think\Request;
use think\Validate;

class RefillAmount extends CommandAction
{
    /**
     * 列表
     * @param Request $request
     * @return array
     * @throws \think\exception\DbException
     */
    public function index(Request $request)
    {
        $refillAmountModel = new MstRefillAmount();

        $pagesize = $request->param("pagesize",config('PAGESIZE'));//显示个数,不传时为10

        if (empty($pagesize)) $pagesize = config('PAGESIZE');

        $nowPage = $request->param("nowPage","1");

        $count = $refillAmountModel->count();//总记录数

        $pageNum = ceil($count/$pagesize); //总页数

        $config = [
            "page" => $nowPage,
        ];

        $list = $refillAmountModel
            ->order("sort")
            ->paginate($pagesize,false,$config);

        return $this->com_return(true,config("params.SUCCESS"),$list);

    }

    /**
     * 添加
     * @param Request $request
     * @return array
     */
    public function add(Request $request)
    {
        $refillAmountModel = new MstRefillAmount();

        $amount    = $request->param("amount","");//充值金额
        $cash_gift = $request->param("cash_gift","");//赠送礼金数
        $desc      = $request->param("desc","");//描述
        $sort      = $request->param("sort","");//排序
        $is_enable = $request->param("is_enable","");//是否激活

        $rule = [
            "amount|充值金额"      => "require|number|max:20|unique:mst_refill_amount|gt:0",
            "cash_gift|赠送礼金数" => "require|number|max:20|egt:0",
            "sort|排序"           => "require|number|max:6",
            "is_enable|是否激活"   => "require|number",
        ];

        $request_res = [
            "amount"    => $amount,
            "cash_gift" => $cash_gift,
            "sort"      => $sort,
            "is_enable" => $sort,
        ];

        $validate = new Validate($rule);

        if ($sort == 0){
            $sort = 100;
        }

        if (!$validate->check($request_res)){
            return $this->com_return(false,$validate->getError(),null);
        }

        $time = time();

        $params = [
            "amount"     => $amount,
            "cash_gift"  => $cash_gift,
            "desc"       => $desc,
            "sort"       => $sort,
            "is_enable"  => $is_enable,
            "created_at" => $time,
            "updated_at" => $time,
        ];

        $is_ok = $refillAmountModel
            ->insert($params);

        if ($is_ok){
            return $this->com_return(true, config("params.SUCCESS"));
        }else{
            return $this->com_return(false, config("params.FAIL"));
        }
    }

    /**
     * 编辑
     * @param Request $request
     * @return array
     */
    public function edit(Request $request)
    {
        $refillAmountModel = new MstRefillAmount();

        $id        = $request->param("id","");//id
        $amount    = $request->param("amount","");//充值金额
        $cash_gift = $request->param("cash_gift","");//赠送礼金数
        $desc      = $request->param("desc","");//描述
        $sort      = $request->param("sort","");//排序
        $is_enable = $request->param("is_enable","");//是否激活

        $rule = [
            "id|参数id"           => "require",
            "amount|充值金额"      => "require|number|max:20|unique:mst_refill_amount|gt:0",
            "cash_gift|赠送礼金数" => "require|number|max:20|egt:0",
            "sort|排序"           => "require|number|max:6",
            "is_enable|是否激活"   => "require|number",
        ];

        $request_res = [
            "id"        => $id,
            "amount"    => $amount,
            "cash_gift" => $cash_gift,
            "sort"      => $sort,
            "is_enable" => $sort,
        ];

        $validate = new Validate($rule);

        if (!$validate->check($request_res)){
            return $this->com_return(false,$validate->getError(),null);
        }

        if ($sort == 0){
            $sort = 100;
        }

        $time = time();

        $params = [
            "amount"     => $amount,
            "cash_gift"  => $cash_gift,
            "desc"       => $desc,
            "sort"       => $sort,
            "is_enable"  => $is_enable,
            "updated_at" => $time,
        ];

        $is_ok = $refillAmountModel
            ->where('id',$id)
            ->update($params);

        if ($is_ok !== false){
            return $this->com_return(true, config("params.SUCCESS"));
        }else{
            return $this->com_return(false, config("params.FAIL"));
        }
    }

    /**
     * 删除
     * @param Request $request
     * @return array
     */
    public function delete(Request $request)
    {
        $refillAmountModel = new MstRefillAmount();

        $id = $request->param("id","");//id

        $rule = [
            "id|参数id"           => "require",
        ];

        $request_res = [
            "id"        => $id,
        ];

        $validate = new Validate($rule);

        if (!$validate->check($request_res)){
            return $this->com_return(false,$validate->getError(),null);
        }

        $is_ok = $refillAmountModel
            ->where('id',$id)
            ->delete();

        if ($is_ok !== false){
            return $this->com_return(true, config("params.SUCCESS"));
        }else{
            return $this->com_return(false, config("params.FAIL"));
        }
    }

    /**
     * 是否启用
     * @param Request $request
     * @return array
     */
    public function enable(Request $request)
    {
        $refillAmountModel = new MstRefillAmount();

        $is_enable = (int)$request->param("is_enable","");//是否启用
        $id        = $request->param("id","");//id

        $rule = [
            "id|参数id"           => "require",
            "is_enable|是否启用"   => "require",
        ];

        $request_res = [
            "id"        => $id,
            "is_enable" => $is_enable,
        ];

        $validate = new Validate($rule);

        if (!$validate->check($request_res)){
            return $this->com_return(false,$validate->getError(),null);
        }

        $time = time();

        $params = [
            "is_enable"  => $is_enable,
            "updated_at" => $time,
        ];

        $is_ok = $refillAmountModel
            ->where('id',$id)
            ->update($params);

        if ($is_ok !== false){
            return $this->com_return(true, config("params.SUCCESS"));
        }else{
            return $this->com_return(false, config("params.FAIL"));
        }
    }

    /**
     * 排序编辑
     * @param Request $request
     * @return array
     */
    public function sortEdit(Request $request)
    {
        $refillAmountModel = new MstRefillAmount();

        $sort      = (int)$request->param("sort","");//排序
        $id        = $request->param("id","");//id

        $rule = [
            "id|参数id"   => "require",
            "sort|排序"   => "require",
        ];

        $request_res = [
            "id"   => $id,
            "sort" => $sort,
        ];

        $validate = new Validate($rule);

        if (!$validate->check($request_res)){
            return $this->com_return(false,$validate->getError(),null);
        }

        $time = time();

        $params = [
            "sort"       => $sort,
            "updated_at" => $time,
        ];

        $is_ok = $refillAmountModel
            ->where('id',$id)
            ->update($params);

        if ($is_ok !== false){
            return $this->com_return(true, config("params.SUCCESS"));
        }else{
            return $this->com_return(false, config("params.FAIL"));
        }
    }
}