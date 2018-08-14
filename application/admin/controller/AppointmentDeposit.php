<?php
/**
 * Created by PhpStorm.
 * User: qubojie
 * Date: 2018/8/13
 * Time: 下午5:30
 */
namespace app\admin\controller;

use app\wechat\model\BillSubscription;
use think\Controller;
use think\Db;
use think\Request;
use think\Validate;

class AppointmentDeposit extends CommandAction
{
    /**
     * 预约定金订单状态分组
     * @return array
     */
    public function orderStatus()
    {
        $typeList = config("order.reservation_subscription_status");

        $billSubscriptionModel = new BillSubscription();

        $res = [];

        foreach ($typeList as $key => $val){
            if ($val["key"] == config("order.reservation_subscription_status")['pending_payment']['key']){
                $count = $billSubscriptionModel
                    ->where("status",config("order.reservation_subscription_status")['pending_payment']['key'])
                    ->count();//未付款总记录数

                $val["count"] = $count;
            }else{
                $val["count"] = 0;
            }
            $res[] = $val;
        }


        return $this->com_return(true,config("params.SUCCESS"),$res);
    }

    /**
     * 预约定金列表
     * @param Request $request
     * @return array
     * @throws \think\exception\DbException
     */
    public function index(Request $request)
    {

        $billSubscriptionModel = new BillSubscription();

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

        $status_where = [];
        if (!empty($status)){

            $status_where['bs.status'] = $status;

        }

        $time_where = [];
        if (!empty($begin_time) && empty($end_time)){
            $time_where['bs.created_at'] = ['EGT',$begin_time];//大于
        }

        if (empty($begin_time) && !empty($end_time)){
            $time_where['bs.created_at'] = ['ELT',$end_time];//小于等于
        }

        if (!empty($begin_time) && !empty($end_time)){
            $time_where['bs.created_at'] = ['BETWEEN',"$begin_time,$end_time"];//时间区间
        }


        $pay_type_where = [];
        if (!empty($pay_type)){
            $pay_type_where['br.pay_type'] = ['eq',$pay_type];
        }


        $where = [];
        if (!empty($keyword)){
            $where['bs.suid'] = ["like","%$keyword%"];
        }

        $column = $billSubscriptionModel->column;

        $alias = $billSubscriptionModel->alias;

        foreach ($column as $key => $val){
            $column[$key] = $alias.".".$val;
        }

        $list = $billSubscriptionModel
            ->alias("bs")
            ->join("user u","u.uid = bs.uid","LEFT")
            ->join("table_revenue tr","tr.trid = bs.trid")
            ->where($where)
            ->where($status_where)
            ->where($time_where)
            ->where($pay_type_where)
            ->order("bs.created_at DESC")
            ->field($column)
            ->field("u.phone,u.name,u.nickname,u.avatar")
            ->field("tr.table_id,tr.table_no,tr.reserve_way,tr.reserve_time,tr.ssid,tr.ssname,tr.sid,tr.sname")
            ->paginate($pagesize,false,$config);

        $list = json_decode(json_encode($list),true);

        $list = _unsetNull($list);

        return $this->com_return(true,config("params.SUCCESS"),$list);
    }


