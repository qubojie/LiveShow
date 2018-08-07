<?php
/**
 * 会员性格,兴趣,对接资源,技能等标签
 * User: qubojie
 * Date: 2018/6/27
 * Time: 上午10:14
 */
namespace app\wechat\controller;

use app\admin\controller\Common;
use app\admin\model\SysSetting;
use app\admin\model\User;
use app\common\controller\SortName;
use app\wechat\model\BcUserInfo;
use app\wechat\model\BillCardFees;
use function Couchbase\fastlzCompress;
use think\Controller;
use think\Db;
use think\Exception;
use think\Request;
use think\Validate;

class UserInfo extends Controller
{
    /**
     * 获取标签信息
     */
    public function tagList()
    {
        $common = new Common();

        $sysSettingModel = new SysSetting();

        $key_arr = $sysSettingModel
            ->where('ktype','user')
            ->field('key')
            ->order("sort",'asc')
            ->select();

        $key_arr = json_decode(json_encode($key_arr),true);

        $value = array();

        for ($i=0;$i<count($key_arr);$i++) {
            $key = $key_arr[$i]['key'];

            $value[$key] = $sysSettingModel
                ->where('key',$key)
                ->field('value')
                ->find();
        }

        return $common->com_return(true,config("SUCCESS"),$value);
    }

    /*
     * 完善用户信息
     * */
    public function postInfo(Request $request)
    {
        $common = new Common();

        $phone        = $request->param("phone","");//电话号码
        $sex          = $request->param("sex","男士");//性别,前台直接传的文字
        $birthday     = $request->param("birthday","");//生日
        $blood        = $request->param("blood","");//血型
        $nation       = $request->param("nation","");//民族
        $native_place = $request->param("native_place","");//籍贯
        $stature      = $request->param("stature","");//身高 cm
        $weight       = $request->param("weight","");//体重 kg
        $car_no       = $request->param("car_no","");//车牌号
        $profession   = $request->param("profession","");//职业
        $interest     = $request->param("interest","");//兴趣
        $skill        = $request->param("skill","");//技能
        $character    = $request->param("character","");//性格
        $need         = $request->param("need","");//希望资源

        $params = $request->param();

        $rule = [
            "phone|电话号码"    => "require",
//            "interest|兴趣爱好" => "require",
//            "skill|技能"       => "require",
//            "character|性格"   => "require",
//            "profession|职业"  => "require",
//            "need|希望资源"     => "require",
        ];

        $request_res = [
            "phone"     => $phone,
//            "interest"  => $interest,
//            "skill"     => $skill,
//            "character" => $character,
//            "profession"=> $profession,
//            "need"      => $need,
        ];

        $validate = new Validate($rule);

        if (!$validate->check($request_res)){
            return $common->com_return(false,$validate->getError(),null);
        }

        $uid_res = $this->getUid($phone);

        if ($uid_res['result'] == false){
            return $common->com_return(false,config('FAIL'));
        }

        //更新用户性别
        $this->updateSex($phone,$sex);

        $uid = $uid_res['data'];

        $params["uid"] = $uid;

        //移除参数 phone,sex
        if(isset($params['phone'])){
            $params = $common->bykey_reitem($params,"phone");
        }
        if (isset($params['sex'])){
            $params = $common->bykey_reitem($params,"sex");
        }

        //如果生日不为空,获取年龄,星座,属相
        if (!empty($birthday)){
            $constellationObj = new AgeConstellation();
            $nxs = $constellationObj->getInfo($birthday);
            $constellation = $nxs['constellation'];
            $params['astro'] = $constellation;
        }

        if (!empty($interest)){
            //如果用户填写了兴趣等标签,则更新用户信息状态为已完善
            $this->updateInfoStatus($phone);
        }

        return $this->insertUserInfo($params);
    }

