<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006~2017 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------

namespace think;

\think\Loader::import('controller/Jump', TRAIT_PATH, EXT);

use app\admin\model\ManageSalesman;
use app\admin\model\SysAdminUser;
use app\admin\model\SysSetting;
use app\services\YlyPrint;
use app\wechat\controller\DishPublicAction;
use app\wechat\model\BillPayDetail;
use think\exception\ValidateException;

class Controller
{
    use \traits\controller\Jump;

    /**
     * @var \think\View 视图类实例
     */
    protected $view;
    /**
     * @var \think\Request Request实例
     */
    protected $request;
    // 验证失败是否抛出异常
    protected $failException = false;
    // 是否批量验证
    protected $batchValidate = false;

    /**
     * 前置操作方法列表
     * @var array $beforeActionList
     * @access protected
     */
    protected $beforeActionList = [];

    /**
     * 架构函数
     * @param Request $request Request对象
     * @access public
     */
    public function __construct(Request $request = null)
    {
        if (is_null($request)) {
            $request = Request::instance();
        }
        $this->view    = View::instance(Config::get('template'), Config::get('view_replace_str'));
        $this->request = $request;

        // 控制器初始化
        $this->_initialize();

        // 前置操作方法
        if ($this->beforeActionList) {
            foreach ($this->beforeActionList as $method => $options) {
                is_numeric($method) ?
                $this->beforeAction($options) :
                $this->beforeAction($method, $options);
            }
        }
    }

    // 初始化
    protected function _initialize()
    {
    }

    /**
     * 前置操作
     * @access protected
     * @param string $method  前置操作方法名
     * @param array  $options 调用参数 ['only'=>[...]] 或者['except'=>[...]]
     */
    protected function beforeAction($method, $options = [])
    {
        if (isset($options['only'])) {
            if (is_string($options['only'])) {
                $options['only'] = explode(',', $options['only']);
            }
            if (!in_array($this->request->action(), $options['only'])) {
                return;
            }
        } elseif (isset($options['except'])) {
            if (is_string($options['except'])) {
                $options['except'] = explode(',', $options['except']);
            }
            if (in_array($this->request->action(), $options['except'])) {
                return;
            }
        }

        call_user_func([$this, $method]);
    }

    /**
     * 加载模板输出
     * @param string $template
     * @param array $vars
     * @param array $replace
     * @param array $config
     * @return string
     * @throws Exception
     */
    protected function fetch($template = '', $vars = [], $replace = [], $config = [])
    {
        return $this->view->fetch($template, $vars, $replace, $config);
    }

    /**
     * 渲染内容输出
     * @access protected
     * @param string $content 模板内容
     * @param array  $vars    模板输出变量
     * @param array  $replace 替换内容
     * @param array  $config  模板参数
     * @return mixed
     */
    protected function display($content = '', $vars = [], $replace = [], $config = [])
    {
        return $this->view->display($content, $vars, $replace, $config);
    }

    /**
     * 模板变量赋值
     * @access protected
     * @param mixed $name  要显示的模板变量
     * @param mixed $value 变量的值
     * @return void
     */
    protected function assign($name, $value = '')
    {
        $this->view->assign($name, $value);
    }

    /**
     * 初始化模板引擎
     * @access protected
     * @param array|string $engine 引擎参数
     * @return void
     */
    protected function engine($engine)
    {
        $this->view->engine($engine);
    }

    /**
     * 设置验证失败后是否抛出异常
     * @access protected
     * @param bool $fail 是否抛出异常
     * @return $this
     */
    protected function validateFailException($fail = true)
    {
        $this->failException = $fail;
        return $this;
    }

