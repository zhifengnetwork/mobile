<?php

namespace app\mobile\controller;

use app\common\util\TpshopException;
use app\common\logic\PushCartLogic;
use app\common\logic\GoodsLogic;
use app\common\logic\Pay;  
use app\common\model\Order as OrderModel; 
use think\Exception;
use think\Page;
use think\db;

class Push extends MobileBase
{
    /**
     * 地推管理首页
     */
    public function index()
    {
        $user_id  = session('user.user_id');
        $integral = M('users')
                  ->where('user_id', $user_id)
                  ->value('integral_push');
        $this->assign('integral', $integral);
        return $this->fetch();
    }

    /**
     * 地推积分充值记录
     */
    public function recharge_record()
    {
        $page = I('page/s', 1);
        $user_id = session('user.user_id');
        $condiction = array('user_id' => $user_id);
        $record = M('recharge_points')
                ->where($condiction)
                ->field('create_time, user_money, exchange_integral, status')
                ->order('id DESC')
                ->page($page,20)
                ->select();
        $this->assign('record', $record);
        if($_GET['is_ajax']){
            return $this->fetch('ajax_recharge_record');
        }
        return $this->fetch();
    }

    /**
     * 地推积分变动记录
     */
    public function account_push()
    {
        $page = I('page/s', 1);
        $user_id = session('user.user_id');
        $condiction = array('user_id' => $user_id);
        $record = M('account_push_log')
                ->where($condiction)
                ->field('change_time, integral_push, desc')
                ->order('id DESC')
                ->page($page,20)
                ->select();
        $this->assign('record', $record);
        if($_GET['is_ajax']){
            return $this->fetch('ajax_account_push');
        }
        return $this->fetch();
    }

    /**
     * 地推明细
     */
    public function push_log()
    {
        $page = I('page/s', 1);
        $user_id = session('user.user_id');
        $condiction = array('leader_id' => $user_id, 'pay_status' => 1);
        $record = M('order')
                ->where($condiction)
                ->field('user_id, order_sn, total_amount, pay_time')
                ->order('order_id DESC')
                ->page($page,16)
                ->select();
        $this->assign('record', $record);
        if($_GET['is_ajax']){
            return $this->fetch('ajax_push_log');
        }
        return $this->fetch();
    }

    /**
     * 地推积分充值
     */
    public function recharge()
    {
        if(IS_POST){
            $data = I('post.');
            $data = $data['data'];

            $arr  = array(
                'inc_type' => 'recharge',
                'name' => ['in',['recharge_open', 'points_rate']],
            );
            $config = M('config')->where($arr)->column('name, value');
            //判断地推积分充值功能是否开启
            if(!$config['recharge_open']){
                $this->ajaxReturn(['status'=>0, 'msg'=>'积分充值功能已关闭']);
                exit;
            }
            
            //网站限制大小
            $max_size    = config('image_upload_limit_size');
            $base64_data = explode(',', $data['voucher_img']);
            $base64_data = $base64_data[1];
            $filepath    = UPLOAD_PATH.'recharge_pic/';
            $result = $this->upBase64($max_size, $base64_data, $filepath);
            if($result['status'] != 1){
                $this->ajaxReturn(['status'=>0, 'msg'=>$result['msg']]);
                exit;
            }
            
            $user_id = session('user.user_id');
            $insert_data['user_id'] = $user_id;
            $insert_data['create_time'] = time();
            $insert_data['recharge_pic'] = $result['data'];
            $insert_data['user_money'] = $data['choice_number'];
            $insert_data['exchange_integral'] = round($config['points_rate'] * $data['choice_number'], 2);
            $result = M('recharge_points')->insert($insert_data);
            if($result){
                //微信消息推送通知
                $this->ajaxReturn(['status'=>1, 'msg'=>'提交成功!']);
                exit;
            }else{
                $this->ajaxReturn(['status'=>0, 'msg'=>'提交失败!']);
                exit;
            }
        }
        $card_data = M('config')->where(['inc_type' => 'recharge', 'name' => ['in','recharge_card,recharge_name']])->column('name, value');
        $this->assign('card_data', $card_data);
        return $this->fetch();
    }