    /**
     * 根据电话号码获取当前用户uid
     * @param $phone
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getUid($phone)
    {
        $common = new Common();

        $userModel = new User();

        $uid_res = $userModel
            ->where('phone',$phone)
            ->field('uid')
            ->find();

        if (!empty($uid_res)){
            $uid = $uid_res['uid'];
            return $common->com_return(true,config("SUCCESS"),$uid);
        }else{
            return $common->com_return(false,config("FAIL"));
        }
    }

    /*
     * 将补全信息资料写入数据库
     * */
    public function insertUserInfo($params=array())
    {
        $common = new Common();

        $userInfoModel = new BcUserInfo();

        $uid = $params["uid"];

        //查看当前是否存在当前用户的信息
        $is_exist = $userInfoModel
            ->where("uid",$uid)
            ->count();

        $time = time();

        $is_ok = false;

        if ($is_exist){
            //存在则更新

            $params["updated_at"] = $time;

            $is_ok = $userInfoModel->where('uid',$uid)->update($params);

        }else{
            //不存在则新增

            $params["created_at"] = $time;
            $params["updated_at"] = $time;

            $is_ok = $userInfoModel->insert($params);

        }

        if ($is_ok){
            return $common->com_return(true,config("SUCCESS"),$uid);
        }else{
            return $common->com_return(false,config("FAIL"));
        }
    }

    /**
     * 更新性别
     * @param $phone
     * @param $sex
     *
     * @return false
     */
    public function updateSex($phone,$sex)
    {
        $userModel = new User();

        $data = [
            'sex' => $sex
        ];

        $is_ok = $userModel->where('phone',$phone)->update($data);

        if ($is_ok){
            return true;
        }else{
            return false;
        }
    }

    /**
     * 更新用户信息状态为已完善
     */
    public function updateInfoStatus($phone)
    {
        $userModel = new User();

        $data = [
            'info_status' => config("user.user_info")['complete']['key']
        ];

        $is_ok = $userModel->where('phone',$phone)->update($data);

        if ($is_ok){
            return true;
        }else{
            return false;
        }
    }


    /**
     * 获取用户指定字段值
     * @param $uid
     * @param $field
     * @return array|false|\PDOStatement|string|\think\Model
     */
    public function getUserFieldValue($uid,$field)
    {
        $userModel = new User();
        $res = $userModel
            ->where('uid',$uid)
            ->field($field)
            ->find();
        $res = json_decode(json_encode($res),true);
        if (!empty($res)){
            $val = $res[$field];
        }else{
            $val = null;
        }

        return $val;

    }

    /**
     * 更新用户礼金账户余额
     * @param $uid
     * @param $num
     * @param $type
     * @return string
     */
    public function updatedAccountCashGift($uid,$num,$type)
    {
        $userModel = new User();

        if ($type == "inc"){
            //增加
            $res = $userModel->where('uid',$uid)
                ->inc("account_cash_gift",$num)
                ->update();
        }else if ($type == "dec"){
            //减少
            $res = $userModel->where('uid',$uid)
                ->dec("account_cash_gift",$num)
                ->update();
        }else{
            $res = false;
        }

        if ($res !== false){
            return true;
        }else{
            return false;
        }
    }

    public function getUserList()
    {
        $sortNameObj = new SortName();

        $userModel = new User();

        $user_list = $userModel
            ->select();

        $user_list = json_decode(json_encode($user_list),true);

        $user_name = [];

        for ($i = 0; $i <count($user_list); $i++){
            $user_name[$i] = $user_list[$i]['name'];
        }

        $charArray=array();
        foreach ($user_name as $name ){

            $char = $sortNameObj->getFirstChar($name);

            $nameArray = array();

            if(isset($charArray[$char])){

                $nameArray = $charArray[$char];

            }

            array_push($nameArray,$name);
            $charArray[$char] = $nameArray;

        }

        ksort($charArray);

        return $this->com_return(true,'成功',$charArray);

    }
}
