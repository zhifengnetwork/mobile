<?php
/**
 * DC环球直供网络
 * ============================================================================
 * * 版权所有 2015-2027 广州滴蕊生物科技有限公司，并保留所有权利。
 * 网站地址: http://www.dchqzg1688.com
 * ----------------------------------------------------------------------------
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和使用 .
 * 不允许对程序代码以任何形式任何目的的再发布。
 * 采用最新Thinkphp5助手函数特性实现单字母函数M D U等简写方式
 * ============================================================================
 * $Author: 当燃   2016-05-10
 */ 
// namespace app\mobile\controller;
namespace app\api\controller;
use app\common\logic\GoodsLogic;
use app\common\model\FlashSale;
use app\common\model\GroupBuy;
use app\common\model\Auction;
use app\common\model\PreSell;
use think\Db;
use think\Page;
use app\common\logic\ActivityLogic;
use app\common\logic\FreightLogic;
use app\common\logic\OrderLogic;

class Activity extends ApiBase {
    /**
     * 抢购活动列表
     */
    public function flash_sale_list()
    {
		$page = I('post.page/d',1);
		$num = I('post.num/d',6);
		$limit = (($page - 1)) * $num . ',' . $num;	    

        $start_time = I('post.start_time/d',0);
        $end_time = I('post.end_time/d',0);
		if(!$start_time || !$end_time)$this->ajaxReturn(['status' => -2, 'msg' => '请传入开始时间和结束时间！', 'data' => null]);
        $where = array(
            'fl.start_time'=>array('egt',$start_time),
            'fl.end_time'=>array('elt',$end_time),
            'g.is_on_sale'=>1,
            'fl.is_end'=>0
        );
		
		$field = 'fl.id,fl.title,fl.goods_id,fl.item_id,fl.price,fl.goods_num,fl.order_num,fl.start_time,fl.end_time,fl.goods_name,g.shop_price,g.original_img';
        $flash_sale_goods = M('Flash_sale')->alias('fl')->join('__GOODS__ g', 'g.goods_id = fl.goods_id','left')
            ->field($field)
            ->where($where)
            ->limit($limit)
            ->select();
			
		$SpecGoodsPrice = M('spec_goods_price');	
		foreach($flash_sale_goods as $k=>$v){
			if($v['item_id']){
				$info = $SpecGoodsPrice->field('price,spec_img')->find($v['item_id']);
				if($info['price']){
					$flash_sale_goods[$k]['shop_price'] = $info['price'];  //更新本店价
					$flash_sale_goods[$k]['disc']  = 100 * number_format(($v['price']/$info['price']),1);  //折扣
				}
				if($info['spec_img'])$flash_sale_goods[$k]['original_img'] = $info['spec_img'];
			}
            $flash_sale_goods[$k]['disc']  = 100 * number_format(($v['price']/$v['shop_price']),1);  //折扣
		}

        $this->ajaxReturn(['status' => 0, 'msg' => '请求成功', 'data' => ['flash_sale_goods'=>$flash_sale_goods]]);
    }

	/**
     * 获取抢购活动时间列表
     */
	 public function get_flash_sale_time(){
		$now_day = date('Y-m-d');
		$now_time = date('H');
		if ($now_time % 2 == 1) {
			$flash_now_time = $now_time;
		} else {
			$flash_now_time = $now_time - 1;
		} 
		$flash_sale_time = strtotime($now_day . " " . $flash_now_time . ":00:00");
		$space = 7200;

		$time_space_past = $time_space_future = [];
		for($i=1; $i<=22; $i++){
			if($i <= 11){
				$time_space_past[] = ['font' => date("Y-m-d H:i", $flash_sale_time - ($i*$space)), 'start_time' => $flash_sale_time - ($i*$space), 'end_time' => $flash_sale_time - (($i-1)*$space)];
			}else{
				$time_space_future[] = ['font' => date("Y-m-d H:i", $flash_sale_time + (($i-11-1)*$space)), 'start_time' => $flash_sale_time + (($i-11-1)*$space), 'end_time' => $flash_sale_time + (($i-11)*$space)];
            }
		}			
		$this->ajaxReturn(['status' => 0, 'msg' => '请求成功', 'data' => ['time_space_past'=>$time_space_past,'time_space_future'=>$time_space_future]]);
	 }

