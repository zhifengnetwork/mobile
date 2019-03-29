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
        $user_id = $this->get_user_id();
        if(!$user_id){
            $this->ajaxReturn(['status' => -1 , 'msg'=>'用户不存在','data'=>'']);
        }

        /*if ($order['pay_code'] == 'cod') {
        if ('WAITSEND')in_array($order['order_status'], array(0, 1)) && $order['shipping_status'] == 0; //'待发货',
        } else // 非货到付款
        {
            if ('WAITPAY')$order['pay_status'] == 0 && $order['order_status'] == 0; //'待支付',
            if ('WAITSEND')$order['pay_status'] == 1 && in_array($order['order_status'], array(0, 1)) && $order['shipping_status'] == 0; //'待发货',
            if ('PORTIONSEND')$order['pay_status'] == 1 && $order['shipping_status'] == 2 && $order['order_status'] == 1; //'部分发货',
        }
        if ('WAITRECEIVE')($order['shipping_status'] == 1) && ($order['order_status'] == 1); //'待收货',
        if ('WAITCCOMMENT')$order['order_status'] == 2; //'待评价',
        if ('CANCEL')$order['order_status'] == 3; //'已取消',
        if ('FINISH')$order['order_status'] == 4; //'已完成',
        if ('CANCELLED')$order['order_status'] == 5; //'已作废',

        $data = Db::name('order')->where('user_id',$user_id)->select();*/
        // $data = '订单列表数据';
        $this->ajaxReturn(['status' => 0 , 'msg'=>'获取成功','data'=>$data]);
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


        $order_id = I('order_id');
        //验证是否本人的



        $data = '订单详情数据';
        

        $this->ajaxReturn(['status' => 0 , 'msg'=>'获取成功','data'=>$data]);
    }

    

    
}
