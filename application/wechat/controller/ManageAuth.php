<?php
/**
 * Created by PhpStorm.
 * User: qubojie
 * Date: 2018/7/30
 * Time: 下午6:31
 */
namespace app\wechat\controller;

use app\admin\model\ManageSalesman;
use app\admin\model\TableRevenue;
use think\Controller;
use think\Log;
use think\Request;
use think\Validate;

class ManageAuth extends Controller
{
    /**
     * 工作人员登陆
     * @param Request $request
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function login(Request $request)
    {
        $phone    = $request->param("phone","");
        $password = $request->param("password","");

        $res = $request->host();

        Log::info("来源 ---- ".$res);

        Log::info("登陆 ---- ".$phone);
        Log::info("密码 ---- ".$password);

        if (empty($phone) || empty($password)){
            return $this->com_return(false,config("params.PARAM_NOT_EMPTY"));
        }

        $password = sha1($password);

        $manageSalesmanModel = new ManageSalesman();

        $manage_column = $manageSalesmanModel->manage_column;

        $manageInfo = $manageSalesmanModel
            ->where('phone',$phone)
            ->where('password',$password)
            ->find();

        $manageInfo = json_decode(json_encode($manageInfo),true);

        if (!empty($manageInfo)){

            $quitStatue = config("salesman.salesman_status")['resignation']['key'];

            $statue = $manageInfo['statue'];

            if ($statue == $quitStatue){
                return $this->com_return(false,"离职员工,不可登陆");
            }

            $remember_token = $this->jm_token($password.time());

            $time = time();

            $update_params = [
                "remember_token" => $remember_token,
                "token_lastime"  => $time,
                "updated_at"     => $time
            ];

            $is_ok = $manageSalesmanModel
                ->where('phone',$phone)
                ->where('password',$password)
                ->update($update_params);

            if ($is_ok !== false){

                $manageInfo = $manageSalesmanModel
                    ->alias("ms")
                    ->join("manage_department md","md.department_id = ms.department_id")
                    ->join("mst_salesman_type st","st.stype_id = ms.stype_id")
                    ->where('ms.phone',$phone)
                    ->where('ms.password',$password)
                    ->field("md.department_title")
                    ->field("st.stype_key,st.stype_name")
                    ->field($manage_column)
                    ->find();

                return $this->com_return(true,config("params.SUCCESS"),$manageInfo);

            }else{

                return $this->com_return(false,config("params.FAIL"));

            }

        }else{
            return $this->com_return(false,config("params.ACCOUNT_PASSWORD_DIF"));
        }
    }

    /**
     * 小程序微信授权信息绑定
     * @param Request $request
     * @return array
     */
    public function phoneBindWechat(Request $request)
    {
        $phone      = $request->param('phone','');

        $openid     = $request->param('openid',"");     //openid
        $nickname   = $request->param('nickname',"");   //昵称
        $headimgurl = $request->param('headimgurl',""); //头像

        $params = $request->param();

        Log::info("授权信息 ----- ".var_export($params,true));

        $rule = [
            "phone|电话"      => "require",
            "openid|openid"  => "require",
            "nickname|昵称"   => "require",
            "headimgurl|头像"  => "require",
        ];

        $request_res = [
            "phone"        => $phone,
            "openid"       => $openid,
            "nickname"     => $nickname,
            "headimgurl"   => $headimgurl,
        ];

        $validate = new Validate($rule);

        if (!$validate->check($request_res)){
            return $this->com_return(false,$validate->getError());
        }

        $params = [
            'wxid'       => $openid,
            'avatar'     => $headimgurl,
            'nickname'   => $nickname,
            'updated_at' => time()
        ];

        $salesmanModel = new ManageSalesman();

        $is_ok = $salesmanModel
            ->where('phone',$phone)
            ->update($params);

        if ($is_ok !== false){
            return $this->com_return(true,'绑定成功');
        }else{
            return $this->com_return(false,'绑定失败');
        }
    }
}