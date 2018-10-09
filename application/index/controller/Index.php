<?php
namespace app\index\controller;

use app\admin\controller\Common;
use app\admin\model\MstCardVip;
use app\admin\model\MstCardVipGiftRelation;
use app\admin\model\MstGift;
use app\admin\model\MstUserLevel;
use app\admin\model\User;
use app\wechat\model\BillCardFees;
use app\wechat\model\BillCardFeesDetail;
use app\wechat\model\BillPayAssist;
use app\wechat\model\BillPayDetail;
use think\Config;
use think\Controller;
use think\Db;
use think\Request;

class Index extends Controller
{
    const userModel = "123";
    public function index()
    {
        return $this->fetch();
    }


    public function test(Request $request)
    {
        $bar_key = $request->param("bar_key", "");
        $menu = [
            'menu_one' => [
                'color' => '#000',
                'selectedColor' => '#999',
                'list' => [
                    [
                        'pagePath' =>  'pages/index/main',
                        'text' => '我的',
                        'iconPath' => '/static/img/caidanlan-lineicon-xiaoxi@2x.png',
                        'selectedIconPath' => '/static/img/caidanlan-blockicon-xiaoxi@2x.png'
                    ],
                    [
                        'pagePath' =>  'pages/workbench/main',
                        'text' => '消息',
                        'iconPath' => '/static/img/caidanlan-lineicon-xiaoxi@2x.png',
                        'selectedIconPath' => '/static/img/caidanlan-blockicon-xiaoxi@2x.png'
                    ]
                ],
            ],
            'menu_two' => [
                'color' => '#345',
                'selectedColor' => '#890',
                'list' => [
                    [
                        'pagePath' =>  'pages/myPage/main',
                        'text' => '你的',
                        'iconPath' => '/static/img/caidanlan-lineicon-xiaoxi@2x.png',
                        'selectedIconPath' => '/static/img/caidanlan-blockicon-xiaoxi@2x.png'
                    ],
                    [
                        'pagePath' =>  'pages/index/main',
                        'text' => '他的',
                        'iconPath' => '/static/img/caidanlan-lineicon-wode@2x.png',
                        'selectedIconPath' => '/static/img/caidanlan-blockicon-wode@2x.png'
                    ],
                    [
                        'pagePath' =>  'pages/workbench/main',
                        'text' => '不要的',
                        'iconPath' => '/static/img/caidanlan-lineicon-xiaoxi@2x.png',
                        'selectedIconPath' => '/static/img/caidanlan-blockicon-xiaoxi@2x.png'
                    ]
                ]
            ]
        ];

        $is_exist = 0;
        foreach ($menu as $key => $val) {
            if ($key == $bar_key){
                $is_exist = 1;
                return json($this->com_return(true,\config("params.SUCCESS"),$menu[$key]));
            }
        }

        if (!$is_exist){
            return json($this->com_return(false,\config("params.FAIL")));
        }
    }

}
