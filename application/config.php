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

    //默认密码
    "DEFAULT_PASSWORD" => "000000",
    "default_name"     => "秀会员",

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
        "AREA_IS_EXIST"         => "当前位置该区域名称已存在",
        "SPENDING_ELT_SUBS"     => "最低消费不能低于定金",
        "PHONE_BIND_OTHER"      => "该手机号码已绑定其他账户",
        "USER_NOT_EXIST"        => "新用户,可直接注册",
        "CREATED_NEW_USER_FAIL" => "创建新用户失败",
        "RECHARGE_MONEY_INVALID"=> "储值金额无效",
        "PHONE_EXIST"           => "电话号码已存在",
        "NOT_OPEN_CARD"         => "未开卡,或者卡已失效",
        "SALESMAN_PHONE_ERROR"  => "请输入正确的营销人员号码",
        "SALESMAN_NOT_EXIST"    => "您输入的推荐人手机号码不存在,请核对后重试",
        "TABLE_INVALID"         => "所订吧台无效",
        "TABLE_IS_RESERVE"      => "很遗憾,该吧台已被其他顾客预约,请重新挑选",
        "DATE_IS_EXIST"         => "指定押金预定日期已存在",
        "PHONE_NOT_EXIST"       => "未找到相关会员信息",
        "TODAY_NOT_HAVE_TABLE"  => "今日无预约",
        "ATT_AL_PRINT"          => "当前属性已绑定此打印机,不可重复绑定",
        "OPEN_TABLE_STATUS"     => [
            "QrCodeINVALID"     => "二维码无效",
            "CANCELED"          => "预约已取消,请重新预约",
            "UNPAY"             => "未支付定金,不可开台",
            "ALREADYOPEN"       => "预约已开台,不可重复操作",
            "CLEARTABLE"        => "桌已清台,不可扫码开台",
            "OPEN_RETURN"       => "不是当日预约信息或其他异常,请核实"
        ],
        "MANAGE_INFO"           => [
            "UsrLMT"            => "权限不足",
        ],
        "ORDER"                 => [
            "ORDER_ID_EMPTY"    => "订单号不能为空",
            "completed"         => "订单已支付,请勿重复操作",
            "NOW_STATUS_NOT_PAY"=> "支付操作异常",
            "BALANCE_NOT_ENOUGH"=> "钱包余额不足",
            "GIFT_NOT_ENOUGH"   => "礼金账户余额不足",
            "NOW_STATUS_ERROR"  => "当前状态不允许此操作",
            "ORDER_ABNORMAL"    => "订单异常",
            "STATUS_NO_CANCEL"  => "订单已支付,不可取消",
            "ORDER_NOT_REFUND"  => "订单不可退",
            "REFUND_WAIT_AUDIT"      => "退单成功,等待审核",
            "REFUND_ABNORMAL"        => "退单异常",
            "PAY_SUCCESS"            => "支付成功",
            "ORDER_CANCEL"           => "订单已取消",
            "WAIT_RESULT"            => "等待支付结果",
            "TURNOVER_LIMIT_SHORT"   => "低消不足,请重新点单",
            "REFUND_DISH_ABNORMAL"   => "退单操作异常",
            "PAY_TYPE_EMPTY"         => "支付方式不能为空",
            "ORDER_NOT_EXIST"        => "订单不存在",
            "VOUCHER_NOT_REFUND"     => "礼券消费不可退款",
            "CREATE_CARD_ORDER_FAIL" => "创建开卡订单失败",
            "MONEY_NOT_ZERO"         => "金额不能为零",
            "RE_BALANCE_MONEY_D"     => "储值退款金额不能大于储值消费金额",
            "RE_CASH_GIFT_MONEY_D"   => "礼金退款金额不能大于礼金消费金额",
            "RE_CASH_MONEY_D"        => "现金退款金额不能大于现金消费金额",
            "SETTLEMENTED_NOT_REFUND"=> "已结算,不可退款",
            "DON_NOT_ETTLEMENT"      => "有订单未处理,请先处理订单"
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
            "XD_TABLE_FALL"     => "用户权限不足以预定本桌",
            "CLEAN_BEFORE_USER" => "清台之前,请完善用户信息",
            "NOT_OPEN_NOT_DISH" => "请先开台",
            "DO_NOT_CANCEL_OPEN"=> "当前状态,不可进行取消开台操作",
            "POINT_LIST_NO_CANCEL" => "已点单,不可取消开台",
            "PHONE_NOT_IS_SALES"=> "预约用户不可是工作人员"
        ],
        "DISHES"                => [
            "CLASS_EXIST_DISHES"=>  "当前分类下存在菜品,不可直接删除",
            "ATTR_EXIST_DISHES" =>  "当前属性下存在菜品,不可直接删除",
            "CARD_PRICE_EMPTY"  =>  "vip价格不能为空",
            "COMBO_DIST_EMPTY"  =>  "套餐内的单品不能为空",
            "COMBO_ID_NOT_EMPTY"=>  "换品组内的单品不能为空",
            "LOW_ELIMINATION"   =>  "不满足最低消费,请核对订单"
        ],
        "MERCHANT"                => [
            "CLASS_EXIST_MERCHANT"=>  "当前分类下存在菜品,不可直接删除",
        ],

        "TABLE"                          => [
            "TABLE_CARD_LIMIT_NOT_EMPTY" => "选择仅会员时,会员卡限定不能为空",
            "AREA_EXIST"                 => "当前大区下存在小区,不可直接删除",
            "TALE_EXIST"                 => "该区域下存在吧台,不可直接删除",
            "IMPROVING_USER_INFO"        => "有用户信息未完善,是否确认清台?",
            "TABLE_NOT_EXIST"            => "桌号不存在,请核实"
        ],
        "VOUCHER"               => [
            "CARD_NOT_EXIST"    => "此卡无效",
            "VOUCHER_NOT_EXIST" => "此券无效",
            "PHONE_USER_NOT_EX" => "用户不存在",
            "VALID_DATE_USE"    => "请在有效时间范围内使用",
            "VOUCHER_USE_ING"   => "此券正在使用中"
        ],
        "USER" => [
            "USER_NOT_EXIST"   => "用户不存在",
            "USER_OPENED_CARD" => "用户已开卡,请勿重复开卡",
            "CARD_VALID_NO"    => "此卡无效",
            "CARD_TYPE_ERROR"  => "卡片类型错误",
            "SALES_NOT_REGISTER_USER" => "当前推荐人未注册用户端账号"
        ],
        "TEMP"             => [
            "SC_DELETE_NO" => "该分类下存在素材，请先删除素材后再删除分类",
            "MOVE_FAIL"    => "素材移动失败,请稍后重试"
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

        "manage_revenue_send"    => "预约成功!时间%date_time% 桌号%table_info% 联系人%sales_name%%sales_phone%。",

        "client_revenue_send"    => "尊敬的LiveShow用户%phone%您好,您已成功预订 %date_time% 的 %table_info% 号桌,如有定金,提前三十分钟取消预约,定金原路退回.感谢您的信任。",

        "web_revenue_send"       => "预约成功!时间%date_time% 桌号%table_info% 客服电话%service_phone%。",
        "web_manage_send"        => "预约成功!时间%date_time% 桌号%table_info% 客户%user_name%%service_phone%。",

        "manage_cancel_send"     => "取消预约成功!时间%date_time% 桌号%table_info%。",
        "client_cancel_send"     => "取消预约成功!时间%date_time% 桌号%table_info%。",
        "web_cancel_send"        => "取消预约成功!时间%date_time% 桌号%table_info%。",

        "voucher_send"           => "您有会员礼券到账,请前往小程序查看!",


        "point_list"      => "消费验证码%code%,请服务人员确认!"
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
        'status' => [
            0 => ['key' => '0', 'name' => '有效待使用'],
            1 => ['key' => '1', 'name' => '已使用'],
            9 => ['key' => '9', 'name' => '已过期']
        ]
    ],

    'bill_assist' => [
        'bill_type' => [
            0 => ['key' => '0', 'name' => '消费'],
            6 => ['key' => '6', 'name' => '礼券']
        ],
        'bill_status' => [
            0 => ['key' => '0', 'name' => '待扣款'],
            1 => ['key' => '1', 'name' => '扣款完成'],
            7 => ['key' => '7', 'name' => '部分退款'],
            8 => ['key' => '8', 'name' => '已退款'],
            9 => ['key' => '9', 'name' => '交易取消']
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | 桌台
    |--------------------------------------------------------------------------
    */
    'table' => [
        'reserve_type' => [
            0 => ['key' => 'all',    'name' => '无限制'],
            1 => ['key' => 'vip',    'name' => '仅会员用户'],
            2 => ['key' => 'normal', 'name' => '仅非会员用户'],
            3 => ['key' => 'keep',   'name' => '保留'],
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
            'recharge'            => ['key' => '601', 'name' => '充值'],
            'hand_consume'        => ['key' => '602', 'name' => '储值消费'],//账户余额消费（手动)（余额账户-)
            'card_recharge'       => ['key' => '603', 'name' => '购卡充值'],
            'consume'             => ['key' => '600', 'name' => '储值消费'],
            'refund'              => ['key' => '609', 'name' => '储值退款'],
            'hand_refund'         => ['key' => '610', 'name' => '储值退款'],//账户余额退款  (手动)（余额账户+）
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
            'refund_consume'    => ['key' => '202', 'name' => '退单减积分'],
            'startWithdraw'     => ['key' => '800', 'name' => '积分提现'],
            'other'             => ['key' => '900', 'name' => '其他原因调整'],
        ],

        //礼金变更状态
        'gift_cash' => [
            'exchange_plus'    => ['key' => '100', 'name' => '赠券兑换礼金'],
            'open_card_reward' => ['key' => '101', 'name' => '开卡赠送礼金'],
            'recharge_give'    => ['key' => '102', 'name' => '充值赠送礼金'],
            'hand_refund'      => ['key' => '103', 'name' => '礼金退款'],//礼金退款（手动） +
            'consume'          => ['key' => '200', 'name' => '礼金消费'],
            'hand_consume'     => ['key' => '201', 'name' => '礼金消费'],//礼金消费（手动 ）-
            'consumption_give' => ['key' => '300', 'name' => '消费赠送礼金'],
            'recommend_reward' => ['key' => '800', 'name' => '推荐会员赠送礼金'],
            'other'            => ['key' => '900', 'name' => '其他原因调整'],
        ],

        //佣金变更状态
        'job_account' => [
            'recommend_reward'  => ['key' => '101', 'name' => '推荐办卡佣金'],
            'consume'           => ['key' => '102', 'name' => '推荐会员消费佣金'],
            'recharge'          => ['key' => '103', 'name' => '推荐会员充值佣金'],
            'return'            => ['key' => '109', 'name' => '退款退还佣金'],//推荐会员退款退还佣金
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
            3 => ['key' => 'platform', 'name' => '无'],
            4 => ['key' => 'boss',     'name' => '董事长'],
            5 => ['key' => 'service',  'name' => '服务员'],
            6 => ['key' => 'reserve',  'name' => '前台'],
            7 => ['key' => 'cashier',  'name' => '银台']
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | 二维码规则配置
    |--------------------------------------------------------------------------
    */
    "qrcode" => [

        //前缀规则
        'prefix' => [
            '0' => ['key' => '258TR',  'name' => '开台二维码'],
            '1' => ['key' => '258LQ',  'name' => '礼券二维码'],
            '2' => ['key' => '258DD',  'name' => '扫码点单'],
            '3' => ['key' => '258PAY', 'name' => '客户扫码钱包支付']
        ],

        //分隔符
        'delimiter' => [ 'key' => ',','name' => '分隔符'],

        //小程序生成二维码是否透明底色 true OR false
        'is_hyaline'=> ['key' => true, 'name' => '是']
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
            'cancel_revenue'    => ['key' => '2', 'name' => '取消预约'],
            'open_table_refund' => ['key' => '3', 'name' => '到场退款'],
            'cancel'            => ['key' => '9', 'name' => '交易取消'],
        ],

        //预定吧台状态
        'table_reserve_status' => [
            'pending_payment' => ['key' => '0','name' => '待付定金或结算'],
            'reserve_success' => ['key' => '1','name' => '预定成功'],
            'already_open'    => ['key' => '2','name' => '已开台'],
            'clear_table'     => ['key' => '3','name' => '已清台'],
            'cancel_table'    => ['key' => '4','name' => '取消开台'],
            'go_to_table'     => ['key' => '8','name' => '到店完成预约'],
            'cancel'          => ['key' => '9','name' => '取消预约'],
        ],

        //支付方式
        'pay_method' => [
            'wxpay'     => ['key' => 'wxpay',    'name' => '微信'],
            'alipay'    => ['key' => 'alipay',   'name' => '支付宝'],
            'bank'      => ['key' => 'bank',     'name' => '银行转账'],
            'cash'      => ['key' => 'cash',     'name' => '现金'],
            'offline'   => ['key' => 'offline',  'name' => '线下'],
            'wxpay_c'   => ['key' => 'wxpay_c',  'name' => '线下微信'],
            'alipay_c'  => ['key' => 'alipay_c',  'name' => '线下支付宝'],
            'balance'   => ['key' => 'balance',  'name' => '余额'],
            'cash_gift' => ['key' => 'cash_gift','name' => '礼金支付'],
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
            'cancel_table'   => ['key' => '50', 'name' => '取消开台'],
            'revenue_table'  => ['key' => '80', 'name' => '预约'],
            'cancel_revenue' => ['key' => '90', 'name' => '取消预约'],
        ],

        //消费单缴费单类型
        'bill_pay_type'    => [
            'consumption'  => ['key' => '0', 'name' => '消费'],
            'change_order' => ['key' => '1', 'name' => '换单'],
            'retire_dish'  => ['key' => '2', 'name' => '退菜'],
            'retire_order' => ['key' => '3', 'name' => '退单'],
            'give'         => ['key' => '4', 'name' => '赠送'],
            'cash_gift'    => ['key' => '5', 'name' => '礼金'],
        ],

        //消费单缴费单状态
        "bill_pay_sale_status" => [
            "wait_audit"             => ['key' => '0', 'name' => '待审核'],
            "pending_payment_return" => ['key' => '1', 'name' => '待付款或待退款'],
            "completed"              => ['key' => '2', 'name' => '付款或完成已落单'],
            "audit_failed"           => ['key' => '8', 'name' => '审核未通过'],
            "cancel"                 => ['key' => '9', 'name' => '交易取消']
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
        'recharge'          => ['key' => 'recharge',            'name' => '充值'],
        'refund'            => ['key' => 'refund',              'name' => '退款'],
        'open_card'         => ['key' => 'open_card',           'name' => '开卡'],
    ],

    /*
    |--------------------------------------------------------------------------
    | 系统设置配置
    |--------------------------------------------------------------------------
    */
    'sys' => [
        'sys'     => "系统设置",
        'card'    => "会籍卡设置",
        'reserve' => "预约设置",
        'sms'     => "短信设置",
        'user'    => "用户设置",
    ],


    /*
    |--------------------------------------------------------------------------
    | 菜品
    |--------------------------------------------------------------------------
    */
    'dish' => [

        //菜品单品套餐分类
        'dish_type' => [
            0 => ['key' => '0', 'name' => '单品'],
            1 => ['key' => '1', 'name' => '套餐']
        ],
        'xcx_dish_menu' => [
            0 => ['key' => 'vip',   'name' => '会员专享', 'img' => ''],
            1 => ['key' => 'combo', 'name' => '套餐',     'img' => '']
        ],
    ],

];
