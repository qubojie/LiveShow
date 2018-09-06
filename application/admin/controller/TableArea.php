<?php
/**
 * 酒桌区域管理
 * User: qubojie
 * Date: 2018/7/24
 * Time: 下午12:04
 */
namespace app\admin\controller;

use app\admin\model\MstCardVip;
use app\admin\model\MstTable;
use app\admin\model\MstTableArea;
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
            $location_where['ta.location_id'] = ['eq',$location_id];
        }

        $column = $tableAreaModel->column;

        foreach ($column as $k => $v){
            $column[$k] = "ta.".$v;
        }

        $list = $tableAreaModel
            ->alias('ta')
            ->join("mst_table_location tl","tl.location_id = ta.location_id")
            ->join("manage_salesman ms","ms.sid = ta.sid")
            ->where('ta.is_delete',0)
            ->where($location_where)
            ->field("tl.location_id,tl.location_title")
            ->field($column)
            ->field("ms.sid,ms.sales_name area_manager")
            ->paginate($pagesize,false,$config);

        $list = json_decode(json_encode($list),true);

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
        $sort           = $request->param('sort','100');//排序
        $is_enable      = $request->param('is_enable',0);//是否启用预定  0否 1是
        $sid            = $request->param("sid","");//服务负责人id

        if (empty($sort)) $sort = 100;

        $rule = [
            "location_id|位置id"     => "require",
            "area_title|区域名称"     => "require|max:30",
            "area_desc|区域描述"      => "max:200",
            "sort|排序"              => "number",
            "sid|服务负责人id"        => "require",
        ];
        $check_data = [
            "location_id"    => $location_id,
            "area_title"     => $area_title,
            "area_desc"      => $area_desc,
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
            'sort'            => $sort,
            'sid'             => $sid,
            'is_enable'       => $is_enable,
            'created_at'      => $time,
            'updated_at'      => $time
        ];

        $is_ok = $tableAreaModel
            ->insert($insert_data);

        if ($is_ok){
            return $this->com_return(true,config("params.SUCCESS"));

        }else{
            return $this->com_return(false,config("params.FAIL"));
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
        $sort           = $request->param('sort','100');//排序
        $is_enable      = $request->param('is_enable',0);//是否启用预定  0否 1是

        $sid            = $request->param("sid","");//服务负责人id

        if (empty($sort)) $sort = 100;

        $rule = [
            "area_id|区域id"         => "require",
            "location_id|位置id"     => "require",
            "area_title|区域名称"     => "require|max:30",
            "area_desc|区域描述"      => "max:200",
            "sort|排序"              => "number",
            "sid|服务负责人id"        => "require",
        ];
        $check_data = [
            "area_id"        => $area_id,
            "location_id"    => $location_id,
            "area_title"     => $area_title,
            "area_desc"      => $area_desc,
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
            'sort'           => $sort,
            'sid'            => $sid,
            'is_enable'      => $is_enable,
            'updated_at'     => time()
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
            ->where("is_delete",0)
            ->count();

        if ($is_exist > 0){
            return $this->com_return(false,config("params.TABLE")['TALE_EXIST']);
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