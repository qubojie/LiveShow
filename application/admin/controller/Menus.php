<?php
/**
 * Created by PhpStorm.
 * User: qubojie
 * Date: 2018/6/20
 * Time: 上午10:32
 */
namespace app\admin\controller;

use app\admin\model\SysAdminUser;
use app\admin\model\SysMenu;
use think\Controller;
use think\Exception;
use think\Request;

class Menus extends CommandAction
{

    /**
     * @api {POST} admin/menus 后台菜单列表获取
     * @apiGroup Admin
     * @apiVersion 1.0.0
     *
     * @apiSampleRequest http://localhost/admin/menus
     *
     * @apiSuccessExample {json} 成功返回:
     *     {
     *       "return_code": "SUCCESS" ,
     *       "return_msg" : "获取成功" ,
     *       "return_body": {
     *          "menu": {
     *              "999": {
     *                  "id": "999000000",
     *                  "title": "系统设置",
     *                  "title_img": "fa fa-cog",
     *                  "url": "",
     *                  "is_show_menu": 1,
     *                  "tip": null,
     *                  "is_show_role": 1,
     *                  "level2": {
     *                      "100": {
     *                          "id": "999100000",
     *                          "title": "管理员列表",
     *                          "title_img": "",
     *                          "url": "adminAdminIndex",
     *                          "is_show_menu": 1,
     *                          "tip": null,
     *                          "is_show_role": 1
     *                      },
     *                      "200": {
     *                          "id": "999200000",
     *                          "title": "角色管理",
     *                          "title_img": "",
     *                          "url": "adminAdminIndex",
     *                          "is_show_menu": 1,
     *                          "tip": null,
     *                          "is_show_role": 1
     *                      }
     *                  }
     *              }
     *          }
     *       }
     *     }
     */
    public function index(Request $request)
    {
        $common = new Common();

        if ($request->method() == "OPTIONS"){
            return $common->com_return(true,"options请求");
        }
        $Authorization = $this->request->header("authorization","");

        //查看当前用户角色
        $sysAdminUserModel = new SysAdminUser();

        $action_list_str = $sysAdminUserModel
            ->alias('sau')
            ->join('sys_role sr','sr.role_id = sau.role_id')
            ->where('token',$Authorization)
            ->field('sr.action_list')
            ->find()
            ->toArray();

        $action_list = $action_list_str['action_list'];
        $where = [];
        if ($action_list == 'all'){
            $where = [];
        }else{
            $where['id'] = array('IN',$action_list);//查询字段的值在此范围之内的做显示
        }

        $result = array('menu' => array());

        $menus = new SysMenu();
        $menus_all =  $menus
            ->where('is_show_menu','1')
            ->where($where)
            ->select();

        $menus_all = json_decode(json_encode($menus_all),true);

        for ($i=0;$i<count($menus_all);$i++){
            $id = $menus_all[$i]['id'];
            $parent = substr($id,0,3);
            $level  = substr($id,3,3);
            $last   = substr($id,-3);

            if ($level == "000"){
                $result['menu'][] = $menus_all[$i];
            }else{
                if ($last == 0) {
                    $level2[] = $menus_all[$i];
                }else{
                    $level3[] = $menus_all[$i];
                }
            }


        }
        if (isset($level2)){
            for ($m=0;$m<count($result["menu"]);$m++){
                $menu_id = $result["menu"][$m]["id"];
                $menu_id_p = substr($menu_id,0,3);
                for ($n=0;$n<count($level2);$n++){
                    $level2_id = $level2[$n]['id'];
                    $parent_level2 = substr($level2_id,0,3);
                    if ($menu_id_p == $parent_level2){
                        $result["menu"][$m]["children"][] = $level2[$n];
                    }
                }
            }
        }

        /*foreach ($menus_all as $key => $value){
            $id     = $value['id'];
            $parent = substr($id,0,3);
            $level  = substr($id,3,3);
            $last   = substr($id,-3);

            if ($level == '000'){
                //一级菜单
                $result['menu'][$parent] = $value;
            } else {
                if ($last == 0) {
                    //二级菜单
                    if (isset($result['menu'][$parent]['id'])) {
                        $result['menu'][$parent]['level2'][$level] = $value;
                    }
                } else {
                    //三级权限菜单
                    if (isset($result['menu'][$parent]['id']) && isset($result['menu'][$parent]['level2'][$level]['id'])) {
                        $result['menu'][$parent]['level2'][$level]['level3'][$last] = $value;
                    }
                }
            }
        }*/
        return $common->com_return(true,"获取成功",$result);
    }


    /**
     * 获取所有的设置列表
     * @param Request $request
     * @return array
     */
    public function lists(Request $request)
    {
        $common = new Common();

        $result = array();


        $menus = new SysMenu();
        $menus_all =  $menus
            ->where('is_show_menu','1')
            ->select();

        $menus_all = json_decode(json_encode($menus_all),true);


        for ($i=0;$i<count($menus_all);$i++){
            $id = $menus_all[$i]['id'];
            $parent = substr($id,0,3);
            $level  = substr($id,3,3);
            $last   = substr($id,-3);

            if ($level == "000"){
                $result[] = $menus_all[$i];
            }else{
                if ($last == 0) {
                    $level2[] = $menus_all[$i];
                }else{
                    $level3[] = $menus_all[$i];
                }
            }
        }
        if (isset($level2)){
            for ($m=0;$m<count($result);$m++){
                $menu_id = $result[$m]["id"];
                $menu_id_p = substr($menu_id,0,3);
                for ($n=0;$n<count($level2);$n++){
                    $level2_id = $level2[$n]['id'];
                    $parent_level2 = substr($level2_id,0,3);
                    if ($menu_id_p == $parent_level2){
                        $result[$m]["children"][] = $level2[$n];
                    }
                }
            }
        }

        return $common->com_return(true,"获取成功",$result);
    }
}