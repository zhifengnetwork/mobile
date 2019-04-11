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
    public function index(){      
        return $this->fetch();
    }

    /**
     * 团购活动列表
     */
    public function group_list()
    {
        $type =I('get.type');
        //以最新新品排序
        if ($type == 'new') {
            $order = 'gb.start_time desc';
        } elseif ($type == 'comment') {
            $order = 'g.comment_count desc';
        } else {
            $order = '';
        }
        $group_by_where = array(
            'gb.start_time'=>array('lt',time()),
            'gb.end_time'=>array('gt',time()),
            'g.is_on_sale'=>1,
            'gb.is_end'            =>0,
        );
        $GroupBuy = new GroupBuy();
    	$count =  $GroupBuy->alias('gb')->join('__GOODS__ g', 'g.goods_id = gb.goods_id')->where($group_by_where)->count();// 查询满足要求的总记录数
        $pagesize = C('PAGESIZE');  //每页显示数
    	$page = new Page($count,$pagesize); // 实例化分页类 传入总记录数和每页显示的记录数
    	$show = $page->show();  // 分页显示输出
    	$this->assign('page',$show);    // 赋值分页输出
        $list = $GroupBuy
            ->alias('gb')
            ->join('__GOODS__ g', 'gb.goods_id=g.goods_id AND g.prom_type=2')
            ->where($group_by_where)
            ->page($page->firstRow, $page->listRows)
            ->order($order)
            ->select();
        $this->assign('list', $list);
        if(I('is_ajax')) {
            return $this->fetch('ajax_group_list');      //输出分页
        }
        return $this->fetch();
    }

    /**
     * 活动商品列表
     */
    public function discount_list(){
        $prom_id = I('id/d');    //活动ID
        $where = array(     //条件
            'is_on_sale'=>1,
            'prom_type'=>3,
            'prom_id'=>$prom_id,
        );
        $count =  M('goods')->where($where)->count(); // 查询满足要求的总记录数
         $pagesize = C('PAGESIZE');  //每页显示数
        $Page = new Page($count,$pagesize); //分页类
        $prom_list = Db::name('goods')->where($where)->limit($Page->firstRow.','.$Page->listRows)->select(); //活动对应的商品
        $spec_goods_price = Db::name('specGoodsPrice')->where(['prom_type'=>3,'prom_id'=>$prom_id])->select(); //规格
        foreach($prom_list as $gk =>$goods){  //将商品，规格组合
            foreach($spec_goods_price as $spk =>$sgp){
                if($goods['goods_id']==$sgp['goods_id']){
                    $prom_list[$gk]['spec_goods_price']=$sgp;
                }
            }
        }
        foreach($prom_list as $gk =>$goods){  //计算优惠价格
            $PromGoodsLogicuse = new \app\common\logic\PromGoodsLogic($goods,$goods['spec_goods_price']);
            if(!empty($goods['spec_goods_price'])){
                $prom_list[$gk]['prom_price']=$PromGoodsLogicuse->getPromotionPrice($goods['spec_goods_price']['price']);
            }else{
                $prom_list[$gk]['prom_price']=$PromGoodsLogicuse->getPromotionPrice($goods['shop_price']);
            }

        }
        $this->assign('prom_list', $prom_list);
        if(I('is_ajax')){
            return $this->fetch('ajax_discount_list');
        }
        return $this->fetch();
    }

    /**
     * 商品活动页面
     * @author lxl
     * @time2017-1
     */
    public function promote_goods(){
        $now_time = time();
        $where = " start_time <= $now_time and end_time >= $now_time and is_end = 0";
        $count = M('prom_goods')->where($where)->count();  // 查询满足要求的总记录数
        $pagesize = C('PAGESIZE');  //每页显示数
        $Page  = new Page($count,$pagesize); //分页类
        $promote = M('prom_goods')->field('id,title,start_time,end_time,prom_img')->where($where)->limit($Page->firstRow.','.$Page->listRows)->select();    //查询活动列表
        $this->assign('promote',$promote);
        if(I('is_ajax')){
            return $this->fetch('ajax_promote_goods');
        }
        return $this->fetch();
    }


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
            //'fl.start_time'=>array('egt',$start_time),
            //'fl.end_time'=>array('elt',$end_time),
            'g.is_on_sale'=>1,
            'fl.is_end'=>0
        );
        $FlashSale = new FlashSale();		
		$field = 'fl.id,fl.title,fl.goods_id,fl.item_id,fl.price,fl.goods_num,fl.order_num,fl.start_time,fl.end_time,fl.goods_name,g.shop_price,g.original_img';
        $flash_sale_goods = M('Flash_sale')->alias('fl')->join('__GOODS__ g', 'g.goods_id = fl.goods_id','left')
            ->field($field)
            ->where($where)
            ->limit($limit)
            ->select();
			
		$SpecGoodsPrice = M('spec_goods_price');	
		$info = $SpecGoodsPrice->field('price,spec_img')->find(13); 
		foreach($flash_sale_goods as $k=>$v){
			$info = $SpecGoodsPrice->field('price,spec_img')->find($v['item_id']);
			if($info['price']){
				$flash_sale_goods[$k]['shop_price'] = $info['price'];  //更新本店价
				$flash_sale_goods[$k]['disc']  = 100 * number_format(($v['price']/$info['price']),1);  //折扣
			}
			if($info['spec_img'])$flash_sale_goods[$k]['original_img'] = $info['spec_img'];
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