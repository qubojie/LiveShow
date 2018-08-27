<?php
/**
 * Created by PhpStorm.
 * User: qubojie
 * Date: 2018/8/27
 * Time: 下午3:52
 */
namespace app\wechat\controller;

use app\admin\model\Dishes;
use think\Request;

class ManageDish extends HomeAction
{
    /**
     * 菜品分类
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function dishClassify()
    {
        $pointListPublicObj = new PointListPublicAction();

        return $pointListPublicObj->dishTypePublic();
    }

    /**
     * 菜品列表
     * @param Request $request
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function index(Request $request)
    {
        $cat_id   = $request->param("cat_id","");//分类id

        $dis_type = $request->param("dis_type","");//套餐

        $is_give  = $request->param("is_give","");//可否赠送  0否   1是

        $cat_where = [];
        if (!empty($cat_id)){
            $cat_where['d.cat_id'] = ["eq",$cat_id];
        }

        $dis_type_where = [];
        if ($dis_type == "1"){
            $dis_type_where['d.dis_type'] = ["eq",1];
        }else{
            $dis_type_where['d.dis_type'] = ["eq",0];
        }

        $is_give_where = [];
        if ($is_give == "1"){
            $is_give_where['d.is_give'] = ["eq",1];
        }else{
            $is_give_where['d.is_normal'] = ["eq",1];
        }

        $dishesModel = new Dishes();

        $list = $dishesModel
            ->alias("d")
            ->where($cat_where)
            ->where($dis_type_where)
            ->where($is_give_where)
            ->where("d.is_enable","1")
            ->where("d.is_delete","0")
            ->select();

        $list = json_decode(json_encode($list),true);

        return $this->com_return(true,config("params.SUCCESS"),$list);
    }

}