<?php
/**
 * 订单API
 */
namespace app\api\controller;
use app\common\model\Users;
use app\common\logic\UsersLogic;
use app\common\logic\Integral;
use app\common\logic\Pay;
use app\common\logic\PlaceOrder;
use app\common\logic\PreSellLogic;
use app\common\logic\UserAddressLogic;
use app\common\logic\CouponLogic;
use app\common\logic\CartLogic;
use app\common\logic\OrderLogic;
use app\common\model\Combination;
use app\common\model\PreSell;
use app\common\model\Shop;
use app\common\model\SpecGoodsPrice;
use app\common\model\Goods;
use app\common\util\TpshopException;
use think\Loader;
use think\Db;
use app\common\logic\FreightLogic;
use app\home\controller\Api;


class Order extends ApiBase
{

   /**
    * 订单列表
    *  20190505 conflict
    */
    public function order_list()
    {   
        $type = I('post.type/d',0);
		$keyword = I('post.keyword/s','');	
        $user_id = $this->get_user_id();
        if(!$user_id){
            $this->ajaxReturn(['status' => -1 , 'msg'=>'用户不存在','data'=>'']);
        } 
        /*if ($type=='WAITSEND')$data = array('order_status' => [0,1],'shipping_status' =>1,'pay_code'=>'cod'); //'待发货',货到付款
        if ($type=='WAITSEND')$data = array('pay_status'=>1,'order_status'=>[0,1],'shipping_status'=>0,'pay_code'=>['not in','cod']);//'待发货',非货到付款*/
        if ($type == 1)$data = array('tp_order.pay_status'=>1,'tp_order.shipping_status'=>['neq',1],'tp_order.order_status' => ['in','0,1'],);//'待发货'	
        if ($type == 2)$data = array('tp_order.pay_status'=>0,'tp_order.order_status'=>0); //'待支付',
        if ($type == 3)$data = array('tp_order.pay_status'=>1,'tp_order.shipping_status'=>1,'order_status'=>1,);//'待收货',
        if ($type == 4)$data = array('tp_order.order_status'=>2,);//'待评价',  
        // $data = '订单列表数据';
        $data['tp_order.user_id'] = $user_id;
        /*$name = array(
            'tp_order.order_id',//订单id
            'tp_order.add_time',//下单时间
            'tp_order_goods.goods_name',//商品名称
            'tp_order_goods.spec_key_name',//商品规格名
            'tp_order_goods.goods_price',//本店价格
            'tp_order_goods.goods_num',//购买数
            'tp_order.order_amount',//应付金额
            'seller_name',//商家名称
            'tp_goods.original_img',//商品上传原始图
        );*/
		$SellerStore = M('seller_store');
		$OrderGoods = M('order_goods');
		$Goods = M('goods');

		if($keyword){
			$order_id = M('order')->alias('O')->join('tp_order_goods OG','O.order_id=OG.order_id','left')->where(['O.user_id'=>$user_id,'OG.goods_name'=>['like','%'.$keyword.'%']])->group('O.order_id')->order('O.add_time desc')->column('O.order_id'); 
			if($order_id)
				$data['order_id'] = ['in',$order_id];
			else
				$this->ajaxReturn(['status' => 0 , 'msg'=>'获取成功','data'=>null]);
		}


		$page = I('post.page/d',1);
		$num = I('post.num/d',6);
		$limit = (($page - 1) * $num) . ',' . $num;
		$field = 'order_id,seller_id,order_sn,user_id,order_status,shipping_status,pay_status,shipping_price,user_money,order_amount,total_amount,add_time,prom_id,prom_type,order_prom_id';
        $order = Db::name('order')->field($field)->where($data)->order('add_time desc')->limit($limit)->select();
        foreach($order as $k=>$v){
			if(!$v['seller_id']){
				$order[$k]['store_name'] = '平台自营';
				$order[$k]['avatar'] = '';
			}else{
				$ssinfo = $SellerStore->field('store_name,avatar')->where(['seller_id'=>$seller_id])->find();
				$order[$k]['store_name'] = $ssinfo['store_name'];
				$order[$k]['avatar'] = $ssinfo['avatar'];
			}
			$order[$k]['goods'] = $OrderGoods->alias('OG')->field('OG.goods_id,OG.goods_name,OG.goods_num,OG.final_price,OG.item_id,OG.spec_key,OG.spec_key_name,G.original_img')->join('tp_goods G','OG.goods_id=G.goods_id','left')->where(['OG.order_id'=>$v['order_id']])->select();
			$order[$k]['num'] = $OrderGoods->where(['order_id'=>$v['order_id']])->sum('goods_num');
        }
        $this->ajaxReturn(['status' => 0 , 'msg'=>'获取成功','data'=>$order]);
    }

