<?php

namespace app\admin\controller;
use think\Db;
use think\Loader;
use think\Page;

class Shipping extends Base{

    /**
     * 快递公司列表
     * @return mixed
     */
    public function index(){
        $shipping_name = input('shipping_name/s');
        $shipping_code = input('shipping_code/s');
        $where = [];
        if($shipping_name){
            $where['shipping_name'] = ['like','%'.$shipping_name.'%'];
        }
        if($shipping_code){
            $where['shipping_code'] = $shipping_code;
        }
        $shipping = new \app\common\model\Shipping();
        $count = $shipping->where($where)->count();
        $Page = new Page($count, 10);
        $list = $shipping->where($where)->limit($Page->firstRow . ',' . $Page->listRows)->select();
        $this->assign('page', $Page);
        $this->assign('list', $list);
        return $this->fetch();
    }

    /**
     * 快递公司详情页
     * @return mixed
     */
    public function info()
    {
        $shipping_id = input('shipping_id/d');
        if ($shipping_id) {
            $Shipping = new \app\common\model\Shipping();
            $shipping = $Shipping->where(['shipping_id'=>$shipping_id])->find();
            if(empty($shipping)){
                $this->error('没有找到相应记录');
            }
            $this->assign('shipping', $shipping);
        }
        $this->assign('express_switch',tpCache('express.express_switch'));
        return $this->fetch();
    }

    /**
     * 添加和更新快递公司
     */
    public function save()
    {
        $data = input('post.');
        $validate = Loader::validate('Shipping');
        if (!$validate->batch()->check($data)) {
            $this->ajaxReturn(['status' => 0, 'msg' => '操作失败', 'result' => $validate->getError()]);
        }
        if (empty($data['shipping_id'])) {
            $shipping = new \app\common\model\Shipping();
        } else {
            $shipping = \app\common\model\Shipping::get($data['shipping_id']);
        }
        $shipping_save = $shipping->data($data, true)->save();
        if ($shipping_save === false) {
            $this->ajaxReturn(['status' => 0, 'msg' => '操作失败', 'result' => $validate->getError()]);
        }else{
            $this->ajaxReturn(['status' => 1, 'msg' => '操作成功', 'result' => '']);
        }

    }

    /**
     * 删除快递公司
     * @throws \think\Exception
     */
    public function delete()
    {
        $shipping_id = input('shipping_id');
        Db::name('shipping')->where('shipping_id', $shipping_id)->delete();
        $this->ajaxReturn(['status' => 1, 'msg' => '删除成功', 'result' => '']);
    }
}