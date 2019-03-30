<?php
/**
 * 订单API
 */
namespace app\api\controller;
use app\common\model\Users;
use app\common\logic\UsersLogic;
use think\Db;

class Order extends ApiBase
{

   /**
    * 订单列表
    */
    public function order_list()
    {   
        // $this->ajaxReturn(['status' => 0 , 'msg'=>'获取成功','data'=>$type]);
        $type = I('type');
        $user_id = $this->get_user_id();
        if(!$user_id){
            $this->ajaxReturn(['status' => -1 , 'msg'=>'用户不存在','data'=>'']);
        }
        /*if ($type=='WAITSEND')$data = array('order_status' => [0,1],'shipping_status' =>1,'pay_code'=>'cod'); //'待发货',货到付款
        if ($type=='WAITSEND')$data = array('pay_status'=>1,'order_status'=>[0,1],'shipping_status'=>0,'pay_code'=>['not in','cod']);//'待发货',非货到付款*/
        if ($type=='WAITSEND')$data = array('tp_order.order_status' => ['in','0,1'],);//'待发货'
        if ($type=='WAITPAY')$data = array('tp_order.pay_status'=>0,'tp_order.order_status'=>0,'tp_order.pay_code'=>['not in','cod'],); //'待支付',
        if ($type=='WAITRECEIVE')$data = array('tp_order.shipping_status'=>1,'order_status'=>1,);//'待收货',
        if ($type=='WAITCCOMMENT')$data = array('tp_order.order_status'=>2,);//'待评价',
        // $data = '订单列表数据';
        $data['tp_order.user_id'] = $user_id;
        $name = array(
            'tp_order.order_id',//订单id
            'tp_order.add_time',//下单时间
            'tp_order_goods.goods_name',//商品名称
            'tp_order_goods.spec_key_name',//商品规格名
            'tp_order_goods.goods_price',//本店价格
            'tp_order_goods.goods_num',//购买数
            'tp_order.order_amount',//应付金额
            'seller_name'//商家名称
        );
        $order = Db::name('order')->join('tp_order_goods','tp_order.order_id=tp_order_goods.order_id','right')->join('tp_seller','tp_order_goods.seller_id = tp_seller.seller_id','left')->field($name)->where($data)->select();
        $this->ajaxReturn(['status' => 0 , 'msg'=>'获取成功','data'=>$order]);
    }


    /**
    * 订单
    */
    public function order_detail()
    {
        $user_id = $this->get_user_id();
        if(!$user_id){
            $this->ajaxReturn(['status' => -1 , 'msg'=>'用户不存在','data'=>'']);
        }
        $order_id = I('id');
        //验证是否本人的
        $order = Db::name('order')->where('order_id',$order_id)->select();
        if(!$order){
            $this->ajaxReturn(['status' => -3 , 'msg'=>'订单不存在','data'=>null]);
        }
        if($order['0']['user_id']!=$user_id){
            $this->ajaxReturn(['status' => -2 , 'msg'=>'非本人订单','data'=>null]);
        }
        $data = Db::name('order')->where('order_id',$order_id)->where('user_id',$user_id)->select();
        $this->ajaxReturn(['status' => 0 , 'msg'=>'获取成功','data'=>$data]);
    }

    

    
}
