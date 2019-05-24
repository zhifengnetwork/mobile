<?php


namespace app\common\model;
use app\common\logic\User;
use app\common\util\TpshopException;
use app\common\logic\OrderLogic;
use app\common\model\PushCart;
use app\common\model\PushStock;
use think\Cache;
use think\Hook;
use think\Model;
use think\Db;
/**
 * 提交下单类
 * Class CatsLogic
 * @package Home\Logic
 */
class PushOrder
{
    private $invoiceTitle;
    private $userNote;
    private $taxpayer;
    private $invoiceDesc;
    private $pay;
    private $order;
    private $userAddress;
    private $payPsw;
    private $promType;
    private $promId;
    private $consignee;
    private $mobile;
    private $shop;
    private $take_time;
    private $preSell;

    /**
     * PlaceOrder constructor.
     * @param Pay $pay
     */
    public function __construct($user)
    {
        $this->user = $user;
        $this->order = new Order();
    }

    /**
     * 删除地推临时购物车个人数据
     */
    public function delPushCart()
    {
        $pushCart = new PushCart();
        $pushCart::where('user_id', $this->user['user_id'])->delete();
    }

    /**
     * 添加个人库存
     */
    public function addPersonStock()
    {
        $pushObj = new PushCart();
        $pushStock  = new PushStock();
        $goods_list = $pushObj::all(['user_id' => $this->user['user_id']]);
        foreach ($goods_list as $key => $value) {
            $arr = array(
                'goods_id' => $value['goods_id'],
                'user_id'  => $value['user_id'],
                'item_id'  => $value['item_id'],
            );
            $good = $pushStock::get($arr);
            if($good){
                $good->goods_num = $value['goods_num'] + $good['goods_num'];
                $good->update_time = time();
                $good->save();
            }else{
                $pre_time = time();
                $arr['goods_num'] = $value['goods_num'];
                $arr['create_time'] = $pre_time;
                $arr['update_time'] = $pre_time;
                $pushStock->save($arr);
            }
        }
    }

    public function addNormalOrder($goodsTotalPrice,$cartList)
    {

        $this->queueInc();
        $this->addOrder($goodsTotalPrice);
        $this->addOrderGoods($cartList);
        Hook::listen('user_add_order', $this->order);//下单行为
        $reduce = tpCache('shopping.reduce');
        if($reduce== 1 || empty($reduce)){
            minus_stock($this->order);//下单减库存
        }
        // 如果应付金额为0  可能是余额支付 + 积分 + 优惠券 这里订单支付状态直接变成已支付
        if ($this->order['order_amount'] == 0) {
            update_pay_status($this->order['order_sn']);
        }
        $this->changUserPointMoney($this->order,$goodsTotalPrice);//扣除用户积分余额
        $this->addPersonStock();
        $this->delPushCart();
        $this->queueDec();
    }

    private function queueInc()
    {
        $queue = Cache::get('queue');
        if($queue >= 100){
            throw new TpshopException('提交订单', 0, ['status' => -99, 'msg' => "当前人数过多请耐心排队!", 'result' => '']);
        }
        Cache::inc('queue');
    }

    /**
     * 订单提交结束
     */
    private function queueDec()
    {
        Cache::dec('queue');
    }

