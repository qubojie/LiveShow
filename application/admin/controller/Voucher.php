<?php
/**
 * 开卡赠卷设置.
 * User: qubojie
 * Date: 2018/6/23
 * Time: 上午11:20
 */
namespace app\admin\controller;


use app\admin\model\MstGiftVoucher;
use app\common\controller\MakeQrCode;
use think\Db;
use think\Exception;
use think\Request;
use think\Validate;

class Voucher extends CommandAction
{
    /**
     * 礼券列表
     * @param Request $request
     * @return array
     */
    public function index(Request $request)
    {
        $common     = new Common();

        $pagesize   = $request->param("pagesize", config('PAGESIZE'));//显示个数,不传时为10

        $nowPage    = $request->param("nowPage", "1");

        $is_enable  = $request->param("is_enable","");//是否只展示激活的卡

        $orderBy    = $request->param("orderBy","");

        $sort       = $request->param("sort","asc");

        if (empty($orderBy)){
            $orderBy = "gift_vou_id";
        }

        if (empty($sort)){
            $sort = "asc";
        }

        $enable_where = [];

        if ($is_enable == '1'){
            $enable_where['is_enable'] = ['eq','1'];
        }
        if ($is_enable == '0'){
            $enable_where['is_enable'] = ['neq','1'];
        }


        if (empty($pagesize)) {
            $pagesize = config('PAGESIZE');
        }

        $gitVoucherModel = new MstGiftVoucher();

        $count = $gitVoucherModel->count();//总记录数

        $pageNum = ceil($count / $pagesize); //总页数

        $config = [
            "page" => $nowPage,
        ];

        $gift_voucher_list = $gitVoucherModel
            ->where('is_delete', '0')
            ->where($enable_where)
            ->order($orderBy,$sort)
            ->paginate($pagesize, false, $config);

        $gift_voucher_list = json_decode(json_encode($gift_voucher_list),true);

        $gift_voucher_list['filter']['orderBy'] = $orderBy;
        $gift_voucher_list['filter']['sort']    = $sort;

        $list = $gift_voucher_list['data'];

        for ($i = 0; $i < count($list); $i++){

            /*$gift_vou_type = $list[$i]['gift_vou_type'];//赠券类型   ‘once’单次    ‘multiple’多次   ‘limitless’ 无限制

            $voucher_type_config = config('voucher.type');

            for ($m=0;$m<count($voucher_type_config);$m++){
                while ($voucher_type_config[$m]['key'] == $gift_vou_type){
                    $gift_vou_type = $voucher_type_config[$m]['name'];
                }
            }

            $gift_voucher_list['data'][$i]['gift_vou_type'] = $gift_vou_type;*/

            $gift_validity_type     = $list[$i]['gift_validity_type'];//类型
            $gift_vou_validity_day  = (int)$list[$i]['gift_vou_validity_day'];//有效天数
            $gift_start_day         = $list[$i]['gift_start_day'];//有效开始时间
            $gift_end_day           = $list[$i]['gift_end_day'];//结束时间

            if ($gift_validity_type == '1'){
                //如果有效期类型为 按天数生效
                $gift_rule_info = "{'gift_time':'".$gift_start_day."','gift_validity_day':'".$gift_vou_validity_day."'}";


            }elseif ($gift_validity_type == '2'){
                //如果类型为 指定了有效日期
                $gift_rule_info = "{'gift_time':'".$gift_start_day.",".$gift_end_day."','gift_validity_day':'".$gift_vou_validity_day."'}";


            }else{
                //如果类型为 0 无限期
                $gift_rule_info = "{'gift_time':'".$gift_start_day."','gift_validity_day':'".$gift_vou_validity_day."'}";

            }

            //将int型的数据转换为string
            $gift_voucher_list['data'][$i] = arrIntToString($gift_voucher_list['data'][$i]);

            $gift_voucher_list['data'][$i]['gift_rule_info'] = $gift_rule_info;
        }

        return $common->com_return(true, config("GET_SUCCESS"), $gift_voucher_list);
    }

