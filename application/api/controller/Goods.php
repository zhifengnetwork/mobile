<?php
/**
 * 用户API
 */
namespace app\api\controller;
use app\common\model\Users;
use app\common\logic\UsersLogic;
use app\common\logic\GoodsLogic;
use app\common\logic\GoodsPromFactory;
use app\common\model\GoodsCategory;
use app\common\logic\FreightLogic;
use think\AjaxPage;
use think\Page;
use think\Db;

class Goods extends ApiBase
{

   /**
    * 分类接口
    */
    public function categoryList()
    {
        
        $data = Db::name('goods_category')->where('is_show=1')->order('id')->select();
        // dump($data);
        $this->ajaxReturn(['status' => 0 , 'msg'=>'获取成功','data'=>$data]);
    }
    public function Products()
    {
        $cat_id = I('get.cat_id/d');
        // dump($cat_id);exit;

        // $data = Db::name('goods')->where('cat_id',$cat_id)->select();
        $data = Db::name('goods')->where('cat_id',$cat_id)->field('goods_id,goods_name,original_img')->select();
        //  dump($data);exit;

        foreach($data as $k => $v){
            $data[$k]['original_img'] = SITE_URL.$v['original_img'];
        }

        $this->ajaxReturn(['status' => 0 , 'msg'=>'获取成功','data'=>$data]);
    }

