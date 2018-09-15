<?php
/**
 *VIP会员卡信息设置.
 * User: qubojie
 * Date: 2018/6/26
 * Time: 下午5:17
 */
namespace app\admin\controller;

use app\admin\model\MstCardVip;
use app\admin\model\MstCardVipGiftRelation;
use app\admin\model\MstCardVipVoucherRelation;
use app\admin\model\MstGiftVoucher;
use think\Config;
use think\Db;
use think\Exception;
use think\Request;
use think\Validate;

class Card extends CommandAction
{
    /**
     * 获取卡类型
     * @return array
     */
    public function type()
    {
        $type = \config('card.type');
        return $this->com_return(true,\config('SUCCESS'),$type);
    }

    /**
     * 获取推荐人类型
     */
    public function getRecommendUserType()
    {
        $res = \config('salesman.salesman_type');
        return $this->com_return(true,\config('params.SUCCESS'),$res);
    }

    /**
     * 会籍卡列表
     * @param Request $request
     * @return array
     */
    public function index(Request $request)
    {
        $common = new Common();
        $cardVipModel = new MstCardVip();

        $pagesize   = $request->param("pagesize",config('PAGESIZE'));//显示个数,不传时为10
        $nowPage    = $request->param("nowPage","1");

        $orderBy     = $request->param("orderBy","sort");//根据什么排序
        $sort        = $request->param("sort","asc");//正序or倒叙

        $type       = $request->param('type','vip');//卡类型.默认为 vip卡

        if (empty($type)) $type = 'vip';

        $keyword = $request->param("keyword","");//关键字

        $where = [];

        if (!empty($keyword)) {
            $where['card_type|card_name|card_level|card_no_prefix|card_desc|card_equities'] = ["like","%$keyword%"];
        }

        if (empty($pagesize)){
            $pagesize = config('PAGESIZE');
        }

        if (empty($orderBy)){
            $orderBy = "sort";
        }

        if (empty($sort)){
            $sort = "asc";
        }

        $count = $cardVipModel->count();//总记录数

        $pageNum = ceil($count/$pagesize); //总页数

        $config = [
            "page" => $nowPage,
        ];

        $gift_list = $cardVipModel
            ->where('is_delete','0')
            ->where('card_type',$type)
            ->where($where)
            ->order($orderBy,$sort)
            ->paginate($pagesize,false,$config);

        $gift_list = json_decode(json_encode($gift_list),true);

        $gift_list['filter']['orderBy'] = $orderBy;
        $gift_list['filter']['sort']    = $sort;
        $gift_list['filter']['keyword'] = $keyword;

        $gift_list_data = $gift_list['data'];

        for ($i=0;$i<count($gift_list_data);$i++){
            $card_id = $gift_list_data[$i]['card_id'];

            $salesman = $gift_list_data[$i]['salesman'];

            $gift_list_data[$i]['salesman'] = explode(",",$salesman);

            $gift_list_data[$i]['gift_info'] = $this->gift_list($card_id);
            $gift_list_data[$i]['voucher_info'] = $this->voucher_list($card_id);
            $gift_list_data[$i]['card_cash_gift'] = (string)$gift_list_data[$i]['card_cash_gift'];
        }
        $gift_list['data'] = $gift_list_data;

        return $common->com_return(true,config("GET_SUCCESS"),$gift_list);
    }

