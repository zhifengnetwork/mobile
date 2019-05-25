<?php
/**
 * 购物车API
 */
namespace app\api\controller;
use app\common\model\Users;
use app\common\logic\UsersLogic;
use app\common\logic\CartLogic;
use think\Db;
use app\common\util\TpshopException;

class Cart extends ApiBase
{

    /**
     * 将商品加入购物车.
     *
     * @param token 登录凭证
     */
    public function addcart()
    {
        $user_id = $this->get_user_id();
        if(!$user_id){
            $this->ajaxReturn(['status' => -1 , 'msg'=>'用户不存在','data'=>'']);
        }





        $data = '购物车数据';
        $this->ajaxReturn(['status' => 0 , 'msg'=>'加入购物车成功','data'=>$data]);
    }

    
    /*
     * 请求获取购物车列表
     */
    public function cartlist()
    {	
        $page = I('post.page/d',1);
		$num = I('post.num/d',10);
        $limit = (($page - 1)) * $num . ',' . $num;	  
        $user_id = $this->get_user_id();
        if(!$user_id){
            $this->ajaxReturn(['status' => -1 , 'msg'=>'用户不存在','data'=>'']);
        }

        $cartLogic = new CartLogic();
        $cartLogic->setUserId($user_id);
        $data = $cartLogic->getCartList2(0,$limit);//用户购物车
        $seller_arr = Db::name('seller')->field('seller_id,seller_name')->select();
		$SpecGoodsPrice = M('spec_goods_price');
        foreach($data as $k=>$v){
            $goods_seller_id = Db::name('goods')->field('goods_id,seller_id,original_img')->where(['goods_id'=>$v['goods_id']])->find();
            if($goods_seller_id){
                $data[$k]['seller_id'] = $goods_seller_id['seller_id'];
				$data[$k]['original_img'] = $goods_seller_id['original_img'];
				$data[$k]['prom_id'] = $goods_seller_id['prom_id'];
				$data[$k]['prom_type'] = $goods_seller_id['prom_type'];
            }
			$sgpinfo = $SpecGoodsPrice->field('price,store_count,spec_img')->where(['item_id'=>$v['item_id']])->find();
			$data[$k]['goods_price'] = $sgpinfo['price'] ? $sgpinfo['price'] : $data[$k]['goods_price'];
			$data[$k]['original_img'] = $sgpinfo['spec_img'] ? $sgpinfo['spec_img'] : $data[$k]['original_img'];
			$data[$k]['store_count'] = $sgpinfo['store_count'] ? $sgpinfo['store_count'] : $data[$k]['store_count'];

        }
       
        foreach($seller_arr as $ks=>$vs){
            $seller_name = $vs['seller_name'];
            foreach($data as $k=>$v){
               
                if($v['seller_id'] == $vs['seller_id'] ){
                   
                    $data[$k]['seller_name'] = $vs['seller_name'];
                }
            }
        }
        
        // 判断购物车已经全部选中了

        $cart_sel = Db::name('cart')->field('id,selected')->where(['user_id'=>$user_id,'selected'=>0])->find();
        // 判断是否存在一个没选中的产品
        if($cart_sel){
            $all_flag = 2; // 没有全部选中标记
        }else{
            $all_flag = 1; // 全部选中标记
        }
        foreach($data as $k=>$v){
            unset($v['user_id']);
            unset($v["session_id"]);
            unset($v["goods_id"]);
            //unset($v["goods_name"]);
            unset($v["market_price"]);
            unset($v["member_goods_price"]);
            unset($v["item_id"]);
           //unset($v["spec_key"]);
            unset($v["bar_code"]);
            unset($v["add_time"]);
            //unset($v["prom_type"]);
            //unset($v["prom_id"]);
            unset($v["sku"]);
            unset($v["combination_group_id"]);
        }
        // $res['list'] = array(
        //     'seller_id'=> 0,
        //     'seller_name'=>'ZF智丰自营',
        //     'data'=>$data,
        // );
        $res['list'] = $data;
        $res['cart_price_info'] = $this->_getTotal($user_id);
        $res['selected_flag'] = array('all_flag'=>$all_flag);

        
        $this->ajaxReturn(['status' => 0 , 'msg'=>'购物车列表成功','data'=>$res]);
    }


