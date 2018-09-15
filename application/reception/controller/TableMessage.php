<?php
/**
 * Created by PhpStorm.
 * User: qubojie
 * Date: 2018/9/15
 * Time: 下午6:04
 */
namespace app\reception\controller;

use think\Request;

class TableMessage extends CommonAction
{
    /**
     * 消息列表
     * @param Request $request
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function messageList(Request $request)
    {
        if ($request->method() == "OPTIONS"){
            return $this->com_return(true,config("params.SUCCESS"));
        }

        $tableMessageModel = new \app\wechat\model\TableMessage();

        $list = $tableMessageModel
            ->where("status",0)
            ->where("is_read",0)
            ->order("created_at DESC")
            ->select();

        $list = json_decode(json_encode($list),true);

        for ($i = 0; $i < count($list); $i ++){
            if ($list[$i]['type'] == "revenue"){
                $list[$i]['type_name'] = "预约通知";
            }else{
                $list[$i]['type_name'] = "服务呼叫";
            }
        }

        return $this->com_return(true,config("params.SUCCESS"),$list);
    }

    /**
     * 确认消息
     * @param Request $request
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function confirm(Request $request)
    {
        if ($request->method() == "OPTIONS"){
            return $this->com_return(true,config("params.SUCCESS"));
        }

        $message_id = $request->param("message_id","");//消息id

        $token = $request->header("Token");

        $manageInfo = $this->tokenGetManageInfo($token);

        $params = [
            "status"       => "1",
            "is_read"      => "1",
            "check_user"   => $manageInfo['sales_name'],
            "check_time"   => time(),
            "check_reason" => "手动确认",
            "updated_at"   => time()
        ];


        $tableMessageModel = new \app\wechat\model\TableMessage();

        $is_ok = $tableMessageModel
            ->where("message_id",$message_id)
            ->update($params);

        if ($is_ok !== false){
            return $this->com_return(true,config("params.SUCCESS"));
        }else{
            return $this->com_return(false,config("params.FAIL"));

        }


    }
}