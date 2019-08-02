<?php
/**
 * 用户API
 */
namespace app\api\controller;
use app\common\model\Users;
use app\common\logic\UsersLogic;
use think\Db;
use think\Page;
use app\common\logic\Message;
use app\common\model\UserMessage;
use app\common\logic\wechat\WechatUtil;
use app\common\logic\ShareLogic;

class Live extends ApiBase
{


    /**
     * 直播列表
     */
    public function videoList()
    {
        $user_id = $this->user->user_id;
        $identity = Db::name('user_verify_identity_info')->where(['user_id' => $user_id, 'verify_state' => 1])->find();

        // 判断是否是主播，是主播显示主播按钮

        $data['zhubo'] = $identity ? 1 : 0;

        //正则直播列表
        $where = ['status' => 1];
//        $count = M('user_video')->where($where)->count();
//        $page_count = C('PAGESIZE');
//        $page = new AjaxPage($count, $page_count);
        $list = M('user_video')->where($where)->order("id desc")
//            ->limit($page->firstRow . ',' . $page->listRows)
            ->select();

        //跳转到用户端直播间
        $http_type = ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https')) ? 'https://' : 'http://';
        $url = $http_type . $_SERVER['SERVER_NAME'];

        foreach ($list as $k => $v) {
            $data[$k] = $v;
            //跳转到直播间url
            $data[$k]['url'] = $url . '/api/live/master_live_room?room_id=' . $v['room_id'];
            $data[$k]['start_time'] = date('Y-m-d H:i:s');
        }
        $this->ajaxReturn(['status' => 0, 'msg' => '获取直播列表成功', 'data' => $data]);
    }






    /**
     * 申请直播
     */
    public function apply()
    {
        //解密token
        $user_id = $this->get_user_id();
        if(!$user_id){
            $this->ajaxReturn(['status' => -1 , 'msg'=>'用户不存在','data'=>'']);
        }

        if($user_id!="") {
//            //选择会员
//            $levelname = M('user_level')->field('level_name')->select();
//
//            foreach ($levelname as $k=>$v){
//                $levelname[$k]=$v['level_name'];
//            }
//
//            foreach ($levelname as $k=>$v){
//                $levelname['name'][$k] = $v;
//            }
//
//            $data['level_name'] = $levelname;


        if (IS_POST) {

            $mobile = I('post.mobile', '');
            $name = I('post.username', '');
            $level = I('post.level_id', '');

            $data['mobile'] = $mobile;
            $data['name'] = $name;
            $data['user_id'] = $user_id;
            $data['level_id'] = $level;
            $data['create_time'] = time();

            // 身份证正面
            $pic_front = request()->file('pic_front');
            $save_url = UPLOAD_PATH.'idcard/' . date('Y', time()) . '/' . date('m-d', time());
            if($pic_front) {
                    // 移动到框架应用根目录/public/uploads/ 目录下
                    $image_upload_limit_size = config('image_upload_limit_size');
                    $info = $pic_front->rule('uniqid')->validate(['size' => $image_upload_limit_size, 'ext' => 'jpg,png,gif,jpeg'])->move($save_url);
                    if ($info) {
                        // 成功上传后 获取上传信息
                        // 输出 jpg
                        $comment_img = '/' . $save_url . '/' . $info->getFilename();
                    } else {
                        // 上传失败获取错误信息
                        $this->ajaxReturn(['status' =>-1,'msg' =>$pic_front->getError()]);
                    }
                $data['pic_front'] =$comment_img;
            }

            $pic_back = request()->file('pic_back');
            $save_url = UPLOAD_PATH.'idcard/' . date('Y', time()) . '/' . date('m-d', time());
            if($pic_back) {
                // 移动到框架应用根目录/public/uploads/ 目录下
                $image_upload_limit_size = config('image_upload_limit_size');
                $info = $pic_back->rule('uniqid')->validate(['size' => $image_upload_limit_size, 'ext' => 'jpg,png,gif,jpeg'])->move($save_url);
                if ($info) {
                    // 成功上传后 获取上传信息
                    // 输出 jpg
                    $comment_img1 = '/' . $save_url . '/' . $info->getFilename();
                } else {
                    // 上传失败获取错误信息
                    $this->ajaxReturn(['status' =>-1,'msg' =>$pic_back->getError()]);
                }
                $data['pic_back'] =$comment_img1;
            }


        }
//        存入表
            $rel = M('user_verify_identity_info')->field('user_id')->where(['user_id'=>$user_id])->find();
            if($rel){
                $res = M('user_verify_identity_info')->where(['user_id'=>$user_id])->update($data);
            }else{
                $res = M('user_verify_identity_info')->insert($data);
            }
        }
        if ($res){
            $data['pic_back'] =SITE_URL.$comment_img1;
            $data['pic_front'] =SITE_URL.$comment_img;
            $this->ajaxReturn(['status' => 0, 'msg' => '提交成功', 'data' => $data]);
        }else{
            $this->ajaxReturn(['status' => -2 , 'msg'=>'提交失败','data'=>$pic_front->getError()]);
        }
    }


