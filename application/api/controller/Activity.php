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

class Activity extends ApiBase {
    /**
     * 抢购活动列表
     */
    public function flash_sale_list()
    {
		$page = I('post.page/d',1);
		$num = I('post.num/d',6);
		$limit = (($page - 1)) * $num . ',' . $num;	    

        $start_time = I('post.start_time/d',155489400);
        $end_time = I('post.end_time/d',155489400);
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
			}else
				$time_space_future[] = ['font' => date("Y-m-d H:i", $flash_sale_time + (($i-1)*$space)), 'start_time' => $flash_sale_time + (($i-1)*$space), 'end_time' => $flash_sale_time + ($i*$space)];
		}			
		$this->ajaxReturn(['status' => 0, 'msg' => '请求成功', 'data' => ['time_space_past'=>$time_space_past,'time_space_future'=>$time_space_future]]);
	 }

	/**
     * 获取抢购活动详情
     */
	 public function flash_sale_info(){	  
		$user_id = $this->get_user_id();
        if(!$user_id){
            $this->ajaxReturn(['status' => -1 , 'msg'=>'用户不存在','data'=>'']);
        }	
		
		$id = I('post.id/d',0);
		if(!$id)$this->ajaxReturn(['status' => -1 , 'msg'=>'参数错误！','data'=>'']);
	
		$field = 'fl.id,fl.title,fl.goods_id,fl.item_id,fl.price,fl.goods_num,fl.order_num,fl.start_time,fl.end_time,fl.goods_name,g.is_on_sale,fl.is_end,g.store_count,g.sales_sum,g.shop_price,g.original_img';
        $info = M('Flash_sale')->alias('fl')->join('__GOODS__ g', 'g.goods_id = fl.goods_id','left')
            ->field($field)
            ->find($id);
			
		$SpecGoodsPrice = M('spec_goods_price');	
	 
		if($info['item_id']){
			$spe_info = $SpecGoodsPrice->field('price,store_count,spec_img')->find($info['item_id']);
			if($spe_info['price']){
				$info['shop_price'] = $spe_info['price'];  //更新本店价
				$info['disc']  = 100 * number_format(($info['price']/$spe_info['price']),1);  //折扣
			}
			if($spe_info['spec_img'])$info['original_img'] = $spe_info['spec_img'];
			$info['store_count'] = $spe_info['store_count'];
		}

		//获取商品图片
		$info['goods_images'] = M('Goods_images')->where(['goods_id'=>$info['goods_id']])->column('image_url');
		$this->ajaxReturn(['status' => 0, 'msg' => '请求成功', 'data' => ['info'=>$info]]);
	 }

    /**
     * 竞拍列表
     */
    public function auction_list()
    {	
		$page = I('post.page/d',1);
		$num = I('post.num/d',6);

		$goods = C('database.prefix') . 'goods';
		$field = 'A.id,A.goods_id,A.activity_name,A.goods_name,A.start_price,A.start_time,G.original_img';
		$limit = (($page - 1)) * $num . ',' . $num;
		$list = M('Auction')->alias('A')->field($field)->join("$goods G" ,"A.goods_id=G.goods_id",'LEFT')->where(['A.auction_status'=>1,'A.is_end'=>0])->order("A.preview_time desc")->limit($limit)->select();	
        $this->ajaxReturn(['status' => 0 , 'msg'=>'获取成功','data'=>['list'=>$list]]);
    }

    /**
     * 竞拍详情
     */
    public function auction_info()
    {	
        $user_id = $this->get_user_id();
        if(!$user_id)$this->ajaxReturn(['status' => -1 , 'msg'=>'用户不存在','data'=>'']);

		$auction_id = I('post.auction_id/d',0);
		if(!$auction_id)$this->ajaxReturn(['status' => -2 , 'msg'=>'竞拍参数错误','data'=>'']);

		$goods = C('database.prefix') . 'goods';
		$field = 'A.id,A.goods_id,A.activity_name,A.goods_name,A.start_price,A.start_time,A.end_time,A.increase_price,G.original_img';
		$list = M('Auction')->alias('A')->field($field)->join("$goods G" ,"A.goods_id=G.goods_id",'LEFT')->where(['A.is_end'=>0])->order("A.preview_time desc")->find();	
        $this->ajaxReturn(['status' => 0 , 'msg'=>'获取成功','data'=>['list'=>$list]]);
    }

    public function coupon_list()
    {
        $atype = I('atype', 1);
        $user = session('user');
        $p = I('p', '');

        $activityLogic = new ActivityLogic();
        $result = $activityLogic->getCouponList($atype, $user['user_id'], $p);
        $this->assign('coupon_list', $result);
        if (request()->isAjax()) {
            return $this->fetch('ajax_coupon_list');
        }
        return $this->fetch();
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

}