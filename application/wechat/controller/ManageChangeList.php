<?php
/**
 * 管理端小程序换单操作.
 * User: qubojie
 * Date: 2018/8/26
 * Time: 下午3:03
 */
namespace app\wechat\controller;

use app\common\controller\UUIDUntil;
use think\Db;
use think\Exception;
use think\Request;
use think\Validate;

class ManageChangeList extends HomeAction
{

    /**
     * TODO 换单接口亟需完善
     * 管理员换单
     * @param Request $request
     * @return array
     */
    public function changeList(Request $request)
    {
        $trid               = $request->param("trid","");//桌台预约id

//        $pid                = $request->param("pid","");//pid

        $refund_dish_group  = $request->param("refund_dish_group","");//要退菜品集合

        $refund_dish_amount = $request->param("refund_dish_amount","");//要退菜品总额

        $need_dish_group    = $request->param("need_dish_group","");//新换菜品集合

        $need_dish_amount   = $request->param("need_dish_amount","");//新换菜品总额

        $pay_type           = $request->param("pay_type","");//支付方式

        $rule = [
            "trid|桌台预约id"                 => "require",
            "refund_dish_group|要退菜品集合"  => "require",
            "refund_dish_amount|要退菜品总额" => "require",
            "need_dish_group|新换菜品集合"    => "require",
            "need_dish_amount|新换菜品总额"   => "require",
            "pay_type|支付方式"               => "require",
        ];

        $request_res = [
            "trid"               => $trid,
            "refund_dish_group"  => $refund_dish_group,
            "refund_dish_amount" => $refund_dish_amount,
            "need_dish_group"    => $need_dish_group,
            "need_dish_amount"   => $need_dish_amount,
            "pay_type"           => $pay_type,
        ];

        $validate = new Validate($rule);

        if (!$validate->check($request_res)){
            return $this->com_return(false,$validate->getError());
        }

        Db::startTrans();
        try{

            /*第一步 插入退菜信息 on*/
            $insertRefundDishReturn = $this->insertRefundDish($trid,$refund_dish_group,$refund_dish_amount);

            dump($insertRefundDishReturn);die;

            /*第一步 插入退菜信息 off*/


            /*第二步 插入新增菜信息 on*/

            $insertNeedDishReturn = $this->insertNeedDish();

            /*第二步 插入新增菜信息 off*/




        }catch (Exception $e){
            Db::rollback();
            $this->com_return(false,$e->getMessage());
        }
    }

    /**
     * 插入退菜信息
     * @param $trid
     * @param $refund_dish_group
     * @param $refund_dish_amount
     */
    public function insertRefundDish($trid,$refund_dish_group,$refund_dish_amount)
    {
        dump($trid);
        dump($refund_dish_group);
        dump($refund_dish_amount);

        $UUID = new UUIDUntil();
        $pid  = $UUID->generateReadableUUID("P");



    }

    /**
     * 插入新增菜信息
     */
    public function insertNeedDish()
    {

    }

}