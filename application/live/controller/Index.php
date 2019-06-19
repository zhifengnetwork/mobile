<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/6/14 0014
 * Time: 11:31
 */

namespace app\live\controller;

use app\common\model\Users as UserModel;
use app\live\service\AccessToken;
use app\live\service\RtmTokenBuilder;
use app\mobile\controller\MobileBase;
use think\Db;

class Index extends Base
{

    /**
     * 直播页面
     * @return mixed
     */
    public function index(){
        $user = $this->user;
        $user_id = $user->user_id;
        $room_id = input('get.room_id',16);
        $room = Db::name('user_video')->where(['room_id'=>$room_id])->find();
        if(empty($room)){
            return $this->failResult('不存在的直播间',301);
        }
        $this->assign('room_id',$room_id);
        $this->assign('user_id', $user_id.time());
        return $this->fetch();
    }

    public function member()
    {
        $user_id = input('user_id');
        if (!empty($user_id)) {
            $user_id = 1;
        }
        $this->assign('user_id', $user_id);
        return $this->fetch('member');
    }

    /**
     * 直播列表页
     * @return mixed
     */
    public function videoList(){
        $user = $this->user;
        $user_id = $user->user_id;

        return $this->fetch();
    }

    /**
     * 设置直播
     * @return mixed
     */
    public function set(){
        return $this->fetch();
    }

    /**
     * 直播结束
     * @return mixed
     */
    public function end(){
        $user = $this->user;
        $user_id = $user->user_id;
        $room_id = input('get.room_id',0);
        $room = Db::name('user_video')->where(['user_id'=>$user_id,'room_id'=>$room_id])->find();
        if(empty($room)){
            return $this->failResult('不存在的直播间',301);
        }
        $user_identity = Db::name('user_verify_identity_info')->where(['user_id'=>$user_id])->order('id desc')->find();
        $user_identity['pic_head'] = $this->url.$user_identity['pic_head'];
        $user_identity['pic_fengmian'] = $this->url.$user_identity['pic_fengmian'];
        $this->assign('user',$user_identity);
        $this->assign('room',$room);
        return $this->fetch();
    }

    public function RtmTokenBuilderSample(){

        $appID = "4c2954a8e1524f5ea15dc5ae14232042";
        $appCertificate = "1580a6da5ed94447840d870a07e1c6e2";
        $account = "1000";
        $expiredTs = 0;
        $builder = new RtmTokenBuilder($appID, $appCertificate, $account);
        $builder->setPrivilege(AccessToken::Privileges["kRtmLogin"], $expiredTs);
        echo  $builder->buildToken();
        exit;
    }


}