    /**
     * 会籍卡添加
     * @param Request $request
     * @return array
     */
    public function add(Request $request)
    {
        $common = new Common();


        $gift_info      = $request->param("gift_info","");//赠送礼品id组,逗号隔开
        $voucher_info   = $request->param("voucher_info","");//礼券id组,逗号隔开

        $card_type      = $request->param("card_type","");//卡片类型 ‘vip’会籍卡 ‘value’ 储值卡 ‘year’ 年费卡
        $card_name      = $request->param("card_name","");//VIP卡名称
        $card_image     = $request->param("card_image","");//VIP卡背景图
        $card_no_prefix = $request->param("card_no_prefix","LV");//卡号前缀（两位数字）
        $card_desc      = $request->param("card_desc","");//VIP卡使用说明及其他描述
        $card_equities  = $request->param("card_equities","");//卡片享受权益详情

        $card_amount         = $request->param("card_amount","");//充值金额
//        $card_point          = $request->param("card_point","");//开卡赠送积分
        $card_cash_gift      = $request->param("card_cash_gift","");//开卡赠送礼金
        $card_job_cash_gif   = $request->param("card_job_cash_gif","");//推荐人返还礼金
        $card_job_commission = $request->param("card_job_commission","");//推荐人返还佣金
        $salesman            = $request->param('salesman',"");//销售人员类型,多个以逗号拼接

        $sort           = $request->param("sort","100");//排序

        $is_enable      = $request->param("is_enable","1");//是否启用  0否 1是

        $time = time();

        $rule = [
            'card_type|卡片类型'                =>  'require|max:10',  //卡片类型 ‘vip’会籍卡 ‘value’ 储值卡 ‘year’ 年费卡
            'card_name|VIP卡名称'               =>  'require|max:30|unique:mst_card_vip',  //VIP卡名称
            'card_image|VIP卡背景图'            =>  'require', //VIP卡背景图
            'card_no_prefix|卡号前缀'           =>  'require|max:2', //卡号前缀（两位数字）
            'card_desc|VIP卡使用说明及其他描述'   =>  'require|max:300', //VIP卡使用说明及其他描述
            'card_equities|卡片享受权益详情'     =>  'require|max:1000', //卡片享受权益详情

            'card_amount|充值金额'              =>  'require|number|max:11', //充值金额
            'card_cash_gift|开卡赠送礼金'        =>  'require|number|max:11', //开卡赠送礼金
            'card_job_cash_gif|推荐人返还礼金'   =>  'require|number|max:11', //推荐人返还礼金
            'card_job_commission|推荐人返还佣金' =>  'require|number|max:11', //推荐人返还佣金
//            'card_point|开卡赠送积分'            =>  'require', //开卡赠送积分
            'salesman|销售人员类型'              =>  'require', //销售人员类型

            'is_enable|是否启用'                =>  'require', //是否启用  0否 1是
        ];

        $check_data = [
            "card_type"      => $card_type,
            "card_name"      => $card_name,
            "card_image"     => $card_image,
            "card_no_prefix" => $card_no_prefix,
            "card_desc"      => $card_desc,
            "card_equities"  => $card_equities,

            "card_amount"         => $card_amount,
            "card_cash_gift"      => $card_cash_gift,
            "card_job_cash_gif"   => $card_job_cash_gif,
            "card_job_commission" => $card_job_commission,
//            "card_point"          => $card_point,
            "salesman"            => $salesman,

            "is_enable"      => $is_enable,
        ];

        $validate = new Validate($rule);

        if (!$validate->check($check_data)){
            return $common->com_return(false,$validate->getError());
        }

        $insert_data = [
            "card_type"           => $card_type,
            "card_name"           => $card_name,
            "card_image"          => $card_image,
            "card_no_prefix"      => $card_no_prefix,
            "card_desc"           => $card_desc,
            "card_equities"       => $card_equities,

            "card_amount"         => $card_amount,
//            "card_point"          => $card_point,
            "card_cash_gift"      => $card_cash_gift,
            "card_job_cash_gif"   => $card_job_cash_gif,
            "card_job_commission" => $card_job_commission,
            "salesman"            => $salesman,

            "sort"                => $sort,
            "is_enable"           => $is_enable,
            "created_at"          => $time,
            "updated_at"          => $time
        ];

        Db::startTrans();

        try{

            //写入卡表
            $card_id = $this->insert_card_vip($insert_data);

            if ($card_id){
                $gift_res = true;
                if (!empty($gift_info)){
                    //写入VIP开卡赠送礼品关系表
                    $gift_id_arr = explode(",",$gift_info);
                    for ($i = 0; $i < count($gift_id_arr); $i++){
                        $gift_id = $gift_id_arr[$i];
                        $gift_res = $this->insert_card_vip_gift_relation($card_id,$gift_id);
                    }
                }
                $voucher_res = true;
                if (!empty($voucher_info)){
                    //写入VIP卡赠送消费券关系表
                    $gift_vou_id_arr = explode(",",$voucher_info);
                    for ($m = 0; $m < count($gift_vou_id_arr); $m++){
                        $gift_vou_id = $gift_vou_id_arr[$m];
                        $voucher_res = $this->insert_card_vip_voucher_relation($card_id,$gift_vou_id);
                    }
                }
                if ($gift_res && $voucher_res){

                    //获取当前登录管理员
                    $action_user = $this->getLoginAdminId($request->header('Authorization'))['user_name'];

                    //添加至系统操作日志
                    $this->addSysLog("$time","$action_user","添加卡 -> $card_id ($card_name)",$request->ip());

                    Db::commit();
                    return $common->com_return(true,config("ADD_SUCCESS"));
                }else{
                    return $common->com_return(false,config("FAIL"));
                }
            }else{
                return $common->com_return(false,config("FAIL"));
            }
        }catch (Exception $e){
            Db::rollback();
            return $common->com_return(false,$e->getMessage());
        }
    }

