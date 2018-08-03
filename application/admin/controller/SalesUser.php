<?php
/**
 * 人员管理.
 * User: qubojie
 * Date: 2018/6/23
 * Time: 下午2:41
 */
namespace app\admin\controller;

use app\admin\model\ManageSalesman;
use app\common\controller\UUIDUntil;
use think\Db;
use think\Env;
use think\Exception;
use think\Request;
use think\Validate;

class SalesUser extends CommandAction
{
    /**
     * 人员状态分组
     * @return array
     */
    public function salesmanStatus()
    {
        $salesmanModel = new ManageSalesman();

        $statusList = config("salesman.salesman_status");

        $res = [];

        foreach ($statusList as $key => $val){
            if ($val["key"] == config("salesman.salesman_status")['pending']['key']){
                $count = $salesmanModel
                    ->where("statue",config("salesman.salesman_status")['pending']['key'])
                    ->count();//未付款总记录数
                $val["count"] = $count;
            }else{
                $val["count"] = 0;
            }
            $res[] = $val;
        }
        return $this->com_return(true,config("params.SUCCESS"),$res);
    }


    /**
     * 人员列表
     * @param Request $request
     * @return array
     * @throws \think\exception\DbException
     */
    public function index(Request $request)
    {
        $common = new Common();
        $manageSalesmanModel = new ManageSalesman();

        $admin_column = $manageSalesmanModel->admin_column;

        $pagesize = $request->param("pagesize",config('PAGESIZE'));//显示个数,不传时为10

        $nowPage  = $request->param("nowPage","1");

        $keyword  = $request->param("keyword","");

        $status   = $request->param('status',"");//营销人员状态  0入职待审核  1销售中  2停职  9离职

        $department_id = $request->param('department_id','');//部门Id

        $department_where = [];
        if (!empty($department_id)){
            $department_where['ms.department_id'] = ["eq","$department_id"];
        }

        if (empty($status)){
            $status = 0;
        }
        $status_where['ms.statue'] = ["eq","$status"];


        $where = [];
        if (!empty($keyword)){
            $where['ms.sid|ms.sales_name|ms.phone|ms.id_no|ms.province|ms.city|ms.country|md.department_title|mst.stype_name'] = ["like","%$keyword%"];
        }

        if (empty($pagesize)){
            $pagesize = config('PAGESIZE');
        }

        $count = $manageSalesmanModel->count();//总记录数

        $pageNum = ceil($count/$pagesize); //总页数

        $config = [
            "page" => $nowPage,
        ];

        $salesman_list = $manageSalesmanModel
            ->alias("ms")
            ->where($where)
            ->where($department_where)
            ->where($status_where)
            ->join("manage_department md","md.department_id = ms.department_id")
            ->join("mst_salesman_type mst","mst.stype_id = ms.stype_id")
            ->field($admin_column)
            ->field("md.department_title,md.department_manager")
            ->field("mst.stype_name,mst.stype_desc,mst.commission_ratio")
            ->paginate($pagesize,false,$config);

        $salesman_list = $salesman_list->toArray();


        for ($i=0;$i<count($salesman_list['data']);$i++){

            //处理身份中照片地址
            if (!empty($salesman_list['data'][$i]['id_img1'])){
                $salesman_list['data'][$i]['id_img1'] = Env::get("IMG_YM_PATH")."/".$salesman_list['data'][$i]['id_img1'];
                $salesman_list['data'][$i]['id_img2'] = Env::get("IMG_YM_PATH")."/".$salesman_list['data'][$i]['id_img2'];
            }

            //处理默认头像
            if (empty($salesman_list['data'][$i]['avatar'])){
                $salesman_list['data'][$i]['avatar'] = Env::get("DEFAULT_AVATAR_URL")."avatar.jpg";
            }

            //更改营销人员状态
            if ($salesman_list['data'][$i]['statue'] == config("salesman.salesman_status")['pending']['key']){
                $salesman_list['data'][$i]['statue_name'] = config("salesman.salesman_status")['pending']['name'];
            }
            if ($salesman_list['data'][$i]['statue'] == config("salesman.salesman_status")['working']['key']){
                $salesman_list['data'][$i]['statue_name'] = config("salesman.salesman_status")['working']['name'];
            }
            if ($salesman_list['data'][$i]['statue'] == config("salesman.salesman_status")['suspended']['key']){
                $salesman_list['data'][$i]['statue_name'] = config("salesman.salesman_status")['suspended']['name'];
            }
            if ($salesman_list['data'][$i]['statue'] == config("salesman.salesman_status")['resignation']['key']){
                $salesman_list['data'][$i]['statue_name'] = config("salesman.salesman_status")['resignation']['name'];
            }

        }

        return $common->com_return(true,config("GET_SUCCESS"),$salesman_list);

    }

