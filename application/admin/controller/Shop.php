<?php
/**
 * DC环球直供网络
 * ============================================================================
 * 版权所有 2015-2027 广州滴蕊生物科技有限公司，并保留所有权利。
 * 网站地址: http://www.dchqzg1688.com
 * ----------------------------------------------------------------------------
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和使用 .
 * 不允许对程序代码以任何形式任何目的的再发布。
 * 如果商业用途务必到官方购买正版授权, 以免引起不必要的法律纠纷.
 * ============================================================================
 * Author: 当燃
 * 拼团控制器
 * Date: 2016-06-09
 */

namespace app\admin\controller;

use app\common\model\Shopper;
use think\Loader;
use think\Db;
use think\AjaxPage;
use think\Page;

class Shop extends Base
{
    /**
     * 门店 - 门店管理 - 门店列表
     */
    public function index()
    {
        $list = array();
        $keywords = I('keywords/s');
        $count = D('shop')->count();
        $Page  = new AjaxPage($count,20);
        $show = $Page->show();
        if (empty($keywords)) {
            $res = DB::name('shop')->limit($Page->firstRow.','.$Page->listRows)->select();
        } else {
            $res = DB::name('shop')->where(['store_name|webid|phone|address|city' => ['like', '%' . $keywords . '%']])->order('store_id')->limit($Page->firstRow.','.$Page->listRows)->select();
        }

        $region = DB::name('region')->getField('id,name');
        if ($region && $res) {
            foreach ($res as $val) {
                $val['province'] = $region[$val['province_id']];
                $val['city'] = $region[$val['city_id']];
                $val['district'] = $region[$val['district_id']];
                $val['add_time'] = date('Y-m-d H:i:s', $val['add_time']);
                $list[] = $val;
            }
        }
        //$show = $Page->show();
        $this->assign('list', $list);
        $this->assign('page',$show);// 赋值分页输出
        $province_list = Db::name('region')->where(['parent_id'=>0,'level'=> 1])->cache(true)->select();
        $this->assign('province_list', $province_list);
        return $this->fetch();
    }

    /**
     * 门店 - 门店管理 - 门店列表 - 添加界面
     */
    public function info()
    {
        $shop_id = input('shop_id/d');
        if ($shop_id) {
            $Shop = new \app\common\model\Shop();
            $shop = $Shop->where(['shop_id' => $shop_id,'deleted' => 0])->find();
            if (empty($shop)) {
                $this->error('非法操作');
            }
            $city_list = Db::name('region')->where(['parent_id'=>$shop['province_id'],'level'=> 2])->select();
            $district_list = Db::name('region')->where(['parent_id'=>$shop['city_id']])->select();
            $shop_image_list = Db::name('shop_images')->where(['shop_id'=>$shop['shop_id']])->select();
            $this->assign('city_list', $city_list);
            $this->assign('district_list', $district_list);
            $this->assign('shop_image_list', $shop_image_list);
            $this->assign('shop', $shop);
        }
        $province_list = Db::name('region')->where(['parent_id'=>0,'level'=> 1])->cache(true)->select();
        $suppliers_list = Db::name("suppliers")->where(['is_check'=>1])->select();
        $this->assign('suppliers_list', $suppliers_list);
        $this->assign('province_list', $province_list);
        return $this->fetch();
    }