	/**
     * 获取抢购活动详情
     */
	 public function flash_sale_info(){
		$user_id = $this->get_user_id();
        if(!$user_id){
            $this->ajaxReturn(['status' => -1 , 'msg'=>'用户不存在','data'=>null]);
        }
		
		$id = I('post.id/d',0);
		if(!$id)$this->ajaxReturn(['status' => -1 , 'msg'=>'参数错误！','data'=>null]);
	
		$field = 'fl.id,fl.title,fl.goods_id,fl.item_id,fl.price,fl.goods_num,fl.order_num,fl.start_time,fl.end_time,fl.goods_name,g.cat_id,g.seller_id,g.is_on_sale,fl.is_end,g.store_count,g.sales_sum,g.shop_price,g.goods_content,g.original_img';
        $info = M('Flash_sale')->alias('fl')->join('__GOODS__ g', 'g.goods_id = fl.goods_id','left')
            ->field($field)
            ->find($id);
			
		$SpecGoodsPrice = M('spec_goods_price');	

        //$info['goods_content'] = $info['goods_content'] ? stripslashes($info['goods_content']) : '';

		if($info['item_id']){
			$spe_info = $SpecGoodsPrice->field('price,store_count,spec_img')->find($info['item_id']);
			if($spe_info['price']){
				$info['shop_price'] = $spe_info['price'];  //更新本店价
				$info['disc']  = 100 * number_format(($info['price']/$spe_info['price']),1);  //折扣
			}
			if($spe_info['spec_img'])$info['original_img'] = $spe_info['spec_img'];
			$info['store_count'] = $spe_info['store_count'];
		}
		
		$seller_info = ['store_id'=>'','store_name'=>'','avatar'=>0];
		if($info['seller_id']){
			$seller_info = M('seller_store')->field('store_id,store_name,avatar,province,city')->where(['seller_id'=>$info['seller_id'],'auditing'=>10,'is_delete'=>10])->find();
		}else{
			$seller_info['province'] = M('Config')->where(['name'=>'province','inc_type'=>'shop_info'])->value('value');
			$seller_info['city'] = M('Config')->where(['name'=>'city','inc_type'=>'shop_info'])->value('value');
		}

		$Region = M('region');
		$seller_info['province_name'] = $seller_info['province'] ? $Region->where(['id'=>$seller_info['province']])->value('name') : '';
		$seller_info['city_name'] = $seller_info['city'] ? $Region->where(['id'=>$seller_info['city']])->value('name') : '';

		$goodsModel = new \app\common\model\Goods();
		$info['comment_fr'] = $goodsModel->getCommentStatisticsAttr('', ['goods_id' => $info['goods_id']]);

		$info['goods_content'] = htmlspecialchars_decode($info['goods_content']); 
		$goods_content = str_replace('/public/upload/goods/',SITE_URL."/public/upload/goods/",$info['goods_content']); 
		unset($info['goods_content']);

		//获取商品图片
		$info['goods_images'] = M('Goods_images')->where(['goods_id'=>$info['goods_id']])->column('image_url');
        echo json_encode(['status' => 0, 'msg' => '请求成功', 'data' => ['info'=>$info,'seller_info'=>$seller_info,'goods_content'=>$goods_content]]);
	 }

