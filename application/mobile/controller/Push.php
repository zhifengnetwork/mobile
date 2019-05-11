<?php

namespace app\mobile\controller;

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
        $user_id = session('user.user_id');
        $condiction = array('user_id' => $user_id);
        $record = M('recharge_points')
                ->where($condiction)
                ->field('create_time, user_money, status')
                ->order('id DESC')
                ->select();
        $this->assign('record', $record);
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
            $data = ['status'=>1, 'msg'=>'上传成功', 'data'=>substr($file['fullName'],1)];
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
        $user_id = session('user.user_id');

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
                $item_id = M('spec_goods_price')->where($arr)->value('item_id');
                $all_data[$good_key]['item_id'] = $item_id;
            }else{
                $all_data[$good_key]['item_id'] = 0;
            }

            $all_data[$good_key]['goods_id'] = $good['comm_id'];
            $all_data[$good_key]['total'] = $good['com_num'];
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
            $result = ['status'=>1, 'msg'=>'操作成功!'];
        }else{
            $result = ['status'=>0, 'msg'=>'操作失败!'];
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
            $result = ['status'=>1, 'msg'=>'操作成功!'];
        }else{
            $result = ['status'=>0, 'msg'=>'操作失败!'];
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
                    $result = ['status'=>1, 'msg'=>'操作成功!'];
                }else{
                    $result = ['status'=>0, 'msg'=>'操作失败!'];
                }
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
        $fir = 'g.goods_name, g.original_img, g.goods_sn, s.goods_num, i.item';
        $stock = M('push_stock')->alias('s')
                ->join('goods g', 'g.goods_id = s.goods_id')
                ->join('spec_item i', 'i.id = s.item_id', 'LEFT')
                ->where('user_id', $user_id)
                ->field($fir)
                ->select();
        $this->assign('stock', $stock);
        return $this->fetch();
    }

    /**
     * 立即订货
     */
    public function order_goods()
    {
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
        //循环取第一种规格作为默认规格
        // foreach($goods as $good_key => $good){
        //     foreach ($good['spec'] as $spec_key => $spec) {
        //         $spec_item = $spec['spec_item'];
        //         dump($spec_item[0]['id']);
        //     }
        // }dump($goods);die;
        
        //删除地推购物车的数据,避免重复
        M('push_cart')->where('user_id', $user_id)->delete();

        $this->assign('goods', $goods);
        return $this->fetch();
    }

    //获取商品规格
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
     * 结算
     */
    public function cart2(){
        $user_id  = session('user.user_id');






        return $this->fetch();
    }


    
}
