<?php
/**
 * 潜在会员管理.
 * User: qubojie
 * Date: 2018/6/23
 * Time: 下午2:41
 */
namespace app\admin\controller;

use app\admin\model\ManagePotentialUser;
use app\common\controller\UUIDUntil;
use think\Db;
use think\Exception;
use think\Request;
use think\Validate;

class Potential extends CommandAction
{
    /*
     * 列表
     * */
    public function index(Request $request)
    {
        $common = new Common();
        $ManagePotentialUserModel = new ManagePotentialUser();

        $pagesize = $request->param("pagesize",config("PAGESIZE"));

        if (empty($pagesize)){
            $pagesize = config('PAGESIZE');
        }

        $nowPage = $request->param("nowPage","1");

        $keyword = $request->param("keyword","");

        $where = [];
        if (!empty($keyword)){
            $where['ms.sales_name|mpu.name|mpu.phone|mpu.remark'] = ["like","%$keyword%"];
        }

        $count = $ManagePotentialUserModel->count();//总记录数

        $pageNum = ceil($count/$pagesize); //总页数

        $config = [
            "page" => $nowPage,
        ];

        $salesman_list = $ManagePotentialUserModel
            ->alias("mpu")
            ->join("manage_salesman ms","ms.sid = mpu.sid")
            ->where($where)
            ->field("mpu.*")
            ->field("ms.sales_name")
            ->paginate($pagesize,false,$config);

        return $common->com_return(true,config("GET_SUCCESS"),$salesman_list);
    }

    /*
     * 添加
     * */
    public function add(Request $request)
    {
        $common = new Common();
        $ManagePotentialUserModel = new ManagePotentialUser();

        $sid            = $request->param("sid","");//负责的销售人员ID（前缀S）
        $statue         = $request->param("statue","0");//用户状态  0待入籍   1已转换  8失败  9删除
        $name           = $request->param("name","");//会员姓名
        $phone          = $request->param("phone","");//联系电话
        $sex            = $request->param("sex","男");//联系电话
        $card_type      = $request->param("card_type","");//意向vip卡类型
        $last_link_time = $request->param("last_link_time","");//最后联系时间
        $remark         = $request->param("remark","");//备注(其他相关信息)

        $rule = [
            "sid|负责的销售人员ID"          => "require",
            "name|会员姓名"                => "require",
            "phone|联系电话"               => "require|regex:1[3-8]{1}[0-9]{9}|unique:manage_potential_user",
            "card_type|意向vip卡类型"      => "require",
            "last_link_time|最后联系时间"  => "require",
            "remark|备注"                 => "require",
        ];

        $request_data = [
            "sid"            => $sid,
            "name"           => $name,
            "phone"          => $phone,
            "card_type"      => $card_type,
            "last_link_time" => $last_link_time,
            "remark"         => $remark
        ];


        $validate = new Validate($rule);

        if (!$validate->check($request_data)){
            return $common->com_return(false,$validate->getError(),null);
        }


        $UUIDUntil = new UUIDUntil();

        $puid  = $UUIDUntil->generateReadableUUID("P");

        $time = time();

        $update_data = [
            "puid"           => $puid,
            "sid"            => $sid,
            "statue"         => $statue,
            "name"           => $name,
            "phone"          => $phone,
            "card_type"      => $card_type,
            "last_link_time" => $last_link_time,
            "remark"         => $remark,
            "created_at"     => $time,
            "updated_at"     => $time,
        ];

        Db::startTrans();
        try{

            $is_ok = $ManagePotentialUserModel
                ->insert($update_data);

            if ($is_ok){
                Db::commit();
                return $common->com_return(true, config("ADD_SUCCESS"));
            }else{
                return $common->com_return(false, config("ADD_FAIL"));
            }
        }catch (Exception $e){
            Db::rollback();
            return $common->com_return(false,$e->getMessage(),null);
        }
    }

    /*
     * 编辑
     * */
    public function edit(Request $request)
    {
        $common = new Common();

        $ManagePotentialUserModel = new ManagePotentialUser();

        $puid = $request->param("puid","");

        if (empty($puid)) {
            return $common->com_return(false,config("PARAM_NOT_EMPTY"));
        }

        $params = $request->param();

        $params["updated_at"] = time();

        $rule = [
            "puid|潜在用户id"             => "require",
            "sid|负责的销售人员ID"         => "require",
            "statue|用户状态"             => "require",
            "name|会员姓名"               => "require",
            "phone|电话"                 => "require|regex:1[3-8]{1}[0-9]{9}|unique:manage_potential_user",
            "card_type|意向vip卡类型"     => "require",
            "last_link_time|最后联系时间" => "require",
            "remark|备注"                => "require",
        ];

        $validate = new Validate($rule);

        if (!$validate->check($params)){
            return $common->com_return(false,$validate->getError(),null);
        }

        Db::startTrans();
        try{
            $is_ok = $ManagePotentialUserModel
                ->where("puid",$puid)
                ->update($params);

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

    /*
     * 删除
     * */
    public function delete(Request $request)
    {
        $common = new Common();
        $ManagePotentialUserModel = new ManagePotentialUser();

        $puids = $request->param("puid","");

        if (empty($puids)) {
            return $common->com_return(false,config("PARAM_NOT_EMPTY"));
        }

        $id_array = explode(",",$puids);

        $time = time();

        Db::startTrans();
        try{
            $is_ok =false;

            foreach ($id_array as $puid){
                $is_ok = $ManagePotentialUserModel
                    ->where("puid",$puid)
                    ->delete();
            }

            if ($is_ok !== false){

                Db::commit();
                return $common->com_return(true, config("DELETE_SUCCESS"));

            }else{
                return $common->com_return(false, config("DELETE_FAIL"));
            }

        }catch (Exception $e){
            Db::rollback();
            return $common->com_return(false,$e->getMessage(),null);
        }
    }
}