    /**
     * 人员添加
     * @param Request $request
     * @return array
     */
    public function add(Request $request)
    {
        $common = new Common();
        $manageSalesmanModel = new ManageSalesman();

        $params = $request->param();

        $department_id  = $request->param("department_id","");//部门id
        $stype_id       = $request->param("stype_id","");//销售员类型ID
        $is_governor    = $request->param("is_governor",0);//是否业务主管
        $sales_name     = $request->param("sales_name","");//姓名
        $statue         = $request->param("statue","1");//销售员状态 0入职待审核  1销售中  2停职  9离职
//        $id_no          = $request->param("id_no","");//身份证号
//        $id_img1        = $request->param("id_img1","");//身份证正面照片
//        $id_img2        = $request->param("id_img2","");//身份证反面照片
        $phone          = $request->param("phone","");//电话号码 必须唯一 未来可用于登录 实名验证等使用
        $password       = $request->param("password","000000");//密码
        $password_confirmation       = $request->param("password_confirmation","000000");//确认密码
        $wxid           = $request->param("wxid","");//微信id(openId/unionId)
        $nickname       = $request->param("nickname","");//昵称
        $avatar         = $request->param("avatar","");//头像
        $sex            = $request->param("sex","女士");//性别
        $province       = $request->param("province","");//省份
        $city           = $request->param("city","");//城市
        $country        = $request->param("country","中国");//国家
//        $entry_time     = $request->param("entry_time",time());//入职时间
        $dimission_time = $request->param("dimission_time","");//离职或停职时间
        $lastlogin_time = $request->param("lastlogin_time","");//最后登录时间
        $sell_num       = $request->param("sell_num","0");//销售数量（定时统计）
        $sell_amount    = $request->param("sell_amount","0");//销售总金额 （定时统计）


        if (empty($password)){
            $password = $password_confirmation = "000000";
        }
        if (empty($sex)) {
            $sex = "1";
        }

        if ($password !== $password_confirmation)
        {
            return $common->com_return(false,config("PASSWORD_DIF"));
        }

        $rule = [
            "department_id|部门"          => "require",
            "stype_id|销售员类型"          => "require",
            "is_governor|是否负责人"       => "require",
            "sales_name|姓名"             => "require",
            "statue|销售员状态"            => "require",
//            "id_no|身份证号"             => "require|unique:manage_salesman",
//            "id_img1|身份证正面照片"      => "require",
//            "id_img2|身份证反面照片"      => "require",
            "phone|电话号码"               => "require|number|regex:1[3-8]{1}[0-9]{9}|unique:manage_salesman",
//            "entry_time|入职时间"        => "require",
        ];

        $request_res = [
            "department_id"         => $department_id,
            "stype_id"              => $stype_id,
            "is_governor"           => $is_governor,
            "sales_name"            => $sales_name,
            "statue"                => $statue,
//            "id_no"                 => $id_no,
//            "id_img1"               => $id_img1,
//            "id_img2"               => $id_img2,
            "phone"                 => $phone,
//            "entry_time"            => $entry_time,
        ];

        $validate = new Validate($rule);

        if (!$validate->check($request_res)){
            return $common->com_return(false,$validate->getError(),null);
        }

        $UUIDUntil = new UUIDUntil();

        $sid  = $UUIDUntil->generateReadableUUID("S");

        $time = time();

        $insert_data = [
            "sid"                   => $sid,
            "department_id"         => $department_id,
            "stype_id"              => $stype_id,
            "is_governor"           => $is_governor,
            "sales_name"            => $sales_name,
            "statue"                => $statue,
//            "id_no"                 => $id_no,
//            "id_img1"               => $id_img1,
//            "id_img2"               => $id_img2,
            "phone"                 => $phone,
            "password"              => sha1($password),
            "wxid"                  => $wxid,
            "nickname"              => $nickname,
            "avatar"                => $avatar,
            "sex"                   => $sex,
            "province"              => $province,
            "city"                  => $city,
            "country"               => $country,
//            "entry_time"            => $entry_time,
            "dimission_time"        => $dimission_time,
            "lastlogin_time"        => $lastlogin_time,
            "sell_num"              => $sell_num,
            "sell_amount"           => $sell_amount,
            "created_at"            => $time,
            "updated_at"            => $time,
        ];

        Db::startTrans();
        try{

            $is_ok = $manageSalesmanModel
                ->insert($insert_data);

            if ($is_ok){
                Db::commit();

                //移动身份证照片至指定地点
//                $id_img1 = $this->moveImage($id_img1,$sid,"idcard_1");
//                $id_img2 = $this->moveImage($id_img2,$sid,"idcard_2");

                /*$img_params = [
                    'id_img1' => $id_img1,
                    'id_img2' => $id_img2
                ];*/

                /*$manageSalesmanModel
                    ->where('sid',$sid)
                    ->update($img_params);*/

                return $common->com_return(true, config("ADD_SUCCESS"));

            }else{
                return $common->com_return(false, config("ADD_FAIL"));
            }
        }catch (Exception $e){
            Db::rollback();
            return $common->com_return(false,$e->getMessage(),null);
        }
    }