    /**
     * 门店 - 门店管理 - 门店列表 - 添加功能处理方法
     */
    public function add()
    {
        $data = I('post.');
        if (empty($data['longitude']) && empty($data['latitude'])) {
            $this->ajaxReturn(['status' => 0, 'msg' => '请选择地图定位', 'result' => '']);
        }
        /*if (empty($data['shopper_name'])) {
            $this->ajaxReturn(['status' => 0, 'msg' => '请填写门店自提点后台账号', 'result' => '']);
        }
        if (empty($data['user_name'])) {
            $this->ajaxReturn(['status' => 0, 'msg' => '请填写会员账号', 'result' => '']);
        }
        if (empty($data['password'])) {
            $this->ajaxReturn(['status' => 0, 'msg' => '请填写登录密码', 'result' => '']);
        }*/
        if (empty($data['shop_name'])) {
            $this->ajaxReturn(['status' => 0, 'msg' => '请填写自提点名称', 'result' => '']);
        }
        if (empty($data['shop_phone'])) {
            $this->ajaxReturn(['status' => 0, 'msg' => '请填写联系电话', 'result' => '']);
        }
        if (empty($data['work_start_time']) || empty($data['work_end_time'])) {
            $this->ajaxReturn(['status' => 0, 'msg' => '请填写营业时间', 'result' => '']);
        }
        if (empty($data['province_id'])) {
            $this->ajaxReturn(['status' => 0, 'msg' => '请选择省份/直辖市', 'result' => '']);
        }
        if (empty($data['city_id'])) {
            $this->ajaxReturn(['status' => 0, 'msg' => '请选择城市', 'result' => '']);
        }
        if (empty($data['district_id'])) {
            $this->ajaxReturn(['status' => 0, 'msg' => '请选择区/县', 'result' => '']);
        }
        if (empty($data['shop_address'])) {
            $this->ajaxReturn(['status' => 0, 'msg' => '请填写详细地址', 'result' => '']);
        }
        if (empty($data['shop_id'])) {
            unset($data['shop_id']);
        }
        if (empty($data['monday'])) {
            unset($data['monday']);
        }
        if (empty($data['tuesday'])) {
            unset($data['tuesday']);
        }
        if (empty($data['wednesday'])) {
            unset($data['wednesday']);
        }
        if (empty($data['thursday'])) {
            unset($data['thursday']);
        }
        if (empty($data['friday'])) {
            unset($data['friday']);
        }
        if (empty($data['saturday'])) {
            unset($data['saturday']);
        }
        if (empty($data['sunday'])) {
            unset($data['sunday']);
        }
        if (empty($data['suppliers_id'])) {
            unset($data['suppliers_id']);
        }
        if (empty($data['shop_images'])) {
            unset($data['shop_images']);
        }else{
            $data['shop_images'] = implode(',',$data['shop_images']);
        }
        $data['add_time'] = time();
        $r = D('shop')->add($data);
        if ($r) {
            $this->ajaxReturn(['status' => 1, 'msg' => '操作成功', 'url' => U('Admin/Shop/index')]);
        } else {
            $this->ajaxReturn(['status' => -1, 'msg' => '操作失败']);
        }
    }

    /**
     * 门店 - 门店管理 - 门店列表 - 编辑功能处理方法
     */
    public function save(){
        $data = I('post.');
        if (empty($data['longitude']) && empty($data['latitude'])) {
            $this->ajaxReturn(['status' => 0, 'msg' => '请选择地图定位', 'result' => '']);
        }
        /*if (empty($data['shopper_name'])) {
            $this->ajaxReturn(['status' => 0, 'msg' => '请填写门店自提点后台账号', 'result' => '']);
        }*/
        /*if (empty($data['user_name'])) {
            $this->ajaxReturn(['status' => 0, 'msg' => '请填写会员账号', 'result' => '']);
        }
        if (empty($data['password'])) {
            $this->ajaxReturn(['status' => 0, 'msg' => '请填写登录密码', 'result' => '']);
        }*/
        if (empty($data['shop_name'])) {
            $this->ajaxReturn(['status' => 0, 'msg' => '请填写自提点名称', 'result' => '']);
        }
        if (empty($data['shop_phone'])) {
            $this->ajaxReturn(['status' => 0, 'msg' => '请填写联系电话', 'result' => '']);
        }
        if (empty($data['work_start_time']) || empty($data['work_end_time'])) {
            $this->ajaxReturn(['status' => 0, 'msg' => '请填写营业时间', 'result' => '']);
        }
        if (empty($data['province_id'])) {
            $this->ajaxReturn(['status' => 0, 'msg' => '请选择省份/直辖市', 'result' => '']);
        }
        if (empty($data['city_id'])) {
            $this->ajaxReturn(['status' => 0, 'msg' => '请选择城市', 'result' => '']);
        }
        if (empty($data['district_id'])) {
            $this->ajaxReturn(['status' => 0, 'msg' => '请选择区/县', 'result' => '']);
        }
        if (empty($data['shop_address'])) {
            $this->ajaxReturn(['status' => 0, 'msg' => '请填写详细地址', 'result' => '']);
        }
        if (empty($data['monday'])) {
            unset($data['monday']);
        }
        if (empty($data['tuesday'])) {
            unset($data['tuesday']);
        }
        if (empty($data['wednesday'])) {
            unset($data['wednesday']);
        }
        if (empty($data['thursday'])) {
            unset($data['thursday']);
        }
        if (empty($data['friday'])) {
            unset($data['friday']);
        }
        if (empty($data['saturday'])) {
            unset($data['saturday']);
        }
        if (empty($data['sunday'])) {
            unset($data['sunday']);
        }
        if (empty($data['suppliers_id'])) {
            unset($data['suppliers_id']);
        }
        if (empty($data['shop_images'])) {
            unset($data['shop_images']);
        }else{
            $data['shop_images'] = implode(',',$data['shop_images']);
        }
        if($data['shop_id']){
            $shop_id = $data['shop_id'];
            unset($data['shopper_name']);
            unset($data['user_name']);
            unset($data['password']);
            unset($data['shop_id']);
            $r = D('shop')->where(['shop_id'=> $shop_id])->save($data);
            if ($r) {
                $this->ajaxReturn(['status' => 1, 'msg' => '操作成功', 'url' => U('Admin/Shop/index')]);
            } else {
                $this->ajaxReturn(['status' => 0, 'msg' => '操作失败', 'result' => '']);
            }
        }
    }

