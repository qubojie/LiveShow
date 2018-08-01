<?php
/**
 * Created by PhpStorm.
 * User: qubojie
 * Date: 2018/7/3
 * Time: 下午2:16
 */
namespace app\wechat\controller;

use app\admin\controller\Common;
use app\admin\model\User;
use app\wechat\model\BillCardFees;
use think\Controller;
use think\Db;
use think\Exception;
use think\Request;
use think\Route;

class BillOrder extends Controller
{
    /**
     * 取消订单
     * @param Request $request
     * @return array
     */
    public function cancelOrder(Request $request)
    {
        $common = new Common();
        $uid = $request->param('uid','');
        $vid = $request->param('vid','');

        if (empty($uid)){
            return $common->com_return(false,'用户id不能为空');
        }
        if (empty($vid)){
            return $common->com_return(false,'订单id不能为空');
        }

        $billCardsFeesModel = new BillCardFees();
        $bill_info = $billCardsFeesModel
            ->where('uid',$uid)
            ->where('vid',$vid)
            ->where('sale_status','0')
            ->count();
        if ($bill_info){
            $time = time();
            //更改订单状态
            $params = [
                'sale_status' => 9,
                'cancel_user' => 'self',
                'cancel_time' => $time,
                'auto_cancel' => 0,
                'auto_cancel_time' => $time,
                'cancel_reason' => '用户手动取消',
                'updated_at' => $time
            ];

            Db::startTrans();
            try{
                $is_ok= $billCardsFeesModel
                    ->where('vid',$vid)
                    ->update($params);
                if ($is_ok){
                    //更改用户状态
                    $userModel = new User();
                    $user_params = [
                        'user_status' => 0,
                        'updated_at'  => $time
                    ];

                    $is_true = $userModel
                        ->where('uid',$uid)
                        ->update($user_params);
                    if ($is_true){
                        Db::commit();
                        return $common->com_return(true,"取消成功");
                    }else{
                        return $common->com_return(false,"取消失败");
                    }
                }else{
                    return $common->com_return(false,"取消失败");
                }
            }catch (Exception $e){
                Db::rollback();
                return $common->com_return(false,$e->getMessage());
            }

        }else{
            return $common->com_return(false,"订单不存在");
        }
    }
}