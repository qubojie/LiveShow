<?php
/**
 * Created by PhpStorm.
 * User: qubojie
 * Date: 2018/8/18
 * Time: 上午11:43
 */
namespace app\wechat\controller;

use app\admin\controller\Common;
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
        $pointListPublicObj = new PointListPublicAction();

        $res = $pointListPublicObj->dishTypePublic();

        $list = json_decode(json_encode($res),true);

        $is_vip = [
            "cat_id"   => config("dish.xcx_dish_menu")[0]['key'],
            "cat_name" => config("dish.xcx_dish_menu")[0]['name'],
            "cat_img"  => config("dish.xcx_dish_menu")[0]['img'],
        ];

        $is_combo = [
            "cat_id"   => config("dish.xcx_dish_menu")[1]['key'],
            "cat_name" => config("dish.xcx_dish_menu")[1]['name'],
            "cat_img"  => config("dish.xcx_dish_menu")[1]['img'],
        ];

        //向数组的前段新增元素
        array_unshift($list['data'],$is_vip,$is_combo);

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

        //$is_vip   = $request->param("is_vip","");//会员专享

        $dis_type = $request->param("dis_type","");//套餐

        $is_gift  = $request->param("is_gift","");//礼金专区


        if ($cat_id == "vip"){
            $is_vip = 1;
            $cat_id = "";

        }else{
            $is_vip = "";
        }

        if ($cat_id == "combo"){
            $dis_type = 1;
            $cat_id = "";
        }

        $cat_where = [];
        if (!empty($cat_id)){
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
            if ($is_vip){
                //如果是 会员专享,则显示左右的会员商品
                $dis_type_where['d.dis_type'] = ["IN",'0,1'];
            }else{
                $dis_type_where['d.dis_type'] = ["eq",0];
            }
        }

        $is_gift_where = [];
        if ($is_gift == "1"){
            $is_gift_where['d.is_gift'] = ["eq",1];

            $cat_where = [];
            $is_normal_where = [];
            $is_vip_where = [];
            $dis_type_where = [];
        }


        $pagesize = $request->param("pagesize",config('PAGESIZE'));

        $nowPage = $request->has("nowPage") ? $request->param("nowPage") : "1"; //当前页

        $config = [
            "page" => $nowPage
        ];

        $dishesModel = new Dishes();

        $list = $dishesModel
            ->alias("d")
            ->where($cat_where)
            ->where($is_normal_where)
            ->where($is_vip_where)
            ->where($dis_type_where)
            ->where($is_gift_where)
            ->where("d.is_enable",1)
            ->where("d.is_delete",0)
//            ->paginate($pagesize,'',$config);
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

                        $list[$i]['dis_vip_price']  = (int)$dishes_card_price[$m]['price'];

                    }

                    if ($card_id == 0){

                        $list[$i]['dis_vip_price']  = (int)$list[$i]['normal_price'];

                    }

                }else{
                    $list[$i]['dis_vip_price']  = $list[$i]['normal_price'];
                }

                $list[$i]['dis_vip_all_price'] = $dishes_card_price;

            }

            $list[$i]['deal_price']     = $list[$i]['normal_price'];
            $list[$i]['discount_price'] = 0;

            if ($is_vip){
                $list[$i]['deal_price'] = $list[$i]['dis_vip_price'];
                $list[$i]['discount_price'] = $list[$i]['normal_price'] - $list[$i]['dis_vip_price'];
            }

            if ($is_gift){
                $list[$i]['deal_price'] = $list[$i]['gift_price'];
                $list[$i]['discount_price'] = 0;
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
        $dis_id  = $request->param("dis_id","");//菜品id
        $is_vip  = $request->param("is_vip","");//会员专区
        $is_gift = $request->param("is_gift","");//礼金区

        if (empty($dis_id)){

            return $this->com_return(false,config("params.PARAM_NOT_EMPTY"));

        }

        /*获取用户开卡信息 on*/
        $token = $request->header("Token");
        $obj   = new CommonAction();

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

        $dishInfo['deal_price']     = $dishInfo['normal_price'];
        $dishInfo['discount_price'] = 0;

        if ($is_vip == "vip"){
            $dishInfo['deal_price'] = $dishInfo['dis_vip_price'];
            $dishInfo['discount_price'] = $dishInfo['normal_price'] - $dishInfo['dis_vip_price'];
        }


        if ($is_gift){
            $dishInfo['deal_price'] = $dishInfo['gift_price'];
            $dishInfo['discount_price'] = 0;
        }

        $dis_type = $dishInfo['dis_type'];

        if ($dis_type){

            //套餐
            $dishesComboInfo = Db::name("dishes_combo")
                ->alias("dc")
                ->join("dishes d","d.dis_id = dc.dis_id","LEFT")
                ->where("dc.main_dis_id",$dis_id)
                ->field("d.dis_name,d.dis_img")
                ->field("dc.combo_id,dc.dis_id,dc.type,dc.type_desc,dc.parent_id,dc.quantity")
                ->select();

            $dishesComboInfo = json_decode(json_encode($dishesComboInfo),true);

            foreach ($dishesComboInfo as $k => $v){
                if ($v['type']){
                    $dishesComboInfo[$k]['dis_name'] = $v['type_desc'];
                }
            }
            $commonObj = new Common();

            $dishesComboInfo = $commonObj->make_tree($dishesComboInfo,"combo_id","parent_id");

            $dishInfo['dishes_combo_info'] = $dishesComboInfo;

        }else{

            $dishInfo['dishes_combo_info'] = [];

        }

        return $this->com_return(true,config("params.SUCCESS"),$dishInfo);

    }
}
