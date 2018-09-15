<?php
/**
 * 微信获取二维码.
 * User: qubojie
 * Date: 2018/8/22
 * Time: 下午6:11
 */
namespace app\admin\controller;

use app\admin\model\MstTable;
use think\Controller;
use think\Env;
use think\Request;

class WxQrcode extends Controller
{
    /**
     * @param $table_ids '参数桌id',多个以逗号隔开
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function create($table_ids)
    {
//        $table_ids = $this->request->param("table_id","");

        if (empty($table_ids)){
            return $this->com_return(false,config("params.PARAM_NOT_EMPTY"));
        }

        $delimiter    = config("qrcode.delimiter")['key'];

        $prefix       = config("qrcode.prefix")['2']['key'];

        $id_array     = explode(",",$table_ids);

        $ACCESS_TOKEN = $this->getAccessToken();

        $tableModel = new MstTable();

        $src = [];

        foreach ($id_array as $table_id){

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

            $postParams = [
                "scene"      => $prefix.$delimiter.$table_id,
                "page"       => "pages/index/main",
                "width"      => "430",
                "auto_color" => false,
                "is_hyaline" => config("qrcode.is_hyaline")['key']
            ];

            $postParams = json_encode($postParams);

            $res = $this->requestPost($ACCESS_TOKEN,$postParams);

            //  设置文件路径和文件前缀名称
            $path = __DIR__."/../../../public/WXQRCODE/";

            is_dir($path) OR @mkdir($path,0777,true);

            $name = $location_title.'_'.$area_title.'_'.$table_no;

            file_put_contents($path.$name.'.png',$res);

            $src[] = "/WXQRCODE/".$name.'.png';
        }

        return $this->com_return(true,config("params.SUCCESS"),$src);
    }

    /**
     * 模拟post接口请求,获取二维码
     *
     * @param $ACCESS_TOKEN
     * @param array $curlPost
     * @return bool|mixed
     */
    protected function requestPost($ACCESS_TOKEN,$curlPost = array())
    {
        $postUrl = "https://api.weixin.qq.com/wxa/getwxacodeunlimit?access_token=$ACCESS_TOKEN";

        if (empty($postUrl) || empty($curlPost)) {
            return false;
        }

        $ch = curl_init();//初始化curl
        curl_setopt($ch, CURLOPT_URL,$postUrl);//抓取指定网页
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);//要求结果为字符串且输出到屏幕上
        curl_setopt($ch, CURLOPT_POST, 1);//post提交方式
        curl_setopt($ch, CURLOPT_POSTFIELDS, $curlPost);
        $data = curl_exec($ch);//运行curl
        curl_close($ch);

        return $data;
    }


    public function getAccessToken() {
        // access_token 应该全局存储与更新，以下代码以写入到文件中做示例
        $data   = json_decode(file_get_contents("xcx_access_token.json"));
        $appid  = Env::get("WECHAT_XCX_APPID");
        $secret = Env::get("WECHAT_XCX_APPSECRET");

        if ($data->expire_time < time()) {
            // 如果是企业号用以下URL获取access_token
            // $url = "https://qyapi.weixin.qq.com/cgi-bin/gettoken?corpid=$this->appId&corpsecret=$this->appSecret";

            $url = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=".$appid."&secret=".$secret."";
            $res = json_decode($this->httpGet($url));
            $access_token = $res->access_token;
            if ($access_token) {
                $data->expire_time = time() + 7000;
                $data->access_token = $access_token;
                $fp = fopen("xcx_access_token.json", "w");
                fwrite($fp, json_encode($data));
                fclose($fp);
            }
        } else {
            $access_token = $data->access_token;
        }
        return $access_token;
    }

    /**
     * 获取管理端的access_token
     * @return mixed
     */
    public function getManageAccessToken() {
        // access_token 应该全局存储与更新，以下代码以写入到文件中做示例
        $data   = json_decode(file_get_contents("xcx_manage_access_token.json"));
        $appid  = Env::get("WECHAT_XCX_MANAGE_APPID");
        $secret = Env::get("WECHAT_XCX_MANAGE_APPSECRET");
        if ($data->expire_time < time()) {
            // 如果是企业号用以下URL获取access_token
            // $url = "https://qyapi.weixin.qq.com/cgi-bin/gettoken?corpid=$this->appId&corpsecret=$this->appSecret";

            $url = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=".$appid."&secret=".$secret."";
            $res = json_decode($this->httpGet($url));
            $access_token = $res->access_token;
            if ($access_token) {
                $data->expire_time = time() + 7000;
                $data->access_token = $access_token;
                $fp = fopen("xcx_manage_access_token.json", "w");
                fwrite($fp, json_encode($data));
                fclose($fp);
            }
        } else {
            $access_token = $data->access_token;
        }
        return $access_token;
    }


    private function httpGet($url) {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_TIMEOUT, 500);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($curl, CURLOPT_URL, $url);

        $res = curl_exec($curl);
        curl_close($curl);

        return $res;
    }

}