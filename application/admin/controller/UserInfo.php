<?php
/**
 * 会员管理-会员信息管理
 * User: qubojie
 * Date: 2018/7/4
 * Time: 下午4:10
 */
namespace app\admin\controller;

use app\admin\model\MstCardVip;
use app\admin\model\User;
use app\wechat\controller\WechatPay;
use app\wechat\model\BillCardFees;
use app\wechat\model\JobUser;
use app\wechat\model\UserAccount;
use app\wechat\model\UserAccountCashGift;
use app\wechat\model\UserAccountDeposit;
use app\wechat\model\UserAccountPoint;
use think\console\command\make\Model;
use think\Env;
use think\Request;

class UserInfo extends CommandAction
{

    /**
     * 会员状态类
     * @param Request $request
     * @return array
     */
    public function userStatus(Request $request)
    {
        $type = \config('user.user_status');

        foreach ($type as $key => $val){
            if ($key == 1){
                unset($type[$key]);
            }
        }
        $type = array_values($type);

        return $this->com_return(true,\config('SUCCESS'),$type);
    }

    /**
     * 获取卡种列表
     * @param Request $request
     * @return array
     */
    public function cardType(Request $request)
    {
        $cardModel = new MstCardVip();
        $card_list = $cardModel
            ->where('is_enable','1')
            ->where('is_delete','0')
            ->field('card_id,card_name')
            ->select();
        return $this->com_return(true,config('SUCCESS'),$card_list);
    }

    /**
     * 会员列表
     * @param Request $request
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function index(Request $request)
    {
        $common = new Common();

        $userModel = new User();

        $pagesize    = $request->param("pagesize",config('PAGESIZE'));//显示个数,不传时为10
        $orderBy     = $request->param("orderBy","");//根据什么排序
        $sort        = $request->param("sort","asc");//正序or倒叙
        $keyword     = $request->param("keyword","");//搜索关键字
        $user_status = $request->param("user_status","");//会员状态 0为已注册 1为提交订单,2开卡成功
        $card_name   = $request->param('card_name',"");//卡种

        if ($user_status == 2){
            $user_status_where['user_status'] = ["eq",2];
        }else{
            $user_status_where['user_status'] = ["neq",2];
        }

        $card_name_where = [];
        if (!empty($card_name)){
            $card_name_where['cfd.card_name'] = ["eq",$card_name];
        }

        if (empty($pagesize)){
            $pagesize = config('PAGESIZE');
        }

        if (empty($orderBy)){
            $orderBy = "created_at";
        }

        if (empty($sort)){
            $sort = "asc";
        }

        if (!empty($keyword)){
            $where['u.uid|u.phone|u.email|u.nickname|u.sex|u.province|u.city|u.country|uc.card_name'] = ["like","%$keyword%"];
        }else{
            $where = [];
        }

        $nowPage = $request->param("nowPage","1");

        $config = [
            "page" => $nowPage,
        ];

        $u_column = $userModel->u_column;

        $userCount = [];

        $openCardNum =  $userModel->where('user_status','2')->count();
        $notOpenCardNum = $userModel->where('user_status','neq 2')->count();

        $userCount['openCardNum']    = $openCardNum;
        $userCount['notOpenCardNum'] = $notOpenCardNum;


        $user_list = $userModel
            ->alias('u')
            ->join('user_card uc','uc.uid = u.uid','LEFT')
            ->join('mst_card_vip cv','cv.card_id = uc.card_id')
            ->where($where)
            ->where($card_name_where)
            ->where($user_status_where)
            ->field($u_column)
//            ->field('uc.card_name,uc.card_type,uc.created_at open_card_time')
            ->field('cv.card_name,cv.card_type,uc.created_at open_card_time')
            ->order("u.".$orderBy,$sort)
            ->paginate($pagesize,false,$config);

        $user_list = json_decode(json_encode($user_list),true);

        $user_list['filter']['orderBy'] = $orderBy;
        $user_list['filter']['sort'] = $sort;
        $user_list['filter']['keyword'] = $keyword;

        $data = $user_list['data'];

        //获取注册途径配置文件
        $register_way_arr = config("user.register_way");

        //会员状态配置文件
        $user_status_arr = config("user.user_status");

        //获取卡类型
        $card_type_arr = config("card.type");

        for ($i=0;$i<count($data);$i++){
            $referrer_type = $data[$i]['referrer_type'];
            $referrer_id   = $data[$i]['referrer_id'];
            $avatar        = $data[$i]['avatar'];
            $uid           = $data[$i]['uid'];

            /*默认头像填充 begin*/
            if (empty($avatar)){
                $data[$i]['avatar'] = Env::get("DEFAULT_AVATAR_URL")."avatar.jpg";
            }
            /*默认头像填充 off*/

            /*卡种翻译 begin*/
            $card_type_s = $data[$i]['card_type'];

