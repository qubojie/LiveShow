<?php
/**
 * Created by PhpStorm.
 * User: qubojie
 * Date: 2018/8/11
 * Time: 下午6:16
 */
namespace app\wechat\controller;

use app\admin\model\TableRevenue;
use think\Db;
use think\Log;
use think\Request;
use app\common\controller\PublicAction;

class TableAction extends HomeAction
{
    /**
     * 管理人员开台
     * @param Request $request
     * @return mixed
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @throws \think\exception\PDOException
     */
    public function openTable(Request $request)
    {
        if ($request->method() == "OPTIONS"){
            return $this->com_return(true,config("params.SUCCESS"));
        }

        $token = $request->header("Token","");

        $trid = $request->param("trid","");

        if (empty($trid)){
            return $this->com_return(false,config("params.ABNORMAL_ACTION"));
        }

        $tableRevenueModel = new TableRevenue();

        $column = $tableRevenueModel->column;

        $info = $tableRevenueModel
            ->where("trid",$trid)
            ->whereTime("reserve_time","today")
            ->field($column)
            ->find();

        $info = json_decode(json_encode($info),true);

        if (empty($info)){

            return $this->com_return(false,config("params.OPEN_TABLE_STATUS")['OPEN_RETURN']);

        }

        $status = $info["status"];

        $uid    = $info["uid"];

        if ($status == config("order.table_reserve_status")['cancel']['key']){
            return $this->com_return(false,config("params.OPEN_TABLE_STATUS")['CANCELED']);
        }

        if ($status == config("order.table_reserve_status")['pending_payment']['key']){
            return $this->com_return(false,config("params.OPEN_TABLE_STATUS")['UNPAY']);
        }

        if ($status == config("order.table_reserve_status")['already_open']['key']){
            return $this->com_return(false,config("params.OPEN_TABLE_STATUS")['ALREADYOPEN']);
        }

        if ($status == config("order.table_reserve_status")['clear_table']['key']){
            return $this->com_return(false,config("params.OPEN_TABLE_STATUS")['ALREADYOPEN']);
        }

        $publicObj = new \app\reception\controller\PublicAction();

        $status = config("order.table_reserve_status")['already_open']['key'];

        $openTable = $publicObj->changeRevenueTableStatus($trid,$status);

        if (!$openTable){
            return $this->com_return(false,config("params.FAIL"));
        }

        /*如果开台成功,查看当前用户是否为定金预约用户,如果是则执行退款*/
        Log::info("开台成功 ---- ");

        $trid              = $info["trid"];//预约桌台id
        $subscription_type = $info["subscription_type"];//类型
        $subscription      = $info["subscription"];//金额

        if ($subscription_type == config("order.subscription_type")['subscription']['key']) {
            //如果预约定金类型为定金 1
            if ($subscription > 0){
                //此时执行开台成功,定金退还操作
                $suid_info = Db::name("bill_subscription")
                    ->where("trid",$trid)
                    ->field("suid")
                    ->find();

                $suid_info = json_decode(json_encode($suid_info),true);

                $suid = $suid_info["suid"];

                $refund_return = $this->refundDeposit($suid,$subscription);

                $res = json_decode($refund_return,true);

                if (isset($res["result"])){

                    if ($res["result"]){
                        //退款成功则变更定金状态
                        $status = config("order.reservation_subscription_status")['open_table_refund']['key'];
                        $params = [
                            "status"        => $status,
                            "is_refund"     => 1,
                            "refund_amount" => $subscription,
                            "updated_at"    => time()
                        ];

                        Db::name("bill_subscription")
                            ->where("suid",$suid)
                            ->update($params);

                    }else{
                        return $res;
                    }

                }else{
                    return $res;
                }
            }
        }

        /*记录开台日志 on*/
        $manageObj = new ManageReservation();

        $manageInfo = $manageObj->tokenGetManageInfo($token);

        $sales_name = $manageInfo['sales_name'];

        $table_id = $info['table_id'];

        $table_no = $info['table_no'];

        $type = config("order.table_action_type")['open_table']['key'];

        //获取用户信息
        $userInfo = getUserInfo($uid);

        $name  = $userInfo['name'];

        $phone = $userInfo['phone'];

        $desc  = " 为用户 ".$name."($phone)"." 开台 ->".$table_no."号桌";

        insertTableActionLog(microtime(true) * 10000,"$type","$table_id","$table_no","$sales_name",$desc,"","");
        /*记录开台日志 off*/

        //预约用户开台成功
        return $this->com_return(true,config("params.SUCCESS"));
    }

