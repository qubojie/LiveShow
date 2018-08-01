<?php
/**
 * Created by PhpStorm.
 * User: qubojie
 * Date: 2018/6/26
 * Time: 下午5:17
 */
namespace app\admin\model;

use app\admin\controller\Common;
use think\Db;
use think\Exception;
use think\Model;
use think\Request;

class MstCardVip extends Model
{

    /**
     * 关联到模型的数据表
     *
     * @var string
     */
    protected $table = 'EL_MST_CARD_VIP';

    protected $primaryKey = 'card_id';

    public $timestamps = false;

    public $column = [
        'card_id',
        'card_type',
        'card_name',
        'card_level',
        'card_image',
        'card_no_prefix',
        'card_desc',
        'card_equities',
        'card_amount',
        'card_deposit',
        'card_point',
        'card_cash_gift',
        'card_job_cash_gif',
        'card_job_commission',
        'salesman',
        'sort',
        'created_at',
        'updated_at',
    ];
}