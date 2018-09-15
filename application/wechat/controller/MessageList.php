<?php
/**
 * Created by PhpStorm.
 * User: guojing
 * Date: 2018/9/14
 * Time: 14:12
 */

namespace app\wechat\controller;


use app\wechat\model\TableMessage;
use think\Request;

class MessageList extends HomeAction
{
    /**
     * 提醒通知消息列表
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function index(){
        $tableMessage = new TableMessage();
        $list = $tableMessage->field('message_id,type,content,status,is_read,check_time,updated_at')->select();
        foreach($list as $k=>$v){
            if ($v['type'] == 'revenue'){
                $list[$k]['type_name'] = '预约消息';
            }elseif ($v['type'] == 'call'){
                $list[$k]['type_name'] = '服务消息';
            }
            if ($v['is_read'] == 0){
                $list[$k]['state'] = '未读';
            }elseif($v['is_read'] == 1){
                $list[$k]['state'] = '已读';
            }
        }
        return $this->com_return(true,config("params.SUCCESS"),$list);
    }

    /**
     * 读取消息
     * @param Request $request
     * @return array
     */
    public function messageReadStatus(Request $request){
        $tableMessage = new TableMessage();
        $message_id   = $request->param("message_id","");//消息内容
        $data = [
            'is_read'     => 1,
            'status'      => 1,
            'updated_at'  => time()
        ];
        $is_ok = $tableMessage->where('message_id',$message_id)->update($data);
        if ($is_ok){
            return $this->com_return(true,config("params.SUCCESS"));
        }else{
            return $this->com_return(false,config("params.FAIL"));
        }
    }
}