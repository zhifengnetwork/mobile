<?php

namespace app\admin\controller;

use app\common\model\LiveGift;
use app\common\model\LiveGiftSendingLog;
use app\common\model\LiveLike;
use app\common\model\LiveRedSendingLog;
use app\common\model\RedDetail;
use app\common\model\RedMaster;
use app\common\model\UserVerifyIdentityInfo;
use app\common\model\UserVideo;
use think\Db;
use think\Loader;
use think\Page;
use think\Request;


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
        $where = ' 1 = 1 ';
        $keywords = I('keywords') ? trim(I('keywords')) : '';
        if ($keywords) {
            $where = "$where and (name like '%$keywords%' or mobile like '%$keywords%')";
        }
        (I('verify_state/d') !== '') && $where = "$where and verify_state = " . I('verify_state');

        $count = D('user_verify_identity_info')->where($where)->count();
        $page = new Page($count, 20);
        $show = $page->show();
        $list = D('user_verify_identity_info')->where($where)->order('id desc')->limit($page->firstRow . ',' . $page->listRows)->select();

        $this->assign(['list' => $list, 'page' => $show, 'pager' => $page]);
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
        $where = ' 1 = 1 ';
        $keywords = I('keywords') ? trim(I('keywords')) : '';
        if ($keywords) {
            $where = "$where and (name like '%$keywords%' or mobile like '%$keywords%')";
        }
        (I('status/d') !== '') && $where = "$where and status = " . I('status/d');

        $userVideo = new UserVideo;
        $count = $userVideo->where($where)->count();
        $page = new Page($count, 20);
        $list = $userVideo->where($where)->order('id desc')
            ->limit($page->firstRow . ',' . $page->listRows)
            ->select();

        $this->assign(['list' => $list, 'page' => $page->show(), 'pager' => $page]);
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

    public function gift_list()
    {
        $where = ' 1 = 1';
        $keywords = I('keywords') ? trim(I('keywords')) : '';
        $keywords && $where .= " and (name like '%$keywords%')";

        (I('is_show/d') !== '') && $where .= " and is_show = " . I('is_show/d');

        $userVideo = new LiveGift();
        $count = $userVideo->where($where)->count();
        $page = new Page($count, 20);
        $list = $userVideo->where($where)->order('id desc')
            ->limit($page->firstRow . ',' . $page->listRows)
            ->select();

        $this->assign(['list' => $list, 'page' => $page->show(), 'pager' => $page]);
        return $this->fetch();
    }

    public function gift()
    {
        $id = I('id');
        if (IS_POST) {
            $data = I('post.');
            $validate = Loader::validate('LiveGift');
            if (!$validate->batch()->check($data)) {
                $this->ajaxReturn(['status' => 0, 'msg' => '操作失败', 'result' => $validate->getError()]);
            }

            $gift = new LiveGift();
            $id > 0 && $gift = $gift->where(['id' => $id]);
            if (!($gift->data($data)->save())) {
                $this->ajaxReturn(['status' => 0, 'msg' => '提交失败']);
            }
            $this->ajaxReturn(['status' => 1, 'msg' => '操作成功']);
        }
        $gift = M('LiveGift')->find($id);
        $this->assign('gift', $gift);
        return $this->fetch();
    }

    public function giftHandle()
    {
        $type = I('post.type');
        $ids = I('post.ids', '');
        if (!in_array($type, array('del', 'show', 'hide')) || empty($ids)) {
            $this->ajaxReturn(['status' => -1, 'msg' => '非法操作！']);
        }
        $ids = rtrim($ids, ',');
        $row = false;
        $gift = new LiveGift();
        if ($type == 'del') {
            // 软删除
            $row = $gift::destroy(explode(',', $ids));
        } elseif ($type == 'show') {
            $row = $gift->where('id', 'IN', $ids)->save(['is_show' => 1]);
        } elseif ($type == 'hide') {
            $row = $gift->where('id', 'IN', $ids)->save(['is_show' => 0]);
        }
        if ($row) {
            $this->ajaxReturn(['status' => 1, 'msg' => '操作完成', 'url' => U('Admin/Live/gift_list')]);
        } else {
            $this->ajaxReturn(['status' => -1, 'msg' => '操作失败', 'url' => U('Admin/Live/gift_list')]);
        }
    }

    public function gift_log()
    {
        $where = ' 1 = 1';
        $keywords = I('keywords') ? trim(I('keywords')) : '';
        $keywords && $where .= " and (room_id like '%$keywords%')";

        $roomId = I('room_id');
        ($roomId != '') && $where .= " and (room_id = $roomId)";

        $log = new LiveGiftSendingLog();
        $count = $log->where($where)->count();
        $page = new Page($count, 20);
        $list = $log->where($where)->order('id desc')
            ->limit($page->firstRow . ',' . $page->listRows)
            ->select();

        $this->assign(['list' => $list, 'page' => $page->show(), 'pager' => $page, 'room_id' => $roomId]);
        return $this->fetch();
    }

    public function gift_log_detail()
    {
        $id = I('get.id/d', 0);
        if ($id) {
            if (!($detail = (new LiveGiftSendingLog)->where('id', $id)->find())) {
                return $this->fetch('public/error');
            }
            $this->assign('detail', $detail);
        }
        return $this->fetch();
    }


    public function like_log()
    {
        $where = ' 1 = 1';
        $keywords = I('keywords') ? trim(I('keywords')) : '';
        $keywords && $where .= " and (name like '%$keywords%')";

        $roomId = I('room_id');
        ($roomId != '') && $where .= " and (room_id = $roomId)";

        $like = new LiveLike();
        $count = $like->where($where)->count();
        $page = new Page($count, 20);
        $list = $like->where($where)->order('id desc')
            ->limit($page->firstRow . ',' . $page->listRows)
            ->select();

        $this->assign(['list' => $list, 'page' => $page->show(), 'pager' => $page, 'room_id' => $roomId]);
        return $this->fetch();
    }

    public function commission_rate()
    {

        $info = Db::name('commission_rate')->find();

        if (request()->isPost()) {
            $data = input('post.');

            if ($data['id']) {
                $res = Db::name('commission_rate')->update($data);
            } else {
                $res = Db::name('commission_rate')->insert($data);
            }

            if ($res !== false) {
                $this->ajaxReturn(['status' => 1, 'msg' => '操作成功！']);
            } else {
                $this->ajaxReturn(['status' => 0, 'msg' => '操作失败！']);
            }
        }

        return $this->fetch('', [
            'info' => $info,
        ]);
    }



    public function video_time()
    {

        $info = Db::name('config')->where(['name' => 'video_time'])->find();

        if (request()->isPost()) {
            $data = input('post.');
           
            $res = Db::name('config')->where(['name' => 'video_time'])->update($data);

            if ($res !== false) {
                $this->ajaxReturn(['status' => 1, 'msg' => '操作成功！']);
            } else {
                $this->ajaxReturn(['status' => 0, 'msg' => '操作失败！']);
            }
        }

        return $this->fetch('', [
            'info' => $info,
        ]);
    }

    public function red_log()
    {
        $where = ' 1 = 1';
        $keywords = I('keywords') ? trim(I('keywords')) : '';
        $keywords && $where .= " and (room_id like '%$keywords%')";

        $roomId = I('room_id');
        ($roomId != '') && $where .= " and (room_id = $roomId)";

        $log = new RedMaster();
        $count = $log->where($where)->count();
        $page = new Page($count, 20);
        $list = $log->where($where)->order('id desc')
            ->limit($page->firstRow . ',' . $page->listRows)
            ->select();

        $this->assign(['list' => $list, 'page' => $page->show(), 'pager' => $page, 'room_id' => $roomId]);
        return $this->fetch();
    }

    public function red_detail()
    {
        $id = $_GET['id'];
        $where = " m_id = $id";

        $log = new RedDetail();
        $count = $log->where($where)->count();
        $page = new Page($count, 20);
        $list = $log->where($where)->order('id desc')
            ->limit($page->firstRow . ',' . $page->listRows)
            ->select();

        $this->assign(['list' => $list, 'page' => $page->show(), 'pager' => $page, 'room_id' => $roomId]);
        return $this->fetch();
    }

    public function user_red_log()
    {
        $where = ' 1 = 1 ';
        $keywords = I('keywords') ? trim(I('keywords')) : '';
        $keywords && $where .= " and (room_id like '%$keywords%')";

        $roomId = I('room_id');
        ($roomId != '') && $where .= " and (room_id = $roomId)";

        $log = new LiveRedSendingLog();
        $count = $log->where($where)->count();
        $page = new Page($count, 20);
        $list = $log->where($where)->order('id desc')
            ->limit($page->firstRow . ',' . $page->listRows)
            ->select();

        $this->assign(['list' => $list, 'page' => $page->show(), 'pager' => $page, 'room_id' => $roomId]);
        return $this->fetch();
    }

}