    /**
     * 会籍卡编辑
     * @param Request $request
     * @return array
     */
    public function edit(Request $request)
    {
        $common = new Common();
        $cardVipModel = new MstCardVip();

        $card_id        = $request->param("card_id","");//卡id
        $gift_info      = $request->param("gift_info","");//礼品id组,以逗号隔开
        $voucher_info   = $request->param("voucher_info","");//礼券id组,以逗号隔开
//        $card_type      = $request->param("card_type","");//卡片类型必须 ‘vip’会籍卡      ‘value’ 储值卡
        $card_name      = $request->param("card_name","");//VIP卡名称必须
        $card_image     = $request->param("card_image","");//VIP卡背景图必须
        $card_no_prefix = $request->param("card_no_prefix","LV");//卡号前缀必须
        $card_desc      = $request->param("card_desc","");//VIP卡使用说明及其他描述必须
        $card_equities  = $request->param("card_equities","");//卡片享受权益详情必须

        $card_amount         = $request->param("card_amount","");//充值金额必须
//        $card_point          = $request->param("card_point","");//开卡赠送积分
        $card_cash_gift      = $request->param("card_cash_gift","");//开卡赠送礼金
        $card_job_cash_gif   = $request->param("card_job_cash_gif","");//推荐人返还礼金
        $card_job_commission = $request->param("card_job_commission","");//推荐人返还佣金
        $salesman            = $request->param('salesman',"");//销售人员类型,多个以逗号拼接

        $sort           = $request->param("sort","");//排序
        $is_enable      = $request->param("is_enable",0);//是否激活

        $rule = [
            "card_id|卡id"                    => "require",
//            "card_type|卡片类型"               => "require|max:10",
            "card_name|VIP卡名称"              => "require|max:30|unique:mst_card_vip",
            "card_image|VIP卡背景图"           => "require",
            "card_no_prefix|卡号前缀"          => "require|max:2",
            "card_desc|VIP卡使用说明及其他描述"  => "require|max:300",
            "card_equities|卡片享受权益详情"    => "require|max:1000",

            "card_amount|充值金额"             => "require|number",
            "card_cash_gift|开卡赠送礼金"       => "require|number",
            'card_job_commission|推荐人返还佣金' =>  'require|number|max:11', //推荐人返还佣金
//            'card_point|开卡赠送积分'            =>  'require', //开卡赠送积分
            'salesman|销售人员类型'              =>  'require', //销售人员类型

            "is_enable|是否激活"       => "require",
        ];

        $check_params = [
            "card_id"       => $card_id,
//            "card_type"     => $card_type,
            "card_name"     => $card_name,
            "card_image"    => $card_image,
            "card_no_prefix"=> $card_no_prefix,
            "card_desc"     => $card_desc,
            "card_equities" => $card_equities,

            "card_amount"         => $card_amount,
//            "card_point"          => $card_point,
            "card_cash_gift"      => $card_cash_gift,
            "card_job_cash_gif"   => $card_job_cash_gif,
            "card_job_commission" => $card_job_commission,
            "salesman"            => $salesman,

            "is_enable"=> $is_enable,
        ];

        $validate = new Validate($rule);

        if (!$validate->check($check_params)){
            return $common->com_return(false,$validate->getError(),null);
        }

        $data = [
//            "card_type"      => $card_type,
            "card_name"      => $card_name,
            "card_image"     => $card_image,
            "card_no_prefix" => $card_no_prefix,
            "card_desc"      => $card_desc,
            "card_equities"  => $card_equities,

            "card_amount"         => $card_amount,
//            "card_point"          => $card_point,
            "card_cash_gift"      => $card_cash_gift,
            "card_job_cash_gif"   => $card_job_cash_gif,
            "card_job_commission" => $card_job_commission,
            "salesman"            => $salesman,

            "sort"           => $sort,
            "is_enable"      => $is_enable,
            "updated_at"     => time()
        ];

        Db::startTrans();
        try{
            $res = $cardVipModel
                ->validate('MstCardVip.edit')
                ->where('card_id',$card_id)
                ->update($data);

            $gift_res = true;

            //删除礼品关系表中关于此卡的信息
            $cardVipGiftRelationModel = new MstCardVipGiftRelation();
            $cardVipGiftRelationModel->where('card_id',$card_id)->delete();

            if (!empty($gift_info)){
                //如果勾选的有礼品
                $gift_id_arr = explode(",",$gift_info);

                for ($i = 0; $i < count($gift_id_arr); $i++){
                    $gift_id = $gift_id_arr[$i];
                    //更新礼品与卡关系表
                    $gift_res = $this->insert_card_vip_gift_relation($card_id,$gift_id);
                }

            }
            $voucher_res = true;

            //删除礼券关系表中关于此卡的信息
            $cardVipVoucherRelationModel = new MstCardVipVoucherRelation();
            $cardVipVoucherRelationModel->where('card_id',$card_id)->delete();

            if (!empty($voucher_info)){
                //如果勾选的有礼券
                $gift_vou_id_arr = explode(",",$voucher_info);

                for ($m = 0; $m < count($gift_vou_id_arr); $m++){
                    $gift_vou_id = $gift_vou_id_arr[$m];
                    //更新礼券与卡关系表
                    $voucher_res = $this->insert_card_vip_voucher_relation($card_id,$gift_vou_id);
                }
            }

            if ($res && $gift_res && $voucher_res){
                Db::commit();
                return $common->com_return(true,config("EDIT_SUCCESS"));

            }else{
                return $common->com_return(false,config("EDIT_FAIL"));
            }

        }catch (Exception $e){
            Db::rollback();
            return $common->com_return(false,$e->getMessage(),null);
        }
    }

