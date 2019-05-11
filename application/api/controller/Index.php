<?php
/**
 * 用户API
 */
namespace app\api\controller;
use app\common\model\Users;
use app\common\logic\UsersLogic;
use think\Db;

class Index extends ApiBase
{

    /**
     * 首页滚动订单
     */
    public function virtual_order()
    {
        $result = M('order')->alias('order')->join('users user', 'user.user_id = order.user_id', 'LEFT')
                ->where('order.pay_status', 1)->order('order_id DESC')->limit(60)
                ->field('order.pay_time, user.nickname, user.head_pic')->select();
        
        foreach($result as $k => $v){
            $result[$k]['content'] = '最新订单来自' . $v['nickname'] . ', ' . friend_date($v['pay_time']);
            unset($result[$k]['pay_time']);
            unset($result[$k]['nickname']);
        }
        // $virtual_list = M('virtual_order')->where('is_show', '1')->column('id, head_ico, content');
        if($result){
            $this->ajaxReturn(['status' => 0 , 'msg'=>'获取成功','data'=>$result]);
            exit;
        }else{
            $this->ajaxReturn(['status' => -1, 'msg'=>'获取失败','data'=>'']);
            exit;
        }
        
    }

	//首页接口
	public function index(){
		$pid = I('post.pid/d',9);
		$cat_id = I('post.cat_id/d',15);
		$time = time();
		$adlist = M('Ad')->field('ad_id,ad_link,ad_code,bgcolor')->where(['pid'=>$pid,'start_time'=>['elt',$time],'end_time'=>['egt',$time],'enabled'=>1])->order('orderby asc')->select();
		foreach($adlist as $k=>$v){
			if(false !== strpos($v['ad_link'],'Mobile/Goods/goodsInfo/id')){ 
				$urlarr = pathinfo($v['ad_link']);
				$adlist[$k]['goods_id'] = $urlarr['filename'];
			}else
				$adlist[$k]['goods_id'] = 0;
		}

		//头条
		$articlelist = M('article')->field('article_id,title,link')->where(['cat_id'=>$cat_id,'is_open'=>1])->select();

		//当前时段的秒杀
		$now_day = date('Y-m-d');
		$now_time = date('H'); 
		if ($now_time % 2 == 1) {
			$flash_now_time = $now_time;
		} else {
			$flash_now_time = $now_time - 1;
		} 
		$start_time = strtotime($now_day . " " . $flash_now_time . ":00:00");
		$end_time = $start_time + 7200;
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
            ->limit('0,6')
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


		//广告
		$ad_top =   $this->get_one_ad(400);
		$ad_left1 = $this->get_one_ad(301);
		$ad_left2 = $this->get_one_ad(302);
		$ad_right = $this->get_one_ad(300);

		$new_left = $this->get_one_ad(303);
		$new_right = $this->get_list_ad(304,2);

		$re_left = $this->get_one_ad(305);
		$re_right = $this->get_one_ad(306);
		$re_button = $this->get_list_ad(307,4);


		$data = [
			'adlist'			=> 	$adlist,		//banner轮播
			'ad_top' => $ad_top, //单个大图
			'ad_left1' => $ad_left1, //单个大图
			'ad_left2' => $ad_left2, //单个大图
			'ad_right' => $ad_right, //单个大图
			
			'new_left' => $new_left, 
			'new_right' => $new_right, 

			're_left' => $re_left,
			're_right '=> $re_right,
			're_button' => $re_button,

			'articlelist'		=>  $articlelist,	//头条
			'flash_sale_goods'	=>  $flash_sale_goods,  //当前时段秒杀商品，只取6条
			'end_time'			=>	($end_time -time())	//剩余秒数
		];
		$this->ajaxReturn(['status' => 0, 'msg'=>'请求成功','data'=>$data]);
	}


	/**
	 * 获取
	 * 单个
	 * ad
	 */
	private function get_one_ad($pid){
		$data = M('ad')->where(['pid'=>$pid,'enabled'=>1])->field('ad_link,ad_code')->find();
		if(false !== strpos($data['ad_link'],'Mobile/Goods/goodsInfo/id')){ 
			$urlarr = pathinfo($data['ad_link']);
			$data['goods_id'] = $urlarr['filename'];
		}else{
			$data['goods_id'] = 0;
		}
		$data['ad_code'] = SITE_URL.$data['ad_code'];
		unset($data['ad_link']);
		return $data;
	}

	/**
	 * 获取
	 * 多个
	 * ad
	 */
	private function get_list_ad($pid,$limit){
		$adlist = M('ad')->where(['pid'=>$pid,'enabled'=>1])->field('ad_link,ad_code')->limit($limit)->select();
		foreach($adlist as $k=>$v){
			if(false !== strpos($v['ad_link'],'Mobile/Goods/goodsInfo/id')){ 
				$urlarr = pathinfo($v['ad_link']);
				$adlist[$k]['goods_id'] = $urlarr['filename'];
			}else{
				$adlist[$k]['goods_id'] = 0;
			}
			$adlist[$k]['ad_code'] = SITE_URL.$v['ad_code'];
			unset($adlist[$k]['ad_link']);
		}

		return $adlist;
	}


	//获取文章详情
	public function getArticleInfo(){
		$article_id = I('post.article_id/d',0);
		if(!$article_id)$this->ajaxReturn(['status' => -1, 'msg'=>'文章不存在','data'=>null]);
		$info = M('article')->field('article_id,title,content,author,author_email,add_time,file_url,link,click')->find($article_id);
		
		$info['goods_content'] = htmlspecialchars_decode($info['content']); 
		$content = preg_replace('/src="(.*?)"/', 'src="'.SITE_URL.'$1"', $info['content']);
		unset($goods['content']);
		
		$data = [
			'info'		=> $info,
			'content'	=> $content
		];
		M('article')->where(['article_id'=>$article_id])->setInc('click');
		$this->ajaxReturn(['status' => 0, 'msg'=>'请求成功','data'=>$data]);

	}

	//获取广告列表
	public function GetAdList(){
		$pid = I('post.pid/d',9);
		$time = time();
		$adlist = M('Ad')->field('ad_id,ad_link,ad_code,bgcolor')->where(['pid'=>$pid,'start_time'=>['elt',$time],'end_time'=>['egt',$time],'enabled'=>1])->order('orderby asc')->select();
		foreach($adlist as $k=>$v){
			if(false !== strpos($v['ad_link'],'Mobile/Goods/goodsInfo/id')){ 
				$urlarr = pathinfo($v['ad_link']);
				$adlist[$k]['goods_id'] = $urlarr['filename'];
			}else
				$adlist[$k]['goods_id'] = 0;
		}	
		$this->ajaxReturn(['status' => 0, 'msg'=>'请求成功','data'=>['list'=>$adlist]]);
	}

}
