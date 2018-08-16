<?php
/**
 * 菜品信息.
 * User: qubojie
 * Date: 2018/8/14
 * Time: 下午3:57
 */
namespace app\admin\controller;

use app\admin\model\Dishes;
use app\common\controller\UUIDUntil;
use think\Controller;
use think\Request;
use think\Validate;

class Dish extends Controller
{
    /**
     * 菜品列表
     * @param Request $request
     * @return array
     * @throws \think\exception\DbException
     */
    public function index(Request $request)
    {
        $pagesize   = $request->param("pagesize",config('PAGESIZE'));//显示个数,不传时为10

        if (empty($pagesize)) $pagesize = config('PAGESIZE');

        $nowPage    = $request->param("nowPage","1");

        $keyword    = $request->param("keyword","");

        $cat_id     = $request->param("cat_id","");//菜品分类id

        $att_id     = $request->param("att_id","");//菜品属性id

        $config = [
            "page" => $nowPage,
        ];

        $where = [];
        if (!empty($keyword)){
            $where["d.dis_sn|d.dis_name"] = ["like","%$keyword%"];
        }

        $cat_where = [];
        if (!empty($cat_id)){
            $cat_where['d.cat_id'] = ['eq',$cat_id];
        }

        $att_where = [];
        if (!empty($att_where)){
            $att_where['d.att_id'] = ['eq',$att_id];
        }

        $dishModel = new Dishes();

        $column = $dishModel->column;

        foreach ($column as $key => $val){
            $column[$key] = "d.".$val;
        }

        $list = $dishModel
            ->alias("d")
            ->join("dishes_attribute da","da.att_id = d.att_id")
            ->join("dishes_category dc","dc.cat_id = d.cat_id")
            ->join("dishes_card_price dcp","dcp.dis_id = d.dis_id","LEFT")
            ->where("d.is_delete","0")
            ->where($where)
            ->order("d.sort")
            ->field($column)
            ->field("dcp.price vip_price")
            ->field("da.att_name")
            ->field("dc.cat_name")
            ->field("dc.cat_img")
            ->paginate($pagesize,false,$config);

        $list = json_decode(json_encode($list),true);

        $list = _unsetNull($list);

        return $this->com_return(true,config("params.SUCCESS"),$list);
    }

    /**
     * 菜品添加
     * @param Request $request
     * @return array
     */
    public function add(Request $request)
    {
        $dis_type     = $request->param("dis_type","");//菜品类型  0 单品    1 套餐
        $dis_name     = $request->param("dis_name","");//菜品名称
        $dis_img      = $request->param("dis_img","");//菜品图片
        $dis_desc     = $request->param("dis_desc","");//菜品规格属性描述
        $cat_id       = $request->param("cat_id","");//菜品分类id
        $att_id       = $request->param("att_id","");//菜品属性id
        $is_normal    = $request->param("is_normal","");//是否在普通区上架   0否  1是
        $normal_price = $request->param("normal_price","");//普通区单价
        $is_gift      = $request->param("is_gift","");//是否在礼金区上架   0否  1是
        $gift_price   = $request->param("gift_price","");//礼金区单价
        $is_vip       = $request->param("is_vip","");//是否在会员区上架
        $is_give       = $request->param("is_give","");//是否可赠送 0 否 1是
        $sort         = $request->param("sort","");//排序
        $is_enable    = $request->param("is_enable","");//是否启用  0否 1是


        $UUID = new UUIDUntil();
        $dis_sn = $UUID->generateReadableUUID("D");

        $rule = [
            "dis_type|菜品类型"         => "require",
            "dis_name|菜品名称"         => "require|max:100|unique_delete:dishes",
            "dis_img|菜品图片"          => "require",
            "dis_desc|菜品描述"         => "max:300",
            "cat_id|菜品分类id"         => "require",
            "att_id|菜品属性id"         => "require",
            "is_normal|是否在普通区上架" => "require",
            "normal_price|普通区单价"   => "egt:0",
            "is_gift|是否在礼金区上架"   => "require",
            "gift_price|礼金区单价"     => "egt:0",
            "is_vip|是否在会员区上架"    => "require",
            "is_give|是否可赠送"        => "require",
            "sort|排序"                => "number",
            "is_enable|是否启用"        => "require|number",
        ];

        $check_res = [
            "dis_type"     => $dis_type,
            "dis_name"     => $dis_name,
            "dis_img"      => $dis_img,
            "dis_desc"     => $dis_desc,
            "cat_id"       => $cat_id,
            "att_id"       => $att_id,
            "is_normal"    => $is_normal,
            "normal_price" => $normal_price,
            "is_gift"      => $is_gift,
            "gift_price"   => $gift_price,
            "is_vip"       => $is_vip,
            "is_give"      => $is_give,
            "sort"         => $sort,
            "is_enable"    => $is_enable,
        ];

        $validate = new Validate($rule);

        if (!$validate->check($check_res)){
            return $this->com_return(false,$validate->getError());
        }

        $nowTime = time();

        $params = [
            "dis_type"     => $dis_type,
            "dis_sn"       => $dis_sn,
            "dis_name"     => $dis_name,
            "dis_img"      => $dis_img,
            "dis_desc"     => $dis_desc,
            "cat_id"       => $cat_id,
            "att_id"       => $att_id,
            "is_normal"    => $is_normal,
            "normal_price" => $normal_price,
            "is_gift"      => $is_gift,
            "gift_price"   => $gift_price,
            "is_vip"       => $is_vip,
            "is_give"      => $is_give,
            "sort"         => $sort,
            "is_enable"    => $is_enable,
            "created_at"   => $nowTime,
            "updated_at"   => $nowTime,
        ];

        $dishModel = new Dishes();

        $is_ok = $dishModel
            ->insert($params);

        if ($is_ok){
            return $this->com_return(true,config("params.SUCCESS"));
        }else{
            return $this->com_return(false,config("params.FAIL"));
        }
    }


