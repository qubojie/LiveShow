<?php
namespace app\reception\controller;

use app\admin\model\ManageSalesman;
use app\admin\model\SysAdminUser;
use app\admin\model\TableRevenue;
use app\admin\model\User;
use think\Controller;
use think\exception\HttpException;
use think\Request;

class CommonAction extends Controller
{
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
}