            foreach ($card_type_arr as $key => $value){
                if ($card_type_s == $value["key"]){
                    $data[$i]['card_type_name'] = $value["name"];
                }
            }
            /*卡种翻译 off*/

            /*注册途径 begin*/
            $register_way_s = $data[$i]['register_way'];

            foreach ($register_way_arr as $key => $value){
                if ($register_way_s == $value["key"]){
                    $data[$i]['register_way_name'] = $value["name"];
                }
            }
            /*注册途径 off*/


            /*会员状态翻译 begin*/
            $user_status_s = $data[$i]['user_status'];
            foreach ($user_status_arr as $key => $value){
                if ($user_status_s == $value["key"]){
                    $data[$i]['user_status_name'] = $value["name"];
                }
            }

            /*会员状态翻译 off*/




            if ($referrer_type == 'user'){
                //去用户表查找推荐人信息
                $authObj   = new \app\wechat\controller\Auth();
                $user_info = $authObj->getUserInfo($referrer_id);
                $user_info = json_decode($user_info,true);
                $data[$i]['referrer_name'] = $user_info['nickname'];

                $data[$i]['referrer_type'] = "用户";

            }elseif ($referrer_type == 'sales' || $referrer_type == 'vip'){
                //去销售表查找推荐人信息
                $salesObj = new SalesUser();
                $sales_info = $salesObj->getSalesManInfo($referrer_id);
                $sales_info = json_decode($sales_info,true);
                $data[$i]['referrer_name'] = $sales_info['sales_name'];

                if ($referrer_type == 'sales'){
                    $data[$i]['referrer_type'] = config('salesman.salesman_type')[1]['name'];
                }else{
                    $data[$i]['referrer_type'] = config('salesman.salesman_type')[0]['name'];
                }

            }else{
                $data[$i]['referrer_name'] = "";
                $data[$i]['referrer_type']= "";
            }


            //获取用户是否有佣金账户余额
            $jobUserModel = new JobUser();
            $jobInfo = $jobUserModel
                ->where('uid',$uid)
                ->field('job_balance,job_freeze,job_cash,consume_amount,referrer_num')
                ->find();
            if (empty($jobInfo)){
                $jobInfo = [];
            };

            $data[$i]['job_user'] = $jobInfo;


            //获取用户日志文件
            $res = $this->getUserLog("$uid");

