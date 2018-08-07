<?php
namespace app\reception\controller;

use app\admin\model\MstTable;
use app\admin\model\TableRevenue;
use app\admin\model\User;
use think\Db;
use think\Request;

class DiningRoom extends CommonAction
{
    /**
     * 获取今日订台信息
     * @param Request $request
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function todayTableInfo(Request $request)
    {
        $keyword       = $request->param("keyword","");//关键字搜索
        $location_id   = $request->param("location_id","");//位置id
        $area_id       = $request->param("area_id","");//区域id;
        $appearance_id = $request->param("appearance_id","");//品相id

        $location_where = [];
        if (!empty($location_id)){
            $location_where['ta.location_id'] = ['eq',$location_id];
        }

        $area_where = [];
        if (!empty($area_id)){
            $area_where['t.area_id'] = ['eq',$area_id];
        }

        $appearance_where = [];
        if (!empty($appearance_where)){
            $appearance_where['t.appearance_id'] = ['eq',$appearance_id];
        }

        $where = [];
        if (!empty($keyword)){
            $where["t.table_no|ta.area_title|tl.location_title|tap.appearance_title|u.phone"] = ["like","%$keyword%"];
        }

        $tableModel = new MstTable();
        $tableInfo = $tableModel
            ->alias("t")
            ->join("mst_table_area ta","ta.area_id = t.area_id")//区域
            ->join("mst_table_location tl","tl.location_id = ta.location_id")//位置
            ->join("mst_table_size ts","ts.size_id = t.size_id")//人数
            ->join("mst_table_appearance tap","tap.appearance_id = t.appearance_id")//品相
            ->join("table_revenue tr","tr.table_id = t.table_id","LEFT")//预约表
            ->join("user u","u.uid = tr.uid","LEFT")
            ->where('t.is_delete',0)
            ->where($where)
            ->where($location_where)
            ->where($area_where)
            ->where($appearance_where)
            ->group("t.table_id")
            ->order('t.sort')
//            ->field("t.table_id,t.table_no,t.turnover_limit_l1,t.turnover_limit_l2,t.turnover_limit_l3,t.subscription_l1,t.subscription_l2,t.subscription_l3,t.people_max,t.table_desc")
            ->field("t.table_id,t.table_no,t.people_max,t.table_desc,t.sort,t.is_enable")
            ->field("ta.area_id,ta.area_title,ta.area_desc,ta.sid")
            ->field("tl.location_title")
            ->field("ts.size_title")
            ->field("tap.appearance_title")
            ->select();

        $tableInfo = json_decode(json_encode($tableInfo),true);

        $tableRevenueModel = new TableRevenue();

        $status_str = "0,1,2";

        for ($i = 0; $i <count($tableInfo); $i ++){

            $table_id = $tableInfo[$i]['table_id'];

            $tableStatusRes = $tableRevenueModel
                ->where('table_id',$table_id)
                ->where('status',"IN",$status_str)
                ->whereTime("reserve_time","today")
                ->find();
            $tableStatusRes = json_decode(json_encode($tableStatusRes),true);

            if (!empty($tableStatusRes)){
                $reserve_time = $tableStatusRes['reserve_time'];
                $reserve_time = date("H:i",$reserve_time);


                $tableInfo[$i]['table_status'] = $tableStatusRes['status'];
                $tableInfo[$i]['is_join']      = $tableStatusRes['is_join'];
                $tableInfo[$i]['reserve_time'] = $reserve_time;
            }else{
                $tableInfo[$i]['table_status'] = 0;
                $tableInfo[$i]['is_join']      = 0;
                $tableInfo[$i]['reserve_time'] = 0;
            }

        }
        $res['filter']['keyword']       = $keyword;
        $res['filter']['location_id']   = $location_id;
        $res['filter']['area_id']       = $area_id;
        $res['filter']['appearance_id'] = $appearance_id;

        $res['data'] = $tableInfo;

        return $this->com_return(true,config("params.SUCCESS"),$res);
    }

    /**
     * 查看桌位详情
     * @param Request $request
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function tableInfo(Request $request)
    {
        $table_id = $request->param("table_id","");//桌台id

        if (empty($table_id)){
            return $this->com_return(false,config("params.PARAM_NOT_EMPTY"));
        }

        $tableModel = new MstTable();

        $table_column = [
            "t.table_no",
            "t.is_enable",
            "t.table_id",
            "t.area_id"
        ];

        $tableRevenueModel = new TableRevenue();

        $tableInfo = $tableModel
            ->alias("t")
            ->join("mst_table_area ta","ta.area_id = t.area_id")
            ->join("manage_salesman ms","ms.sid = ta.sid")
            ->where("table_id",$table_id)
            ->field("ms.sales_name,ms.phone sales_phone")
            ->field($table_column)
            ->find();

        $tableInfo = json_decode(json_encode($tableInfo),true);



        $area_id = $tableInfo['area_id'];

        $cardInfo = Db::name("mst_table_area_card")
            ->alias("tac")
            ->join("mst_card_vip cv","cv.card_id = tac.card_id")
            ->where('tac.area_id',$area_id)
            ->field("cv.card_name")
            ->select();
        $cardInfo = json_decode(json_encode($cardInfo),true);

        $tableInfo['card_vip'] = $cardInfo;

        $status_str = "0,1,2";

        $revenue_column = $tableRevenueModel->revenue_column;
        $revenueInfo = $tableRevenueModel
            ->alias("tr")
            ->join("user u","u.uid = tr.uid")
            ->join("user_card uc","uc.uid = tr.uid")
            ->join("mst_user_level ul","ul.level_id = u.level_id")
            ->where('tr.table_id',$table_id)
            ->where("tr.status","IN",$status_str)
            ->whereTime("tr.reserve_time","today")
            ->field("u.name,u.phone user_phone,u.nickname,u.level_id,u.credit_point")
            ->field("ul.level_name")
            ->field("uc.card_name,uc.card_type")
            ->field($revenue_column)
            ->select();

        $revenueInfo = json_decode(json_encode($revenueInfo),true);

        $tableInfo['revenue_info'] = $revenueInfo;

        return $this->com_return(true,config("params.SUCCESS"),$tableInfo);
    }
}