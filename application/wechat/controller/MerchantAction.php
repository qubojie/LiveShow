<?php
/**
 * Created by PhpStorm.
 * User: guojing
 * Date: 2018/9/13
 * Time: 18:23
 */

namespace app\wechat\controller;


use app\admin\model\MstMerchant;
use app\admin\model\MstMerchantCategory;
use think\Request;

class MerchantAction extends CommonAction
{

    /**
     * 分类列表 ———— 键值对（小程序）
     * @param Request $request
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function cateList(Request $request)
    {
        $merchantCategoryModel = new MstMerchantCategory();

        $res = $merchantCategoryModel
            ->where('is_delete',0)
            ->field("cat_id,cat_name")
            ->select();

        $res = json_decode(json_encode($res),true);

        $list = [];
        foreach ($res as $key => $val){

            foreach ($val as $k => $v){

                if ($k == "cat_id"){
                    $k = "key";
                }else{
                    $k = "name";
                }

                $list[$key][$k] = $v;
            }
        }
        return $this->com_return(true,config("params.SUCCESS"),$list);
    }

    /**
     * 联盟商家列表（小程序）
     * @param Request $request
     * @return array
     * @throws \think\exception\DbException
     */
    public function merchatList(Request $request)
    {
        $pagesize   = $request->param("pagesize",config('PAGESIZE'));//显示个数,不传时为10

        if (empty($pagesize)) $pagesize = config('PAGESIZE');

        $nowPage    = $request->param("nowPage","1");

        $cat_id     = $request->param("cat_id","");//联盟商家分类id

        $config = [
            "page" => $nowPage,
        ];

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
            ->where($cat_where)
            ->order("m.sort")
            ->field($column)
            ->field("mc.cat_name")
            ->paginate($pagesize,false,$config);

        $list = json_decode(json_encode($list),true);
        return $this->com_return(true,config("params.SUCCESS"),$list);
    }
}