    /**
     * 会籍卡删除
     * @param Request $request
     * @return array
     */
    public function delete(Request $request)
    {
        $common = new Common();


        $card_ids = $request->param("card_id","");

        if (empty($card_ids)){
            return $common->com_return(true,config("PARAM_NOT_EMPTY"));
        }

        $id_array = explode(",",$card_ids);

        Db::startTrans();
        try{
            $is_ok =false;
            $time = time();
            foreach ($id_array as $card_id){

                $is_ok = $this->delete_card($card_id);

                //获取当前登录管理员
                $action_user = $this->getLoginAdminId($request->header('Authorization'))['user_name'];

                //添加至系统操作日志
                $this->addSysLog("$time","$action_user","删除卡 -> $card_id",$request->ip());

            }
            if ($is_ok !== false){
                Db::commit();

                return $common->com_return(true, config("DELETE_SUCCESS"));
            }else{
                return $common->com_return(false, config("DELETE_FAIL"));
            }

        }catch (Exception $e){
            Db::rollback();
            return $common->com_return(false,$e->getMessage(),null);
        }

    }

    /**
     * 会籍卡是否启用
     * @param Request $request
     * @return array
     */
    public function enable(Request $request)
    {
        $cardVipModel = new MstCardVip();

        $card_id   = $request->param("card_id","");//卡id
        $is_enable = (int)$request->param("is_enable","");//是否启用

        if (empty($card_id)) return $this->com_return(false,\config("PARAM_NOT_EMPTY"));

        if ($is_enable == 1){
            $action_des = "启用卡";
        }else{
            $action_des = "禁用卡";
        }

        $time = time();

        $params = [
            'is_enable' => $is_enable,
            'updated_at' => $time
        ];

        $is_ok = $cardVipModel
            ->where('card_id',$card_id)
            ->update($params);

        if ($is_ok !== false){

            //获取当前登录管理员
            $action_user = $this->getLoginAdminId($request->header('Authorization'))['user_name'];

            //添加至系统操作日志
            $this->addSysLog("$time","$action_user","$action_des -> $card_id",$request->ip());


            return $this->com_return(true,\config('SUCCESS'));
        }else{
            return $this->com_return(true,\config('FAIL'));
        }
    }

