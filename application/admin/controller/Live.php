<?php

namespace app\admin\controller;

use app\common\model\UserVerifyIdentityInfo;
use app\common\model\UserVideo;
use think\AjaxPage;
use think\Db;


class Live extends Base
{
    public function _initialize()
    {
        parent::_initialize();
    }

    /**
     * 直播 - 主播列表
     */
    public function info_list()
    {
        return $this->fetch();
    }

    public function ajax_info_list()
    {
        $where = ' 1 = 1 ';
        $keywords = I('keywords') ? trim(I('keywords')) : '';
        if ($keywords) {
            $where = "$where and (name like '%$keywords%' or mobile like '%$keywords%')";
        }
        (I('verify_state/d') !== '') && $where = "$where and verify_state = " . I('verify_state');

        $count = D('user_verify_identity_info')->where($where)->count();
        $Page = new AjaxPage($count, 20);
        $show = $Page->show();
        $list = D('user_verify_identity_info')->where($where)->order('id desc')->limit($Page->firstRow . ',' . $Page->listRows)->select();

        $this->assign('list', $list);
        $this->assign('page', $show);
        return $this->fetch();
    }

    /**
     * 直播 - 主播列表 - 查看/审核
     *
     */
    public function info()
    {
        $id = I('get.id/d', 0);
        if ($id) {
            $info = (new UserVerifyIdentityInfo)->where('id', $id)->find();
            if (!$info) {
                return $this->fetch('public/error');
            }
            $this->assign('info', $info);
        }
        return $this->fetch();
    }

    /**
     * 直播 - 主播列表 - 审核操作
     */
    public function verify()
    {
        $id = I('post.id/d', 0);
        if (!$id) $this->ajaxReturn(['status' => -1, 'msg' => '此信息不可审核']);

        $info = Db::name('user_verify_identity_info')->where('id', $id)->find();
        if (!$info || $info['status'] != 0) {
            $this->ajaxReturn(['status' => -1, 'msg' => '此信息不可审核']);
        }
        $status = input('post.verify_status/d');
        $reason = input('post.reason/s');

        Db::startTrans();
        $result = Db::name('user_verify_identity_info')->where('id', $id)->save(['verify_state' => $status]);
        if (!$result) {
            Db::rollback();
            return $this->ajaxReturn(['status' => -1, 'msg' => '设置失败']);
        }
        //新增认证日志
        $logData = array(
            'verify_id' => $id,
            'verify_state' => $status,
            'reason_cn' => $reason,
            'admin_id' => $this->admin_id,
            'create_time' => time(),
        );
        if (!($result = Db::name('user_verify_identity_log')->insert($logData))) {
            Db::rollback();
            return $this->ajaxReturn(['status' => -1, 'msg' => '设置失败']);
        }
        Db::commit();
        $this->ajaxReturn(['status' => 1, 'msg' => '操作成功', 'url' => U('Admin/Live/info', array('id' => $id))]);
    }

    /**
     * 直播 - 主播列表 - 删除
     */
    public function infoHandle()
    {
        $data = I('post.');
        if ($data['act'] == 'del' && $data['id'] > 1) {
            $r = D('user_verify_identity')->where('id', $data['id'])->delete();
        }
        if ($r) {
            $this->ajaxReturn(['status' => 1, 'msg' => '操作成功', 'url' => U('Admin/Live/info_list')]);
        } else {
            $this->ajaxReturn(['status' => -1, 'msg' => '操作失败']);
        }
    }

    public function video_list()
    {
        return $this->fetch();
    }

    public function ajax_video_list()
    {
        $where = ' 1 = 1 ';
        $keywords = I('keywords') ? trim(I('keywords')) : '';
        if ($keywords) {
            $where = "$where and (name like '%$keywords%' or mobile like '%$keywords%')";
        }
        (I('status/d') !== '') && $where = "$where and status = " . I('status/d');

        $userVideo = new UserVideo;
        $count = $userVideo->where($where)->count();
        $page = new AjaxPage($count, 20);
        $list = $userVideo->where($where)->order('id desc')
            ->limit($page->firstRow . ',' . $page->listRows)
            ->select();

        $this->assign('list', $list);
        $this->assign('page', $page->show());
        return $this->fetch();
    }

    public function video()
    {
        $id = I('get.id/d', 0);
        if ($id) {
            if (!($video = (new UserVideo)->where('id', $id)->find())) {
                return $this->fetch('public/error');
            }
            $this->assign('video', $video);
        }
        return $this->fetch();
    }
}