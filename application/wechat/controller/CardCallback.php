<?php
/**
 * 卡片信息公共调用.
 * User: qubojie
 * Date: 2018/6/28
 * Time: 上午10:26
 */

namespace app\wechat\controller;

use app\admin\model\User;
use app\wechat\model\BillCardFees;
use app\wechat\model\BillCardFeesDetail;
use app\wechat\model\JobAccount;
use app\wechat\model\JobUser;
use app\wechat\model\UserAccount;
use app\wechat\model\UserAccountCashGift;
use app\wechat\model\UserAccountDeposit;
use app\wechat\model\UserAccountPoint;
use app\wechat\model\UserCard;
use app\wechat\model\UserCardHistory;
use app\wechat\model\UserGiftVoucher;
use think\Controller;
use think\Db;

class CardCallback extends Controller
{

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
     *
     * @return string
     */
    public function  billCardFees($params = array())
    {
        //dump($params);die;
        $billCardFeesModel = new BillCardFees();

        $is_ok = $billCardFeesModel->insert($params);
        if ($is_ok){
            return $params['vid'];
        }else{
            return false;
        }
    }

    /**
     * 更新缴费订单礼品礼券快照
     * @param array $params
     * @return bool
     */
    public function billCardFeesDetail($params = array())
    {
        //return $params;
        $billCardFeesDetailModel = new BillCardFeesDetail();

        $is_ok = $billCardFeesDetailModel->insert($params);

        return $is_ok;

    }



    /**************支付回调内调用方法***************/

    /**
     * 更新订单状态
     * @param array $params
     * @param $vid
     * @return bool
     */
    public function updateOrderStatus($params = array(),$vid)
    {
        $billCardFeesModel = new BillCardFees();


        $is_ok = $billCardFeesModel
            ->where('vid',$vid)
            ->update($params);
        if ($is_ok){
            return true;

        }else{
            return false;
        }

    }

    /**
     * 添加或更新开卡信息
     *
     * @table el_user_card
     *
     * @param uid '用户id'
     * @param card_no '卡号'
     * @param card_id '充值卡id'
     * @param card_type '卡片类型' 'vip'会籍卡 'value' 储值卡
     * @param card_name '充值卡名称标题'
     * @param card_image '充值卡背景图'
     * @param card_amount '充值金额'
     * @param card_deposit '会员权益保证金额'
     * @param card_desc '充值卡使用说明及其他描述'
     * @param is_valid '是否有效'
     * @param valid_time '有效时间 0 表示长期有效'
     * @param created_at '数据创建时间'
     * @param updated_at '最后更新时间'
     *
     * @return string
     * */
    public function updateCardInfo($params = array())
    {
        $userCardModel        = new UserCard();
        $userCardHistoryModel = new UserCardHistory();

        //查看当前用户是否已经办过卡
        $is_exist = $userCardModel
            ->where('uid',$params['uid'])
            ->find();


        $is_exist = json_decode($is_exist,true);

        if (!empty($is_exist)){

            //将旧数据写入user_card_history历史表
            $is_exist["record_time"] = time();

            $userCardHistoryModel->insert($is_exist);

            //存在则更新
            $is_ok = $userCardModel
                ->where('uid',$params['uid'])
                ->update($params);
        }else{
            //不存在则新增
            $params['created_at'] = $params['updated_at'];
            $is_ok = $userCardModel
                ->insert($params);
        }

        return $is_ok;

    }

    /**
     * 添加或更新用户余额账户及明细账
     * @table el_user
     * @param uid             '用户id'
     * @param account_balance '用户钱包可用余额'
     *
     * @table el_user_account
     * @param uid           '用户id'
     * @param balance       '账户可用余额变动  正加 负减'
     * @param last_balance  '变动后的钱包总余额'
     * @param freeze        '账户冻结金额变动  正加 负减'
     * @param last_freeze   '变动后的总冻结余额'
     * @param cash          '提现金额变动  正加 负减'
     * @param last_cash     '变动后的总提现金额'
     * @param change_type   '变更类型   0 用户操作  1管理员及销售员操作  2 系统自动'
     * @param action_user   '操作用户名  change_type=0 时 值为‘cus’     change_type=1 记录操作管理员登录名 change_type=2  时 值为‘sys’ '
     * @param action_type   '601 账户余额充值（余额账户+）
                             600 账户余额消费（余额账户-)
                             609 账户余额退款（余额账户+）
                             700 平台扣除订单佣金（余额账户-）
                             705 平台扣除提现手续费（余额账户-）
                             800 提现（冻结账户+  余额账户-）
                             801 提现完成（冻结账户- 提现账户+）
                             802 提现失败 （冻结账户-  余额账户+)
                             900 账务调整'
     * @param oid           '6XX  类动作 的相关缴费充值单号   80X相关的提现单号'
     * @param deal_amount   '成交金额'
     * @param charge        '平台佣金'
     * @param action_desc   '操作描述'
     * @param created_at    '数据创建时间'
     * @param updated_at    '最后更新时间'
     *
     * @return string
     * */
    public function updateUserAccount($params = array())
    {
        $userAccountModel = new UserAccount();

        $is_ok = $userAccountModel
            ->insert($params);
        if ($is_ok){
            return true;
        }else{
            return false;
        }

    }

