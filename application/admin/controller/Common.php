<?php
/**
 * Created by PhpStorm.
 * User: qubojie
 * Date: 2018/6/20
 * Time: 下午2:20
 */
namespace app\admin\controller;

use think\Controller;
use think\Response;

class Common extends Controller
{
    /*
     * 加密token
     * */
    public function jm_token($str)
    {
        return md5(sha1($str).time());
    }

    /*
     * 公共返回
     * */
    public function com_return($result = false,$message,$data = null)
    {
        return [
            "result"  => $result,
            "message" => $message,
            "data"    => $data
        ];
    }

    /*
     * 移除数组中指定Key的元素
     * */
    public function bykey_reitem($arr,$key)
    {
        if (!array_key_exists($key,$arr)){
            return $arr;
        }
        $keys = array_keys($arr);
        $index = array_search($key,$keys);
        if ($index !== false){
            array_splice($arr,$index,1);
        }
        return $arr;
    }

    /*
     * 将手机号码中间四位替换为 *
     * */
    public function jm_tel($tel)
    {
        $xing = substr($tel,3,4);  //获取手机号中间四位
        $return = str_replace($xing,'****',$tel);  //用****进行替换
        return $return;
    }


    /*
     * 生成无限极分类树
     * @param $arr 数据数组结构
     * @param $key_id 主键id的key
     * @param $parent_id 区分层级关系的 Key名
     * */
    public function make_tree($arr,$key_id,$parent_id)
    {
        $refer = array();
        $tree = array();

        foreach($arr as $k => $v){
            $refer[$v[$key_id]] = & $arr[$k]; //创建主键的数组引用
        }

        foreach($arr as $k => $v){
            $pid = $v[$parent_id];  //获取当前分类的父级id
            if($pid == 0){
                $arr[$k]['parent_id'] = (string)$arr[$k]['parent_id'];
                $tree[] = & $arr[$k];  //顶级栏目

            }else{
                if(isset($refer[$pid])){
                    $arr[$k]['parent_id'] = (string)$arr[$k]['parent_id'];
                    $refer[$pid]['children'][] = & $arr[$k]; //如果存在父级栏目，则添加进父级栏目的子栏目数组中
                }
            }
        }
        return $tree;
    }

    /*
     * 备用
     * */
    public function make_tree2($arr,$key_id,$parent_id)
    {
        $refer = array();
        $tree = array();

        foreach($arr as $k => $v){
            $refer[$v[$key_id]] = & $arr[$k]; //创建主键的数组引用
        }

        foreach($arr as $k => $v){
            $pid = $v[$parent_id];  //获取当前分类的父级id
            if($pid == 0){
                //dump($arr[$k]);die;
                $tree[] = & $arr[$k];  //顶级栏目

            }else{
                if(isset($refer[$pid])){
                    $refer[$pid]['children'][] = & $arr[$k]; //如果存在父级栏目，则添加进父级栏目的子栏目数组中
                }
            }
        }
        return $tree;
    }

    /**
     * 生成唯一字符串 最长32位
     * @return string
     */
    public function uniqueCode($length = 8)
    {
        if ($length > 32) $length = 32;

        $charid = strtoupper(md5(uniqid(rand(),true)));
        $hyphen = chr(45);// "-"
        $uuid = chr(123)// "{"
            .substr($charid, 0, 8).$hyphen
            .substr($charid, 8, 4).$hyphen
            .substr($charid,12, 4).$hyphen
            .substr($charid,16, 4).$hyphen
            .substr($charid,20,12)
            .chr(125);// "}"
        $code = $uuid;
//        return strtolower(substr(str_replace('-', '', $code), 4, 8));
        return (substr(str_replace('-', '', $code), 1, $length));
    }

}