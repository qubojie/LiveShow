<?php
/**
 * Created by PhpStorm.
 * User: qubojie
 * Date: 2018/9/14
 * Time: 下午4:31
 */
namespace app\wechat\controller;

use app\common\controller\UUIDUntil;
use app\wechat\model\BillPayAssist;
use think\Db;
use think\Exception;
use think\Request;
use think\Validate;

class ManageUseVoucher extends HomeAction
{
    /**
     * 申请使用礼券
     * @param Request $request
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function applyUseVoucher(Request $request)
    {
        $gift_vou_code = $request->param("gift_vou_code","");//礼券码

        $table_id      = $request->param("table_id","");//桌id

        $table_no      = $request->param("table_no","");//桌号

        $rule = [
            "gift_vou_code|礼券码" => "require",
            "table_id|桌id"        => "require",
            "table_no|桌号"        => "require",
        ];

        $request_res = [
            "gift_vou_code" => $gift_vou_code,
            "table_id"      => $table_id,
            "table_no"      => $table_no,
        ];

        $validate = new Validate($rule);

        if (!$validate->check($request_res)){
            return $this->com_return(false,$validate->getError(),null);
        }

        if (empty($gift_vou_code)){
            return $this->com_return(false,config("params.PARAM_NOT_EMPTY"));
        }

        $publicObj = new PublicAction();

        $res = $publicObj->checkVoucherValid($gift_vou_code);

        if (!$res['result']){
            return $res;
        }

        $voucherInfo = $res['data'];

        //插入礼券使用单据
        $UUID = new UUIDUntil();

        $pid  = $UUID->generateReadableUUID("P");

        $uid  = $voucherInfo['uid'];

        $userInfo = Db::name("user")
            ->alias("u")
            ->join("user_card uc","uc.uid = u.uid","LEFT")
            ->join("mst_card_vip cv","cv.card_id = uc.card_id","LEFT")
            ->field("u.phone")
            ->field("cv.card_name")
            ->where("u.uid",$uid)
            ->find();

        $phone     = $userInfo['phone'];
        $card_name = $userInfo['card_name'];

        if (empty($card_name)){
            $card_name = "非会员";
        }

        $params = [
            "pid"           => $pid,
            "uid"           => $uid,
            "card_name"     => $card_name,
            "phone"         => $phone,
            "table_id"      => $table_id,
            "table_no"      => $table_no,
            "type"          => config("bill_assist.bill_type")['6']['key'],
            "sale_status"   => config("bill_assist.bill_status")['0']['key'],
            "gift_vou_code" => $gift_vou_code,
            "created_at"    => time(),
            "updated_at"    => time()
        ];

        $billAssistModel = new BillPayAssist();

        $is_ok = $billAssistModel
            ->insert($params);

        if ($is_ok){
            return $this->com_return(true,config("params.SUCCESS"));
        }else{
            return $this->com_return(false,config("params.FAIL"));
        }
    }
}