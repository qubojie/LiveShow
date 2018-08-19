<?php
/**
 * Created by PhpStorm.
 * User: qubojie
 * Date: 2018/6/19
 * Time: 下午6:34
 */
namespace app\admin\controller;

use app\admin\model\SysAdminUser;
use app\admin\model\SysRole;
use think\Controller;
use think\Db;
use think\Exception;
use think\Request;
use think\Validate;

class Admin extends CommandAction
{
    /**
     * 管理员列表
     * @param Request $request
     * @return array
     */
    public function index(Request $request)
    {

        $common     = new Common();

        $pagesize   = $request->param("pagesize",config('PAGESIZE'));

        $result     = array();

        $admin_user = new SysAdminUser();

        $orderBy    = $request->param("orderBy","");

        $sort       = $request->param("sort","asc");

        if (!empty($orderBy)){
            $result['filter']['orderBy'] = $orderBy;
        }else{
            $result['filter']['orderBy'] = "id";
        }

        if (!empty($sort)){
            $result['filter']['sort'] = $sort;
        }else{
            $result['filter']['sort'] = "asc";
        }


        $query = $admin_user
            ->where('is_delete','0')
            ->order($result['filter']['orderBy'],$result['filter']['sort']);

        $count   = $query -> count();//统计记录数

        $pageNum = ceil($count/$pagesize); //总页数

        $nowPage = $request->has("nowPage") ? $request->param("nowPage") : "1"; //当前页


        if ($request->has("user_name")){
            if (!empty($request->param("user_name"))){
                $query = $query->where("sdu.user_name","like","%".$request->param("user_name")."%");
            }
        }

        if ($request->has("role_id")){
            if (!empty($request->param("role_id"))){
                $query = $query->where("sdu.role_id",$request->param("role_id"));
            }
        }
        if ($nowPage <= $pageNum){

            $config = [
                "page" => $nowPage
            ];
            $result = $query
                ->alias("sdu")
                ->join("sys_role sr","sr.role_id = sdu.role_id")
                ->where('is_delete','0')
                ->order($result['filter']['orderBy'],$result['filter']['sort'])
                ->field("sdu.id,sdu.user_sn,sdu.user_name,sdu.ec_salt,sdu.avatar,sdu.phone,sdu.email,sdu.last_ip,sdu.action_list,sdu.nav_list,sdu.lang_type,sdu.role_id,sdu.is_delete,sdu.is_sys,sdu.created_at,sdu.updated_at")
                ->field("sr.role_id,sr.role_name,sr.role_describe")
                ->paginate($pagesize,'',$config);

            if ($count > 0){
                return $this->com_return(true,config("params.SUCCESS"),$result);

            }else{
                return $this->com_return(false,config("params.FAIL"));
            }
        }else{
            return $this->com_return(true,config("params.NOT_HAVE_MORE"),$result);
        }


    }

    /**
     * 登陆管理员详细
     * @return array
     */
    public function detail()
    {
        $common = new Common();
        $Authorization = $this->request->header("Authorization","");

        $sysAdminUserModel = new SysAdminUser();

        $admin_info =  $sysAdminUserModel
            ->where("token",$Authorization)
            ->field("id,user_sn,user_name,ec_salt,avatar,phone,email,last_ip,action_list,nav_list,lang_type,role_id,is_delete,is_sys,created_at,updated_at")
            ->find();
        if (!empty($admin_info)){
            return $common->com_return(true,"获取成功",$admin_info);
        }else{
            return $common->com_return(false,"获取失败");
        }
    }