    /**
     * +---------------------------------
     * 首页点击[看相似]根据分类id跳转至商品列表页
     * +---------------------------------
    */
    public function goodsList()
    {
        $filter_param = array();            // 筛选数组
        $id = I('id');                      // 当前分类id
        $brand_id = I('brand_id/d', 0);     // 品牌
        $spec = I('spec', 0);               // 规格
        $attr = I('attr', '');              // 属性
        $sort = I('sort', 'sort');          // 排序
        $sort_asc = I('sort_asc', 'desc');  // 排序
        $price = I('price', '');            // 价钱
        $goods_id = I('goods_id/d');            // 商品id
        $start_price = trim(I('start_price', '0'));         // 输入框价钱
        $end_price = trim(I('end_price', '0'));             // 输入框价钱
		$is_distribut = I('is_distribut/d');            // 商品id
		$is_agent = I('is_agent/d');            // 商品id
        if ($start_price && $end_price) $price = $start_price . '-' . $end_price; // 如果输入框有价钱 则使用输入框的价钱
		$type = I('post.type/s', '');  //类型，推荐is_recommend，新品is_new，热卖is_hot

		$page = I('post.page/d',1);
		$num = I('post.num/d',6);
		$limit = (($page-1)*$num) . ',' . $num;

        //如果分类是数字
        if(is_numeric($id)){
            $filter_param['id'] = $id; //加入筛选条件中
        }else{
           
            //如果不是字母
            if($id == 'DISTRIBUT'){
                $con['sign_free_receive'] = 1;
            }
            if($id == 'AGENT'){
                $con['sign_free_receive'] = 2;
            }
        }

		if($type)$filter_param[$type] = 1;
           
        $brand_id && ($filter_param['brand_id'] = $brand_id); //加入筛选条件中
        $spec && ($filter_param['spec'] = $spec);             //加入筛选条件中
        $attr && ($filter_param['attr'] = $attr);             //加入筛选条件中
        $price && ($filter_param['price'] = $price);          //加入筛选条件中
		$is_distribut && ($filter_param['is_distribut'] = $is_distribut);
		$is_agent && ($filter_param['is_agent'] = $is_agent);

        $goodsLogic = new GoodsLogic(); // 前台商品操作逻辑类
        // 分类菜单显示
        $goodsCate = M('GoodsCategory')->where("id", $id)->find();  // 当前分类
        //($goodsCate['level'] == 1) && header('Location:'.U('Home/Channel/index',array('cat_id'=>$id))); //一级分类跳转至大分类馆
        $cateArr = $goodsLogic->get_goods_cate($goodsCate);

        // 筛选 品牌 规格 属性 价格
        $cat_id_arr = getCatGrandson($id);
        $goods_where = ['is_on_sale' => 1, 'exchange_integral' => 0, 'cat_id' => ['in', $cat_id_arr]];
        $filter_goods_id = Db::name('goods')->where($goods_where)->cache(true)->getField("goods_id", true);

        // 过滤筛选的结果集里面找商品
        if ($brand_id || $price)// 品牌或者价格
        {
            $goods_id_1 = $goodsLogic->getGoodsIdByBrandPrice($brand_id, $price); // 根据 品牌 或者 价格范围 查找所有商品id
            $filter_goods_id = array_intersect($filter_goods_id, $goods_id_1);    // 获取多个筛选条件的结果 的交集
        }
        if ($spec) // 规格
        {
            $goods_id_2 = $goodsLogic->getGoodsIdBySpec($spec);                 // 根据 规格 查找当所有商品id
            $filter_goods_id = array_intersect($filter_goods_id, $goods_id_2);  // 获取多个筛选条件的结果 的交集
        }
        if ($attr)  // 属性
        {
            $goods_id_3 = $goodsLogic->getGoodsIdByAttr($attr);                 // 根据 规格 查找当所有商品id
            $filter_goods_id = array_intersect($filter_goods_id, $goods_id_3);  // 获取多个筛选条件的结果 的交集
        }

        //筛选网站自营,入驻商家,货到付款,仅看有货,促销商品
        $sel = I('sel');
        if ($sel) {
            $goods_id_4 = $goodsLogic->getFilterSelected($sel, $cat_id_arr);
            $filter_goods_id = array_intersect($filter_goods_id, $goods_id_4);
        }

        $filter_menu = $goodsLogic->get_filter_menu($filter_param, 'goodsList');                      // 获取显示的筛选菜单
        $filter_price = $goodsLogic->get_filter_price($filter_goods_id, $filter_param, 'goodsList');  // 筛选的价格期间
        $filter_brand = $goodsLogic->get_filter_brand($filter_goods_id, $filter_param, 'goodsList');  // 获取指定分类下的筛选品牌
        $filter_spec = $goodsLogic->get_filter_spec($filter_goods_id, $filter_param, 'goodsList', 1); // 获取指定分类下的筛选规格
        $filter_attr = $goodsLogic->get_filter_attr($filter_goods_id, $filter_param, 'goodsList', 1); // 获取指定分类下的筛选属性

        $now_goods = '';
        if($goods_id){
            $now_goods = M('goods')
            ->field('goods_id,seller_id,cat_id,goods_sn,goods_name,comment_count,shop_price,market_price,sku')
            ->where(array('goods_id'=>$goods_id))
            ->find(); // 获取当前点击的商品信息
        }

        $count = count($filter_goods_id);
        if ($count > 0) {
            $sort_asc = $sort_asc == 'asc' ? 'desc' : 'asc'; // 防注入
            $sort_arr = ['sales_sum','shop_price','is_new','comment_count','sort'];
            if(!in_array($sort,$sort_arr)) $sort='sort';    // 防注入

            $goods_list = M('goods')->where("goods_id", "in", implode(',', $filter_goods_id))
            ->field('goods_id,seller_id,cat_id,extend_cat_id,goods_sn,goods_name,store_count,sales_sum,comment_count,weight,shop_price,goods_remark,original_img,is_distribut,is_agent')
            ->where($con)
            ->order([$sort => $sort_asc])->limit($limit)
            ->select();
            $filter_goods_id2 = get_arr_column($goods_list, 'goods_id');
            if ($filter_goods_id2)
                $goods_images = M('goods_images')->where("goods_id", "in", implode(',', $filter_goods_id2))->cache(true)->select();
        }

		$GoodsCategory = M('goods_category');
		foreach($goods_list as $k=>$v){
			$commission_rate = $v['is_distribut'] ? $GoodsCategory->where(['id'=>$v['cat_id']])->value('commission_rate') : 0;
			$goods_list[$k]['commission_num'] = (intval($v['shop_price'] * $commission_rate) / 100);
        }
        
        if($goods_list){
            $seller_arr = Db::name('seller')->field('seller_id,seller_name')->select();
            foreach($goods_list as $k=>$v){
                foreach($seller_arr as $ks=>$vs){
                    if($v['seller_id'] == $vs['seller_id'] ){
                   
                        $goods_list[$k]['seller_name'] = $vs['seller_name'];
                        $goods_list[$k]['sale_total'] = intval($v['shop_price']*$v['sales_sum']); 

                    }
                }  
            }
        }        

        // $goods_category = M('goods_category')->where('is_show=1')->cache(true)->getField('id,name,parent_id,level'); // 键值分类数组
        C('TOKEN_ON', false);
        $data = [
            'now_goods' => $now_goods,              //当前点击的商品
            'goods_list' => $goods_list,            // 商品列表
            // 'goods_category' => $goods_category,    // 商品分类
            //'goods_images' => $goods_images,        // 相册图片
            'filter_menu' => $filter_menu,          // 筛选菜单
            'filter_spec' => $filter_spec,          // 筛选规格
            'filter_attr' => $filter_attr,          // 筛选属性
            'filter_brand' => $filter_brand,        // 列表页筛选属性 - 商品品牌
            'filter_price' => $filter_price,        // 筛选的价格期间
            'goodsCate' => $goodsCate,              // 传入当前分类
            'cateArr' => $cateArr,                  // 分类菜单显示
            'filter_param' => $filter_param,        // 筛选参数
            'cat_id' => $cat_id,                    // 筛选分类id
            'sort_asc' => $sort_asc == 'asc' ? 'desc' : 'asc'
        ];
        $this->ajaxReturn(['status' => 0 , 'msg'=>'获取成功','data'=>$data]);
    }

