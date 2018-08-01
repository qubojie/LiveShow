<?php
/**
 * 台位信息管理
 * User: qubojie
 * Date: 2018/7/24
 * Time: 下午12:04
 */
namespace app\admin\controller;

use app\admin\model\MstTable;
use app\admin\model\MstTableImage;
use app\admin\model\MstTableLocation;
use app\admin\model\TableRevenue;
use think\Db;
use think\Exception;
use think\Request;
use think\Validate;

class TableInfo extends CommandAction
{

    /**
     * 位置类型列表
     * @param Request $request
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function tableLocation(Request $request)
    {
        $tableLocationModel = new MstTableLocation();

        $res = $tableLocationModel
            ->where('is_delete',0)
            ->field("location_id,location_title")
            ->select();

        $res = json_decode(json_encode($res),true);

        $list = [];
        foreach ($res as $key => $val){

            foreach ($val as $k => $v){

                if ($k == "location_id"){
                    $k = "key";
                }else{
                    $k = "name";
                }

               $list[$key][$k] = $v;
            }

        }

        return $this->com_return(true,config("params.SUCCESS"),$list);
    }

    /**
     * 台位信息列表
     * @param Request $request
     * @return array
     * @throws \think\exception\DbException
     */
    public function index(Request $request)
    {
        $tableModel = new MstTable();

        $pagesize = $request->param("pagesize",config('PAGESIZE'));//显示个数,不传时为10

        if (empty($pagesize)) $pagesize = config('PAGESIZE');

        $nowPage     = $request->param("nowPage","1");
        $location_id = $request->param("location_id","");

        $location_where = [];
        if (!empty($location_id)){
            $location_where['ta.location_id'] = ['eq',$location_id];
        }

        $config = [
            "page" => $nowPage,
        ];

        $list = $tableModel
            ->alias("t")
            ->join("mst_table_area ta","ta.area_id = t.area_id")
            ->join("mst_table_appearance tap","tap.appearance_id = t.appearance_id")
            ->join("mst_table_size ts","ts.size_id = t.size_id")
            ->where('t.is_delete',0)
            ->where($location_where)
            ->group('t.area_id,t.table_id')
            ->paginate($pagesize,false,$config);
        $list = json_decode(json_encode($list),true);


        $tableImageModel = new MstTableImage();

        $tableRevenueModel = new TableRevenue();

        for ($i = 0; $i < count($list['data']); $i++){
            $list['data'][$i]['image_group'] = [];

            $table_id = $list['data'][$i]['table_id'];

            $pending_payment = config("order.table_reserve_status")['pending_payment']['key'];//待付定金或结算
            $reserve_success = config("order.table_reserve_status")['reserve_success']['key'];//预定成功
            $already_open    = config("order.table_reserve_status")['already_open']['key'];//已开台
            $clear_table     = config("order.table_reserve_status")['clear_table']['key'];//已清台
            $cancel          = config("order.table_reserve_status")['cancel']['key'];//取消预约

            $can_not_reserve = $pending_payment.",".$reserve_success.",".$already_open;

            $where_status['status'] = array('like',"%$can_not_reserve%");//查询字段的值在此范围之内的做显示

            //统计订单表中吧台当天是否已被预定
            $table_reserve_info = $tableRevenueModel
                ->where('table_id',$table_id)
                ->whereTime("reserve_time","today")
                ->where($where_status)
                ->count();
            //dump($table_reserve_info);die;
            if ($table_reserve_info > 0){
                //已被预定,
                $list['data'][$i]['table_status'] = 1;

            }else{
                //可预订
                $list['data'][$i]['table_status'] = 0;
            }


            $image_res = $tableImageModel
                ->where('table_id',$table_id)
                ->field('type,sort,image')
                ->select();
            $image_res = json_decode(json_encode($image_res),true);

            $image = "";

            for ($m = 0; $m < count($image_res); $m++){
                $image .= $image_res[$m]['image'].",";
            }
            //使用 rtrim() 函数从字符串右端删除字符 ,
            $image = rtrim($image,",");

            $list['data'][$i]['image_group'] = $image;

        }

        return $this->com_return(true,config("params.SUCCESS"),$list);

    }

