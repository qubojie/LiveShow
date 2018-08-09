<?php
/**
 * Created by PhpStorm.
 * User: qubojie
 * Date: 2018/7/6
 * Time: 上午11:14
 */
namespace app\wechat\controller;

use app\admin\controller\SalesUser;
use app\admin\model\ManageSalesman;
use app\admin\model\User;
use app\wechat\model\UserCard;
use think\Controller;
use think\Request;
use think\exception\HttpException;
use think\Response;

class CommonAction extends Controller
{
    /**
     * 初始化方法,其余控制器继承此方法，进行判断登录
     * @
     */
    public function _initialize()
    {
        parent::_initialize();
        $method = Request::instance()->method();

        if ( $method != "OPTIONS"){

            $Token = Request::instance()->header("Token","");

            if (!empty($Token)){

                $userModel = new User();
                $is_exist = $userModel
                    ->where("remember_token",$Token)
                    ->field('token_lastime')
                    ->find();

                if (!empty($is_exist)){
                    $time = time();//当前时间
                    $token_lastime = $is_exist['token_lastime'];//上次刷新token时间

                    $over_time = $token_lastime + 6000;   //过期时间
                    if ($time < $over_time){

                    }else{
                        abort(403,"登陆失效");
                    }
                }else{
                    abort(403,"登陆失效");

                }

            }else{
                abort(403,"登陆失效");

            }
        }
    }


    /**
     * 根据token获取用户信息
     * @param $token
     * @return array|false|null|\PDOStatement|string|\think\Model
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function tokenGetUserInfo($token)
    {
        $userModel = new User();

        $column = $userModel->column;

        $user_info = $userModel
            ->where('remember_token',$token)
            ->field($column)
            ->find();

        if (!empty($user_info)){
            $user_info = $user_info->toArray();
            return $user_info;
        }else{
            return null;
        }
    }

    /**
     * 根据uid获取用户开卡信息
     * @param $uid
     * @return array|false|null|\PDOStatement|string|\think\Model
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function uidGetCardInfo($uid)
    {
        $userCardModel = new UserCard();

        $card_info = $userCardModel
            ->where('uid',$uid)
            ->where("is_valid",1)
            ->find();

        if (!empty($card_info)){
            $card_info = $card_info->toArray();
            return $card_info;
        }else{
            return null;
        }

    }

    /**
     * 根据营销手机号码获取营销人员信息
     * @param $phone
     * @return array|false|mixed|\PDOStatement|string|\think\Model
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function phoneGetSalesmanInfo($phone)
    {
        $salesModel = new ManageSalesman();

        $salesmanInfo = $salesModel
            ->where("phone",$phone)
            ->field("sid,department_id,stype_id,sales_name,statue,phone,nickname,avatar,sex")
            ->find();

        $salesmanInfo = json_decode(json_encode($salesmanInfo),true);

        return $salesmanInfo;

    }
}