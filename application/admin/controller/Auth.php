<?php
/**
 * 后台认证控制器
 * User: qubojie
 * Date: 2018/6/20
 * Time: 下午2:07
 */
namespace app\admin\controller;

use app\admin\model\SysAdminUser;
use think\Controller;
use think\Db;
use think\Exception;
use think\Request;
use think\Validate;
use app\admin\controller\Common;

class Auth extends Controller
{
    /**
     * 管理员用户密码登录
     *
     */
    public function login(Request $request)
    {
        $user_name = $request->param("user_name","");
        $password  = $request->param("password","");

        $rule = [
            "user_name|账号" => "require",
            "password|密码"  => "require"
        ];

        $request_res = [
            "user_name" => $user_name,
            "password"  => $password
        ];

        $validate = new Validate($rule);

        if (!$validate->check($request_res)){
            return $this->com_return(false,$validate->getError(),null);
        }


        $ip = $request->ip();

        $password = sha1($password);//密码加密

        $sysAdminUserModel = new SysAdminUser();

        $is_exist =  $sysAdminUserModel
            ->where("user_name",$user_name)
            ->where("password",$password)
            ->where("is_delete","0")
            ->field("id,user_sn,user_name,ec_salt,avatar,phone,email,last_ip,action_list,nav_list,lang_type,role_id,token,is_delete,is_sys,created_at,updated_at")
            ->find();
        if ($is_exist){
            $user_id = $is_exist['id'];

            //变更token,并返回token
            $Common = new Common();

            $token = $Common->jm_token($password);


            //更新token
            $save_data = [
                "token" => $token,
                "last_ip" => $ip
            ];

            Db::startTrans();
            try{
                $is_ok = $sysAdminUserModel
                    ->where("id",$user_id)
                    ->update($save_data);
                if ($is_ok !== false){
                    Db::commit();
                    $is_exist['token'] = $token;
                    $is_exist['last_ip'] = $ip;

                    return $this->com_return(true,'登陆成功',$is_exist);
                }else{
                    return $this->com_return(false,'登陆失败',null);
                }
            }catch (Exception $e){
                Db::rollback();
                return $this->com_return(false,$e->getMessage(),null);
            }
        }else{
            return $this->com_return(false,'登陆失败,请重试',null);
        }

    }


    /*
     * 刷新token
     * @return 新token
     * */
    public function refresh_token()
    {
        $Authorization = Request::instance()->header("authorization","");

        $common = new \app\admin\controller\Common();

        if (empty($Authorization)){

            return $this->com_return(false,config("PARAM_NOT_EMPTY"));

        }

        $sysAdminUserModel = new SysAdminUser();

        $new_token = $common->jm_token($Authorization);

        $update_date = [
            "token" => $new_token
        ];

        Db::startTrans();
        try{
            $is_exist = $sysAdminUserModel
                ->where("token",$Authorization)
                ->update($update_date);
            if ($is_exist){
                return $this->com_return(true,'操作成功',$new_token);
            }else{
                return $this->com_return(false,'操作失败');
            }
        }catch (Exception $e){
            Db::rollback();
            return $this->com_return(false,$e->getMessage());
        }
    }
}