    /**
     * 卡排序
     * @param Request $request
     * @return array
     */
    public function sortEdit(Request $request)
    {
        $cardModel = new MstCardVip();

        $card_id = $request->param('card_id','');
        $sort    = $request->param('sort','');

        if (empty($card_id)){
            return $this->com_return(false,\config('params.PARAM_NOT_EMPTY'));
        }

        if (empty($sort)){
            $sort = 0;
        }

        $params = [
            'sort'       => $sort,
            'updated_at' => time()
        ];

        $is_ok = $cardModel
            ->where('card_id',$card_id)
            ->update($params);

        if ($is_ok !== false){

            return $this->com_return(true,\config('params.SUCCESS'));

        }else{

            return $this->com_return(false,\config('params.FAIL'));

        }
    }




    /*
     * 写入 mst_card_vip
     * 写入 vip卡表中
     * */
    public function insert_card_vip($params)
    {

        $cardVipModel = new MstCardVip();

        $card_id = $cardVipModel

            ->insertGetId($params);

        return $card_id;
    }


    /*
     * 关联mst_card_vip_gift_relation表
     *
     *@params $card_id:VIP卡id
     *@params $gift_id:关联的礼品id
     *@params $qty:赠送数量
     *
     * */
    public function insert_card_vip_gift_relation($card_id,$gift_id)
    {
        $cardVipGiftRelationModel = new MstCardVipGiftRelation();

        $data = [
            "card_id" => $card_id,
            "gift_id" => $gift_id,
        ];

        //去表里查询是否存在,如果存在更新,如果不存在则新建
        $is_exist = $cardVipGiftRelationModel
            ->where('card_id',$card_id)
            ->where('gift_id',$gift_id)
            ->count();

        if ($is_exist){
            $is_ok = $cardVipGiftRelationModel
                ->update($data);
        }else{
            $is_ok = $cardVipGiftRelationModel
                ->insert($data);
        }

        if ($is_ok){
            return true;
        }else{
            return false;
        }
    }


    /**
     * 关联mst_card_vip_voucher_relation表
     *
     * VIP卡赠送消费券关系表
     *
     * @params $card_id VIP卡id
     * @params $gift_vou_id 关联的礼品券id
     * @params $gift_vou_type 赠券类型  ‘once’单次    ‘multiple’多次   ‘limitless’ 无限制
     * @params $qty 赠送数量
     * @params $use_qty 使用数量
     *
     */
    public function insert_card_vip_voucher_relation($card_id,$gift_vou_id)
    {
        $cardVipVoucherRelationModel = new MstCardVipVoucherRelation();

        $voucherModel = new MstGiftVoucher();

        $gift_vou_type_res = $voucherModel
            ->where('gift_vou_id',$gift_vou_id)
            ->field('gift_vou_type')
            ->find();
        $gift_vou_type = $gift_vou_type_res['gift_vou_type'];

        $data = [
            "card_id"       => $card_id,
            "gift_vou_id"   => $gift_vou_id,
            "gift_vou_type" => $gift_vou_type,
        ];

        //去表里查询是否存在,如果存在更新,如果不存在则新建
        $is_exist = $cardVipVoucherRelationModel
            ->where('card_id',$card_id)
            ->where('gift_vou_id',$gift_vou_id)
            ->count();

        if ($is_exist){
            $is_ok = $cardVipVoucherRelationModel
                ->update($data);
        }else{
            $is_ok = $cardVipVoucherRelationModel
                ->insert($data);
        }
        if ($is_ok){
            return true;
        }else{
            return false;
        }

    }


    /*
     *礼品与卡关联信息列表
     * */
    public function gift_list($card_id)
    {
        $res = Db::name('mst_card_vip_gift_relation')
            ->alias('gr')
            ->join('mst_gift mg','mg.gift_id = gr.gift_id')
            ->where('gr.card_id',$card_id)
            ->select();

        return $res;

    }

