<?php
/**
 * 酒桌区域管理
 * User: qubojie
 * Date: 2018/7/24
 * Time: 下午12:04
 */
namespace app\admin\controller;

use app\admin\model\ManageSalesman;
use app\admin\model\MstCardVip;
use app\admin\model\MstTable;
use app\admin\model\MstTableArea;
use app\admin\model\MstTableAreaCard;
use think\Db;
use think\Exception;
use think\Request;
use think\Validate;

class TableArea extends CommandAction
{
    /**
     * 区域列表
     * @param Request $request
     * @return array
     * @throws \think\exception\DbException
     */
    public function index(Request $request)
    {
        $tableAreaModel = new MstTableArea();

        $location_id    = $request->param('location_id','');

        $pagesize       = $request->param("pagesize",config('PAGESIZE'));//显示个数,不传时为10

        if (empty($pagesize)) $pagesize = config('PAGESIZE');

        $nowPage = $request->param("nowPage","1");

        $config = [
            "page" => $nowPage,
        ];

        $location_where = [];
        if (!empty($location_id)){
            $location_where['location_id'] = ['eq',$location_id];
        }

        $list = $tableAreaModel
            ->where('is_delete',0)
            ->where($location_where)
            ->paginate($pagesize,false,$config);

        $list = json_decode(json_encode($list),true);

        $tableAreaCardModel = new MstTableAreaCard();

        $cardModel = new MstCardVip();
        $salesmanModel = new ManageSalesman();

        for ($i = 0; $i < count($list['data']); $i++){

            //区域负责人
            $sid = $list['data'][$i]['sid'];
            $area_manager_res = $salesmanModel
                ->where('sid',$sid)
                ->field("sales_name")
                ->find();
            $area_manager_res = json_decode(json_encode($area_manager_res),true);
            $area_manager = $area_manager_res['sales_name'];
            $list['data'][$i]['area_manager'] = $area_manager;

            //获取区域与卡绑定关系
            $area_id     = $list['data'][$i]['area_id'];

            $card_id_res = $tableAreaCardModel
                ->where("area_id",$area_id)
                ->select();

            $card_id_res = json_decode(json_encode($card_id_res),true);

            if (!empty($card_id_res)){
                for ($m = 0; $m < count($card_id_res); $m++){

                    //获取卡的名字
                    $card_id = $card_id_res[$m]['card_id'];
                    $card_name_res = $cardModel
                        ->where('card_id',$card_id)
                        ->field("card_name")
                        ->find();

                    $card_name_res = json_decode(json_encode($card_name_res),true);

                    $card_name     = $card_name_res['card_name'];
                    $list['data'][$i]['card_id'][$m]['card_id'] = $card_id;
                    $list['data'][$i]['card_id'][$m]['card_name'] = $card_name;
                }
            }else{
                $list['data'][$i]['card_id'] = [];
            }
        }

        return $this->com_return(true,config("params.SUCCESS"),$list);
    }