    /**
     * 获取清台列表信息
     * @param Request $request
     * @return array|false|mixed|\PDOStatement|string|\think\Model
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getCleanTableList(Request $request)
    {
        $table_id   = $request->param("table_id","");//桌位id

        if (empty($table_id)){
            return $this->com_return(false,config("params.ABNORMAL_ACTION"));
        }

        $tableInfo = $this->lookUserInfoComplete($table_id);

        $tableInfo = _unsetNull($tableInfo);

        if (empty($tableInfo)){
            return $this->com_return(false,config("params.ABNORMAL_ACTION"));
        }

        return $this->com_return(true,config("params.SUCCESS"),$tableInfo);
    }



    /**
     * 清台
     * @param Request $request
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function cleanTable(Request $request)
    {
        $table_id   = $request->param("table_id","");//桌位id

//        $is_confirm = $request->param("is_confirm","");//是否确认清台

        if (empty($table_id)){
            return $this->com_return(false,config("params.ABNORMAL_ACTION"));
        }

//        $tableInfo = $this->lookUserInfoComplete($table_id);

        /*if (!$is_confirm){
            foreach ($tableInfo as $value){
                if (empty($value['uid'])){
                    return $this->com_return(false,config("params.TABLE")['IMPROVING_USER_INFO']);
                }
            }
        }*/

        $tableRevenueModel = new TableRevenue();

        $updateParams = [
            "status"     => config("order.table_reserve_status")['clear_table']['key'],
            "updated_at" => time()
        ];

        $is_ok = $tableRevenueModel
            ->where("table_id",$table_id)
            ->where("status",config("order.table_reserve_status")['already_open']['key'])
            ->update($updateParams);

        if ($is_ok !== false){

            /*登陆前台人员信息 on*/
            $token      = $request->header("Token",'');
            $manageInfo = $this->tokenGetManageInfo($token);

            $stype_name = $manageInfo["stype_name"];
            $sales_name = $manageInfo["sales_name"];

            $action_user = $stype_name . " ". $sales_name;
            /*登陆前台人员信息 off*/
            $desc        = $action_user. " 清台";
            $type        = config("order.table_action_type")['clean_table']['key'];

            $tableInfo = Db::name("mst_table")
                ->where("table_id",$table_id)
                ->field("table_id,table_no")
                ->find();

            $tableInfo = json_decode(json_encode($tableInfo),true);

            $table_id = $tableInfo['table_id'];
            $table_no = $tableInfo['table_no'];

            insertTableActionLog(microtime(true) * 10000,"$type","$table_id","$table_no","$action_user","$desc","","");

            return $this->com_return(true,config("params.SUCCESS"));
        }else{
            return $this->com_return(false,config("params.FAIL"));
        }
    }

    /**
     * 查看指定$table_id的预约订台信息
     * @param $table_id
     * @return array|false|mixed|\PDOStatement|string|\think\Model
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    protected function lookUserInfoComplete($table_id)
    {
        $tableRevenueModel = new TableRevenue();

        $column = $tableRevenueModel->column;

        foreach ($column as $key => $val){
            $column[$key] = "tr.".$val;
        }

        $info = $tableRevenueModel
            ->alias("tr")
            ->join("user u","u.uid = tr.uid","LEFT")
            ->join("manage_salesman ms","ms.sid = tr.ssid","LEFT")
            ->where("tr.table_id",$table_id)
            ->where("tr.status",config("order.table_reserve_status")['already_open']['key'])
            ->field("u.name,u.phone user_phone")
            ->field("ms.phone sales_phone")
            ->field($column)
            ->select();

        $info = json_decode(json_encode($info),true);

        return $info;

    }


    /**
     * 定金退款
     * @param $suid
     * @param $subscription
     * @return bool|mixed
     */
    protected function refundDeposit($suid,$subscription)
    {

        $postParams = [
            "vid"           => $suid,
            "total_fee"     => $subscription,
            "refund_fee"    => $subscription,
            "out_refund_no" => $suid
        ];

        Log::info(date("Y-m-d H:i:s",time())."退押金组装参数 ---- ".var_export($postParams,true));


        $request = Request::instance();

        $url = $request->domain()."/wechat/reFund";

        Log::info(date("Y-m-d H:i:s",time())."退押金模拟请求地址 ---- ".$url);


        if (empty($url)) {
            return false;
        }

        $o = "";
        foreach ( $postParams as $k => $v )
        {
            $o.= "$k=" . urlencode( $v ). "&" ;
        }

        $postParams = substr($o,0,-1);


        $postUrl = $url;
        $curlPost = $postParams;

        $ch = curl_init();//初始化curl
        curl_setopt($ch, CURLOPT_URL,$postUrl);//抓取指定网页
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);//要求结果为字符串且输出到屏幕上
        curl_setopt($ch, CURLOPT_POST, 1);//post提交方式
        curl_setopt($ch, CURLOPT_POSTFIELDS, $curlPost);
        $data = curl_exec($ch);//运行curl
        curl_close($ch);

        return $data;

    }
}