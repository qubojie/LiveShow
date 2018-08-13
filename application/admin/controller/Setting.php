<?php
/**
 * 系统设置类.
 * User: qubojie
 * Date: 2018/6/19
 * Time: 下午4:00
 */
namespace app\admin\controller;

use app\admin\model\SysSetting;
use think\Controller;
use think\Exception;
use think\Hook;
use think\Db;
use think\Env;
use think\Request;
use think\Validate;

class Setting extends CommandAction
{

    /**
     * 设置类型列表
     * @param Request $request
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function lists(Request $request)
    {
        $common = new Common();
        $sysSettingModel = new SysSetting();

        $ktype_arr = $sysSettingModel
            ->group("ktype")
            ->field("ktype")
            ->select();
        /*$ktype_arr = json_decode(json_encode($ktype_arr),true);
        $result = array();
        $coloum = [
            "key",
            "ktype",
            "key_title",
            "key_des",
            "vtype",
            "select_cont",
            "value",
            "default_value",
            "is_sys"
        ];
        for ($i=0;$i<count($ktype_arr);$i++){
            $ktype = $ktype_arr[$i]['ktype'];

            $this_ktype = $sysSettingModel
                ->where("ktype",$ktype)
                ->field($coloum)
                ->order('sort', 'asc')
                ->select();
            $result[$ktype] = $this_ktype;
        }*/

        $res = json_decode(json_encode($ktype_arr),true);
        $res_arr = [];
        $mn = [];
        foreach ($res as $k => $v){
            foreach ($v as $m => $n){
                //$mn[] = $n;
                if ($n == "card"){
                    $mn[$k]["key"] = $n;
                    $mn[$k]["name"] = config("sys.card");
                }

                if ($n == "reserve"){
                    $mn[$k]["key"] = $n;
                    $mn[$k]["name"] = config("sys.reserve");
                }

                if ($n == "sms" ){
                    $mn[$k]["key"] = $n;
                    $mn[$k]["name"] = config("sys.sms");
                }

                if ($n == "sys" ){
                    $mn[$k]["key"] = $n;
                    $mn[$k]["name"] = config("sys.sys");
                }

                if ($n == "user" ){
                    $mn[$k]["key"] = $n;
                    $mn[$k]["name"] = config("sys.user");
                }
            }
        }
        return $common->com_return(true,"获取成功",$mn);
    }

    /**
     * 根据类型查找相应下的数据
     * @param Request $request
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function get_info(Request $request)
    {
        $common = new Common();
        $ktype = $request->param("ktype","");
        if (!empty($ktype)){
            $sysSettingModel = new SysSetting();

            $coloum = [
                "key",
                "ktype",
                "key_title",
                "key_des",
                "vtype",
                "select_cont",
                "value",
                "default_value",
                "is_sys"
            ];

            $res = $sysSettingModel
                ->where('ktype',$ktype)
                ->field($coloum)
                ->order('sort asc')
                ->select();
            return $common->com_return(true,"获取成功",$res);

        }else{
            return $common->com_return(false,"缺少参数");
        }
    }

    /**
     * 类型详情编辑提交
     * @param Request $request
     * @return array
     */
    public function edit(Request $request)
    {
        $common = new Common();

        $key    = $request->param("key","");
        $value  = $request->param("value","");

        $rule = [
            "key"       => "require",
            "value|内容" => "require"
        ];

        $request_res = [
            "key"   => $key,
            "value" => $value,
        ];

        $validate = new Validate($rule);

        if (!$validate->check($request_res)){
            return $common->com_return(false,$validate->getError(),null);
        }

        $update_data = [
            "value" => $value
        ];
        Db::startTrans();
        try{
            $sysSettingModel = new SysSetting();
            $is_ok = $sysSettingModel
                ->where("key",$key)
                ->update($update_data);
            if ($is_ok){
                Db::commit();
                return $common->com_return(true,"更改成功",null);
            }else{
                return $common->com_return(false,"更改失败",null);
            }
        }catch (Exception $e){
            Db::rollback();
            return $common->com_return(false,$e->getMessage(),null);
        }
    }

    /**
     * 新增系统设置
     * @param Request $request
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function create(Request $request)
    {
        $common = new Common();

        $key           = $request->param("key","");
        $ktype         = $request->param("ktype","");
        $sort          = $request->param("sort","");
        $key_title     = $request->param("key_title","");
        $key_des       = $request->param("key_des","");
        $vtype         = $request->param("vtype","");
        $select_cont   = $request->param("select_cont","");
        $value         = $request->param("value","");
        $default_value = $request->param("default_value","");

        $Authorization = $this->request->header("Authorization",'');
        //获取当前登录用户id
        $admin_info = Db::name("sys_admin_user")
            ->where("token",$Authorization)
            ->field("id")
            ->find();
        $admin_id = $admin_info['id'];

        $rule = [
            "key"                   => "require|unique_me:sys_setting|max:50",
            "ktype"                 => "require|max:10",
            "sort|排序"             => "require|number|unique_me:sys_setting",
            "key_title|标题"        => "require|unique_me:sys_setting|max:40",
            "key_des|描述"          => "require|max:200",
            "vtype|内容类型"         => "require|max:20",
            "value|内容"            => "require|max:2000",
            "default_value|默认内容" => "require|max:2000"
        ];

        $request_res = [
            "key"           => $key,
            "ktype"         => $ktype,
            "sort"          => $sort,
            "key_title"     => $key_title,
            "key_des"       => $key_des,
            "vtype"         => $vtype,
            "value"         => $value,
            "default_value" => $default_value,
        ];

        $validate = new Validate($rule);

        if (!$validate->check($request_res)){
            return $common->com_return(false,$validate->getError(),null);
        }

        $sysSettingModel = new SysSetting();

        //要写入的数据
        $insert_data = [
            "key"           => $key,
            "ktype"         => $ktype,
            "sort"          => $sort,
            "key_title"     => $key_title,
            "key_des"       => $key_des,
            "vtype"         => $vtype,
            "select_cont"   => $select_cont,
            "value"         => $value,
            "default_value" => $default_value,
            "last_up_time"  => time(),
            "last_up_admin" => $admin_id
        ];

        Db::startTrans();
        try{
            $is_ok = $sysSettingModel
                ->insert($insert_data);
            if ($is_ok){
                Db::commit();
                return $common->com_return(true,"添加成功");
            }else{
                return $common->com_return(false,"添加失败");
            }
        }catch (Exception $e){
            Db::rollback();
            return $common->com_return(false,$e->getMessage());
        }

    }
}
