<?php
/**
 * Created by PhpStorm.
 * User: qubojie
 * Date: 2018/6/20
 * Time: 上午11:58
 */
namespace app\admin\controller;

use app\admin\model\SysAdminUser;
use app\admin\model\SysLog;
use app\admin\model\SysMenu;
use app\admin\model\SysRole;
use think\Controller;
use think\Db;
use think\Exception;
use think\Request;
use think\Validate;

class Roles extends CommandAction
//class Roles extends Controller
{
    /**
     * @api {POST} roles/index 角色列表
     * @apiGroup Admin
     * @apiVersion 1.0.0
     *
     * @apiParam {header} Authorization token
     *
     * @apiSampleRequest http://localhost/admin/roles/index
     *
     * @apiErrorExample {json} 错误返回:
     *     {
     *       "result"  : false ,
     *       "message" : "【删除失败】或【权限不足】或【未找到相关管理员信息】" ,
     *       "data"    : null
     *     }
     * @apiSuccessExample {json} 成功返回:
     *     {
     *       "result"   : true ,
     *       "message"  : "获取成功" ,
     *       "data"     : {
     *          "total": 3,
     *          "per_page": "20",
     *          "current_page": 1,
     *          "data": [
     *              {
     *                  "role_id": 1,
     *                  "role_name": "超级管理员",
     *                  "action_list": "all",
     *                  "role_describe": "as",
     *                  "is_sys": 1
     *              },
     *              {
     *                  "role_id": 2,
     *                  "role_name": "测试管理员1",
     *                  "action_list": "999000000,999100000,999100100,999100200,999100300,999200000,999200100",
     *                  "role_describe": "描述测试",
     *                  "is_sys": 0
     *              }
     *          ]
     *
     *       }
     *     }
     */
    public function index(Request $request)
    {
        $common = new Common();

        $result = array('roles' => array());
        $result['filter']['orderBy'] = $request->has('orderBy') ? $request->input('orderBy') : 'role_id';
        $result['filter']['sort'] = $request->has('sort') ? $request->input('sort') : 'asc';

        $query = new SysRole();

        $count = $query->count();

        $pageNum = ceil($count/config('PAGESIZE')); //总页数

        $nowPage = $request->has("nowPage") ? $request->param("nowPage") : "1"; //当前页

        if ($nowPage <= $pageNum){
            $config = [
                "page" => $nowPage
            ];

            $roles = $query
                ->order($result['filter']['orderBy'], $result['filter']['sort'])
                ->paginate(config('PAGESIZE'),'',$config);
            $result = $roles;

            /*$menus = new SysMenu();
            $menus_all =  $menus
                ->where('is_show_role','1')
                ->order("id","asc")
                ->field("id,title")
                ->select();
            $menus_all = json_decode(json_encode($menus_all),true);

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
        }else{
            return $common->com_return(true,"暂无更多",$result);
        }

    }

    /**
     * @api {POST} roles/add 角色添加
     * @apiGroup Admin
     * @apiVersion 1.0.0
     *
     * @apiParam {header} Authorization token
     * @apiParam {String} role_name 角色名
     * @apiParam {String} role_describe 角色描述
     * @apiParam {String} action (可选参数)所选权限id,以逗号作为分隔符拼接
     *
     * @apiSampleRequest http://localhost/admin/roles/add
     *
     * @apiErrorExample {json} 错误返回:
     *     {
     *       "result"  : false ,
     *       "message" : "【角色添加失败】或【角色名已存在】或【角色描述不能为空】" ,
     *       "data"    : null
     *     }
     * @apiSuccessExample {json} 成功返回:
     *     {
     *       "result"   : true ,
     *       "message"  : "角色添加成功" ,
     *       "data"     : null
     *     }
     */
    public function add(Request $request)
    {
        $common = new Common();

        $role_name       = $request->param("role_name","");
        $role_describe   = $request->param("role_describe","");
        $action_list     = $request->param("action_list","");

        try{
            $rule = [
                "role_name|角色名"       => "require|max:60|unique:sys_role",
                "role_describe|角色描述" => "require"
            ];

            $request_res = [
                "role_name"     => $role_name,
                "role_describe" => $role_describe
            ];

            $validate = new Validate($rule);

            if (!$validate->check($request_res)){
                return $common->com_return(false,$validate->getError());
            }

            //写入数据,返回id
            $insert_data = [
                "role_name" => $role_name,
                "role_describe" => $role_describe,
                "action_list" => $action_list
            ];
            Db::startTrans();
            $sysRole = new SysRole();
            $id = $sysRole->insertGetId($insert_data);
            if (!empty($id)){
                Db::commit();

                //获取当前登录管理员
                $action_user = $this->getLoginAdminId($request->header('Authorization'))['user_name'];

                $time = time();
                //添加至系统操作日志
                $this->addSysLog("$time","$action_user","添加角色 -> $role_name",$request->ip());

                return $common->com_return(true,"角色添加成功");

            }else{
                Db::rollback();
                return $common->com_return(false,"角色添加失败");
            }
        }catch (Exception $e){
            return $common->com_return(false,$e->getMessage());
        }
    }

    /**
     * 角色编辑
     * @param Request $request
     * @return array
     */
    public function edit(Request $request)
    {
        $common = new Common();

        $role_id        = $request->param("role_id","");
        $role_name      = $request->param("role_name","");
        $role_describe  = $request->param("role_describe","");
        $action_list    = $request->param("action_list","");

        $rule = [
            "role_id|角色id"         => "require",
            "role_name|角色名"       => "require|max:60|unique:sys_role",
            "role_describe|角色描述" => "require"
        ];

        $request_res = [
            "role_id"       => $role_id,
            "role_name"     => $role_name,
            "role_describe" => $role_describe
        ];

        $validate = new Validate($rule);

        if (!$validate->check($request_res)){
            return $common->com_return(false,$validate->getError());
        }

        //更新数据,返回
        $update_data = [
            "role_name"     => $role_name,
            "role_describe" => $role_describe,
            "action_list"   => $action_list
        ];

        //查看老数据
        $sysRoleModel = new SysRole();
        $old_info     = $sysRoleModel->where("role_id",$role_id)
            ->field("role_name,role_describe,action_list")
            ->find()
            ->toArray();

        Db::startTrans();
        try{
            $is_ok = $sysRoleModel
                ->where("role_id",$role_id)
                ->update($update_data);
            if ($is_ok !== false){
                Db::commit();
                return $common->com_return(true,'变更成功');
            }else{
                return $common->com_return(false,'变更失败');
            }

        }catch (Exception $e){
            Db::rollback();
            return $common->com_return(false,$e->getMessage());
        }
    }


    /**
     * 角色删除
     * @param Request $request
     * @return array
     */
    public function delete(Request $request)
    {
        $common = new Common();

        $role_id  =$request->param("role_id","");

        if (!empty($role_id)){
            $sysAdminUserModel = new SysAdminUser();
            //查看当前角色是否有管理员
            $role_id_exist = $sysAdminUserModel
                ->where('role_id',$role_id)
                ->count();
            if (!$role_id_exist){
                //可以删除
                $sysRoleMode = new SysRole();
                Db::startTrans();
                try{
                    $is_delete = $sysRoleMode
                        ->where("role_id",$role_id)
                        ->delete();
                    if ($is_delete){
                        Db::commit();

                        //获取当前登录管理员
                        $action_user = $this->getLoginAdminId($request->header('Authorization'))['user_name'];

                        $time = time();
                        //添加至
                        $this->addSysLog("$time","$action_user","角色删除 -> $role_id",$request->ip());

                        return $common->com_return(true,"删除成功");
                    }
                }catch (Exception $e){
                    Db::rollback();
                    return $common->com_return(false,$e->getMessage());
                }
            }else{
                return $common->com_return(false,'当前角色下存在管理员,不可删除');
            }
        }else{
            return $common->com_return(false,'角色id不能为空');
        }
    }
}