    /**
     * 人员编辑
     * @param Request $request
     * @return array
     */
    public function edit(Request $request)
    {
        $common = new Common();
        $manageSalesmanModel = new ManageSalesman();

        $sid = $request->param("sid",""); //销售人员id

        if (empty($sid)) {
            return $common->com_return(false,config("PARAM_NOT_EMPTY"));
        }

        $department_id  = $request->param("department_id","");//部门id
        $stype_id       = $request->param("stype_id","");//销售员类型ID
        $is_governor    = $request->param("is_governor","");//是否业务主管 0否,1是
        $sales_name     = $request->param("sales_name","");//姓名
        $phone          = $request->param("phone","");//电话号码 必须唯一 未来可用于登录 实名验证等使用



        $rule = [
            "department_id|部门"          => "require",
            "stype_id|销售员类型"          => "require",
            "is_governor|是否是负责人"          => "require",
            "sales_name|姓名"             => "require",
//            "id_no|身份证号"             => "require|unique:manage_salesman",
//            "id_img1|身份证正面照片"      => "require",
//            "id_img2|身份证反面照片"      => "require",
            "phone|电话号码"               => "require|regex:1[3-8]{1}[0-9]{9}|number",
//            "entry_time|入职时间"        => "require",
        ];

        $request_res = [
            "department_id"         => $department_id,
            "stype_id"             => $stype_id,
            "is_governor"             => $is_governor,
            "sales_name"            => $sales_name,
//            "id_no"                 => $id_no,
//            "id_img1"               => $id_img1,
//            "id_img2"               => $id_img2,
            "phone"                 => $phone,
//            "entry_time"            => $entry_time,
        ];

        $validate = new Validate($rule);

        if (!$validate->check($request_res)){
            return $common->com_return(false,$validate->getError(),null);
        }

        //判断电话号码是否存在
        $is_exist = $manageSalesmanModel
            ->where('sid','neq',$sid)
            ->where('phone',$phone)
            ->count();

        if ($is_exist > 0){
            return $this->com_return(false,config("params.PHONE_EXIST"));
        }

        $param = $request->param();

        $time = time();

        if (isset($param["password"]) && isset($param["password_confirmation"])) {

            if ($param["password"] !== $param["password_confirmation"]) {
                return $common->com_return(false,config("PASSWORD_DIF"));
            }

            $param = $common->bykey_reitem($param,'password_confirmation');
        }

        $param["updated_at"] = $time;

        Db::startTrans();
        try{
            $is_ok = $manageSalesmanModel
                ->where("sid",$sid)
                ->update($param);

            if ($is_ok !== false){
                Db::commit();
                return $common->com_return(true, config("EDIT_SUCCESS"));
            }else{
                return $common->com_return(false, config("EDIT_FAIL"));
            }
        }catch (Exception $e){
            Db::rollback();
            return $common->com_return(false,$e->getMessage(),null);
        }
    }

