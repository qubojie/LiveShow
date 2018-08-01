<?php
/**
 * 去开卡
 * User: qubojie
 * Date: 2018/6/28
 * Time: 下午2:07
 */
namespace app\wechat\controller;

use app\admin\controller\Common;
use app\admin\model\ManageSalesman;
use app\admin\model\MstCardVip;
use app\admin\model\MstGift;
use app\admin\model\MstSalesmanType;
use app\admin\model\User;
use app\common\controller\UUIDUntil;
use app\wechat\model\BillCardFees;
use app\wechat\model\BillCardFeesDetail;
use app\wechat\model\SysSetting;
use think\Controller;
use think\Db;
use think\Exception;
use think\Request;
use think\Validate;

class OpenCard extends Controller
{
    /**
     * 点击去开卡,获取相应参数,
     * @param Request $request
     * @return array
     */
    public function index(Request $request)
    {
        //开卡
        $cardCallbackObj = new CardCallback();
        $UUIDObj         = new UUIDUntil();
        $common          = new Common();

        $time = time();

        $uid              = $request->param("uid","");//用户id
        $name             = $request->param('name',"");//用户真实姓名
        $card_id          = $request->param("card_id","");//卡id
        $gift_id          = $request->param("gift_id","");//开卡礼id
        $delivery_name    = $request->param("delivery_name","");//收货人姓名
        $delivery_phone   = $request->param("delivery_phone","");// 收货人电话
        $delivery_area    = $request->param("delivery_area","");//区
        $delivery_address = $request->param("delivery_address","");//详细地址


        $card_amount      = $request->param("card_amount","");//详细地址

        if (empty($uid)){
            return $this->com_return(false,config("params.PARAM_NOT_EMPTY"));
        }
        if (empty($name)){
            return $this->com_return(false,config("params.NAME_NOT_EMPTY"));
        }

        //如果选择可开卡礼,则验证收货人信息
        if (!empty($gift_id)){
            $rule = [
                "delivery_name|收货人姓名"   => "require",
                "delivery_phone|收货人电话"  => "require|regex:1[3-8]{1}[0-9]{9}",
                "delivery_area|区"          => "require",
                "delivery_address|详细地址"  => "require",
            ];

            $request_res = [
                "delivery_name"    => $delivery_name,
                "delivery_phone"   => $delivery_phone,
                "delivery_area"    => $delivery_area,
                "delivery_address" => $delivery_address,
            ];

            $validate = new Validate($rule);

            if (!$validate->check($request_res)){
                return $common->com_return(false,$validate->getError(),null);
            }

        }

        //判断用户是否已开卡
        $userModel = new User();
        $is_open_card = $userModel
            ->where('uid',$uid)
            ->where('user_status',config("user.user_status")[2]['key'])
            ->count();

        if ($is_open_card) return $this->com_return(false,'该用户成功开卡');

        //首先判断此用户是否已有待支付订单
        $res = $this->checkUserIsOpenCard($uid);

        if (!empty($res)){
            return $this->com_return(true,'已开卡',$res);
        }

        //①获取卡信息
        $card_info      = $this->getCardInfo($card_id);
        $card_info      = json_decode($card_info,true);

        $cardInfo_amount  = $card_info["card_amount"];//获取卡的基本金额

        if ($card_amount < $cardInfo_amount){
            //如果储值金额小于基本储值金额,则返回储值金额无效
            return $this->com_return(true,config("params.RECHARGE_MONEY_INVALID"));
        }

        //②获取礼品信息
        $gift_info   = $this->getGiftInfo($gift_id);

        $gift_info   = json_decode($gift_info,true);



        /**
         * @param array $params
         * @param vid               '充值缴费单 V前缀'
         * @param uid               '用户id'
         * @param sales_type        '推荐人类型    ‘vip’ 会籍  ‘sales’营销  ‘user’ 会员  ‘null’ 无'
         * @param sid               '推荐人id'
         * @param card_id           '缴费开通卡种id'
         * @param gift_id           '礼品id'
         * @param cus_remark        '买家留言 暂时保留'
         * @param sale_status       '单据状态  0待付款    1 付款完开卡赠品待发货   2赠品已发货待收货  3 赠品收货确认   9交易取消'
         * @param deal_time         '成交时间'
         * @param pay_time          '付款时间'
         * @param cancel_user       '取消人，系统‘sys’ ，货主‘self’ ，回收人‘recycle’ ，悦商客服 ‘管理员用户名’'
         * @param cancel_time       '单据取消时间'
         * @param auto_cancel       '是否自动取消  0 手工取消  1自动取消'
         * @param cancel_reason     '订单取消原因   系统自动取消（“时限内未付款”）'
         * @param finish_time       '订单完成时间'
         * @param order_amount      '订单金额（计算应等于 order_amount = payable_amount + discount）'
         * @param payable_amount    '线上应付且未付金额'
         * @param deal_price        '实付金额'
         * @param discount          '折扣 暂保留'
         * @param pay_type          '支付方式   微信‘wxpay’    支付宝 ‘alipay’    线下银行转账 ‘bank’   现金‘cash’'
         * @param pay_no            '支付回单号（对方流水单号）'
         * @param pay_name          '付款人或公司名称'
         * @param is_settlement     '是否结算佣金  0未结算   1已结算   没有邀请人的默认1已结算'
         * @param commission_ratio  '下单时的佣金比例   百分比整数     没有推荐人的自动为0'
         * @param send_type         '赠品发货类型   ‘express’ 快递     ‘salesman’销售'
         * @param delivery_name     '收货人姓名'
         * @param delivery_phone    '收货人电话'
         * @param delivery_area     '收货人区域'
         * @param delivery_address  '收货人详细地址'
         * @param express_company   '收货人物流公司'
         * @param express_number    '物流单号'
         * @param express_name      '开卡礼品id'
         * @param commission        '支付给 推荐人佣金金额  没推荐人的自动为0'
         * @param created_at        '数据创建时间'
         * @param updated_at        '最后更新时间'
         */

        Db::startTrans();
        try{
            $referrer_info = $this->getSalesmanId($uid);

            $referrer_info = json_decode($referrer_info,true);

            if (!empty($referrer_info["referrer_id"])){

                $referrer_id = $referrer_info['referrer_id'];
                $referrer_type = $referrer_info['referrer_type'];
                $commission_ratio = 0;
                /*if ($referrer_type == 'user'){
                    //如果是用户推荐,获取礼金比例
                    $commission_ratio = $this->getSysSettingInfo('card_user_commission_ratio');
                }elseif($referrer_type == 'vip' || $referrer_type=='sales'){
                    //如果是销售或会籍推荐,获取相应佣金比例
                    $commission_ratio = $this->getCommissionRatio($referrer_type);
                }else{
                    $commission_ratio = 0;
                }*/



            }else{
                $referrer_id = "";
                $referrer_type = "empty";
                $commission_ratio = 0;
            }

            //获取自动取消分钟数
            $cardAutoCancelMinutes = $this->getSysSettingInfo("card_auto_cancel_time");
            //将分钟数转换为秒
            $cardAutoCancelTime = $cardAutoCancelMinutes * 60;

            $discount = 0;//折扣金额
            //③生成缴费订单,创建发货订单
            $billCardFeesParams = [
                'vid'             => $UUIDObj->generateReadableUUID("V"),//充值缴费单 V前缀
                'uid'             => $uid,//用户id
                'referrer_type'   => $referrer_type,//推荐人类型
                'referrer_id'     => $referrer_id,//推荐人id
                'sale_status'     => config("order.open_card_status")['pending_payment']['key'],//单据状态
                'deal_time'       => $time,//成交时间
                'auto_cancel_time'=> time()+$cardAutoCancelTime,//单据自动取消的时间
                'order_amount'    => $card_amount,//订单金额
                'discount'        => $discount,//折扣,暂且为0
                'payable_amount'  => $card_amount - $discount,//线上应付且未付金额
                'pay_type'        => config("order.pay_method")['wxpay']['key'],
                'is_settlement'   => $referrer_type == "empty" ? 1 : 0 ,//是否结算佣金
                'commission_ratio'=> $commission_ratio,//下单时的佣金比例   百分比整数     没有推荐人的自动为0
                'commission'      => ($card_amount - $discount) * $commission_ratio / 100,
                'send_type'       => config("order.send_type")['express']['key'],//赠品发货类型
                'delivery_name'   => $delivery_name,//收货人姓名
                'delivery_phone'  => $delivery_phone,//收货人电话
                'delivery_area'   => $delivery_area,//收货人区
                'delivery_address'=> $delivery_address,//收货详细地址
                'created_at'      => $time,//创建时间
                'updated_at'      => $time,//更新时间
            ];


            //返回订单id
            $billCardFeesReturn = $cardCallbackObj->billCardFees($billCardFeesParams);


            if ($billCardFeesReturn == false){
                return $common->com_return(false,'开卡失败');
            }

            //将礼品信息写入 bill_card_fees_detail表中
            $billCardFeesDetailParams = [
                'vid'           => $billCardFeesReturn,
                'card_id'       => $card_id,
                'card_type'     => $card_info["card_type"],//卡片类型   ‘vip’会籍卡      ‘value’ 储值卡
                'card_name'     => $card_info["card_name"],//VIP卡名称
                'card_level'    => $card_info["card_level"],//vip卡级别名称
                'card_image'    => $card_info["card_image"],//VIP卡背景图
                'card_no_prefix'=> $card_info["card_no_prefix"],//卡号前缀（两位数字）
                'card_desc'     => $card_info["card_desc"],//VIP卡使用说明及其他描述
                'card_equities' => $card_info["card_equities"],//卡片享受权益详情
                'card_deposit'  => $card_info["card_deposit"],//卡片权益保证金额

                'card_amount'         => $card_amount,//充值金额
                'card_point'          => $card_info["card_point"],//开卡赠送积分
                'card_cash_gift'      => $card_info["card_cash_gift"],//开卡赠送礼金数
                'card_job_cash_gif'   => $card_info["card_job_cash_gif"],//推荐人返佣礼金
                'card_job_commission' => $card_info["card_job_commission"],//推荐人返佣金

                'gift_id'       => $gift_id,
                'gift_img'      => $gift_info["gift_img"],//礼品图片
                'gift_name'     => $gift_info["gift_name"],//礼品名称标题
                'gift_desc'     => $gift_info["gift_desc"],//礼品描述
                'gift_amount'   => $gift_info["gift_amount"]////礼品价格
            ];


            $billCardFeesDetailReturn = $cardCallbackObj->billCardFeesDetail($billCardFeesDetailParams);

            if ($billCardFeesDetailReturn){
                //更改用户user_status为 1 提交订单状态
                $updateUserInfoParams = [
                    'user_status' => config('user.user_status')['1']['key'],
                    'name'        => $name
                ];
                $updateUserInfoReturn = $cardCallbackObj->updateUserInfo($updateUserInfoParams,$uid);

                if ($updateUserInfoReturn){

                    Db::commit();

                    $cardCallBackObj = new CardCallback();

                    $referrer_info = $cardCallBackObj->getUserCardInfo($uid,'0');

                    return $common->com_return(true,'请支付',$referrer_info);

                }else{

                    return $common->com_return(false,'开卡失败');

                }

            }else{

                return $common->com_return(false,'开卡失败');

            }

        }catch (Exception $e){

            Db::rollback();
            return $common->com_return(false,$e->getMessage());

        }
    }

