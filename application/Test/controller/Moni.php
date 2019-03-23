<?php
/**
 * 测试
 */
namespace app\test\controller;


use think\Db;
use think\Controller;


class Moni extends Controller
{


    public function monidenglu(){

        $user_id =  input('user_id');
        if(!$user_id){
            exit("user_id不能为空");
        }

        $user = M('users')->where(['user_id'=>$user_id])->find();
        if(!$user){
            exit("user为空");
        }

        session('user',$$user);
        setcookie('user_id',$user['user_id'],null,'/');
        setcookie('is_distribut',$user['is_distribut'],null,'/');
        setcookie('uname',$user['nickname'],null,'/');
        session('openid',$user['openid']);

        $this->redirect('mobile/user/index');

    }
}

