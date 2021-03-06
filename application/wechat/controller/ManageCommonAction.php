<?php
/**
 * Created by PhpStorm.
 * User: qubojie
 * Date: 2018/7/30
 * Time: 下午3:38
 */
class ManageCommonAction extends \think\Controller
{
    /**
     * 初始化方法,其余控制器继承此方法，进行判断登录
     * @throws HttpException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function _initialize()
    {
        parent::_initialize();
        $method = \think\Request::instance()->method();

        if ( $method != "OPTIONS"){

            $Token = \think\Request::instance()->header("Token","");

            if (!empty($Token)){

                $salesmanModel = new \app\admin\model\ManageSalesman();
                $is_exist = $salesmanModel
                    ->where("remember_token",$Token)
                    ->field('token_lastime')
                    ->find();

                if (!empty($is_exist)){
                    $time = time();//当前时间
                    $token_lastime = $is_exist['token_lastime'];//上次刷新token时间

                    $over_time = $token_lastime + 6000;   //过期时间
                    if ($time < $over_time){

                    }else{
                        throw new HttpException(403,'登陆失效');
                    }
                }else{
                    throw new HttpException(403,'登陆失效');
                }

            }else{
                throw new HttpException(403,'登陆失效');
            }
        }
    }
}