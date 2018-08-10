<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006~2016 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------

return [
    // +----------------------------------------------------------------------
    // | 应用设置
    // +----------------------------------------------------------------------

    // 应用命名空间
    'app_namespace'          => 'app',
    // 应用调试模式
    'app_debug'              => \think\Env::get("APP_DEBUG"),
    // 应用Trace
    'app_trace'              => false,
    // 应用模式状态
    'app_status'             => '',
    // 是否支持多模块
    'app_multi_module'       => true,
    // 入口自动绑定模块
    'auto_bind_module'       => false,
    // 注册的根命名空间
    'root_namespace'         => [],
    // 扩展函数文件
    'extra_file_list'        => [THINK_PATH . 'helper' . EXT],
    // 默认输出类型
    'default_return_type'    => 'html',
    // 默认AJAX 数据返回格式,可选json xml ...
    'default_ajax_return'    => 'json',
    // 默认JSONP格式返回的处理方法
    'default_jsonp_handler'  => 'jsonpReturn',
    // 默认JSONP处理方法
    'var_jsonp_handler'      => 'callback',
    // 默认时区
    'default_timezone'       => 'PRC',
    // 是否开启多语言
    'lang_switch_on'         => false,
    // 默认全局过滤方法 用逗号分隔多个
    'default_filter'         => '',
    // 默认语言
    'default_lang'           => 'zh-cn',
    // 应用类库后缀
    'class_suffix'           => false,
    // 控制器类后缀
    'controller_suffix'      => false,

    // +----------------------------------------------------------------------
    // | 模块设置
    // +----------------------------------------------------------------------

    // 默认模块名
    'default_module'         => 'index',
    // 禁止访问模块
    'deny_module_list'       => ['common'],
    // 默认控制器名
    'default_controller'     => 'Index',
    // 默认操作名
    'default_action'         => 'index',
    // 默认验证器
    'default_validate'       => '',
    // 默认的空控制器名
    'empty_controller'       => 'Error',
    // 操作方法后缀
    'action_suffix'          => '',
    // 自动搜索控制器
    'controller_auto_search' => false,

    // +----------------------------------------------------------------------
    // | URL设置
    // +----------------------------------------------------------------------

    // PATHINFO变量名 用于兼容模式
    'var_pathinfo'           => 's',
    // 兼容PATH_INFO获取
    'pathinfo_fetch'         => ['ORIG_PATH_INFO', 'REDIRECT_PATH_INFO', 'REDIRECT_URL'],
    // pathinfo分隔符
    'pathinfo_depr'          => '/',
    // URL伪静态后缀
    'url_html_suffix'        => 'html',
    // URL普通方式参数 用于自动生成
    'url_common_param'       => false,
    // URL参数方式 0 按名称成对解析 1 按顺序解析
    'url_param_type'         => 0,
    // 是否开启路由
    'url_route_on'           => true,
    // 路由使用完整匹配
    'route_complete_match'   => false,
    // 路由配置文件（支持配置多个）
    'route_config_file'      => ['route'],
    // 是否强制使用路由
//    'url_route_must'         => false,
    'url_route_must'         => true,
    // 域名部署
    'url_domain_deploy'      => false,
    // 域名根，如thinkphp.cn
    'url_domain_root'        => '',
    // 是否自动转换URL中的控制器和操作名
    'url_convert'            => true,
    // 默认的访问控制器层
    'url_controller_layer'   => 'controller',
    // 表单请求类型伪装变量
    'var_method'             => '_method',
    // 表单ajax伪装变量
    'var_ajax'               => '_ajax',
    // 表单pjax伪装变量
    'var_pjax'               => '_pjax',
    // 是否开启请求缓存 true自动缓存 支持设置请求缓存规则
    'request_cache'          => false,
    // 请求缓存有效期
    'request_cache_expire'   => null,

    // +----------------------------------------------------------------------
    // | 模板设置
    // +----------------------------------------------------------------------

    'template'               => [
        // 模板引擎类型 支持 php think 支持扩展
        'type'         => 'Think',
        // 模板路径
        'view_path'    => '',
        // 模板后缀
        'view_suffix'  => 'html',
        // 模板文件名分隔符
        'view_depr'    => DS,
        // 模板引擎普通标签开始标记
        'tpl_begin'    => '{',
        // 模板引擎普通标签结束标记
        'tpl_end'      => '}',
        // 标签库标签开始标记
        'taglib_begin' => '{',
        // 标签库标签结束标记
        'taglib_end'   => '}',
    ],

    // 视图输出字符串内容替换
    'view_replace_str'       => [],
    // 默认跳转页面对应的模板文件
    'dispatch_success_tmpl'  => THINK_PATH . 'tpl' . DS . 'dispatch_jump.tpl',
    'dispatch_error_tmpl'    => THINK_PATH . 'tpl' . DS . 'dispatch_jump.tpl',

    // +----------------------------------------------------------------------
    // | 异常及错误设置
    // +----------------------------------------------------------------------

    // 异常页面的模板文件
    'exception_tmpl'         => THINK_PATH . 'tpl' . DS . 'think_exception.tpl',

    // 错误显示信息,非调试模式有效
    'error_message'          => '页面错误！请稍后再试～',
    // 显示错误信息
    'show_error_msg'         => false,
    // 异常处理handle类 留空使用 \think\exception\Handle
    'exception_handle'       => '',

    // +----------------------------------------------------------------------
    // | 日志设置
    // +----------------------------------------------------------------------

    'log'                    => [
        // 日志记录方式，内置 file socket 支持扩展
        'type'  => 'File',
        // 日志保存目录
        'path'  => LOG_PATH,
        // 日志记录级别
        'level' => [],
    ],

    // +----------------------------------------------------------------------
    // | Trace设置 开启 app_trace 后 有效
    // +----------------------------------------------------------------------
    'trace'                  => [
        // 内置Html Console 支持扩展
        'type' => 'Html',
    ],

    // +----------------------------------------------------------------------
    // | 缓存设置
    // +----------------------------------------------------------------------

    'cache'                  => [
        // 驱动方式
        'type'   => 'File',
        // 缓存保存目录
        'path'   => CACHE_PATH,
        // 缓存前缀
        'prefix' => '',
        // 缓存有效期 0表示永久缓存,秒计时
        'expire' => 0,
    ],

    // +----------------------------------------------------------------------
    // | 会话设置
    // +----------------------------------------------------------------------

    'session'                => [
        'id'             => '',
        // SESSION_ID的提交变量,解决flash上传跨域
        'var_session_id' => '',
        // SESSION 前缀
        'prefix'         => 'think',
        // 驱动方式 支持redis memcache memcached
        'type'           => '',
        // 是否自动开启 SESSION
        'auto_start'     => true,
    ],

    // +----------------------------------------------------------------------
    // | Cookie设置
    // +----------------------------------------------------------------------
    'cookie'                 => [
        // cookie 名称前缀
        'prefix'    => '',
        // cookie 保存时间
        'expire'    => 0,
        // cookie 保存路径
        'path'      => '/',
        // cookie 有效域名
        'domain'    => '',
        //  cookie 启用安全传输
        'secure'    => false,
        // httponly设置
        'httponly'  => '',
        // 是否使用 setcookie
        'setcookie' => true,
    ],

    //分页配置(框架默认)
    'paginate'               => [
        'type'      => 'bootstrap',
        'var_page'  => 'page',
        'list_rows' => 15,
    ],

    "DEFAULT_PASSWORD" => "000000",

    /*
    |--------------------------------------------------------------------------
    | 分页-自配
    |--------------------------------------------------------------------------
    */
    'PAGESIZE'       => '20',
    'XCXPAGESIZE'    => '5',


    /*
    |--------------------------------------------------------------------------
    | 参数返回值
    |--------------------------------------------------------------------------
    */
    'params' => [
        "PASSWORD_NOT_EMPTY"    => "密码不能为空",
        "LONG_NOT_ENOUGH"       => "长度不够",
        "PARAM_NOT_EMPTY"       => "非法操作",
        "ABNORMAL_ACTION"       => "异常操作",
        "PASSWORD_DIF"          => "密码不一致",
        "PASSWORD_PP"           => "密码不匹配",
        "ACCOUNT_PASSWORD_DIF"  => "账号密码不匹配",
        'SUCCESS'               => "成功",
        'FAIL'                  => "失败",
        "NOT_HAVE_MORE"         => "暂无更多",
        'PERMISSION_NOT_ENOUGH' => "权限不足",
        'POINT_POST_RETURN'     => "积分最大值不能小于积分最小值",
        "NAME_NOT_EMPTY"        => "姓名不能为空",
        "PURVIEW_SHORT"         => "权限不足",
        "CHECK_SHIP_TYPE"       => "请选择发货类型",
        "INSTEAD_SALES_NAME"    => "请输入代收货人姓名",
        "EXIST_TALE"            => "该区域下存在吧台,不可删除",
        "AREA_IS_EXIST"         => "当前位置该区域名称已存在",
        "SPENDING_ELT_SUBS"     => "最低消费不能低于定金",
        "PHONE_BIND_OTHER"      => "该手机号码已绑定其他账户",
        "RECHARGE_MONEY_INVALID"=> "储值金额无效",
        "PHONE_EXIST"           => "电话号码已存在",
        "NOT_OPEN_CARD"         => "未开卡,或者卡已失效",
        "SALESMAN_PHONE_ERROR"  => "请输入正确的营销人员号码",
        "TABLE_INVALID"         => "所订吧台无效",
        "TABLE_IS_RESERVE"      => "很遗憾,该吧台已被其他顾客预约,请从新挑选",
        "DATE_IS_EXIST"         => "指定押金预定日期已存在",
        "PHONE_NOT_EXIST"       => "未找到相关会员信息",
        "TODAY_NOT_HAVA_TABLE"  => "今日无预约",
        "OPEN_TABLE_STATUS"     => [
            "QrCodeINVALID"     => "二维码无效",
            "CANCELED"          => "预约已取消,请重新预约",
            "UNPAY"             => "未支付定金,不可开台",
            "ALREADYOPEN"       => "预约已开台,不可重复操作",
            "CLEARTABLE"        => "桌已清台,不可扫码开台"
        ],
        "MANAGE_INFO"           => [
            "UsrLMT"            => "权限不足",
        ],
        "ORDER"                 => [
            "completed"         => "订单已支付,请勿重复操作"
        ],
        "REVENUE"               => [
            "DO_NOT_OPEN"       => "当前台位已被占用,不可开台",
            "STATUS_NO_OPEN"    => "当前状态不可开台",
            "STATUS_NO_EDIT"    => "当前状态不可编辑",
            "NO_OPEN_SPELLING"  => "吧台当前状态不可进行拼台操作",
            "USER_HAVE_TABLE"   => "用户已有开台,不可进行开拼操作",
            "NOT_OPEN_NO_TURN"  => "当前用户未开台,不可进行转台操作",
            "TABLE_NOT_LDLE"    => "转至的台位已被占用,不可进行转台操作",
            "DATE_NOT_EMPTY"    => "日期不能为空",
            "TURN_OBJ_NO_SELF"  => "转至对象不能是自身",
            "XD_TABLE_FALL"     => "用户权限不足以预定本桌"
        ],
     ],

    /*
    |--------------------------------------------------------------------------
    | 短信配置
    |--------------------------------------------------------------------------
    */
    'sms' => [
        'api_key'         => \think\Env::get('SMS_API_KEY', ''),
        'sign'            => '【Live Show】',
        //验证码
        "sms_verify_code" => "验证码为 %code%  (Live Show绝不会索取此验证码，切勿告知他人，如非本人操作请无视。)",
        "send_success"    => "发送成功",
        "send_fail"       => "发送失败",
        "send_repeat"     => "已发送,请勿重复操作",
        "verify_success"  => "验证成功",
        "verify_fail"     => "验证失败",

    ],


    /*
    |--------------------------------------------------------------------------
    | 定时任务
    | 每分钟
    | 每小时 某分
    | 每天 某时:某分
    | 每周-某天 某时:某分  0=周日
    | 每月-某天 某时:某分
    | 某月-某日 某时-某分
    | 某年-某月-某日 某时-某分
    |--------------------------------------------------------------------------
    */
    'sys_crond_timer' => array('*', '*:i', 'H:i', '@-w H:i', '*-d H:i', 'm-d H:i', 'Y-m-d H:i'),
    'crond' => include_once("extra/crond.php"),

    /*
   |--------------------------------------------------------------------------
   | 卡配置
   |--------------------------------------------------------------------------
    */
    'card' => [
        'type' => [
            0   => ['key' => 'vip',  'name' => '押金卡'],
            1   => ['key' => 'value','name' => '储值卡'],
            2   => ['key' => 'year', 'name' => '年费卡']
        ]
    ],

    /*
    |--------------------------------------------------------------------------
    | 礼券
    |--------------------------------------------------------------------------
    */
    'voucher' => [
        'type' => [
            0 => ['key' => 'once',      'name' => '单次'],
            1 => ['key' => 'multiple',  'name' => '多次'],
            2 => ['key' => 'limitless', 'name' => '无限制']
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | 会员
    |--------------------------------------------------------------------------
    */
    'user' => [
        'default_sex' => "先生",
        //用户状态
        'user_status' => [
            0 => ['key' => '0','name' => '注册会员'],
            1 => ['key' => '1','name' => '提交订单'],
            2 => ['key' => '2','name' => '开卡会员']
        ],

        //用户开卡流程状态
        'user_register_status' => [
            'register'      => ['key' => '0', 'name' => '已注册'],
            'post_order'    => ['key' => '1', 'name' => '提交订单'],
            'open_card'     => ['key' => '2', 'name' => '开卡成功'],
        ],

        //用户资料状态
        'user_info' => [
            'empty_info'    => ['key' => '0', 'name' => '待填写资料'],
            'referrer'      => ['key' => '1', 'name' => '填写推荐人'],
            'interest'      => ['key' => '2', 'name' => '兴趣标签'],
            'complete'      => ['key' => '4', 'name' => '已完整信息']
        ],

        //平台推荐设置
        'platform_recommend' => [
            'referrer_id'     => ['key' => 'id', 'name' => 'platform'],
            'referrer_type'   => ['key' => 'type', 'name' => 'platform']
        ],

        //余额(钱包)变更状态
        'account' => [
            'recharge'            => ['key' => '601', 'name' => '账户余额充值'],
            'consume'             => ['key' => '600', 'name' => '账户余额消费'],
            'refund'              => ['key' => '609', 'name' => '账户余额退款'],
            'brokerage'           => ['key' => '700', 'name' => '平台扣除订单佣金'],
            'withdrawHandlingFee' => ['key' => '705', 'name' => '平台扣除提现手续费'],
            'startWithdraw'       => ['key' => '800', 'name' => '提现'],
            'endWithdraw'         => ['key' => '801', 'name' => '提现完成'],
            'failWithdraw'        => ['key' => '802', 'name' => '提现失败'],
            'other'               => ['key' => '900', 'name' => '账务调整'],
        ],


        //押金变更状态
        'deposit' => [
            'pay'     => ['key' => '100', 'name' => '缴纳保证金'],
            'return'  => ['key' => '200', 'name' => '退还保证金'],
            'other'   => ['key' => '900', 'name' => '其他原因调整 '],
        ],

        //积分变更状态
        'point' => [
            'open_card_reward'  => ['key' => '100', 'name' => '开卡赠送积分'],
            'consume_reward'    => ['key' => '200', 'name' => '消费赠送积分'],
            'consume'           => ['key' => '201', 'name' => '积分消费'],
            'startWithdraw'     => ['key' => '800', 'name' => '积分提现'],
            'other'             => ['key' => '900', 'name' => '其他原因调整'],
        ],

        //礼金变更状态
        'gift_cash' => [
            'exchange_plus'    => ['key' => '100', 'name' => '赠券兑换礼金'],
            'open_card_reward' => ['key' => '101', 'name' => '开卡赠送礼金'],
            'recharge_give'    => ['key' => '102', 'name' => '充值赠送礼金'],
            'consume'          => ['key' => '200', 'name' => '礼金消费'],
            'recommend_reward' => ['key' => '800', 'name' => '推荐会员赠送礼金'],
            'other'            => ['key' => '900', 'name' => '其他原因调整'],
        ],

        //佣金变更状态
        'job_account' => [
            'recommend_reward'  => ['key' => '101', 'name' => '推荐办卡佣金'],
            'consume'           => ['key' => '102', 'name' => '推荐会员消费佣金'],
            'return'            => ['key' => '109', 'name' => '推荐会员退款退还佣金'],
            'startWithdraw'     => ['key' => '800', 'name' => '佣金提现'],
            'endWithdraw'       => ['key' => '801', 'name' => '佣金提现完成'],
            'failWithdraw'      => ['key' => '802', 'name' => '佣金提现失败'],
            'other'             => ['key' => '900', 'name' => '佣金账务调整'],
        ],

        //注册途径  'h5'  'wxapp' 'app'  'web'
        'register_way' => [
            'h5'    => ['key' => 'h5',   'name' => 'h5'],
            'wxapp' => ['key' => 'wxapp','name' => '小程序'],
            'app'   => ['key' => 'app',  'name' => 'app'],
            'web'   => ['key' => 'web',  'name' => 'web']
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | 营销人员
    |--------------------------------------------------------------------------
    */
    'salesman' => [
        'salesman_status' => [
            'pending'       => ['key' => '0','name' => '待审核'],
            'working'       => ['key' => '1','name' => '在职'],
            'suspended'     => ['key' => '2','name' => '停职'],
            'resignation'   => ['key' => '9','name' => '离职'],
        ],
        'salesman_type'   => [
            0 => ['key' => 'vip',      'name' => '会籍顾问'],
            1 => ['key' => 'sales',    'name' => '销售'],
            2 => ['key' => 'user',     'name' => '会员'],
            3 => ['key' => 'platform', 'name' => '平台推荐'],
            4 => ['key' => 'boss',     'name' => '董事长'],
            5 => ['key' => 'service',  'name' => '服务人员'],
            6 => ['key' => 'reserve',  'name' => '前台']
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | 二维码前缀规则配置
    |--------------------------------------------------------------------------
    */
    "qrcode" => [

        //前缀规则
        'prefix' => [
            '0' => ['key' => '258TR', 'name' => '开台二维码'],
            '1' => ['key' => '258LQ', 'name' => '礼券二维码']
        ],
        //分隔符
        'delimiter' => [ 'key' => '|','name' => '分隔符'],
    ],

    /*
   |--------------------------------------------------------------------------
   | 订单
   |--------------------------------------------------------------------------
   */
    'order' => [
        //开卡订单状态
        'open_card_status' => [
            'pending_payment'   => ['key' => '0', 'name' => '待付款'],
            'pending_ship'      => ['key' => '1', 'name' => '待发货'],
            'pending_receipt'   => ['key' => '2', 'name' => '待收货'],
            'completed'         => ['key' => '3', 'name' => '交易完成'],
            'cancel'            => ['key' => '9', 'name' => '交易取消'],
        ],

        //开卡订单列表分类
        'open_card_type' => [
            'pending_payment' => ['key' => '0',     'name' => '待付款'],
            'completed'       => ['key' => '1,2,3', 'name' => '交易完成'],
            'cancel'          => ['key' => '9',     'name' => '交易取消'],
        ],
        //开卡礼寄送分类
        'gift_ship_type' => [
            'pending_ship'      => ['key' => '1', 'name' => '待发货'],
            'pending_receipt'   => ['key' => '2', 'name' => '待收货'],
            'completed'         => ['key' => '3', 'name' => '已完成'],
        ],

        //预约定金类型
        'subscription_type' => [
            'null_subscription' => ['key' => '0', 'name' => '无订金'],
            'subscription'      => ['key' => '1', 'name' => '订金'],
            'order'             => ['key' => '2', 'name' => '订单'],
        ],

        //预定途经
        'reserve_way' => [
            'client'    => ['key' => 'client',  'name' => '客户预订'],
            'service'   => ['key' => 'service', 'name' => '服务端预定'],
            'manage'    => ['key' => 'manage',  'name' => '管理端预定'],
        ],

        //预约定金支付状态
        'reservation_subscription_status' => [
            'pending_payment'   => ['key' => '0', 'name' => '待付款'],
            'Paid'              => ['key' => '1', 'name' => '付款完成'],
            'cancel'            => ['key' => '9', 'name' => '交易取消'],
        ],

        //预定吧台状态
        'table_reserve_status' => [
            'pending_payment' => ['key' => '0','name' => '待付定金或结算'],
            'reserve_success' => ['key' => '1','name' => '预定成功'],
            'already_open'    => ['key' => '2','name' => '已开台'],
            'clear_table'     => ['key' => '3','name' => '已清台'],
            'cancel'          => ['key' => '9','name' => '取消预约'],
        ],

        //支付方式
        'pay_method' => [
            'wxpay'   => ['key' => 'wxpay',  'name' => '微信'],
            'alipay'  => ['key' => 'alipay', 'name' => '支付宝'],
            'bank'    => ['key' => 'bank',   'name' => '线下银行转账'],
            'cash'    => ['key' => 'cash',   'name' => '现金'],
        ],

        //支付场景
        'pay_scene' => [
            'open_card'  => ['key' => 'open_card', 'name' => '开卡支付'],
            'reserve'    => ['key' => 'reserve',   'name' => '预约定金支付'],
            'point_list' => ['key' => 'point_list','name' => '点单支付'],
            'recharge'   => ['key' => 'recharge',  'name' => '充值'],
        ],

        //赠品发货类型
        'send_type' => [
            'express'  => ['key' => 'express', 'name' => '快递'],
            'salesman' => ['key' => 'salesman', 'name' => '销售'],
        ],

        //充值状态
        'recharge_status' => [
            'pending_payment' => ['key' => '0',     'name' => '待付款'],
            'completed'       => ['key' => '1',     'name' => '已付款'],
            'cancel'          => ['key' => '9',     'name' => '已取消'],
        ],

        //桌子变更操作类型
        'table_action_type' => [
            'open_table'     => ['key' => '10', 'name' => '开台'],
            'turn_to'        => ['key' => '21', 'name' => '换去'],
            'turn_come'      => ['key' => '22', 'name' => '换来'],
            'spelling_to'    => ['key' => '31', 'name' => '拼去'],
            'spelling_com'   => ['key' => '32', 'name' => '拼来'],
            'clean_table'    => ['key' => '40', 'name' => '清台'],
            'revenue_table'  => ['key' => '80', 'name' => '预约'],
            'cancel_revenue' => ['key' => '90', 'name' => '取消预约'],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | 日志操作类配置
    |--------------------------------------------------------------------------
    */
    'useraction' => [
        'ban'               => ['key' => 'ban',                 'name' => '禁止登陆'],
        'unban'             => ['key' => 'unban',               'name' => '解禁'],
        'ship'              => ['key' => 'ship',                'name' => '发货'],
        'deal_cancel'       => ['key' => 'deal_cancel',         'name' => '订单取消'],
        'deal_pay'          => ['key' => 'deal_pay',            'name' => '订单支付'],
        'deal_dispose'      => ['key' => 'deal_dispose',        'name' => '订单处理'],
        'deal_finish'       => ['key' => 'deal_finish',         'name' => '确认收货'],
        'verify_passed'     => ['key' => 'verify_passed',       'name' => '审核通过'],
        'suspended'         => ['key' => 'suspended',           'name' => '停职'],
        'resignation'       => ['key' => 'resignation',         'name' => '离职'],
        'change_user_pass'  => ['key' => 'change_user_pass',    'name' => '更改用户密码'],
        'change_admin_pass' => ['key' => 'change_admin_pass',   'name' => '更改管理员密码'],
    ],

];