    /**
     * 添加用户押金账户及明细账
     * @table el_user
     * @param account_deposit '用户会员押金余额'
     * @param updated_at      '最后更新时间'
     *
     * @table el_user_account_deposit
     * @param uid           '用户id'
     * @param deposit       '会员押金余额变动  正加 负减'
     * @param last_deposit  '变动后的押金总余额'
     * @param change_type   '变更类型   0 用户操作  1管理员及销售员操作  2 系统自动'
     * @param action_user   '操作用户名  change_type=0 时 值为‘cus’     change_type=1 记录操作管理员登录名 change_type=2  时 值为‘sys’ '
     * @param action_type   '100 缴纳担保金 200退还担保金 900 其他原因调整 '
     * @param action_desc   '操作描述'
     * @param oid           '1XX  类动作的相关缴费充值单号 20X相关的押金退款单号 '
     * @param created_at    '数据创建时间'
     * @param updated_at    '最后更新时间'
     *
     * @return  string
     */
    public function updateUserAccountDeposit($params = array())
    {
        $userAccountDepositModel = new UserAccountDeposit();

        $is_ok = $userAccountDepositModel
            ->insert($params);
        if ($is_ok){
            return true;
        }else{
            return false;
        }
    }

    /**
     *添加用户积分明细账
     * @table el_user
     * @param level_id          '用户等级id'
     * @param credit_point      '信用积分（用于标识用户信用等级）只增不减'
     * @param account_point     '用户积分账户余额'
     * @param account_point     '用户积分账户余额'
     * @param updated_at        '最后更新时间'
     *
     * @table le_mst_user_level '积分等级表,根据用户的积分在 point_min~point_max 区间,为用户升级等级,变更 level_id'
     *
     * @table el_user_account_point
     * @param uid           '用户id'
     * @param point         '账户可用余额变动  正加 负减'
     * @param last_deposit  '变动后的钱包总余积分'
     * @param change_type   '变更类型   0 用户操作  1管理员及销售员操作  2 系统自动'
     * @param action_user   '操作用户名  change_type=0 时 值为‘cus’     change_type=1 记录操作管理员登录名 change_type=2  时 值为‘sys’ '
     * @param action_type   '100 缴费积分收入   200 消费积分收入  201消费积分减少  900 其他原因调整     其他待定'
     * @param action_desc   '操作描述'
     * @param oid           '1XX  类动作 的相关缴费充值单号   20X相关的押金退款单号 '
     * @param created_at    '数据创建时间'
     * @param updated_at    '最后更新时间'
     * @return bool
     */
    public function updateUserAccountPoint($params = array())
    {
        $userAccountPointModel = new UserAccountPoint();

        $is_ok = $userAccountPointModel
            ->insert($params);

        if ($is_ok){
            return true;
        }else{
            return false;
        }

    }


    /**
     * 用户礼金明细
     * @param array $params
     * @param string $uid           '用户id'
     * @param string $cash_gift     '账户可用礼金变动  正加 负减'
     * @param string $last_cash_gift'变动后的礼金总余额'
     * @param string $change_type   '变更类型   0 用户操作  1管理员及销售员操作  2 系统自动'
     * @param string $action_user   '操作用户名  change_type=0 时 值为‘cus’     change_type=1 记录操作管理员登录名 change_type=2  时 值为‘sys’ '
     * @param string $action_type   '100 赠券兑换礼金+  200 礼金消费-   800 推荐会员赠送礼金+    900 其他原因调整     其他待定    '
     * @param string $action_desc   '操作描述'
     * @param string $oid           '1XX  类动作 的相关缴费充值单号   20X相关的押金退款单号 '
     * @param string $created_at
     * @param string $updated_at
     * @return bool
     */
    public function updateUserAccountCashGift($params = array())
    {
        $userAccountCashGiftModel = new UserAccountCashGift();

        $is_ok = $userAccountCashGiftModel
            ->insert($params);

        if ($is_ok){
            return true;
        }else{
            return false;
        }

    }

