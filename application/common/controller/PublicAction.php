<?php
/**
 * Created by PhpStorm.
 * User: qubojie
 * Date: 2018/9/8
 * Time: 下午4:55
 */
namespace app\common\controller;

class PublicAction
{


    /**
     * 判断是否为空数组
     * @param mixed $arr
     * @return boolean
     */
    public static function isEmptyArray($arr)
    {
        if (empty($arr) || !is_array($arr) || count($arr) == 0) {
            return true;
        }
        return false;
    }

    /**
     * 判断是否为空数据
     * @param mixed $data
     * @return boolean
     */
    public static function isEmpty($data)
    {
        if (empty($data)) {
            return true;
        }
        return false;
    }

    /**
     * 解析时间范围查询条件
     * @param string $strTimeRange
     * @param string $inputType 字段配置中的input_type类型
     * @param string $sep 间隔符
     * @return array
     */
    public static function parseTimeRange($strTimeRange, $inputType, $sep = '-')
    {
        $arrTimeRange = explode('-', $strTimeRange);
        $arrTimeRange[0] = trim($arrTimeRange[0]);
        $arrTimeRange[1] = trim($arrTimeRange[1]);

        if ('timestamp' === $inputType) {
            if (!empty($arrTimeRange[0])) {
                $arrTimeRange[0] = strtotime($arrTimeRange[0]);
            }

            if (!empty($arrTimeRange[1])) {
                $arrTimeRange[1] = strtotime($arrTimeRange[1]) + 86400 - 1;
            }
        } else {
            if (!empty($arrTimeRange[1])) {
                if (strpos($arrTimeRange[1], ':') === false) {
                    $arrTimeRange[1] .= ' 23:59:59';
                }
            }
        }
        return $arrTimeRange;
    }


    /**
     * 验证密码 : 长度6位及以上,至少包含1个数字,1个大写字母,1个小写字母,不包含空格
     * @param $password
     * @return bool
     */
    public static function checkPassword($password)
    {
        if (preg_match("/^((?=\S*?[A-Z])(?=\S*?[a-z])(?=\S*?[0-9]).{5,})\S$/",$password)){
            return true;
        }else{
            return false;
        }
    }


