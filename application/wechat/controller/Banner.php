<?php
/**
 * 首页banner
 * User: qubojie
 * Date: 2018/8/1
 * Time: 上午11:58
 */
namespace app\wechat\controller;

use app\admin\model\PageBanner;
use think\Controller;

class Banner extends Controller
{
    /**
     * 首页banner列表
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function index()
    {
        $bannerModel = new PageBanner();

        $list = $bannerModel
            ->where('is_show',1)
            ->order("sort")
            ->field("is_show,created_at,updated_at",true)
            ->select();

        $list = json_decode(json_encode($list),true);
        return $this->com_return(true,config("params.SUCCESS"),$list);
    }
}