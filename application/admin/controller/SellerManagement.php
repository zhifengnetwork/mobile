<?php

namespace app\admin\controller;

use app\admin\logic\GoodsLogic;
use app\common\model\Order;
use think\Db;
use think\Page;

class SellerManagement extends Base
{
    public function _initialize()
    {
        parent::_initialize();
    }

    /**
     * 商家管理 - 商家列表
     */
    public function seller_list()
    {
        $list = array();
        $keywords = I('keywords/s');
        if (empty($keywords)) {
            $res = D('seller')->select();
        } else {
            $res = DB::name('seller')->where('user_name', 'like', '%' . $keywords . '%')->order('seller_id')->select();
        }
        foreach ($res as $val) {
            $val['add_time'] = date('Y-m-d H:i:s', $val['add_time']);
            $val['last_login'] = date('Y-m-d H:i:s', $val['last_login']);
            $list[] = $val;
        }
        $this->assign('list', $list);
        return $this->fetch();
    }

    /**
     * 商家管理 - 商家列表 - 添加商家(商家审核)
     */
    public function seller_info()
    {
        $seller_id = I('get.seller_id/d', 0);
        if ($seller_id) {
            $info = Db::name('seller')->where("seller_id", $seller_id)->find();
            $info['password'] = "";
            $this->assign('info', $info);
        }
        $act = empty($seller_id) ? 'add' : 'edit';
        $this->assign('act', $act);
        return $this->fetch();
    }

    /**
     * 商家管理 - 商家列表 - 添加商家(商家审核)
     */
    public function sellerHandle()
    {
        $data = I('post.');
        if (empty($data['seller_phone'])) {
            unset($data['seller_phone']);
        }
        if (empty($data['seller_mobile'])) {
            unset($data['seller_mobile']);
        }
        if (empty($data['password'])) {
            unset($data['password']);
        } else {
            $data['password'] = encrypt($data['password']);
        }
        if ($data['act'] == 'add') {
            $count = D('seller')->where('user_name', $data['user_name'])->count();
            if ($count) {
                $this->ajaxReturn(['status' => -1, 'msg' => '此商家登录名已存在，请更换']);
            }
            $count = D('seller')->where('seller_name', $data['seller_name'])->count();
            if ($count) {
                $this->ajaxReturn(['status' => -1, 'msg' => '此商家名称已被使用，请更换']);
            }
            unset($data['seller_id']);
            $data['status'] = 10;
            $data['add_time'] = time();
            $r = D('seller')->add($data);
        }
        if ($data['act'] == 'edit') {
            $count = D('seller')->where(['user_name' => $data['user_name'], 'seller_id' => ['neq', $data['seller_id']]])->count();
            if ($count) {
                $this->ajaxReturn(['status' => -1, 'msg' => '此商家登录名已存在，请更换']);
            }
            $count = D('seller')->where(['seller_name' => $data['seller_name'], 'seller_id' => ['neq', $data['seller_id']]])->count();
            if ($count) {
                $this->ajaxReturn(['status' => -1, 'msg' => '此商家名称已被使用，请更换']);
            }
            $r = D('seller')->where('seller_id', $data['seller_id'])->save($data);
        }
        if ($data['act'] == 'del' && $data['seller_id'] > 1) {
            $r = D('seller')->where('seller_id', $data['seller_id'])->delete();
        }
        if ($r) {
            $this->ajaxReturn(['status' => 1, 'msg' => '操作成功', 'url' => U('Admin/SellerManagement/seller_list')]);
        } else {
            $this->ajaxReturn(['status' => -1, 'msg' => '操作失败']);
        }
    }

    /**
     * 商家管理 - 商家门店管理
     */
    public function store_list()
    {
        $list = array();
        $keywords = I('keywords/s');
        if (empty($keywords)) {
            $res = D('seller_store')->select();
        } else {
            $seller_key = DB::name('seller')->where(['user_name|seller_name' => ['like', '%' . $keywords . '%']])->getField('seller_id,seller_name');
            if (empty($seller_key)) {
                $res = DB::name('seller_store')->where(['store_name|webid|phone|address|city' => ['like', '%' . $keywords . '%']])->order('seller_id')->select();
            } else {
                $res = DB::name('seller_store')->where(['store_name|webid|phone|address|city' => ['like', '%' . $keywords . '%']])->whereOr(['seller_id' => ['in', array_keys($seller_key)]])->order('seller_id')->select();
            }
        }
        $seller = DB::name('seller')->getField('seller_id,seller_name');
        if ($seller && $res) {
            foreach ($res as $val) {
                $val['seller_name'] = $seller[$val['seller_id']];
                $val['add_time'] = date('Y-m-d H:i:s', $val['add_time']);
                $list[] = $val;
            }
        }
        $this->assign('list', $list);
        return $this->fetch();
    }
}