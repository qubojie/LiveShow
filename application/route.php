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

use think\Route;


/*return [
    '__pattern__' => [
        'name' => '\w+',
    ],
    '[hello]'     => [
        ':id'   => ['index/hello', ['method' => 'get'], ['id' => '\d+']],
        ':name' => ['index/hello', ['method' => 'post']],
    ],
];*/

Route::rule('test','index/test');

//测试资源路由注册
Route::resource('restTest','index/RestTest');

Route::group(['name' => 'sys'],function (){

    //定时处理未支付超出指定时间的订单
    Route::rule('changeOrderStatus','index/ChangeStatus/changeOrderStatus');

    //自动收货完成订单
    Route::rule('AutoFinishTime','index/ChangeStatus/AutoFinishTime');

    //定时取消未支付预约吧台订单
    Route::rule('AutoCancelTableRevenue','index/ChangeStatus/AutoCancelTableRevenue');

    //自动取消未支付单据
    Route::rule('AutoCancelBillRefill','index/ChangeStatus/AutoCancelBillRefill');

});


//微信小程序接口路由群组
Route::group(['name' => 'wechat','prefix' => 'wechat/'],function (){

    //前台获取系统设置信息
    Route::rule('getSettingInfo','wechat/GetSettingInfo/getSettingInfo');

    //微信三方登陆
    Route::rule('wechatLogin','wechat/ThirdLogin/wechatLogin');

    //发送验证码
    Route::rule('captcha','auth/sendVerifyCode','post|options');

    //手机号码注册
    Route::rule('phoneRegister','auth/phoneRegister','post|options');

    //推荐人绑定
    Route::rule('referrerUser','auth/referrerUser','post|options');

    //手机+密码登陆
    Route::rule('phonePassLogin','auth/phonePassLogin','post|options');

    //手机号+验证码登陆
    Route::rule('phoneVerifyLogin','auth/phoneVerifyLogin','post|options');

    //H5支付
    Route::rule('wappay','wechatPay/wappay','get|post|options');

    //公众号支付
    Route::rule('jspay','wechatPay/jspay','get|post|options');

    //小程序支付
    Route::rule('smallapp','wechatPay/smallapp','get|post|options');

    //钱包支付
    Route::rule('walletPay','DishOrderPay/walletPay','post|options');

    //现金支付
    Route::rule('cashPay','DishOrderPay/cashPay','post|options');

    //退款
    Route::rule('reFund','wechatPay/reFund','get|post|options');

    //回调地址
    Route::rule('notify','wechatPay/notify','get|post|options');

    Route::rule('putVoucher','wechatPay/putVoucher','get|post|options');

    //订单查询
    Route::rule('query','wechatPay/query','get|post|options');

    //下载对账单
    Route::rule('download','wechatPay/download','get|post|options');

    //公众号分享获取签名
    Route::rule('getSignPackage','JsSdk/getSignPackage','get|post|options');


    Route::group(['name' => 'h5'],function (){
        //获取用户兴趣之类的标签列表
        Route::rule('tagList','UserInfo/tagList','post|options');

        //提交用户选中标签
        Route::rule('postInfo','UserInfo/postInfo','post|options');


        Route::rule('getUserList','UserInfo/getUserList','post|options');

        //Vip卡列表
        Route::rule('cardList','auth/cardList','post|options');

        //所有有效礼品列
        Route::rule('giftList','OpenCard/getGiftListInfo');

        //开卡操作
        Route::rule('OpenCard','OpenCard/index');

        //取消订单
        Route::rule('cancelOrder','BillOrder/cancelOrder');

    });

    //小程序
    Route::group(['name' => 'xcx'],function(){
        //获取未支付订单信息
        Route::rule('cardInfo','MyInfo/getUserOpenCardInfo','post|options');

        //首页Banner
        Route::rule('bannerList','Banner/index');

        //首页获取文章列表
        Route::rule('article','Article/getArticle','post|options');

        //我的信息
        Route::rule('myInfo','myInfo/index','post|options');

        //变更手机号码
        Route::rule('changePhone','Auth/changePhone','post|options');

        //钱包明细
        Route::rule('wallet','myInfo/wallet','post|options');

        //礼品券列表
        Route::rule('giftVoucher','myInfo/giftVoucher','post|options');

        //获取预约时退款提示信息
        Route::rule('getReserveWaringInfo','PublicAction/getReserveWaringInfo');

        //充值
        Route::group(['name' => 'Recharge'],function (){
            //充值列表
            Route::rule('index','Recharge/rechargeList','post|options');

            //充值确认
            Route::rule('rechargeConfirm','Recharge/rechargeConfirm','post|options');


        });

        //菜品
        Route::group(['name' => 'dishes'],function (){

            //菜品分类
            Route::rule('dishClassify','Dish/dishClassify','get|options');

            //菜品列表
            Route::rule('index','Dish/index','post|options');

        });

        //个人信息
        Route::group(['name' => 'myCenter'],function (){

            //我的预约列表
            Route::rule('reservationOrder','MyInfo/reservationOrder','post|options');

            //我的订单列表
            Route::rule('dishOrder','MyInfo/dishOrder','post|options');

        });

        //预约
        Route::group(['name' => 'reservation'],function (){

            //筛选条件获取
            Route::rule('reserveCondition','PublicAction/reserveCondition','post|options');

            //可预约吧台列表
            Route::rule('tableList','Reservation/tableList','post|options');

            //预约确认(交定金)
            Route::rule('reservationConfirm','Reservation/reservationConfirm','post|options');

            //取消预约
            Route::rule('cancelReservation','Reservation/cancelReservation','post|options');

            //用户主动取消支付,释放桌台
            Route::rule('releaseTable','Reservation/releaseTable','post|options');

            //预约点单结算
            Route::rule('settlementOrder','ReservationOrder/settlementOrder','post|options');

        });

        //点单
        Route::group(['name' => 'pointList'],function (){

            //用户点单
            Route::rule('createPointList','PointList/createPointList','post|options');

            //用户手动取消未支付订单
            Route::rule('cancelDishOrder','PointList/cancelDishOrder','post|options');

        });

        //二维码
        Route::group(['name' => 'qrCode'],function (){
            //使用
            Route::rule('useQrCode','QrCodeAction/useQrCode','post|options');
        });

        //工作人员
        Route::group(['name' => 'manage'],function (){
            //登陆
            Route::rule('login','ManageAuth/login','post|options');

            //变更密码
            Route::rule('changePass','ManageInfo/changePass','post|options');

            //phoneGetUserName
            Route::rule('getUserName','ManageReservation/phoneGetUserName','post|options');

            //可预约吧台列表
            Route::rule('tableList','ManageReservation/tableList','post|options');

            //预约确认
            Route::rule('reservationConfirm','ManageReservation/reservationConfirm','post|options');

            //我的预约列表
            Route::rule('reservationOrder','ManageInfo/reservationOrder','post|options');

            //取消预约
            Route::rule("cancelReservation","ManageReservation/cancelReservation",'post|options');

            //我的客户列表
            Route::rule("customerList","ManageInfo/customerList");

            //编辑客户信息
            Route::rule("customerEdit","ManageInfo/customerEdit","post|options");

            //开台
            Route::rule("openTable","TableAction/openTable","post|options");

            //清台
            Route::rule("cleanTable","TableAction/cleanTable","post|options");

            Route::group(['name' => 'pointList'],function (){

                //订台可退订单列表
                Route::rule('canRefundOrderList','ManagePointList/canRefundOrderList','post|options');


                //退单
                Route::rule('refundOrder','ManagePointList/refundOrder','post|options');
            });


        });

    });

});

