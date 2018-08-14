<?php
/**
 * 充值管理
 * User: qubojie
 * Date: 2018/8/6
 * Time: 上午9:37
 */
namespace app\admin\controller;

use app\wechat\controller\PublicAction;
use app\wechat\model\BillRefill;
use think\Db;
use think\Exception;
use think\Request;
use think\Validate;

class Recharge extends CommandAction
{
    /**
     * 充值订单状态组
     * @return array
     */
    public function orderStatus()
    {
        $billRefillModel = new BillRefill();

        $res = config("order.recharge_status");

        $statusGroup = [];

        foreach ($res as $key => $val){
            if ($val["key"] == config("order.recharge_status")['pending_payment']['key']){
                $count = $billRefillModel
                    ->where("status",config("order.recharge_status")['pending_payment']['key'])
                    ->count();//未付款总记录数

                $val["count"] = $count;
            }else{
                $val["count"] = 0;
            }
            $statusGroup[] = $val;
        }

        return $this->com_return(true,config("params.SUCCESS"),$statusGroup);
    }

    /**
     * 充值订单列表
     * @param Request $request
     * @return array
     * @throws \think\exception\DbException
     */
    public function order(Request $request)
    {
        $billRefillModel = new BillRefill();

        $status     = $request->param("status","");

        $keyword    = $request->param("keyword","");

        $pay_type   = $request->param("pay_type","");//支付方式

        $begin_time = $request->param('begin_time',"");//开始时间

        $end_time   = $request->param('end_time',"");//结束时间

        $pagesize   = $request->param("pagesize",config('PAGESIZE'));//显示个数,不传时为10

        if (empty($pagesize)) $pagesize = config('PAGESIZE');

        $nowPage = $request->param("nowPage","1");

        $config = [
            "page" => $nowPage,
        ];


        $time_where = [];
        if (!empty($begin_time) && empty($end_time)){
            $time_where['br.created_at'] = ['EGT',$begin_time];//大于
        }

        if (empty($begin_time) && !empty($end_time)){
            $time_where['br.created_at'] = ['ELT',$end_time];//小于等于
        }

        if (!empty($begin_time) && !empty($end_time)){
            $time_where['br.created_at'] = ['BETWEEN',"$begin_time,$end_time"];//时间区间
        }

        $pay_type_where = [];
        if (!empty($pay_type)){
            $pay_type_where['br.pay_type'] = ['eq',$pay_type];
        }

        $where = [];
        if (!empty($keyword)){
            $where['br.rfid|br.pay_name|br.pay_bank|br.pay_account|br.receipt_name|br.receipt_bank|br.pay_user|u.phone|u.name|u.nickname|ms.sales_name|ms.sales_phone'] = ["like","%$keyword%"];
        }


        $admin_column = $billRefillModel->admin_column;

        foreach ($admin_column as $key => $val){
            $admin_column[$key] = "br.".$val;
        }

        $list = $billRefillModel
            ->alias("br")
            ->join("user u","u.uid = br.uid","LEFT")
            ->join("manage_salesman ms","ms.sid = br.referrer_id","LEFT")
            ->where('br.status',$status)
            ->where($time_where)
            ->where($where)
            ->where($pay_type_where)
            ->field($admin_column)
            ->field("u.phone,u.name,u.nickname,u.avatar,u.sex")
            ->field("ms.sales_name,ms.phone sales_phone")
            ->order("created_at DESC")
            ->paginate($pagesize,false,$config);

        $list = json_decode(json_encode($list),true);

        $data = $list["data"];

        for ($i = 0; $i < count($data); $i++){
            $rfid = $data[$i]['rfid'];

            $log_info = Db::name('sys_adminaction_log')
                ->where('oid',$rfid)
                ->select();

            $useraction = config("useraction");

            for ($m = 0; $m < count($log_info); $m++){
                $action = $log_info[$m]['action'];
                foreach ($useraction as $key => $val){
                    if ($action == $key){
                        $log_info[$m]['action'] = $val['name'];
                    }
                }
            }
            $list['data'][$i]['log_info'] = $log_info;
        }
        return $this->com_return(true,config("params.SUCCESS"),$list);

    }

