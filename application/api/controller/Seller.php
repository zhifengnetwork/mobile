<?php
/**
 * 用户API
 */
namespace app\api\controller;
use think\Db;

class Seller extends ApiBase
{

	//获取店铺列表
	public function GetSellerList(){ 
		$user_id = $this->get_user_id();
		if(!$user_id){
			$this->ajaxReturn(['status' => -1 , 'msg'=>'用户不存在','data'=>null]);
		}

		$page = I('post.page/d',1);
		$num = I('post.num/d',6);
		$limit = ($page-1)*$num . ',' . $num;

		$goodsnum = I('post.goodsnum/d',6);

		$list = M('seller_store')->field('store_id,store_name,avatar,businesshours,email,seller_id')->where(['auditing'=>10,'is_delete'=>10])->order('add_time asc')->limit($limit)->select();

		$Goods = M('goods');
		$SellerCollect = M('seller_collect');
		foreach($list as $k=>$v){
			$list[$k]['goods'] = $Goods->field('goods_id,goods_name,shop_price,original_img')->where(['seller_id'=>$v['seller_id'],'is_on_sale'=>1])->limit('0,'.$goodsnum)->select();
			$list[$k]['collect_num'] = $SellerCollect->where(['seller_id'=>$v['seller_id']])->count();
			$list[$k]['is_collect'] = $SellerCollect->where(['user_id'=>$user_id,'seller_id'=>$v['seller_id']])->count();
		}

		$this->ajaxReturn(['status' => 0 , 'msg'=>'请求成功','data'=>['list'=>$list]]);
	}


}
