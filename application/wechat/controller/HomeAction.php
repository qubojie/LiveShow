<?php
/**
 * Created by PhpStorm.
 * User: qubojie
 * Date: 2018/7/30
 * Time: 下午3:38
 */
namespace app\wechat\controller;

use app\admin\model\TableRevenue;
use think\Controller;
use think\exception\HttpException;
use think\Log;

class HomeAction extends Controller
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
                    $over_time = $token_lastime + 604800;   //过期时间

                    if ($time < $over_time){

                    }else{
                        throw new HttpException(403,'登陆失效1');
                    }
                }else{
                    throw new HttpException(403,'登陆失效2');
                }

            }else{
                throw new HttpException(403,'登陆失效3');
            }
        }
    }


    /**
     * 根据token获取服务人员信息
     * @param $token
     * @return array|false|PDOStatement|string|\think\Model
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function tokenGetManageInfo($token)
    {
        $manageSalesmanModel = new \app\admin\model\ManageSalesman();

        $manage_column = $manageSalesmanModel->manage_column;

        $manageInfo = $manageSalesmanModel
            ->alias("ms")
            ->join("manage_department md","md.department_id = ms.department_id")
            ->join("mst_salesman_type st","st.stype_id = ms.stype_id")
            ->where('ms.remember_token',$token)
            ->field("md.department_title")
            ->field("st.stype_key,st.stype_name")
            ->field($manage_column)
            ->find();

        $manageInfo = json_decode(json_encode($manageInfo),true);

        return $manageInfo;
    }

    /**
     * 变更台位状态操作
     * @param $trid
     * @return array
     */
    protected function openTableAction($trid)
    {
        $tableRevenueModel = new TableRevenue();

        $params = [
            "status"     => config("order.table_reserve_status")['already_open']['key'],
            "updated_at" => time()
        ];

        $is_ok = $tableRevenueModel
            ->where('trid',$trid)
            ->update($params);

        if ($is_ok !== false){
            return $this->com_return(true,config("SUCCESS"));
        }else{
            return $this->com_return(false,config("FAIL"));
        }
    }
}