    /**
     * 后台新增充值
     * @param Request $request
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function addRechargeOrder(Request $request)
    {
        $Authorization = $request->header("Authorization");

        $notifyType    = $request->param('notifyType','adminCallback');//后台支付回调类型参数

        $user_phone  = $request->param("user_phone","");//用户电话
        $sales_phone = $request->param("sales_phone","");//营销电话
        $pay_type    = $request->param("pay_type","");//支付方式
        $amount      = $request->param("amount","");//支付金额
        $cash_gift   = $request->param("cash_gift","");//赠送礼金数

        $pay_no          = $request->param("pay_no","");//支付回单号

        $pay_name        = $request->param("pay_name","");//付款人或公司名称
        $pay_bank        = $request->param("pay_bank","");//付款方开户行
        $pay_account     = $request->param("pay_account","");//付款方账号
        $pay_bank_time   = $request->param("pay_bank_time",time());//银行转账付款时间或现金支付时间
        $receipt_name    = $request->param("receipt_name","");//收款账户或收款人
        $receipt_bank    = $request->param("receipt_bank","");//收款银行
        $receipt_account = $request->param("receipt_account","");//收款账号

        $pay_user    = $request->param("pay_user","");//代收付款人       有代收人时填写

        $review_desc = $request->param("review_desc","");//审核备注         微信   “微信系统收款”


        $rule = [
            "user_phone|用户电话"  => "require",
            "pay_type|支付方式"    => "require",
            "amount|充值金额"      => "require|number|max:20|gt:0",
            "cash_gift|赠送礼金数" => "require|number|max:20|egt:0",
        ];

        $request_res = [
            "user_phone" => $user_phone,
            "pay_type"   => $pay_type,
            "amount"     => $amount,
            "cash_gift"  => $cash_gift,
        ];

        $validate = new Validate($rule);

        if (!$validate->check($request_res)){
            return $this->com_return(false,$validate->getError(),null);
        }


        if ($pay_type == config("order.pay_method")['wxpay']['key'] || $pay_type == config("order.pay_method")['alipay']['key']){
            //微信充值或阿里充值
            $pay_rule = [
                'pay_no|支付回单号' => 'require|unique:bill_refill'
            ];

            $check_pay_params = [
                'pay_no' => $pay_no
            ];

        }elseif ($pay_type == config("order.pay_method")['bank']['key']){
            //线下银行转账
            $pay_rule = [
                'pay_no|支付回单号' => 'require|unique:bill_refill'
            ];

            $check_pay_params = [
                'pay_no' => $pay_no
            ];


        }elseif ($pay_type == config("order.pay_method")['cash']['key']) {
            //现金支付
            $pay_rule = [];
            $check_pay_params = [];

        }else{
            //其他支付报错
            return $this->com_return(false,config("params.FAIL"));
        }

        //支付的回单号验证
        $pay_validate = new Validate($pay_rule);

        if (!$pay_validate->check($check_pay_params)){
            return $this->com_return(false,$pay_validate->getError(),null);
        }

        //根据用户电话获取用户信息
        $userInfo = $this->userPhoneGetInfo($user_phone);

        if (empty($userInfo)){
            return $this->com_return(false,config("params.PHONE_NOT_EXIST"));
        }

        $amount = $amount * 100;

        $uid = $userInfo['uid'];

        $publicAction = new PublicAction();
        $res = $publicAction->rechargePublicAction($uid,$amount,$cash_gift,$pay_type,$sales_phone);

        if (!$res['result']){
            return $this->com_return(false,config("params.FAIL"));
        }

        $rfid =$res['data']['rfid'];
        $amount =$res['data']['amount'];

        $res = $this->callBackPay("$Authorization","$notifyType","$rfid","$amount","$amount","$review_desc","$pay_no");

        $res= json_decode(json_encode(simplexml_load_string($res, 'SimpleXMLElement', LIBXML_NOCDATA)), true);

        $action_user = $this->getLoginAdminId($request->header('Authorization'))['user_name'];

        $billRefillModel = new BillRefill();

        $time = time();
        if ($res['return_code'] == "SUCCESS"){
            //如果支付成功
            //更改订单支付信息
            $orderParams = [
                'pay_type'         => $pay_type,
                'pay_name'         => $pay_name,
                'pay_bank'         => $pay_bank,
                'pay_account'      => $pay_account,
                'pay_bank_time'    => $pay_bank_time,
                'receipt_name'     => $receipt_name,
                'receipt_bank'     => $receipt_bank,
                'receipt_account'  => $receipt_account,
                'pay_user'         => $pay_user,
                'review_time'      => $time,
                'review_user'      => $action_user,
                'review_desc'      => $review_desc,
                'updated_at'       => $time
            ];

            $is_ok = $billRefillModel
                ->where('rfid',$rfid)
                ->update($orderParams);

            if ($is_ok !== false){

                //记录操作日志
                addSysAdminLog("$uid","","$rfid",config("useraction.recharge")["key"],config("useraction.recharge")["name"],"$action_user","$time");

                return $this->com_return(true,$res['return_msg']);
            }else{
                return $this->com_return(false,config("params.FAIL"));
            }
        }else{
            return $this->com_return(false,$res['return_msg']);
        }

    }


    /**
     * 后台充值收款操作
     * @param Request $request
     * @return array
     * @throws \think\exception\DbException
     */
    public function receipt(Request $request)
    {
        $Authorization = $request->header("Authorization");

        $notifyType    = $request->param('notifyType','adminCallback');//后台支付回调类型参数

        $rfid          = $request->param('rfid','');

        $payable_amount   = $request->param('payable_amount','');//线上应付且未付金额

        $payable_amount   = $payable_amount * 100;//(以分为单位)

        $pay_type         = $request->param('pay_type','');//支付方式 微信‘wxpay’ 支付宝 ‘alipay’ 线下银行转账 ‘bank’ 现金‘cash’

        $pay_no           = $request->param('pay_no','');//支付回单号


        $pay_name        = $request->param("pay_name","");//付款人或公司名称
        $pay_bank        = $request->param("pay_bank","");//付款方开户行
        $pay_account     = $request->param("pay_account","");//付款方账号
        $pay_bank_time   = $request->param("pay_bank_time",time());//银行转账付款时间或现金支付时间
        $receipt_name    = $request->param("receipt_name","");//收款账户或收款人
        $receipt_bank    = $request->param("receipt_bank","");//收款银行
        $receipt_account = $request->param("receipt_account","");//收款账号

        $pay_user    = $request->param("pay_user","");//代收付款人       有代收人时填写

        $review_desc = $request->param("review_desc","");//审核备注         微信   “微信系统收款”


        $public_rule = [
            'rfid|订单号'                      => 'require',
            'payable_amount|付款金额'         => 'require',
            'pay_type|支付方式'                => 'require',
        ];

        $check_public_params = [
            "rfid"              => $rfid,
            "payable_amount"    => $payable_amount,
            "pay_type"          => $pay_type,
        ];

        $validate = new Validate($public_rule);

        if (!$validate->check($check_public_params)){
            return $this->com_return(false,$validate->getError(),null);
        }

        $time = time();

        if (empty($pay_bank_time)){
            $pay_bank_time = $time;
        }


        if ($pay_type == config("order.pay_method")['wxpay']['key'] || $pay_type == config("order.pay_method")['alipay']['key']){
            //微信充值或阿里充值
            $pay_rule = [
                'pay_no|支付回单号' => 'require|unique:bill_refill'
            ];

            $check_pay_params = [
                'pay_no' => $pay_no
            ];

        }elseif ($pay_type == config("order.pay_method")['bank']['key']){
            //线下银行转账
            $pay_rule = [
                'pay_no|支付回单号' => 'require|unique:bill_refill'
            ];

            $check_pay_params = [
                'pay_no' => $pay_no
            ];


        }elseif ($pay_type == config("order.pay_method")['cash']['key']) {
            //现金支付
            $pay_rule = [];
            $check_pay_params = [];

        }else{
            //其他支付报错
            return $this->com_return(false,config("params.FAIL"));
        }

        //支付的回单号验证
        $pay_validate = new Validate($pay_rule);

        if (!$pay_validate->check($check_pay_params)){
            return $this->com_return(false,$pay_validate->getError(),null);
        }

        $res = $this->callBackPay("$Authorization","$notifyType","$rfid","$payable_amount","$payable_amount","$review_desc","$pay_no");

        $res= json_decode(json_encode(simplexml_load_string($res, 'SimpleXMLElement', LIBXML_NOCDATA)), true);

        $action_user = $this->getLoginAdminId($request->header('Authorization'))['user_name'];

        $billRefillModel = new BillRefill();

        $time = time();
        if ($res['return_code'] == "SUCCESS"){
            //如果支付成功
            //更改订单支付信息
            $orderParams = [
                'pay_type'         => $pay_type,
                'pay_name'         => $pay_name,
                'pay_bank'         => $pay_bank,
                'pay_account'      => $pay_account,
                'pay_bank_time'    => $pay_bank_time,
                'receipt_name'     => $receipt_name,
                'receipt_bank'     => $receipt_bank,
                'receipt_account'  => $receipt_account,
                'pay_user'         => $pay_user,
                'review_time'      => $time,
                'review_user'      => $action_user,
                'review_desc'      => $review_desc,
                'updated_at'       => $time
            ];

            $is_ok = $billRefillModel
                ->where('rfid',$rfid)
                ->update($orderParams);

            if ($is_ok !== false){

                addSysAdminLog("","","$rfid",config("useraction.recharge")["key"],config("useraction.recharge")["name"],"$action_user","$time");

                return $this->com_return(true,$res['return_msg']);
            }else{
                return $this->com_return(false,config("params.FAIL"));
            }
        }else{
            return $this->com_return(false,$res['return_msg']);
        }
    }



