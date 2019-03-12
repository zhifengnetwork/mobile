<?php

namespace app\home\controller;

use think\Db;

class Aatest {


    public function index(){

        $dbconf1 = [
            // 数据库类型
            'type'        => 'mysql',
            // 数据库连接DSN配置
            'dsn'         => '',
            // 服务器地址
            'hostname'    => 'rm-wz9msz657o82h58g83o.mysql.rds.aliyuncs.com',
            // 数据库名
            'database'    => 'zxx',
            // 数据库用户名
            'username'    => 'zxx',
            // 数据库密码
            'password'    => 'Zxx123456',
            // 数据库连接端口
            'hostport'    => '3306',
            // 数据库连接参数
            'params'      => [],
            // 数据库编码默认采用utf8
            'charset'     => 'utf8',
            // 数据库表前缀
            'prefix'      => '',
        ];

        $dbconf2 = [
            'type'        => 'mysql',
             // 服务器地址
             'hostname'    => 'rm-wz9msz657o82h58g83o.mysql.rds.aliyuncs.com',
             // 数据库名
             'database'    => 'mobileshop',
             // 数据库用户名
             'username'    => 'mobileshop',
             // 数据库密码
             'password'    => 'zhifeng123123@',
        ];

        // $fiels = "`uid` as `user_id`,`openid`,`nickname`,`realname`,`mobile`,`weixin`,`isagent`,`avatar` as `head_pic`,`province`,`city`,`alipay`";

        $fiels = '`uid` as `user_id`,`openid`,`isagent`,`level`';

        $sql = "select $fiels from `hs_sz_yi_member` where uid > 0 order by uid asc limit 100";

        $res = Db::connect($dbconf1)->query($sql);
        if($res){

            foreach($res as $v){
                $sql1 = "select `uid` from hs_sz_yi_member where openid='$v[openid]'";
                $insql = "select mc.openid,sum(mc.teams)+sum(mc.total) total from hs_sz_yi_bonusorder mc where mc.openid='$v[openid]'";
                
                $res2 = Db::connect($dbconf1)->query($insql);
                $res1 = Db::connect($dbconf1)->query($sql1);
                $result1 = [];
                $result = [];
                array_map(function ($value) use (&$result) {
                    $result = array_merge($result, array_values($value));
                }, $res2);
                array_map(function ($value) use (&$result1) {
                    $result1 = array_merge($result1, array_values($value));
                }, $res1);
                $insql1 = "insert into `tp_agent_performance` (`user_id`,`agent_per`) values ('$result1[0]','$result[1]')";
                if(empty($result[1])){
                    continue;
                }
                Db::connect($dbconf2)->execute($insql1);
                $insql = '';
            }
            echo $res[count($res) - 1]['user_id'];
        }
        exit;
    }



}