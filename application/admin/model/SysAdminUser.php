<?php
/**
 * Created by PhpStorm.
 * User: qubojie
 * Date: 2018/6/19
 * Time: 下午6:31
 */
namespace app\admin\model;

use think\Model;

class SysAdminUser extends Model
{
    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'el_sys_admin_user';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['user_name', 'password'];

    /**
     * The attributes excluded from the model's JSON form.
     *
     * @var array
     */
    protected $hidden = ['password', 'remember_token'];
}