    /**
     * 验证数据
     * @access protected
     * @param array        $data     数据
     * @param string|array $validate 验证器名或者验证规则数组
     * @param array        $message  提示信息
     * @param bool         $batch    是否批量验证
     * @param mixed        $callback 回调方法（闭包）
     * @return array|string|true
     * @throws ValidateException
     */
    protected function validate($data, $validate, $message = [], $batch = false, $callback = null)
    {
        if (is_array($validate)) {
            $v = Loader::validate();
            $v->rule($validate);
        } else {
            if (strpos($validate, '.')) {
                // 支持场景
                list($validate, $scene) = explode('.', $validate);
            }
            $v = Loader::validate($validate);
            if (!empty($scene)) {
                $v->scene($scene);
            }
        }
        // 是否批量验证
        if ($batch || $this->batchValidate) {
            $v->batch(true);
        }

        if (is_array($message)) {
            $v->message($message);
        }

        if ($callback && is_callable($callback)) {
            call_user_func_array($callback, [$v, &$data]);
        }

        if (!$v->check($data)) {
            if ($this->failException) {
                throw new ValidateException($v->getError());
            } else {
                return $v->getError();
            }
        } else {
            return true;
        }
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

    /*过滤表情*/
    public function filterEmoji($str)
    {
        $str = preg_replace_callback( '/./u',
            function (array $match) {
                return strlen($match[0]) >= 4 ? '' : $match[0];
            },
            $str);

        return $str;
    }

    /**
     * 获取登陆管理人员信息
     * @param $token
     * @return array
     * @throws exception\DbException
     */
    public function getLoginAdminId($token)
    {
        $id_res = SysAdminUser::get(['token' => $token],false)->toArray();
        return $id_res;
    }

    /*
     * 加密token
     * */
    public function jm_token($str)
    {
        return md5(sha1($str).time());
    }


    /**
     * 调用打印机打印订单(消费)
     * @param $pid
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function openTableToPrintYly($pid)
    {
        /*调用打印机 处理落单 on*/
        $YlyPrintObj = new YlyPrint();

        $printToken  = $YlyPrintObj->getToken();

        $message     = $printToken['message'];

        if ($printToken['result'] == false){
            //获取token失败
            return $this->com_return(false,$message);
        }

        $data          = $printToken['data'];

        $access_token  = $data['access_token'];

        $refresh_token = $data['refresh_token'];


        /*for ($i = 0; $i <count($pids); $i ++){
            $pid = $pids[$i]['pid'];

            $printRes = $YlyPrintObj->printDish($access_token,$pid);

            if ($printRes['error'] != "0"){
                //落单失败
                return $this->com_return(false,$printRes['error_description'],$pid);
            }
        }*/

        $printRes = $YlyPrintObj->printDish($access_token,$pid);

        Log::info("易连云打印结果 -----".var_export($printRes,true));

        return $this->com_return(true,config("params.SUCCESS"));
        /*调用打印机 处理落单 off*/
    }

    /**
     * 调用打印机打印订单(退单)
     * @param $pid
     * @param $detail_dis_info
     * @return array
     * @throws db\exception\DataNotFoundException
     * @throws db\exception\ModelNotFoundException
     * @throws exception\DbException
     */
    public function refundToPrintYly($pid,$detail_dis_info)
    {
        //获取菜品信息
        $dishPublicActionObj = new DishPublicAction();

        $orderInfo = $dishPublicActionObj->pidGetTableInfo($pid);

//        $tableName = $orderInfo['location_title']." - ".$orderInfo['area_title']." - ".$orderInfo['table_no']."号桌";
        $tableName = $orderInfo['table_no'];

        /*$billPayDetailModel = new BillPayDetail();

        $dis_info = [];

        foreach ($detail_id_arr as $detail_ids){

            $detail_id = $detail_ids['detail_id'];//退菜单id
            $quantity  = $detail_ids['quantity'];//退菜数量

            $detail_info = $billPayDetailModel
                ->where("id",$detail_id)
                ->field("dis_name,quantity,dis_type")
                ->find();

            $detail_info = json_decode(json_encode($detail_info),true);

            $detail_info['quantity'] = $quantity;

            $dis_type = $detail_info['dis_type'];

            $children = [];
            if ($dis_type){
                $children = $billPayDetailModel
                    ->where("parent_id",$detail_id)
                    ->field("dis_name,quantity,dis_type")
                    ->select();
                $children = json_decode(json_encode($children),true);
            }

            $detail_info['children'] = $children;

            $dis_info[] = $detail_info;
        }*/

        $params = [
            "table_name" => $tableName,
            "dis_info"   => $detail_dis_info,
            "pid"        => $pid
        ];

        $YlyPrintObj = new YlyPrint();

        $printToken = $YlyPrintObj->getToken();

        $message = $printToken['message'];

        if ($printToken['result'] == false){
            //获取token失败
            return $this->com_return(false,$message);
        }

        $data = $printToken['data'];

        $access_token = $data['access_token'];

        $refresh_token = $data['refresh_token'];

        $printRes = $YlyPrintObj->refundDish($access_token,$params);

        return $this->com_return(true,config("params.SUCCESS"));
    }

    /**
     * 根据营销手机号码获取营销人员信息
     * @param $phone
     * @return array|false|mixed|\PDOStatement|string|\think\Model
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function phoneGetSalesmanInfo($phone)
    {
        $salesModel = new ManageSalesman();

        $str1 = config("salesman.salesman_type")['0']['key'];
        $str2 = config("salesman.salesman_type")['1']['key'];
        $str3 = config("salesman.salesman_type")['3']['key'];
        $str4 = config("salesman.salesman_type")['4']['key'];

        $stype_key_str = $str1.",".$str2.",".$str3.",".$str4;

        $salesmanInfo = $salesModel
            ->alias("sm")
            ->join("mst_salesman_type mst","mst.stype_id = sm.stype_id")
            ->where("sm.phone",$phone)
            ->where("mst.stype_key","IN",$stype_key_str)
            ->field("mst.stype_name,mst.stype_key")
            ->field("sm.sid,sm.department_id,sm.stype_id,sm.sales_name,sm.statue,phone,sm.nickname,sm.avatar,sm.sex")
            ->find();

        $salesmanInfo = json_decode(json_encode($salesmanInfo),true);

        return $salesmanInfo;

    }
}