    /**
     * 获取卡信息
     * @param $card_id
     * @return array
     */
    public function getCardInfo($card_id)
    {
        $cardVipModel = new MstCardVip();

        $column = $cardVipModel->column;

        $card_info = $cardVipModel->where('card_id',$card_id)->field($column)->find();

        return $card_info;
    }

    /**
     * 获取指定礼品信息
     * @param $gift_id
     * @return array|false|\PDOStatement|string|\think\Model
     */
    public function getGiftInfo($gift_id)
    {
        $giftModel = new MstGift();

        $column = $giftModel->column;

        $gift_info = $giftModel->where('gift_id',$gift_id)->field($column)->find();

        return $gift_info;
    }

    /**
     * 获取所有有效礼品信息
     * @param Request $request
     * @return array
     */
    public function getGiftListInfo(Request $request)
    {
        $giftModel = new MstGift();
        $column = $giftModel->column;
        $gift_info = $giftModel
            ->where('is_enable',1)
            ->where('is_delete',0)
            ->field($column)
            ->select();

        return $this->com_return(true,config('params.SUCCESS'),$gift_info);

    }

    /**
     * 获取推荐人信息
     * @param $uid
     * @return string
     */
    public function getSalesmanId($uid)
    {
        $userModel = new User();
        $res = $userModel->where('uid',$uid)
            ->field('referrer_id,referrer_type')
            ->find();
        return $res;
    }

    /**
     * 获取积分比例
     * @param $stype_key '类型'
     * @return int
     */
    public function getCommissionRatio($stype_key)
    {
        $salesmanTypeModel = new MstSalesmanType();
        $info = $salesmanTypeModel
            ->where('stype_key',$stype_key)
            ->field('commission_ratio')
            ->find();
        $info = json_decode($info,true);
        return $info['commission_ratio'];
    }

    /**
     * 获取系统设置相关key值
     * @param $key
     * @return mixed
     */
    public function getSysSettingInfo($key)
    {
        $settingModel = new SysSetting();

        $res = $settingModel
            ->where('key',$key)
            ->field('value')
            ->find()
            ->toArray();

        $value = $res['value'];
        return $value;
    }

    /**
     * 获取当前用户是否已经开卡,且订单未支付
     * @param $uid
     * @return array
     */
    protected function checkUserIsOpenCard($uid)
    {
        $callBackObj = new CardCallback();

        $res = $callBackObj->getUserCardInfo($uid,0);

        return $res;
    }

}