    /**
    * 订单
    */
    public function order_detail()
    {	
        $user_id = $this->get_user_id();
        if(!$user_id){
            $this->ajaxReturn(['status' => -1 , 'msg'=>'用户不存在','data'=>null]);
        }
        $order_id = I('post.order_id/d',0);
        //验证是否本人的
        $order = Db::name('order')->where('order_id',$order_id)->select();
        if(!$order){
            $this->ajaxReturn(['status' => -3 , 'msg'=>'订单不存在','data'=>null]);
        }
        if($order['0']['user_id']!=$user_id){
            $this->ajaxReturn(['status' => -2 , 'msg'=>'非本人订单','data'=>null]);
        }

		$field = 'order_id,seller_id,order_sn,user_id,order_status,shipping_status,pay_status,consignee,country,province,city,district,twon,address,shipping_price,user_money,order_amount,total_amount,mobile,pay_time,pay_name,add_time,prom_id,prom_type,order_prom_id,goods_price,shipping_name,user_note,order_prom_amount,sign_price,integral_money,coupon_price';
        $orderinfo = Db::name('order')->field($field)->order('add_time desc')->find($order_id);
		$Region = M('Region');
		$orderinfo['province_name'] = $orderinfo['province'] ? $Region->where(['id'=>$orderinfo['province']])->value('name') : '';
		$orderinfo['city_name'] = $orderinfo['city'] ? $Region->where(['id'=>$orderinfo['city']])->value('name') : '';
		$orderinfo['district_name'] = $orderinfo['district'] ? $Region->where(['id'=>$orderinfo['district']])->value('name') : '';
		$orderinfo['twon_name'] = $orderinfo['province'] ? $Region->where(['id'=>$orderinfo['twon']])->value('name') : '';
		if(!$orderinfo['seller_id']){
			$orderinfo['store_name'] = '平台自营';
			$orderinfo['avatar'] = '';
		}else{
			$ssinfo = M('Seller_store')->field('store_name,avatar')->where(['seller_id'=>$seller_id])->find();
			$orderinfo['store_name'] = $ssinfo['store_name'];
			$orderinfo['avatar'] = $ssinfo['avatar'];
		}
		$orderinfo['goods'] = M('Order_goods')->alias('OG')->field('OG.goods_id,OG.goods_name,OG.goods_num,OG.final_price,OG.item_id,OG.spec_key,OG.spec_key_name,G.original_img')->join('tp_goods G','OG.goods_id=G.goods_id','left')->where(['OG.order_id'=>$order_id])->select();
		$orderinfo['num'] = M('Order_goods')->where(['order_id'=>$order_id])->sum('goods_num');

        $this->ajaxReturn(['status' => 0 , 'msg'=>'获取成功','data'=>$orderinfo]);
    }