    /*
     * 获取商品属性
     */
    public function goodsAttr()
    {
        $goods_id = I("get.goods_id/d", 0);		
        $goods_attribute = M('GoodsAttribute')->field('attr_id,attr_name')->select(); // 查询属性
		foreach($goods_attribute as $k=>$v){
			$goods_attribute[$k]['attr'] = M('GoodsAttr')->where(["goods_id"=>$goods_id,'attr_id'=>$v['attr_id']])->select();
		}

		$this->ajaxReturn(['status' => 0, 'msg' => '请求成功', 'data' => ['goods_attribute'=>$goods_attribute]]);
    }

    /*
     * 获取商品规格
     */
    public function goodsSpec()
    {
        $goods_id = I("get.goods_id/d", 0);	
		$keys = M('spec_goods_price')->where(['goods_id'=>$goods_id])->column('key');
		$arr = [];
		$num = count(explode('_',$keys[0]));  
		$n = 0;
		for($j=0; $j<$num; $j++){
			$arr2 = [];
			foreach($keys as $v){		
				$arr1 = explode('_',$v);
				
				for($i=0; $i<count($arr1); $i++){
					if(($i == $n) && !(in_array($arr1[$n],$arr2)))$arr2[] = $arr1[$n]; 		
				}	
			}
			$arr[] = $arr2;
			$n++;
		}

		$data = [];
		$SpecItem = M('spec_item');
		$Spec = M('spec');
		foreach($arr as $v){
			$data1 = $SpecItem->alias('ST')->field('S.name,ST.id,ST.item')->JOIN('tp_spec S','ST.spec_id=S.id','LEFT')->where(['st.id'=>['in',$v]])->order('st.order_index asc')->select();	
			if($data1)$data[] = $data1;

		}

        $this->ajaxReturn(['status' => 0, 'msg' => '请求成功', 'data' => $data]);     
    }