    /**
     * 设置开始直播商品
     */
//    public function livegoods()
//    {
//        $user_id = $this->user->user_id;
//        if(!$user_id){
//            $this->ajaxReturn(['status' => -1 , 'msg'=>'用户不存在','data'=>'']);
//        }
//        $goodsList  =  Db::name('goods')->field('goods_id,goods_name,original_img,store_count,market_price,shop_price,cost_price')->limit(0, 100)->select();
//
//        $goodsListData = [];
//
//        foreach ($goodsList as $k => $v) {
//            $goodsListData[$k] = $v;
//            $goodsListData[$k]['original_img'] = SITE_URL.$v['original_img'];
//        }
//
//        $identity = Db::name('user_verify_identity_info')->where(['user_id' => $user_id, 'verify_state' => 1])->find();
//        if (empty($identity)) {
//            $this->ajaxReturn(['status' => -2 , 'msg'=>'身份验证错误','data'=>'']);
//        }
//        $this->ajaxReturn(['status' => 0 , 'msg'=>'获取成功','data'=>$goodsListData]);
//    }

    /**
     * 开始直播
     */
    public function beginlive()
    {
//        解密token
        $user_id = $this->get_user_id();
        if(!$user_id){
            $this->ajaxReturn(['status' => -1 , 'msg'=>'用户不存在','data'=>'']);
        }

        $identity = Db::name('user_verify_identity_info')->where(['user_id' => $user_id, 'verify_state' => 1])->find();
        if (empty($identity)) {
            $this->ajaxReturn(['status' => -1 , 'msg'=>'身份验证错误','data'=>'']);
        }
        if (!($fengmian = request()->file('image'))) {
            $this->ajaxReturn(['status' => -1 , 'msg'=>'请设置封面','data'=>'']);
        }
        //设置封面
        if($user_id!=""){
            // 获取表单上传文件 例如上传了001.jpg
            $file = request()->file('image');
            // 移动到框架应用根目录/uploads/ 目录下
            $info = $file->validate(['size'=>204800,'ext'=>'jpg,png,gif']);
            $info = $file->rule('md5')->move(ROOT_PATH . DS.'public/upload');//加密->保存路径
            if($info){
                // 成功上传后 获取上传信息
                // 输出 jpg
                // echo $info->getExtension();
                // 输出 20160820/42a79759f284b767dfcb2a0197904287.jpg
                // echo $info->getSaveName();
                // 输出 42a79759f284b767dfcb2a0197904287.jpg


                $data = [
                    'user_id'  => $user_id,
                    'room_id'  => $user_id . time(),
                    'pic_fengmian' => SITE_URL.'/public/upload/'.$info->getSaveName(),
                    'location' => '',
                    'start_time' => time(),
                    'status' => 1
                ];
                $result = Db::name('user_video')->insert($data);
                if($result){
                    $this->ajaxReturn(['status' => 0 , 'msg'=>'开始直播成功','data'=>$data]);
                }else{
                    $this->ajaxReturn(['status' => -1 , 'msg'=>'开始直播失败','data'=>'']);
                }
            }else{
                $this->ajaxReturn(['status' => -2 , 'msg'=>'上传失败','data'=>$file->getError()]);
            }

        }

    }

    //主播直播间
    public function master_live_room()
    {
        $user_id = $this->get_user_id();
        if(!$user_id){
            $this->ajaxReturn(['status' => -1 , 'msg'=>'用户不存在','data'=>'']);
        }
        if (IS_GET) {
            $room_id = I('get.room_id');
            $rel = M('user_video')->field('user_id,look_amount,top_amount')->where(['room_id' => $room_id])->find();
            $this->ajaxReturn(['status' => 0, 'msg' => '获取成功', 'data' => $rel]);
        }
    }



    //商品弹窗
    public function goods_upwindows()
    {
        $user_id = $this->get_user_id();

        if(!$user_id){
            $this->ajaxReturn(['status' => -1 , 'msg'=>'用户不存在','data'=>'']);
        }
        if (IS_POST) {
            $room_id = I('post.room_id');
            $good = M('goods')->field('goods_id,original_img,goods_name,shop_price')->where(['is_show'=>1])->select();

            foreach ($good as $k=>$v){
                $goodimg = M('goods_images')->field('image_url')->where(['goods_id'=>$good[$k]['goods_id']])->find();

                $good[$k]['original_img'] = SITE_URL. $goodimg['image_url'];

            }
        }
        $identity = Db::name('user_verify_identity_info')->where(['user_id' => $user_id, 'verify_state' => 2])->find();
        if (empty($identity)) {
            $this->ajaxReturn(['status' => -2 , 'msg'=>'身份验证错误','data'=>'']);
        }
        $this->ajaxReturn(['status' => 0, 'msg' => '提交成功', 'data' => $good]);
    }

