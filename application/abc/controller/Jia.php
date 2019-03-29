<?php
namespace app\abc\controller;

use think\Db;
use think\Controller;

class Jia extends Controller
{
    
    public function index(){

        exit('--已操作---');

       $data = M('order_goods_399')->where(['flag'=>2])->limit(10)->select();

       foreach($data as $k => $v){

            if($v['flag'] == 2){

                // $user_id = M('oauth_users')->where(['openid'=>$v['openid']])->value('user_id');
                $nickname = M('users')->where(['openid'=>$v['openid']])->value('nickname');

                // M('order_goods_399')->where(['openid'=>$v['openid']])->update(['user_id'=>$user_id,'flag'=>1]);
               
                // $res = M('users')->where(['user_id'=>$v['user_id']])->setInc('agent_free_num');
                // $r = M('users')->where(['user_id'=>$v['user_id']])->update(['super_nsign'=>1]);

                // if($res){
                //     $jieguo = '已增加次数';
                // }

                // if($r){
                //     $jieguo =  $jieguo.'已变成可签到';
                // }

                M('order_goods_399')->where(['openid'=>$v['openid']])->update(['flag'=>3,'nickname'=>$nickname]);

            }
       }
    
       dump($data);


    }

}