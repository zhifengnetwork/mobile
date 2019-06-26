<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/6/14 0014
 * Time: 11:31
 */

namespace app\live\controller;

use app\common\model\Goods;
use app\common\model\Users as UserModel;
use app\common\model\UserVideo;
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
        //上线后去掉默认值  add by zgp
        $room_id = input('get.room_id', 1);
        $room = Db::name('user_video')->where(['room_id' => $room_id, 'status' => 1])->find();
//        if (empty($room)) {
//            return $this->failResult('不存在的直播间', 301);
//        }

        //获取礼物列表
        $giftList = Db::name('live_gift')->order('sort asc')->select();

        $this->assign('user_id', time() . $this->user->user_id);
        $this->assign('user_name',$this->user->nickname);
        $this->assign('head_pic',$this->user->head_pic);
        $this->assign('level',isset($this->user->agentlevel)&&!empty($this->user->agentlevel) ? $this->user->agentlevel : 0);
        $this->assign('room_id', $room_id);
        $http_type = ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https')) ? 'https://' : 'http://';
        $url=$http_type.$_SERVER['SERVER_NAME'];
        $this->assign('url',$url);
        $this->assign('room',$room);
        $this->assign('giftList',$giftList);
        return $this->fetch();
    }

    /**
     * 用户发红包
     * @return \think\response\Json
     * @throws \think\Exception
     */
    public function sendGift(){
        $room_id = input('post.room_id', 0);
        //上线后去掉默认值  add by zgp
        $gift_id = 1;
//        $gift_id = input('post.gift_id',1);
        if(empty($room_id) || empty($gift_id)){
            return $this->failResult('参数有误',301);
        }
        $userId = $this->user->user_id;
        $user = Db::name('users')->where(['user_id'=>$userId])->find();
        $money = 0;
        //获取礼物的价格
        $gift = Db::name('live_gift')->where(['id'=>$gift_id])->find();
        if(empty($gift)){
            return $this->failResult('礼物不存在',301);
        }
        $money = $gift['price'];
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
            Db::rollback();
            return $this->failResult('事务处理失败',301);
        }

        //新增用户给主播发礼物流水
        $zhubo_user_id = 0;
        $zhobuInfo = Db::name('user_video')->where(['room_id'=>$room_id])->find();
        $zhubo_user_id = $zhobuInfo['user_id'];
        $zhobuInfo = Db::name('users')->where(['user_id'=>$zhubo_user_id])->find();
        $data = [
            'gift_id'=>$gift_id,
            'user_id'=>$userId,
            'to_user_id'=>$zhubo_user_id,
            'room_id'=>$room_id,
            'data'=>'粉丝给主播发'.$gift['name'].'礼物',
            'create_time'=>time(),
        ];
        $result = Db::name('live_gift_sending_log')->insert($data);
        if(!$result){
            Db::rollback();
            return $this->failResult('事务处理失败',301);
        }

        Db::commit();

        $message = array(
            'type'=>'gift',
            'from_client_id'=>$userId,
            'from_client_name' =>$this->user->nickname,
            'to_client_id'=>'all',
            'gift_id'=>1,
            'content'=>$this->user->nickname.'给主播发了'.$gift['name'].'礼物',
            'time'=>date('Y-m-d H:i:s'),
        );
        return $this->successResult($message);
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
            Db::rollback();
            return $this->failResult('事务处理失败1',301);
        }

        //增加主播余额的钱
        $zhubo_user_id = 0;
        $zhobuInfo = Db::name('user_video')->where(['room_id'=>$room_id])->find();
        if(!$zhobuInfo){
            Db::rollback();
            return $this->failResult('主播直播间不存在',301);
        }
        $zhubo_user_id = $zhobuInfo['user_id'];
        $zhobuInfo = Db::name('users')->where(['user_id'=>$zhubo_user_id])->find();
        $user_money = bcadd($zhobuInfo['user_money'],$money,2);
        //增加主播余额的钱
        $result = Db::name('users')->where(['user_id'=>$zhubo_user_id])->update(['user_money'=>$user_money]);
        if(!$result){
            Db::rollback();
            return $this->failResult('事务处理失败2',301);
        }

        //新增用户给主播发红包流水
        $data = [
            'user_id'=>$userId,
            'to_user_id'=>$zhubo_user_id,
            'room_id'=>$room_id,
            'money'=>$money,
            'data'=>'粉丝给主播发红包',
            'create_time'=>time(),
        ];
        $result = Db::name('live_red_sending_log')->insert($data);
        if(!$result){
            Db::rollback();
            return $this->failResult('事务处理失败3',301);
        }
        Db::commit();

        $message = array(
            'type'=>'say',
            'from_client_id'=>$userId,
            'from_client_name' =>$this->user->nickname,
            'to_client_id'=>'all',
            'content'=>'给主播发了'.$money.'红包',
            'time'=>date('Y-m-d H:i:s'),
        );
        return $this->successResult($message);
    }

    /**
     * 主播分享购物链接
     * @return \think\response\Json
     * @throws \think\Exception
     */
    public function sendGoodsUrl(){
        $room_id = input('post.room_id', 0);
        //上线后去掉默认值  add by zgp
        $goods_id = 285;
//        $goods_id = input('post.goods_id',0);
        if(empty($room_id) || empty($goods_id)){
            return $this->failResult('参数有误',301);
        }
        $userId = $this->user->user_id;
        $user = Db::name('users')->where(['user_id'=>$userId])->find();
        $user_video = Db::name('user_video')->where(['room_id'=>$room_id])->find();

        $http_type = ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https')) ? 'https://' : 'http://';
        $url=$http_type.$_SERVER['SERVER_NAME'];

        $goods_url = $url.'/Mobile/Goods/goodsInfo/id/'.$goods_id.'.html?zhubo_id='.$user_video['user_id'];
        $message = array(
            'type'=>'gift',
            'from_client_id'=>$userId,
            'from_client_name' =>$this->user->nickname,
            'to_client_id'=>'all',
            'goods_url'=>$goods_url,
            'content'=>'主播发了商品链接分享',
            'time'=>date('Y-m-d H:i:s'),
        );
        return $this->successResult($message);
    }

    /**
     * 点赞
     * @return \think\response\Json
     * @throws \think\Exception
     */
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
            $user_video = Db::name('user_video')->where(['room_id' => $room_id, 'status' => 1])->find();
            if ($like && $result) {
                Db::commit();
                $message['msg']='点赞成功';
                $message['count'] = $user_video['top_amount'];
                return $this->successResult($message);
            } else {
                Db::rollback();
                return $this->failResult('点赞失败', 301);
            }
        }
        return $this->failResult('已点赞');
    }

    /**
     * 获取点赞的人数
     * @return \think\response\Json
     */
    public function userTopAmount(){
        $room_id = I('post.room_id', 1);
        $room = Db::name('user_video')->where(['room_id' => $room_id, 'status' => 1])->find();
        if (empty($room)) {
            return $this->failResult('不存在的直播间', 301);
        }
        $room = Db::name('user_video')->where(['room_id' => $room_id, 'status' => 1])->find();
        $data['count'] = $room['top_amount'];
        return $this->successResult($data);
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
}