    /**
     * 礼券添加
     * @param Request $request
     * @return array
     */
    public function add(Request $request)
    {
        $common = new Common();

        $gift_vou_type         = $request->param("gift_vou_type", "");         //赠券类型  ‘once’单次    ‘multiple’多次   ‘limitless’ 无限制
        $gift_vou_name         = $request->param("gift_vou_name", "");         //礼券名称标题
        $gift_vou_desc         = $request->param("gift_vou_desc", "");         //礼券详细描述
        $gift_vou_amount       = $request->param("gift_vou_amount", "");       //礼券金额
        $gift_validity_type    = $request->param("gift_validity_type", "");    //有效类型   0无限期   1按天数生效   2按指定有效日期
        $gift_rule_info        = $request->param('gift_rule_info','');//json规则
        $gift_vou_exchange     = $request->param("gift_vou_exchange", "");     //兑换规则 （保存序列）
        $qty_max               = $request->param("qty_max", "");     //单日最大使用数量    无限制卡表示单日最大使用数量
        $is_enable             = $request->param("is_enable", 0);             //是否启用  0否 1是

        $rule = [
            "gift_vou_type|赠券类型"                  => "require",
            "gift_vou_name|礼券名称标题"               => "require|unique_delete:mst_gift_voucher",
            "gift_vou_amount|礼券金额"                => "require",
            "gift_validity_type|礼券有效类型"          => "require",
            "gift_vou_exchange|兑换规则"              => "require",
            "is_enable|是否启用"                      => "require",
        ];

        $request_res = [
            "gift_vou_type"         => $gift_vou_type,
            "gift_vou_name"         => $gift_vou_name,
            "gift_vou_amount"       => $gift_vou_amount,
            "gift_validity_type"    => $gift_validity_type,
            "gift_vou_exchange"     => $gift_vou_exchange,
            "is_enable"             => $is_enable,
        ];

        $validate = new Validate($rule);

        if (!$validate->check($request_res)) {
            return $common->com_return(false, $validate->getError(), null);
        }

        $gift_rule_info = json_decode($gift_rule_info,true);
        if ($gift_validity_type == '1'){
            //如果有效期类型为 按天数生效
            $gift_time             = $gift_rule_info['gift_time'];
            $gift_validity_day     = $gift_rule_info['gift_validity_day'];

            //给前端将秒时间戳转换为毫秒
            if (strlen($gift_time) > 11){
                $gift_time = (int)($gift_time * 0.001);
            }

            if (!empty($gift_time)){
                $gift_start_day        = $gift_time;//有效开始时间
                $gift_end_day          = $gift_time + $gift_validity_day * 24 * 60 * 60;
                $gift_vou_validity_day = $gift_validity_day;
            }else{
                $gift_start_day        = "";
                $gift_end_day          = "";
                $gift_vou_validity_day = $gift_validity_day;
            }

        }elseif ($gift_validity_type == '2'){
            //如果类型为 指定了有效日期
            $gift_time             = $gift_rule_info['gift_time'];
            $gift_time_arr         = explode(",",$gift_time);

            if (count($gift_time_arr) < 2){
                return $this->com_return(false,'时间范围不正确');
            }
            $gift_start_day        = $gift_time_arr[0];
            $gift_end_day          = $gift_time_arr[1];

            if (strlen($gift_start_day) > 11){
                $gift_start_day = (int)($gift_start_day * 0.001);
            }

            if (strlen($gift_end_day) > 11){
                $gift_end_day = (int)($gift_end_day * 0.001);
            }

            $gift_vou_validity_day = "";


        }else{
            //如果类型为 0 无限期
            $gift_time = $gift_rule_info['gift_time'];

            if (strlen($gift_time) > 11){
                $gift_time = (int)($gift_time * 0.001);
            }

            if (!empty($gift_time)){
                //如果设置了有效开始时间
                $gift_start_day        = $gift_time;//有效开始时间
                $gift_end_day          = "";
                $gift_vou_validity_day = 0; //有效时间无限期

            }else{
                $gift_start_day = "";
                $gift_end_day = "";
                $gift_vou_validity_day = 0;
            }
        }


        $gitVoucherModel = new MstGiftVoucher();

        $time = time();

        $insert_data = [
            "gift_vou_type"         => $gift_vou_type,
            "gift_vou_name"         => $gift_vou_name,
            "gift_vou_desc"         => $gift_vou_desc,
            "gift_vou_amount"       => $gift_vou_amount,
            "gift_validity_type"    => $gift_validity_type,
            "gift_vou_validity_day" => $gift_vou_validity_day,
            "gift_start_day"        => $gift_start_day,
            "gift_end_day"          => $gift_end_day,
            "gift_vou_exchange"     => $gift_vou_exchange,
            "qty_max"               => $qty_max,
            "is_enable"             => $is_enable,
            "created_at"            => $time,
            "updated_at"            => $time
        ];

        Db::startTrans();
        try {
            $is_ok = $gitVoucherModel
                ->insert($insert_data);

            if ($is_ok) {
                Db::commit();
                return $common->com_return(true, config("ADD_SUCCESS"));
            } else {
                return $common->com_return(false, config("ADD_FAIL"));
            }
        } catch (Exception $e) {
            Db::rollback();
            return $common->com_return(false, $e->getMessage(), null);
        }

    }

