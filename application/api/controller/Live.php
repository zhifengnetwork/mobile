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
        if (IS_POST) {

            $data['uid'] = $user_id;
            $data['room_id'] = I('post.room_id');
            $data['money'] = I('total_money');
            $data['num'] = I('red_number');
            $data['create_time'] = time();
            $rel = M('red_master')->insert($data);

            if ($rel){
                $this->ajaxReturn(['status' => 0, 'msg' => '提交成功', 'data' => $data]);
            }

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

//        $user_id = $this->get_user_id();
        $user_id= 57580;
        if(!$user_id){
            $this->ajaxReturn(['status' => -1 , 'msg'=>'用户不存在','data'=>'']);
        }

        $room_id = input('get.room_id', 0);
        $goods_id = input('get.goods_id', 0);
        if (empty($room_id) || empty($goods_id)) {
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
        $goods_url = $url . '/Mobile/Goods/goodsInfo/id/' . $goods_id . '.html?zhubo_id=' . $room['user_id'];
        $data['goods_url'] = $goods_url;
        //获取礼物列表
        $giftList = Db::name('live_gift')->where(['delete_time' => ['EXP', 'IS NULL']])->order('sort asc')->select();
        foreach ($giftList as $k=>$v){
            $giftList[$k]['image'] = SITE_URL.$giftList[$k]['image'];
        }
        $data['giftlist'] = $giftList;

        //主播的用户名  主播图片
        $zhubo = Db::name('users')->field('user_id,nickname,head_pic')->where(['user_id' => $room['user_id']])->find();
        $zhubo['head_pic'] = SITE_URL.$zhubo['head_pic'];
        $data['zhuobo'] = $zhubo;

        $this->ajaxReturn(['status' => 0, 'msg' => '提交成功', 'data' => $data]);
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
            return $this->failResult('参数有误', 301);
        }

//        $userId = $this->get_user_id();
        $userId = 57580;
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
        print_r($red_master_find);die;
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
        $message = array(
            'type' => 'red_receive_user',
            'from_client_id' => $userId,
            'from_client_name' => $this->user->nickname,
            'to_client_id' => 'all',
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
}