    /**
     * 插入订单表
     * @throws TpshopException
     */
    private function addOrder($goodsTotalPrice)
    {
        $Totalprice=$goodsTotalPrice;
        $OrderLogic = new OrderLogic();
        $user = $this->user;
        $invoice_title = $this->invoiceTitle;
        if($this->invoiceTitle == "" && $this->invoiceDesc != "不开发票"){
            $invoice_title = "个人";
        }
        $orderData = [
            'order_sn' => $OrderLogic->get_order_sn(), // 订单编号
            'user_id' => $user['user_id'], // 用户id
            'email' => $user['email'],//'邮箱'
            'invoice_title' => ($this->invoiceDesc != '不开发票') ?  $invoice_title : '', //'发票抬头',
            'invoice_desc' => $this->invoiceDesc, //'发票内容',
            'goods_price' => $Totalprice['goodsPrice'],//'商品价格',
            'shipping_price' => $Totalprice['shippingPrice'],//'物流价格',
            'user_money' => 0,//'使用余额',
            'coupon_price' => 0,//'使用优惠券',
            'pay_status' => 1,
            'integral' => $Totalprice['totalAmount'], //'使用积分',
            'integral_money' => $Totalprice['totalAmount'],//'使用积分抵多少钱',
            'sign_price' => 0,//'签到抵扣金额',
            'total_amount' => $Totalprice['totalAmount'],// 订单总额
            'order_amount' => $Totalprice['totalAmount'],//'应付款金额',
            'add_time' => time(), // 下单时间
            'pay_name' =>  '积分兑换',
        ];
        if($orderData["order_amount"] < 0){
            throw new TpshopException("订单入库", 0, ['status' => -8, 'msg' => '订单金额不能小于0', 'result' => '']);
        }
      if (!empty($this->userAddress)) {
            $orderData['consignee'] = $this->userAddress['consignee'];// 收货人
            $orderData['province'] = $this->userAddress['province'];//'省份id',
            $orderData['city'] = $this->userAddress['city'];//'城市id',
            $orderData['district'] = $this->userAddress['district'];//'县',
            $orderData['twon'] = $this->userAddress['twon'];// '街道',
            $orderData['address'] = $this->userAddress['address'];//'详细地址'
            $orderData['mobile'] = $this->userAddress['mobile'];//'手机',
            $orderData['zipcode'] = $this->userAddress['zipcode'];//'邮编',
        } else {
            $orderData['consignee'] = $user['nickname'];// 收货人
            $orderData['mobile'] = $user['mobile'];//'手机',
        }
        if (!empty($this->userNote)) {
            $orderData['user_note'] = $this->userNote;// 用户下单备注
        }
        if (!empty($this->taxpayer)) {
            $orderData['taxpayer'] = $this->taxpayer; //'发票纳税人识别号',
        }

        if ($this->promType) {
            $orderData['prom_type'] = $this->promType;//订单类型
        }
        $this->order->data($orderData, true);
        $orderSaveResult = $this->order->save();
        if ($orderSaveResult === false) {
            throw new TpshopException("订单入库", 0, ['status' => -8, 'msg' => '添加订单失败', 'result' => '']);
        }
    }

    /**
     * 插入订单商品表
     */
    private function addOrderGoods($cartList)
    {
        $payList = $cartList;
        $goods_ids = get_arr_column($payList,'goods_id');
        $goodsArr = Db::name('goods')->where('goods_id', 'IN', $goods_ids)->getField('goods_id,cat_id,cost_price,give_integral');
        $orderGoodsAllData = [];
        foreach ($payList as $payKey => $payItem) {
            $orderGoodsData['order_id'] = $this->order['order_id']; // 订单id
            $orderGoodsData['goods_id'] = $payItem['goods_id']; // 商品id
            $orderGoodsData['cat_id'] = $goodsArr[$payItem['goods_id']]['cat_id']; // 商品分类id
            $orderGoodsData['goods_name'] = $payItem['goods_name']; // 商品名称
            $orderGoodsData['goods_sn'] = $payItem['goods_sn']; // 商品货号
            $orderGoodsData['goods_num'] = $payItem['goods_num']; // 购买数量
            $orderGoodsData['final_price'] = $payItem['shop_price']; // 每件商品实际支付价格
            $orderGoodsData['goods_price'] = $payItem['shop_price']; // 商品价               为照顾新手开发者们能看懂代码，此处每个字段加于详细注释
            if (!empty($payItem['spec_key'])) {
                $orderGoodsData['spec_key'] = $payItem['spec_key']; // 商品规格
                $orderGoodsData['spec_key_name'] = $payItem['spec_key_name']; // 商品规格名称
                $spec_goods_price = db('spec_goods_price')->where(['goods_id'=>$payItem['goods_id'],'key'=>$payItem['spec_key']])->find();
                $orderGoodsData['cost_price'] = $spec_goods_price['cost_price']; // 成本价
                $orderGoodsData['item_id'] = $spec_goods_price['item_id']; // 商品规格id
            } else {
                $orderGoodsData['spec_key'] = ''; // 商品规格
                $orderGoodsData['spec_key_name'] = ''; // 商品规格名称
                $orderGoodsData['cost_price'] = $goodsArr[$payItem['goods_id']]['cost_price']; // 成本价
                $orderGoodsData['item_id'] = 0; // 商品规格id
            }
            $orderGoodsData['sku'] = $payItem['sku']; // sku
            $orderGoodsData['member_goods_price'] = $payItem['shop_price']; // 会员折扣价
            $orderGoodsData['give_integral'] = 0; // 购买商品赠送积分

            if ($payItem['prom_type']) {
                $orderGoodsData['prom_type'] = 0; // 0 普通订单,1 限时抢购, 2 团购 , 3 促销优惠
                $orderGoodsData['prom_id'] = 0; // 活动id
            } else {
                $orderGoodsData['prom_type'] = 0; // 0 普通订单,1 限时抢购, 2 团购 , 3 促销优惠
                $orderGoodsData['prom_id'] = 0; // 活动id
            }
            array_push($orderGoodsAllData, $orderGoodsData);
        }
        Db::name('order_goods')->insertAll($orderGoodsAllData);
    }