    /**
     * 台位信息添加
     * @param Request $request
     * @return array
     */
    public function add(Request $request)
    {
        $tableModel = new MstTable();

        $table_no          = $request->param('table_no','');//台号
        $appearance_id     = $request->param("appearance_id",'');//品相id
        $size_id           = $request->param("size_id",'');//容量id
        $area_id           = $request->param('area_id','');//区域id
        $turnover_limit_l1 = $request->param('turnover_limit_l1',0);//平日最低消费 0表示无最低消费（保留）
        $turnover_limit_l2 = $request->param('turnover_limit_l2',0);//周末最低消费 0表示无最低消费（保留）
        $turnover_limit_l3 = $request->param('turnover_limit_l3',0);//假日最低消费 0表示无最低消费（保留）
        $subscription_l1   = $request->param('subscription_l1',0);//平日押金
        $subscription_l2   = $request->param('subscription_l2',0);//周末押金
        $subscription_l3   = $request->param('subscription_l3',0);//假日押金
//        $people_max     = $request->param("people_max",'');//最大预定上线人数
        $table_desc        = $request->param('table_desc','');//台位描述
        $sort              = $request->param('sort','');//台位描述
        $is_enable         = $request->param('is_enable',0);//排序

        $image_group       = $request->param('image_group',0);//图片组,以逗号隔开

        $rule = [
            "table_no|台号"                  => "require|max:20|unique:mst_table",
            "appearance_id|品相"             => "require",
            "size_id|容量"                   => "require",
            "area_id|区域"                   => "require",
//            "people_max|最大预定上线人数" => "require|number",
            "image_group|图片"               => "require",
            "turnover_limit_l1|平日最低消费"  => "require|number",
            "turnover_limit_l2|周末最低消费"  => "require|number",
            "turnover_limit_l3|假日最低消费"  => "require|number",
            "subscription_l1|平日押金"        => "require|number",
            "subscription_l2|周末押金"        => "require|number",
            "subscription_l3|假日押金"        => "require|number",
            "table_desc|台位描述"             => "max:200",
        ];

        $check_data = [
            "table_no"           => $table_no,
            "appearance_id"      => $appearance_id,
            "size_id"            => $size_id,
            "area_id"            => $area_id,
            "image_group"        => $image_group,
            "turnover_limit_l1"  => $turnover_limit_l1,
            "turnover_limit_l2"  => $turnover_limit_l2,
            "turnover_limit_l3"  => $turnover_limit_l3,
            "subscription_l1"    => $subscription_l1,
            "subscription_l2"    => $subscription_l2,
            "subscription_l3"    => $subscription_l3,
//            "people_max"     => $people_max,
            "table_desc"         => $table_desc,
        ];

        $validate = new Validate($rule);

        if (!$validate->check($check_data)){
            return $this->com_return(false,$validate->getError());
        }

        $time = time();

        $insert_data = [
            'table_no'          => $table_no,
            'appearance_id'     => $appearance_id,
            'size_id'           => $size_id,
            'area_id'           => $area_id,
            'turnover_limit_l1' => $turnover_limit_l1,
            'turnover_limit_l2' => $turnover_limit_l2,
            'turnover_limit_l3' => $turnover_limit_l3,
            'subscription_l1'   => $subscription_l1,
            'subscription_l2'   => $subscription_l2,
            'subscription_l3'   => $subscription_l3,
//            'people_max'      => $people_max,
            'table_desc'        => $table_desc,
            'sort'              => $sort,
            'is_enable'         => $is_enable,
            'created_at'        => $time,
            'updated_at'        => $time
        ];

        Db::startTrans();
        try{

            $table_id = $tableModel
                ->insertGetId($insert_data);
            if ($table_id){
                $image_group = explode(",",$image_group);

                $tableImageModel = new MstTableImage();

                $is_ok = false;

                for ($i = 0; $i < count($image_group); $i++){
                    $image_data = [
                        'table_id' => $table_id,
                        'sort'     => $i,
                        'image'    => $image_group[$i]
                    ];
                    $is_ok = $tableImageModel
                        ->insert($image_data);
                }
                if ($is_ok){
                    Db::commit();
                    return $this->com_return(true,config("params.SUCCESS"));
                }else{
                    return $this->com_return(false,config("params.FAIL"));
                }

            }else{
                return $this->com_return(false,config("params.FAIL"));
            }
        }catch (Exception $e){
            Db::rollback();
            return $this->com_return(false,$e->getMessage());
        }

    }