    /*
	 * 处理base64图片
	*/
	private function upBase64($max_size, $base64_data, $dirname){
        $img = base64_decode($base64_data);

	    $file['filesize'] = strlen($img);
	    $file['oriName'] = date('YmdHis') . '.png';
	    $file['ext'] = strtolower(strrchr($file['oriName'],'.'));
	    $file['name'] = uniqid().$file['ext'];
	    $file['fullName'] = $dirname.$file['name'];
	    $fullName = $file['fullName'];

 	    //检查文件大小是否超出限制
	    if($file['filesize'] >= ($max_size)){
            $data = ['status'=>0, 'msg'=>'文件大小超出网站限制'];
            return $data;
	    }

	    //创建目录失败
	    if(!file_exists($dirname) && !mkdir($dirname,0777,true)){
            $data = ['status'=>0, 'msg'=>'目录创建失败'];
            return $data;
	    }else if(!is_writeable($dirname)){
	        $data = ['status'=>0, 'msg'=>'目录创建失败'];
            return $data;
	    }

	    //移动文件
        if(!(file_put_contents($fullName, $img) && file_exists($fullName))){ 
            //移动失败
            $data = ['status'=>0, 'msg'=>'写入文件内容错误'];
            return $data;
	    }else{ 
            $data = ['status'=>1, 'msg'=>'上传成功', 'data'=>$file['fullName']  ];
            return $data;
	    }
    }
    
    /**
     * 地推商品购物车
     */
    public function push_cart()
    {
        $action = I('action');
        $data   = I('data/a');
        $user_id = I('user_id');;

        if(!$user_id){
            $result = ['status'=> 0, 'msg'=>'请登录', 'result' => ''];
            $this->ajaxReturn($result);   
        }

        switch ($action) {
            case 'insert':
                $data   = $this->handle_data($user_id, $data);
                $result = $this->insert_cart($data);
                $this->ajaxReturn($result);   
                break;
            
            case 'delete':
                $data   = $this->handle_data($user_id, $data);
                $result = $this->delete_cart($user_id, $data);
                $this->ajaxReturn($result);   
                break;

            default:
                $data   = $this->handle_data($user_id, $data);
                $result = $this->update_cart($user_id, $data);
                $this->ajaxReturn($result);  
                break;
        }
    }

    /**
     * 处理数据
     */
    public function handle_data($user_id, $data)
    {   
        $all_data = array();
        $pre_time = time();
        foreach ($data as $good_key => $good) {
            $goods_info = M('goods')->where('goods_id', $good['comm_id'])
                        ->field('goods_name, shop_price')->find();
            if(isset($good['com_speci'])){
                $key = '';
                foreach ($good['com_speci'] as $spec_key => $spec) {
                    if($spec_key == 0){
                        $key = $spec['speci_id'];
                    }else{
                        $key = $key . '_' . $spec['speci_id'];
                    }
                }
                $arr = array('goods_id'=>$good['comm_id'], 'key'=>$key);
                $item = M('spec_goods_price')->where($arr)->field('item_id, key, key_name, price')->find();
                $all_data[$good_key]['spec_key'] = $item['key'];
                $all_data[$good_key]['item_id'] = $item['item_id'];
                $all_data[$good_key]['goods_price'] = $item['price'];
                $all_data[$good_key]['goods_spec'] = $item['key_name'];
            }else{
                $all_data[$good_key]['spec_key'] = 0;
                $all_data[$good_key]['item_id'] = 0;
                $all_data[$good_key]['goods_price'] = $goods_info['shop_price'];
                $all_data[$good_key]['goods_spec'] = 0;
            }

            $all_data[$good_key]['goods_name'] = $goods_info['goods_name'];
            $all_data[$good_key]['goods_id'] = $good['comm_id'];
            $all_data[$good_key]['goods_num'] = $good['com_num'];
            $all_data[$good_key]['create_time'] = $pre_time;
            $all_data[$good_key]['user_id'] = $user_id; 
        }
        return $all_data;
    }

    /**
     * 添加购买地推商品
     */
    public function insert_cart($data)
    {
        $result = M('push_cart')->insertAll($data);
        if($result){
            $result = ['status'=>1, 'msg'=>'操作成功!', 'result' => ''];
        }else{
            $result = ['status'=>0, 'msg'=>'操作失败!', 'result' => ''];
        }
        return $result;
    }

    /**
     * 删除购买地推商品
     */
    public function delete_cart($user_id, $data)
    {
        foreach ($data as $key => $value) {
            $goods_id[] = $value['goods_id'];
            $item_id[]  = $value['item_id'];
        }
        $arr = array(
            'user_id' => $user_id, 
            'goods_id'=> ['in', $goods_id],
            'item_id' => ['in', $item_id],
        );
        $retult = M('push_cart')->where($arr)->delete();
        if($result){
            $result = ['status'=>1, 'msg'=>'操作成功!', 'result' => ''];
        }else{
            $result = ['status'=>0, 'msg'=>'操作失败!', 'result' => ''];
        }
        return $result;
    }