    /**
     * 删除
     */
    public function delete()
    {
        $shop_id = input('shop_id/d');
        if(empty($shop_id)){
            $this->ajaxReturn(['status' => 0, 'msg' => '参数错误']);
        }
        $Shop = new \app\common\model\Shop();
        $shop = $Shop->where(['shop_id'=>$shop_id])->find();
        if(empty($shop)){
            $this->ajaxReturn(['status' => 0, 'msg' => '非法操作', 'result' => '']);
        }
        $row = $shop->save(['deleted'=>1]);
        if($row !== false){
            $this->ajaxReturn(['status' => 1, 'msg' => '删除成功', 'result' => '']);
        }else{
            $this->ajaxReturn(['status' => 0, 'msg' => '删除失败', 'result' => '']);
        }
    }

    public function shopImageDel()
    {
        $path = input('filename','');
        Db::name('goods_images')->where("image_url",$path)->delete();
    }

    /**
     * 门店 - 门店管理 - 核销员列表
     */
    public function write_off_clerk_list()
    {
        $list = [];
        //$lists = [];
        $res = DB::name('shopper')->select();
        //if(!empty($res)){
        //$users =    DB::name('users')->getField('user_id,nickname');
        $shop =    DB::name('shop')->getField('shop_id,shop_name');
        //}
        /*if ($users && $res) {
            foreach ($res as $val) {
                $val['nickname'] = $users[$val['user_id']];
                $val['add_time'] = date('Y-m-d H:i:s', $val['add_time']);
                $list[] = $val;
            }
        }*/
        if ($shop && $res) {
            foreach ($res as $val) {
                $val['shop_name'] = $shop[$val['shop_id']];
                $val['add_time'] = date('Y-m-d H:i:s', $val['add_time']);
                $list[] = $val;
            }
        }

        $this->assign('list', $list);
        return $this->fetch();
    }

    /**
     * 门店 - 门店管理 - 核销员列表 - 门店绑定核销员
     */
    public function write_off_clerk_info()
    {
        $shopper_id = I('get.shopper_id/d', 0);

        print_r($shopper_id);
        if ($shopper_id) {
            $info = Db::name('shopper')->where("shopper_id", $shopper_id)->find();

            $shop = Db::name('shop')->where("shop_id", $info['shop_id'])->find();
            $users = Db::name('users')->where("user_id", $info['user_id'])->find();
            $info['nickname'] = $users['nickname'];
            $info['shop_name'] = $shop['shop_name'];



            $this->assign('shop', $info);
            /*$city =  M('region')->where(array('parent_id'=>$info['province']))->select();
            $area =  M('region')->where(array('parent_id'=>$info['city']))->select();
            $this->assign('city',$city);
            $this->assign('area',$area);*/
        }
        //$act = empty($store_id) ? 'add' : 'edit';
        //$province = M('region')->where(array('parent_id'=>0))->select();
        //$this->assign('province',$province);
        //$this->assign('act', $act);
        return $this->fetch();
    }

    /**
     * 门店 - 门店管理 - 核销员列表 - 门店绑定核销员数据处理
     */
    public function shopHandle()
    {
        $data = I('post.');
        $shopper_id = $data['shopper_id'];
        unset($data['nickname']);
        unset($data['shop_name']);
        if(empty($shopper_id)){
            unset($data['shopper_id']);
            $data['add_time'] = time();
            $r = D('shopper')->add($data);
        }else{
            $r = D('shopper')->where('shopper_id', $data['shopper_id'])->save($data);
        }
        if ($r) {
            $this->ajaxReturn(['status' => 1, 'msg' => '操作成功', 'url' => U('Admin/Shop/write_off_clerk_list')]);
        } else {
            $this->ajaxReturn(['status' => -1, 'msg' => '操作失败']);
        }
    }

