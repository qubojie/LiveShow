<?php
/**
 * Created by PhpStorm.
 * User: qubojie
 * Date: 2018/10/9
 * Time: 下午2:50
 */
namespace app\common\controller;

use Qiniu\Auth;
use Qiniu\Config;
use Qiniu\Storage\BucketManager;
use Qiniu\Storage\UploadManager;
use think\Controller;
use think\Env;
use think\Request;

vendor("Qiniu.autoload");

class QiNiuUpload extends Controller
{
    /**
     * 七牛云上传
     * @param $doc
     * @param $prefix
     * @param $type
     * @return array
     * @throws \Exception
     */
    public function upload($doc,$prefix,$type)
    {
        $file = Request::instance()->file($doc);

        if (empty($file)){
            return $this->com_return(false,'请选择文件');
        }

        // 要上传图片的本地路径
        $filePath = $file->getRealPath();

        //按照type判断允许的扩展名
        if ($type == '0'){
            $allowExt = 'jpeg,jpg,png,bmp,gif,ico';
        }elseif ($type == '1'){
            $allowExt = 'mov,3gp,mp4,flv,wmv,avi,rm,rmvb,mp3,aac,wma';
        }elseif ($type == '2'){
            $allowExt = 'doc,txt,xls';
        }

        //后缀
        $ext = pathinfo($file->getInfo('name'), PATHINFO_EXTENSION);
        if (!in_array($ext, explode(',',$allowExt))){
            return $this->com_return(false,'该类型不被允许');
        }

        //上传到七牛后保存的文件名
        $key = $prefix . "/" .substr(md5($file->getRealPath()), 0, 5) . date('YmdHis') . rand(0, 9999) . '.' . $ext;
        // 需要填写你的 Access Key 和 Secret Key
        $accessKey = Env::get("QINIU_ACCESS_KEY");
        $secretKey = Env::get("QINIU_SECRET_KEY");

        //构建鉴权对象
        $auth = new Auth($accessKey, $secretKey);

        //要上传的空间
        $bucket = Env::get("QINIU_IMG_BUCKET");
        //空间绑定的域名
        $domain = Env::get("QINIU_IMG_URL");

        $token = $auth->uploadToken($bucket);

        //初始化 UploadManager 对象并进行文件的上传
        $uploadMgr = new UploadManager();

        //调用 UploadManager 的 putFile 方法进行文件的上传
        list($ret, $err) = $uploadMgr->putFile($token, $key, $filePath);

        if ($err !== null){
            return $this->com_return(false,$err);
        }

        $ret["size"] = $file->getInfo('size');
        $ret["url"] = $domain;
        $ret["extension"] = $ext;
        return $this->com_return(true,'上传成功',$ret);
    }

    /**
     * 七牛云资源删除
     * @param $key
     * @return array
     */
    public function delete($key)
    {
        $accessKey = Env::get("QINIU_ACCESS_KEY");
        $secretKey = Env::get("QINIU_SECRET_KEY");

        //要上传的空间
        $bucket = Env::get("QINIU_IMG_BUCKET");
        //空间绑定的域名
        $domain = Env::get("QINIU_IMG_URL");

        $auth          = new Auth($accessKey, $secretKey);
        $config        = new Config();
        $bucketManager = new BucketManager($auth, $config);
        $err           = $bucketManager->delete($bucket, $key);

        if ($err) {
            return $this->com_return(false,$err);
        }

        return $this->com_return(true,'删除成功');
    }
}