    /**
     * 获取区域服务负责人列表
     * @param Request $request
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getGovernorSalesman(Request $request)
    {
        $salesUserObj = new SalesUser();

        $res = $salesUserObj->getGovernorSalesman("1");

        $list = [];
        if (!empty($res)){

            for ($i = 0; $i < count($res); $i++){
                $list[$i]['key']  = $res[$i]['sid'];
                $list[$i]['name'] = $res[$i]['sales_name'];
            }
        }

        return $this->com_return(true,config("params.SUCCESS"),$list);
    }

    /**
     * 获取所有的有效卡种
     * @param Request $request
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getCardInfo(Request $request)
    {
        $cardModel = new MstCardVip();

        $cardInfo = $cardModel
            ->where('is_enable',1)
            ->where('is_delete',0)
            ->order('sort')
            ->field('card_id,card_name')
            ->select();
        $list = json_decode(json_encode($cardInfo),true);

        $cardInfo = [];

        foreach ($list as $key => $val){
            foreach ($val as $k => $v){

                if ($k == "card_id"){
                    $k = "key";
                }else{
                    $k = "name";
                }

                $cardInfo[$key][$k] = $v;
            }

        }

        return $this->com_return(true,config("params.SUCCESS"),$list);
    }

    /**
     * 区域添加
     * @param Request $request
     * @return array
     */
    public function add(Request $request)
    {
        $tableAreaModel = new MstTableArea();

        $location_id    = $request->param('location_id','');//位置id
        $area_title     = $request->param('area_title','');
        $area_desc      = $request->param('area_desc','');
//        $turnover_limit = $request->param('turnover_limit','');//最低消费 0表示无最低消费
        $sort           = $request->param('sort','100');//排序
        $is_enable      = $request->param('is_enable',0);//是否启用预定  0否 1是
        $card_ids       = $request->param("card_id","");//区域绑定卡

        $sid            = $request->param("sid","");//服务负责人id

        if (empty($sort)) $sort = 100;

        $rule = [
            "location_id|位置id"     => "require",
            "area_title|区域名称"     => "require|max:30",
            "area_desc|区域描述"      => "max:200",
//            "turnover_limit|最低消费" => "number",
            "sort|排序"              => "number",
            "sid|服务负责人id"        => "require",
        ];
        $check_data = [
            "location_id"    => $location_id,
            "area_title"     => $area_title,
            "area_desc"      => $area_desc,
//            "turnover_limit" => $turnover_limit,
            "sort"           => $sort,
            "sid"            => $sid,
        ];

        $validate = new Validate($rule);

        if (!$validate->check($check_data)){
            return $this->com_return(false,$validate->getError());
        }

        //查询当前位置下是否存在该区域

        $is_exist = $tableAreaModel
            ->where('location_id',$location_id)
            ->where("area_title",$area_title)
            ->count();

        if ($is_exist > 0){
            return $this->com_return(false,config("params.AREA_IS_EXIST"));
        }


        $time = time();

        $insert_data = [
            'location_id'     => $location_id,
            'area_title'      => $area_title,
            'area_desc'       => $area_desc,
//            'turnover_limit'  => $turnover_limit,
            'sort'            => $sort,
            'sid'             => $sid,
            'is_enable'       => $is_enable,
            'created_at'      => $time,
            'updated_at'      => $time
        ];

        Db::startTrans();
        try{

            $area_id = $tableAreaModel
                ->insertGetId($insert_data);

            if ($area_id){
                if (!empty($card_ids)){
                    $card_id_arr = explode(",",$card_ids);

                    $tableAreaCardModel = new MstTableAreaCard();

                    $is_ok = false;

                    for ($i = 0; $i < count($card_id_arr); $i++){
                        $card_id = $card_id_arr[$i];

                        $insert_table_card_data = [
                            "area_id" => $area_id,
                            "card_id" => $card_id
                        ];

                        $is_ok = $tableAreaCardModel
                            ->insert($insert_table_card_data);

                    }
                }else{
                    $is_ok = true;
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
     * 区域编辑
     * @param Request $request
     * @return array
     */
    public function edit(Request $request)
    {
        $tableAreaModel = new MstTableArea();


        $area_id        = $request->param('area_id','');
        $location_id    = $request->param('location_id','');//位置id
        $area_title     = $request->param('area_title','');
        $area_desc      = $request->param('area_desc','');
//        $turnover_limit = $request->param('turnover_limit','');//最低消费 0表示无最低消费
        $sort           = $request->param('sort','100');//排序
        $is_enable      = $request->param('is_enable',0);//是否启用预定  0否 1是
        $card_ids       = $request->param("card_id","");//区域绑定卡

        $sid            = $request->param("sid","");//服务负责人id

        if (empty($sort)) $sort = 100;

        $rule = [
            "area_id|区域id"         => "require",
            "location_id|位置id"     => "require",
            "area_title|区域名称"     => "require|max:30",
            "area_desc|区域描述"      => "max:200",
//            "turnover_limit|最低消费" => "number",
            "sort|排序"              => "number",
            "sid|服务负责人id"        => "require",
        ];
        $check_data = [
            "area_id"        => $area_id,
            "location_id"    => $location_id,
            "area_title"     => $area_title,
            "area_desc"      => $area_desc,
//            "turnover_limit" => $turnover_limit,
            "sort"           => $sort,
            "sid"            => $sid,
        ];

        $validate = new Validate($rule);

        if (!$validate->check($check_data)){
            return $this->com_return(false,$validate->getError());
        }

        $update_data = [
            "location_id"    => $location_id,
            'area_title'     => $area_title,
            'area_desc'      => $area_desc,
//            'turnover_limit' => $turnover_limit,
            'sort'           => $sort,
            'sid'            => $sid,
            'is_enable'      => $is_enable,
            'updated_at'     => time()
        ];

        Db::startTrans();
        try{

            $is_ok = $tableAreaModel
                ->where('area_id',$area_id)
                ->update($update_data);

            if ($is_ok !== false){

                $tableAreaCardModel = new MstTableAreaCard();

                //首先删除此区域绑定卡信息
                $delete_ok = $tableAreaCardModel
                    ->where('area_id',$area_id)
                    ->delete();

                if ($delete_ok !== false){

                    if (!empty($card_ids)){
                        $card_id_arr = explode(",",$card_ids);

                        $is_ok = false;

                        for ($i = 0; $i < count($card_id_arr); $i++){
                            $card_id = $card_id_arr[$i];

                            $insert_table_card_data = [
                                "area_id" => $area_id,
                                "card_id" => $card_id
                            ];

                            $is_ok = $tableAreaCardModel
                                ->insert($insert_table_card_data);
                        }
                    }else{
                        $is_ok = true;
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
     * 区域删除
     * @param Request $request
     * @return array
     */
    public function delete(Request $request)
    {
        $tableAreaModel = new MstTableArea();
        $area_id        = $request->param('area_id','');

        //首先查询当前区域下是否有吧台
        $tableModel = new MstTable();

        $is_exist = $tableModel
            ->where('area_id',$area_id)
            ->count();
        if ($is_exist > 0){
            return $this->com_return(false,config("params.EXIST_TALE"));
        }

        $delete_data = [
            'is_delete'  => 1,
            'updated_at' => time()
        ];

        $is_ok = $tableAreaModel
            ->where('area_id',$area_id)
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
        $tableAreaModel = new MstTableArea();

        $is_enable = (int)$request->param("is_enable","");
        $area_id   = $request->param("area_id","");

        if (empty($area_id)){
            return $this->com_return(false,config("params.PARAM_NOT_EMPTY"));
        }

        $update_data = [
            'is_enable'  => $is_enable,
            'updated_at' => time()
        ];

        $is_ok = $tableAreaModel
            ->where('area_id',$area_id)
            ->update($update_data);

        if ($is_ok !== false){
            return $this->com_return(true,config("params.SUCCESS"));
        }else{
            return $this->com_return(false,config("params.FAIL"));
        }
    }
}