    /**
     * 菜品编辑
     * @param Request $request
     * @return array
     */
    public function edit(Request $request)
    {
        $dis_id       = $request->param("dis_id","");//菜品id
        $dis_type     = $request->param("dis_type","");//菜品类型  0 单品    1 套餐
        $dis_name     = $request->param("dis_name","");//菜品名称
        $dis_img      = $request->param("dis_img","");//菜品图片
        $dis_desc     = $request->param("dis_desc","");//菜品规格属性描述
        $cat_id       = $request->param("cat_id","");//菜品分类id
        $att_id       = $request->param("att_id","");//菜品属性id
        $is_normal    = $request->param("is_normal","");//是否在普通区上架   0否  1是
        $normal_price = $request->param("normal_price","");//普通区单价
        $is_gift      = $request->param("is_gift","");//是否在礼金区上架   0否  1是
        $gift_price   = $request->param("gift_price","");//礼金区单价
        $is_vip       = $request->param("is_vip","");//是否在会员区上架
        $is_give      = $request->param("is_give","");//是否可赠送 0否 1是
        $sort         = $request->param("sort","");//排序
        $is_enable    = $request->param("is_enable","");//是否启用  0否 1是

        $rule = [
            "dis_id|菜品id"            => "require",
            "dis_type|菜品类型"         => "require",
            "dis_name|菜品名称"         => "require|max:100|unique_me:dishes,dis_id",
            "dis_img|菜品图片"          => "require",
            "dis_desc|菜品描述"         => "max:300",
            "cat_id|菜品分类id"         => "require",
            "att_id|菜品属性id"         => "require",
            "is_normal|是否在普通区上架" => "require",
            "normal_price|普通区单价"   => "egt:0",
            "is_gift|是否在礼金区上架"   => "require",
            "gift_price|礼金区单价"     => "egt:0",
            "is_vip|是否在会员区上架"    => "require",
            "is_give|是否可赠送"        => "require",
            "sort|排序"                => "number",
            "is_enable|是否启用"        => "require|number",
        ];

        $check_res = [
            "dis_id"       => $dis_id,
            "dis_type"     => $dis_type,
            "dis_name"     => $dis_name,
            "dis_img"      => $dis_img,
            "dis_desc"     => $dis_desc,
            "cat_id"       => $cat_id,
            "att_id"       => $att_id,
            "is_normal"    => $is_normal,
            "normal_price" => $normal_price,
            "is_gift"      => $is_gift,
            "gift_price"   => $gift_price,
            "is_vip"       => $is_vip,
            "is_give"      => $is_give,
            "sort"         => $sort,
            "is_enable"    => $is_enable,
        ];

        $validate = new Validate($rule);

        if (!$validate->check($check_res)){
            return $this->com_return(false,$validate->getError());
        }

        $nowTime = time();

        $params = [
            "dis_type"     => $dis_type,
            "dis_name"     => $dis_name,
            "dis_img"      => $dis_img,
            "dis_desc"     => $dis_desc,
            "cat_id"       => $cat_id,
            "att_id"       => $att_id,
            "is_normal"    => $is_normal,
            "normal_price" => $normal_price,
            "is_gift"      => $is_gift,
            "gift_price"   => $gift_price,
            "is_vip"       => $is_vip,
            "is_give"      => $is_give,
            "sort"         => $sort,
            "is_enable"    => $is_enable,
            "updated_at"   => $nowTime,
        ];

        $dishModel = new Dishes();

        $is_ok = $dishModel
            ->where('dis_id',$dis_id)
            ->update($params);

        if ($is_ok !== false){
            return $this->com_return(true,config("params.SUCCESS"));
        }else{
            return $this->com_return(false,config("params.FAIL"));
        }
    }

