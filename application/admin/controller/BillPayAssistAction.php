<?php
/**
 * Created by PhpStorm.
 * User: qubojie
 * Date: 2018/9/18
 * Time: 上午11:37
 */
namespace app\admin\controller;

use app\wechat\model\BillPayAssist;
use think\Request;

class BillPayAssistAction extends CommandAction
{

    /**
     * 手动协助消费单缴费单列表
     * @param Request $request
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function index(Request $request)
    {
        $billPayAssistModel = new BillPayAssist();

        $list = $billPayAssistModel
            ->select();

        return $this->com_return(true,config("params.SUCCESS"),$list);
    }

}