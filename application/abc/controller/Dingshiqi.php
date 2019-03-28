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
    public function index(){


      
        //每次访问倒叙处理最新10个
        M('wx_message')->where(['event'=>'VIEW'])->delete();
        M('wx_message')->where(['event'=>''])->delete();

        $data = M('wx_message')->limit(10)->where(['flag'=>0])->select();
        
        foreach($data as $k => $v){
            $xiaji = M('users')->where(['openid'=>$v['openid']])->value('user_id');
            
            if( $v['event'] == 'SCAN'){
                $shangji_user_id = $v['eventkey'];
            }

            if( $v['event'] == 'subscribe'){
                $shangji_user_id = substr($v['eventkey'], strlen('qrscene_'));
                
            }

            if(!$shangji_user_id){
                M('wx_message')->where(['id'=>$v['id']])->update(['flag'=>1]);
                dump('没有上级ID');

                continue;
            }

            //检测上下级
            $now_shangji = M('users')->where(['user_id'=>$xiaji])->value('first_leader');
            if($now_shangji){
                $res = '已存在上级';
            }
            if(!$now_shangji){
                $res = '不存在上级';

                dump($res);
                exit;
            }



            $data = array(
                'xiaji'=>$xiaji,
                'shangji'=>$shangji_user_id,
                'res'=>$res
            );

            $r = M('wx_message_scan')->add($data);

            if($r > 0){
                dump('删除');
                M('wx_message')->where(['id'=>$v['id']])->delete();
            }
        }


       
    }

   

}