    /**
     * 提交订单
     */
	 public function post_order(){
        $user_id = $this->get_user_id();
        if(!$user_id){
            $this->ajaxReturn(['status' => -1 , 'msg'=>'用户不存在','data'=>null]);
        }
        $address_id = input("address_id/d", 0); //  收货地址id
        $invoice_title = input('invoice_title');  // 发票  
        $taxpayer = input('taxpayer');       // 纳税人识别号
        $invoice_desc = input('invoice_desc');       // 发票内容
        $coupon_id = input("coupon_id/d"); //  优惠券id
        $pay_points = input("pay_points/d", 0); //  使用积分
        $user_money = input("user_money/f", 0); //  使用余额
        $user_note = input("user_note/s", ''); // 用户留言
        $pay_pwd = input("pay_pwd/s", ''); // 支付密码
        $goods_id = input("goods_id/d",0); // 商品id
        $goods_num = input("goods_num/d",0);// 商品数量
        $item_id = input("item_id/d",0); // 商品规格id
        $action = input("action/d",0); // 立即购买
        $shop_id = input('shop_id/d', 0);//自提点id
        $take_time = input('take_time/d');//自提时间
        $consignee = input('consignee/s');//自提点收货人
        $mobile = input('mobile/s');//自提点联系方式
		$act = input('act/d',1);
		$prom_type = input('prom_type/d',0); //0默认1秒杀2团购3优惠促销4预售5虚拟(5其实没用)6拼团7搭配购8竞拍
		$prom_id = input('prom_id/d',0);
        $is_virtual = input('is_virtual/d',0);
        $data = I('POST.');
        $cart_validate = Loader::validate('Cart');
        if($is_virtual === 1){
            $cart_validate->scene('is_virtual');
        } 
        if (($act == 1) && !$cart_validate->check($data)) {
            $error = $cart_validate->getError();
            $this->ajaxReturn(['status' => -4, 'msg' => $error, 'data' => null]);  //留言长度不符或收货人错误
        }
		if($act != 1){
            if($address_id){
                $address = Db::name('user_address')->where(["user_id"=> $user_id,"address_id"=>$address_id])->find();
            }else{
                $address = Db::name('user_address')->where(["user_id"=> $user_id])->order('is_default desc')->find();
            }
        }else{
			$address = Db::name('user_address')->where(["user_id"=> $user_id,"address_id"=>$address_id])->find();
        }

        //判断地址正不正确
        if(!$address){
            $this->ajaxReturn(['status' => -1, 'msg' => '地址不存在', 'data'=> null]);
        }

        $cartLogic = new CartLogic();
        $pay = new Pay();
		$goodsinfo = [];
        try {
            $cartLogic->setUserId($user_id);
            if ($action === 1) {
                $cartLogic->setGoodsModel($goods_id);
                $cartLogic->setSpecGoodsPriceById($item_id);
                $cartLogic->setGoodsBuyNum($goods_num);
                $buyGoods = $cartLogic->buyNow($prom_type,$prom_id,1);
                $cartList[0] = $buyGoods;	
                $pay->payGoodsList($cartList);
				$arr = M('Goods')->field('goods_id,goods_name,shop_price,original_img')->find($goods_id);
				if($item_id){
					$arr['item'] = M('spec_item')->where(['id'=>$item_id])->value('item');
					$price = M('spec_goods_price')->field('price,spec_img')->find($item_id);
					$arr['shop_price'] = $price['price'] ? $price['price'] : $arr['shop_price'];
					$arr['original_img'] = $price['spec_img'] ? $price['spec_img'] : $arr['original_img'];
				}
				$arr['goods_num'] = $goods_num;
				$goodsinfo[] = $arr;
            } else {
                $userCartList = $cartLogic->getCartList(1);
                $cartLogic->checkStockCartList($userCartList);
                $pay->payCart($userCartList);
				$goodsinfo = [];
				foreach($userCartList as $v){
					$arr = [];
					$arr = M('Goods')->field('goods_id,goods_name,shop_price,original_img')->find($v['goods_id']);	
					$arr['item'] = '';
					if($v['item_id']){
						$arr['item'] = M('spec_item')->where(['id'=>$v['item_id']])->value('item');
						$price = M('spec_goods_price')->field('price,spec_img')->find($v['item_id']);
						$arr['shop_price'] = $price['price'] ? $price['price'] : $arr['shop_price'];
						$arr['original_img'] = $price['spec_img'] ? $price['spec_img'] : $arr['original_img'];
					}
					$arr['goods_num'] = $v['goods_num'];
					$goodsinfo[] = $arr;
				}
            } 


			$pay->setUserId($user_id)->setShopById($shop_id)->delivery($address)->orderPromotion()
                ->useCouponById($coupon_id)->getAuction()->getUserSign(1)->useUserMoney($user_money)
                ->usePayPoints($pay_points,false,'mobile');
            // 提交订单
            if ($act == 1) {
                $placeOrder = new PlaceOrder($pay);
                $placeOrder->setMobile($mobile)->setUserAddress($address)->setConsignee($consignee)->setInvoiceTitle($invoice_title)
                    ->setUserNote($user_note)->setTaxpayer($taxpayer)->setInvoiceDesc($invoice_desc)->setPayPsw($pay_pwd)->setTakeTime($take_time)->addNormalOrder();
                $cartLogic->clear();
                $order = $placeOrder->getOrder();
                $this->ajaxReturn(['status' => ($user_money ? 1 : 0), 'msg' => '提交订单成功', 'data' => ['order_sn' => $order['order_sn']] ]);
            }
			$address = M('user_address')->where(['user_id'=>$user_id])->order('is_default desc')->find();
			if($address){
				$address['province_name'] = $address['province'] ? M('region')->where(['id'=>$address['province']])->value('name') : '';
				$address['city_name'] = $address['city'] ? M('region')->where(['id'=>$address['city']])->value('name') : '';
				$address['district_name'] = $address['district'] ? M('region')->where(['id'=>$address['district']])->value('name') : '';
				$address['twon_name'] = $address['twon'] ? M('region')->where(['id'=>$address['twon']])->value('name') : '';
			} 
			$usermoney = M('Users')->where(['user_id'=>$user_id])->value('user_money');
			$this->ajaxReturn(['status' => 0, 'msg' => '计算成功', 'data' => ['user_money'=>$usermoney,'price'=>$pay->toArray(),'address'=>$address,'goodsinfo'=>$goodsinfo]]);
        } catch (TpshopException $t) {
            $error = $t->getErrorArr();
            $this->ajaxReturn(['status' => $error['status'], 'msg' => $error['msg'], 'data'=> null]);
        }	
	 }