    /**
     * 验证邮箱是否准确
     *
     * @param string $str
     *            被验证的字符串
     * @return boolean
     */
    public static function checkEmail($str)
    {
        if (preg_match("/^([a-zA-Z0-9])+([\w-.])*([a-zA-Z0-9])+@([a-zA-Z0-9_-])+(\.[a-zA-Z0-9_-]+)/", $str)) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * 验证url格式
     * @param string $url
     * @return boolean
     */
    public static function checkUrl($url)
    {
        if (!preg_match('/http:\/\/[\w.]+[\w\/]*[\w.]*\??[\w=&\+\%]*/is', $url)) {
            return false;
        }
        return true;
    }

    /**
     * 验证是否全是中文
     *
     * @param string $str
     *            被验证的字符串
     * @return boolean
     */
    public static function checkCn($str)
    {
        if (!eregi("[^\x80-\xff]", "$str")) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * 判断是否存在中文
     *
     * @param string $str
     *            被验证的字符串
     * @return boolean
     */
    public static function deCn($str)
    {
        $strlen = strlen($str);
        $length = 1;
        for ($i = 0; $i < $strlen; $i++) {
            $tmpstr = ord(substr($str, $i, 1));
            if (($tmpstr <= 161 || $tmpstr >= 247)) {
                $a = 0;
            } else {
                $a = 1;
                break;
            }
        }
        if ($a == '0')
            return false;
        else
            return true;
    }

    /**
     * 验证是否全是字母
     *
     * @param string $str
     *            被验证的字符串
     * @return boolean
     */
    public static function checkEn($str)
    {
        if (preg_match("/^[a-zA-Z]+$/", "$str")) {
            return true;
        } else {
            return false;
        }
    }


    /**
     * 验证是否全是数字
     *
     * @param string $str
     *            被验证的字符串
     * @return boolean
     */
    public static function checkNumber($str)
    {
        if (preg_match("/^[0-9]+$/", $str)) {
            return true;
        } else {
            return false;
        }
    }


    /**
     * 验证是否全是字符
     *
     * @param string $str
     *            被验证的字符串
     * @return boolean
     */
    public static function checkStr($str)
    {
        if (preg_match("/^[a-zA-Z_0-9]+$/", "$str")) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * 验证RGB color颜色
     *
     * @param string $str
     *            被验证的字符串
     * @return boolean
     */
    public static function checkColorRGB($str)
    {
        if (strlen($str) != 6) {
            return false;
        }
        if (preg_match("/^[a-zA-Z0-9]+$/", "$str")) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * 验证是否全是英文字母或数字
     * @param string $str 被验证的字符串
     * @return boolean
     */
    public static function checkLetterNumber($str)
    {
        if (preg_match("/^[a-zA-Z0-9]+$/", "$str")) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * 检测微信id规则
     * @param string $str
     * @return boolean
     */
    public static function checkWxId($str)
    {
        //前2位字符为"wx" 及 长度为16~20位(微信 appid长度为18位, 检测时考虑点伸缩)
        if (substr($str, 0, 2) == 'wx' && preg_match("/^[a-zA-Z0-9]{16,20}$/", $str)) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * 验证是否为unix时间戳
     * @param string $str 被验证的字符串
     * @return boolean
     */
    public static function checkUnixTimeStamp($str)
    {
        if (preg_match("/^[1-9][0-9]{9}$/", "$str")) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * 验证是否是百分率
     *
     * @param string $str
     *            被验证的字符串
     * @return boolean
     */
    public static function checkPercent($str)
    {
        if (preg_match("/^[0-9]+(.[0-9]+)?%$/", "$str")) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * 验证价格格式是否正确
     *
     * @param string $str
     *            被验证的字符串
     * @return boolean
     */
    public static function checkMoney($str)
    {
        if (preg_match("/^[-]?[0-9]+(.[0-9]+)?$/", "$str")) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * 验证浮点数
     *
     * @param string $str
     *            被验证的字符串
     * @return boolean
     */
    public static function checkFloat($str)
    {
        if (preg_match("/^[0-9]+(.[0-9]+)?$/", "$str")) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * 验证QQ号码
     *
     * @param string $str
     *            被验证的字符串
     * @return boolean
     */
    public static function checkQq($str)
    {
        if (preg_match("/^[1-9][0-9]{4,9}$/", "$str")) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * 验证手机号码
     *
     * @param string $str
     *            被验证的字符串
     * @return boolean
     */
    public static function checkMobile($str)
    {
        if (preg_match("/^(?=\d{11}$)^1(?:3\d|4[57]|5[^4\D]|7[^249\D]|8\d)\d{8}$/", $str)) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * 验证电话号码
     *
     * @param string $str
     *            被验证的字符串
     * @return boolean
     */
    public static function checkPhone($str)
    {
        if (preg_match("/^([0]\d{2,3})?[1-9]{1}\d{6,7}$/", $str)) {
            return true;
        }
        return false;
    }

    /**
     * 验证特殊电话号码, 如10000、95555、400号码(长度不小于5位的数字)
     * @param string $str
     * @return boolean
     */
    public static function checkSpecialPhone($str)
    {
        if (preg_match("/^\d{5,20}$/", "$str")) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * 验证身份证号码
     *
     * @param string $str
     *            被验证的字符串
     * @return boolean
     */
    public static function checkIdCard($str)
    {
        if (preg_match("/^[1-9][0-9]{17}$/", "$str")) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * 验证日期是否有效
     *
     * @param string $str
     *            被验证的字符串
     * @return boolean
     */
    public static function checkDateTime($str)
    {
        if (preg_match("/^[1-9][0-9]{3}-[0-1]?[0-9]-[0-3][0-9]$/", "$str")) {
            $tmp_arr = explode("-", $str);
            // 『月 『日 『年
            if (checkdate("$tmp_arr[1]", "$tmp_arr[2]", "$tmp_arr[0]")) {
                return true;
            }
        } else {
            return false;
        }
    }

    /**
     * 验证ip地址
     *
     * @param string $str
     *            被验证的字符串
     * @return boolean
     */
    public static function checkIp($str)
    {
        if (preg_match("/^[1-2]\d{1,2}\.\d{0,3}\.\d{0,3}\.\d{0,3}$/", $str)) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * 判断用户输入的endpoint是否是 xxx.xxx.xxx.xxx:port 或者 xxx.xxx.xxx.xxx的ip格式
     *
     * @param string $endpoint 需要做判断的endpoint
     * @return boolean
     */
    public static function isIPFormat($endpoint)
    {
        $ip_array = explode(":", $endpoint);
        $hostname = $ip_array[0];
        $ret = filter_var($hostname, FILTER_VALIDATE_IP);
        if (!$ret) {
            return false;
        } else {
            return true;
        }
    }

    /**
     * 生成query params
     *
     * @param array $options 关联数组
     * @return string 返回诸如 key1=value1&key2=value2
     */
    public static function toQueryString($options = array())
    {
        $temp = array();
        uksort($options, 'strnatcasecmp');
        foreach ($options as $key => $value) {
            if (is_string($key) && !is_array($value)) {
                $temp[] = rawurlencode($key) . '=' . rawurlencode($value);
            }
        }
        return implode('&', $temp);
    }

    /**
     * 转义字符替换
     *
     * @param string $subject
     * @return string
     */
    public static function sReplace($subject)
    {
        $search = array('<', '>', '&', '\'', '"');
        $replace = array('&lt;', '&gt;', '&amp;', '&apos;', '&quot;');
        return str_replace($search, $replace, $subject);
    }

    /**
     * 检查是否是中文编码
     *
     * @param $str
     * @return int
     */
    public static function chkChinese($str)
    {
        return preg_match('/[\x80-\xff]./', $str);
    }

    /**
     * 检测是否GB2312编码
     *
     * @param string $str
     * @return boolean false UTF-8编码  TRUE GB2312编码
     */
    public static function isGb2312($str)
    {
        for ($i = 0; $i < strlen($str); $i++) {
            $v = ord($str[$i]);
            if ($v > 127) {
                if (($v >= 228) && ($v <= 233)) {
                    if (($i + 2) >= (strlen($str) - 1)) return true;  // not enough characters
                    $v1 = ord($str[$i + 1]);
                    $v2 = ord($str[$i + 2]);
                    if (($v1 >= 128) && ($v1 <= 191) && ($v2 >= 128) && ($v2 <= 191))
                        return false;
                    else
                        return true;
                }
            }
        }
        return false;
    }

    /**
     * 检测是否GBK编码
     *
     * @param string $str
     * @param boolean $gbk
     * @return boolean
     */
    public static function checkChar($str, $gbk = true)
    {
        for ($i = 0; $i < strlen($str); $i++) {
            $v = ord($str[$i]);
            if ($v > 127) {
                if (($v >= 228) && ($v <= 233)) {
                    if (($i + 2) >= (strlen($str) - 1)) return $gbk ? true : FALSE;  // not enough characters
                    $v1 = ord($str[$i + 1]);
                    $v2 = ord($str[$i + 2]);
                    if ($gbk) {
                        return (($v1 >= 128) && ($v1 <= 191) && ($v2 >= 128) && ($v2 <= 191)) ? FALSE : TRUE;//GBK
                    } else {
                        return (($v1 >= 128) && ($v1 <= 191) && ($v2 >= 128) && ($v2 <= 191)) ? TRUE : FALSE;
                    }
                }
            }
        }
        return $gbk ? TRUE : FALSE;
    }

    /**
     * 判断字符串$str是不是以$findMe开始
     *
     * @param string $str
     * @param string $findMe
     * @return bool
     */
    public static function startsWith($str, $findMe)
    {
        if (strpos($str, $findMe) === 0) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * 检测是否windows系统，因为windows系统默认编码为GBK
     *
     * @return bool
     */
    public static function isWin()
    {
        return strtoupper(substr(PHP_OS, 0, 3)) == "WIN";
    }


    /**
     * 主要是由于windows系统编码是gbk，遇到中文时候，如果不进行转换处理会出现找不到文件的问题
     *
     * @param $file_path
     * @return string
     */
    public static function encodePath($file_path)
    {
        if (self::chkChinese($file_path) && self::isWin()) {
            $file_path = iconv('utf-8', 'gbk', $file_path);
        }
        return $file_path;
    }


    /**
     * The main function for converting to an XML document.
     * Pass in a multi dimensional array and this recrusively loops through and builds up an XML document.
     *
     * @param array $data 待转换的数组
     * @param string $rootNodeName 根结点名称
     *            - what you want the root node to be - defaultsto data.
     * @param SimpleXMLElement $xml xml格式协议头
     *            - should only be used recursively
     * @return string XML
     */
    public static function toXml($data, $rootNodeName = 'data', $xml = null)
    {
        // turn off compatibility mode as simple xml throws a wobbly if you don't.
        if (ini_get('zend.ze1_compatibility_mode') == 1) {
            ini_set('zend.ze1_compatibility_mode', 0);
        }

        if ($xml == null) {
            $xml = simplexml_load_string("<?xml version='1.0' encoding='utf-8'?><$rootNodeName />");
        }

        // loop through the data passed in.
        foreach ($data as $key => $value) {
            // no numeric keys in our xml please!
            if (is_numeric($key)) {
                // make string key...
                $key = "unknownNode_" . (string)$key;
            }

            // replace anything not alpha numeric
            $key = preg_replace('/[^a-z]/i', '', $key);

            // if there is another array found recrusively call this function
            if (is_array($value)) {
                $node = $xml->addChild($key);
                // recrusive call.
                PublicAction::toXml($value, $rootNodeName, $node);
            } else {
                // add single node.
                $value = htmlentities($value);
                $xml->addChild($key, $value);
            }
        }
        // pass back as string. or simple xml object if you want!
        return $xml->asXML();
    }

    /**
     * 将xml字符串转化成数组
     *
     * @param string $xml xml字符串
     * @param boolean $flagCDATA 读取xml中<![CDATA[]]>数据
     * @return array
     */
    public static function xmlToArray($xml, $flagCDATA = false)
    {
        if (!$flagCDATA) {
            $objTmp = simplexml_load_string($xml);  //xml转化为对象
        } else {
            $objTmp = simplexml_load_string($xml, null, LIBXML_NOCDATA);  //xml转化为对象
        }
        $strTmp = json_encode($objTmp);  //对象转化为json
        $arrTmp = json_decode($strTmp, true);  //json转换为
        return $arrTmp;
    }

    /**
     * 号码隐藏
     * @param $phone
     * @return mixed
     */
    public static function hideTelephone($phone)
    {
        if (!preg_match('/^\d{5,20}$/', $phone)) {
            return $phone;
        }

        $isWhat = preg_match('/(0[0-9]{2,3}[-]?[2-9][0-9]{6,7}[-]?[0-9]?)/i', $phone); //固定电话
        if ($isWhat) {
            return preg_replace('/(0[0-9]{2,3}[-]?[2-9])[0-9]{3,4}([0-9]{3}[-]?[0-9]?)/i', '$1****$2', $phone);
        } else {
            return preg_replace('/([1-9][0-9]{1}[0-9])[0-9]{1,4}([0-9]{1,4})/i', '$1****$2', $phone);
        }
    }

    /**
     * 电话号码加密
     * @param $phone
     * @return string
     */
    public static function encryptPhone($phone)
    {
        $head = 'QBJ:';
        return $head . base64_encode($phone);
    }

    /**
     * 电话号码解密
     * @param $phone
     * @return string
     */
    public static function decryptPhone($phone)
    {
        $phone = ltrim($phone, 'QBJ:');
        return base64_decode($phone);
    }

    static public function getRealHostIp()
    {
        $temp_ip = explode(':', $_SERVER['HTTP_HOST']);
        $http_host = isset($temp_ip[0]) ? $temp_ip[0] : $_SERVER['SERVER_ADDR'];
        return $http_host;
    }

}