	//根据规格获取图片，价格
	public function getPricePic(){
		$key = I('post.key/s','');
		$goods_id = I('post.goods_id/d',0);
		if(!$goods_id || !$key){
            $this->ajaxReturn(['status' => -1 , 'msg'=>'规格不存在','data'=>null]);
        }
		
		$info = M('spec_goods_price')->field('key_name,price,store_count,spec_img')->where(['goods_id'=>$goods_id,'key'=>$key])->find();
		$this->ajaxReturn(['status' => 0, 'msg' => '请求成功', 'data' => ['info'=>$info]]);
	}

    /*
     * 获取商品评论
     */
    public function getGoodsComment()
    {	
		$user_id = $this->get_user_id();
        if(!$user_id){
            $this->ajaxReturn(['status' => -1 , 'msg'=>'用户不存在','data'=>null]);
        }

        $goods_id = I("post.goods_id/d", 0);
        $commentType = I('commentType', '1'); // 1 全部 2好评 3 中评 4差评
        if ($commentType == 5) {
            $where = array(
                'goods_id' => $goods_id, 'parent_id' => 0, 'img' => ['<>', ''], 'is_show' => 1
            );
        } else {
            $typeArr = array('1' => '0,1,2,3,4,5', '2' => '4,5', '3' => '3', '4' => '0,1,2');
            $where = array('is_show' => 1, 'goods_id' => $goods_id, 'parent_id' => 0, 'ceil((deliver_rank + goods_rank + service_rank) / 3)' => ['in', $typeArr[$commentType]]);
        }

		$page = I('post.page/d',1);
		$num = I('post.num/d',6);
		$limit = (($page - 1)) * $num . ',' . $num;	    
        $list = M('Comment')
            ->alias('c')
            ->join('__USERS__ u', 'u.user_id = c.user_id', 'LEFT')
            ->where($where)->field('c.*,ceil((deliver_rank + goods_rank + service_rank) / 3) as goods_rank ,u.head_pic')
            ->order("add_time desc")
            ->limit($limit)
            ->select();
	
        foreach ($list as $k => $v) {		
            $list[$k]['img'] = $v['img'] ? unserialize($v['img']) : []; // 晒单图片
            $reply = M('Comment')->where(['is_show' => 1, 'goods_id' => $goods_id, 'parent_id' => $v['comment_id']])->order("add_time desc")->select();	
			$list[$k]['reply'] = $reply ? $reply : null;
            //$list[$k]['reply_num'] = Db::name('reply')->where(['comment_id' => $v['comment_id'], 'parent_id' => 0])->count();
        }

		$goodsModel = new \app\common\model\Goods();
		$comment_fr = $goodsModel->getCommentStatisticsAttr('', ['goods_id'=>$goods_id]);

		$this->ajaxReturn(['status' => 0, 'msg' => '请求成功', 'data' => ['commentlist'=>$list,'comment_fr'=>$comment_fr]]);
    }

