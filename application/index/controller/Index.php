<?php
namespace app\index\controller;

use app\admin\model\MstCardVip;
use app\admin\model\MstCardVipGiftRelation;
use app\admin\model\MstGift;
use app\admin\model\User;
use app\wechat\model\BillCardFees;
use app\wechat\model\BillCardFeesDetail;
use think\Config;
use think\Controller;
use think\Db;
use think\Request;

class Index extends Controller
{
    public function index()
    {
        return $this->fetch();
    }

    public function test(Request $request)
    {
        //获取表信息
        $model = new BillCardFeesDetail();
        $res = $model
            ->select();
        $res = json_decode(json_encode($res),true);
        dump($res);die;
    }
}
