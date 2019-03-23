<?php

namespace app\mobile\controller;

use think\Db;
use think\Controller;

use app\common\model\WxMessage;

 
class Message extends Controller
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
      
        if( $event == 'SCAN'){
            $this->deal($openid,$eventkey);
        }


        if( $event == 'subscribe'){
            $shangji_user_id = substr($v['eventkey'], strlen('qrscene_'));
            $this->deal($openid,$shangji_user_id);
        }
        
        //$this->handle();

        echo $res;
    }


    //处理关系
    public function deal($xiaji_openid,$shangji_user_id){
        
        $this->write_log($xiaji_openid.'-------deal--------'.$shangji_user_id);

        //有用户绑定
        $xiaji = M('users')->where(['openid'=>$xiaji_openid])->find();
        if(!$xiaji){

            //注册用户
            $new_data = array(
                'openid' => $xiaji_openid
            );
            $xiaji_user_id = M('users')->add($new_data);

            //先注册 users 表

            $oauth_data = array(
                'openid' => $xiaji_openid,
                'user_id' => $xiaji_user_id
            );
            M('oauth_users')->add($new_data);

            $this->write_log($xiaji_user_id.'-------注册成功--------'.$shangji_user_id);
        }

       //注册好了，
       // 绑定关系
       share_deal_after($xiaji_user_id,$shangji_user_id);
       $this->write_log($xiaji_user_id.'-------绑定成功--------'.$shangji_user_id);

        $xiaji_user_id = $xiaji['user_id'];


    }


    public function write_log($content)
    {
        $content = "[".date('Y-m-d H:i:s')."]".$content."\r\n";
        $dir = rtrim(str_replace('\\','/',$_SERVER['DOCUMENT_ROOT']),'/').'/logs';
        if(!is_dir($dir)){
            mkdir($dir,0777,true);
        }
        if(!is_dir($dir)){
            mkdir($dir,0777,true);
        }
        $path = $dir.'/'.date('Ymd').'.txt';
        file_put_contents($path,$content,FILE_APPEND);
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