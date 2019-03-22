<?php
/**
 * 测试
 */
namespace app\test\controller;


use think\Db;
use think\Controller;


class Index extends Controller
{

    
    public function index()
    {
       

        // select username,count(*) as count from hk_test group by username having count>1;

        dump('重复');

        $res = M('oauth_users')->field('user_id,openid,count(*) as count')->group('openid')->having('count(*)>1')->limit(1)->select();

        dump($res);

        $all = M('oauth_users')->where(['openid'=>$res[0]['openid']])->select();



        dump($all);

    }

}
