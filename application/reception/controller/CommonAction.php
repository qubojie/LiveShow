<?php
namespace app\reception\controller;

use app\admin\model\ManageSalesman;
use app\admin\model\SysAdminUser;
use app\admin\model\TableRevenue;
use app\admin\model\User;
use think\Controller;
use think\Db;
use think\exception\HttpException;
use think\Request;

class CommonAction extends Controller
{
    /**
     * 初始化方法,其余控制器继承此方法，进行判断登录
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function _initialize()
    {
        parent::_initialize();
        $method = Request::instance()->method();

        if ( $method != "OPTIONS"){

            $Token = Request::instance()->header("Token","");

            if (!empty($Token)){

                $manageSalesModel = new ManageSalesman();

                $reserve = config("salesman.salesman_type")[6]['key'];
                $cashier = config("salesman.salesman_type")[7]['key'];

                $stype_key_str = "$reserve,$cashier";

                $is_exist = $manageSalesModel
                    ->alias("ms")
                    ->join("mst_salesman_type mst","mst.stype_id = ms.stype_id")
                    ->where("ms.reception_token",$Token)
                    ->where('mst.stype_key','IN',$stype_key_str)
                    ->field('ms.reception_token_lastime')
                    ->find();

                if ($is_exist){
                    $time = time();//当前时间
                    $reception_token_lastime = $is_exist['reception_token_lastime'];//上次刷新token时间

                    $over_time = $reception_token_lastime + 24 * 60 * 60;   //过期时间
                    if ($time < $over_time){

                    }else{
                        abort(403,"登陆失效");
                    }

                }else{
                    throw new HttpException(403,'登陆失效');
                }

            }else{
                throw new HttpException(403,'登陆失效');
            }
        }
    }



    /**
     * 根据电话号码获取用户信息
     * @param $phone
     * @return array|false|mixed|\PDOStatement|string|\think\Model
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function userPhoneGetInfo($phone)
    {
        $userModel = new User();

        $column = $userModel->column;

        $userInfo = $userModel
            ->where("phone",$phone)
            ->field($column)->find();
        $userInfo = json_decode(json_encode($userInfo),true);

        return $userInfo;
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

        $str1 = config("salesman.salesman_type")['0']['key'];
        $str2 = config("salesman.salesman_type")['1']['key'];
        $str3 = config("salesman.salesman_type")['3']['key'];
        $str4 = config("salesman.salesman_type")['4']['key'];

        $stype_key_str = $str1.",".$str2.",".$str3.",".$str4;

        $salesmanInfo = $salesModel
            ->alias("sm")
            ->join("mst_salesman_type mst","mst.stype_id = sm.stype_id")
            ->where("sm.phone",$phone)
            ->where("mst.stype_key","IN",$stype_key_str)
            ->field("mst.stype_name,mst.stype_key")
            ->field("sm.sid,sm.department_id,sm.stype_id,sm.sales_name,sm.statue,phone,sm.nickname,sm.avatar,sm.sex")
            ->find();

        $salesmanInfo = json_decode(json_encode($salesmanInfo),true);

        return $salesmanInfo;

    }

    /**
     * 更新预约桌台信息
     * @param array $params
     * @param $trid
     * @return bool
     */
    public function updateTableRevenueInfo($params = array(),$trid)
    {
        $tableRevenueModel = new TableRevenue();

        $is_ok = $tableRevenueModel
            ->where("trid",$trid)
            ->update($params);
        if ($is_ok !== false){
            return true;
        }else{
            return false;
        }

    }

    /**
     * 根据服务人员token获取服务人员信息
     * @param $token
     * @return array|false|PDOStatement|string|\think\Model
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function tokenGetManageInfo($token)
    {
        $manageSalesmanModel = new ManageSalesman();

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
     * 根据前台登陆token获取服务人员信息
     * @param $reception_token
     * @return array|false|mixed|\PDOStatement|string|\think\Model
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function receptionTokenGetManageInfo($reception_token)
    {
        $manageSalesmanModel = new ManageSalesman();

        $manage_column = $manageSalesmanModel->manage_column;

        $manageInfo = $manageSalesmanModel
            ->alias("ms")
            ->join("manage_department md","md.department_id = ms.department_id")
            ->join("mst_salesman_type st","st.stype_id = ms.stype_id")
            ->where('ms.reception_token',$reception_token)
            ->field("md.department_title")
            ->field("st.stype_key,st.stype_name")
            ->field($manage_column)
            ->find();

        $manageInfo = json_decode(json_encode($manageInfo),true);

        return $manageInfo;
    }

    /**
     * 记录禁止登陆解禁登陆 单据操作等日志
     * @param string $uid           '被操作用户id'
     * @param string $gid           '被操作的的商品id'
     * @param string $oid           '相关单据id'
     * @param string $action        '操作内容'
     * @param string $reason        '操作原因描述'
     * @param string $action_user   '操作管理员id'
     * @param string $action_time   '操作时间'
     */
    public function addSysAdminLog($uid = '',$gid = '',$oid = '',$action = 'empty',$reason = '',$action_user = '',$action_time = '')
    {
        $params  = [
            'uid'         => $uid,
            'gid'         => $gid,
            'oid'         => $oid,
            'action'      => $action,
            'reason'      => $reason,
            'action_user' => $action_user,
            'action_time' => $action_time,
        ];

        Db::name('sys_adminaction_log')
            ->insert($params);
    }
}