<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/5/23 0023
 * Time: 9:38
 */
namespace app\mobile\controller;
use app\common\logic\CartLogic;
use app\common\model\Goods as GoodsModel;
use app\common\logic\Pay as PayModel;
use think\Db;
use think\Loader;
use app\common\model\PushOrder as pushOrder ;

class PushCart extends MobileBase
{


    public $cartLogic; // 购物车逻辑操作类
    public $user_id = 0;
    public $integral_push; //支付积分
    public $paypwd;
    public $user = array();

    /**
     * 析构流函数
     */
    public function  __construct() {
        parent::__construct();
        $this->cartLogic = new CartLogic();
        if (session('?user')) {
            $user = session('user');
            $user = M('users')->where("user_id", $user['user_id'])->find();
            session('user', $user);  //覆盖session 中的 user
            $this->user = $user;
            $this->user_id = $user['user_id'];
            $this->assign('user', $user); //存储用户信息
            // 给用户计算会员价 登录前后不一样
            if ($user) {
                $discount = (empty((float)$user['discount'])) ? 1 : $user['discount'];
                if ($discount != 1) {
                    $c = Db::name('cart')->where(['user_id' => $user['user_id'], 'prom_type' => 0])->where('member_goods_price = goods_price')->count();
                    $c && Db::name('cart')->where(['user_id' => $user['user_id'], 'prom_type' => 0])->update(['member_goods_price' => ['exp', 'goods_price*' . $discount]]);
                }
            }
        }
    }

    //生成订单页
    public  function cart2(){

        //商品goodsId
        $goodsIdList=array();
        //商品数量
        $goodsNumList=array();
        //商品属性
        $goodsItemList=array();
        if ($this->user_id == 0){
            $this->error('请先登录', U('Mobile/User/login'));
        }
        $cartList=Db::name('push_cart')->where(['user_id' => $this->user_id])->select();
        foreach ($cartList as $key =>$value){
            array_push( $goodsIdList,$value['goods_id']);
            array_push( $goodsNumList,$value['goods_num']);
            array_push( $goodsItemList,$value['item_id']);
        }
        if(empty($goodsIdList)){
            $this->error('您没有选中商品', 'Push/order_goods');
        }

        $goodsId=implode(',',$goodsIdList);
        $cartLogic = new CartLogic();
        $cartLogic->setUserId($this->user_id);
        $goodsPriceTotal=0;
        //获取平台商品信息;
        $Goods=new GoodsModel();
        $goodsList=$Goods->getGoodsList($goodsId); 
        $goodsLen=count($goodsList);
        for($i=0;$i<$goodsLen;$i++){
            $goodsList[$i]['goods_num']=$goodsNumList[$i];
            $goodsList[$i]['item_id']=$goodsItemList[$i];
            $data['goods_id']=$goodsIdList[$i];
            if(!empty($goodsList[$i]['item_id'])){
                $keyInfo=$this->getKeyInfo($goodsList[$i]['item_id']);
                $goodsList[$i]['spec_key_name']=$keyInfo['key_name'];
                //计算使用属性的价格；
                $goodsList[$i]['shop_price']=$keyInfo['price'];
            }else{
                $goodsList[$i]['spec_key_name']='';
            }
            $goodsPriceTotal+=$goodsList[$i]['shop_price']*$goodsNumList[$i];

        }
        $url="http://".$_SERVER['HTTP_HOST']."/index.php/Mobile/PushCart/cart2.html";
        $cartList['cartList'] = $goodsList;  //找出搭配购副商品
        $cartGoodsTotalNum = count($cartList['cartList']);
        $this->assign('goodsIdList', implode(',',$goodsIdList));
        $this->assign('goodsNumList', implode(',',$goodsNumList));
        $this->assign('goodsItemList', implode(',',$goodsItemList));
        $this->assign('redirect_url', $url);
        $this->assign('cartGoodsTotalNum', $cartGoodsTotalNum);
        $this->assign('cartList', $cartList['cartList']); // 购物车的商品
        return $this->fetch();
    }

