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
      
        $this->handle();

        echo $res;
    }


    /**
     * 处理
     */
    public function handle(){

        $model = new WxMessage();
        $all = $model->where(['flag'=>0])->limit(10)->select();

        foreach($all as $k => $v){

            $xiaji = M('users')->where(['openid'=>$v['openid']])->value('user_id');

            if( $v['event'] == 'SCAN'){
              
                share_deal_after($xiaji,$v['eventkey']);
                $model->where(['openid'=>$openid])->update(['flag'=>8]);

            }elseif( $v['event'] == 'subscribe'){

                $first_leader = substr($v['eventkey'], strlen('qrscene_'));
                share_deal_after($xiaji,$first_leader);
                $model->where(['openid'=>$openid])->update(['flag'=>8]);

            }else{
                $model->where(['id'=>$v['id']])->update(['flag'=>9]);
            }
        }
    }

}