    /**
     * 门店 - 门店管理 - 核销员列表 - 门店绑定核销员 - 选择核销员
     */
    public function search_user(){
        //$usersModel = new Users();
        $count = Db::name('users')->count();
        $Page = new AjaxPage($count, 20);
        //$userList = $usersModel->limit($Page->firstRow . ',' . $Page->listRows)->select();
        $usersList = Db::name('users')->limit($Page->firstRow . ',' . $Page->listRows)->select();
        $show = $Page->show();
        $this->assign('usersList', $usersList);
        $this->assign('page', $show);// 赋值分页输出
        return $this->fetch();
    }

    /**
     * 门店 - 门店管理 - 核销员列表 - 门店绑定核销员 - 选择门店
     */
    public function search_shop(){
        $count = Db::name('shop')->count();
        $Page = new AjaxPage($count, 20);
        $shopList = Db::name('shop')->limit($Page->firstRow . ',' . $Page->listRows)->select();
        $show = $Page->show();
        $this->assign('shopList', $shopList);
        $this->assign('page', $show);// 赋值分页输出
        return $this->fetch();
    }

    /**
     * 门店 - 门店商品管理 - 门店绑定商品并分配库存 - 选择商品
     */
    public function search_goods(){
        //$usersModel = new Users();
        $count = Db::name('goods')->count();
        $Page = new AjaxPage($count, 20);
        //$userList = $usersModel->limit($Page->firstRow . ',' . $Page->listRows)->select();
        $goodsList = Db::name('goods')->limit($Page->firstRow . ',' . $Page->listRows)->select();
        $show = $Page->show();
        $this->assign('goodsList', $goodsList);
        $this->assign('page', $show);// 赋值分页输出
        return $this->fetch();
    }

    /**
     * 门店 - 门店管理 - 门店商品管理
     */
    public function shop_goods_list(){
        /*$shopList = Db::name('shop')->select();
        $this->assign('shopList', $shopList);
        return $this->fetch();*/
        $list = [];
        //$lists = [];
        $res = DB::name('shop_goods')->select();
        //if(!empty($res)){
        //$users =    DB::name('users')->getField('user_id,nickname');
        $shop =    DB::name('shop')->getField('shop_id,shop_name');
        //}
        /*if ($users && $res) {
            foreach ($res as $val) {
                $val['nickname'] = $users[$val['user_id']];
                $val['add_time'] = date('Y-m-d H:i:s', $val['add_time']);
                $list[] = $val;
            }
        }*/
        if ($shop && $res) {
            foreach ($res as $val) {
                $val['shop_name'] = $shop[$val['shop_id']];
                $val['add_time'] = date('Y-m-d H:i:s', $val['add_time']);
                $list[] = $val;
            }
        }

        $this->assign('list', $list);
        return $this->fetch();
    }

    /**
     * 商品绑定门店并分配门店库存
     * creat_name 陈焕强
     * creat_time 2019年3月14日12:00:20
     */
    public function store_binding_goods(){
        $goods_id = I('get.goods_id/d',0);
        $count = Db::name('shop')->count();
        $Page = new AjaxPage($count, 10);
        $goods = [];
        if($goods_id){
            $goods = Db::name('goods')->where(['goods_id'=>$goods_id])->find();
        }
        $this->assign('goodsList', $goods);
        $shopList = Db::name('shop')->limit($Page->firstRow . ',' . $Page->listRows)->select();
        $show = $Page->show();
        $this->assign('usersList', $shopList);
        $this->assign('page', $show);// 赋值分页输出
        return $this->fetch();
    }

    /**
     * 商品绑定门店并分配门店库存数据处理
     */
    public function shop_goodsHandle()
    {
        $data = I('post.');
        $shop_goods_id = $data['shop_goods_id'];
        unset($data['goods_name']);
        unset($data['shop_name']);
        if(empty($shop_goods_id)){
            unset($data['shop_goods_id']);
            $data['add_time'] = time();
            $r = D('shop_goods')->add($data);
        }else{
            $r = D('shop_goods')->where('shop_goods_id', $data['shop_goods_id'])->save($data);
        }
        if ($r) {
            $this->ajaxReturn(['status' => 1, 'msg' => '操作成功', 'url' => U('Admin/Shop/store_binding_goods')]);
        } else {
            $this->ajaxReturn(['status' => -1, 'msg' => '操作失败']);
        }
    }
}