     /**
     * 删除购物车的商品
     */
    public function delcart()
    {
        $user_id = $this->get_user_id();
        if(!$user_id){
            $this->ajaxReturn(['status' => -1 , 'msg'=>'用户不存在','data'=>(object)null]);
        }
        $id_arr = input();
        $cartLogic = new CartLogic();
        $cartLogic->setUserId($user_id);
        foreach($id_arr as $k=>$v){
            $data = $cartLogic->delete($v['id']);
        }
       
        if($data){
            $res['cart_price_info'] = $this->_getTotal($user_id);
            $this->ajaxReturn(['status' => 0 , 'msg'=>'删除成功','data'=>$res]);
        }else{
            $this->ajaxReturn(['status' => -1 , 'msg'=>'删除失败','data'=>(object)null]);
        }
        
    }


    /**
     * 更新数量
     */
    public function update_num()
    {

    }

    /**
     * +---------------------------------
     * 更新购物车，并返回计算结果
     * +---------------------------------
    */
    public function AsyncUpdateCart()
    {
        $user_id = $this->get_user_id();
        if(!$user_id){
            $this->ajaxReturn(['status' => -1 , 'msg'=>'用户不存在','data'=>(object)null]);
        }
        $cart = input();
        $cartLogic = new CartLogic();
        $cartLogic->setUserId($user_id);
        $cartLogic->AsyncUpdateCart($cart);
        $select_cart_list = $cartLogic->getCartList(1);//获取选中购物车
        $cart_price_info = $cartLogic->getCartPriceInfo($select_cart_list);//计算选中购物车
        $user_cart_list = $cartLogic->getCartList();//获取用户购物车
        // $return['cart_list'] = $cartLogic->cartListToArray($user_cart_list);//拼接需要的数据
        $return['cart_price_info'] = $cart_price_info;
        $this->ajaxReturn(['status' => 0 , 'msg'=>'计算成功','data'=>$return]);
    }

    
    /* +---------------------------------
     * 购物车加减
     * +---------------------------------
    */
    public function changeNum(){
        $user_id = $this->get_user_id();
        $cart = input('cart/a',[]);
        $id = I('post.id/d',0);
        $goods_num = I('post.goods_num/d',0);

        if(!$cart){
            $cart = ['id'=>$id,'goods_num'=>$goods_num];
        }

        if (empty($cart) || !$cart['id']) {
            $this->ajaxReturn(['status' => 0, 'msg' => '请选择要更改的商品', 'result' => (object)null]);
        }
        $cartLogic = new CartLogic();
        $result = $cartLogic->changeNum($cart['id'], $cart['goods_num']);
        $result['cart_price_info'] = $this->_getTotal($user_id);
        
        if($result['status'] == 1)
            $this->ajaxReturn(['status' => 0 , 'msg'=>'修改成功','data'=>$result]);
        else
        $this->ajaxReturn(['status' => -1 , 'msg'=>$result['msg'],'data'=>null]);

    }

    /* +---------------------------------
     * 购物车全选或取消全选
     * +---------------------------------
    */
    public function selectedOrAll(){
        $user_id = $this->get_user_id();
        if (IS_POST) {
            $flag = I('all_flag/a');
            if($flag[0]<=2){
                $flag = $flag[0]==1?1:0;
                $res = Db::name('cart')->where(['user_id'=>$user_id])->update(['selected'=>$flag]);
                $data['cart_price_info'] = $this->_getTotal($user_id);
            }else{
                $this->ajaxReturn(['status' => -1 , 'msg'=>'失败','data'=>'']);
            }
            $this->ajaxReturn(['status' => 0 , 'msg'=>'成功','data'=>$data]);
        }else{
            $this->ajaxReturn(['status' => -1 , 'msg'=>'提交方式错误','data'=>'']);
        }
    }

