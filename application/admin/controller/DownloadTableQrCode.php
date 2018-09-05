<?php
/**
 * Created by PhpStorm.
 * User: qubojie
 * Date: 2018/8/23
 * Time: 下午4:32
 */
namespace app\admin\controller;

use app\admin\model\MstTable;
use think\Controller;
use think\Request;

class DownloadTableQrCode extends Controller
{

    /**
     * 一键生成打包或者单独下载
     * @param Request $request
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function zipTableQrCode(Request $request)
    {
        $type = $request->param("type","");//1一键打包 其他的 单个下载

        $table_id = $request->param("table_id","");

        if ($type){
            return $this->allDownload();
        }else{
            return $this->oneDownload($table_id);
        }


    }

    /**
     * 单个下载
     * @param $table_id
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function oneDownload($table_id)
    {
        $tableModel = new MstTable();

        $tableInfo = $tableModel
            ->alias("t")
            ->join("mst_table_area ta","ta.area_id = t.area_id")
            ->join("mst_table_location tl","tl.location_id = ta.location_id")
            ->where("t.table_id",$table_id)
            ->field("t.table_no")
            ->field("ta.area_title")
            ->field("tl.location_title")
            ->find();

        $tableInfo = json_decode(json_encode($tableInfo),true);

        $location_title = $tableInfo['location_title'];//大区
        $area_title     = $tableInfo['area_title'];//小区
        $table_no       = $tableInfo['table_no'];//桌号

        $name = $location_title.'_'.$area_title.'_'.$table_no;



        $wxQrCodeObj = new WxQrcode();

        $res = $wxQrCodeObj->create($table_id);

        if (isset($res['result']) && $res['result']){

            $tableQrCode = ["/WXQRCODE/".$name.'.png'];

            $zipName = __DIR__."/../../../public/WXQRCODE/download".$table_no.".zip";

            //is_dir($zipName) OR @mkdir($zipName,0777,true);

            $zip     = new \ZipArchive();//使用本类，linux需开启zlib，windows需取消php_zip.dll前的注释

            // | \ZipArchive::CREATE
            if ($zip->open($zipName, \ZIPARCHIVE::OVERWRITE || \ZIPARCHIVE::CREATE) !== true){
                return $this->com_return(false,"无法打开文件，或者文件创建失败");
            }

            foreach ($tableQrCode as $val){
                $vals = __DIR__."/../../../public".$val;

                if (file_exists($vals)){
                    $zip->addFile($vals);
                }
            }

            $zip->close();

            if(!file_exists($zipName)){
                return $this->com_return(false,"无法找到文件");//即使创建，仍有可能失败
            }


            //如果不要下载，下面这段删掉即可，如需返回压缩包下载链接，只需 return $zipName;
            header("Cache-Control: public");
            header("Content-Description: File Transfer");
            header('Content-disposition: attachment; filename='.basename($zipName)); //文件名
            header("Content-Type: application/zip"); //zip格式的
            header("Content-Transfer-Encoding: binary"); //告诉浏览器，这是二进制文件
            header('Content-Length: '. filesize($zipName)); //告诉浏览器，文件大小
            @readfile($zipName);
        }
    }


    /**
     * 全部打包
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function allDownload()
    {
        $tableModel = new MstTable();

        $table_info = $tableModel
            ->where("is_delete",'0')
            ->field("table_id")
            ->select();

        $table_info = json_decode(json_encode($table_info),true);

        $table_id_str = "";
        for ($i = 0; $i < count($table_info); $i ++){
            $table_id = $table_info[$i]['table_id'];

            $table_id_str .= $table_id.",";
        }


        $table_id_str = rtrim($table_id_str,",");

        $wxQrCodeObj = new WxQrcode();

        $res = $wxQrCodeObj->create($table_id_str);

        if (isset($res['result']) && $res['result']){

            $tableQrCode = $res["data"];

            $zipName = __DIR__."/../../../public/WXQRCODE/downloadQrCode.zip";

            //is_dir($zipName) OR @mkdir($zipName,0777,true);

            $zip     = new \ZipArchive();//使用本类，linux需开启zlib，windows需取消php_zip.dll前的注释

            /*
             * 通过ZipArchive的对象处理zip文件
             * $zip->open这个方法如果对zip文件对象操作成功，$zip->open这个方法会返回TRUE
             * $zip->open这个方法第一个参数表示处理的zip文件名。
             * 这里重点说下第二个参数，它表示处理模式
             * ZipArchive::OVERWRITE 总是以一个新的压缩包开始，此模式下如果已经存在则会被覆盖。
             * ZIPARCHIVE::CREATE 如果不存在则创建一个zip压缩包，若存在系统就会往原来的zip文件里添加内容。
             *
             * 这里 大坑。
             * 我的应用场景是需要每次都是创建一个新的压缩包，如果之前存在，则直接覆盖，不要追加
             * so，根据官方文档和参考其他代码，$zip->open的第二个参数我应该用 ZipArchive::OVERWRITE
             * 问题来了，当这个压缩包不存在的时候，会报错：ZipArchive::addFile(): Invalid or uninitialized Zip object
             * 也就是说，通过我的测试发现，ZipArchive::OVERWRITE 不会新建，只有当前存在这个压缩包的时候，它才有效
             * 所以我的解决方案是 $zip->open($zipName, \ZIPARCHIVE::OVERWRITE | \ZIPARCHIVE::CREATE)
             *
             * 以上总结基于我当前的运行环境来说
             *
             */

            // | \ZipArchive::CREATE
            if ($zip->open($zipName, \ZIPARCHIVE::OVERWRITE || \ZIPARCHIVE::CREATE) !== true){
                return $this->com_return(false,"无法打开文件，或者文件创建失败");
            }

            foreach ($tableQrCode as $val){
                $vals = __DIR__."/../../../public".$val;

                if (file_exists($vals)){
                    $zip->addFile($vals);
                }
            }

            $zip->close();

            if(!file_exists($zipName)){
                return $this->com_return(false,"无法找到文件");//即使创建，仍有可能失败
            }


            //如果不要下载，下面这段删掉即可，如需返回压缩包下载链接，只需 return $zipName;
            header("Cache-Control: public");
            header("Content-Description: File Transfer");
            header('Content-disposition: attachment; filename='.basename($zipName)); //文件名
            header("Content-Type: application/zip"); //zip格式的
            header("Content-Transfer-Encoding: binary"); //告诉浏览器，这是二进制文件
            header('Content-Length: '. filesize($zipName)); //告诉浏览器，文件大小
            @readfile($zipName);
        }
    }
}