//后台路由群组
Route::group(['name' => 'admin','prefix' => 'admin/'],function (){

    //二维码测试
    Route::rule('WxQrcodeCreate','WxQrcode/create');


    //图片上传至本地
    Route::rule('imageUpload','ImageUpload/uploadLocal');

    //七牛云存储
    Route::rule("qiniu/upload","QiniuUpload/upload","post|options");

    //登陆
    Route::rule('auth/login','auth/login','post|options');

    //退出登录,刷新token
    Route::rule('auth/refresh_token','auth/refresh_token','post|options');

    //系统设置
    Route::rule('setting/:id','setting/index','post|options');

    //后台导航栏菜单
    Route::rule('menus','menus/index','post|options');

    //菜单小红点
    Route::rule('menuRedDot','menus/menuRedDot','post|options');

    //后台导航栏所有列表
    Route::rule('menusLists','menus/lists','post|options');

    //统计
    Route::rule('count','Count/index','post|options');


    //应用内容管理
    Route::group(['name' => 'appContent'],function (){
        //首页文章管理
        Route::group(['name' => 'HomeArticles'],function (){
            //文章列表
            Route::rule('index','HomeArticles/index','post/options');

            //文章添加
            Route::rule('add','HomeArticles/add','post/options');

            //文章编辑
            Route::rule('edit','HomeArticles/edit','post/options');

            //是否置顶
            Route::rule('is_top',"HomeArticles/is_top",'post/options');

            //是否显示
            Route::rule('is_show',"HomeArticles/is_show",'post/options');

            //排序编辑
            Route::rule('sortEdit',"HomeArticles/sortEdit",'post/options');

            //文章删除
            Route::rule('delete','HomeArticles/delete','post/options');
        });

        //首页Banner管理
        Route::group(['name' => 'HomeBanner'],function (){
            //Banner列表
            Route::rule('index','HomeBanner/index','post/options');

            //Banner添加
            Route::rule('add','HomeBanner/add','post/options');

            //Banner编辑
            Route::rule('edit','HomeBanner/edit','post/options');

            //Banner是否显示
            Route::rule('isShow',"HomeBanner/isShow",'post/options');

            //Banner排序编辑
            Route::rule('sortEdit',"HomeBanner/sortEdit",'post/options');

            //Banner删除
            Route::rule('delete','HomeBanner/delete','post/options');
        });

    });

    //会员管理
    Route::group(['name' => 'member'],function (){
        //会员信息管理
        Route::group(['name' => 'user'],function(){

            //获取卡种
            Route::rule('cardType','UserInfo/cardType','post/options');

            //会员列表
            Route::rule('index','UserInfo/index','post/options');

            //会员状态类型
            Route::rule('userStatus','UserInfo/userStatus','post/options');

            //会员编辑
            Route::rule('edit','UserInfo/edit','post/options');

            //禁止登陆或解禁
            Route::rule('noLogin','UserInfo/noLogin','post/options');

            //后台操作变更会员密码
            Route::rule('changePass','UserInfo/changePass','post/options');

            //钱包明细
            Route::rule('accountInfo','UserInfo/accountInfo','post/options');

            //押金明细
            Route::rule('depositInfo','UserInfo/depositInfo','post/options');

            //礼金明细
            Route::rule('cashGiftInfo','UserInfo/cashGiftInfo','post/options');

            //积分明细
            Route::rule('accountPointInfo','UserInfo/accountPointInfo','post/options');
        });

        //会员等级设置
        Route::group(['name' => 'level'],function (){
            //等级列表
            Route::rule('index','userLevel/index','post/options');

            //等级添加
            Route::rule('add','userLevel/add','post/options');

            //等级编辑
            Route::rule('edit','userLevel/edit','post/options');

            //等级删除
            Route::rule('delete','userLevel/delete','post/options');
        });

        //开卡赠送礼品设置
        Route::group(['name' => 'gift'],function (){
            //礼品列表
            Route::rule('index','gift/index','post/options');

            //礼品添加
            Route::rule('add','gift/add','post/options');

            //礼品编辑
            Route::rule('edit','gift/edit','post/options');

            //礼品删除
            Route::rule('delete','gift/delete','post/options');

            //礼品启用
            Route::rule('enable','gift/enable','post/options');

        });

        //开卡赠卷设置
        Route::group(['name' => 'voucher'],function (){
            //礼券列表
            Route::rule('index','voucher/index','post/options');

            //礼券添加
            Route::rule('add','voucher/add','post/options');

            //礼券编辑
            Route::rule('edit','voucher/edit','post/options');

            //礼券删除
            Route::rule('delete','voucher/delete','post/options');

            //礼券启用
            Route::rule('enable','voucher/enable','post/options');

            //生成二维码
            Route::rule('makeQrCode','voucher/makeQrCode','post/options');

        });

        //会籍及储蓄卡设置
        Route::group(['name' => 'card'],function (){

            //获取卡类型
            Route::rule('type','card/type','post/options');

            //获取推荐人类型
            Route::rule('recommendUserType','card/getRecommendUserType','post/options');

            //VIP会员卡信息列表
            Route::rule('index','card/index','post/options');

            //VIP会员卡信息添加
            Route::rule('add','card/add','post/options');

            //Vip会员卡信息编辑
            Route::rule('edit','card/edit','post/options');

            //Vip会员卡信息删除
            Route::rule('delete','card/delete','post/options');

            //Vip会员卡是否启用
            Route::rule('enable','card/enable','post/options');

            //Vip会员卡排序
            Route::rule('sortEdit','card/sortEdit','post/options');

            //Vip会员卡新增赠品
            Route::rule('addGiftOrVoucher','card/addGiftOrVoucher','post/options');

            //Vip会员卡赠品删除
            Route::rule('deleteGiftOrVoucher','card/deleteGiftOrVoucher','post/options');

        });


        //开卡订单管理
        Route::group(['name' => 'openCardOrder'],function (){

            //开卡订单分组
            Route::rule('orderType','OpenCardOrder/orderType','post/options');

            //开卡礼寄送分组
            Route::rule('giftShipType','OpenCardOrder/giftShipType','post/options');

            //订单列表
            Route::rule('index','OpenCardOrder/index','post/options');

            //发货操作
            Route::rule('ship','OpenCardOrder/ship','post/options');

            //付款操作
            Route::rule('adminPay','OpenCardOrder/adminPay','post/options');

            //快递公司列表
            Route::rule('getLogisticsCompany','OpenCardOrder/getLogisticsCompany','post/options');

        });


        //充值金额设置
        Route::group(['name' => 'refillAmount'],function (){
            //充值金额列表
            Route::rule('index','refillAmount/index','post|options');

            //充值金额添加
            Route::rule('add','refillAmount/add','post/options');

            //充值金额编辑
            Route::rule('edit','refillAmount/edit','post/options');

            //充值金额删除
            Route::rule('delete','refillAmount/delete','post/options');

            //充值金额是否启用
            Route::rule('enable','refillAmount/enable','post/options');

            //充值金额排序
            Route::rule('sortEdit','refillAmount/sortEdit','post/options');

        });
    });

    //会籍销售管理
    Route::group(['name' => 'sales'],function (){
        //营销人员类型设置
        Route::group(['name' => 'salesType'],function (){

            //营销人员类型列表
            Route::rule("index",'salesType/index','post/options');

            //营销人员类型添加
            Route::rule("add",'salesType/add','post/options');

            //营销人员类型编辑
            Route::rule("edit",'salesType/edit','post/options');

            //营销人员类型删除
            Route::rule("delete",'salesType/delete','post/options');
        });

        //会籍部门设置
        Route::group(['name' => 'department'],function (){

            Route::rule("test",'department/test','post/options');

            //会籍部门列表
            Route::rule("index",'department/index','post/options');

            //会籍部门添加
            Route::rule("add",'department/add','post/options');

            //会籍部门编辑
            Route::rule("edit",'department/edit','post/options');

            //会籍部门删除
            Route::rule("delete",'department/delete','post/options');
        });

        //营销人员管理
        Route::group(['name' => 'salesUser'],function (){
            //人员状态分组
            Route::rule("salesmanStatus",'salesUser/salesmanStatus','post/options');

            //营销人员列表
            Route::rule("index",'salesUser/index','post/options');

            //营销人员添加
            Route::rule("add",'salesUser/add','post/options');

            //营销人员编辑
            Route::rule("edit",'salesUser/edit','post/options');

            //营销人员删除
            Route::rule("changeStatus",'salesUser/changeStatus','post/options');


        });

        //潜在会员管理
        Route::group(['name' => 'potential'],function (){
            //潜在会员列表
            Route::rule("index",'potential/index','post/options');

            //潜在会员添加
            Route::rule("add",'potential/add','post/options');

            //潜在会员编辑
            Route::rule("edit",'potential/edit','post/options');

            //潜在会员删除
            Route::rule("delete",'potential/delete','post/options');
        });

    });

    //吧台管理
    Route::group(['name' => 'barCounter'],function (){

        //台位信息管理
        Route::group(['name' => 'tableInfo'],function (){
            //位置类型列表
            Route::rule("tableLocation",'tableInfo/tableLocation','post/options');

            //台位列表
            Route::rule("index",'tableInfo/index','post/options');

            //台位添加
            Route::rule("add",'tableInfo/add','post/options');

            //台位编辑
            Route::rule("edit",'tableInfo/edit','post/options');

            //台位删除
            Route::rule("delete",'tableInfo/delete','post/options');

            //是否启用
            Route::rule("enable",'tableInfo/enable','post/options');

            //台位排序
            Route::rule("sortEdit",'tableInfo/sortEdit','post/options');

            //桌位二维码打包下载
            Route::rule("zipTableQrCode",'DownloadTableQrCode/zipTableQrCode');
        });

        //特殊日期设置
        Route::group(['name' => 'specialDate'],function (){
            //特殊日期列表
            Route::rule("index",'TableSpecialDate/index','post/options');

            //特殊日期添加
            Route::rule("add",'TableSpecialDate/add','post/options');

            //特殊日期编辑
            Route::rule("edit",'TableSpecialDate/edit','post/options');

            //特殊日期删除
            Route::rule("delete",'TableSpecialDate/delete','post/options');

            //是否启用,是否允许预定,是否可退押金
            Route::rule("statusAction",'TableSpecialDate/enableOrRevenueOrRefund','post/options');
        });

        //酒桌位置设置
        Route::group(['name' => 'tablePosition'],function (){
            //位置列表
            Route::rule("index",'tablePosition/index','post/options');

            //位置添加
            Route::rule("add",'tablePosition/add','post/options');

            //位置编辑
            Route::rule("edit",'tablePosition/edit','post/options');

            //位置删除
            Route::rule("delete",'tablePosition/delete','post/options');

            //排序编辑
            Route::rule("sortEdit",'tablePosition/sortEdit','post/options');

        });

        //酒桌区域管理
        Route::group(['name' => 'tableArea'],function (){

            //获取负责人信息列表
            Route::rule("getGovernorSalesman",'tableArea/getGovernorSalesman','post/options');


            //获取所有的有效卡种
            Route::rule("getCardInfo",'tableArea/getCardInfo','post/options');

            //区域列表
            Route::rule("index",'tableArea/index','post/options');

            //区域添加
            Route::rule("add",'tableArea/add','post/options');

            //区域编辑
            Route::rule("edit",'tableArea/edit','post/options');

            //区域删除
            Route::rule("delete",'tableArea/delete','post/options');

            //是否启用
            Route::rule("enable",'tableArea/enable','post/options');
        });

        //酒桌品项设置
        Route::group(['name' => 'tableAppearance'],function (){
            //品项列表
            Route::rule("index",'tableAppearance/index','post/options');

            //品项添加
            Route::rule("add",'tableAppearance/add','post/options');

            //品项编辑
            Route::rule("edit",'tableAppearance/edit','post/options');

            //品项删除
            Route::rule("delete",'tableAppearance/delete','post/options');

            //排序编辑
            Route::rule("sortEdit",'tableAppearance/sortEdit','post/options');

        });

        //酒桌容量设置
        Route::group(['name' => 'tableSize'],function (){
            //容量列表
            Route::rule("index",'tableSize/index','post/options');

            //容量添加
            Route::rule("add",'tableSize/add','post/options');

            //容量编辑
            Route::rule("edit",'tableSize/edit','post/options');

            //容量删除
            Route::rule("delete",'tableSize/delete','post/options');

            //排序编辑
            Route::rule("sortEdit",'tableSize/sortEdit','post/options');

        });
    });


    //菜品商品管理
    Route::group(['name' => 'dishesGoods'],function (){
        //菜品信息设置
        Route::group(['name' => 'dish'],function (){

            //打印机测试
            Route::rule("test",'dish/test','post/options');

            //菜品类型
            Route::rule("dishType",'dish/dishType','post/options');

            //菜品列表
            Route::rule("index",'dish/index','post/options');

            //菜品添加
            Route::rule("add",'dish/add','post/options');

            //主菜品编辑提交
            Route::rule("edit",'dish/edit','post/options');

            //菜品套餐编辑提交
            Route::rule("combEdit",'dish/combEdit','post/options');

            //菜品详情
            Route::rule("dishDetails",'dish/dishDetails','post/options');

            //菜品删除
            Route::rule("delete",'dish/delete','post/options');

            //菜品排序
            Route::rule("sortEdit",'dish/sortEdit','post/options');

            //菜品是否启用
            Route::rule("enable",'dish/enable','post/options');

        });

        //套餐菜品设置
        Route::group(['name' => 'setMeal'],function (){

            //套餐菜品列表
            Route::rule("index",'setMeal/index','post/options');

            //菜品添加
            Route::rule("add",'setMeal/add','post/options');

            //菜品编辑
            Route::rule("edit",'setMeal/edit','post/options');

            //菜品删除
            Route::rule("delete",'setMeal/delete','post/options');

            //菜品排序
            Route::rule("sortEdit",'setMeal/sortEdit','post/options');

            //菜品是否启用
            Route::rule("enable",'setMeal/enable','post/options');

        });

        //菜品分类设置
        Route::group(['name' => 'dishClassify'],function (){

            //菜品分类列表无分页
            Route::rule("dishType",'dishClassify/dishType','post/options');

            //菜品分类列表
            Route::rule("index",'dishClassify/index','post/options');

            //菜品分类添加
            Route::rule("add",'dishClassify/add','post/options');

            //菜品分类编辑
            Route::rule("edit",'dishClassify/edit','post/options');

            //菜品分类删除
            Route::rule("delete",'dishClassify/delete','post/options');

            //菜品分类排序
            Route::rule("sortEdit",'dishClassify/sortEdit','post/options');

            //菜品分类是否启用
            Route::rule("enable",'dishClassify/enable','post/options');

        });

        //菜品属性设置
        Route::group(['name' => 'dishAttribute'],function (){

            //菜品属性列表 无分页
            Route::rule("dishAttr",'dishAttribute/dishAttr','post/options');

            //菜品属性列表
            Route::rule("index",'dishAttribute/index','post/options');

            //菜品属性添加
            Route::rule("add",'dishAttribute/add','post/options');

            //菜品属性编辑
            Route::rule("edit",'dishAttribute/edit','post/options');

            //菜品属性删除
            Route::rule("delete",'dishAttribute/delete','post/options');

            //菜品属性排序
            Route::rule("sortEdit",'dishAttribute/sortEdit','post/options');

            //菜品属性是否启用
            Route::rule("enable",'dishAttribute/enable','post/options');


            //属性绑定打印机添加
            Route::rule("attrBindPrinterAdd",'dishAttribute/attrBindPrinterAdd','post/options');

            //属性绑定打印机删除
            Route::rule("attrBindPrinterDelete",'dishAttribute/attrBindPrinterDelete','post/options');





        });
    });

    //财务管理
    Route::group(['name' => 'finance'],function (){

        //充值单据管理
        Route::group(['name' => 'refillOrder'],function (){

            //单据状态组获取
            Route::rule('orderStatus','Recharge/orderStatus');

            //充值单据列表
            Route::rule('index','Recharge/order','post|options');

            //新增充值信息
            Route::rule('addRecharge','Recharge/addRechargeOrder','post|options');

            //充值收款操作
            Route::rule('receipt','Recharge/receipt','post|options');

        });

        //预约定金单据管理
        Route::group(['name' => 'appointmentDeposit'],function (){

            //单据状态分组获取
            Route::rule('orderStatus','AppointmentDeposit/orderStatus','post|options');

            //预约定金单据列表
            Route::rule('index','AppointmentDeposit/index','post|options');

            //预约定金收款操作
            Route::rule('receipt','AppointmentDeposit/receipt','post|options');

            //预约定金退款操作
            Route::rule('refund','AppointmentDeposit/refund','post|options');

        });

    });

    //系统设置
    Route::group(['name' => 'system'],function (){

        //管理员路由组
        Route::group(['name' => 'manager'],function (){
            //管理员列表
            Route::rule('index','admin/index','post|options');

            //登陆管理员详细
            Route::rule("detail",'admin/detail','post|options');

            //添加管理员
            Route::rule("create",'admin/create','post|options');

            //编辑管理员
            Route::rule("edit",'admin/edit','post|options');

            //删除管理员
            Route::rule("delete",'admin/delete','post|options');

            //更改管理员密码
            Route::rule("changePass",'admin/changeManagerPass','post|options');

            //修改自身信息
            Route::rule("changeManagerInfo","admin/changeManagerInfo",'post|options');

        });

        //角色路由组
        Route::group(['name' => 'roles'],function (){
            //角色一览
            Route::rule('index','roles/index','post|options');

            //角色添加
            Route::rule('add','roles/add','post|options');

            //角色编辑
            Route::rule('edit','roles/edit','post|options');

            //角色删除
            Route::rule('delete','roles/delete','post|options');

        });

        //设置
        Route::group(['name' => 'setting'],function (){
            //设置类型列表
            Route::rule('lists','setting/lists','post|options');

            //类型下详情获取
            Route::rule('get_info','setting/get_info','post|options');

            //编辑系统设置
            Route::rule('edit','setting/edit','post|options');

            //添加系统设置
            Route::rule('create','setting/create','post|options');

        });

        //系统日志
        Route::group(['name' => 'sysLog'],function (){
            //系统日志列表
            Route::rule('sysLogList','SysLog/sysLogList');
        });
    });

});

