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

        $fiels = '`user_id`,`agentid`';

        $sql = "select $fiels from `hs_mc_members` where uid > 0 order by uid asc limit 700";

        $res = Db::connect($dbconf1)->query($sql);
        dump($res);exit;
        if($res){

            foreach($res as $v){
                $v['nickname'] = addslashes($v['nickname']);
                $insql = "insert into `tp_users_copy` (`user_id`,`nickname`,`realname`,`mobile`,`head_pic`) values ('$v[user_id]','$v[nickname]','$v[realname]','$v[mobile]','$v[avatar]')";
               
                if($insql) Db::connect($dbconf2)->execute($insql);
                $insql = '';
            }
            // dump($insql);exit;
            echo $res[count($res) - 1]['user_id'];
        }


        


        exit;
    }



}