    /**
     * 预约定金收款操作
     * @param Request $request
     * @return array
     * @throws \think\exception\DbException
     */
    public function receipt(Request $request)
    {
        $billSubscriptionModel = new BillSubscription();

        $Authorization  = $request->header("Authorization");

        $notifyType     = $request->param('notifyType','adminCallback');//后台支付回调类型参数

        $suid           = $request->param('suid','');

        $payable_amount = $request->param('payable_amount','');//线上应付且未付金额

        $payable_amount = $payable_amount * 100;//(以分为单位)

        $pay_type       = $request->param('pay_type','');//支付方式 微信‘wxpay’ 支付宝 ‘alipay’ 线下银行转账 ‘bank’ 现金‘cash’

        $pay_no         = $request->param('pay_no','');//支付回单号

        $pay_bank_time  = $request->param("pay_bank_time",time());//银行转账付款时间或现金支付时间

        $review_desc    = $request->param("review_desc","");//审核备注         微信   “微信系统收款”


        $public_rule = [
            'suid|订单号'                      => 'require',
            'payable_amount|付款金额'         => 'require',
            'pay_type|支付方式'                => 'require',
        ];

        $check_public_params = [
            "suid"              => $suid,
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

        $res = $this->callBackPay("$Authorization","$notifyType","$suid","$payable_amount","$payable_amount","$review_desc","$pay_no");

        $res= json_decode(json_encode(simplexml_load_string($res, 'SimpleXMLElement', LIBXML_NOCDATA)), true);

        $time = time();
        if ($res['return_code'] == "SUCCESS"){
            //如果支付成功
            //更改定金订单支付信息
            $params = [
                "cancel_user"      => NULL,
                "cancel_time"      => NULL,
                "auto_cancel"      => NULL,
                "auto_cancel_time" => NULL,
                "cancel_reason"    => NULL,
                "pay_type"         => $pay_type,
                "updated_at"       => $time
            ];

            //更新订单支付类型
            $billSubscriptionModel
                ->where("suid",$suid)
                ->update($params);

            //记录日志
            $action      = config("useraction.deal_pay")['key'];
            $reason      = config("useraction.deal_pay")['name'];
            $action_user = $this->getLoginAdminId($request->header('Authorization'))['user_name'];
            addSysAdminLog("","","$suid","$action","$reason","$action_user","$time");

            return $this->com_return(true,config("params.SUCCESS"));

        }else{
            return $this->com_return(false,$res['return_msg']);
        }
    }


    /**
     * 预约定金退款操作
     * @param Request $request
     * @return array|bool|mixed
     * @throws \think\exception\DbException
     */
    public function refund(Request $request)
    {
        $Authorization = $request->header("Authorization");

        $billSubscriptionModel = new BillSubscription();

        $suid = $request->param("suid","");

        $subscription = $request->param("subscription","");

        if (empty($suid) || empty($subscription)){
            return $this->com_return(false,config("params.ABNORMAL_ACTION"));
        }

        $postParams = [
            "vid"           => $suid,
            "total_fee"     => $subscription,
            "refund_fee"    => $subscription,
            "out_refund_no" => $suid
        ];

        $type = "refund";

        $res = $this->requestPost($type,$Authorization,$postParams);

        $res = json_decode($res,true);


        if (isset($res["result"])){
            if ($res["result"]){
                //退款成功则变更定金状态
                $status = config("order.reservation_subscription_status")['cancel_revenue']['key'];
                $params = [
                    "status"        => $status,
                    "is_refund"     => 1,
                    "refund_amount" => $subscription,
                    "updated_at"    => time()
                ];

                $billSubscriptionModel
                    ->where("suid",$suid)
                    ->update($params);

                $action      = config("useraction.refund")['key'];
                $reason      = config("useraction.refund")['name'];
                $action_user = $this->getLoginAdminId($Authorization)['user_name'];
                $action_time = time();

                //记录日志
                addSysAdminLog("","","$suid","$action","$reason","$action_user","$action_time");


                return $this->com_return(true,config("params.SUCCESS"));
            }
        }else{
            return $res;
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
        $attach = config("order.pay_scene")['reserve']['key'];//充值支付场景

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

        $type = "receipt";

        $res = $this->requestPost($type,$Authorization,$values);

        return $res;

    }

    /**
     * 模拟post接口请求支付回调接口和退款接口
     * @param $type
     * @param $Authorization
     * @param array $postParams
     * @return bool|mixed
     */
    protected function requestPost($type,$Authorization,$postParams = array())
    {
        $request = Request::instance();

        if ($type == "receipt"){
            $url = $request->domain()."/wechat/notify";
        }elseif($type == "refund"){
            $url = $request->domain()."/wechat/reFund";
        }else{
            return false;
        }

        if (empty($url) || empty($postParams)) {
            return false;
        }

        $o = "";
        foreach ( $postParams as $k => $v )
        {
            $o.= "$k=" . urlencode( $v ). "&" ;
        }

        $postParams = substr($o,0,-1);

        $postUrl  = $url;
        $curlPost = $postParams;

        $header = array();
        $header[] = 'Authorization:'.$Authorization;

        $ch = curl_init();//初始化curl
        curl_setopt($ch, CURLOPT_URL,$postUrl);//抓取指定网页
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);//设置header
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);//要求结果为字符串且输出到屏幕上
        curl_setopt($ch, CURLOPT_POST, 1);//post提交方式
        curl_setopt($ch, CURLOPT_POSTFIELDS, $curlPost);
        $data = curl_exec($ch);//运行curl
        curl_close($ch);

        return $data;
    }
}