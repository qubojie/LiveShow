<?php
/**
 * 七牛云存储.
 * User: qubojie
 * Date: 2018/6/25
 * Time: 上午9:28
 */
namespace app\admin\controller;

vendor('Qiniu.autoload');

use Qiniu\Auth as Auth;

use Qiniu\Storage\BucketManager;
use Qiniu\Storage\UploadManager;
use think\Env;
use think\Request;

class QiniuUpload extends CommandAction
{
    /*
     * 对象上传
     * */
    public function upload(Request $request)
    {
        $common = new Common();

        $file = $request->file("image");

        $prefix = $request->param("prefix","");

        if (empty($prefix)) {
            return $common->com_return(false,config("PARAM_NOT_EMPTY"));
        }

        if (!empty($file)){

            // 要上传图片的本地路径
            $filePath = $file->getRealPath();

            //后缀
            $ext = pathinfo($file->getInfo('name'), PATHINFO_EXTENSION);

            //获取当前控制器的名称
            $controllerName = 'upload';

            //上传到七牛后保存的文件名
            $key = $prefix . "/" .substr(md5($file->getRealPath()), 0, 5) . date('YmdHis') . rand(0, 9999) . '.' . $ext;

            // 需要填写你的 Access Key 和 Secret Key
            $accessKey = Env::get("QINIU_ACCESS_KEY");
            $secretKey = Env::get("QINIU_SECRET_KEY");

            //构建鉴权对象
            $auth = new Auth($accessKey, $secretKey);

            //要上传的空间
            $bucket = Env::get("QINIU_SYS_BUCKET");

            //空间绑定的域名
            $domain = Env::get("QINIU_SYS_URL");

            $token = $auth->uploadToken($bucket);

            //初始化 UploadManager 对象并进行文件的上传
            $uploadMgr = new UploadManager();

            //调用 UploadManager 的 putFile 方法进行文件的上传
            list($ret, $err) = $uploadMgr->putFile($token, $key, $filePath);


            if ($err !== null){
                return $common->com_return(false,$err);
            }else{
                $ret["url"] = $domain;
                return $common->com_return(true,'上传成功',$ret);
            }

        }else{
            return $common->com_return(false,'请选择文件');
        }
    }
}