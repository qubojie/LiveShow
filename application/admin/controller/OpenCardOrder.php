<?php
/**
 * 开卡订单管理
 * User: qubojie
 * Date: 2018/7/19
 * Time: 下午2:25
 */
namespace app\admin\controller;

use app\wechat\controller\OpenCard;
use app\wechat\controller\WechatPay;
use app\wechat\model\BillCardFees;
use think\Db;
use think\Env;
use think\Request;
use think\Validate;

class OpenCardOrder extends CommandAction
{
    /**
     * 开卡订单分组
     * @return array
     */
    public function orderType()
    {
        $billCardModel = new BillCardFees();



        $typeList = config("order.open_card_type");

        $res = [];

        foreach ($typeList as $key => $val){
            if ($val["key"] == config("order.open_card_status")['pending_payment']['key']){
                $count = $billCardModel
                    ->where("sale_status",config("order.open_card_status")['pending_payment']['key'])
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
     * 开卡礼寄送分组
     */
    public function giftShipType()
    {
        $billCardModel = new BillCardFees();

        $typeList = config("order.gift_ship_type");
        $res = [];
        foreach ($typeList as $key => $val){

            if ($val["key"] == config("order.open_card_status")['pending_ship']['key']){
                $count = $billCardModel
                    ->where("sale_status",config("order.open_card_status")['pending_ship']['key'])
                    ->count();//待发货

                $val["count"] = $count;
            }else{
                $val["count"] = 0;
            }

            $res[] = $val;
        }
        return $this->com_return(true,config("params.SUCCESS"),$res);
    }

    /**
     * 开卡订单列表
     * @param Request $request
     * @return array
     * @throws \think\exception\DbException
     */
    public function index(Request $request)
    {
        $status     = $request->param('status','');

        $pagesize   = $request->param("pagesize",config('PAGESIZE'));//显示个数,不传时为10

        if (empty($pagesize)) $pagesize = config('PAGESIZE');

        $nowPage    = $request->param("nowPage","1");

        $keyword    = $request->param("keyword","");

        $pay_type   = $request->param("pay_type","");//支付方式

        $card_type  = $request->param("card_type","");//卡的类型

        $begin_time = $request->param('begin_time',"");//开始时间

        $end_time   = $request->param('end_time',"");//结束时间

        $gift_ship = $request->param('gift_ship',"");//是否是发货管理请求

        $gift_ship_where = [];
        if ($gift_ship == "gift_ship"){
            $gift_ship_where['delivery_name'] = ['neq',""];
        }

        $time_where = [];
        if (!empty($begin_time) && empty($end_time)){
            $time_where['bc.created_at'] = ['EGT',$begin_time];//大于
        }

        if (empty($begin_time) && !empty($end_time)){
            $time_where['bc.created_at'] = ['ELT',$end_time];//小于等于
        }

        if (!empty($begin_time) && !empty($end_time)){
            $time_where['bc.created_at'] = ['BETWEEN',"$begin_time,$end_time"];//时间区间
        }


        $card_type_where = [];

        if (!empty($card_type)){
            $card_type_where['bcf.card_type'] = ['eq',$card_type];
        }

        $pay_type_where = [];
        if (!empty($pay_type)){
            $pay_type_where['bc.pay_type'] = ['eq',$pay_type];
        }

        $config = [
            "page" => $nowPage,
        ];

        $billCardModel = new BillCardFees();

        $count = $billCardModel->count();//总记录数

        $pageNum = ceil($count/$pagesize); //总页数

        $where = [];
        if (!empty($keyword)){
            $where['bc.vid|u.name|u.nickname|bcf.card_name'] = ["like","%$keyword%"];
        }

        $status_where = [];
        if ($status != NULL){
            if ($status == config("order.open_card_type")['completed']['key']){
                $status_where['bc.sale_status'] = ["IN",$status];
            }else{
                $status_where['bc.sale_status'] = ["eq",$status];

            }
        }

        $get_column       = $billCardModel->get_column;
        $card_gift_column = $billCardModel->card_gift_column;
        $user_column      = $billCardModel->user_column;

        $list = $billCardModel
            ->alias('bc')
            ->join('user u','u.uid = bc.uid')
            ->join('bill_card_fees_detail bcf','bcf.vid = bc.vid')
            ->where($where)
            ->where($status_where)
            ->where($pay_type_where)
            ->where($card_type_where)
            ->where($time_where)
            ->where($gift_ship_where)
            ->field($get_column)
            ->field($user_column)
            ->field($card_gift_column)
            ->paginate($pagesize,false,$config);

        $list = json_decode(json_encode($list),true);

        //获取付款方式
        $pay_type_arr = config("order.pay_method");

        //获取卡类型
        $card_type_arr = config("card.type");

        //获取订单状态
        $sale_status_arr = config("order.open_card_status");

        //获取发货类型
        $send_type_arr = config("order.send_type");

        for ($i = 0; $i<count($list['data']); $i++){
            /*名字电话编辑 on*/
            $name = $list['data'][$i]['name'];
            $phone = $list['data'][$i]['phone'];
            if (!empty($name)){
                $list['data'][$i]['phone_name'] = $name . " " . $phone;
            }else{
                $list['data'][$i]['phone_name'] = $phone;
            }
            /*名字电话编辑 off*/

            /*默认头像 begin*/
            $avatar = $list['data'][$i]['avatar'];
            if (empty($avatar)){
                $list['data'][$i]['avatar'] = Env::get("DEFAULT_AVATAR_URL")."avatar.jpg";
            }
            /*默认头像 off*/

            /*支付类型翻译 begin*/
            $pay_type = $list['data'][$i]['pay_type'];
            foreach ($pay_type_arr as $key => $value){

                if ($pay_type == $key){
                    $list['data'][$i]['pay_type_name'] = $value['name'];
                }
            }
            /*支付类型翻译 off*/

            /*卡种翻译 begin*/
            $card_type_s = $list['data'][$i]['card_type'];

            foreach ($card_type_arr as $key => $value){
                if ($card_type_s == $value["key"]){
                    $list['data'][$i]['card_type_name'] = $value["name"];
                }
            }
            /*卡种翻译 off*/

            /*状态翻译 begin*/
            $sale_status_s = $list['data'][$i]['sale_status'];
            foreach ($sale_status_arr as $key => $value){
                if ($sale_status_s == $value['key']){
                    $list['data'][$i]['sale_status_name'] = $value['name'];
                }
            }
            /*状态翻译 off*/

            /*发货类型翻译 begin*/
            $send_type_s = $list['data'][$i]['send_type'];
            foreach ($send_type_arr as $key => $value){
                if ($send_type_s == $value['key']){
                    $list['data'][$i]['send_type_name'] = $value['name'];
                }
            }
            /*发货类型翻译 off*/

            /*用户操作日子 begin*/
            $vid = $list['data'][$i]['vid'];
            $log_info = Db::name('sys_adminaction_log')
                ->where('oid',$vid)
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
            /*用户操作日子 off*/

        }

        $list['filter'] = [
            "status"     => $status,
            "keyword"    => $keyword,
            "pay_type"   => $pay_type,
            "card_type"  => $card_type,
            "begin_time" => $begin_time,
            "end_time"   => $end_time,
        ];

        return $this->com_return(true,config("params.SUCCESS"),$list);
    }

    /**
     * 发货操作
     * @param Request $request
     * @return array
     */
    public function ship(Request $request)
    {
        $vid                = $request->param('vid','');
        $send_type          = $request->param("send_type",'');//赠品发货类型   ‘express’ 快递 ‘salesman’销售代收
        $express_company    = $request->param('express_company','');//收货人物流公司
        $express_code       = $request->param('express_code','');//物流公司编码
        $express_number     = $request->param('express_number','');//物流单号
        $delivery_name      = $request->param('delivery_name','');//收货人姓名
        $delivery_phone     = $request->param('delivery_phone','');//收货人电话
        $delivery_area      = $request->param('delivery_area','');//收货人区域
        $delivery_address   = $request->param('delivery_address','');//收货人详细地址
        $express_name       = $request->param('express_name','');//代收货人姓名

        if (empty($vid)){
            return $this->com_return(false,config("params.PARAM_NOT_EMPTY"));
        }
        if (empty($send_type)){
            return $this->com_return(false,config("params.CHECK_SHIP_TYPE"));
        }

        $time = time();

        //获取自动收货时间
        $openCardObj = new OpenCard();//
        $card_auto_delivery_day = $openCardObj->getSysSettingInfo("card_auto_delivery_day");
        $auto_finish_time = $time + $card_auto_delivery_day * 24 * 60 * 60;

        if ($send_type == config("order.send_type")['express']['key']){

            //快递发货
            $rule = [
                "express_company|收货人物流公司"    => "require|max:50",
                "express_code|物流公司编码"         => "require",
                "express_number|物流单号"          => "require|unique:bill_card_fees|max:50",
                "delivery_name|收货人姓名"         => "require",
                "delivery_phone|收货人电话"        => "require|regex:1[3-8]{1}[0-9]{9}",
                "delivery_area|收货人区域"         => "require",
                "delivery_address|收货人详细地址"   => "require|max:200",
            ];

            $check_params = [
                "express_company"   => $express_company,
                "express_code"      => $express_code,
                "express_number"    => $express_number,
                "delivery_name"     => $delivery_name,
                "delivery_phone"    => $delivery_phone,
                "delivery_area"     => $delivery_area,
                "delivery_address"  => $delivery_address,
            ];

            $validate = new Validate($rule);

            if (!$validate->check($check_params)){
                return $this->com_return(false,$validate->getError(),null);
            }

            $updated_params = [
                "delivery_time"     => $time,
                "auto_finish_time"  => $auto_finish_time,
                "send_type"         => config("order.send_type")['express']['key'],
                "sale_status"       => config("order.open_card_status")['pending_receipt']['key'],//改为待收货状态
                "delivery_name"     => $delivery_name,
                "delivery_phone"    => $delivery_phone,
                "delivery_area"     => $delivery_area,
                "delivery_address"  => $delivery_address,
                "express_company"   => $express_company,
                "express_code"      => $express_code,
                "express_number"    => $express_number,
                "updated_at"        => $time

            ];

        }else{
            //销售代收
            if (empty($express_name)){
                return $this->com_return(false,config("params.INSTEAD_SALES_NAME"));
            }

            $updated_params = [
                "express_name"      => $express_name,
                "auto_finish_time"  => $auto_finish_time,
                "sale_status"       => config("order.open_card_status")['pending_receipt']['key'],//改为待收货状态
                "delivery_time"     => $time,
                "send_type"         => config("order.send_type")['salesman']['key'],
                "updated_at"        => $time
            ];
        }

        $billCardFeesModel = new BillCardFees();

        $is_ok = $billCardFeesModel
            ->where('vid',$vid)
            ->where('sale_status',config('order.open_card_status')['pending_ship']['key'])
            ->update($updated_params);
        if ($is_ok){
            //记日志
            //获取当前登录管理员
            $action_user = $this->getLoginAdminId($request->header('Authorization'))['user_name'];
            $action = config("useraction.ship")['key'];
            $reason = config("useraction.ship")['name'];
            $this->addSysAdminLog("","","$vid","$action","$reason","$action_user","$time");

            return $this->com_return(true,config("params.SUCCESS"));
        }else{
            return $this->com_return(false,config("params.FAIL"));
        }
    }

    /**
     * 收款操作
     * @param Request $request
     * @return array
     */
    public function adminPay(Request $request)
    {
        if ($request->method() == "OPTIONS"){
            return $this->com_return(true,config("params.SUCCESS"));
        }

        $Authorization    = $request->header("Authorization");

        $notifyType       = $request->param('notifyType','adminCallback');//后台支付回调类型参数

        $vid              = $request->param('vid','');

        $payable_amount   = $request->param('payable_amount','');//线上应付且未付金额
        $payable_amount   = $payable_amount * 100;//(以分为单位)

        $pay_type         = $request->param('pay_type','');//支付方式 微信‘wxpay’ 支付宝 ‘alipay’ 线下银行转账 ‘bank’ 现金‘cash’

        $pay_no           = $request->param('pay_no','');//支付回单号

        $pay_name         = $request->param('pay_name','');//付款人或公司名称
        $pay_bank         = $request->param('pay_bank','');//付款方开户行
        $pay_account      = $request->param('pay_account','');//付款方账号
        $pay_bank_time    = $request->param('pay_bank_time','');//银行转账付款时间或现金支付时间
        $receipt_name     = $request->param('receipt_name','');//收款账户或收款人
        $receipt_bank     = $request->param('receipt_bank','');//收款银行
        $receipt_account  = $request->param('receipt_account','');//收款账号

        $delivery_name    = $request->param('delivery_name','');//收货人姓名
        $delivery_phone   = $request->param('delivery_phone','');//收货人电话
        $delivery_area    = $request->param('delivery_area','');//收货人区域
        $delivery_address = $request->param('delivery_address','');//收货人详细地址

        $reason           = $request->param('reason','');//操作原因

        $billCardFeesModel = new BillCardFees();

        $public_rule = [
            'vid|订单号'                      => 'require',
            'payable_amount|线上应付且未付金额' => 'require',
            'pay_type|支付方式'                => 'require',
//            'delivery_name|收货人姓名'         => 'require',
//            'delivery_phone|收货人电话'        => 'regex:1[3-8]{1}[0-9]{9}',
//            'delivery_area|收货人区域'         => 'require',
//            'delivery_address|收货人详细地址'   => 'require',
            'reason|操作原因'                  => 'require',
        ];

        $check_public_params = [
            "vid"               => $vid,
            "payable_amount"    => $payable_amount,
            "pay_type"          => $pay_type,
//            "delivery_name"     => $delivery_name,
//            "delivery_phone"    => $delivery_phone,
//            "delivery_area"     => $delivery_area,
//            "delivery_address"  => $delivery_address,
            "reason"            => $reason,
        ];

        $validate = new Validate($public_rule);

        if (!$validate->check($check_public_params)){
            return $this->com_return(false,$validate->getError(),null);
        }

        $time = time();

        //如果是微信支付
        if ($pay_type == config('order.pay_method')['wxpay']['key']){

            $pay_rule = [
                'pay_no|支付回单号' => 'require|unique:bill_card_fees'
            ];

            $check_pay_params = [
                'pay_no' => $pay_no
            ];

            $pay_validate = new Validate($pay_rule);

            if (!$pay_validate->check($check_pay_params)){
                return $this->com_return(false,$pay_validate->getError(),null);
            }

            $res = $this->callBackPay("$Authorization","$notifyType","$vid","$payable_amount","$payable_amount","$reason","$pay_no");

            $res= json_decode(json_encode(simplexml_load_string($res, 'SimpleXMLElement', LIBXML_NOCDATA)), true);

            if ($res['return_code'] == "SUCCESS"){
                //如果支付成功
                //更改订单支付信息
                $orderParams = [
                    'pay_type'         => $pay_type,
                    'pay_no'           => $pay_no,
                    'delivery_name'    => $delivery_name,
                    'delivery_phone'   => $delivery_phone,
                    'delivery_area'    => $delivery_area,
                    'delivery_address' => $delivery_address,
                    'updated_at'       => time()
                ];

                $is_ok = $billCardFeesModel
                    ->where('vid',$vid)
                    ->update($orderParams);

                if ($is_ok !== false){

                    /*//记日志
                    $action_user = $this->getLoginAdminId($request->header('Authorization'))['user_name'];
                    $action = config("useraction.deal_pay")['key'];
                    $this->addSysAdminLog("","","$vid","$action","$reason","$action_user","$time");*/


                    return $this->com_return(true,$res['return_msg']);
                }else{
                    return $this->com_return(false,config("params.FAIL"));
                }
            }else{
                return $this->com_return(false,$res['return_msg']);
            }
        }

        //如果是支付宝支付
        if ($pay_type == config('order.pay_method')['alipay']['key']){

            $pay_rule = [
                'pay_no|支付回单号' => 'require|unique:bill_card_fees'
            ];

            $check_pay_params = [
                'pay_no' => $pay_no
            ];

            $pay_validate = new Validate($pay_rule);

            if (!$pay_validate->check($check_pay_params)){
                return $this->com_return(false,$pay_validate->getError(),null);
            }

            $res = $this->callBackPay("$Authorization","$notifyType","$vid","$payable_amount","$payable_amount","$reason","$pay_no");

            $res= json_decode(json_encode(simplexml_load_string($res, 'SimpleXMLElement', LIBXML_NOCDATA)), true);

            if ($res['return_code'] == "SUCCESS"){
                //如果支付成功
                //更改订单支付信息
                $orderParams = [
                    'pay_type'         => $pay_type,
                    'pay_no'           => $pay_no,
                    'delivery_name'    => $delivery_name,
                    'delivery_phone'   => $delivery_phone,
                    'delivery_area'    => $delivery_area,
                    'delivery_address' => $delivery_address,
                    'updated_at'       => time()
                ];

                $is_ok = $billCardFeesModel
                    ->where('vid',$vid)
                    ->update($orderParams);
                if ($is_ok !== false){

                    //记日志
                    $action_user = $this->getLoginAdminId($request->header('Authorization'))['user_name'];
                    $action = config("useraction.deal_pay")['key'];
                    $this->addSysAdminLog("","","$vid","$action","$reason","$action_user","$time");

                    return $this->com_return(true,$res['return_msg']);
                }else{
                    return $this->com_return(false,config("params.FAIL"));
                }
            }else{
                return $this->com_return(false,$res['return_msg']);
            }
        }

        //如果是线下银行支付
        if ($pay_type == config('order.pay_method')['bank']['key']){
            $bank_rule = [
                'pay_no|支付回单号'              => 'require|unique:bill_card_fees',
                'pay_name|付款人或公司名称'       => 'require',
                'pay_bank|付款方开户行'          => 'require',
                'pay_account|付款方账号'         => 'require',
                'pay_bank_time|银行转账付款时间'  => 'require',
                'receipt_name|收款账户或收款人'   => 'require',
                'receipt_bank|收款银行'          => 'require',
                'receipt_account|收款账号'       => 'require',
            ];

            $check_bank_params = [
                'pay_no'            => $pay_no,
                'pay_name'          => $pay_name,
                'pay_bank'          => $pay_bank,
                'pay_account'       => $pay_account,
                'pay_bank_time'     => $pay_bank_time,
                'receipt_name'      => $receipt_name,
                'receipt_bank'      => $receipt_bank,
                'receipt_account'   => $receipt_account,
            ];

            $bank_validate = new Validate($bank_rule);

            if (!$bank_validate->check($check_bank_params)){
                return $this->com_return(false,$bank_validate->getError(),null);
            }

            $res = $this->callBackPay("$Authorization","$notifyType","$vid","$payable_amount","$payable_amount","$reason");

            $res= json_decode(json_encode(simplexml_load_string($res, 'SimpleXMLElement', LIBXML_NOCDATA)), true);

            if ($res['return_code'] == "SUCCESS"){
                //如果支付成功
                //更改订单支付信息
                $orderParams = [
                    'pay_type'        => $pay_type,
                    'pay_no'          => $pay_no,
                    'pay_name'        => $pay_name,
                    'pay_bank'        => $pay_bank,
                    'pay_account'     => $pay_account,
                    'pay_bank_time'   => $pay_bank_time,
                    'receipt_name'    => $receipt_name,
                    'receipt_bank'    => $receipt_bank,
                    'receipt_account' => $receipt_account,
                    'updated_at'      => time()
                ];

                $is_ok = $billCardFeesModel
                    ->where('vid',$vid)
                    ->update($orderParams);
                if ($is_ok !== false){

                    /*//记日志
                    $action_user = $this->getLoginAdminId($request->header('Authorization'))['user_name'];
                    $action = config("useraction.deal_pay")['key'];
                    $this->addSysAdminLog("","","$vid","$action","$reason","$action_user","$time");*/

                    return $this->com_return(true,$res['return_msg']);
                }else{
                    return $this->com_return(false,config("params.FAIL"));
                }
            }else{
                return $this->com_return(false,$res['return_msg']);
            }
        }

        //cash 现金支付
        if ($pay_type == config('order.pay_method')['cash']['key']){

            $cash_rule = [
                'pay_name|付款人或公司名称' => 'require',
                'pay_bank_time|付款时间'   => 'require',
                'receipt_name|收款人'      => 'require',
            ];

            $check_cash_params = [
                'pay_name'          => $pay_name,
                'pay_bank_time'     => $pay_bank_time,
                'receipt_name'      => $receipt_name,
            ];

            $cash_validate = new Validate($cash_rule);

            if (!$cash_validate->check($check_cash_params)){
                return $this->com_return(false,$cash_validate->getError(),null);
            }

            $res = $this->callBackPay("$Authorization","$notifyType","$vid","$payable_amount","$payable_amount","$reason");

            $res= json_decode(json_encode(simplexml_load_string($res, 'SimpleXMLElement', LIBXML_NOCDATA)), true);

            if ($res['return_code'] == "SUCCESS"){
                //如果支付成功
                //更改订单支付信息
                $orderParams = [
                    'pay_type'         => $pay_type,
                    'pay_name'         => $pay_name,
                    'pay_bank_time'    => $pay_bank_time,
                    'receipt_name'     => $receipt_name,
                    'delivery_name'    => $delivery_name,
                    'delivery_phone'   => $delivery_phone,
                    'delivery_area'    => $delivery_area,
                    'delivery_address' => $delivery_address,
                    'updated_at'       => time()
                ];

                $is_ok = $billCardFeesModel
                    ->where('vid',$vid)
                    ->update($orderParams);
                if ($is_ok !== false){

                   /* //记日志
                    $action_user = $this->getLoginAdminId($request->header('Authorization'))['user_name'];
                    $action = config("useraction.deal_pay")['key'];
                    $this->addSysAdminLog("","","$vid","$action","$reason","$action_user","$time");*/

                    return $this->com_return(true,$res['return_msg']);
                }else{
                    return $this->com_return(false,config("params.FAIL"));
                }
            }else{
                return $this->com_return(false,$res['return_msg']);
            }
        }

        //其他付款方式 返回错误
        return $this->com_return(false,config("params.FAIL"));
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
        $values = [
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

    /**
     * 获取快递公司列表
     * @return false|mixed|PDOStatement|string|\think\Collection
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    function getLogisticsCompany()
    {
        $list = Db::name("mst_express")
            ->where("is_enable",1)
            ->where("is_delete",0)
            ->field("express_id,express_code,express_name")
            ->select();
        $list = json_decode(json_encode($list),true);
        return $this->com_return(true,config("params.SUCCESS"),$list);
    }
}