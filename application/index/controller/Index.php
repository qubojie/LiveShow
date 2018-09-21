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
        dump($res);die;

    }

}
