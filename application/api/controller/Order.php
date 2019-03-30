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
    public function order_list($type="")
    {   
        // $this->ajaxReturn(['status' => 0 , 'msg'=>'获取成功','data'=>$type]);
        // $type = I('type');
        $user_id = $this->get_user_id();
        if(!$user_id){
            $this->ajaxReturn(['status' => -1 , 'msg'=>'用户不存在','data'=>'']);
        }
        /*if ($type=='WAITSEND')$data = array('order_status' => [0,1],'shipping_status' =>1,'pay_code'=>'cod'); //'待发货',货到付款
        if ($type=='WAITSEND')$data = array('pay_status'=>1,'order_status'=>[0,1],'shipping_status'=>0,'pay_code'=>['not in','cod']);//'待发货',非货到付款*/
        if ($type=='WAITSEND')$data = array('order_status' => ['in','0,1'],);//'待发货'
        if ($type=='WAITPAY')$data = array('pay_status'=>0,'order_status'=>0,'pay_code'=>['not in','cod'],); //'待支付',// 非货到付款
        if ($type=='PORTIONSEND')$data = array('pay_status'=>1,'shipping_status'=>2,'order_status'=>1,'pay_code'=>['not in','cod'],);//'部分发货',// 非货到付款
        if ($type=='WAITRECEIVE')$data = array('shipping_status'=>1,'order_status'=>1,);//'待收货',
        if ($type=='WAITCCOMMENT')$data = array('order_status'=>2,);//'待评价',
        if ($type=='CANCEL')$data = array('order_status'=>3,);//'已取消',
        if ($type=='FINISH')$data = array('order_status'=>4,);//'已完成',
        if ($type=='CANCELLED')$data = array('order_status'=>5,);//'已作废',
        // $data = '订单列表数据';
        $data['user_id'] = $user_id;
        $order = Db::name('order')->where($data)->select();
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


        $order_id = I('order_id');
        //验证是否本人的



        $data = '订单详情数据';
        

        $this->ajaxReturn(['status' => 0 , 'msg'=>'获取成功','data'=>$data]);
    }

    

    
}