    /**
     * 台位信息编辑
     * @param Request $request
     * @return array
     */
    public function edit(Request $request)
    {
        $tableModel = new MstTable();


        $table_id          = $request->param('table_id','');//酒桌id

        $table_no          = $request->param('table_no','');//台号
        $appearance_id     = $request->param('appearance_id','');//品相id
        $size_id           = $request->param('size_id','');//容量id
        $area_id           = $request->param('area_id','');//区域id
        $turnover_limit_l1 = $request->param('turnover_limit_l1',0);//平日最低消费 0表示无最低消费（保留）
        $turnover_limit_l2 = $request->param('turnover_limit_l2',0);//周末最低消费 0表示无最低消费（保留）
        $turnover_limit_l3 = $request->param('turnover_limit_l3',0);//假日最低消费 0表示无最低消费（保留）
        $subscription_l1   = $request->param('subscription_l1',0);//平日押金
        $subscription_l2   = $request->param('subscription_l2',0);//周末押金
        $subscription_l3   = $request->param('subscription_l3',0);//假日押金
//        $people_max     = $request->param("people_max",'');//最大预定人数上限
        $table_desc        = $request->param('table_desc','');//台位描述
        $sort              = $request->param('sort','');//台位描述
        $is_enable         = $request->param('is_enable',0);//排序

        $image_group       = $request->param('image_group',"");//图片组,以逗号隔开

        $rule = [
            "table_id|酒桌id"               => "require",
            "table_no|台号"                 => "require|max:20|unique:mst_table",
            "appearance_id|品相"            => "require",
            "size_id|容量"                  => "require",
            "area_id|区域"                  => "require",
            "image_group|图片"              => "require",
            "turnover_limit_l1|平日最低消费" => "require|number",
            "turnover_limit_l2|周末最低消费" => "require|number",
            "turnover_limit_l3|假日最低消费" => "require|number",
            "subscription_l1|平日押金"      => "require|number",
            "subscription_l2|周末押金"      => "require|number",
            "subscription_l3|假日押金"      => "require|number",
//            "people_max|最大预定人数上限" => "require",
            "table_desc|台位描述"           => "max:200",
        ];

        $check_data = [
            "table_id"          => $table_id,
            "table_no"          => $table_no,
            "appearance_id"     => $appearance_id,
            "size_id"           => $size_id,
            "area_id"           => $area_id,
            "image_group"       => $image_group,
            "turnover_limit_l1" => $turnover_limit_l1,
            "turnover_limit_l2" => $turnover_limit_l2,
            "turnover_limit_l3" => $turnover_limit_l3,
            "subscription_l1"   => $subscription_l1,
            "subscription_l2"   => $subscription_l2,
            "subscription_l3"   => $subscription_l3,
//            "people_max" => $people_max,
            "table_desc"     => $table_desc,
        ];

        $validate = new Validate($rule);

        if (!$validate->check($check_data)){
            return $this->com_return(false,$validate->getError());
        }

        $time = time();

        $update_data = [
            'table_no'          => $table_no,
            "appearance_id"     => $appearance_id,
            "size_id"           => $size_id,
            'area_id'           => $area_id,
            'turnover_limit_l1' => $turnover_limit_l1,
            'turnover_limit_l2' => $turnover_limit_l2,
            'turnover_limit_l3' => $turnover_limit_l3,
            'subscription_l1'   => $subscription_l1,
            'subscription_l2'   => $subscription_l2,
            'subscription_l3'   => $subscription_l3,
//            "people_max"      => $people_max,
            'table_desc'        => $table_desc,
            'sort'              => $sort,
            'is_enable'         => $is_enable,
            'updated_at'        => $time
        ];

        Db::startTrans();
        try{
            $is_ok  = $tableModel
                ->where('table_id',$table_id)
                ->update($update_data);

            if ($is_ok !== false){
                //首先删除表中此吧台的图片
                $tableImageModel = new MstTableImage();

                $is_delete = $tableImageModel
                    ->where('table_id',$table_id)
                    ->delete();
                if ($is_delete !== false){

                    $image_group = explode(",",$image_group);
                    $is_ok = false;
                    for ($i = 0; $i < count($image_group); $i++){
                        $image_data = [
                            'table_id' => $table_id,
                            'sort' => $i,
                            'image' => $image_group[$i]
                        ];
                        $is_ok = $tableImageModel
                            ->insert($image_data);
                    }

                    if ($is_ok){
                        Db::commit();
                        return $this->com_return(true,config("params.SUCCESS"));
                    }else{
                        return $this->com_return(false,config("params.FAIL"));
                    }

                }else{
                    return $this->com_return(false,config("params.FAIL"));
                }
            }else{
                return $this->com_return(false,config("params.FAIL"));
            }

        }catch (Exception $e){
            Db::rollback();
            return $this->com_return(false,$e->getMessage());
        }
    }

    /**
     * 台位删除
     * @param Request $request
     * @return array
     */
    public function delete(Request $request)
    {
        $tableModel = new MstTable();

        $table_id  = $request->param('table_id','');//酒桌id

        $delete_data = [
            'is_delete'  => 1,
            'updated_at' => time()
        ];

        $is_ok = $tableModel
            ->where('table_id',$table_id)
            ->update($delete_data);

        if ($is_ok !== false){
            return $this->com_return(true,config("params.SUCCESS"));
        }else{
            return $this->com_return(false,config("params.FAIL"));
        }

    }

    /**
     * 是否启用
     * @param Request $request
     * @return array
     */
    public function enable(Request $request)
    {
        $tableModel = new MstTable();

        $is_enable = (int)$request->param("is_enable","");
        $table_id   = $request->param("table_id","");

        if (empty($table_id)){
            return $this->com_return(false,config("params.PARAM_NOT_EMPTY"));
        }

        $update_data = [
            'is_enable'  => $is_enable,
            'updated_at' => time()
        ];

        $is_ok = $tableModel
            ->where('table_id',$table_id)
            ->update($update_data);

        if ($is_ok !== false){
            return $this->com_return(true,config("params.SUCCESS"));
        }else{
            return $this->com_return(false,config("params.FAIL"));
        }
    }
}