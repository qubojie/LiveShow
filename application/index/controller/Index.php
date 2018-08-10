<?php
namespace app\index\controller;

use app\admin\model\MstCardVip;
use app\admin\model\MstCardVipGiftRelation;
use app\admin\model\MstGift;
use app\admin\model\User;
use app\wechat\model\BillCardFees;
use app\wechat\model\BillCardFeesDetail;
use think\Config;
use think\Controller;
use think\Db;
use think\Request;

class Index extends Controller
{
    public function index()
    {
        return $this->fetch();
    }

    public function test(Request $request)
    {
        $params = [
            "offerId" => 67
        ];

        $res = $this->requestPost($params);

        dump($res);die;

    }

    /**
     * 模拟post支付回调接口请求
     *
     * @param array $postParams
     * @return bool|mixed
     */
    protected function requestPost($postParams = array())
    {
//        $request = Request::instance();

//        $url = $request->domain()."/wechat/notify";
        $url = "http://wefound.51ideal.com:8000/offerAnalysis/";


        if (empty($url) || empty($postParams)) {
            return false;
        }

        $o = "";
        foreach ( $postParams as $k => $v )
        {
            $o.= "$k=" . urlencode( $v ). "&" ;
        }

        $postParams = substr($o,0,-1);


        $postUrl = $url;
        $curlPost = $postParams;

        $header = array();
//        $header[] = 'Authorization:'.$Authorization;

        $ch = curl_init();//初始化curl
        curl_setopt($ch, CURLOPT_URL,$postUrl);//抓取指定网页
//        curl_setopt($ch, CURLOPT_HEADER, $header);//设置header
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);//设置header
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);//要求结果为字符串且输出到屏幕上
        curl_setopt($ch, CURLOPT_POST, 1);//post提交方式
        curl_setopt($ch, CURLOPT_POSTFIELDS, $curlPost);
        $data = curl_exec($ch);//运行curl
        curl_close($ch);

        return $data;
    }
}
