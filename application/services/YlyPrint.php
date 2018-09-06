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

        $orderInfo = $dishPublicActionObj->pidGetOrderDishInfo2($pid);

//        dump($orderInfo);die;

//        $tableName = $orderInfo['location_title']."-".$orderInfo['area_title']."-".$orderInfo['table_no'];
        $tableName = $orderInfo['table_no'];

        $dishInfo  = $orderInfo['dish_info'];

        $nowTime = date("Y-m-d H:i");

        $api = new \YLYOpenApiClient();

        foreach ($dishInfo as $key => $val){

            $content = "";                          //打印内容
            $content .= '<MS>0,0</MS>';
            $content .= '<FS><center>'.$val['att_name'].'分单</center></FS>';
            $content .= '<FS2> 桌号: '.$tableName.'</FS2>'."\n";
            $content .= '单号:'.$pid.'   '.$nowTime.''."\n";
            $content .= '<FS>'.str_repeat('-',36)."</FS>\n";
            $content .= '<FS><table>';
            $content .= '<tr><td>商品</td><td>数量</td></tr>';
            $content .= '<tr><td>'.$val['dis_name'].'</td><td>'.$val['quantity'].'</td></tr>';
            $content .= '</table></FS>';
            $content .= '<FS>'.str_repeat('-',36)."</FS>\n";

            $machineCode = $val['printer_sn'];  //授权的终端号
            $originId    = '1234567890';     //商户自定义id
            $timesTamp   = time();          //当前服务器时间戳(10位)

            $print_num = $val['print_num'];

            for ($i = 0; $i < $print_num; $i ++){

                $res = $api->printIndex($machineCode,$accessToken,$content,$originId,$timesTamp);
                $res = json_decode($res,true);

                if ($res['error'] != "0"){
                    //落单失败
                    return $this->com_return(false,$res['error_description'],$pid);
                }

            }
        }
//        return $res;
        /*$content = "";                          //打印内容
        $content .= '<MS>0,0</MS>';
        $content .= '<FS><center>热菜分单</center></FS>';
        $content .= '<FS2> 桌号: '.$tableName.'</FS2>'."\n";
        $content .= '单号:'.$pid.'   '.$nowTime.''."\n";
        $content .= '<FS>'.str_repeat('-',36)."</FS>\n";
        $content .= '<FS><table>';
        $content .= '<tr><td>商品</td><td>数量</td></tr>';

        $allPrice = 0;
        for ($i = 0; $i <count($dishInfo); $i ++){
            $price = $dishInfo[$i]['quantity'] * $dishInfo[$i]['price'];

            if (!$dishInfo[$i]['dis_type']){
                $content .= '<tr><td>'.$dishInfo[$i]['dis_name'].'</td><td>'.$dishInfo[$i]['quantity'].'</td></tr>';
            }
            if ($dishInfo[$i]['dis_type']){
                $children = $dishInfo[$i]['children'];
                for ($m = 0; $m < count($children); $m ++){
                    $content .= '<tr><td>'.$children[$m]['dis_name'].'</td><td>'.$children[$m]['quantity'].'</td></tr>';
                }
            }

            $allPrice += $price;
        }

        $content .= '</table></FS>';
        $content .= '<FS>'.str_repeat('-',36)."</FS>\n";

        $machineCode = Env::get("YLY_MACHINE_CODE_1");  //授权的终端号
        $originId    = '1234567890';     //商户自定义id
        $timesTamp   = time();          //当前服务器时间戳(10位)

        $res = $api->printIndex($machineCode,$accessToken,$content,$originId,$timesTamp);

        $res = json_decode($res,true);

        return $res;*/
    }

    /**
     * 退单落单
     * @param $accessToken
     * @param $params
     * @return mixed
     */
    public function refundDish($accessToken,$params)
    {
        $api = new \YLYOpenApiClient();

        $tableName = $params['table_name'];

        $dishInfo  = $params['dis_info'];

        $pid       = $params['pid'];

        $nowTime   = date("Y-m-d H:i");

        foreach ($dishInfo as $key => $val){
            $quantity = $val['quantity'];
            if ($val['dis_type']){
                //如果是套餐
                $children = $val['children'];
                foreach ($children as $k => $v){
                    $print_num_children = $v['print_num'];
                    $printer_sn         = $v['printer_sn'];
                    $att_name_children  = $v['att_name'];
                    $dis_name_children  = $v['dis_name'];
                    $quantity_children  = $v['quantity'];
                    $total_quantity     = $quantity_children * $quantity;

                    $content  = "";                          //打印内容
                    $content .= '<MS>0,0</MS>';
                    $content .= '<FS2>退单</FS2><FS><center>'.$att_name_children.'退单</center></FS>';
                    $content .= '<FS2> 桌号: '.$tableName.'</FS2>'."\n";
                    $content .= '单号:'.$pid.'   '.$nowTime.''."\n";
                    $content .= '<FS>'.str_repeat('-',36)."</FS>\n";
                    $content .= '<FS><table>';
                    $content .= '<tr><td>商品</td><td>数量</td></tr>';
                    $content .= '<tr><td>(退)'.$dis_name_children.'</td><td>'.$total_quantity.'</td></tr>';
                    $content .= '</table></FS>';
                    $content .= '<FS>'.str_repeat('-',36)."</FS>\n";
                    $machineCode = $printer_sn;  //授权的终端号
                    $originId    = '1234567890';     //商户自定义id
                    $timesTamp   = time();          //当前服务器时间戳(10位)


                    for ($i = 0; $i < $print_num_children; $i ++){
                        $res = $api->printIndex($machineCode,$accessToken,$content,$originId,$timesTamp);

                        $res_error = isset($res['error']) ? $res['error']:0;
                        if ($res_error != "0"){
                            //落单失败
                            return $this->com_return(false,$res['error_description'],$pid);
                        }
                    }
                }

            }else{
                //如果是单品
                $att_name = $val['att_name'];
                $dis_name = $val['dis_name'];
                $print_num = $val['print_num'];
                $printer_sn = $val['printer_sn'];

                $content2  = "";                          //打印内容
                $content2 .= '<MS>0,0</MS>';
                $content2 .= '<FS2>退单</FS2><FS><center>'.$att_name.'退单</center></FS>';
                $content2 .= '<FS2> 桌号: '.$tableName.'</FS2>'."\n";
                $content2 .= '单号:'.$pid.'   '.$nowTime.''."\n";
                $content2 .= '<FS>'.str_repeat('-',36)."</FS>\n";
                $content2 .= '<FS><table>';
                $content2 .= '<tr><td>商品</td><td>数量</td></tr>';
                $content2 .= '<tr><td>(退)'.$dis_name.'</td><td>'.$quantity.'</td></tr>';
                $content2 .= '</table></FS>';
                $content2 .= '<FS>'.str_repeat('-',36)."</FS>\n";

                $machineCode = $printer_sn;  //授权的终端号
                $originId    = '1234567890';     //商户自定义id
                $timesTamp   = time();          //当前服务器时间戳(10位)

                for ($i = 0; $i < $print_num; $i ++){
                    $res = $api->printIndex($machineCode,$accessToken,$content2,$originId,$timesTamp);
                    $res_error = isset($res['error']) ? $res['error']:0;
                    if ($res_error != "0"){
                        //落单失败
                        return $this->com_return(false,$res['error_description'],$pid);
                    }

                }
            }
        }
    }
}