	 //取消订单
	 public function CancelOrder(){  
		$user_id = $this->get_user_id();
        if(!$user_id){
            $this->ajaxReturn(['status' => -1 , 'msg'=>'用户不存在','data'=>null]);
        }
		$order_id = I('post.order_id/d',0);
		if(!$order_id){
            $this->ajaxReturn(['status' => -2 , 'msg'=>'订单不存在','data'=>null]);
        }

        //检查是否有积分，余额支付
        $logic = new OrderLogic();
        $data = $logic->cancel_order($user_id, $order_id);	
        $res = Db::name('order_sign_receive')->where('order_id',$order_id)->find();
        if($res['type']==1){
            Db::name('users')->where('user_id',$res['uid']) ->setInc('distribut_free_num');
        }elseif($res['type']==2){
            Db::name('users')->where('user_id',$res['uid']) ->setInc('agent_free_num');
        }

        if ($data['status'] != 1) {
			$this->ajaxReturn(['status' => -2 , 'msg'=>$data['msg'],'data'=>null]);
        } else {
            $this->ajaxReturn(['status' => 0 , 'msg'=>$data['msg'],'data'=>null]);
        }

	 }

	//获取物流
	public function express_detail(){ 
        $user_id = $this->get_user_id();
        if(!$user_id){
            $this->ajaxReturn(['status' => -1 , 'msg'=>'用户不存在','data'=>null]);
        }
        $order_id = I('post.order_id/d',0);
        //验证是否本人的
        $order = Db::name('order')->where('order_id',$order_id)->select();
        if(!$order){
            $this->ajaxReturn(['status' => -3 , 'msg'=>'订单不存在','data'=>null]);
        }
        if($order['0']['user_id']!=$user_id){
            $this->ajaxReturn(['status' => -2 , 'msg'=>'非本人订单','data'=>null]);
        }

        $Api = new Api;
        $data = M('delivery_doc')->where('order_id', $order_id)->find();
        $shipping_code = $data['shipping_code'];
        $invoice_no = $data['invoice_no'];
        $result = $Api->queryExpress($shipping_code, $invoice_no);
        if ($result['status'] == 0) {
            $this->ajaxReturn(['status' => 0 , 'msg'=>'请求成功！','data'=>['invoice_no'=>$invoice_no,'result'=>$result['result']['list']]]);
        }else
			$this->ajaxReturn(['status' => 0 , 'msg'=>'未获取到信息！','data'=>null]);
	}