    /**
     * 人员状态操作 销售员状态 0入职待审核  1销售中  2停职  9离职
     * @param Request $request
     * @return array
     */
    public function changeStatus(Request $request)
    {
        $common = new Common();
        $manageSalesmanModel = new ManageSalesman();

        $sids   = $request->param("sid","");
        $status = $request->param("status","");
        $reason = $request->param("reason","");//操作原因

        if ($status == config("salesman.salesman_status")['working']['key']){
            $action = config("useraction.verify_passed")['name'];
        }elseif ($status == config("salesman.salesman_status")['suspended']['key']){
            $action = config("useraction.suspended")['name'];
        }elseif ($status == config("salesman.salesman_status")['resignation']['key']){
            $action = config("useraction.resignation")['name'];
        }else{
            $action = "empty";
        }

        if (!empty($sids)) {

            $id_array = explode(",",$sids);

            $time = time();
            $params = [
                'statue'     => $status,
                'updated_at' => $time
            ];

            Db::startTrans();
            try{
                $is_ok =false;
                foreach ($id_array as $sid){
                    $is_ok = $manageSalesmanModel
                        ->where("sid",$sid)
                        ->update($params);

                    //获取当前登录管理员
                    $action_user = $this->getLoginAdminId($request->header('Authorization'))['user_name'];

                    //记录操作日志
                    $this->addSysAdminLog("$sid","","","$action","$reason","$action_user","$time");

                }
                if ($is_ok !== false){
                    Db::commit();
                    return $common->com_return(true, config("SUCCESS"));
                }else{
                    return $common->com_return(false, config("FAIL"));
                }

            }catch (Exception $e){
                Db::rollback();
                return $common->com_return(false,$e->getMessage(),null);
            }
        }else{
            return $common->com_return(false, config("PARAM_NOT_EMPTY"));
        }
    }

    /**
     * 移动指定文件目录的文件至新的文件夹下并重命名
     * @param $pic_src
     * @param $sid
     * @param $name
     * @return bool|string 返回新的完整路径
     */
    protected function moveImage($pic_src,$sid,$name)
    {
        $pic_name_arr = explode("/",$pic_src);
        $pic_name     = end($pic_name_arr);
        $pic_ext_arr  = explode(".",$pic_name);
        $pic_ext      = $pic_ext_arr[1];
        $move_path    = Env::get("IMG_FILE_PATH").$sid."/";
        //移动文件
        if (!file_exists($move_path)){
            $ret =  @mkdir($move_path,0777,true);
        }else{
            $ret = true;
        }
        if ($ret){
            $is_ok =  copy($pic_src,$move_path.$name.".".$pic_ext);
            if ($is_ok){
                $finish_path = $sid."/".$name.".".$pic_ext;
                @unlink($pic_src);
                return $finish_path;
            }else{
                return false;
            }
        }else{
            return false;
        }

    }

    /**
     * 根据sid获取销售人员信息
     * @param $sid
     * @return array|false|\PDOStatement|string|\think\Model
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getSalesManInfo($sid)
    {
        $salesModel = new ManageSalesman();
        $admin_column = $salesModel->admin_column;
        $salesmanInfo = $salesModel
            ->alias("ms")
            ->where('sid',$sid)
            ->field($admin_column)
            ->find();
        return $salesmanInfo;
    }

    /**
     * 获取服务人员负责人列表或者非负责人列表
     * @param $is_governor
     * @return array|false|mixed|\PDOStatement|string|\think\Model
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getGovernorSalesman($is_governor)
    {
        $salesModel = new ManageSalesman();
        $admin_column = $salesModel->admin_column;
        $where['ms.is_governor'] = ['eq',"$is_governor"];

        $salesmanList = $salesModel
            ->alias("ms")
            ->join("mst_salesman_type mst","mst.stype_id = ms.stype_id")
            ->where($where)
            ->where('mst.stype_key',config("salesman.salesman_type")['5']['key'])
            ->field($admin_column)
            ->select();

        $salesmanList = json_decode(json_encode($salesmanList),true);

        return $salesmanList;

    }
}