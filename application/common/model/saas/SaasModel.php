<?php

namespace app\common\model\saas;
use think\Model;
class SaasModel extends Model {
// 设置当前模型的数据库连接
    protected $connection = [
        // 数据库类型
        'type'        => 'mysql',
        // 服务器地址
        'hostname'    => '120.79.57.160',
        // 数据库名
        'database'    => 'saas_a',
        // 数据库用户名
        'username'    => 'root',
        // 数据库密码
        'password'    => 'tpshop.demo@99soubao.COM',
        // 数据库编码默认采用utf8
        'charset'     => 'utf8',
        // 数据库表前缀
        'prefix'      => 'tp_',
        // 数据库调试模式
        'debug'       => false,
    ];
//    protected $connection = [
//        // 数据库类型
//        'type'        => 'mysql',
//        // 服务器地址
//        'hostname'    => '127.0.0.1',
//        // 数据库名
//        'database'    => 'saas',
//        // 数据库用户名
//        'username'    => 'demo',
//        // 数据库密码
//        'password'    => 'tpshop_demo',
//        // 数据库编码默认采用utf8
//        'charset'     => 'utf8',
//        // 数据库表前缀
//        'prefix'      => 'tp_',
//        // 数据库调试模式
//        'debug'       => false,
//    ];

}