    /**
     * 礼券编辑
     * @param Request $request
     * @return array
     */
    public function edit(Request $request)
    {
        $common = new Common();

        $gift_vou_id            = $request->param("gift_vou_id", "");  //礼券id
        $gift_vou_type          = $request->param("gift_vou_type", "");  //赠券类型  ‘once’单次    ‘multiple’多次   ‘limitless’ 无限制
        $gift_vou_name          = $request->param("gift_vou_name", "");      //礼券名称标题
        $gift_vou_desc          = $request->param("gift_vou_desc", "");      //礼券详细描述
        $gift_vou_amount        = $request->param("gift_vou_amount", "");    //礼券金额
        $gift_validity_type     = $request->param('gift_validity_type',''); //有效类型   0无限期   1按天数生效   2按指定有效日期
        $gift_rule_info         = $request->param('gift_rule_info','');//json规则
        $gift_vou_exchange      = $request->param("gift_vou_exchange", "");     //兑换规则 （保存序列）
        $qty_max                = $request->param("qty_max", "");     //最大使用数量    无限制卡表示单日最大使用数量

        $rule = [
            "gift_vou_id|赠券id"                       => "require",
            "gift_vou_type|赠券类型"                    => "require",
            "gift_vou_name|礼券名称标题"                => "require|unique_delete:mst_gift_voucher,gift_vou_id",
            "gift_vou_amount|礼券金额"                  => "require",
            "gift_vou_exchange|兑换规则"                => "require",
        ];

        $request_res = [
            "gift_vou_id"           => $gift_vou_id,
            "gift_vou_type"         => $gift_vou_type,
            "gift_vou_name"         => $gift_vou_name,
            "gift_vou_amount"       => $gift_vou_amount,
            "gift_vou_exchange"     => $gift_vou_exchange,
        ];

        $validate = new Validate($rule);

        if (!$validate->check($request_res)) {
            return $common->com_return(false, $validate->getError(), null);
        }


        $gift_rule_info = json_decode($gift_rule_info,true);

        if ($gift_validity_type == '1'){
            //如果有效期类型为 按天数生效
            $gift_time             = $gift_rule_info['gift_time'];
            $gift_validity_day     = $gift_rule_info['gift_validity_day'];

            if (strlen($gift_time) > 11){
                $gift_time = (int)($gift_time * 0.001);
            }

            if (!empty($gift_time)){
                $gift_start_day        = $gift_time;//有效开始时间
                $gift_end_day          = $gift_time + $gift_validity_day * 24 * 60 * 60;
                $gift_vou_validity_day = $gift_validity_day;
            }else{
                $gift_start_day        = "";
                $gift_end_day          = "";
                $gift_vou_validity_day = $gift_validity_day;
            }

        }elseif ($gift_validity_type == '2'){
            //如果类型为 指定了有效日期
            $gift_time             = $gift_rule_info['gift_time'];
            $gift_time_arr         = explode(",",$gift_time);
            $gift_start_day        = $gift_time_arr[0];
            $gift_end_day          = $gift_time_arr[1];
            $gift_vou_validity_day = "";

            if (strlen($gift_start_day) > 11){
                $gift_start_day = (int)($gift_start_day * 0.001);
            }

            if (strlen($gift_end_day) > 11){
                $gift_end_day = (int)($gift_end_day * 0.001);
            }


        }else{
            //如果类型为 0 无限期
            $gift_time = $gift_rule_info['gift_time'];

            if (strlen($gift_time) > 11){
                $gift_time = (int)($gift_time * 0.001);
            }

            if (!empty($gift_time)){
                //如果设置了有效开始时间
                $gift_start_day        = $gift_time;//有效开始时间
                $gift_end_day          = "";
                $gift_vou_validity_day = 0; //有效时间无限期
            }else{
                $gift_start_day = "";
                $gift_end_day = "";
                $gift_vou_validity_day = 0;
            }
        }

        $gitVoucherModel = new MstGiftVoucher();

        $time = time();

        $update_data = [
            "gift_vou_type"         => $gift_vou_type,
            "gift_vou_name"         => $gift_vou_name,
            "gift_vou_desc"         => $gift_vou_desc,
            "gift_vou_amount"       => $gift_vou_amount,
            "gift_validity_type"    => $gift_validity_type,
            "gift_vou_validity_day" => $gift_vou_validity_day,
            "gift_start_day"        => $gift_start_day,
            "gift_end_day"          => $gift_end_day,
            "gift_vou_exchange"     => $gift_vou_exchange,
            "qty_max"               => $qty_max,
            "updated_at"            => $time
        ];

        Db::startTrans();
        try {
            $is_ok = $gitVoucherModel
                ->where('gift_vou_id', $gift_vou_id)
                ->update($update_data);

            if ($is_ok !== false) {
                Db::commit();
                return $common->com_return(true, config("EDIT_SUCCESS"));
            } else {
                return $common->com_return(false, config("EDIT_FAIL"));
            }
        } catch (Exception $e) {
            Db::rollback();
            return $common->com_return(false, $e->getMessage(), null);
        }
    }

