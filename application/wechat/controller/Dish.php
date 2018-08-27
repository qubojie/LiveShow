<?php
/**
 * Created by PhpStorm.
 * User: qubojie
 * Date: 2018/8/18
 * Time: 上午11:43
 */
namespace app\wechat\controller;

use app\admin\model\Dishes;
use app\admin\model\DishesCategory;
use think\Controller;
use think\Db;
use think\Request;

class Dish extends CommonAction
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
        $dishCateGateModel = new DishesCategory();

        $list = $dishCateGateModel
            ->where("is_enable",1)
            ->where("is_delete",0)
            ->order("sort")
            ->field("cat_id,cat_name,cat_img")
            ->select();

        return $this->com_return(true,config("params.SUCCESS"),$list);
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
        if ($request->method() == "OPTIONS"){
            return $this->com_return(true,config("params.SUCCESS"));
        }

        $token    = $request->header("Token");

        $cat_id   = $request->param("cat_id","");//分类id

        $is_vip   = $request->param("is_vip","");//会员专享

        $dis_type = $request->param("dis_type","");//套餐

        $is_gift  = $request->param("is_gift","");//礼金专区

        $is_give  = $request->param("is_give","");//可否赠送  0否   1是

        $cat_where = [];
        if (!empty($cat_where)){
            $cat_where['d.cat_id'] = ["eq",$cat_id];
        }

        $is_normal_where = [];
        $is_vip_where = [];
        if (empty($is_vip)){
            $is_normal_where['d.is_normal'] = ["eq",1];
        }else{
            $is_vip_where['d.is_vip'] = ["eq",1];
        }

        $dis_type_where = [];
        if ($dis_type == "1"){
            $dis_type_where['d.dis_type'] = ["eq",1];
        }else{
            $dis_type_where['d.dis_type'] = ["eq",0];
        }

        $is_gift_where = [];
        if ($is_gift == "1"){
            $is_gift_where['d.is_gift'] = ["eq",1];
        }else{
            $is_gift_where['d.is_gift'] = ["eq",0];

        }

        $is_give_where = [];
        if ($is_give == "1"){
            $is_give_where['d.is_give'] = ["eq",1];
            $is_gift_where = [];
        }else{
            $is_gift_where['d.is_give'] = ["eq",0];
            $is_gift_where = [];
        }

        $dishesModel = new Dishes();

        $list = $dishesModel
            ->alias("d")
            ->where($cat_where)
            ->where($is_normal_where)
            ->where($is_vip_where)
            ->where($dis_type_where)
            ->where($is_gift_where)
            ->where($is_give_where)
            ->where("d.is_enable",1)
            ->where("d.is_delete",0)
            ->select();

        $list = json_decode(json_encode($list),true);

        /*获取用户开卡信息 on*/
        $obj = new CommonAction();

        $userInfo = $obj->tokenGetUserInfo($token);

        $uid = $userInfo["uid"];

        $userCardInfo = Db::name("user_card")
            ->where("uid",$uid)
            ->field("card_id")
            ->where("is_valid",1)
            ->find();

        if (!empty($userCardInfo)){
            $card_id = $userCardInfo["card_id"];
        }else{
            $card_id = 0;
        }
        /*获取用户开卡信息 off*/

        for ($i = 0; $i <count($list); $i ++){
            $dis_id = $list[$i]['dis_id'];

            $dishes_card_price = Db::name("dishes_card_price")
                ->alias("dcp")
                ->join("mst_card_vip mcv","mcv.card_id = dcp.card_id")
                ->where("dcp.dis_id",$dis_id)
                ->field("mcv.card_name")
                ->field("dcp.dis_id,dcp.card_id,dcp.price")
                ->select();

            $dishes_card_price = json_decode(json_encode($dishes_card_price),true);

            for ($m = 0; $m <count($dishes_card_price); $m ++){

                if ($dishes_card_price[$m]['dis_id'] == $dis_id){
                    if ($dishes_card_price[$m]['card_id'] == $card_id){
                        $list[$i]['dis_vip_price']  = $dishes_card_price[$m]['price'];
                    }else{
                        $list[$i]['dis_vip_price']  = $list[$i]['normal_price'];
                    }
                }else{
                    $list[$i]['dis_vip_price']  = $list[$i]['normal_price'];
                }

                $list[$i]['dis_vip_all_price'] = $dishes_card_price;

            }
        }

        return $this->com_return(true,config("params.SUCCESS"),$list);
    }

    /**
     * 菜品详情
     * @param Request $request
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function dishDetail(Request $request)
    {
        $dis_id = $request->param("dis_id","");//菜品id

        if (empty($dis_id)){

            return $this->com_return(false,config("params.PARAM_NOT_EMPTY"));

        }

        /*获取用户开卡信息 on*/
        $token    = $request->header("Token");
        $obj = new CommonAction();

        $userInfo = $obj->tokenGetUserInfo($token);

        $uid = $userInfo["uid"];

        $userCardInfo = Db::name("user_card")
            ->where("uid",$uid)
            ->field("card_id")
            ->where("is_valid",1)
            ->find();

        if (!empty($userCardInfo)){
            $card_id = $userCardInfo["card_id"];
        }else{
            $card_id = 0;
        }
        /*获取用户开卡信息 off*/

        $dishesModel = new Dishes();

        $dishInfo = $dishesModel
            ->alias("d")
            ->where("dis_id",$dis_id)
            ->where("d.is_enable",1)
            ->where("d.is_delete",0)
            ->find();

        $dishInfo = json_decode(json_encode($dishInfo),true);

        if (empty($dishInfo)){

            return $this->com_return(false,config("params.ABNORMAL_ACTION"));
        }

        if ($card_id != 0){
            $dishes_card_price = Db::name("dishes_card_price")
                ->alias("dcp")
                ->join("mst_card_vip mcv","mcv.card_id = dcp.card_id")
                ->where("dcp.dis_id",$dis_id)
                ->field("mcv.card_name")
                ->field("dcp.dis_id,dcp.card_id,dcp.price")
                ->select();

            $dishes_card_price = json_decode(json_encode($dishes_card_price),true);

            for ($m = 0; $m <count($dishes_card_price); $m ++){

                if ($dishes_card_price[$m]['dis_id'] == $dis_id){
                    if ($dishes_card_price[$m]['card_id'] == $card_id){
                        $dishInfo['dis_vip_price']  = $dishes_card_price[$m]['price'];
                    }
                }else{
                    $dishInfo['dis_vip_price']  = $dishInfo['normal_price'];
                }

                $dishInfo['dis_vip_all_price'] = $dishes_card_price;

            }

        }else{
            $dishInfo['dis_vip_price'] = $dishInfo['normal_price'];
        }

        $dis_type = $dishInfo['dis_type'];

        if ($dis_type){

            //套餐
            $dishesComboInfo = Db::name("dishes_combo")
                ->alias("dc")
                ->join("dishes d","d.dis_id = dc.dis_id")
                ->where("dc.main_dis_id",$dis_id)
                ->field("d.dis_name")
                ->field("dc.combo_id,dc.dis_id,dc.type,dc.type_desc,dc.parent_id,dc.quantity")
                ->select();

            $dishesComboInfo = json_decode(json_encode($dishesComboInfo),true);

            dump($dishesComboInfo);die;

        }




    }
}