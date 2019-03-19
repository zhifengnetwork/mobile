<?php

namespace app\mobile\controller;

use think\Db;
use app\common\model\WxMessage;
 
class Message extends MobileBase
{
    /**
     * 消息处理
     */
    public function index()
    {
        // SITE_URL.'/mobile/message/index?eventkey='.$re['EventKey'].'&openid='.$re['FromUserName'].'&event='.$re['Event'];
        $eventkey = I('eventkey');
        $openid = I('openid');
        $event = I('event');

        $model = new WxMessage();

        $model->eventkey = $eventkey;
        $model->openid = $openid;
        $model->event = $event;
        $res = $model->save();
        echo $res;

        if( $event == 'SCAN'){
            $xiaji = M('users')->where(['openid'=>$openid])->value('user_id');
            share_deal_after($xiaji,$eventkey);
            $model->where(['openid'=>$openid])->update(['flag'=>8]);
        }
    }


    /**
     * 处理
     */
    public function handle(){

        $model = new WxMessage();
        $all = $model->where(['flag'=>0])->select();


        foreach($all as $k => $v){

            if( $v['event'] == 'SCAN'){
                $xiaji = M('users')->where(['openid'=>$v['openid']])->value('user_id');
                share_deal_after($xiaji,$v['eventkey']);
                $model->where(['openid'=>$openid])->update(['flag'=>8]);
            }else{
                $model->where(['id'=>$v['id']])->update(['flag'=>9]);
            }
        }

       
        
    }

}