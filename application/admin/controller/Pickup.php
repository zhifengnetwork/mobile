<?php

namespace app\admin\controller;

use think\AjaxPage;
use think\Page;
use think\Db;

class Pickup extends Base {

	public function pickup_add($id)
	{
		return $this->fetch('pickup');
	}

	// public function pickup_list(){

	// 	$Pickup =  M('offline_pickup'); 
	// 	$res = $Pickup->order('id desc')->select();
 //  		$this->assign('res',$res);


	// 	return $this->fetch();
	// }
	public function stay(){

        // $Pickup =  M('order'); 
        // $res = $Pickup->order('order_id desc')->select();
        // $this->assign('res',$res);
        if ($_POST) {
            $order_sn = input('order_sn/s');
            $where = "and  order_sn like '%$order_sn%' ";
            $cwhere['order_sn'] = "like '%$order_sn%' ";
        }

        // $order_sn = input('order_sn/s');
        // if (empty($order_sn)) {
        //     $res = D('order')->select();
        // } else {
        //     $res = DB::name('order')->where(['order_sn' => ['like', '%' . $order_sn . '%']])->order('order_id')->select();
        // }

        $count = Db::table("tp_order")->join('tp_pick_up',' tp_order.user_id=tp_pick_up.pickup_id')->where($cwhere)->count();
        $page = new Page($count,10);
        $Pickup =  Db::query("select * from tp_order as a,tp_pick_up as b where a.user_id = b.pickup_id $where order by a.order_id desc limit $page->firstRow,$page->listRows");
        // var_dump($Pickup);exit;
        // $res = $Pickup->order('order_id desc')->select();
        $this->assign('page',$page);
        $this->assign('res',$Pickup);


        return $this->fetch();
    }
	

	/**
     * 核销页
     */
    public function off(){

        // $Pickup = M('shop_order');
        // $res = $Pickup->shop_order('shop_order_id desc')->select();
        // $this->assign('res',$res);
        $shop_order_wait_off_num = Db::name('shop_order')->alias('s')
            ->join('__ORDER__ o','o.order_id = s.order_id')->where(['s.is_write_off' => 0,'order_status'=>1])->count('s.shop_order_id');
        $this->assign('shop_order_wait_off_num', $shop_order_wait_off_num);
        return $this->fetch();


    }
    /**
     * ajax 获取自提订单信息
     * order_id
     */
    public function getOrderGoodsInfo()
    {
        $order_id = input("order_id/d",0);
        $Order = new Order();
        $order = $Order->with("shop,shop_order")->where(['order_id'=>$order_id])->find();
        $order_info = $order->append(['delivery_method','shipping_status_desc'])->toArray();
        $this->ajaxReturn($order_info);
    }
    /**
     * 核销
     */
    public function writeOff()
    {
        $shop_order_id = input('shop_order_id/d', 0);
        $ShopOrderLogic = new \app\common\logic\ShopOrder();
        $ShopOrderLogic->setShopOrderById($shop_order_id);
        try {
            $ShopOrderLogic->writeOff();
            $this->ajaxReturn(['status' => 1, 'msg'=>'核销成功']);
        } catch (TpshopException $t) {
            $error = $t->getErrorArr();
            $this->ajaxReturn($error);
        }
    }

    public function statistic(){

        $Pickup =  M('order'); 
        $res = $Pickup->order('order_id desc')->select();
        $this->assign('res',$res);
    	
        return $this->fetch();

    }

    public function index(){
    	if ($_POST) {
    		$order_sn = input('order_sn/s');
            $where = "and  order_sn like '%$order_sn%' ";
            $cwhere['order_sn'] = "like '%$order_sn%' ";
    	}

        // $order_sn = input('order_sn/s');
        // if (empty($order_sn)) {
        //     $res = D('order')->select();
        // } else {
        //     $res = DB::name('order')->where(['order_sn' => ['like', '%' . $order_sn . '%']])->order('order_id')->select();
        // }

        $count = Db::table("tp_order")->join('tp_pick_up',' tp_order.user_id=tp_pick_up.pickup_id')->where($cwhere)->count();
        $page = new Page($count,10);
        $Pickup =  Db::query("select * from tp_order as a,tp_pick_up as b where a.user_id = b.pickup_id $where order by a.order_id desc limit $page->firstRow,$page->listRows");
        // var_dump($Pickup);exit;
        // $res = $Pickup->order('order_id desc')->select();
        $this->assign('page',$page);
        $this->assign('res',$Pickup);


        return $this->fetch();
    }

    public function store(){

        $Pickup =  M('shop'); 
        $res = $Pickup->order('shop_id desc')->select();
        $this->assign('res',$res);


        return $this->fetch();
    }

	

	public function verification(){
		$Pickup =  M('offline_pickup'); 
		$res = $Pickup->order('id desc')->select();
  		$this->assign('res',$res);

		
		
		return $this->fetch();
	}
}

// 	public function place(){
// 		$Pickup =  M(''); 
// 		$res = $Pickup->order('id desc')->select();
//   		$this->assign('res',$res);

		
		
// 		return $this->fetch();
// 	}
// }