    /**
     * 添加管理员
     * @return array
     */
    public function create()
    {
        $common = new Common();

        $user_name              = $this->request->param("user_name","");            //用户名
        $phone                  = $this->request->param("phone","");                //电话号码
        $email                  = $this->request->param("email","");                //邮箱
        $password               = $this->request->param("password","");             //密码
        $password_confirmation  = $this->request->param("password_confirmation",""); //确认密码
        $role_id                = $this->request->param("role_id","");              //角色id
        $user_sn                = $this->request->param("user_sn","");              //工号


        $rule = [
            "user_name|账号"               => "require|unique:sys_admin_user",
            "password|密码"                => "require",
            "password_confirmation|确认密码"=> "require",
            "role_id|角色分配"              => "require",
            "user_sn|工号"                 => "unique:sys_admin_user",
            "phone|电话"                   => "regex:1[3-8]{1}[0-9]{9}|unique:sys_admin_user",
            "email|邮箱"                   => "email|unique:sys_admin_user",
        ];

        $request_res = [
            "user_name"              => $user_name,
            "phone"                  => $phone,
            "email"                  => $email,
            "password"               => $password,
            "password_confirmation"  => $password_confirmation,
            "role_id"                => $role_id,
            "user_sn"                => $user_sn,
        ];

        $validate = new Validate($rule);

        if (!$validate->check($request_res)){
            return $this->com_return(false,$validate->getError(),null);
        }

        if ($password !== $password_confirmation){
            return $this->com_return(false,config("params.PASSWORD_DIF"),null);
        }

        $sysRoleModel = new SysRole();
        $action_list_res =  $sysRoleModel
            ->where('role_id',$role_id)
            ->field('action_list')
            ->find();
        $action_list_res = json_decode($action_list_res,true);
        $action_list = $action_list_res['action_list'];



        $sysModel = new SysAdminUser();

        Db::startTrans();
        try{
            $time = time();

            $insert_data = [
                "user_name"              => $user_name,
                "phone"                  => $phone,
                "email"                  => $email,
                "password"               => sha1($password),
                "role_id"                => $role_id,
                "user_sn"                => $user_sn,
                "action_list"            => $action_list,
                "nav_list"               => "all",
                "lang_type"              => "E",
                "is_delete"              => 0,
                "created_at"             => $time,
                "updated_at"             => $time
            ];

            $id = $sysModel
                ->insertGetId($insert_data);

            if ($id){
                Db::commit();
                $this_info = $sysModel
                    ->alias('sum')
                    ->join('sys_role sr','sr.role_id = sum.role_id')
                    ->where("id",$id)
                    ->field("sum.user_sn,sum.user_name,sum.role_id,sum.phone,sum.updated_at,sum.last_ip,sum.is_sys,sum.email,sum.created_at,sum.avatar")
                    ->field('sr.role_name,sr.role_describe')
                    ->find();
                return $this->com_return(true,config("params.SUCCESS"),$this_info);
            }else{
                return $this->com_return(false,config("params.FAIL"));
            }
        }catch (Exception $e){
            Db::rollback();
            return $this->com_return(false,$e->getMessage());
        }
    }

    /**
     * 编辑管理员
     * @return array
     */
    public function edit()
    {
        $common = new Common();

        $id         = $this->request->param("id","");
        $user_name  = $this->request->param("user_name","");
        $role_id    = $this->request->param("role_id","");
        $user_sn    = $this->request->param("user_sn","");
        $email      = $this->request->param("email","");
        $phone      = $this->request->param("phone","");

        $rule = [
            "id"                            => "require",
            "user_name|用户名"               => "require|unique:sys_admin_user",
            "role_id|角色分配"               => "require",
            "user_sn|工号"                   => "unique:sys_admin_user",
            "email|邮箱"                    => "email|unique:sys_admin_user",
            "phone|电话号码"                 => "regex:1[3-8]{1}[0-9]{9}|unique:sys_admin_user",
        ];

        $request_res = [
            "id"                    => $id,
            "user_name"             => $user_name,
            "role_id"               => $role_id,
            "user_sn"               => $user_sn,
            "email"                 => $email,
            "phone"                 => $phone,
        ];

        $validate = new Validate($rule);

        if (!$validate->check($request_res)){
            return $common->com_return(false,$validate->getError(),null);
        }

        $sysModel = new SysAdminUser();

        Db::startTrans();
        try{
            $update_data = [
                "user_name"              => $user_name,
                "role_id"                => $role_id,
                "user_sn"                => $user_sn,
                "email"                  => $email,
                "phone"                  => $phone,
                "updated_at"             => time()
            ];
            $res = $sysModel
                ->where("id",$id)
                ->update($update_data);

            if ($res !== false){
                Db::commit();
                return $this->com_return(true,config("params.SUCCESS"));
            }else{
                return $this->com_return(false,config("params.FAIL"));
            }

        }catch (Exception $e){
            Db::rollback();
            return $this->com_return(false,$e->getMessage());
        }
    }

    /**
     * 管理员删除
     * @return array
     */
    public function delete()
    {
        $common = new Common();

        $ids = $this->request->param("id","");

        if (!empty($ids)){
            $id_array = explode(",",$ids);

            //查看当前登录管理员是否有删除权限
            $Authorization = $this->request->header("Authorization",'');

            $admin_info = Db::name("sys_admin_user")
                ->where("token",$Authorization)
                ->field("is_sys")
                ->find();
            if (!empty($admin_info)){
                $is_sys = $admin_info["is_sys"];
                if ($is_sys){
                    $sysModel = new SysAdminUser();

                    $update_data = [
                        "is_delete" => "1"
                    ];
                    Db::startTrans();
                    try{
                        $is_ok = false;
                        foreach ($id_array as $id_l){
                            $is_ok = $sysModel->where("id",$id_l)->update($update_data);
                        }
                        if ($is_ok){
                            Db::commit();
                            return $this->com_return(true,config("params.SUCCESS"));
                        }else{
                            return $this->com_return(false,config("params.FAIL"));
                        }

                    }catch (Exception $e){
                        Db::rollback();
                        return $this->com_return(false,$e->getMessage());
                    }
                }else{
                    return $this->com_return(false,config("params.PURVIEW_SHORT"));
                }
            }else{
                return $this->com_return(false,config("params.FAIL"));
            }
        }else{
            return $this->com_return(false,config("params.PARAM_NOT_EMPTY"));
        }
    }