    /* +---------------------------------
     * 购物车修改商品规格
     * +---------------------------------
     */
    public function update_cart_spec(){
        $user_id = $this->get_user_id();
        if (!IS_POST) {
            $this->ajaxReturn(['status' => -1 , 'msg'=>'提交方式错误','data'=>(object)null]);
        }
        $cart_info = input();
        $cart_id = intval($cart_info['cart_id']);
        $item_id = $cart_info['item_id'];//2019/4/28 星期日 item_id接收值修改为tp_spec_goods_price的key
        if(!$cart_id || !$item_id){
            $this->ajaxReturn(['status' => -1 , 'msg'=>'缺少参数','data'=>(object)null]);
        }
        // 获取当前购物车id和规格id是否存在
        $cart_one = Db::name('cart')->field('id,goods_id')->where(['user_id'=>$user_id, 'id'=>$cart_id])->find();
        if(!$cart_one){
            $this->ajaxReturn(['status' => -1 , 'msg'=>'购物车记录不存在','data'=>(object)null]);
        }
        $spec_one = Db::name('spec_goods_price')
        ->field('item_id,goods_id,key,key_name,price,store_count')
        ->where(['key'=>$item_id,'goods_id'=>$cart_one['goods_id']])
        ->find();
        if(!$spec_one){
            $this->ajaxReturn(['status' => -1 , 'msg'=>'产品规格不存在','data'=>(object)null]);
        }

        // 根据item_id修改购物车规格
        $update_data = array(
            'item_id'=>$spec_one['item_id'],
            'spec_key'=>$spec_one['key'],
            'spec_key_name'=>$spec_one['key_name'],
            'goods_price'=>$spec_one['price'],
            'member_goods_price'=>$spec_one['price']
        );
        $res = Db::name('cart')->where(['user_id'=>$user_id, 'id'=>$cart_id])->update($update_data);
        if(false === $res){
            $this->ajaxReturn(['status' => -1 , 'msg'=>'规格修改失败','data'=>(object)null]);
        }
        $data['cart_price_info'] = $this->_getTotal($user_id);
        $this->ajaxReturn(['status' => 0 , 'msg'=>'规格修改成功','data'=>$data]);

    }


    // 计算购物车合计
    public function _getTotal($user_id){
        $cartLogic = new CartLogic();
        $cartLogic->setUserId($user_id);
        $cartLogic->AsyncUpdateCart($cart);
        $select_cart_list = $cartLogic->getCartList(1);//获取选中购物车
        $cart_price_info = $cartLogic->getCartPriceInfo($select_cart_list);//计算选中购物车
        // $user_cart_list = $cartLogic->getCartList();//获取用户购物车
        return $cart_price_info;
    }

    /**
     * ajax 将商品加入购物车
     */
    function add_cart()
    { 
        $user_id = $this->get_user_id();
        if (!$user_id) {
            $this->ajaxReturn(['status' => -1 , 'msg'=>'用户不存在','data'=>null]);
        }
        $goods_id = I("goods_id/d",0); // 商品id
        $goods_num = I("goods_num/d",1);// 商品数量
        $key = I("item_id/s",''); // 商品规格id
        if(empty($goods_id)){
            $this->ajaxReturn(['status'=>-2,'msg'=>'请选择要购买的商品','data'=>null]);
        }
        if(empty($goods_num)){
            $this->ajaxReturn(['status'=>-3,'msg'=>'购买商品数量不能为0','data'=>null]);
        }
		$item_id = $key ? M('spec_goods_price')->where(['goods_id'=>$goods_id,'key'=>$key])->value('item_id') : 0; 
		
		if($key && !$item_id)$this->ajaxReturn(['status'=>-3,'msg'=>'未查询到此商品规格','data'=>null]); //可能为key顺序拼接错误
        
		$cartLogic = new CartLogic();
        $cartLogic->setUserId($user_id);
        $cartLogic->setGoodsModel($goods_id);
        $cartLogic->setSpecGoodsPriceById($item_id);
        $cartLogic->setGoodsBuyNum($goods_num);
        try {
            $res = $cartLogic->addGoodsToCart();	//Db::name('cart')->getLastInsID()
            $this->ajaxReturn(['status' => 0, 'msg' => '加入购物车成功','data'=>null]);
        } catch (TpshopException $t) {
            $error = $t->getErrorArr();
			$this->ajaxReturn(['status' => -4, 'msg' => $error['msg'],'data'=>null]);
        }
    }
    
    
}