    /**
     * 修改购买地推商品
     */
    public function update_cart($user_id, $data)
    {
        foreach ($data as $key => $value) {
            $condition = array(
                'user_id'  => $user_id,
                'goods_id' => $value['goods_id'],
            );
            $is_exisit = M('push_cart')->where($condition)->find();
            if($is_exisit){
                $result = M('push_cart')->where('id', $is_exisit['id'])->update($value);
                if($result){
                    $result = ['status'=>1, 'msg'=>'操作成功!', 'result' => $value['goods_price']];
                }else{
                    $result = ['status'=>0, 'msg'=>'操作失败!', 'result' => ''];
                }
                return $result;
            }else{
                $result = ['status'=>0, 'msg'=>'操作失败!', 'result' => $value['goods_price']];
                return $result;
            }
        }
    }

    /**
     * 我的库存
     */
    public function my_stock()
    {
        $user_id = session('user.user_id');
        $fir = 'g.goods_name, g.original_img, g.goods_sn, s.goods_num, s.goods_spec';
        $stock = M('push_stock')->alias('s')
                ->join('goods g', 'g.goods_id = s.goods_id', 'LEFT')
                ->where('user_id', $user_id)
                ->field($fir)
                ->order('id DESC')
                ->select();
        $this->assign('stock', $stock);
        return $this->fetch();
    }

    /**
     * 立即订货
     */
    public function order_goods()
    {
        $user_id = session('user.user_id');
        if(!$user_id){
            $this->error('请先登录','Mobile/User/login');
        }

        $condiction = array(
            'prom_type'  => 0,
            'is_virtual' => 0,
            'is_on_sale' => 1,
            'is_ground_push' => 1,
            'sign_free_receive' => ['neq', 1],
        );
        $user_id  = session('user.user_id');
        $goods_id = M('goods')->where($condiction)->column('goods_id');
        $goodsModel = new \app\common\model\Goods();
        $goods = $goodsModel::all($goods_id);
        
        //删除地推购物车的数据,避免重复
        M('push_cart')->where('user_id', $user_id)->delete();
        $this->assign('goods', $goods);

        $this->assign('user_id', $user_id);

        return $this->fetch();
    }

    /**
     * 通过 item_id 获取组合规格的价格
     */
    function get_item_price(){
        $item_id = I('item_id/s',0);
        $price = M('spec_goods_price')->where('item_id', $item_id)->value('price');
        if($price){
            $this->ajaxReturn(['status'=>1, 'msg'=>'获取成功', 'result'=>$price]);
        }else{
            $this->ajaxReturn(['status'=>0, 'msg'=>'获取失败', 'result'=>'']);
        }
    }

    /**
     * 获取商品规格
     */
    public function getSpec($goods_id)
    {
        $spec_goods_price_key = db('spec_goods_price')->where("goods_id", $goods_id)->column('key');
        if($spec_goods_price_key){
            $spec_goods_price_key_str = implode('_', $spec_goods_price_key);
            $spec_goods_price_key_arr = explode('_', $spec_goods_price_key_str);
            $spec_goods_price_key_arr = array_unique($spec_goods_price_key_arr);
            $spec_item_list = db('spec_item')->where('id', 'IN', $spec_goods_price_key_arr)->order('order_index asc')->select();
            $spec_ids = get_arr_column($spec_item_list, 'spec_id');
            $spec_list = db('spec')->where('id', 'IN', $spec_ids)->order('`order` desc, id asc')->select();
            foreach($spec_list as $spec_key=>$spec_val){
                foreach($spec_item_list as $spec_item_key=>$spec_item_val){
                    if($spec_val['id'] == $spec_item_val['spec_id']){
                        $spec_list[$spec_key]['spec_item'][] = $spec_item_val;
                    }
                }
            }
            $this->ajaxReturn(['status'=>1, 'msg'=>'获取成功', 'result'=>$spec_list]);
        }
        $this->ajaxReturn(['status'=>0, 'msg'=>'获取失败', 'result'=>'']);
    }


    /**
     * 下级订单
     */
    public function lower_order()
    {
        $type = input('type/s');
        $page = input('page/s', 1);
        $user_id = session('user.user_id');

        $order_ids = M('order')->where('leader_id', $user_id)->column('order_id');
        $order = new OrderModel();
        $where_arr = [
            'order_id' => ['in', $order_ids],
            'deleted' => 0,//删除的订单不列出来
            'prom_type' => array(['lt',5], ['eq', 9], ['eq', 10], 'or')//虚拟拼团订单不列出来
        ];

        if($type == 'WAITPAY'){
            $where_arr['pay_status'] = 0;
        }else if($type == 'WAITSEND'){
            $where_arr['pay_status'] = 1;
        }

        $order_list = $order->where($where_arr)->page($page, 16)
                    ->order("order_id DESC")->select(); 
        $this->assign('order_list', $order_list);
        if ($_GET['is_ajax']) {
            return $this->fetch('ajax_lower_order');
        }
        return $this->fetch();
    }
}
