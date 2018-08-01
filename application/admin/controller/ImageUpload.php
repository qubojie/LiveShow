<?php
/**
 * Created by PhpStorm.
 * User: qubojie
 * Date: 2018/7/7
 * Time: 下午2:55
 */
namespace app\admin\controller;

use think\Controller;
use think\Env;
use think\Request;

class ImageUpload extends Controller
{
    /**
     * 图片上传至本地服务器
     * @param Request $request
     * @return array
     */
    public function uploadLocal(Request $request)
    {
        $type = $request->param('type','1');

        $file_path = "";
        if ($type == 1){
            //身份证上传
            $file_path = 'upload/user_card/';

        }elseif ($type == 2){
            $file_path = 'upload/other/';
        }


        $file = \request()->file("image");

        if (empty($file)){
            return $this->com_return(false,"请选择上传的图片");
        }

        //处理单图上传
        $res = $this->upload($file_path);

        return $res;

    }

    /**
     * 单图上传
     * @param $save_path
     * @return array
     */
    protected  function upload($save_path)
    {
        $ret = true;
        if (!file_exists($save_path)){
            $ret = @mkdir($save_path,0777,true);
        }
        if (!$ret){
            return $this->com_return(false,'创建保存图片的路径失败');
        }

        $file = \request()->file('image');

        $info = $file->validate(['size' => 2048000,'ext' => 'jpg,png,jpeg,gif'])->move($save_path);

        if ($info){
            $image_info = $info->getFilename();
            $image_src[]["pic_src"] = $save_path.date('Ymd',time()).'/'.$image_info;
            return $this->com_return(true,'上传成功',$image_src);
        }else{
            return $this->com_return(false,'图片格式不正确或超过2M');
        }
    }
}