    /**
     * 提交抢购
     */
    public function post_flash_sale()
    {	
		$user_id = $this->get_user_id();
        if(!$user_id){
            $this->ajaxReturn(['status' => -1 , 'msg'=>'用户不存在','data'=>null]);
        }
		
		$id = I('post.id/d',0);		
		$address_id = input("post.address_id/d", 0); //  收货地址id
		if(!$id || !$address_id)$this->ajaxReturn(['status' => -2 , 'msg'=>'参数错误！','data'=>null]);

		$FlashSale = M('Flash_sale');
		$info = $FlashSale->field('goods_id,price,goods_num,buy_limit,buy_num,order_num,start_time,is_end')->find($id);
		if($info['order_num'] >= $info['goods_num'])$this->ajaxReturn(['status' => -3 , 'msg'=>'下单数已满！','data'=>null]);
        if($info['start_time'] > time())$this->ajaxReturn(['status' => -9 , 'msg'=>'活动还未开始！','data'=>null]);
        if($info['is_end'])$this->ajaxReturn(['status' => -10 , 'msg'=>'活动已经结束！','data'=>null]);
        
		$address = Db::name('user_address')->field('consignee,email,country,province,city,district,twon,address,zipcode,mobile')->where("address_id", $address_id)->find();
		if(!$address)$this->ajaxReturn(['status' => -4 , 'msg'=>'请先填写收货地址！','data'=>null]);

		$Order = M('Order');
		$num = $Order->where(['prom_id'=>$id,'user_id'=>$user_id])->count();
		if($num >= $info['buy_limit'])$this->ajaxReturn(['status' => -6 , 'msg'=>'您已达到限购件数！','data'=>null]);
		if($num >= $info['goods_num'])$this->ajaxReturn(['status' => -3 , 'msg'=>'下单数已满！','data'=>null]);

		$OrderLogic = new OrderLogic();
        $Goods = new \app\common\model\Goods();
        $goods = $Goods::get($info['goods_id']);	
        $freightLogic = new FreightLogic();
        $freightLogic->setGoodsModel($goods);
        $freightLogic->setRegionId($address['city']);
        $freightLogic->setGoodsNum(1);
        $isShipping = $freightLogic->checkShipping();		
        if ($isShipping) {
            $freightLogic->doCalculation();
            $freight = $freightLogic->getFreight();
        } else {
            $dispatching_data = ['status' => -5, 'msg' => '该地区不支持配送', 'data' => null];
        }	
		// 启动事务
        Db::startTrans();
        try{		
            $data=[
                'user_id'			=> $user_id,
                'seller_id'			=> M('goods')->where(['goods_id'=>$info['goods_id']])->getField('seller_id'),
                'order_sn'			=> $OrderLogic->get_order_sn(),
                'consignee'			=> $address['consignee'],
				'country'			=> $address['country'],
				'province'			=> $address['province'],
				'city'				=> $address['city'],
				'district'			=> $address['district'],
				'twon'				=> $address['twon'],
				'address'			=> $address['address'],
				'zipcode'			=> $address['zipcode'],
				'mobile'			=> $address['mobile'],		
				'email'				=> $address['email'],
				'goods_price'		=> $info['price'],
				'shipping_price'	=> $freight,
				'order_amount'		=> ($info['price'] + $freight),
				'total_amount'		=> ($info['price'] + $freight),
				'add_time'			=> time(),
				'prom_id'			=> $id,
				'prom_type'			=> 1
            ];
            $res = $Order->lock(true)->add($data);
			if($res){
				$FlashSale->where(['id'=>$id])->setInc('buy_num',1);	
				$FlashSale->where(['id'=>$id])->setInc('order_num',1);

				$info = $FlashSale->field('goods_id,price,goods_num,buy_limit,buy_num,order_num')->find($id);
				if($info['order_num'] > $info['goods_num']){
					// 回滚事务
					Db::rollback();
					$this->ajaxReturn(['status' => -3 , 'msg'=>'下单数已满！','data'=>null]);
				}  
			}else{
				// 回滚事务
				Db::rollback();
				$this->ajaxReturn(['status' => -4 , 'msg'=>'提交失败！','data'=>null]);
			}

            // 提交事务
            Db::commit(); 
			$this->ajaxReturn(['status' => 0 , 'msg'=>'提交成功！','data'=>['order_id'=>$res]]);
        } catch (TpshopException $t) {
            // 回滚事务
            Db::rollback();
            $error = $t->getErrorArr();
            $this->ajaxReturn(['status' => -5 , 'msg'=>$error,'data'=>null]);
        }

    }

