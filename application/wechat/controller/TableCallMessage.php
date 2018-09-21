<?php
/**
 *
 * User: guojing
 * Date: 2018/9/14
 * Time: 11:58
 */

namespace app\wechat\controller;


use app\admin\controller\WxQrcode;
use app\admin\model\ManageSalesman;
use app\admin\model\MstTable;
use app\admin\model\MstTableArea;
use app\wechat\model\TableMessage;
use think\Exception;
use think\Log;
use think\Request;

class TableCallMessage extends CommonAction
{
    /**
     * 获取桌号
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function tableNumber()
    {
        $mstTable = new MstTable();
        $where['is_enable'] = 1;
        $where['is_delete'] = 0;
        $list = $mstTable->where($where)->field('table_id,table_no')->order('table_no', 'asc')->select();
        return $this->com_return(true, config("params.SUCCESS"), $list);
    }

    /**
     * 获取服务信息
     * @param Request $request
     * @return array
     */
    public function getCallMessage(Request $request)
    {
        $message = $request->param("message", "");//消息内容
        $table_id = $request->param("table_id", "");//桌号
        $table = new MstTable();
        $table_info = $table->where('table_id', $table_id)->find();
        $table_no = $table_info['table_no'];
        $area_id = $table_info['area_id'];
        $tableArea = new MstTableArea();
        $area_info = $tableArea->where('area_id', $area_id)->find();
        $sid = $area_info['sid'];
        $manageSalesman = new ManageSalesman();
        $manageSalesman_info = $manageSalesman->where('sid',$sid)->find();
        $manage_name = $manageSalesman_info['sales_name'];
        $manage_phone = $manageSalesman_info['phone'];
        $manageInfo = $manage_name.'('.$manage_phone.')';
        $content = '【桌号】' . $table_no . ' 【服务】' . $message. ' 【服务员组长】'. $manageInfo;
        $time = time();
        $tableMessage = new TableMessage();
        $data = [
            'type' => 'call',
            'content' => $content,
            'ssid' => $sid,
            'is_read' => 0,
            'created_at' => $time,
            'updated_at' => $time,
        ];
        try {
            $is_ok = $tableMessage->insertGetId($data);
            if ($is_ok) {
                return $this->com_return(true, config("params.SUCCESS"));
            } else {
                return $this->com_return(false, config("params.FAIL"));
            }
        } catch (Exception $e) {
            return $this->com_return(false, $e->getMessage());
        }
    }



//    public function getCallMessage2(Request $request)
//    {
//        $message = $request->param("message", "");//消息内容
//        $table_id = $request->param("table_id", "");//桌号
//        $form_id = $request->param("form_id", "");//表单ID
//        error_log("form_id数据" . $form_id, 3, APP_PATH . '../runtime/log/form_id.log');
//        $table = new MstTable();
//        $table_info = $table->where('table_id', $table_id)->find();
//        $table_no = $table_info['table_no'];
//        $area_id = $table_info['area_id'];
//        $tableArea = new MstTableArea();
//        $area_info = $tableArea->where('area_id', $area_id)->find();
//        $sid = $area_info['sid'];
//        $manageSalesman = new ManageSalesman();
//        $manage_info = $manageSalesman->where('sid', $sid)->find();
//        $wxid = "oP20J4576-lIGxo-WK_8nUnohuXY";//$manage_info['wxid']
//        $content = '【桌号】' . $table_no . ' 【服务】' . $message;
//        $time = time();
//        $tableMessage = new TableMessage();
//        $data = [
//            'type' => 'call',
//            'content' => $content,
//            'status' => 0,
//            'is_read' => 0,
//            'created_at' => $time,
//            'updated_at' => $time,
//        ];
//        try {
//            $is_ok = $tableMessage->insertGetId($data);
//            if ($is_ok) {
//                $date = date("Y-m-d H:i:s", $time);
//                /*登陆用户信息 on*/
//                $token = $request->header("Token", '');
//                $userInfo = $this->tokenGetUserInfo($token);
//                $userInfo = json_decode(json_encode($userInfo), true);
//                //$suid = $userInfo['suid'];
//                $phone = $userInfo['phone'];
//                $name = $userInfo['name'];
//                $userData = $name . ' ' . $phone;
//
//                $sendTemplateMessage = new SendTemplateMessage();
//                $keyword = array(
//                    'keyword1' => array(
//                        'value' => $content
//                    ),
//                    'keyword2' => array(
//                        'value' => $date
//                    ),
//                    'keyword3' => array(
//                        'value' => $userData
//                    )
//                );
//
//                $wx = new WxQrcode();
//                $access_token = $wx->getManageAccessToken();
//                $ret = $sendTemplateMessage->index($access_token, $wxid, 'index', $form_id, $keyword);
//                error_log("返回的数据" . var_export($ret, true), 3, APP_PATH . '../runtime/log/form_id.log');
//                $errcode = $ret['errcode'];
//                //dump($ret);die;
//                if ($errcode == 0) {
//                    return $this->com_return(true, config("params.SUCCESS"));
//                } else {
//                    $error = $sendTemplateMessage->error_code($errcode);
//                    return $this->com_return(false, $error);
//                }
//            } else {
//                return $this->com_return(false, config("params.FAIL"));
//            }
//        } catch (Exception $e) {
//            return $this->com_return(false, $e->getMessage());
//        }
//    }
}