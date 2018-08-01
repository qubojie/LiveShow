<?php
/**
 * Created by PhpStorm.
 * User: qubojie
 * Date: 2018/7/30
 * Time: 下午6:31
 */
namespace app\wechat\controller;

use app\admin\model\ManageSalesman;
use think\Controller;
use think\Request;

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

        if (empty($phone) || empty($password)){
            return $this->com_return(false,config("params.PARAM_NOT_EMPTY"));
        }

        $password = sha1($password);

        $manageSalesmanModel = new ManageSalesman();

        $manage_column = $manageSalesmanModel->manage_column;

        $manageIsExist = $manageSalesmanModel
            ->where('phone',$phone)
            ->where('password',$password)
            ->count();

        if ($manageIsExist){

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

            if ($is_ok){

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
}