    /**
     * 竞拍列表
     */
    public function auction_list()
    {	
		$page = I('post.page/d',1);
		$num = I('post.num/d',6);
										
		$goods = C('database.prefix') . 'goods';
		$field = 'A.id,A.goods_id,A.activity_name,A.goods_name,A.start_price,A.start_time,A.end_time,G.original_img';
		$limit = (($page - 1)) * $num . ',' . $num;	
		//先按小于当前时间升序，再按大于当前时间上升序
		$sort = "A.start_time > UNIX_TIMESTAMP(NOW()),IF(A.start_time > UNIX_TIMESTAMP(NOW()), 0, A.start_time), A.start_time ASC";
		$list = M('Auction')->alias('A')->field($field)->join("$goods G" ,"A.goods_id=G.goods_id",'LEFT')->where(['A.auction_status'=>1,'A.is_end'=>0,'G.is_on_sale'=>1])->order($sort)->limit($limit)->select();	
        $this->ajaxReturn(['status' => 0 , 'msg'=>'获取成功','data'=>['list'=>$list]]);
    }

    /**
     * 竞拍详情
     */
    public function auction_info()
    {	
        $user_id = $this->get_user_id();
        if(!$user_id)$this->ajaxReturn(['status' => -1 , 'msg'=>'用户不存在','data'=>null]);	

		$auction_id = I('post.auction_id/d',0);
		if(!$auction_id)$this->ajaxReturn(['status' => -2 , 'msg'=>'竞拍参数错误','data'=>null]);

		$goods = C('database.prefix') . 'goods';
		$field = 'A.id,A.goods_id,A.activity_name,A.goods_name,A.start_price,A.start_time,A.end_time,A.increase_price,G.original_img';
		$info = M('Auction')->alias('A')->field($field)->join("$goods G" ,"A.goods_id=G.goods_id",'LEFT')->where(['A.is_end'=>0])->order("A.preview_time desc")->find();	
       
       //如果已结束，则返回竞拍成功的用户竞价信息
       if($info['is_end'] == 1){
           $price_info = M('auction_price')->where(['is_out'=>2])->find();
       }

        $this->ajaxReturn(['status' => 0 , 'msg'=>'获取成功','data'=>['list'=>$info,'price_info'=>$price_info]]);
    }

    /**
     * 领券
     */
    public function getCoupon()
    {
        $id = I('coupon_id/d');
        $user = session('user');
        $user['user_id'] = $user['user_id'] ?: 0;
        $activityLogic = new ActivityLogic();
        $return = $activityLogic->get_coupon($id, $user['user_id']);
        $this->ajaxReturn($return);
    }

    public function pre_sell_list()
    {
        $p = input('p', 1);
        $PreSell = new PreSell();
        //$pre_sell_list = $PreSell->where(['sell_end_time'=>['gt',time()],'is_finished' => 0])->order(['pre_sell_id' => 'desc'])->page($p, 10)->select();
        $type = input('type', 0);

        if($type == 1){
            $order['is_new'] = 'desc';
        }elseif($type == 2){
            $order['comment_count'] = 'desc';
        }else{
            $order = ['pre_sell_id' => 'desc'];
        }
        $pre_sell_list = Db::view('PreSell','pre_sell_id,goods_id,item_id,goods_name,deposit_goods_num,sell_end_time')
            ->view('Goods','is_new,sort,comment_count,collect_sum','Goods.goods_id=PreSell.goods_id')
            ->where(['sell_end_time'=>['gt',time()],'is_finished' => 0])
            ->page($p, 10)
            ->order($order)
            ->select();
        foreach($pre_sell_list as $k => $v){
            $pre_sell = $PreSell::get($v['pre_sell_id']);
            $pre_sell_list[$k]['ing_price'] = $pre_sell->ing_price;
        }
        $this->assign('pre_sell_list', $pre_sell_list);
        if (request()->isAjax()) {
            return $this->fetch('ajax_pre_sell_list');
        }
        return $this->fetch();
    }