    /*
     * 礼券与卡关联信息列表
     * */
    public function voucher_list($card_id)
    {
        $res = Db::name('mst_card_vip_voucher_relation')
            ->alias('vr')
            ->join('mst_gift_voucher mgv','mgv.gift_vou_id = vr.gift_vou_id')
            ->where('vr.card_id',$card_id)
            ->select();

        return $res;
    }

    /*
     * 删除卡
     * */
    public function delete_card($card_id)
    {
        $cardVipModel = new MstCardVip();

        $time = time();

        $params['updated_at'] = $time;
        $params['is_delete']  = 1;

        $is_ok = $cardVipModel
            ->where("card_id",$card_id)
            ->update($params);

        return $is_ok;
    }


    /**
     * 给卡新增赠品or礼券
     * @param Request $request
     * @param string $type 'gift 或者 voucher'
     * @return array
     */
    public function addGiftOrVoucher(Request $request)
    {
        $type          = $request->param('type','');
        $card_id       = $request->param('card_id','');
        $gift_id       = $request->param('gift_id','');
        $qty           = $request->param('qty','');
        $gift_vou_id   = $request->param('gift_vou_id','');
        $gift_vou_type = $request->param('gift_vou_type','');

        if (empty($type) || empty($card_id)) return $this->com_return(false,config("PARAM_NOT_EMPTY"));
        if (empty($qty)) return $this->com_return(false,'数量不能为空');

        if ($type == "gift"){

            if (empty($gift_id)) return $this->com_return(false,'礼品不能为空');

            $params = [
                'card_id' => $card_id,
                'gift_id' => $gift_id,
                'qty'     => $qty
            ];

            $tableModel = new MstCardVipGiftRelation();

            $is_exist = $tableModel
                ->where('card_id',$card_id)
                ->where('gift_id',$gift_id)
                ->count();


        }else{
            if (empty($gift_vou_id)) return $this->com_return(false,'礼券不能为空');
            if (empty($gift_vou_type)) return $this->com_return(false,'礼券类型不能为空');


            $params = [
                'card_id'       => $card_id,
                'gift_vou_id'   => $gift_vou_id,
                'gift_vou_type' => $gift_vou_type,
                'qty'           => $qty
            ];

            $tableModel = new MstCardVipVoucherRelation();

            $is_exist = $tableModel
                ->where('card_id',$card_id)
                ->where('gift_vou_id',$gift_vou_id)
                ->where('gift_vou_type',$gift_vou_type)
                ->count();
        }

        if ($is_exist){
            return $this->com_return(false,'已存在相关礼品');
        }


        $res = $tableModel
            ->insert($params);

        if ($res){
            return $this->com_return(true,config("SUCCESS"));
        }else{
            return $this->com_return(true,config("FAIL"));
        }
    }


    /**
     * 删除卡内关联赠品or礼券
     * @param Request $request
     * @return array
     */
    public function deleteGiftOrVoucher(Request $request)
    {
        $type          = $request->param('type','');
        $card_id       = $request->param('card_id','');
        $gift_id       = $request->param('gift_id','');
        $gift_vou_id   = $request->param('gift_vou_id','');
        $gift_vou_type = $request->param('gift_vou_type','');

        if (empty($type) || empty($card_id)) return $this->com_return(false,config("PARAM_NOT_EMPTY"));

        if ($type == "gift"){
            if (empty($gift_id)) return $this->com_return(false,'礼品不能为空');
            $tableModel = new MstCardVipGiftRelation();

            $is_ok = $tableModel
                ->where('card_id',$card_id)
                ->where('gift_id',$gift_id)
                ->delete();

        }else{

            if (empty($gift_vou_id)) return $this->com_return(false,'礼券不能为空');
            if (empty($gift_vou_type)) return $this->com_return(false,'礼券类型不能为空');
            $tableModel = new MstCardVipVoucherRelation();

            $is_ok = $tableModel
                ->where('card_id',$card_id)
                ->where('gift_vou_id',$gift_vou_id)
                ->where('gift_vou_type',$gift_vou_type)
                ->delete();
        }

        if ($is_ok){
            return $this->com_return(true,config("SUCCESS"));

        }else{
            return $this->com_return(true,config("FAIL"));

        }
    }
}