    /**
     * 扣除用户地推积分
     * @param Order $order
     */
    public function changUserPointMoney(Order $order,$goodsTotalPrice)
    {

            $user = $this->user;
            $user = Users::get($user['user_id']);
            $user->integral_push = $user->integral_push - $goodsTotalPrice['totalAmount'];// 消费积分
            $user->save();
            $accountLogData = [
                'user_id' => $order['user_id'],
                'user_money' => -0,
                'integral_push' => -$goodsTotalPrice['totalAmount'],
                'change_time' => time(),
                'desc' => '下单消费',
                'order_sn'=>$order['order_sn'],
                'order_id'=>$order['order_id'],
            ];
            Db::name('account_log')->insert($accountLogData);

    }


    /**
     * 这方法特殊，只限拼团使用。
     * @param $order
     */
    public function setOrder($order)
    {
        $this->order = $order;
    }
    /**
     * 获取订单表数据
     * @return Order
     */
    public function getOrder()
    {
        return $this->order;
    }

    public function setPayPsw($payPsw)
    {
        $this->payPsw = $payPsw;
        return $this;
    }

    public function setInvoiceTitle($invoiceTitle)
    {
        $this->invoiceTitle = $invoiceTitle;
        return $this;
    }
    public function setUserNote($userNote)
    {
        $this->userNote = $userNote;
        return $this;
    }
    public function setTaxpayer($taxpayer)
    {
        $this->taxpayer = $taxpayer;
        return $this;
    }
    public function setInvoiceDesc($invoice_desc)
    {
        $this->invoiceDesc = $invoice_desc;
        return $this;
    }

    public function setUserAddress($userAddress)
    {
        $this->userAddress = $userAddress;
        return $this;
    }
    public function setShop($shop)
    {
        $this->shop = $shop;
        return $this;
    }
    public function setTakeTime($take_time)
    {
        $this->take_time = $take_time;
        return $this;
    }
    public function setConsignee($consignee)
    {
        $this->consignee = $consignee;
        return $this;
    }
    public function setMobile($mobile)
    {
        $payList = $this->pay->getPayList();
        if($payList[0]['is_virtual']){
            if($mobile){
                if(check_mobile($mobile)){
                    $this->mobile = $mobile;
                }else{
                    throw new TpshopException("提交订单",0,['status'=>-1,'msg'=>'手机号码格式错误','result'=>['']]);
                }
            }else{
                throw new TpshopException("提交订单",0,['status'=>-1,'msg'=>'请填写手机号码','result'=>['']]);
            }
        }
        $this->mobile = $mobile;
        return $this;
    }



}