    /**
     * 礼券删除
     * @param Request $request
     * @return array
     */
    public function delete(Request $request)
    {
        $common = new Common();

        $gift_vou_ids = $request->param("gift_vou_id", "");  //礼券id

        if (!empty($gift_vou_ids)) {
            $id_array = explode(",", $gift_vou_ids);

            $time = time();

            $update_data = [
                "is_delete" => "1",
                "updated_at" => $time
            ];
            $gitVoucherModel = new MstGiftVoucher();

            Db::startTrans();
            try {
                $is_ok = false;
                foreach ($id_array as $gift_vou_id) {
                    $is_ok = $gitVoucherModel
                        ->where("gift_vou_id", $gift_vou_id)
                        ->update($update_data);
                }
                if ($is_ok !== false) {
                    Db::commit();
                    return $common->com_return(true, config("DELETE_SUCCESS"));
                } else {
                    return $common->com_return(false, config("DELETE_FAIL"));
                }
            } catch (Exception $e) {
                Db::rollback();
                return $common->com_return(false, $e->getMessage(), null);
            }

        } else {
            return $common->com_return(false, config("PARAM_NOT_EMPTY"));
        }
    }

    /**
     * 是否启用
     * @param Request $request
     * @return array
     */
    public function enable(Request $request)
    {
        $common = new Common();

        $is_enable     = (int)$request->param("is_enable","");
        $gift_vou_id   = $request->param("gift_vou_id","");

        if ($is_enable == "1"){
            $success_message = "启用成功";
            $fail_message = "启用失败";
        }else{
            $success_message = "关闭成功";
            $fail_message = "关闭失败";
        }

        if (!empty($gift_vou_id)){
            $update_data = [
                "is_enable"  => $is_enable,
                "updated_at" => time()
            ];

            $gitVoucherModel = new MstGiftVoucher();

            Db::startTrans();
            try{
                $is_ok = $gitVoucherModel
                    ->where("gift_vou_id",$gift_vou_id)
                    ->update($update_data);
                if ($is_ok !== false){
                    Db::commit();
                    return $common->com_return(true, $success_message);
                }else{
                    return $common->com_return(false, $fail_message);
                }
            }catch (Exception $e){
                Db::rollback();
                return $common->com_return(false, $e->getMessage());
            }
        }else {
            return $common->com_return(false, "缺少参数");
        }
    }

    /**
     * 生成二维码
     */
    public function makeQrCode(Request $request)
    {
        $arr = [
            'uid' => 'U1807191310549403287',
            'vid' => 'V180716163228380F72A',
        ];
        $json = json_encode($arr);
        $savePath = APP_PATH . '/../public/upload/qrcode/';
        $webPath = 'upload/qrcode/';

        $qrData = $json;

        $qrLevel = 'H';

        $qrSize = '8';

        $savePrefix = 'V';

        $QrCodeObj = new MakeQrCode();
        $qrCode = $QrCodeObj->createQrCode($savePath, $qrData, $qrLevel, $qrSize, $savePrefix);
        if ($qrCode){
            $pic = $webPath . $qrCode;
        }else{
            $pic = null;
        }
        dump($pic);die;
    }
}