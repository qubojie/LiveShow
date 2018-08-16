<?php
/**
 * 开卡赠送礼品信息.
 * User: qubojie
 * Date: 2018/6/23
 * Time: 上午10:30
 */
namespace app\admin\controller;

use app\admin\model\MstGift;
use think\Db;
use think\Exception;
use think\Request;
use think\Validate;

class Gift extends CommandAction
{
    /**
     * 礼品列表
     * @param Request $request
     * @return array
     * @throws \think\exception\DbException
     */
    public function index(Request $request)
    {
        $common = new Common();

        $mstGiftModel = new MstGift();


        $pagesize = $request->param("pagesize",config('PAGESIZE'));//显示个数,不传时为10

        if (empty($pagesize)) $pagesize = config('PAGESIZE');

        $nowPage = $request->param("nowPage","1");

        $query = $mstGiftModel;

        $count = $query->count();//总记录数

        $pageNum = ceil($count/$pagesize); //总页数

        $config = [
            "page" => $nowPage,
        ];

        $gift_list = $query
            ->where('is_delete','0')
            ->paginate($pagesize,false,$config);

        return $common->com_return(true,config("GET_SUCCESS"),$gift_list);
    }

    /**
     * 礼品添加
     * @param Request $request
     * @return array
     */
    public function add(Request $request)
    {
        $common = new Common();

        $gift_img    = $request->param("gift_img","");      //礼品图片
        $gift_name   = $request->param("gift_name","");     //礼品名称标题
        $gift_desc   = $request->param("gift_desc","");     //礼品详细描述
        $gift_amount = $request->param("gift_amount","");//礼品金额
        $is_enable   = $request->param("is_enable","0");     //是否启用  0否 1是

        $rule = [
            "gift_img|礼品图片"      => "require",
            "gift_name|礼品名称标题"  => "require|max:30|unique_delete:mst_gift",
            "gift_amount|礼品金额"   => "require|number|max:11",
            "is_enable|是否启用"     => "require",
        ];

        $request_res = [
            "gift_img"    => $gift_img,
            "gift_name"   => $gift_name,
            "gift_amount" => $gift_amount,
            "is_enable"   => $is_enable,
        ];

        $validate = new Validate($rule);

        if (!$validate->check($request_res)){
            return $common->com_return(false,$validate->getError(),null);
        }

        $mstGiftModel = new MstGift();

        $time = time();

        $insert_data = [
            "gift_img"    => $gift_img,
            "gift_name"   => $gift_name,
            "gift_desc"   => $gift_desc,
            "gift_amount" => $gift_amount,
            "is_enable"   => $is_enable,
            "created_at"  => $time,
            "updated_at"  => $time
        ];

        Db::startTrans();
        try{
            $is_ok = $mstGiftModel
                ->insert($insert_data);

            if ($is_ok){
                Db::commit();
                return $common->com_return(true, config("ADD_SUCCESS"));
            }else{
                return $common->com_return(false, config("ADD_FAIL"));
            }
        }catch (Exception $e){
            Db::rollback();
            return $common->com_return(false,$e->getMessage(),null);
        }

    }

    /**
     * 礼品编辑
     * @param Request $request
     * @return array
     */
    public function edit(Request $request)
    {
        $common = new Common();

        $gift_id     = $request->param("gift_id","");      //礼品id
        $gift_img    = $request->param("gift_img","");      //礼品图片
        $gift_name   = $request->param("gift_name","");     //礼品名称标题
        $gift_desc   = $request->param("gift_desc","");     //礼品详细描述
        $gift_amount = $request->param("gift_amount","100");//礼品金额

        $rule = [
            "gift_id|礼品id"        => "require",
            "gift_img|礼品图片"      => "require",
            "gift_name|礼品名称"     => "require|max:30|unique_delete:mst_gift,gift_id",
            "gift_amount|礼品金额"   => "require|number|max:11",
        ];

        $request_res = [
            "gift_id"     => $gift_id,
            "gift_img"    => $gift_img,
            "gift_name"   => $gift_name,
            "gift_amount" => $gift_amount,
        ];

        $validate = new Validate($rule);

        if (!$validate->check($request_res)){
            return $common->com_return(false,$validate->getError(),null);
        }

        $mstGiftModel = new MstGift();

        $time = time();

        $update_data = [
            "gift_img"    => $gift_img,
            "gift_name"   => $gift_name,
            "gift_desc"   => $gift_desc,
            "gift_amount" => $gift_amount,
            "updated_at"  => $time
        ];

        try{
            $is_ok = $mstGiftModel
                ->where("gift_id",$gift_id)
                ->update($update_data);

            if ($is_ok !== false){
                Db::commit();
                return $common->com_return(true, config("EDIT_SUCCESS"));
            }else{
                return $common->com_return(false, config("EDIT_FAIL"));
            }
        }catch (Exception $e){
            Db::rollback();
            return $common->com_return(false,$e->getMessage(),null);
        }
    }

    /**
     * 礼品删除
     * @param Request $request
     * @return array
     */
    public function delete(Request $request)
    {
        $common = new Common();

        $gift_ids    = $request->param("gift_id","");      //礼品id

        if (!empty($gift_ids)){

            $id_array = explode(",",$gift_ids);

            $mstGiftModel = new MstGift();

            $time = time();

            $update_data = [
                "is_delete"  => '1',
                "updated_at" => $time
            ];

            Db::startTrans();
            try{
                $is_ok =false;
                foreach ($id_array as $gift_id){
                    $is_ok = $mstGiftModel
                        ->where("gift_id",$gift_id)
                        ->update($update_data);
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

        }else{
            return $common->com_return(false, config("PARAM_NOT_EMPTY"));
        }
    }

    /**
     * 礼品是否启用
     * @param Request $request
     * @return array
     */
    public function enable(Request $request)
    {
        $common = new Common();

        $is_enable = (int)$request->param("is_enable","");
        $gift_id   = $request->param("gift_id","");

        if ($is_enable == "1"){
            $success_message = "启用成功";
            $fail_message = "启用失败";
        }else{
            $success_message = "关闭成功";
            $fail_message = "关闭失败";
        }

        if (!empty($gift_id)){
            $update_data = [
                "is_enable"  => $is_enable,
                "updated_at" => time()
            ];
            $mstGiftModel = new MstGift();

            Db::startTrans();
            try{
                $is_ok = $mstGiftModel
                    ->where("gift_id",$gift_id)
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
}