//前台管理路由群组
Route::group(['name' => 'reception','prefix' => 'reception/'],function (){

    //后台操作预约定金退款
    Route::rule('adminRefundDeposit','DiningRoom/adminRefundDeposit','post|options');


    //基础操作
    Route::group(['name' => 'auth'],function (){
        //登陆
        Route::rule('login','Auth/login');
    });

    //堂吃
    Route::group(['name' => 'diningRoom'],function (){

        //首页
        Route::rule('index','DiningRoom/todayTableInfo','post|options');

        //桌位详情
        Route::rule('tableInfo','DiningRoom/tableInfo','post|options');

        //手机号码检索
        Route::rule('phoneRetrieval','DiningRoom/phoneRetrieval','post|options');

        //开台
        Route::rule('openTable','DiningRoom/openTable','post|options');

        //补全已开台用户信息
        Route::rule('supplementInfo','DiningRoom/supplementRevenueInfo','post|options');

        //开拼
        Route::rule('openSpelling','DiningRoomTurnSpelling/openSpelling','post|options');

        //获取今日已开台或者空台的桌
        Route::rule('alreadyOpenTable','DiningRoomTurnSpelling/alreadyOpenTable','post|options');

        //转拼
        Route::rule('turnSpelling','DiningRoomTurnSpelling/turnSpelling','post|options');

        //转台
        Route::rule('turnTable','DiningRoomTurnSpelling/turnTable','post|options');
    });

    //预定
    Route::group(['name' => 'reservation'],function (){

        //预定列表
        Route::rule('index','Reservation/index','post|options');

        //桌位详情
        Route::rule('tableDetails','Reservation/tableDetails','post|options');

        //预约确认
        Route::rule('reservationConfirm','Reservation/createReservation','post|options');

        //取消预约
        Route::rule('cancelReservation','Reservation/cancelReservation','post|options');
    });
});