    /**
     * 主播分享购物链接
     * @return \think\response\Json
     * @throws \think\Exception
     */
    public function sendGoodsUrl()
    {
        $room_id = input('post.room_id', 0);
        $goods_id = input('post.goods_id', 0);
        if (empty($room_id) || empty($goods_id)) {
            $this->ajaxReturn(['status' => -1 , 'msg'=>'参数有误','data'=>'']);
        }
        $userId =$this->get_user_id();
        if(!$userId){
            $this->ajaxReturn(['status' => -1 , 'msg'=>'用户不存在','data'=>'']);
        }
        $useinfo = Db::name('users')->field('nickname')->where(['user_id' => $userId])->find();
        $user_video = Db::name('user_video')->where(['room_id' => $room_id])->find();

        $http_type = ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https')) ? 'https://' : 'http://';
        $url = $http_type . $_SERVER['SERVER_NAME'];

        $goods_url = $url . '/Mobile/Goods/goodsInfo/id/' . $goods_id . '.html?zhubo_id=' . $user_video['user_id'];
        $message = array(
//            'type' => 'gift',
            'from_client_id' => $userId,
            'from_client_name' => $useinfo['nickname'],
//            'to_client_id' => 'all',
            'goods_url' => $goods_url,
            'content' => '主播发了商品链接分享',
            'time' => date('Y-m-d H:i:s'),
        );
        $this->ajaxReturn(['status' => 0, 'msg' => '提交成功', 'data' => $message]);
    }



    //红包弹窗
    public function red_upwindows()
    {
        $user_id = $this->get_user_id();
        if(!$user_id){
            $this->ajaxReturn(['status' => -1 , 'msg'=>'用户不存在','data'=>'']);
        }

        $money_input = input('post.total_money', 0); //红包金额
        $num = input('post.red_number', 0); //红包个数
        $room_id = input('post.room_id', 0); //房间id
        $money = bcadd($money_input, '0.00', 2);

        if (empty($num) || empty($money_input) || empty($room_id)) {
            $this->ajaxReturn(['status' => -1 , 'msg'=>'参数有误','data'=>'']);
        }
        if ($money < 0 || $money != $money_input) {
            $this->ajaxReturn(['status' => -1 , 'msg'=>'金融格式不正确','data'=>'']);
        }
        if (!is_numeric($num) || strpos($num, ".") !== false) {
            $this->ajaxReturn(['status' => -1 , 'msg'=>'红包个数不正确','data'=>'']);
        }

        $user = Db::name('users')->where(['user_id' => $user_id])->find();
        $deduct_money = bcsub($user['user_money'], $money, 2);
        // dump($deduct_money);
        if ($deduct_money < 0) {
            $this->ajaxReturn(['status' => -1 , 'msg'=>'余额不足','data'=>'']);
        }

        //事务处理
        Db::startTrans();
        //剩余的用户钱
        $user_money = bcsub($user['user_money'], $money, 2);
        // dump($user_money);
//        扣减用户余额的钱
         $result = Db::name('users')->where(['user_id' => $user_id])->update(['user_money' => $user_money]);
         if (!$result) {
             $this->ajaxReturn(['status' => -1 , 'msg'=>'事务处理失败','data'=>'']);
         }
        $createRedDate = $this->createRedDate($money, $num); //生成红包
        if (!$createRedDate) {
            $this->ajaxReturn(['status' => -1 , 'msg'=>'事务处理失败','data'=>'']);
        }

        $red_master_data = [
            "uid" => $user_id,
            "room_id" => $room_id,
            "num" => $num,
            "money" => $money,
            "create_time" => time()
        ];
        $red_master = $this->tp_red_master($red_master_data);
        if (!$red_master) {
            $this->ajaxReturn(['status' => -1 , 'msg'=>'事务处理失败','data'=>'']);
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
                $this->ajaxReturn(['status' => -1 , 'msg'=>'事务处理失败','data'=>'']);
            }
        }

        //添加消费记录
        accountLog($user_id, -$money, 0,  '直播发红包', 0, $red_master, time());

        Db::commit();
        $user_find = Db::name("users")->where(['user_id' => $user_id])->find();
        $message = array(
//            'type' => 'red_anchor',
            'from_client_id' => $user_id,
            'from_client_name' => $user_find['nickname'],
//            'to_client_id' => 'all',
            'm_id' => $red_master,
            'content' => $this->user->nickname . '主播发了' . $money . '元红包',
            'time' => date('Y-m-d H:i:s'),
        );

