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

        $pid                = $request->param("pid","");//pid

        $refund_dish_group  = $request->param("refund_dish_group","");//要退菜品集合

        $refund_dish_amount = $request->param("refund_dish_amount","");//要退菜品总额

        $need_dish_group    = $request->param("need_dish_group","");//新换菜品集合

        $need_dish_amount   = $request->param("need_dish_amount","");//新换菜品总额

        $pay_type           = $request->param("pay_type","");//支付方式

        $rule = [
            "trid|桌台预约id"                 => "require",
            "pid|订单id"                     => "require",
            "refund_dish_group|要退菜品集合"  => "require",
            "refund_dish_amount|要退菜品总额" => "require",
            "need_dish_group|新换菜品集合"    => "require",
            "need_dish_amount|新换菜品总额"   => "require",
        ];

        $request_res = [
            "trid"               => $trid,
            "pid"                => $pid,
            "refund_dish_group"  => $refund_dish_group,
            "refund_dish_amount" => $refund_dish_amount,
            "need_dish_group"    => $need_dish_group,
            "need_dish_amount"   => $need_dish_amount,
        ];

        $validate = new Validate($rule);

        if (!$validate->check($request_res)){
            return $this->com_return(false,$validate->getError());
        }

        Db::startTrans();
        try{
            /*第二步 插入新增菜信息 on*/

            $insertNeedDishReturn = $this->insertNeedDish($trid,$need_dish_group,$need_dish_amount);

            /*第二步 插入新增菜信息 off*/


            $token         = $request->header("Token","");

            $manageInfo    = $this->tokenGetManageInfo("$token");

            $cancel_user   = $manageInfo['sales_name'];
            $cancel_reason = "换单";

            /*第一步 插入退菜信息 on*/
            $insertRefundDishReturn = $this->insertRefundDish($trid,$pid,$refund_dish_group,$refund_dish_amount,$cancel_user,$cancel_reason);

            if (!$insertRefundDishReturn){
                return $this->com_return(false,config("params.ORDER")['REFUND_ABNORMAL']);
            }
            /*第一步 插入退菜信息 off*/


        }catch (Exception $e){
            Db::rollback();
            $this->com_return(false,$e->getMessage());
        }
    }

    /**
     * 插入退菜信息
     * @param $trid
     * @param $pid
     * @param $refund_dish_group
     * @param $refund_dish_amount
     * @param $cancel_user
     * @param $cancel_reason
     * @return bool
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function insertRefundDish($trid,$pid,$refund_dish_group,$refund_dish_amount,$cancel_user,$cancel_reason)
    {
        $refundListObj = new ManageRefundList();

        $res  = $refundListObj->oneRefundOrder("$pid","$refund_dish_group","$cancel_user","$cancel_reason","$trid");

        if (isset($res['result']) && $res['result']){
            return true;
        }else{
            return false;
        }

    }

    /**
     * 插入新增菜信息
     * @param $trid
     * @param $need_dish_group
     * @param $need_dish_amount
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function insertNeedDish($trid,$need_dish_group,$need_dish_amount)
    {
        dump("$need_dish_group");
        dump("$need_dish_amount");
        die;

        $pointListPublicObj = new PointListPublicAction();

        $res = $pointListPublicObj->pointListPublicAction("$trid","","","","","","");

    }

}