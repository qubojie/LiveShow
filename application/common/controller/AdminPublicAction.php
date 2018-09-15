<?php
/**
 * Created by PhpStorm.
 * User: qubojie
 * Date: 2018/9/14
 * Time: 下午12:15
 */
namespace app\common\controller;

use app\admin\controller\Common;
use app\admin\model\MstGiftVoucher;
use app\admin\model\User;
use think\Controller;

class AdminPublicAction extends Controller
{
    /**
     * 发券检测礼券有效性
     * @param $gift_vou_id
     * @return bool
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public static function checkVoucherValid($gift_vou_id)
    {

        $info = self::getVoucherInfo($gift_vou_id);

        if (empty($info)){
            return false;
        }

        $gift_validity_type    = $info['gift_validity_type'];// 有效类型   0无限期   1按天数生效   2按指定有效日期

        $gift_vou_validity_day = $info['gift_vou_validity_day'];//礼券有效时间（天） 0表示无限期

        $gift_start_day        = $info['gift_start_day'];//有效开始日期   开始日期 0 或者空 表示 发劵日期开始      否则  指定开始日期

        $gift_end_day          = $info['gift_end_day'];//有效结束日期

        $gift_vou_exchange     = $info['gift_vou_exchange'];//使用时段信息规则（保存序列）

        if ($gift_validity_type == 1){
            //按天数生效
            if ($gift_start_day > 0){
                //指定开始日期
                if (time() > $gift_end_day){
                    //如果当前时间大于结束日期
                    return false;
                }
            }

        }elseif ($gift_validity_type == 2){
            //按指定有效日期
            if ($gift_start_day > 0){
                //指定开始日期
                if (time() > $gift_end_day){
                    //如果当前时间大于结束日期
                    return false;
                }
            }

        }else{
            //无限制
            return true;
        }

        return true;
    }

    /**
     * 获取礼券信息
     * @param $gift_vou_id
     * @return array|false|mixed|\PDOStatement|string|\think\Model
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public static function getVoucherInfo($gift_vou_id)
    {
        $voucherModel = new MstGiftVoucher();

        $info = $voucherModel
            ->where("gift_vou_id",$gift_vou_id)
            ->where("is_delete",0)
            ->where("is_enable",1)
            ->find();

        $info = json_decode(json_encode($info),true);

        return $info;
    }

    /**
     * 电话号码获取用户信息
     * @param $phone
     * @return array|false|mixed|\PDOStatement|string|\think\Model
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public static function phoneGetUserInfo($phone)
    {
        $userModel = new User();

        $u_column = $userModel->u_column;

        $userInfo = $userModel
            ->alias("u")
            ->join("user_card uc","uc.uid = u.uid","LEFT")
            ->join("mst_card_vip cv","cv.card_id = uc.card_id","LEFT")
            ->where("u.phone",$phone)
            ->field($u_column)
            ->field("cv.card_id,cv.card_type,cv.card_name")
            ->find();

        $userInfo = json_decode(json_encode($userInfo),true);

        return $userInfo;
    }
}