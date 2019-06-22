<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/6/14 0014
 * Time: 11:31
 */

namespace app\live\controller;

use app\common\model\UserVideo;
use app\live\service\AccessToken;
use app\live\service\RtmTokenBuilder;
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
        $identity = Db::name('user_verify_identity_info')->where(['user_id' => $user_id, 'verify_state' => 1])->find();
        !$identity && $this->redirect('/Live/Apply');

        // 没有正在直播的，跳转设置直播信息
        $room = Db::name('user_video')->where(['user_id' => $user_id, 'status' => 1])->order('id desc')->find();
        !$room && $this->redirect('/Live/Index/set');

        $this->assign('room_id', $room['room_id']);
        $this->assign('user_id', $user_id . time());
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
        $this->assign('videoList', $list);
        $this->assign('count', $count);//总条数
        $this->assign('page_count', $page_count);//页数
        $this->assign('current_count', $page_count * I('p'));//当前条
        $this->assign('p', I('p'));//页数
        return $this->fetch();
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

        $room = Db::name('user_video')->where(['user_id' => $user_id, 'status' => 1])->find();
        if (empty($room)) {
            return $this->failResult('不存在的直播间', 301);
        }
        $identity['pic_head'] = $this->user['head_pic'];
        $identity['pic_fengmian'] = $this->url . $identity['pic_fengmian'];
        $this->assign('identity', $identity);
        $this->assign('room', $room);
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

}