    /**
     * ajax 获取订单商品价格 或者提交 订单
     */
    public function cart3(){
        if (!$this->user_id) {
            exit(json_encode(array('status' => -100, 'msg' => "登录超时请重新登录!", 'result' => null))); // 返回结果状态
        }
        $address_id = input("address_id/d", 0); //  收货地址id
        $invoice_title = input('invoice_title');  // 发票
        $taxpayer = input('taxpayer');       // 纳税人识别号
        $invoice_desc = input('invoice_desc');       // 发票内容
        $user_note = input("user_note/s", ''); // 用户留言
        $pay_pwd = input("pay_pwd/s", ''); // 支付密码
        $is_virtual = input('is_virtual/d',0);
        $data = input('request.');
        $cart_validate = Loader::validate('Cart');

        if($is_virtual === 1){
            $cart_validate->scene('is_virtual');
        }
        if (!$cart_validate->check($data)) {
            $error = $cart_validate->getError();
            $this->ajaxReturn(['status' => 0, 'msg' => $error, 'result' => '']);
        }
        $address = Db::name('user_address')->where("address_id", $address_id)->find();
        $cartLogic = new CartLogic();
        $pay = new PayModel();
        try {
            $cartList=Db::name('push_cart')
                ->alias('a')
                ->join('goods w','a.goods_id = w.goods_id')
                ->where(['user_id' => $this->user_id])
                ->select();
            $cartLogic->checkStockCartList($cartList);
            $pay->setUserId($this->user_id);
            $userInfo=$pay->getUser();
            $pay->payPushCart($cartList);
            $goodsTotalPrice=$pay->deliveryPush($address);
            // 提交订单
            if ($_REQUEST['act'] == 'submit_order') {
                if($goodsTotalPrice['integralPush']>$userInfo['integral_push']){
                    $this->ajaxReturn(['status' => 0, 'msg' => '很抱歉,您的积分不足！']);
                }
                $paypwd=encrypt($pay_pwd);
                if($paypwd !=$userInfo['paypwd']){
                    $this->ajaxReturn(['status' => 0, 'msg' => '支付密码错误！']);
                }
                $pushOrder = new PushOrder($this->user);
                $pushOrder->setUserAddress($address)->setInvoiceTitle($invoice_title)
                    ->setUserNote($user_note)->setTaxpayer($taxpayer)->setInvoiceDesc($invoice_desc)->setPayPsw($pay_pwd)
                    ->addNormalOrder($goodsTotalPrice,$cartList);
                $order = $pushOrder->getOrder();
                $this->ajaxReturn(['status' => 1, 'msg' => '提交订单成功',  'result' => $order['order_id']]);
            }
        } catch (TpshopException $t) {
            $error = $t->getErrorArr();
            $this->ajaxReturn($error);
        }
    }

    //查看某个用户购买的上级商品是否存在
    public function  checkHigherGoods(){

        $leader_id = I('leader_id');
        $action = I('action');
        $goods_id = I('goods_id');
        $goods_num = I('goods_num');
        $item_id = I('item_id');
        if ($this->user_id == 0){
            $this->error('请先登录', U('Mobile/User/login'));
        }
        $cartLogic = new CartLogic();
        //该用户的上级用户id
        $HigherUserId=$this->getHigherId();
         if($leader_id==$HigherUserId){
             //立即购买
             if($action=="buy_now"){
                //该商品是否存在属性
                 $goodsWhere['user_id']=$HigherUserId;
                 $goodsWhere['goods_id']=$goods_id;
                 if(!empty($item_id)){
                   $goodsWhere['item_id']=$item_id;
                 }
                 $goodsInfo=Db::name("push_stock")->where($goodsWhere)->find();
                 if(!empty($goodsInfo)){
                     if($goodsInfo['goods_num']<$goods_num){
                         $this->ajaxReturn(['status'=>0,'msg'=>"商品库存不足！"]);
                     }else{
                         $this->ajaxReturn(['status'=>1,'msg'=>""]);
                     }
                 }else{
                     $this->ajaxReturn(['status'=>0,'msg'=>"您的上级没有订购这个商品！"]);
                 }
                }else{
                 $userCartList = $cartLogic->getCartList(1);
                 $this->checkHeigherStock($userCartList,$HigherUserId);
             }

         }else{
             $this->ajaxReturn(['status'=>0,'msg'=>"您的上级id不存在，请重新输入！"]);
         }

    }

    //计算商品价格，运费
    public function getDeliver(){
        if (!$this->user_id) {
            exit(json_encode(array('status' => -100, 'msg' => "登录超时请重新登录!", 'result' => null))); // 返回结果状态
        }
        $address_id=I('post.address_id');
        $address = Db::name('user_address')->where("address_id", $address_id)->find();
        $cartList=Db::name('push_cart')
            ->alias('a')
            ->join('goods w','a.goods_id = w.goods_id')
            ->where(['user_id' => $this->user_id])
            ->select();
        $cartLogic = new CartLogic();
        $pay=new PayModel();
        $cartLogic->checkStockCartList($cartList);
        $pay->setUserId($this->user_id);
        $pay->payPushCart($cartList);
        $data=$pay->setUserId($this->user_id)->deliveryPush($address);
        $this->ajaxReturn(['status'=>1,'data'=>$data]);
    }

    //获取某个用户的上级userId
    public function getHigherId(){
        $userInfo=$this->user;
        return  $userInfo["first_leader"];
    }

    //检测上级商品库
   public function checkHeigherStock($userCartList,$HigherUserId){
       $goodsWhere['user_id']=$HigherUserId;
       foreach ($userCartList as $key =>$value ){
           $goodsWhere['goods_id']=$value['goods_id'];
           if(!empty($value['item_id'])){
               $goodsWhere['item_id']=$value['item_id'];
           }
           $goodsInfo=Db::name("push_stock")->where($goodsWhere)->find();
           if(!empty($goodsInfo)){
               if($goodsInfo['goods_num']<$value['goods_num']){
                   $msg=$value['goods_name']."商品库存不足!";
                   $this->ajaxReturn(['status'=>0,'msg'=>$msg]);
                   exit;
               }else{
                   $this->ajaxReturn(['status'=>1,'msg'=>""]);
                   exit;
               }
           }else{
               $msg="您的上级没有".$value['goods_name']."这个商品!";
               $this->ajaxReturn(['status'=>0,'msg'=>$msg]);
               exit;
           }
       }
   }

   //获取商品的属性值
    public function getKeyInfo($item_id){

       $keyInfo= Db::name("spec_goods_price")->where(["item_id"=>$item_id])->field('key_name,price')->find();
       return $keyInfo;

    }

}