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
        // ->limit(2)
        $res = M('oauth_users')->field('user_id,openid,count(*) as count')->group('openid')->having('count(*)>1')->select();

        dump($res);

        dump('遍历用户信息');
        foreach($res as $k=>$oauth_user){
            $all = M('oauth_users')->where(['openid'=>$oauth_user['openid']])->select();
            
            foreach ($all as $key=>$val){
                $users = M('users')->where(['user_id'=>$val['user_id']])->field('user_id,openid,agent_user,user_money,is_distribut,is_agent,first_leader')->find();
                $money_per = M('agent_performance')->where(['user_id'=>$val['user_id']])->field('ind_per,agent_per')->find();
                $users['ind_per'] = $money_per['ind_per'];
                $users['agent_per'] = $money_per['agent_per'];
                dump($users);
            }
        }
        




    }

}
