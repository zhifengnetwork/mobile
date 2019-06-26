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

class User extends Base
{

    /**
     * 用户进入直播页面
     * @return mixed
     */
    public function index()
    {
        $userId = $this->user->user_id;
        $room_id = input('get.room_id', 1);
//        $room = Db::name('user_video')->where(['room_id' => $room_id, 'status' => 1])->find();
//        if (empty($room)) {
//            return $this->failResult('不存在的直播间', 301);
//        }
        $this->assign('user_id', time() . $this->user->user_id);
        $this->assign('user_name',$this->user->nickname);
        $this->assign('head_pic',$this->user->head_pic);
        $this->assign('level',isset($this->user->agentlevel)&&!empty($this->user->agentlevel) ? $this->user->agentlevel : 0);
        $this->assign('room_id', $room_id);
        return $this->fetch();
    }

    /**
     * 用户发红包
     * @return \think\response\Json
     * @throws \think\Exception
     */
    public function sendGift(){
        $room_id = input('post.room_id', 0);
        $money = input('post.money',0);
        if(empty($room_id) || empty($money)){
            return $this->failResult('参数有误',301);
        }
        $money = bcadd($money,'0.00',2);
        $userId = $this->user->user_id;
        $user = Db::name('users')->where(['user_id'=>$userId])->find();
        $koujian = bcsub($user['user_money'],$money,2);
        if($koujian < 0){
            return $this->failResult('余额不足',301);
        }
        //事务处理
        Db::startTrans();
        //剩余的用户钱
        $user_money = bcsub($user['user_money'],$money,2);
        //扣减用户余额的钱
        $result = Db::name('users')->where(['user_id'=>$userId])->update(['user_money'=>$user_money]);
        if(!$result){
            return $this->failResult('事务处理失败',301);
        }


        //增加主播余额的钱
        $zhubo_user_id = 0;
        $zhobuInfo = Db::name('user_video')->where(['room_id'=>$room_id])->find();
        $zhubo_user_id = $zhobuInfo['user_id'];
        $result = Db::name('users')->where(['user_id'=>$zhubo_user_id])->find();
        $user_money = bcadd($zhobuInfo['user_money'],$money,2);
        //增加主播余额的钱
        $result = Db::name('users')->where(['user_id'=>$zhubo_user_id])->update(['user_money'=>$user_money]);
        if(!$result){
            return $this->failResult('事务处理失败',301);
        }
        Db::commit();
        $message = array(
            'type'=>'say',
            'from_client_id'=>$userId,
            'from_client_name' =>$this->user->nickname,
            'to_client_id'=>'all',
            'content'=>$this->user->nickname.'给主播发了'.$money.'红包',
            'time'=>date('Y-m-d H:i:s'),
        );
        $json = json_encode($message,true);
        Events::onMessage($userId,$json);
        return $this->successResult('success');
    }

    /**
     * 用户发礼物
     * @return \think\response\Json
     * @throws \think\Exception
     */

    public function sendRedPacket(){
        $room_id = input('post.room_id', 0);
        $money = input('post.money',0);
        if(empty($room_id) || empty($money)){
            return $this->failResult('参数有误',301);
        }
        $money = bcadd($money,'0.00',2);
        $userId = $this->user->user_id;
        $user = Db::name('users')->where(['user_id'=>$userId])->find();
        $koujian = bcsub($user['user_money'],$money,2);
        if($koujian < 0){
            return $this->failResult('余额不足',301);
        }
        //事务处理
        Db::startTrans();
        //剩余的用户钱
        $user_money = bcsub($user['user_money'],$money,2);
        //扣减用户余额的钱
        $result = Db::name('users')->where(['user_id'=>$userId])->update(['user_money'=>$user_money]);
        if(!$result){
            return $this->failResult('事务处理失败',301);
        }


        //增加主播余额的钱
        $zhubo_user_id = 0;
        $zhobuInfo = Db::name('user_video')->where(['room_id'=>$room_id])->find();
        $zhubo_user_id = $zhobuInfo['user_id'];
        $result = Db::name('users')->where(['user_id'=>$zhubo_user_id])->find();
        $user_money = bcadd($zhobuInfo['user_money'],$money,2);
        //增加主播余额的钱
        $result = Db::name('users')->where(['user_id'=>$zhubo_user_id])->update(['user_money'=>$user_money]);
        if(!$result){
            return $this->failResult('事务处理失败',301);
        }

        //新增用户给主播发红包流水
        $data = [
            'user_id'=>$userId,
            'to_user_id'=>$zhubo_user_id,
            'room_id'=>$room_id,
            'money'=>$money,
            'create_time'=>time(),
        ];
        $result = Db::name('live_red_sending_log')->insert($data);
        if(!$result){
            return $this->failResult('事务处理失败',301);
        }
        Db::commit();

        $message = array(
            'type'=>'say',
            'from_client_id'=>$userId,
            'from_client_name' =>$this->user->nickname,
            'to_client_id'=>'all',
            'content'=>$this->user->nickname.'给主播发了'.$money.'红包',
            'time'=>date('Y-m-d H:i:s'),
        );
//        $json = json_encode($message,true);
//        Events::onMessage($userId,$json);
        return $this->successResult($message);
    }

    public function RtmTokenBuilderSample()
    {
        $appID = "4c2954a8e1524f5ea15dc5ae14232042";
        $appCertificate = "1580a6da5ed94447840d870a07e1c6e2";
        $account = input('post.room_id', 1);
        $expiredTs = 0;
        $builder = new RtmTokenBuilder($appID, $appCertificate, $account);
        $builder->setPrivilege(AccessToken::Privileges["kRtmLogin"], $expiredTs);
        echo $builder->buildToken();
        exit;
    }

    public function like()
    {
        $room_id = I('post.room_id', 0);
        $room = Db::name('user_video')->where(['room_id' => $room_id, 'status' => 1])->find();
        if (empty($room)) {
            return $this->failResult('不存在的直播间', 301);
        }

        $user_id = $this->user->user_id;
        $verifyData = Db::name('live_like')->where(['user_id' => $user_id, 'room_id' => $room_id])->find();
        // 没有点赞记录，新增、top_amount++
        if (!$verifyData) {
            $data = array(
                'room_id' => $room_id,
                'user_id' => $user_id,
                'create_time' => time()
            );
            Db::startTrans();
            $like = Db::name('live_like')->insert($data);
            $result = Db::name('user_video')->where(['room_id' => $room_id, 'status' => 1])->setInc('top_amount');
            if ($like && $result) {
                Db::commit();
                return $this->successResult('点赞成功');
            } else {
                Db::rollback();
                return $this->failResult('点赞失败', 301);
            }
        }
        return $this->successResult('已点赞');
    }
}