            $data[$i]['log_info'] = $res;


        }

        $user_list['userCount'] = $userCount;

        $user_list['data'] = $data;


        return $common->com_return(true,config("GET_SUCCESS"),$user_list);
    }

    /**
     * 编辑会员
     * @param Request $request
     * @return array
     */
    public function edit(Request $request)
    {
        $common = new Common();
        $userModel = new User();
        $uid = $request->param('uid','');
        if (empty($uid)){
            return $common->com_return(false,config('PARAM_NOT_EMPTY'));
        }
        $params = $request->param();

        $params['updated_at'] = time();
        $is_ok = $userModel
            ->where('uid',$uid)
            ->update($params);
        if ($is_ok !== false){
            return $common->com_return(false,config('EDIT_SUCCESS'));
        }else{
            return $common->com_return(false,config('EDIT_FAIL'));
        }
    }

    /**
     * 禁止登陆或解禁
     * @param Request $request
     * @return array
     */
    public function noLogin(Request $request)
    {
        $common = new Common();
        $userModel = new User();

        $uid = $request->param('uid','');
        $status = $request->param('status','0');
        if (empty($uid)){
            return $common->com_return(false,config('PARAM_NOT_EMPTY'));
        }
        if ($status == 0){
            $log_info = 'unban';
            $reason = "解禁用户";
        }else{
            $log_info = 'ban';
            $reason = "禁止此用户登陆";
        }

        $params = [
            'status' => $status
        ];

        //获取当前登录管理员id
        $action_user = $this->getLoginAdminId($request->header('Authorization'))['user_name'];

        $is_ok = $userModel
            ->where('uid',$uid)
            ->update($params);

        if ($is_ok !== false){

            //操作日志记录禁止登陆和解禁操作 time(),$user_id,$log_info,$request->ip()
            $this->addSysAdminLog("$uid","","","$log_info","$reason","$action_user",time());

            return $common->com_return(true,config('SUCCESS'));
        }else{
            return $common->com_return(false,config('FAIL'));
        }
    }

    /**
     * 后台操作变更会员密码
     * @param Request $request
     * @return array
     */
    public function changePass(Request $request)
    {
        $uid                   = $request->param('uid','');
        $password              = $request->param('password','');
        $password_confirmation = $request->param('password_confirmation','');

        if (empty($uid)){
            return $this->com_return(false,config("params.PARAM_NOT_EMPTY"));
        }
        if (empty($password) || empty($password_confirmation)){
            return $this->com_return(false,config("params.PASSWORD_NOT_EMPTY"));
        }
        if (strlen($password) < 6){
            return $this->com_return(false,config("params.LONG_NOT_ENOUGH"));
        }
        if ($password !== $password_confirmation){
            return $this->com_return(false,config("params.PASSWORD_DIF"));
        }

        $password = sha1($password);

        $token = $this->jm_token($password.time());

        $time = time();

        $params = [
            'token_lastime'  => $time,
            'remember_token' => $token,
            'password'       => $password,
            'updated_at'     => $time
        ];

        $userModel = new User();

        $is_ok = $userModel
            ->where('uid',$uid)
            ->update($params);
        if ($is_ok){
            //获取当前登录管理员
            $action_user = $this->getLoginAdminId($request->header('Authorization'))['user_name'];

            //添加至
            $this->addSysLog("$time","$action_user",config("useraction.change_user_pass")['name']." -> $uid",$request->ip());

            return $this->com_return(true,config("params.SUCCESS"));
        }else{
            return $this->com_return(false,config("params.FAIL"));
        }

    }


    /**
     * 用户操作日志信息获取
     * @param $uid
     * @return false|\PDOStatement|string|\think\Collection
     */
    public function getUserLog($uid)
    {
        $logObj = new Log();

        $res = $logObj->log_list("uid","$uid");

        for ($i = 0; $i < count($res); $i++){
            $action = $res[$i]['action'];
            $action_des = config("useraction.$action")['name'];

            $res[$i]['action'] = $action_des;

        }

       return $res;
    }

    /**
     * 会员钱包明细
     * @param Request $request
     * @return array
     * @throws \think\exception\DbException
     */
    public function accountInfo(Request $request)
    {
        $uid = $request->param('uid','');

        if (empty($uid)){
            return $this->com_return(false,config("params.PARAM_NOT_EMPTY"));
        }

        $pagesize    = $request->param("pagesize",config('PAGESIZE'));//显示个数,不传时为10

        if (empty($pagesize)){
            $pagesize = config('PAGESIZE');
        }


        $nowPage = $request->param("nowPage","1");

        $config = [
            "page" => $nowPage,
        ];

        $userAccountModel = new UserAccount();

        $list = $userAccountModel
            ->where('uid',$uid)
            ->paginate($pagesize,false,$config);

        return $this->com_return(true,config('params.SUCCESS'),$list);
    }


    /**
     * 会员押金明细
     * @param Request $request
     * @return array
     * @throws \think\exception\DbException
     */
    public function depositInfo(Request $request)
    {
        $uid = $request->param('uid','');

        if (empty($uid)){
            return $this->com_return(false,config("params.PARAM_NOT_EMPTY"));
        }

        $pagesize    = $request->param("pagesize",config('PAGESIZE'));//显示个数,不传时为10

        if (empty($pagesize)){
            $pagesize = config('PAGESIZE');
        }


        $nowPage = $request->param("nowPage","1");

        $config = [
            "page" => $nowPage,
        ];



        $userAccountDepositModel = new UserAccountDeposit();

        $list = $userAccountDepositModel
            ->where('uid',$uid)
            ->paginate($pagesize,false,$config);

        return $this->com_return(true,config('params.SUCCESS'),$list);
    }

    /**
     * 会员礼金明细
     * @param Request $request
     * @return array
     * @throws \think\exception\DbException
     */
    public function cashGiftInfo(Request $request)
    {
        $uid = $request->param('uid','');
        if (empty($uid)){
            return $this->com_return(false,config("params.PARAM_NOT_EMPTY"));
        }

        $pagesize    = $request->param("pagesize",config('PAGESIZE'));//显示个数,不传时为10

        if (empty($pagesize)){
            $pagesize = config('PAGESIZE');
        }


        $nowPage = $request->param("nowPage","1");

        $config = [
            "page" => $nowPage,
        ];

        $CashGiftModel = new UserAccountCashGift();

        $list = $CashGiftModel
            ->where('uid',$uid)
            ->paginate($pagesize,false,$config);

        return $this->com_return(true,config('params.SUCCESS'),$list);

    }

    /**
     * 会员积分明细
     * @param Request $request
     * @return array
     * @throws \think\exception\DbException
     */
    public function accountPointInfo(Request $request)
    {
        $uid = $request->param('uid','');
        if (empty($uid)){
            return $this->com_return(false,config("params.PARAM_NOT_EMPTY"));
        }

        $pagesize    = $request->param("pagesize",config('PAGESIZE'));//显示个数,不传时为10

        if (empty($pagesize)){
            $pagesize = config('PAGESIZE');
        }

        $nowPage = $request->param("nowPage","1");

        $config = [
            "page" => $nowPage,
        ];

        $accountPointModel = new UserAccountPoint();

        $list = $accountPointModel
            ->where('uid',$uid)
            ->paginate($pagesize,false,$config);

        return $this->com_return(true,config('params.SUCCESS'),$list);
    }

}