    /**
     * +---------------------------------
     * 用户优惠券中心列表
     * 0 未使用 1已使用 2已过期
     * +---------------------------------
    */
    public function coupon_list()
    {
        $user_id = $this->get_user_id();
        if (!IS_POST) {
            $this->ajaxReturn(['status' => -1 , 'msg'=>'提交方式错误','data'=>'']);
        }
        $status = I('status', 0);
        $p = I('p', '');
        $result = $this->getCouponList($status, $user_id, $p);
        
        $this->ajaxReturn(['status' => 0 , 'msg'=>'获取成功','data'=>$result]);
    }

    /**
     * 获取优惠券信息
     * @param type $status 状态 0未使用 1已使用 2已过期
     * @param $user_id  用户ID
     * @param int $p 第几页
     * @return array
     */
    public function getCouponList($status, $user_id, $p = 1)
    {
        $time = time();
        $where = array('status'=>1,'send_start_time'=>['elt',time()],'send_end_time'=>['egt',time()]);
        $order = array('id' => 'desc');
        if ($status == 1) {
            //已使用
            $order = ['spacing_time' => 'asc'];
            // $where["send_end_time-'$time'"] = ['egt', 0];

        } elseif ($status == 2) {
            //已过期
            $order = ['money' => 'desc'];
        }
        if ($user_id) {
            $user_coupon = M('coupon_list')->where(['uid' => $user_id, 'status'=>$status])->getField('id,cid,use_time,code,send_time,status',true);
        }

        if (is_array($user_coupon) && count($user_coupon) > 0) {
            $coupon_list = M('coupon')
            ->field("id cid,name,type,money,condition,use_start_time,use_end_time,status,use_type,send_end_time-'$time' as spacing_time")
            ->where($where)->page($p, 15)
            ->order($order)->select();
            if (!empty($user_coupon)) {
                foreach ($coupon_list as $k => $val) {
                    $coupon_list[$k]['use_scope'] = C('COUPON_USER_TYPE')[$coupon_list[$k]['use_type']];
                    foreach($user_coupon as $ks=>$vs){
                        if($val['cid'] == $vs['cid']){
                            $coupon_list[$k]['coupon_code'] =$vs['code'];
                            $coupon_list[$k]['cid'] =$vs['id'];
                        }
                    }
                }
            }
        }
        
        return $coupon_list;
    }

    public function group_list()
    {
        $type =I('post.type/s','');
        $page =I('post.page/d',1);
        $num =I('post.num/d',6);
        $sort =I('post.sort/s','desc');
        //以最新新品排序
        if ($type == 'new') {
            $order = 'gb.start_time ';
        } elseif ($type == 'comment') {
            $order = 'g.comment_count ';
        } else {
            $order = '';
        }

        if($order)$order .= $sort;

        $limit = ($page-1) * $num . ',' . $num;
        $group_by_where = array(
            'gb.start_time'=>array('lt',time()),
            'gb.end_time'=>array('gt',time()),
            'g.is_on_sale'=>1,
            'gb.is_end'            =>0,
        );
        $GroupBuy = new GroupBuy();

        $list = $GroupBuy
            ->alias('gb')
            ->field('g.goods_id,g.goods_name,g.original_img,g.comment_count,gb.id,gb.title,gb.start_time,gb.end_time,gb.item_id,gb.price,gb.goods_num,gb.buy_num,gb.order_num,gb.virtual_num,gb.rebate,gb.goods_price')
            ->join('__GOODS__ g', 'gb.goods_id=g.goods_id AND g.prom_type=2')
            ->where($group_by_where)
            ->page($limit)
            ->order($order)
            ->select();

            $SpecGoodsPrice = M('spec_goods_price');
            foreach($list as $k=>$v){
                $info = $SpecGoodsPrice->field('key,key_name,price,spec_img')->find($v['item_id']);
                $info['price'] && $list[$k]['price'] = $info['price'];
                $info['spec_img'] && $list[$k]['original_img'] = $info['spec_img'];
                if(($v['buy_num'] < 10) && ($v['buy_num'] < $v['virtual_num']))$list[$k]['num'] = $v['virtual_num'];
            }

        $this->ajaxReturn(['status' => 0 , 'msg'=>'获取成功','data'=>['list'=>$list]]);

    }    

}