    /**
     * 商品详情页
     */
    public function goodsInfo()
    {	
		$user_id = $this->get_user_id();
        if(!$user_id){
            $this->ajaxReturn(['status' => -1 , 'msg'=>'用户不存在','data'=>'']);
        }		

        $goodsLogic = new GoodsLogic();
        $goods_id = I("post.goods_id/d", 0);
		$goodsnum = I("post.goodsnum/d", 6);
        $goods = M('Goods')->field('template_id,sku,spu,cost_price',true)->find($goods_id);
        if (empty($goods) || ($goods['is_on_sale'] == 0)) {
            $this->ajaxReturn(['status' => -2, 'msg' => '此商品不存在或者已下架', 'data' => NULL]);
        }
        if(($goods['is_virtual'] == 1 && $goods['virtual_indate'] <= time())){
            $goods->save(['is_on_sale' => 0]);
            $this->ajaxReturn(['status' => -2, 'msg' => '此商品不存在或者已下架', 'data' => NULL]);
        }

        if ($user_id) {
            $goodsLogic->add_visit_log($user_id, $goods);
            $collect = db('goods_collect')->where(array("goods_id" => $goods_id, "user_id" => $user_id))->count(); //当前用户收藏
            $this->assign('collect', $collect);
        }

		$seller_info = ['store_id'=>'','store_name'=>'','avatar'=>0,'num'=>0];
		if($goods['seller_id']){
			$seller_info = M('seller_store')->field('store_id,store_name,avatar,province,city')->where(['seller_id'=>$goods['seller_id'],'auditing'=>10,'is_delete'=>10])->find();
		}else{
			$seller_info['province'] = M('Config')->where(['name'=>'province','inc_type'=>'shop_info'])->value('value');
			$seller_info['city'] = M('Config')->where(['name'=>'city','inc_type'=>'shop_info'])->value('value');
		}
		$seller_info['store_name'] = $seller_info['store_name'] ? $seller_info['store_name'] : '平台自营';
		$seller_info['num'] = M('goods')->where(['seller_id'=>$goods['seller_id'],'is_on_sale'=>1])->count();
		$seller_info['goods'] = M('Goods')->field('goods_id,goods_name,shop_price,original_img')->where(['seller_id'=>$goods['seller_id'],'is_on_sale'=>1])->limit('0,'.$goodsnum)->select(); 

		$Region = M('region');
		$seller_info['province_name'] = $seller_info['province'] ? $Region->where(['id'=>$seller_info['province']])->value('name') : '';
		$seller_info['city_name'] = $seller_info['city'] ? $Region->where(['id'=>$seller_info['city']])->value('name') : '';

		$goods['seller_info'] = $seller_info;

		unset($goods['template_id']);
		unset($goods['sku']);
		unset($goods['spu']);
		unset($goods['cost_price']);

		if($goods['prom_type'] == 1){ //秒杀
			$pinfo = M('flash_sale')->field('end_time')->find($goods['prom_id']);
			if($pinfo['end_time'] < time()){
				M('flash_sale')->update(['id'=>$goods['prom_id'],'is_end'=>1]);
				M('goods')->update(['goods_id'=>$goods['goods_id'],'prom_type'=>0,'prom_id'=>0]);
			}
		}
		if($goods['prom_type'] == 2){ //团购
			$pinfo = M('group_buy')->field('start_time,end_time,price')->find($goods['prom_id']);
			if($pinfo['end_time'] < time()){
				M('group_buy')->update(['id'=>$goods['prom_id'],'is_end'=>1]);
				M('goods')->update(['goods_id'=>$goods['goods_id'],'prom_type'=>0,'prom_id'=>0]);
			}
			$goods['prom_price'] = $pinfo['price'];
			$goods['start_time'] = $pinfo['start_time'];
			$goods['end_time'] = $pinfo['end_time'];
		}
		if($goods['prom_type'] == 8){ //竞拍
			$pinfo = M('auction')->field('end_time')->find($goods['prom_id']);
			if($pinfo['end_time'] < time()){
				M('goods')->update(['goods_id'=>$goods['goods_id'],'prom_type'=>0,'prom_id'=>0]);
			}
		}
		
		$goods['goods_content'] = htmlspecialchars_decode($goods['goods_content']); 
		$goods_content = preg_replace('/src="(.*?)"/', 'src="'.SITE_URL.'$1"', $goods['goods_content']);
		unset($goods['goods_content']);
		$goodsModel = new \app\common\model\Goods();
		$goods['is_collect'] = M('goods_collect')->where(['goods_id'=>$goods_id,'user_id'=>$user_id])->count();
		$goods['is_cart'] = M('cart')->where(['goods_id'=>$goods_id,'user_id'=>$user_id])->count();
		$goods['comment_count'] = M('comment')->where(['goods_id'=>$goods_id,'is_show'=>1])->count();
		$goods['comment_fr'] = $goodsModel->getCommentStatisticsAttr('', ['goods_id' => $goods_id]);
		$goods['goods_images'] = M('Goods_images')->where(['goods_id'=>$goods_id])->column('image_url');	
        echo json_encode(['status' => 0, 'msg' => '请求成功', 'data' => ['goods'=>$goods,'goods_content'=>$goods_content]],JSON_UNESCAPED_UNICODE );

    }

