<?php
/**
 * 获取系统设置的相关信息
 * User: qubojie
 * Date: 2018/7/4
 * Time: 下午3:33
 */
namespace app\wechat\controller;

use app\admin\controller\Common;
use app\wechat\model\SysSetting;
use think\Controller;
use think\Request;

class GetSettingInfo extends Controller
{

    /**
     * 获取需要的后台设置的系统信息
     * @param Request $request
     * @return array
     */
    public function getSettingInfo(Request $request)
    {
        $common = new Common();
        $settingModel = new SysSetting();

        $keys = $request->param('key',"");

        if (empty($keys)){
            return $common->com_return(false,config("PARAM_NOT_EMPTY"));
        }

        $key_array = explode(",",$keys);

        $res = array();
        foreach ($key_array as $key => $value){
            $info = $settingModel
                ->where('key',$value)
                ->field('value')
                ->find();
            $info = json_decode($info,true);
            $res[$value] = $info['value'];

        }
        return $common->com_return(true,config("SUCCESS"),$res);
    }
}