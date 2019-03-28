<?php
/**
 * 测试
 */
namespace app\abc\controller;


use think\Db;
use think\Controller;


class Index extends Controller
{

    public function index(){
        $url = "http://thirdwx.qlogo.cn/mmopen/vi_32/1ZeNR1gSiczejQL7picQwpFHxQJbmqQPuyvnMBmEphISvBlPmHeC1wsEPuy9KRMtiacbrje3kH9ic1Cvib0hkFpp4vw/132";

        $end = substr($url,-3);
        if($end == '132'){
            $url = substr($url,0,count($url)-4).'0';
            dump($url);
        }
    }



    /**
     * 补
     */
    public function bu(){
        $oa = M('users')->field('user_id,openid,count(*) as count')->group('openid')->having('count(*)>1')->limit(1)->order('user_id desc')->select();

        dump($oa);

        //$res = M('users')->field('user_id,openid')->order('user_id desc')->where('openid', '')->limit(10)->select();

        // dump($res);
        // foreach($res as $k => $v){
        //     $openid = M('oauth_users')->where(['user_id'=>$v['user_id']])->value('openid');
        
        //     M('users')->where(['user_id'=>$v['user_id']])->update(['openid'=>$openid]);
        // } 
    }

    
    public function aa()
    {
        exit('结束');
        // select username,count(*) as count from hk_test group by username having count>1;

        dump('重复');
        // ->limit(2)

        // $con['user_id'] = array('gt',50000);
        // ->where($con)
        $res = M('oauth_users')->field('user_id,openid,count(*) as count')->group('openid')->having('count(*)>1')->limit(1)->order('user_id desc')->select();
        // dump($res);die;
        $liang_openid = M('oauth_users')->where(['openid'=>$res[0]['openid']])->field('user_id,openid')->select();
        dump($liang_openid);

        $duoge = M('users')->where(['openid'=>$res[0]['openid']])->select();

        dump('遍历用户信息');

        foreach($liang_openid as $k=>$val){
            
            $users = M('users')->where(['user_id'=>$val['user_id']])->field('user_id,openid,agent_user,user_money,is_distribut,is_agent,first_leader')->find();
            $money_per = M('agent_performance')->where(['user_id'=>$val['user_id']])->field('ind_per,agent_per')->find();
            $users['ind_per'] = $money_per['ind_per'];
            $users['agent_per'] = $money_per['agent_per'];
            dump($users);
        
            $count_order = M('order')->where(['user_id'=>$val['user_id']])->count();
            dump("订单数：".$count_order);
            $id = $val["user_id"];
            echo "<a href='/test/index/dele?user_id=$id'>删除这个".$val['user_id']."</a>";
        }

    }


    public function dele(){

        exit;

        $user_id = $_GET['user_id'];

        // echo $user_id;die;

        $rs = M('oauth_users')->where(['user_id'=>$user_id])->delete();
        $r = M('users')->where(['user_id'=>$user_id])->delete();

        dump("删除成功：".$rs);
        dump("删除成功：".$r);

        $this->redirect('index');
    }

}