    /**
     * 统一模拟支付,组装参数
     * @param $Authorization
     * @param $notifyType
     * @param $vid
     * @param $total_fee
     * @param $cash_fee
     * @param $reason
     * @param string $transaction_id
     * @return mixed
     */
    protected function callBackPay($Authorization,$notifyType,$vid,$total_fee,$cash_fee,$reason,$transaction_id= '')
    {
        $attach = config("order.pay_scene")['recharge']['key'];//充值支付场景

        $values = [
            'attach'         => $attach,
            'notifyType'     => $notifyType,
            'total_fee'      => $total_fee,
            'cash_fee'       => $cash_fee,
            'out_trade_no'   => $vid,
            'transaction_id' => $transaction_id,
            'time_end'       => date("YmdHi",time()),
            'reason'         => $reason
        ];

        $res = $this->requestPost($Authorization,$values);

        return $res;

    }


    /**
     * 模拟post支付回调接口请求
     *
     * @param $Authorization
     * @param array $postParams
     * @return bool|mixed
     */
    protected function requestPost($Authorization,$postParams = array())
    {
        $request = Request::instance();

        $url = $request->domain()."/wechat/notify";

        if (empty($url) || empty($postParams)) {
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

        $header = array();
        $header[] = 'Authorization:'.$Authorization;

        $ch = curl_init();//初始化curl
        curl_setopt($ch, CURLOPT_URL,$postUrl);//抓取指定网页
//        curl_setopt($ch, CURLOPT_HEADER, $header);//设置header
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);//设置header
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);//要求结果为字符串且输出到屏幕上
        curl_setopt($ch, CURLOPT_POST, 1);//post提交方式
        curl_setopt($ch, CURLOPT_POSTFIELDS, $curlPost);
        $data = curl_exec($ch);//运行curl
        curl_close($ch);

        return $data;
    }

}