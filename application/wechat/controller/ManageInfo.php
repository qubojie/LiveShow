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
use app\admin\model\User;
use app\common\controller\SortName;
use app\wechat\model\BcUserInfo;
use think\Config;
use think\Db;
use think\Exception;
use think\Request;
use think\Validate;

class ManageInfo extends HomeAction
{
    /**
     * 服务人员变更密码
     * @param Request $request
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
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

        /*权限判断 on*/
        $manageInfo = $this->tokenGetManageInfo($remember_token);
        $statue     = $manageInfo['statue'];

        if ($statue != \config("salesman.salesman_status")['working']['key']){
            return $this->com_return(false,\config("params.MANAGE_INFO")['UsrLMT']);
        }
        /*权限判断 off*/

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

        $token    =  $request->header('Token','');

        $status   = $request->param("status",'');//  0待付定金或结算   1 预定成功   2已开台  3已清台   9取消预约

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

//        $reserve_way = Config::get("order.reserve_way")['service']['key'];//预定途径  client  客户预订（小程序）    service 服务端预定（内部小程序）   manage 管理端预定（pc管理端暂保留）

        $list = $tableRevenueModel
            ->alias("tr")
            ->join("mst_table t","t.table_id = tr.table_id")
            ->join("mst_table_appearance tap","tap.appearance_id = t.appearance_id")
            ->join("mst_table_area ta","ta.area_id = tr.area_id")
            ->join("mst_table_location tl","ta.location_id = tl.location_id")
            ->join("manage_salesman ms","ms.sid = tr.ssid","LEFT")
            ->join("user u","u.uid = tr.uid")
            ->where('tr.ssid',$sid)
//            ->where('reserve_way',$reserve_way)
            ->where($where_status)
            ->field("tr.trid,tr.table_id,tr.table_no,tr.status,tr.reserve_time,tr.subscription,tr.subscription_type,tr.reserve_way,tr.ssid,tr.ssname")
            ->field("u.name,u.nickname,u.phone as userPhone")
            ->field("ms.phone")
            ->field("tl.location_title")
            ->field("ta.area_title")
            ->field("tap.appearance_title")
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

    /**
     * 我的客户列表
     * @param Request $request
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function customerList(Request $request)
    {
        $token = $request->header("Token","");

        $manageInfo  = $this->tokenGetManageInfo($token);

        $sid = $manageInfo['sid'];

        $sortNameObj = new SortName();

        $userModel = new User();

        $user_num = $userModel
            ->where("referrer_id",$sid)
            ->count();

        $user_list = $userModel
            ->alias("u")
            ->where("referrer_id",$sid)
            ->field("u.uid,u.phone,u.email,u.name,u.nickname,u.avatar,u.sex,u.city,u.province,u.register_way,u.lastlogin_time,u.user_status")
            ->select();

        $user_list = json_decode(json_encode($user_list),true);

        $user_name = [];

        for ($i = 0; $i <count($user_list); $i++){
            $re_name     = $user_list[$i]['name'];

            $nickname =  $user_list[$i]['nickname'];

            $phone =  $user_list[$i]['phone'];

            if (!empty($re_name)){
                $user_list[$i]['look_name'] = $re_name;
            }else{
                if (!empty($nickname)){
                    $user_list[$i]['look_name'] = $nickname;
                }else{
                    $user_list[$i]['look_name'] = $phone;
                }
            }
        }

        $charArray=array();
        foreach ($user_list as $name ){

            $char = $sortNameObj->getFirstChar($name['look_name']);

            $nameArray = array();

            if(isset($charArray[$char])){

                $nameArray = $charArray[$char];

            }

            array_push($nameArray,$name);
            $charArray[$char] = $nameArray;

        }

        ksort($charArray);


        $charArray["userNumCount"] = $user_num;

        return $this->com_return(true,'成功',$charArray);
    }

    /**
     * 编辑客户信息
     * @param Request $request
     * @return array
     */
    public function customerEdit(Request $request)
    {
        $uid       = $request->param("uid","");//用户id
        $phone     = $request->param("phone","");//电话
        $name      = $request->param("name","");//用户姓名
        $sex       = $request->param("sex","");//性别
        $wx_number = $request->param("wx_number","");//微信号
        $email     = $request->param("email","");//邮箱

        $car_no    = $request->param("car_no","");//车牌号

        $remarks   = $request->param("remarks","");//备注

        $rule = [
            "uid|用户id"  => "require",
            "phone|电话"  => "require",
            "name|姓名"   => "require",
            "sex|性别"    => "require",
        ];

        $check_data = [
            "uid"   => $uid,
            "phone" => $phone,
            "name"  => $name,
            "sex"   => $sex,
        ];

        $validate = new Validate($rule);

        if (!$validate->check($check_data)){
            return $this->com_return(false,$validate->getError());
        }

        $userModel = new User();
        $userInfoModel = new BcUserInfo();

        $updateUserParams = [
            "phone"     => $phone,
            "name"      => $name,
            "sex"       => $sex,
            "wx_number" => $wx_number,
            "email"     => $email,
            "updated_at"=> time()
        ];

        $updateUserInfoParams = [
            "car_no"    => $car_no,
            "updated_at"=> time()
        ];

        Db::startTrans();
        try{
            $updateUserReturn = $userModel
                ->where('uid',$uid)
                ->update($updateUserParams);

            if (!empty($car_no)){

                $updateUserInfoReturn = $userInfoModel
                    ->where("uid",$uid)
                    ->update($updateUserInfoParams);
            }else{
                $updateUserInfoReturn = true;
            }

            if ($updateUserReturn !== false && $updateUserInfoReturn !== false){

                return $this->com_return(true,\config("params.SUCCESS"));

            }else{
                return $this->com_return(false,\config("params.FAIL"));
            }

        }catch (Exception $e){
            Db::rollback();
            return $this->com_return(false,$e->getMessage());
        }
    }
}