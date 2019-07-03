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
        $room_id = input('get.room_id', 0);
        $room = Db::name('user_video')->where(['room_id' => $room_id])->find();
        if(empty($room)){//不存在直播；跳转到直播间列表
            $this->redirect("Live/Index/videoList");
            exit;
        }
        if ($room['status']==2) {//如果主播已结束，跳转到结束页面
            $this->redirect(U('Live/index/end', ['id' => $room_id]));
            exit;
        }

        //获取礼物列表
        $giftList = Db::name('live_gift')->order('sort asc')->select();

        //主播的用户名  主播图片
        $zhubo = Db::name('users')->where(['user_id'=>$room['user_id']])->find();

        $this->assign('user_id', $this->user->user_id);
        $this->assign('user_name',$this->user->nickname);
        $this->assign('head_pic',$this->user->head_pic);
        $this->assign('zhubo_user_name',$zhubo['nickname']);
        $this->assign('zhubo_head_pic',$zhubo['head_pic']);

        $this->assign('level',isset($this->user->agentlevel)&&!empty($this->user->agentlevel) ? $this->user->agentlevel : 0);
        $this->assign('room_id', $room_id);
        $this->assign('users_id', $userId);
        $http_type = ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https')) ? 'https://' : 'http://';
        $url=$http_type.$_SERVER['SERVER_NAME'];
        $this->assign('url',$url);
        $this->assign('room',$room);
        $this->assign('giftList',$giftList);
        $this->assign('server_name',$_SERVER['SERVER_NAME']);
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
        $gift_id = input('post.gift_id',0);
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
            'data'=>"【{$userId}:{$this->user->nickname}】给【{$zhubo_user_id}:{$zhobuInfo['nickname']}】发价值【{$gift['price']}】的【{$gift_id}:{$gift['name']}】礼物",
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
            'content'=>'给主播发了'.$gift['name'].'礼物',
            'time'=>date('Y-m-d H:i:s'),
        );
        return $this->successResult($message);
    }

    /**
     * 用户发红包
     * @return \think\response\Json
     * @throws \think\Exception
     */

    public function sendRedPacket(){
        $room_id = input('post.room_id', 0);
        $money_input = input('post.money',0);
        if(empty($room_id) || empty($money_input)){
            return $this->failResult('参数有误',301);
        }
        $money = bcadd($money_input,'0.00',2);
        if($money < 0 || $money != $money_input ){
            return $this->failResult('金额格式不对',301);
        }
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
        $zhobuVideo = Db::name('user_video')->where(['room_id'=>$room_id])->find();
        if(!$zhobuVideo){
            Db::rollback();
            return $this->failResult('主播直播间不存在',301);
        }
        $zhubo_user_id = $zhobuVideo['user_id'];
        $zhobuUser = Db::name('users')->where(['user_id'=>$zhubo_user_id])->find();
        $user_money = bcadd($zhobuUser['user_money'],$money,2);
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
            'data'=>"【{$userId}:{$this->user->nickname}】给主播【{$zhubo_user_id}:{$zhobuUser['nickname']}】发了【{$money}】的红包",
            'create_time'=>time(),
        ];
        $result = Db::name('live_red_sending_log')->insert($data);
        if(!$result){
            Db::rollback();
            return $this->failResult('事务处理失败3',301);
        }

        //修改用户的金额
        $updateMoney = bcadd($zhobuVideo['money'],$money,2);
        $result = Db::name('user_video')->where(['user_id'=>$zhubo_user_id])->update(['money'=>$updateMoney]);
        if(!$result){
            Db::rollback();
            return $this->failResult('事务处理失败4',301);
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
        $goods_id = input('post.goods_id',0);
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

    /**
     * 用户观看人数加1
     * @throws \think\Exception
     */
    public function userLookAmount(){
        $room_id = I('post.room_id', 0);
        Db::name('user_video')->where(['room_id' => $room_id])->setInc('look_amount');
    }

    public function RtmTokenBuilderSample()
    {
        $appID = "4c2954a8e1524f5ea15dc5ae14232042";
        $appCertificate = "1580a6da5ed94447840d870a07e1c6e2";
        $account = input('post.channel', 0);
        $expiredTs = 0;
        $builder = new RtmTokenBuilder($appID, $appCertificate, $account);
        $builder->setPrivilege(AccessToken::Privileges["kRtmLogin"], $expiredTs);
        echo $builder->buildToken();
        exit;
    }

    /**
     * 用户点击领取红包
     * user_id    抢包人id
     * room_id    房间id
     * users_id   发包人id
     * red_master_id   红包主表id
     */
    public function click_red_packet()
    {
        $room_id = input('post.room_id', 0); //房间id
        $users_id = input('post.users_id', 0); //用户id
        $m_id = input('post.m_id', 0); //用户id
        if (empty($users_id) || empty($room_id) || empty($m_id)) {
            return $this->failResult('参数有误', 301);
        }

        $userId = $this->user->user_id;
        //判断用户是否已经抢过红包
        $if_red = Db::name('red_detail')->where(['get_uid' => $userId,'m_id' => $m_id,'room_id' => $room_id])->find();
        if($if_red){
            return $this->failResult('已抢过红包!!!', 301);
        }
        //事务处理
        Db::startTrans();
        //获取红包从表信息
        $red_master_find = $this->red_master_find($room_id,$m_id);
        if (!$red_master_find) {
            return $this->failResult('事务处理失败',301);
        }
        $red_detail_find = Db::name('red_detail')->where(['m_id' => $m_id,'type'=>0, 'room_id' => $room_id])->find();
        if (!$red_detail_find) {
            return $this->failResult('红包已领完!!!',301);
        }
        //获取抢包用户信息
        $user_data = $this->user($userId);
        $data = ['get_uid'=>$user_data['user_id'],'type'=>1,'get_award_money'=>$red_detail_find['money']];

        $result = Db::name('red_detail')->where(['m_id'=>$m_id,'id'=>$red_detail_find['id'],'room_id'=>$room_id])->update($data);
        if(!$result){
            return $this->failResult('事务处理失败', 301);
        }

        $user_money = bcadd($user_data['user_money'],$red_detail_find['money'],2);

        //增加抢包用户余额的钱
        $result_money = Db::name('users')->where(['user_id'=>$user_data['user_id']])->update(['user_money'=>$user_money]);
        if(!$result_money){
            return $this->failResult('事务处理失败', 301);
        }
        Db::commit();
        $money = bcadd($red_detail_find['money'],'0.00',2);
        $message = array(
            'type' => 'red_receive_user',
            'from_client_id' => $userId,
            'from_client_name' => $this->user->nickname,
            'to_client_id' => 'all',
            'moeny' => $money,
            'content' => $this->user->nickname . '领取了' . $money . '元红包',
            'time' => date('Y-m-d H:i:s'),
        );
        return $this->successResult($message);
    }
    /**
     * 查找对应红包从表数据
     */
    public function red_master_find($room_id,$m_id)
    {   
        $where = "room_id = '".$room_id."' and id = '".$m_id."' and all_get = 0";
        $red_user_find = Db::name("red_master")->where($where)->find();
        if ($red_user_find) {
            return $red_user_find;
        } else {
            return false;
        }
    }
    /**
     * 查询用户信息
     */
    public function user($user_id)
    {
        $user_find = Db::name("users")->where(['user_id' => $user_id])->find();
        if ($user_find) {
            return $user_find;
        } else {
            return false;
        }
    }
}