    /**
     * 获取商品物流配送和运费
     */
    public function dispatching()
    {
		$user_id = $this->get_user_id();
        if(!$user_id){
            $this->ajaxReturn(['status' => -1 , 'msg'=>'用户不存在','data'=>'']);
        }	

        $goods_id = I('post.goods_id/d',0);//143
        $region_id = I('post.region_id/d',0);//28242
		$buy_num = I('post.buynum/d',1);
        $Goods = new \app\common\model\Goods();
        $goods = $Goods->cache(true)->where('goods_id', $goods_id)->find();
        $freightLogic = new FreightLogic();
        $freightLogic->setGoodsModel($goods);
        $freightLogic->setRegionId($region_id);
        $freightLogic->setGoodsNum($buy_num);
        $isShipping = $freightLogic->checkShipping();
        if ($isShipping) {
            $freightLogic->doCalculation();
            $freight = $freightLogic->getFreight();
            $dispatching_data = ['status' => 0, 'msg' => '可配送', 'data' => ['freight' => $freight]];
        } else {
            $dispatching_data = ['status' => -2, 'msg' => '该地区不支持配送', 'data' => null];
        }	
		$this->ajaxReturn($dispatching_data);
    }

	public function php_info(){
		phpinfo();
    }
    
    /**
     * +---------------------------------
     * 用户收藏某一件商品
     * +---------------------------------
     */
    public function collect_goods()
    {
        $user_id = $this->get_user_id();
        if (!IS_POST) {
            $this->ajaxReturn(['status' => -1 , 'msg'=>'提交方式错误','data'=>(object)null]);
        }
        $goods_id = I('goods_id/d');
        $goods_one = Db::name('goods')->where("goods_id", $goods_id)->find();
        if(!$goods_one){
            $this->ajaxReturn(['status' => -1 , 'msg'=>'商品不存在','data'=>(object)null]);

        }
        $count = Db::name('goods_collect')->where("user_id", $user_id)->where("goods_id", $goods_id)->count();
        if($count > 0){
            $this->ajaxReturn(['status' => -1 , 'msg'=>'商品已收藏','data'=>(object)null]);

        }
        Db::name('goods')->where('goods_id', $goods_id)->setInc('collect_sum');
        Db::name('goods_collect')->add(array('goods_id'=>$goods_id,'user_id'=>$user_id, 'add_time'=>time()));
        $this->ajaxReturn(['status' => 0 , 'msg'=>'收藏成功','data'=>(object)null]);
    }

    
    /**
     * +---------------------------------
     * 删除收藏某一件商品
     * +---------------------------------
     */
    public function del_collect_goods()
    {	
        $user_id = $this->get_user_id();
        if (!IS_POST) {
            $this->ajaxReturn(['status' => -1 , 'msg'=>'提交方式错误','data'=>(object)null]);
        }
        $goods_id = I('goods_id/d',0);
        $count = Db::name('goods_collect')->where(["user_id"=>$user_id,'goods_id'=>$goods_id])->count();
        $collect_arr = Db::name('goods_collect')->where(["user_id"=>$user_id,'goods_id'=>$goods_id])->find();
        if(!$count){
            $this->ajaxReturn(['status' => -1 , 'msg'=>'收藏产品不存在','data'=>(object)null]);
        }
        Db::name('goods')->where('goods_id', $collect_arr['goods_id'])->setDec('collect_sum');
        $res = Db::name('goods_collect')->where(["user_id"=>$user_id,'goods_id'=>$goods_id])->delete();
        if(!$res){
            $this->ajaxReturn(['status' => -1 , 'msg'=>'删除收藏失败','data'=>(object)null]);
        }
        $this->ajaxReturn(['status' => 0 , 'msg'=>'删除收藏成功','data'=>(object)null]);
    }
}
