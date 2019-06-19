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
use think\Controller;
use think\Db;

class User extends LiveAbstract
{

    /**
     * 直播页面
     * @return mixed
     */
    public function index(){

        $room_id = input('get.room_id',16);
        $room = Db::name('user_video')->where(['room_id'=>$room_id])->find();
        if(empty($room)){
            return $this->failResult('不存在的直播间',301);
        }
        $user_id = input('user_id');
        if (!empty($user_id)) {
            $user_id = 1;
        }
        $this->assign('user_id', $user_id);
        $this->assign('room_id',$room_id);

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