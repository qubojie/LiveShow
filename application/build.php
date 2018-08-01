<?php
/**
 * Created by PhpStorm.
 * User: qubojie
 * Date: 2018/7/18
 * Time: 上午10:02
 */

return [
    // 定义demo模块的自动生成 （按照实际定义的文件名生成）
    'demo'     => [
        '__file__'   => ['common.php'],
        '__dir__'    => ['behavior', 'controller', 'model', 'view'],
        'controller' => ['Index', 'Test', 'UserType'],
        'model'      => ['User', 'UserType'],
        'view'       => ['index/index'],
    ],
];