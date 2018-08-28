<?php
/**
 * 管理端小程序换单操作.
 * User: qubojie
 * Date: 2018/8/26
 * Time: 下午3:03
 */
namespace app\wechat\controller;

use think\Request;

class ManageChangeList extends HomeAction
{

    /**
     * 管理员换单
     * @param Request $request
     */
    public function changeList(Request $request)
    {
        $trid               = $request->param("trid","");//桌台预约id

        $pid                = $request->param("pid","");//pid

        $refund_dish_group  = $request->param("refund_dish_group","");//要退菜品集合

        $refund_dish_amount = $request->param("refund_dish_amount","");//要退菜品总额

        $need_dish_group    = $request->param("need_dish_group","");//新换菜品集合

        $need_dish_amount   = $request->param("need_dish_amount","");//新换菜品总额

        $params = $request->param();

        dump($params);die;









    }

}