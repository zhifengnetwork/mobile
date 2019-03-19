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
    }

}