<?php
/**
 * Created by PhpStorm.
 * User: qubojie
 * Date: 2018/8/16
 * Time: 上午11:40
 */
namespace app\services;

use think\Controller;
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

        $token = new \YLYTokenClient();

        //获取token;
        $grantType = 'client_credentials';  //自有模式(client_credentials) || 开放模式(authorization_code)
        $scope = 'all';                     //权限
        $timesTamp = time();                //当前服务器时间戳(10位)
//$code = '';                       //开放模式(商户code)
        $getToken = $token->GetToken($grantType,$scope,$timesTamp);

        $getToken = json_decode(json_encode($getToken),true);

        return $getToken;

/*//刷新token;
        $grantType = 'refresh_token';       //自有模式或开放模式一致
        $scope = 'all';                     //权限
        $timesTamp = time();                //当前服务器时间戳(10位)
        $RefreshToken = '';                 //刷新token的密钥
        $refreshToken = $token->RefreshToken($grantType,$scope,$timesTamp,$RefreshToken);

        dump($refreshToken);die;*/
    }


    /**
     * @param $accessToken'api访问令牌'
     * @return mixed
     */
    public function printDish($accessToken)
    {
        $api = new \YLYOpenApiClient();

        $content = '';                          //打印内容
        $content .= '<FS><center>8号桌</center></FS>';
        $content .= str_repeat('-',48);
        $content .= '<FS><table>';
        $content .= '<tr><td>商品</td><td>数量</td><td>价格</td></tr>';
        $content .= '<tr><td>土豆回锅肉</td><td>x1</td><td>￥20</td></tr>';
        $content .= '<tr><td>干煸四季豆</td><td>x1</td><td>￥12</td></tr>';
        $content .= '<tr><td>苦瓜炒蛋</td><td>x1</td><td>￥15</td></tr>';
        $content .= '</table></FS>';
        $content .= str_repeat('-',48)."\n";
        $content .= '<FS>金额: 47元</FS>';

        $machineCode = '4004566461';                      //授权的终端号
        $originId = '1234567890';                         //商户自定义id
        $timesTamp = time();                    //当前服务器时间戳(10位)

        $res = $api->printIndex($machineCode,$accessToken,$content,$originId,$timesTamp);


        $res = json_decode(json_encode($res),true);

        return $res;
    }
}