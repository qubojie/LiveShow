<?php
/**
 * Created by PhpStorm.
 * User: qubojie
 * Date: 2018/7/18
 * Time: 上午10:02
 */

return [
    // 定义前台管理模块的自动生成 （按照实际定义的文件名生成）
    'Reception'     => [
        '__file__'   => ['common.php','config.php'],
        '__dir__'    => ['behavior', 'controller', 'model'],
        'controller' => ['DiningRoom', 'Reserve', 'CommonAction', 'Test'],
        'model'      => ['ManageSalesman', 'MstTable'],
    ],
];