    /**
     * 确认收货
     */
    public function order_confirm()
    {	
        $user_id = $this->get_user_id();
        if(!$user_id){
            $this->ajaxReturn(['status' => -1 , 'msg'=>'用户不存在','data'=>null]);
        }
        $order_id = I('post.order_id/d',0);
        //验证是否本人的
        $order = Db::name('order')->where('order_id',$order_id)->select();
        if(!$order){
            $this->ajaxReturn(['status' => -3 , 'msg'=>'订单不存在','data'=>null]);
        }
        if($order['0']['user_id']!=$user_id){
            $this->ajaxReturn(['status' => -2 , 'msg'=>'非本人订单','data'=>null]);
        }

        $data = confirm_order($order_id, $user_id);
        if ($data['status'] != 1) {
			$this->ajaxReturn(['status' => -4 , 'msg'=>$data['msg'],'data'=>null]);
        } else {
            $this->ajaxReturn(['status' => 0 , 'msg'=>$data['msg'],'data'=>null]);
        }
    }

	//评论图片上传
	public function common_upload_pic(){ 
        $user_id = $this->get_user_id();
        if(!$user_id){
            $this->ajaxReturn(['status' => -1 , 'msg'=>'用户不存在','data'=>null]);
        }
        if (IS_POST) {
            // 晒图片
            $files = request()->file('pic');
            $save_url = UPLOAD_PATH . 'comment/' . date('Y', time());
            if($files) {	
				// 移动到框架应用根目录/public/uploads/ 目录下
				$image_upload_limit_size = config('image_upload_limit_size');
				$info = $files->validate(['size' => $image_upload_limit_size, 'ext' => 'jpg,png,gif,jpeg'])->move($save_url);

				if ($info) {
					// 成功上传后 获取上传信息
					// 输出 jpg
					$comment_img = '/' . $save_url . '/' . date('Ymd', time()) . '/' . $info->getFilename();
				} else {
					// 上传失败获取错误信息
					$this->ajaxReturn(['status' =>-1,'msg' =>$files->getError()]);
				}
            }
			$this->ajaxReturn(['status' => 0 , 'msg'=>'上传成功','data'=>['dir'=>$comment_img]]);
		}
	}

	//订单评论
	public function order_common(){ 
        $user_id = $this->get_user_id();
        if(!$user_id){
            $this->ajaxReturn(['status' => -1 , 'msg'=>'用户不存在','data'=>null]);
        }
		$user_info = M('Users')->field('nickname,email')->find($user_id);
		$order_id = I('post.order_id/d',0); 
		if(!$order_id){
            $this->ajaxReturn(['status' => -2 , 'msg'=>'订单不存在','data'=>null]);
        }
		$info = json_decode(htmlspecialchars_decode(I('post.info/s',''))); //共提交几个商品评论

		$orderinfo = M('Order')->field('order_status')->where(['user_id'=>$user_id])->find($order_id);
		if(!$orderinfo){
            $this->ajaxReturn(['status' => -2 , 'msg'=>'订单不存在','data'=>null]);
        }
		if($orderinfo['order_status'] != 2){
            $this->ajaxReturn(['status' => -3 , 'msg'=>'该订单不能评价','data'=>null]);
        }
		
		$Comment = M('comment');
        $OrderGoods = M('Order_goods');
        $Goods = M('Goods');
		foreach($info as $v){
			$Comment->add([
				'goods_id'	=> $v->goods_id,
				'email'		=> $user_info['email'] ? $user_info['email'] : '',
				'username'	=> $user_info['nickname'] ? $user_info['nickname'] : '',
				'content'	=> $v->content ? $v->content : '',
				'add_time'	=> time(),
				'user_id'	=> $user_id ,
				'img'		=> $v->img ? serialize($v->img) : '',
				'order_id'	=> $order_id,
				'deliver_rank'	=> $v->deliver_rank ? $v->deliver_rank : '',
				'goods_rank'	=> $v->goods_rank ? $v->goods_rank : '',
				'service_rank'	=> $v->service_rank ? $v->service_rank : '',
				'is_anonymous'	=> $v->is_anonymous ? $v->is_anonymous : '',
				'rec_id'	=> $OrderGoods->where(['order_id'=>$order_id,'goods_id'=>$v->goods_id])->value('rec_id'),
            ]);
            $Goods->where(['goods_id'=>$v->goods_id])->setInc('comment_count',1);
            
		}

		M('Order')->update(['order_id'=>$order_id,'order_status'=>4]);

		$this->ajaxReturn(['status' => 0 , 'msg'=>'请求成功','data'=>null]);
	}
	  
}
