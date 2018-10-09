<?php
/**
 * 素材管理.
 * User: qubojie
 * Date: 2018/10/9
 * Time: 下午1:26
 */
namespace app\admin\controller;

use app\admin\model\ResourceFile;
use think\Controller;
use think\Db;
use think\Exception;
use think\Request;
use think\Validate;

class SourceMaterial extends Controller
{
    /**
     * 素材列表
     * @param Request $request
     * @return array
     * @throws \think\exception\DbException
     */
    public function index(Request $request)
    {
        $type     = $request->param('type', '');//类型 0 图片 ;  1 视频
        $cat_id   = $request->param('cat_id', '');//分类id

        $pagesize = $request->param("pagesize",config('PAGESIZE'));//显示个数,不传时为10
        if (empty($pagesize)) $pagesize = config('PAGESIZE');
        $nowPage    = $request->param("nowPage","1");

        $rule = [
            "type|类型"     => "require",
            "cat_id|分类id" => "require",
        ];
        $check_data = [
            "type"    => $type,
            "cat_id"  => $cat_id,
        ];
        $validate = new Validate($rule);
        if (!$validate->check($check_data)){
            return $this->com_return(false,$validate->getError());
        }

        $cat_where['cat_id'] = ['eq',$cat_id];

        $where['type'] = $type;

        $resourceFileModel = new ResourceFile();

        $config = [
            "page" => $nowPage,
        ];

        $res = $resourceFileModel
            ->where($where)
            ->where($cat_where)
            ->order('sort')
            ->paginate($pagesize,false,$config);

        return $this->com_return(true,config("params.SUCCESS"),$res);
    }

    /**
     * 素材上传
     * @param Request $request
     * @return array
     * @throws \Exception
     */
    public function upload(Request $request)
    {
        $type   = $request->param('type', '');
        $cat_id = $request->param('cat_id', '');
        $sort   = $request->param('sort', '500');
        $prefix = 'source';
        $genre  = 'file';//上传的文件容器参数名称

        if (empty($sort))   $sort   = '500';

        //验证
        $rule = [
            "type|素材类型"    => "require",
            "cat_id|分类id"   => "number",
            "sort|排序"       => "number",
            "prefix|前缀"     => "require",
        ];
        $check_data = [
            "type"   => $type,
            "cat_id" => $cat_id,
            "sort"   => $sort,
            "prefix" => $prefix,
        ];

        $validate = new Validate($rule);
        if (!$validate->check($check_data)){
            return $this->com_return(false,$validate->getError());
        }

        //todo 允许的类型没有加全
        $qiNiuUpload = new \app\common\controller\QiNiuUpload();
        $upload = $qiNiuUpload->upload("$genre","$prefix",$type);

        if (isset($upload['result']) && !$upload['result']){
            return $this->com_return(false, $upload['message']);
        }

        $link           = 'http://' . $upload['data']['url'] . '/' . $upload['data']['key'];
        $file_size      = $upload['data']['size'];
        $file_extension = $upload['data']['extension'];

        $params = [
            'cat_id'         => $cat_id,
            'type'           => $type,
            'link'           => $link,
            'file_size'      => $file_size,
            'file_extension' => $file_extension,
            'sort'           => $sort,
            'created_at'     => time(),
            'updated_at'     => time(),
        ];

        $resourceFileModel = new ResourceFile();

        $result = $resourceFileModel
            ->insert($params);

        if ($result !== false){
            return $this->com_return(true, config("params.SUCCESS"));
        }else{
            return $this->com_return(false, config("params.FAIL"));
        }
    }

    /**
     * 素材删除
     * @param Request $request
     * @return array
     */
    public function delete(Request $request)
    {
        $ids = $request->param('id', '');

        //验证
        $rule = [
            "id|素材id" => "require",
        ];
        $check_data = [
            "id" => $ids,
        ];

        $validate = new Validate($rule);
        if (!$validate->check($check_data)){
            return $this->com_return(false,$validate->getError());
        }

        $resourceFileModel = new ResourceFile();

        $ids = explode(",",$ids);

        Db::startTrans();
        try{
            foreach ($ids as $id){
                $res = $resourceFileModel
                    ->where('id', $id)
                    ->delete();

                if ($res == false){
                    return $this->com_return(false,config("params.FAIL"));
                }
            }

            Db::commit();
            return $this->com_return(true,config("params.SUCCESS"));

        }catch (Exception $e){
            Db::rollback();
            return $this->com_return(false,$e->getMessage());
        }
    }

    /**
     * 移动素材至新的分组
     * @param Request $request
     * @return array
     */
    public function moveMaterial(Request $request)
    {
        $type   = $request->param("type","");//素材类型
        $ids    = $request->param("id","");//素材id 多个以逗号隔开
        $cat_id = $request->param("cat_id","");//移动至的新分类id

        $rule = [
            "type|素材类型"   => "require",
            "id|素材id"      => "require",
            "cat_id|分类id"  => "require",
        ];
        $check_data = [
            "type"    => $type,
            "id"      => $ids,
            "cat_id"  => $cat_id,
        ];
        $validate = new Validate($rule);
        if (!$validate->check($check_data)){
            return $this->com_return(false,$validate->getError());
        }

        $ids = explode(",",$ids);//将素材id 以逗号分割为数组

        $where['type'] = $type;

        $resourceFile  = new ResourceFile();

        Db::startTrans();
        try{
            foreach ($ids as $id) {
                $where['id'] = $id;
                $params = [
                    "cat_id"     => $cat_id,
                    "type"       => $type,
                    "updated_at" => time()
                ];
                $move_res = $resourceFile
                    ->where($where)
                    ->update($params);
                if ($move_res == false){
                    return $this->com_return(false,config("params.TEMP")['MOVE_FAIL']);
                }
            }
            Db::commit();
            return $this->com_return(true,config("params.SUCCESS"));
        }catch (Exception $e){
            Db::rollback();
            return $this->com_return(false,$e->getMessage());
        }

    }
}