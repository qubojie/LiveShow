<?php
/**
 * 特殊日期设置
 * User: qubojie
 * Date: 2018/7/24
 * Time: 下午12:04
 */
namespace app\admin\controller;

use app\admin\model\MstTableReserveDate;
use think\Request;
use think\Validate;

class TableSpecialDate extends CommandAction
{
    /**
     * 特殊日期列表
     * @param Request $request
     * @return array
     * @throws \think\exception\DbException
     */
    public function index(Request $request)
    {
        $specialDateModel = new MstTableReserveDate();

        $pagesize = $request->param("pagesize",config('PAGESIZE'));//显示个数,不传时为10

        if (empty($pagesize)) $pagesize = config('PAGESIZE');

        $nowPage = $request->param("nowPage","1");

        $config = [
            "page" => $nowPage,
        ];

        $column = $specialDateModel->column;

        $list  = $specialDateModel
            ->field($column)
            ->paginate($pagesize,false,$config);

        return $this->com_return(true,config("params.SUCCESS"),$list);
    }

    /**
     * 特殊日期添加
     * @param Request $request
     * @return array
     */
    public function add(Request $request)
    {
        $specialDateModel = new MstTableReserveDate();

        $appointment    = $request->param('appointment','');//指定押金预定日期
        $desc           = $request->param('desc','');//
        $is_expiry      = $request->param('is_expiry','');//是否启用  0否 1是

        $rule = [
            "appointment|指定押金预定日期" => "require",
            "desc|描述"                  => "max:100",
        ];

        $check_data = [
            "appointment"    => $appointment,
            "desc"           => $desc,
        ];

        $validate = new Validate($rule);

        if (!$validate->check($check_data)){
            return $this->com_return(false,$validate->getError());
        }

        $is_exist = $specialDateModel
            ->where('appointment',$appointment)
            ->count();
        if ($is_exist > 0){
            return $this->com_return(false,config("params.DATE_IS_EXIST"));
        }

        $time = time();

        $insert_data = [
            'appointment'       => $appointment,
            'desc'              => $desc,
            'is_expiry'         => $is_expiry,
            'created_at'        => $time,
            'updated_at'        => $time
        ];

        $is_ok = $specialDateModel
            ->insert($insert_data);
        if ($is_ok){
            return $this->com_return(true,config("params.SUCCESS"));
        }else{
            return $this->com_return(false,config("params.FAIL"));
        }


    }

    /**
     * 特殊日期编辑
     * @param Request $request
     * @return array
     */
    public function edit(Request $request)
    {
        $specialDateModel = new MstTableReserveDate();

        $appointment = $request->param('appointment','');

        $desc           = $request->param('desc','');//
        $is_expiry      = $request->param('is_expiry','');//是否启用  0否 1是

        $rule = [
            "appointment|指定押金预定日期" => "require",
//            "subscription|预约定金"       => "require|number",
//            "turnover_limit|最低消费"     => "number",
            "desc|描述"                  => "max:100",
        ];

        $check_data = [
            "appointment"    => $appointment,
//            "subscription"   => $subscription,
//            "turnover_limit" => $turnover_limit,
            "desc"           => $desc,
        ];

        $validate = new Validate($rule);

        if (!$validate->check($check_data)){
            return $this->com_return(false,$validate->getError());
        }

        /*if ($turnover_limit < $subscription){
            return $this->com_return(false,config("params.SPENDING_ELT_SUBS"));
        }*/

        $time = time();

        $update_data = [
//            'subscription'      => $subscription,
//            'turnover_limit'    => $turnover_limit,
            'desc'              => $desc,
            'is_expiry'         => $is_expiry,
            'updated_at'        => $time
        ];

        $is_ok = $specialDateModel
            ->where('appointment',$appointment)
            ->update($update_data);
        if ($is_ok){
            return $this->com_return(true,config("params.SUCCESS"));
        }else{
            return $this->com_return(false,config("params.FAIL"));
        }
    }

    /**
     * 特殊日期删除
     * @param Request $request
     * @return array]
     */
    public function delete(Request $request)
    {
        $specialDateModel = new MstTableReserveDate();

        $appointment = $request->param('appointment','');

        $rule = [
            "appointment|指定押金预定日期" => "require",
        ];

        $check_data = [
            "appointment"    => $appointment,
        ];

        $validate = new Validate($rule);

        if (!$validate->check($check_data)){
            return $this->com_return(false,$validate->getError());
        }

        $is_ok = $specialDateModel
            ->where('appointment',$appointment)
            ->delete();
        if ($is_ok !== false){
            return $this->com_return(true,config("params.SUCCESS"));
        }else{
            return $this->com_return(false,config("params.FAIL"));
        }

    }

    /**
     * 是否启用
     * @param Request $request
     * @return array
     */
    public function enable(Request $request)
    {
        $specialDateModel = new MstTableReserveDate();

        $is_expiry = (int)$request->param("is_expiry","");
        $appointment   = $request->param("appointment","");

        if (empty($appointment)){
            return $this->com_return(false,config("params.PARAM_NOT_EMPTY"));
        }

        $update_data = [
            'is_expiry'  => $is_expiry,
            'updated_at' => time()
        ];

        $is_ok = $specialDateModel
            ->where('appointment',$appointment)
            ->update($update_data);

        if ($is_ok !== false){
            return $this->com_return(true,config("params.SUCCESS"));
        }else{
            return $this->com_return(false,config("params.FAIL"));
        }
    }
}