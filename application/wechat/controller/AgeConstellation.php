<?php
/**
 * 星座和年龄计算
 * User: qubojie
 * Date: 2018/6/28
 * Time: 下午8:15
 */
namespace app\wechat\controller;

use think\Controller;

class AgeConstellation extends Controller
{
    /**
     * 根据生日计算年龄和生肖和星座
     * @param $birthday 890101
     *
     * @return string
     */
    public function getInfo($birthday)
    {
        $birth=$birthday;

        $by = substr($birth,0,2);
        if ($by <100 && $by >= 40){
            $by = "19".$by;
        }else{
            $by = "20".$by;
        }

        //获取生肖
        $animal = $this->get_animal($by);

        $res['animal'] = $animal;

        $bm = substr($birth,2,2);
        $bd = substr($birth,4,2);

        //获取星座
        $constellation = $this->get_constellation($bm,$bd);

        $res['constellation'] = $constellation;

        $cm=date('n');
        $cd=date('j');
        $age=date('Y')-$by-1;
        if ($cm>$bm || $cm==$bm && $cd>$bd) $age++;

        $res['age'] = $age;
        return $res;
    }

    /**
     *  计算.生肖
     *
     * @param int $year 年份
     * @return string
     */
    public function get_animal($year){
        $animals = array(
            '鼠', '牛', '虎', '兔', '龙', '蛇',
            '马', '羊', '猴', '鸡', '狗', '猪'
        );
        $key = ($year - 1900) % 12;
        return $animals[$key];
    }

    /**
     *  计算.星座
     *
     * @param int $month 月份
     * @param int $day 日期
     * @return str
     */
    public  function get_constellation($month, $day){
        // 检查参数有效性
        if ($month < 1 || $month > 12 || $day < 1 || $day > 31) return false;

        // 星座名称以及开始日期
        $constellations = array(
            array( "20" => "宝瓶座"),
            array( "19" => "双鱼座"),
            array( "21" => "白羊座"),
            array( "20" => "金牛座"),
            array( "21" => "双子座"),
            array( "22" => "巨蟹座"),
            array( "23" => "狮子座"),
            array( "23" => "处女座"),
            array( "23" => "天秤座"),
            array( "24" => "天蝎座"),
            array( "22" => "射手座"),
            array( "22" => "摩羯座")
        );

        //dump($month);die;

        foreach ($constellations[$month - 1] as $key => $val) {
            $constellation_start = $key;
            $constellation_name = $val;
        }

        if ($day < $constellation_start){

            foreach ($constellations[($month -2 < 0) ? $month = 11: $month -= 2] as $key=> $val){
                $constellation_start = $key;
                $constellation_name = $val;
            }

        }
        return $constellation_name;

    }

}