    /**
     * 菜品删除
     * @param Request $request
     * @return array
     */
    public function delete(Request $request)
    {
        $dis_id = $request->param("dis_id","");//菜品id

        $rule = [
            "dis_id|菜品id"      => "require",
        ];

        $check_res = [
            "dis_id" => $dis_id,
        ];

        $validate = new Validate($rule);

        if (!$validate->check($check_res)){
            return $this->com_return(false,$validate->getError());
        }

        $dishModel = new Dishes();

        $params = [
            "is_delete"  => 1,
            "updated_at" => time()
        ];

        $is_ok = $dishModel
            ->where("dis_id",$dis_id)
            ->update($params);

        if ($is_ok !== false){
            return $this->com_return(true,config("params.SUCCESS"));
        }else{
            return $this->com_return(false,config("params.FAIL"));
        }
    }

    /**
     * 菜品排序
     * @param Request $request
     * @return array
     */
    public function sortEdit(Request $request)
    {
        $dis_id = $request->param("dis_id","");//菜品id
        $sort   = $request->param("sort","");//排序

        $rule = [
            "dis_id|菜品id"  => "require",
            "sort|排序"      => "require|number",
        ];

        $check_res = [
            "dis_id"  => $dis_id,
            "sort"    => $sort,
        ];

        $validate = new Validate($rule);

        if (!$validate->check($check_res)){
            return $this->com_return(false,$validate->getError());
        }

        $params = [
            "sort"       => $sort,
            "updated_at" => time()
        ];

        $dishModel = new Dishes();

        $is_ok = $dishModel
            ->where("dis_id",$dis_id)
            ->update($params);

        if ($is_ok !== false){
            return $this->com_return(true,config("params.SUCCESS"));
        }else{
            return $this->com_return(false,config("params.FAIL"));
        }

    }

    /**
     * 菜品是否启用
     * @param Request $request
     * @return array
     */
    public function enable(Request $request)
    {

        $dis_id    = $request->param("dis_id","");//菜品id
        $is_enable = $request->param("is_enable","");//是否启用

        $rule = [
            "dis_id|菜品id"     => "require",
            "is_enable|是否启用" => "require|number",
        ];

        $check_res = [
            "dis_id"    => $dis_id,
            "is_enable" => $is_enable,
        ];

        $validate = new Validate($rule);

        if (!$validate->check($check_res)){
            return $this->com_return(false,$validate->getError());
        }

        $params = [
            "is_enable"  => $is_enable,
            "updated_at" => time()
        ];

        $dishModel = new Dishes();

        $is_ok = $dishModel
            ->where("dis_id",$dis_id)
            ->update($params);

        if ($is_ok !== false){
            return $this->com_return(true,config("params.SUCCESS"));
        }else{
            return $this->com_return(false,config("params.FAIL"));
        }
    }
}