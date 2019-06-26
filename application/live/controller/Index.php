<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/6/14 0014
 * Time: 11:31
 */

namespace app\live\controller;

use app\admin\validate\Goods;
use app\common\model\UserVideo;
use app\live\service\AccessToken;
use app\live\service\RtmTokenBuilder;
use app\live\controller\Events;
use think\AjaxPage;
use think\Db;

class Index extends Base
{
    private $uploadDir = 'public' . DS . 'static' . DS . 'uploads' . DS . 'fengmian';

    /**
     * 主播开始直播页面
     * @return mixed
     */
    public function index()
    {
        $user_id = $this->user->user_id;
        // 不是主播，跳转申请页面
//        $identity = Db::name('user_verify_identity_info')->where(['user_id' => $user_id, 'verify_state' => 1])->find();
//        !$identity && $this->redirect('/Live/Apply');

        // 没有正在直播的，跳转设置直播信息
        $room = Db::name('user_video')->where(['user_id' => $user_id, 'status' => 1])->order('id desc')->find();
        !$room && $this->redirect('/Live/Index/set');

        //add by zgp 2019.6.26
        //获取商品列表
        $room_id = input('get.room_id', 1);
        $room = Db::name('user_video')->where(['room_id' => $room_id, 'status' => 1])->find();
//        if (empty($room)) {
//            return $this->failResult('不存在的直播间', 301);
//        }
        $goodsList = [];
        if(!empty($room['good_ids'])){
            $ids = json_decode($room['good_ids']);
            if (count($ids) > 0) {
                foreach ($ids as $id) {
                    $goodsList[] = Db::name('goods')->where(['goods_id' => $id])->field('goods_id,goods_name,original_img,store_count,market_price,shop_price,cost_price')->find();
                }
            }
        }
        $goodsListData = [];
        $http_type = ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https')) ? 'https://' : 'http://';
        $url=$http_type.$_SERVER['SERVER_NAME'];
        foreach($goodsList as $k=>$v){
            $goodsListData[$k] = $v;
            $goodsListData[$k]['goods_url'] = $url.$v['original_img'];
        }
//        print_r($goodsListData);die;
        $this->assign('goodsList',$goodsListData);
        $this->assign('user_name',$this->user->nickname);
        $this->assign('level',isset($this->user->agentlevel)&&!empty($this->user->agentlevel) ? $this->user->agentlevel : 0);
        //add by zgp 2019.6.26

        $this->assign('room_id', $room['room_id']);
        $this->assign('user_id', $user_id . time());
        $this->assign('users_id', $user_id);
        return $this->fetch();
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
     * 直播列表页
     * @return mixed
     */
    public function videoList()
    {
        $user_id = $this->user->user_id;
        $identity = Db::name('user_verify_identity_info')->where(['user_id' => $user_id, 'verify_state' => 1])->find();
        // 主播显示直播按钮
        $this->assign('zhubo', $identity ? 1 : 0);
        return $this->fetch();
    }

    /*
     * ajax正在直播列表
     */
    public function ajaxVideoList()
    {
        $where = ['status' => 1];
        $count = M('UserVideo')->where($where)->count();
        $page_count = C('PAGESIZE');
        $page = new AjaxPage($count, $page_count);
        $list = (new UserVideo)->where($where)->order("id desc")
            ->limit($page->firstRow . ',' . $page->listRows)
            ->select();
        //跳转到用户端直播间
        $data = [];
        $http_type = ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https')) ? 'https://' : 'http://';
        $url=$http_type.$_SERVER['SERVER_NAME'];
        foreach($list as $k=>$v){
            $data[$k] = $v;
            //跳转到直播间url
            $data[$k]['url'] = $url.'/Live/user/index.html?room_id='.$v['room_id'];
        }
        return $this->ajaxReturn([
            'content' => $this->fetch('index/ajaxVideoList', ['videoList' => $data, 'count' => $count]),
            'count' => $count,
            'list_count' => count($list),
            'page_count' => $page_count,
            'current_count' => $page_count * I('p'),
            'p' => I('p')
        ]);
    }

    /**
     * 设置直播
     * @return mixed
     */
    public function set()
    {
        $user_id = $this->user->user_id;
        $identity = Db::name('user_verify_identity_info')->where(['user_id' => $user_id, 'verify_state' => 1])->find();
        if (empty($identity)) {
            return $this->failResult('身份验证错误', 301);
        }
        return $this->fetch();
    }

    /**
     * 提交开始直播
     * @return mixed
     */
    public function start()
    {
        $user_id = $this->user->user_id;
        $identity = Db::name('user_verify_identity_info')->where(['user_id' => $user_id, 'verify_state' => 1])->find();
        if (empty($identity)) {
            return $this->failResult('身份验证错误', 301);
        }

        if (!($fengmian = request()->file('fengmian'))) {
            return $this->failResult('请设置封面', 301);
        }
        //将传入的图片移动到框架应用根目录/public/uploads/ 目录下，ROOT_PATH是根目录下，DS是代表斜杠 /
        if (!($info = $fengmian->move(ROOT_PATH . $this->uploadDir))) {
            // 上传失败获取错误信息
            return $this->failResult('封面上传失败', 301);
        }
        $data = [
            'user_id' => $user_id,
            'room_id' => $user_id . time(),
            'pic_fengmian' => DS . $this->uploadDir . DS . $info->getSaveName(),
            'location' => '',
            'start_time' => time(),
            'status' => 1
        ];
        $result = Db::name('user_video')->insert($data);
        if (!$result) {
            return $this->failResult('开始直播失败', 301);
        }

        return $this->successResult(['room_id' => $data['room_id']]);
    }

    /**
     * 直播结束
     * @return mixed
     */
    public function end()
    {
        $user_id = $this->user->user_id;
        $identity = Db::name('user_verify_identity_info')->where(['user_id' => $user_id, 'verify_state' => 1])->find();
        if (empty($identity)) {
            return $this->failResult('身份验证错误', 301);
        }

        $room_id = I('id');
        $room = (new UserVideo)->where(['user_id' => $user_id, 'room_id' => $room_id, 'status' => 1])->find();
        if (empty($room)) {
            return $this->failResult('不存在的直播间', 301);
        }
//        if (!$room->save(['status' => 2])) {
//            return $this->failResult('结束直播失败', 301);
//        }
        $identity['pic_head'] = $this->user['head_pic'];
        $identity['pic_fengmian'] = $this->url . $identity['pic_fengmian'];
        $this->assign('identity', $identity);
        $this->assign('room', $room);
        $http_type = ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https')) ? 'https://' : 'http://';
        $url=$http_type.$_SERVER['SERVER_NAME'];
        $this->assign('room_pic',$url.$room['pic_fengmian']);
        $this->assign('user_name',$this->user->nickname);
        $this->assign('user_id',$user_id);
        $this->assign('head_pic',$this->user->head_pic);
        return $this->fetch();
    }

    public function RtmTokenBuilderSample()
    {
        $appID = "4c2954a8e1524f5ea15dc5ae14232042";
        $appCertificate = "1580a6da5ed94447840d870a07e1c6e2";
        $account = "1000";
        $expiredTs = 0;
        $builder = new RtmTokenBuilder($appID, $appCertificate, $account);
        $builder->setPrivilege(AccessToken::Privileges["kRtmLogin"], $expiredTs);
        echo $builder->buildToken();
        exit;
    }

    /**
     * 红包生成
     */
    public function redSubmit(){

        $money = input('post.money', 0); //红包金额
        $num = input('post.num', 0); //红包个数
        $room_id = input('post.room_id', 0); //房间id
        // $users_id = input('post.users_id', 0); //用户id
        if (empty($num) || empty($money) || empty($room_id)) {
            return $this->failResult('参数有误', 301);
        }
        $userId = $this->user->user_id;
        $user = Db::name('users')->where(['user_id' => $userId])->find();
        $koujian = bcsub($user['user_money'], $money, 2);
        if ($koujian < 0) {
            return $this->failResult('余额不足', 301);
        }
        //事务处理
        Db::startTrans();
        //剩余的用户钱
        $user_money = bcsub($user['user_money'], $money, 2);
        //扣减用户余额的钱
        $result = Db::name('users')->where(['user_id' => $userId])->update(['user_money' => $user_money]);
        if (!$result) {
            return $this->failResult('事务处理失败', 301);
        }
        $createRedDate = $this->createRedDate($money, $num); //生成红包
        if (!$createRedDate) {
            return $this->failResult('事务处理失败', 301);
        }
        // dump($createRedDate);
        $red_master_data = [
            "uid" => $userId,
            "room_id" => $room_id,
            "num" => $num,
            "money" => $money,
            "create_time" => time()
        ];
        $red_master = $this->tp_red_master($red_master_data);
        if (!$red_master) {
            return $this->failResult('事务处理失败', 301);
        }
        // 遍历插入红包从表
        foreach ($createRedDate['redMoneyList'] as $key => $vey_money) {
            $red_detail_data = [
                'money' => $vey_money,
                'm_id' => $red_master,
                'room_id' => $room_id
            ];
            $red_detail = $this->tp_red_detail($red_detail_data);
            if (!$red_detail) {
                return $this->failResult('事务处理失败', 301);
            }
        }
        Db::commit();
        $message = array(
            'type' => 'red_anchor',
            'from_client_id' => $userId,
            'from_client_name' => $this->user->nickname,
            'to_client_id' => 'all',
            'content' => $this->user->nickname . '主播发了' . $money . '元红包',
            'time' => date('Y-m-d H:i:s'),
        );
        return $this->successResult($message);
        // dump($createRedDate);
    }
    //tp_red_detail红包从表插入
    public function tp_red_detail($data)
    {
        $result = Db::name('red_detail')->insert($data);
        if ($result) {
            return true;
        } else {
            return false;
        }
    }

    //tp_red_detail红包主表插入
    public function tp_red_master($data)
    {
        $result = Db::name('red_master')->insertGetId($data);
        if ($result) {
            return $result;
        } else {
            return false;
        }
    }

    /**
     * @param $total  [要发的红包总额]
     * @param int $num  [红包个数]
     * @return array [生成红包金额]
     */
    public function createRedDate($total, $num)
    {
        if (!$total || !$num) {
            return false;
        }
        $min = 0.01; // 保证最小金额
        $wamp = array();
        $returnData = array();
        for ($i = 1; $i < $num; ++$i) {
            $safe_total = ($total - ($num - $i) * $min) / ($num - $i); // 随机安全上限 红包金额的最大值
            if ($safe_total < 0) break;
            $money = @mt_rand($min * 100, $safe_total * 100) / 100; // 随机产生一个红包金额
            $total = $total - $money;   // 剩余红包总额
            $wamp[$i] = sprintf("%.2f", $money); // 保留两位有效数字
        }
        $wamp[$i] = sprintf("%.2f", $total);
        $returnData['redMoneyList'] = $wamp;
        $returnData['newTotalMoney'] = array_sum($wamp);
        return $returnData;
    }

    /**
     * 用户点击领取红包
     * user_id    抢包人id
     * room_id    房间id
     * users_id   发包人id
     * red_master_id   红包主表id
     */
    public function click_red_packet($user_id, $room_id, $users_id, $red_master_id)
    {
        //获取红包从表信息
        $red_master_find = $this->red_master_find($room_id, $users_id);
        if (empty($red_master_find)) {
            return $this->failResult('该红包已不存在');
        }
        $red_detail_find = Db::name('red_detail')->where(['m_id' => $red_master_find['id'], 'room_id' => $red_master_find['room_id']])->find();
        if (empty($red_detail_find)) {
            return $this->failResult('红包已领完!!!');
        }

        //获取抢包用户信息
        $user_data = $this->user($user_id);
    }
    /**
     * 查找对应红包从表数据
     */
    public function red_master_find($red_master_id, $room_id)
    {
        $red_user_find = Db::name("red_master")->where(['room_id' => $room_id, 'id' => $red_master_id])->find();
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
