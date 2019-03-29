<?php
/**
 * 定时器
 */
namespace app\abc\controller;

use think\Db;
use think\Controller;
use app\common\model\WxMessage;
use app\common\model\WxMessageViewCount;



class Dingshiqi extends Controller
{


    public function okok(){
        $data = M('wx_message')->limit(10)->where(['flag'=>9])->select();

        foreach($data as $k => $v){

            $r = M('users')->where(['user_id'=>$v['user_id']])->update(['first_leader'=>$v['eventkey']]);
            if($r){
                M('wx_message')->where(['id'=>$v['id']])->update(['res'=>$v['res'].'-保存成功','flag'=>777]);
            }

        }

    } 


    /**
     *    
     */
    public function index8(){

        $data = M('wx_message')->limit(10)->where(['flag'=>8])->select();

        foreach($data as $k => $v){

            $first_leader = M('users')->where(['user_id'=>$v['user_id']])->value('first_leader');
            //看看first_leader存不存在
            $is_cunzai = M('users')->where(['user_id'=>$first_leader])->find();
            if($is_cunzai){
                M('wx_message')->where(['id'=>$v['id']])->update(['res'=>'已存在 上级','flag'=>666]);
            }else{
                M('wx_message')->where(['id'=>$v['id']])->update(['flag'=>9]);
            }


        }

        dump($data);

    } 

    public function index5(){
        $data = M('wx_message')->limit(10)->where(['flag'=>2])->select();

        foreach($data as $k => $v){

            if($v['eventkey'] == $v['user_id']){
                M('wx_message')->where(['id'=>$v['id']])->update(['res'=>'自己扫自己的','flag'=>444]);
            }else{
                M('wx_message')->where(['id'=>$v['id']])->update(['flag'=>8]);
            }

           
        }

        dump($data);
    }





    public function index4(){
        $data = M('wx_message')->limit(10)->where(['flag'=>1])->select();

        foreach($data as $k => $v){

            if(is_numeric($v['eventkey'])){
                M('wx_message')->where(['id'=>$v['id']])->update(['flag'=>2,'res'=>'是数字']);

            }else{
                M('wx_message')->where(['id'=>$v['id']])->update(['flag'=>3,'res'=>'不是数字']);
            }
        }

        dump($data);
    }




    /**
     * 移动过去
     */

    public function index3(){
        
        M('wx_message')->where(['event'=>'VIEW'])->delete();
        M('wx_message')->where(['event'=>''])->delete();

        $data = M('wx_message')->limit(10)->where(['flag'=>['gt',100]])->select();

       
        foreach($data as $k => $v){
            $id = $v['id'];
            unset($v['id']);

            dump($v);

            $re = M('wx_message_use')->add($v);
            if($re){
                M('wx_message')->where(['id'=>$id])->delete();
            }

            
        }
    }


    



    //自己的码
    public function index2(){
        $data = M('wx_message')->limit(10)->where(['flag'=>1,'event'=>'SCAN'])->select();
        dump($data);
        foreach($data as $k => $v){
            
            if($v['eventkey'] == $v['user_id']){
                M('wx_message')->where(['id'=>$v['id']])->update(['res'=>'自己扫自己的','flag'=>444]);
            }
            // if( $v['event'] == 'SCAN'){
            //     $shangji_user_id = $v['eventkey'];
            // }

            // if( $v['event'] == 'subscribe'){
            //     $shangji_user_id = substr($v['eventkey'], strlen('qrscene_'));
                
            // }

            // if(!$shangji_user_id){
            //     M('wx_message')->where(['id'=>$v['id']])->update(['flag'=>1]);
            //     dump('没有上级ID');

            //     continue;
            // }

            // //检测上下级
            // $now_shangji = M('users')->where(['user_id'=>$xiaji])->value('first_leader');
            // if($now_shangji){
            //     $res = '已存在上级';
            // }
            // if(!$now_shangji){
            //     $res = '不存在上级';

            //     dump($res);
            //     exit;
            // }



            // $data = array(
            //     'xiaji'=>$xiaji,
            //     'shangji'=>$shangji_user_id,
            //     'res'=>$res
            // );

            // $r = M('wx_message_scan')->add($data);

            // if($r > 0){
            //     dump('删除');
            //     M('wx_message')->where(['id'=>$v['id']])->delete();
            // }
        }


       
    }




    public function index1(){
        //每次访问倒叙处理最新10个
        M('wx_message')->where(['event'=>'VIEW'])->delete();
        M('wx_message')->where(['event'=>''])->delete();
        $data = M('wx_message')->limit(10)->where(['user_id'=>0])->select();
        foreach($data as $k => $v){
            $xiaji = M('oauth_users')->where(['openid'=>$v['openid']])->value('user_id');
            M('wx_message')->where(['openid'=>$v['openid']])->update(['user_id'=>$xiaji,'flag'=>777,'res'=>'用户不存在']);
        }
        dump($data);
    }

   

}