        $this->ajaxReturn(['status' => 0, 'msg' => '提交成功', 'data' => $message]);

    }

    /**
     * 生成红包
     */
    public function createRedDate($total, $num)
    {
        if (!$total || !$num) {
            return false;
        }
        $min = 0.01; // 保证最小金额
        if ($total <= $min) {
            $this->ajaxReturn(['status' => -1, 'msg' => '金额不正确', 'data' => '']);
        }
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



    //结束直播
    public function liveover()
    {
        $user_id = $this->get_user_id();
        if(!$user_id){
            $this->ajaxReturn(['status' => -1 , 'msg'=>'用户不存在','data'=>'']);
        }
        if (IS_GET) {
            $room_id = I('get.room_id');

            $data = M('user_video')->field('pic_fengmian,user_id,start_time,end_time,money,look_amount,top_amount')->where(['room_id'=>$room_id])->find();
            if ($data){
                $data['pic_fengmian'] = SITE_URL.$data['pic_fengmian'];

                //计算天数
                $timediff = $data['end_time']-$data['start_time'];
                $days = intval($timediff/86400);
                //计算小时数
                $remain = $timediff%86400;
                $hours = intval($remain/3600);
                //计算分钟数
                $remain = $remain%3600;
                $mins = intval($remain/60);
                //计算秒数
                $secs = $remain%60;
                $res = array("hour" => $hours,"min" => $mins,"sec" => $secs);
                if($res['hour'] < 10){
                    $res['hour'] = '0'.$res['hour'];
                }
                if($res['min'] < 10){
                    $res['min'] = '0'.$res['min'];
                }
                if($res['sec'] < 10){
                    $res['sec'] = '0'.$res['sec'];
                }
                $resdata = $res['hour'].':'.$res['min'].':'.$res['sec'];
                $data['live_time'] = $resdata;
                unset($data['end_time']);
                unset($data['start_time']);
                $this->ajaxReturn(['status' => 0, 'msg' => '提交成功', 'data' => $data]);
            }else{
                $this->ajaxReturn(['status' => -1 , 'msg'=>'房间不存在','data'=>'']);
            }

        }
    }




    /**
     * 用户视频直播
     */
    public function user_live()
    {

        $user_id = $this->get_user_id();
        if(!$user_id){
            $this->ajaxReturn(['status' => -1 , 'msg'=>'用户不存在','data'=>'']);
        }

        $room_id = input('get.room_id', 0);
        $goods_id = input('get.goods_id', 0);
        if (empty($room_id)) {
            $this->ajaxReturn(['status' => -1 , 'msg'=>'参数有误','data'=>'']);
        }
        $room = Db::name('user_video')->where(['room_id' => $room_id])->find();
        $userinfo = Db::name('users')->field('user_id,nickname,head_pic')->where(['user_id' => $user_id])->find();
        $data['userinfo'] = $userinfo;
        if (empty($room)) {
            $this->ajaxReturn(['status' => -1 , 'msg'=>'不存在的直播请到直播间列表','data'=>'']);
        }
        if ($room['status'] == 2) { //如果主播已结束，跳转到结束页面
            $this->ajaxReturn(['status' => -2 , 'msg'=>'主播已结束','data'=>'']);
        }

        $http_type = ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https')) ? 'https://' : 'http://';
        $url = $http_type . $_SERVER['SERVER_NAME'];

        //获取礼物列表
        $giftList = Db::name('live_gift')->where(['delete_time' => ['EXP', 'IS NULL']])->order('sort asc')->select();
        foreach ($giftList as $k=>$v){
            $giftList[$k]['image'] = SITE_URL.$giftList[$k]['image'];
        }
        $data['giftlist'] = $giftList;

        //主播的用户名  主播图片
        $zhubo = Db::name('users')->field('user_id,nickname,head_pic')->where(['user_id' => $room['user_id']])->find();
        $zhubo['head_pic'] = $zhubo['head_pic'];
        $data['zhuobo'] = $zhubo;

        $this->ajaxReturn(['status' => 0, 'msg' => '获取成功', 'data' => $data]);
    }

    /**
     * 用户点击领取红包
     */

    public function click_red_packet()
    {

        $room_id = input('post.room_id', 0); //房间id
        $users_id = input('post.users_id', 0); //发包人id
        $m_id = input('post.m_id', 0); //红包主表id
        if (empty($users_id) || empty($room_id) || empty($m_id)) {
            $this->ajaxReturn(['status' => -1, 'msg' => '参数错误', 'data' => '']);
        }

        $userId = $this->get_user_id();
        if(!$userId){
            $this->ajaxReturn(['status' => -1 , 'msg'=>'用户不存在','data'=>'']);
        }
        //判断用户是否已经抢过红包
        $if_red = Db::name('red_detail')->where(['get_uid' => $userId, 'm_id' => $m_id, 'room_id' => $room_id])->find();
        if ($if_red) {
            $this->ajaxReturn(['status' => -1 , 'msg'=>'已抢过红包!!!','data'=>'']);
        }
        //事务处理
//        Db::startTrans();
        //获取红包从表信息
        $red_master_find = $this->red_master_find($room_id, $m_id);

        if (!$red_master_find) {
            $this->ajaxReturn(['status' => -1 , 'msg'=>'事务处理失败','data'=>'']);
        }
        $red_detail_find = Db::name('red_detail')->where(['m_id' => $m_id, 'type' => 0, 'room_id' => $room_id])->find();

        if (!$red_detail_find) {
            $all_get_master = Db::name('red_master')->where(['id' => $m_id, 'room_id' => $room_id])->update(['all_get' => 1]);
            if (!$all_get_master) {
                $this->ajaxReturn(['status' => -1 , 'msg'=>'红包已领完!!!','data'=>'']);
            }
            $this->ajaxReturn(['status' => -1 , 'msg'=>'红包已领完!!!','data'=>'']);
        }
        //获取抢包用户信息
        $user_data = $this->user($userId);
        $data = ['get_uid' => $user_data['user_id'], 'type' => 1, 'get_award_money' => $red_detail_find['money']];

        $result = Db::name('red_detail')->where(['m_id' => $m_id, 'id' => $red_detail_find['id'], 'room_id' => $room_id])->update($data);
        if (!$result) {
            $this->ajaxReturn(['status' => -1 , 'msg'=>'事务处理失败','data'=>'']);
        }

        $user_money = bcadd($user_data['user_money'], $red_detail_find['money'], 2);

        //增加抢包用户余额的钱
        $result_money = Db::name('users')->where(['user_id' => $user_data['user_id']])->update(['user_money' => $user_money]);
        if (!$result_money) {
            $this->ajaxReturn(['status' => -1 , 'msg'=>'事务处理失败','data'=>'']);

        }
        //添加消费记录
        accountLog($userId, $red_detail_find['money'], 0,  '领取红包', 0, $red_detail_find, time());
        Db::commit();
        $money = bcadd($red_detail_find['money'], '0.00', 2);
        $user_find = Db::name("users")->where(['user_id' => $userId])->find();
        $message = array(
//            'type' => 'red_receive_user',
            'from_client_id' => $userId,
            'from_client_name' => $user_find['nickname'],
//            'to_client_id' => 'all',
            'moeny' => $money,
            'content' => $this->user->nickname . '领取了' . $money . '元红包',
            'time' => date('Y-m-d H:i:s'),
        );
        $this->ajaxReturn(['status' => 0, 'msg' => '提交成功', 'data' => $message]);
    }


    /**
     * 查找对应红包从表数据
     */
    public function red_master_find($room_id, $m_id)
    {
        $where = "room_id = '" . $room_id . "' and id = '" . $m_id . "' and all_get = 0";

        $red_user_find = Db::name("red_master")->where($where)->find();
        if ($red_user_find) {
            return $red_user_find;
        } else {
            return false;
        }
    }


    /**
     * 红包超时退回
     */
    public function sendBackRed()
    {

        // 获取所有大于5分钟的红包
        $key = $_GET['key'];

        if (!$key || $key != 'HGQ3keAjEyWPnT9AoutsCWFky99lbgKE') {
            echo 'no...';
            exit;
        }
        // dump($key);exit;
        $map['m.time_out'] = 0;
        $map['m.all_get'] = 0;
        $red_all = Db::name('red_master')->alias('m')
            ->field('m.id,m.uid,m.room_id,m.num,m.money,m.create_time,m.time_out,m.all_get')
            ->where('m.time_out', 0)
            ->select();
        // 如果超时退回标记主表time_out=1 以及从表type=2，并且统计红包是否全部领取，如果全部领取标记主表all_get=1
        $out_time = 350; // 过期时间
        $i = 0;
        if ($red_all) {
            foreach ($red_all as $k => $v) {
                // 判断当前时间是否大于等于红包创建时间+过期时间
                if (time() >= $v['create_time'] + $out_time) {
                    // 根据当前主表id获取从表没被抢的红包记录 统计没被领取红包总金额
                    $no_get_money = Db::name('red_detail')->where(['m_id' => $v['id'], 'type' => 0])->sum('money');
                    // dump($v['uid']);
                    // 退还金额到对应用户
                    if ($no_get_money) {
                        $out_money_res = Db::name('users')->where(['user_id' => $v['uid']])->setInc('user_money', $no_get_money);
                        if ($out_money_res) {
                            // dump($out_money_res);
                            // 修改状态
                            $out_update_res = Db::name('red_detail')->where(['m_id' => $v['id'], 'type' => 0])->update(['type' => 2, 'out_time' => time()]);
                            $out_update_res2 = Db::name('red_master')->where(['id' => $v['id']])->update(['time_out' => 1, 'all_get' => 1]);
                            // 插入日志
                            $out_money_log = [
                                'from_id' => $v['uid'],
                                'uid' => $v['uid'],
                                'm_id' => $v['id'],
                                'red_money' => $v['money'],
                                'money' => $no_get_money,
                                'type' => 12,
                                'create_time' => time(),
                                'remake' => '红包退回'
                            ];
                            $out_money_log_res = Db::name('red_log')->insert($out_money_log);
                        } else {
                            // echo 'out red update red err\n';
                        }
                    } else {
                        // 修改主表标记全部领取
                        $out_update_res3 = Db::name('red_master')->where(['id' => $v['id']])->update(['all_get' => 1]);
                        continue;
                    }
                }
                $i++;
            }
            echo 'out red ' . $i;
        } else {
            echo 'no order\n';
            exit;
        }
    }


    /**
     * 用户发红包
     */
    public function user_send_red() {
        $room_id = input('post.room_id', 0);
        $money_input = input('post.money', 0);
        if (empty($room_id) || empty($money_input)) {
            $this->ajaxReturn(['status' => -1 , 'msg'=>'参数有误','data'=>'']);
        }

        $room = Db::name('user_video')->where(['room_id' => $room_id])->find();
        if (empty($room)) { //不存在直播；跳转到直播间列表
            $this->ajaxReturn(['status' => -1 , 'msg'=>'不存在的直播','data'=>'']);
        }
        if ($room['status'] == 2) { //如果主播已结束，跳转到结束页面
            $this->ajaxReturn(['status' => -1 , 'msg'=>'直播已结束','data'=>'']);
        }

        $money = bcadd($money_input, '0.00', 2);
        if ($money < 0 || $money != $money_input) {
            $this->ajaxReturn(['status' => -1 , 'msg'=>'金融格式不对','data'=>'']);
        }

        $userId = $this->get_user_id();
        $user = Db::name('users')->where(['user_id' => $userId])->find();
        $koujian = bcsub($user['user_money'], $money, 2);
        if ($koujian < 0) {
            $this->ajaxReturn(['status' => -1 , 'msg'=>'余额不足','data'=>'']);
        }
        //事务处理
        Db::startTrans();
        //剩余的用户钱
        $user_money = bcsub($user['user_money'], $money, 2);
        //扣减用户余额的钱
        $result = Db::name('users')->where(['user_id' => $userId])->update(['user_money' => $user_money]);
        if (!$result) {
            Db::rollback();
            $this->ajaxReturn(['status' => -1 , 'msg'=>'事务处理失败1','data'=>'']);

        }

        //增加主播余额的钱
        $zhobuVideo = Db::name('user_video')->where(['room_id' => $room_id])->find();
        if (!$zhobuVideo) {
            Db::rollback();
            $this->ajaxReturn(['status' => -1 , 'msg'=>'主播直播间不存在','data'=>'']);
        }
        $zhubo_user_id = $zhobuVideo['user_id'];
        $zhobuUser = Db::name('users')->where(['user_id' => $zhubo_user_id])->find();
        $user_money = bcadd($zhobuUser['user_money'], $money, 2);
        //增加主播余额的钱
        $result = Db::name('users')->where(['user_id' => $zhubo_user_id])->update(['user_money' => $user_money]);
        if (!$result) {
            Db::rollback();
            $this->ajaxReturn(['status' => -1 , 'msg'=>'事务处理失败2','data'=>'']);
        }
        //新增用户给主播发红包流水
        $data = [
            'user_id' => $userId,
            'to_user_id' => $zhubo_user_id,
            'room_id' => $room_id,
            'money' => $money,
            'data' => "【{$userId}:{$this->user->nickname}】给主播【{$zhubo_user_id}:{$zhobuUser['nickname']}】发了【{$money}】的红包",
            'create_time' => time(),
        ];
        $result = Db::name('live_red_sending_log')->insert($data);
        if (!$result) {
            Db::rollback();
            $this->ajaxReturn(['status' => -1 , 'msg'=>'事务处理失败3','data'=>'']);
        }

        //修改用户的金额
        $updateMoney = bcadd($zhobuVideo['money'], $money, 2);
        $result = Db::name('user_video')->where(['user_id' => $zhubo_user_id])->update(['money' => $updateMoney]);
        if (!$result) {
            Db::rollback();
            $this->ajaxReturn(['status' => -1 , 'msg'=>'事务处理失败4','data'=>'']);
        }
        //添加消费记录
        accountLog($userId, -$money, 0,  '给主播发红包', 0, $result, time());
        accountLog($zhubo_user_id, $money, 0,  '用户给主播发红包', 0, $result, time());

        Db::commit();
        $user_find = Db::name("users")->where(['user_id' => $userId])->find();
        $message = array(
//            'type' => 'say',
            'from_client_id' => $userId,
            'from_client_name' => $user_find['nickname'],
//            'to_client_id' => 'all',
            'content' => '给主播发了' . $money . '红包',
            'time' => date('Y-m-d H:i:s'),
        );
        $this->ajaxReturn(['status' => 0 , 'msg'=>'提交成功','data'=>$message]);
    }

    /**
     * 用户发礼物
     */
    public function user_send_gift(){
        $room_id = input('post.room_id', 0);
        //上线后去掉默认值  add by zgp
        $gift_id = input('post.gift_id', 0);
        if (empty($room_id) || empty($gift_id)) {
            $this->ajaxReturn(['status' => -1 , 'msg'=>'参数错误','data'=>'']);
        }

        $room = Db::name('user_video')->where(['room_id' => $room_id])->find();
        if (empty($room)) { //不存在直播；跳转到直播间列表
            $this->ajaxReturn(['status' => -1 , 'msg'=>'不存在直播','data'=>'']);
        }
        if ($room['status'] == 2) { //如果主播已结束，跳转到结束页面
            $this->ajaxReturn(['status' => -1 , 'msg'=>'直播已结束','data'=>'']);

        }

        $userId = $this->get_user_id();
        $user = Db::name('users')->where(['user_id' => $userId])->find();
        $money = 0;
        //获取礼物的价格
        $gift = Db::name('live_gift')->where(['id' => $gift_id])->find();
        if (empty($gift)) {
            $this->ajaxReturn(['status' => -1 , 'msg'=>'礼物不存在','data'=>'']);
        }
        $money = $gift['price'];
        $koujian = bcsub($user['user_money'], $money, 2);
        if ($koujian < 0) {
            $this->ajaxReturn(['status' => -1 , 'msg'=>'余额不足','data'=>'']);
        }
        //事务处理
        Db::startTrans();
        //剩余的用户钱
        $user_money = bcsub($user['user_money'], $money, 2);
        //扣减用户余额的钱
        $result = Db::name('users')->where(['user_id' => $userId])->update(['user_money' => $user_money]);
        if (!$result) {
            Db::rollback();
            $this->ajaxReturn(['status' => 0 , 'msg'=>'事务处理失败','data'=>'']);
        }

        //新增用户给主播发礼物流水
        $zhubo_user_id = 0;
        $zhobuInfo = Db::name('user_video')->where(['room_id' => $room_id])->find();
        $zhubo_user_id = $zhobuInfo['user_id'];
        $zhobuInfo = Db::name('users')->where(['user_id' => $zhubo_user_id])->find();
        $data = [
            'gift_id' => $gift_id,
            'user_id' => $userId,
            'to_user_id' => $zhubo_user_id,
            'room_id' => $room_id,
            'data' => "【{$userId}:{$this->user->nickname}】给【{$zhubo_user_id}:{$zhobuInfo['nickname']}】发价值【{$gift['price']}】的【{$gift_id}:{$gift['name']}】礼物",
            'create_time' => time(),
        ];
        $result = Db::name('live_gift_sending_log')->insert($data);
        if (!$result) {
            Db::rollback();
            $this->ajaxReturn(['status' => -1 , 'msg'=>'事务处理失败2','data'=>'']);
        }

        Db::commit();
        $user_find = Db::name("users")->where(['user_id' => $userId])->find();
        $message = array(
//            'type' => 'gift',
            'from_client_id' => $userId,
            'from_client_name' => $user_find['nickname'] ,
//            'to_client_id' => 'all',
            'gift_id' => $data['gift_id'],
            'content' => '给主播发了' . $gift['name'] . '礼物',
            'time' => date('Y-m-d H:i:s'),
        );
        $this->ajaxReturn(['status' => 0 , 'msg'=>'提交成功','data'=>$message]);
    }


    /***
     * 点赞
     */
    public  function like(){

        $room_id = I('post.room_id', 0);
        $room = Db::name('user_video')->where(['room_id' => $room_id, 'status' => 1])->find();
        if (empty($room)) {
            $this->ajaxReturn(['status' => -1 , 'msg'=>'不存在的直播间','data'=>'']);
        }

        $user_id = $this->get_user_id();
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
                $message['msg'] = '点赞成功';
                $message['count'] = $user_video['top_amount'];
                $this->ajaxReturn(['status' => 0 , 'msg'=>'提交成功','data'=>$message]);
            } else {
                Db::rollback();
                $this->ajaxReturn(['status' => -1 , 'msg'=>'点赞失败','data'=>'']);
            }
        }
        $this->ajaxReturn(['status' => -1 , 'msg'=>'已点赞','data'=>'']);
    }



    /**
     * 获取点赞人数
     */

    public function userTopAmount(){
        $room_id = I('post.room_id', 1);
        $room = Db::name('user_video')->where(['room_id' => $room_id, 'status' => 1])->find();
        if (empty($room)) {
            $this->ajaxReturn(['status' => -1 , 'msg'=>'不存在的直播间','data'=>'']);
        }
        $room = Db::name('user_video')->where(['room_id' => $room_id, 'status' => 1])->find();
        $data['count'] = $room['top_amount'];
        $this->ajaxReturn(['status' => 0 , 'msg'=>'获取成功','data'=>$data]);
    }



    /**
     * 观看人数加1
     */
    public function userLookAmount(){
        $room_id = I('post.room_id', 0);
        Db::name('user_video')->where(['room_id' => $room_id])->setInc('look_amount');
    }


    /**
     * 身份证上传
     */
    public function update_icard_pic()
    {
        $user_id = $this->get_user_id();
//        $user_id = 57534;
        if($user_id!=""){
            // 获取表单上传文件 例如上传了001.jpg
            $file = request()->file('picfront');
            // 移动到框架应用根目录/uploads/ 目录下
            $info = $file->validate(['size'=>2048000000,'ext'=>'jpg,png,gif']);
            $info = $file->rule('md5')->move(ROOT_PATH . DS.'public/upload');//加密->保存路径
            if($file){
                // 成功上传后 获取上传信息
                // 输出 jpg
                // echo $info->getExtension();
                // 输出 20160820/42a79759f284b767dfcb2a0197904287.jpg
                // echo $info->getSaveName();
                // 输出 42a79759f284b767dfcb2a0197904287.jpg
                $data['pic_front'] = SITE_URL.'/public/upload/'.$info->getSaveName(); //输出路径
                // ROOT_PATH .DS.

                // 存着 地址
                M('user_verify_identity_info')->where(['user_id' => $user_id])->update($data);

            }else{
                $this->ajaxReturn(['status' => -2 , 'msg'=>'上传失败','data'=>$file->getError()]);
            }


            // 获取表单上传文件 例如上传了001.jpg
            $files = request()->file('picback');
            // 移动到框架应用根目录/uploads/ 目录下
            $infos = $files->validate(['size'=>204800000000,'ext'=>'jpg,png,gif']);
            $infos = $files->rule('md5')->move(ROOT_PATH . DS.'public/upload');//加密->保存路径
            if($files){
                // 成功上传后 获取上传信息
                // 输出 jpg
                // echo $info->getExtension();
                // 输出 20160820/42a79759f284b767dfcb2a0197904287.jpg
                // echo $info->getSaveName();
                // 输出 42a79759f284b767dfcb2a0197904287.jpg
                $data['pic_back'] = SITE_URL.'/public/upload/'.$infos->getSaveName(); //输出路径
                // ROOT_PATH .DS.

                // 存着 地址
                M('user_verify_identity_info')->where(['user_id' => $user_id])->update($data);

            }else{
                $this->ajaxReturn(['status' => -2 , 'msg'=>'上传失败','data'=>$files->getError()]);
            }


        }
        $this->ajaxReturn(['status' => 0 , 'msg'=>'上传成功','data'=>$data]);



//
//        $user_id = $this->get_user_id();
//        if (!$user_id) {
//            $this->ajaxReturn(['status' => -1, 'msg' => '用户不存在', 'data' => '']);
//        }
//
//        if ($_FILES['pic_front']['tmp_name']) {
//            $file = $this->request->file('pic_front');
//            $image_upload_limit_size = config('image_upload_limit_size');
//            $validate = ['size'=>$image_upload_limit_size,'ext'=>'jpg,png,gif,jpeg'];
//            $dir = UPLOAD_PATH.'pic/';
//            if (!($_exists = file_exists($dir))){
//                $isMk = mkdir($dir);
//            }
//            $parentDir = date('Ymd');
//            $info = $file->validate($validate)->move($dir, true);
//            if($info){
//                $post['pic_front'] = SITE_URL . '/'.$dir.$parentDir.'/'.$info->getFilename();
//
//            }else{
//                // $this->error($file->getError());//上传错误提示错误信息
//                $this->ajaxReturn(['status' => -1 , 'msg'=>'上传错误','data'=>'']);
//            }
//        }
//
//        if ($_FILES['pic_back']['tmp_name']) {
//            $file = $this->request->file('pic_back');
//            $image_upload_limit_size = config('image_upload_limit_size');
//            $validate = ['size'=>$image_upload_limit_size,'ext'=>'jpg,png,gif,jpeg'];
//            $dir = UPLOAD_PATH.'pic/';
//            if (!($_exists = file_exists($dir))){
//                $isMk = mkdir($dir);
//            }
//            $parentDir = date('Ymd');
//            $info = $file->validate($validate)->move($dir, true);
//            if($info){
//                $post['pic_back'] = SITE_URL . '/'.$dir.$parentDir.'/'.$info->getFilename();
//
//            }else{
//                // $this->error($file->getError());//上传错误提示错误信息
//                $this->ajaxReturn(['status' => -1 , 'msg'=>'上传错误','data'=>'']);
//            }
//        }
//
//        $this->ajaxReturn(['status' => 0, 'msg' => '提交成功', 'data' => $post]);


//        $user_id = $this->get_user_id();
//        if (!$user_id) {
//            $this->ajaxReturn(['status' => -1, 'msg' => '用户不存在', 'data' => '']);
//        }
//        print_r(request()->file);die;
//        if ($user_id != "") {
//                // 身份证正面
//                $pic_front = request()->file('pic_front');
//                print_r($pic_front);die;
//                $save_url = UPLOAD_PATH . 'idcard/' . date('Y', time()) . '/' . date('m-d', time());
//                if ($pic_front) {
//                    // 移动到框架应用根目录/public/uploads/ 目录下
//                    $image_upload_limit_size = config('image_upload_limit_size');
//                    $info = $pic_front->rule('uniqid')->validate(['size' => $image_upload_limit_size, 'ext' => 'jpg,png,gif,jpeg'])->move($save_url);
//                    if ($info) {
//                        // 成功上传后 获取上传信息
//                        // 输出 jpg
//                        $comment_img = '/' . $save_url . '/' . $info->getFilename();
//                    } else {
//                        // 上传失败获取错误信息
//                        $this->ajaxReturn(['status' => -1, 'msg' => $pic_front->getError()]);
//                    }
//                    $data['pic_front'] = $comment_img;
//                }
//
//                $pic_back = request()->file('pic_back');
//                $save_url = UPLOAD_PATH . 'idcard/' . date('Y', time()) . '/' . date('m-d', time());
//                if ($pic_back) {
//                    // 移动到框架应用根目录/public/uploads/ 目录下
//                    $image_upload_limit_size = config('image_upload_limit_size');
//                    $info = $pic_back->rule('uniqid')->validate(['size' => $image_upload_limit_size, 'ext' => 'jpg,png,gif,jpeg'])->move($save_url);
//                    if ($info) {
//                        // 成功上传后 获取上传信息
//                        // 输出 jpg
//                        $comment_img1 = '/' . $save_url . '/' . $info->getFilename();
//                    } else {
//                        // 上传失败获取错误信息
//                        $this->ajaxReturn(['status' => -1, 'msg' => $pic_back->getError()]);
//                    }
//                    $data['pic_back'] = $comment_img1;
//                }
//
////        存入表
//            $rel = M('user_verify_identity_info')->field('user_id')->where(['user_id' => $user_id])->find();
//            if ($rel) {
//                $res = M('user_verify_identity_info')->where(['user_id' => $user_id])->update($data);
//            } else {
//                $res = M('user_verify_identity_info')->insert($data);
//            }
//
//            if ($res) {
//                $data['pic_back'] = SITE_URL . $comment_img1;
//                $data['pic_front'] = SITE_URL . $comment_img;
//                $this->ajaxReturn(['status' => 0, 'msg' => '提交成功', 'data' => $data]);
//            } else {
//                $this->ajaxReturn(['status' => -2, 'msg' => '提交失败', 'data' => $pic_front->getError()]);
//            }
////        }
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
