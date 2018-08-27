<?php
/**
 * Created by PhpStorm.
 * User: qubojie
 * Date: 2018/8/16
 * Time: 上午11:40
 */
namespace app\services;

use app\wechat\controller\DishPublicAction;
use think\Cache;
use think\Controller;
use think\Env;
use think\Loader;

Loader::import('yly.YLYTokenClient');
Loader::import('yly.YLYOpenApiClient');


class YlyPrint extends Controller
{
    /**
     *获取token
     */
    public function getToken()
    {
        $yly_access_token  = Cache::get("yly_access_token");
        $yly_refresh_token = Cache::get("yly_refresh_token");


        if ($yly_access_token !== false){

            $params = [
                "access_token" => $yly_access_token,
                "refresh_token"=> $yly_refresh_token
            ];

            return $this->com_return(true,"缓存获取".config("params.SUCCESS"),$params);
        }


        if ($yly_refresh_token !== false){
            //刷新token
            $newToken = $this->refreshToken($yly_refresh_token);
            $newToken = json_decode($newToken,true);

            if ($newToken['error'] == '0'){
                $body          = $newToken['body'];
                $access_token  = $body['access_token'];//令牌
                $refresh_token = $body['refresh_token'];//更新access_token所需，有效时间35天
                $machine_code  = $body['machine_code'];//易连云终端号
                $expires_in    = $body['expires_in'];//令牌的有效时间，单位秒 (30天)

                Cache::set("yly_access_token",$access_token,$expires_in);//缓存

                $refresh_expires_in = $expires_in + 5 * 24 * 60 * 60;

                Cache::set("yly_refresh_token",$refresh_token,$refresh_expires_in);

                $params = [
                    "access_token" => $access_token,
                    "refresh_token"=> $refresh_token
                ];

                return $this->com_return(true,"刷新".config("params.SUCCESS"),$params);

            }else{
                return $this->com_return(false,$newToken['error_description']);
            }
        }

        //如果缓存和刷新都失效,获取新得token
        $token = new \YLYTokenClient();

        //获取token;
        $grantType = 'client_credentials';  //自有模式(client_credentials) || 开放模式(authorization_code)
        $scope = 'all';                     //权限
        $timesTamp = time();                //当前服务器时间戳(10位)
        //$code = '';                       //开放模式(商户code)
        $getToken = $token->GetToken($grantType,$scope,$timesTamp);

        $getToken = json_decode($getToken,true);

        if ($getToken['error'] == '0') {
            $body          = $getToken['body'];
            $access_token  = $body['access_token'];//令牌
            $refresh_token = $body['refresh_token'];//更新access_token所需，有效时间35天
            $machine_code  = $body['machine_code'];//易连云终端号
            $expires_in    = $body['expires_in'];//令牌的有效时间，单位秒 (30天)

            Cache::set("yly_access_token",$access_token,$expires_in);//缓存

            $refresh_expires_in = $expires_in + 5 * 24 * 60 * 60;

            Cache::set("yly_refresh_token",$refresh_token,$refresh_expires_in);

            $params = [
                "access_token" => $access_token,
                "refresh_token"=> $refresh_token
            ];

            return $this->com_return(true,"获取".config("params.SUCCESS"),$params);


        }else{
            return $this->com_return(false,$getToken['error_description']);
        }
    }

    public function refreshToken($RefreshToken)
    {
        $token = new \YLYTokenClient();

        $grantType      = 'refresh_token';       //自有模式或开放模式一致
        $scope          = 'all';                     //权限
        $timesTamp      = time();                //当前服务器时间戳(10位)
        $refreshToken   = $token->RefreshToken($grantType,$scope,$timesTamp,$RefreshToken);

        return $refreshToken;
    }


    /**
     * @param $accessToken'api访问令牌'
     * @param $pid
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function printDish($accessToken,$pid)
    {
        //获取菜品信息
        $dishPublicActionObj = new DishPublicAction();

        $orderInfo = $dishPublicActionObj->pidGetOrderDishInfo($pid);

        $tableName = $orderInfo['location_title']."-".$orderInfo['area_title']."-".$orderInfo['table_no']."号桌";

        $dishInfo  = $orderInfo['dish_info'];

        $api = new \YLYOpenApiClient();

        $content = "";                          //打印内容
        $content .= '<FS><center>'.$tableName.'</center></FS>';
        $content .= str_repeat('-',48);
        $content .= '<FS><table>';
        $content .= '<tr><td>商品</td><td>数量</td><td>价格</td></tr>';

        $allPrice = 0;
        for ($i = 0; $i <count($dishInfo); $i ++){
            $price = $dishInfo[$i]['quantity'] * $dishInfo[$i]['price'];
            $content .= '<tr><td>'.$dishInfo[$i]['dis_name'].'</td><td>x'.$dishInfo[$i]['quantity'].'</td><td>￥'.$price.'</td></tr>';
            if ($dishInfo[$i]['dis_type']){
                $children = $dishInfo[$i]['children'];
                for ($m = 0; $m < count($children); $m ++){
                    $content .= '<tr><td> </td><td>'.$children[$m]['dis_name'].'</td><td>'.$children[$m]['quantity'].'</td></tr>';
                }
            }

            $allPrice += $price;
        }

        $content .= '</table></FS>';
        $content .= str_repeat('-',48)."\n";
        $content .= '<FS>金额: '.$allPrice.'元</FS>';

        $machineCode = Env::get("YLY_MACHINE_CODE_1");  //授权的终端号
        $originId = '1234567890';     //商户自定义id
        $timesTamp = time();          //当前服务器时间戳(10位)

        $res = $api->printIndex($machineCode,$accessToken,$content,$originId,$timesTamp);

        $res = json_decode($res,true);

        return $res;
    }

    public function refundDish($accessToken,$params)
    {
        $api = new \YLYOpenApiClient();

        $tableName = $params['table_name'];

        $dishInfo = $params['dis_info'];

        $content  = "";                          //打印内容
        $content .= '<MS>0,0</MS>';
        $content .= '<FS2><center>退 单</center></FS2>';
        $content .= '<FS><center>'.$tableName.'</center></FS>';
        $content .= str_repeat('-',48);
        $content .= '<FS><table>';
        $content .= '<tr><td>商品</td><td>数量</td></tr>';
        $content .= str_repeat('-',48);

        for ($i = 0; $i <count($dishInfo); $i ++){
            $content .= '<tr><td>'.$dishInfo[$i]['dis_name'].'</td><td> x '.$dishInfo[$i]['quantity'].'</td></tr>';
            if ($dishInfo[$i]['dis_type']){
                $children = $dishInfo[$i]['children'];
                for ($m = 0; $m < count($children); $m ++){
                    $num = $m+1;
                    $content .= '<tr><td> --- '.$num.')'.$children[$m]['dis_name'].'</td><td> x '.$children[$m]['quantity'].'</td></tr>';
                }
            }

        }

        $content .= '</table></FS>';
        $content .= str_repeat('-',48)."\n";
        $content .= '打印时间'.date("Y-m-d H:i:s");

        $machineCode = Env::get("YLY_MACHINE_CODE_1");  //授权的终端号
        $originId = '1234567890';     //商户自定义id
        $timesTamp = time();          //当前服务器时间戳(10位)

        $res = $api->printIndex($machineCode,$accessToken,$content,$originId,$timesTamp);

        $res = json_decode($res,true);

        return $res;


    }
}