    /**
     * 变更管理员密码
     * @param Request $request
     * @return array
     */
    public function changeManagerPass(Request $request)
    {
        //获取当前登录管理员
        if ($request->method() == "OPTIONS"){
            return $this->com_return(true,'options返回');
        }

        $action_user = $this->getLoginAdminId($request->header('Authorization'))['user_name'];
        $role_id     = $this->getLoginAdminId($request->header('Authorization'))['role_id'];


        if ($role_id != 1){
            return $this->com_return(false,config("params.PERMISSION_NOT_ENOUGH"));
        }

        $id                    = $request->param('id',"");//被操作管理员id
        $password              = $request->param("password","");
        $password_confirmation = $request->param("password_confirmation","");

        if (empty($id)){
            return $this->com_return(false,config("params.PARAM_NOT_EMPTY"));
        }

        if (empty($password) || empty($password_confirmation)){
            return $this->com_return(false,config("params.PASSWORD_NOT_EMPTY"));
        }

        if (strlen($password) < 6){
            return $this->com_return(false,config("params.LONG_NOT_ENOUGH"));
        }

        if ($password !== $password_confirmation){
            return $this->com_return(false,config("params.PASSWORD_DIF"));
        }

        $password = sha1($password);

        $time = time();

        $token = $this->jm_token($password.$time);

        $params = [
            'password'   => $password,
            'token'      => $token,
            'updated_at' => $time
        ];

        $adminUserModel = new SysAdminUser();

        $is_ok = $adminUserModel
            ->where('id',$id)
            ->update($params);

        if ($is_ok !== false){
            //添加至系统日志
            $this->addSysLog("$time","$action_user",config("useraction.change_admin_pass")['name']." -> $id",$request->ip());

            return $this->com_return(true,config("params.SUCCESS"));
        }else{
            return $this->com_return(true,config("params.FAIL"));
        }
    }


    /**
     * 登陆管理员信息变更
     * @param Request $request
     * @return array
     */
    public function changeManagerInfo(Request $request)
    {

        $adminUserModel = new SysAdminUser();

        $Authorization = $this->request->header("Authorization",'');

        $avatar     = $request->param('avatar','');//头像
        $user_name  = $request->param('user_name','');//用户名
        $phone      = $request->param('phone','');//电话号码
        $email      = $request->param('email','');//邮箱
        $password   = $request->param('password','');//密码

        $new_password          = $request->param('new_password','');//新密码
        $password_confirmation = $request->param('password_confirmation','');//确认密码


        $rule = [
            'user_name|用户名' => 'require|alphaNum',
            'avatar|头像'      => 'url',
            'phone|电话'       => 'regex:1[3-8]{1}[0-9]{9}',
            'email|邮箱'       => 'email',
        ];

        $check_data = [
            'user_name' => $user_name,
            'avatar'    => $avatar,
            'phone'     => $phone,
            'email'     => $email,
        ];

        $validate = new Validate($rule);

        if (!$validate->check($check_data)){
            return $this->com_return(false,$validate->getError(),null);
        }

        $time = time();

        if (empty($password)){
            //如果登陆密码为空,则只更新基本信息
            $update_params = [
                'user_name'  => $user_name,
                'avatar'     => $avatar,
                'phone'      => $phone,
                'email'      => $email,
                'updated_at' => $time
            ];
        }else{
            //否则,全部更新
            //首先判断当前输入密码是否正确
            $is_can_change = $adminUserModel
                ->where('token',$Authorization)
                ->where('password',sha1($password))
                ->count();

            if ($is_can_change != 1){
                return $this->com_return('false',config('params.PERMISSION_NOT_ENOUGH'));
            }


            /*验证新密码有效性 begin*/
            $rule = [
                'new_password|密码'                   =>  'require|length:6,16|alphaNum',
                'password_confirmation|确认密码'       =>  'require|length:6,16|alphaNum|confirm:new_password',
            ];

            $check_data = [
                'new_password'              => $new_password,
                'password_confirmation'     => $password_confirmation
            ];

            $validate = new Validate($rule);

            if (!$validate->check($check_data)){
                return $this->com_return(false,$validate->getError());
            }
            /*验证密码有效性 off*/


            $new_password = sha1($new_password);

            $time = time();

            $token = $this->jm_token($new_password.$time);


            $update_params = [
                'password'   => $new_password,
                'token'      => $token,
                'user_name'  => $user_name,
                'avatar'     => $avatar,
                'phone'      => $phone,
                'email'      => $email,
                'updated_at' => $time
            ];
        }

        $adminUserModel = new SysAdminUser();

        $is_ok = $adminUserModel
            ->where('token',$Authorization)
            ->update($update_params);


        if ($is_ok !== false){
            return $this->com_return(true,config('params.SUCCESS'));
        }else{
            return $this->com_return(true,config('params.FAIL'));
        }
    }
}