    /**
     * 下发赠券
     * @table el_user_gift_voucher
     * @param  gift_vou_code            '礼品券兑换编码'
     * @param  uid                      '用户id'
     * @param  gift_vou_id              '礼券id'
     * @param  gift_vou_type            '赠券类型  ‘once’单次    ‘multiple’多次   ‘limitless’ 无限制'
     * @param  gift_vou_name            '礼券名称标题'
     * @param  gift_vou_desc            '礼券详细描述'
     * @param  gift_vou_amount          '礼券金额'
     * @param  gift_vou_validity_start  '礼券有效开始日期'
     * @param  gift_vou_validity_end    '礼券有效结束日期'
     * @param  gift_vou_exchange        '兑换规则 （保存序列） '
     * @param  qty                      '赠送总数量   类型为‘once’单次时 数量为1   类型为‘limitless’   数量为0'
     * @param  use_qty                  '已使用数量'
     * @param  status                   '礼券状态  0有效待使用  1 已使用  9已过期'
     * @param  use_time                 '使用时间'
     * @param  review_user              '兑换审核人'
     * @param  created_at               '数据创建时间'
     * @param  updated_at               '最后更新时间'
     *
     */
    public function updateUserGiftVoucher($params = array())
    {
        $userGiftVoucherModel = new UserGiftVoucher();

        $is_ok = $userGiftVoucherModel
            ->insert($params);
        if ($is_ok){
            return true;
        }else{
            return false;
        }

    }

    /**
     * 更新或新增兼职推荐用户佣金表
     * @param $uid
     * @param $job_balance
     * @return bool
     */
    public function updateJobUser($uid,$job_balance)
    {
        $jobUserModel = new JobUser();

        $userInfo = $jobUserModel
            ->where('uid',$uid)
            ->count();

        $userInfo = json_decode(json_encode($userInfo),true);

        $time = time();

        $params['updated_at'] = $time;

        if ($userInfo){
            //如果存在,更新

            $is_ok = $jobUserModel
                ->where('uid',$uid)
                ->inc("job_balance","$job_balance")
                ->inc("referrer_num")
                ->exp("updated_at","$time")
                ->update();

        }else{
            //如果不存在,新增
            $params = [
                "uid"           => $uid,
                "job_balance"   => $job_balance,
                "referrer_num"  => 1,
                "created_at"    => $time,
                "updated_at"    => $time
            ];

            $is_ok = $jobUserModel
                ->insert($params);
        }

        if ($is_ok !== false){
            return true;
        }else{
            return false;
        }
    }

    /**
     * 新增兼职推荐用户佣金明细表
     * @param array $params
     * @return bool
     */
    public function insertJobAccount($params = array())
    {
        $jobAccountModel = new JobAccount();

        $is_ok = $jobAccountModel
            ->insert($params);

        if ($is_ok){
            return true;
        }else{
            return false;
        }
    }



    /**
     * 更新用户信息
     * @param array $params
     * @param string $uid
     * @return bool
     */
    public function updateUserInfo($params = array(),$uid)
    {
        $userModel = new User();
        $is_ok = $userModel
            ->where('uid',$uid)
            ->update($params);
        if ($is_ok){
            return true;
        }else{
            return false;
        }
    }

    /**
     * 获取用户开卡推荐人信息
     * @param $uid
     * @param $sale_status
     * @return array
     */
    public function getUserCardInfo($uid,$sale_status)
    {
        $column = [
            'vid',
            'uid',
            'referrer_id',
            'referrer_type',
            'payable_amount',
            'delivery_name',
            'delivery_phone',
            'delivery_area',
            'delivery_address',
            'created_at'
        ];

        $bill = Db::name('bill_card_fees')
            ->where('uid',$uid)
            ->where('sale_status',$sale_status)
            ->field($column)
            ->find();

        if (!empty($bill)){
            $vid = $bill['vid'];
            $bill_detail = Db::name('bill_card_fees_detail')
                ->where('vid',$vid)
                ->find();
            $bill['card_gift'] = $bill_detail;
        }else{
            return null;
        }
        //获取推荐人电话
        $referrer_id_res = Db::name('user')
            ->where('uid',$uid)
            ->field('referrer_type,referrer_id')
            ->find();

        if (!empty($referrer_id_res)){

            $referrer_type = $referrer_id_res['referrer_type'];
            $referrer_id   = $referrer_id_res['referrer_id'];

            if ($referrer_type == 'empty'){

                $referrer_info = array();
            }else{
                if ($referrer_type == 'user'){

                    $dbName = 'user';
                    $id = 'uid';

                }else{
                    $dbName = 'manage_salesman';
                    $id = 'sid';
                }

                $referrer_info  = Db::name($dbName)
                    ->where($id,$referrer_id)
                    ->field('phone')
                    ->find();
            }
        }else{
            $referrer_info = array();
        }

        $bill['referrer_info'] = $referrer_info;
        return $bill;
    }

    /**
     * 获取用户办卡时的开卡信息
     * @param $vid
     * @return array|false|mixed|\PDOStatement|string|\think\Model
     */
    public function getBillCardFeesDetail($vid)
    {
        $billCardFeesDetailModel = new BillCardFeesDetail();

        $info = $billCardFeesDetailModel
            ->where('vid',$vid)
            ->find();
        $info = json_decode($info,true);
        return $info;

    }
}