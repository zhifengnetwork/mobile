<?php
/**
 * 测试
 */
namespace app\test\controller;


use think\Db;
use think\Controller;


class Message extends Controller
{

    public function index(){

        M('wx_message')->where(['event'=>''])->delete();
        M('wx_message')->where(['event'=>'unsubscribe'])->delete();
        M('wx_message')->where(['event'=>'VIEW'])->delete();
       
        
        $data = M('wx_message')->limit(100)->select();



        // foreach($data as $k => $v){



        //     if($v['event'] == 'VIEW' ){
        //         dump($v['id']);

        //         M('wx_message')->where(['id'=>$v['id']])->delete();

        //     }

        // }


        dump($data);
        

    }

}