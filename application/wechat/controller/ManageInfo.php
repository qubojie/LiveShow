<?php
/**
 * Created by PhpStorm.
 * User: qubojie
 * Date: 2018/7/31
 * Time: 下午5:29
 */
namespace app\wechat\controller;

use app\admin\model\ManageSalesman;
use app\admin\model\MstTableImage;
use app\admin\model\TableRevenue;
use think\Config;
use think\Request;
use think\Validate;

class ManageInfo extends HomeAction
{
    /**
     * 服务人员变更密码
     * @param Request $request
     * @return array
     */
    public function changePass(Request $request)
    {
        $old_password = $request->param("old_password","");
        $password     = $request->param("password","");

        $remember_token = $request->header("Token","");

        $rule = [
            "old_password|旧密码" => "require|alphaNum|length:6,16",
            "password|新密码"     => "require|alphaNum|length:6,16",
        ];

        $request_res = [
            "old_password" => $old_password,
            "password"     => $password,
        ];

        $validate = new Validate($rule);

        if (!$validate->check($request_res)){
            return $this->com_return(false,$validate->getError(),null);
        }

        $old_password = sha1($old_password);

        $manageModel = new ManageSalesman();

        $is_true = $manageModel
            ->where("remember_token",$remember_token)
            ->where('password',$old_password)
            ->count();
        if(!$is_true){
            return $this->com_return(false,config("params.PASSWORD_PP"));
        }

        $new_token = $this->jm_token($password.time().$old_password);

        $params = [
            "password" => sha1($password),
            "remember_token" => $new_token,
            "updated_at" => time()
        ];

        $is_ok = $manageModel
            ->where('remember_token',$remember_token)
            ->update($params);

        if ($is_ok !== false){
            return $this->com_return(true,config("params.SUCCESS"),$new_token);
        }else{
            return $this->com_return(false,config("params.FAIL"));

        }
    }

    /**
     * 我的预约列表
     * @param Request $request
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function reservationOrder(Request $request)
    {
        $pagesize = $request->param("pagesize",config('PAGESIZE'));//显示个数,不传时为10

        $nowPage  = $request->param("nowPage","1");

        $token =  $request->header('Token','');

        $status = $request->param("status",'');//  0待付定金或结算   1 预定成功   2已开台  3已清台   9取消预约

        if (empty($status)){
            $status = 0;
        }

        $where_status['tr.status'] = ["eq",$status];

        $manageInfo  = $this->tokenGetManageInfo($token);

        $sid = $manageInfo['sid'];

        $tableRevenueModel = new TableRevenue();

        $config = [
            "page" => $nowPage,
        ];

        $reserve_way = Config::get("order.reserve_way")['service']['key'];//预定途径  client  客户预订（小程序）    service 服务端预定（内部小程序）   manage 管理端预定（pc管理端暂保留）


        $list = $tableRevenueModel
            ->alias("tr")
            ->join("mst_table_area ta","ta.area_id = tr.area_id")
            ->join("mst_table_location tl","ta.location_id = tl.location_id")
            ->join("manage_salesman ms","ms.sid = tr.ssid","LEFT")
            ->join("user u","u.uid = tr.uid")
            ->where('tr.ssid',$sid)
            ->where('reserve_way',$reserve_way)
            ->where($where_status)
            ->field("tr.trid,tr.table_id,tr.table_no,tr.status,tr.reserve_time,tr.subscription,tr.subscription_type,tr.reserve_way,tr.ssid,tr.ssname")
            ->field("u.name,u.nickname,u.phone as userPhone")
            ->field("ms.phone")
            ->field("tl.location_title")
            ->field("ta.area_title")
            ->paginate($pagesize,false,$config);

        $list = json_decode(json_encode($list),true);

        $data = $list["data"];

        $tableImageModel = new MstTableImage();

        for ($i = 0; $i <count($data); $i++){

            $table_id = $data[$i]['table_id'];

            $tableImage = $tableImageModel
                ->where('table_id',$table_id)
                ->field("image")
                ->select();

            $tableImage = json_decode(json_encode($tableImage),true);

            for ($m = 0; $m < count($tableImage); $m++){
                $list["data"][$i]['image_group'][] = $tableImage[$m]['image'];
            }
        }
        return $this->com_return(true,config("params.SUCCESS"),$list);
    }
}