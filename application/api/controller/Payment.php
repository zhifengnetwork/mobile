<?php
/**
 * tpshop
 * ============================================================================
 * * 版权所有 2015-2027 广州滴蕊生物科技有限公司，并保留所有权利。
 * 网站地址: http://www.dchqzg1688.com
 * ----------------------------------------------------------------------------
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和使用 .
 * 不允许对程序代码以任何形式任何目的的再发布。
 * 采用最新Thinkphp5助手函数特性实现单字母函数M D U等简写方式
 * ============================================================================
 * $Author: IT宇宙人 2015-08-10 $
 */ 
namespace app\api\controller; 
use think\Request;
use think\Db;
use app\common\model\Users;
use app\common\logic\UsersLogic;
use app\common\model\Order;
use app\common\logic\OrderLogic;

class Payment extends ApiBase {
    
    public $payment; //  具体的支付类
    public $pay_code; //  具体的支付code
 
    /**
     * 析构流函数
     */
    public function  __construct() {   
        parent::__construct();           
        
        $this->pay_code = I('post.pay_code/s','AppWeixinPay');
        //unset(I('post.pay_code/s','')); // 用完之后删除, 以免进入签名判断里面去 导致错误
																	                   
        //获取通知的数据
        $xml = $GLOBALS['HTTP_RAW_POST_DATA'];      
        $xml = file_get_contents('php://input');
        if(empty($this->pay_code))$this->ajaxReturn(['status' => -9 , 'msg'=>'pay_code 不能为空','data'=>null]);	
        // 导入具体的支付类文件                
        include_once  "plugins/payment/{$this->pay_code}/{$this->pay_code}.class.php"; 
		// D:\wamp\www\svn_tpshop\www\plugins\payment\alipay\alipayPayment.class.php                       
        $code = '\\'.$this->pay_code; // \alipay
        $this->payment = new $code();
    }

    /*
     * 获取支付方式
     */
    public function get_pay_way(){  
        $user_id = $this->get_user_id();
        if(!$user_id)$this->ajaxReturn(['status' => -1 , 'msg'=>'用户不存在','data'=>null]);		
        $order_id = I('post.order_id/d',0);
        $order_sn= I('post.order_sn/s','');
        $order_where = ['user_id'=>$user_id];
        if($order_sn)
        {
            $order_where['order_sn'] = $order_sn;
        }else{
            $order_where['order_id'] = $order_id;
        }
        $Order = new Order();
        $order = $Order->where($order_where)->find();		

        if(!$order)$this->ajaxReturn(['status' => -2 , 'msg'=>'订单不存在！','data'=>null]);
        if($order['order_status'] == 1){
			$this->ajaxReturn(['status' => -3 , 'msg'=>'该订单已支付过','data'=>null]);
        }
        if($order['order_status'] == 3){
			$this->ajaxReturn(['status' => -4 , 'msg'=>'该订单已取消','data'=>null]);
        }
        if(empty($order) || empty($user_id)){
            $this->ajaxReturn(['status' => -5 , 'msg'=>'用户不存在','data'=>null]);
        }
        // 如果已经支付过的订单直接到订单详情页面. 不再进入支付页面
        if($order['pay_status'] == 1){
            $this->ajaxReturn(['status' => -3 , 'msg'=>'该订单已支付过','data'=>null]);
        }
        $orderGoodsPromType = M('order_goods')->where(['order_id'=>$order['order_id']])->getField('prom_type',true);
        //如果是预售订单，支付尾款
        if ($order['pay_status'] == 2 && $order['prom_type'] == 4) {
            if ($order['pre_sell']['pay_start_time'] > time()) {
				$this->ajaxReturn(['status' => -6 , 'msg'=>'还未到支付尾款时间','data'=>null]);  //date('Y-m-d H:i:s', $order['pre_sell']['pay_start_time'])
            }
            if ($order['pre_sell']['pay_end_time'] < time()) {
				$this->ajaxReturn(['status' => -7 , 'msg'=>'对不起，该预售商品已过尾款支付时间','data'=>null]); //date('Y-m-d H:i:s',$order['pre_sell']['pay_end_time'] )
            }
        }
        $payment_where['type'] = 'payment';
        $no_cod_order_prom_type = [4,5];//预售订单，虚拟订单不支持货到付款
 
		if(in_array($order['prom_type'],$no_cod_order_prom_type) || in_array(1,$orderGoodsPromType) || $order['shop_id'] > 0){
			//预售订单和抢购不支持货到付款
			$payment_where['code'] = array('neq','cod');
		}
		$payment_where['scene'] = array('in',array('0','1','2','3','4'));
        
        $payment_where['status'] = 1;
        //预售和抢购暂不支持货到付款
        $orderGoodsPromType = M('order_goods')->where(['order_id'=>$order['order_id']])->getField('prom_type',true);
        if($order['prom_type'] == 4 || in_array(1,$orderGoodsPromType)){
            $payment_where['code'] = array('neq','cod');
        }	
        $paymentList = M('Plugin')->where($payment_where)->select();   
        $paymentList = convert_arr_key($paymentList, 'code');  

        foreach($paymentList as $key => $val)
        {
            $val['config_value'] = unserialize($val['config_value']);
            if($val['config_value']['is_bank'] == 2)
            {
                $bankCodeList[$val['code']] = unserialize($val['bank_code']);
            }
            if($key != 'cod' && (($key == 'weixin' && !is_weixin()) // 不是微信app,就不能微信付，只能weixinH5付,用于手机浏览器
                || ($key != 'weixin' && is_weixin()) //微信app上浏览，只能微信
                || ($key != 'alipayMobile' && is_alipay()))){ //在支付宝APP上浏览，只能用支付宝支付
                unset($paymentList[$key]);
            }
			unset($paymentList[$key]['author']);
			unset($paymentList[$key]['config']);
			unset($paymentList[$key]['config_value']);
			unset($paymentList[$key]['bank_code']);
			unset($paymentList[$key]['scene']);
        }

        $bank_img = include APP_PATH.'home/bank.php'; // 银行对应图片
		$data = [
			'paymentList'	=> $paymentList,
			'bank_img'		=> $bank_img,
			'order'	     	=> ['order_amount'=>$order['order_amount'],'add_time'=>$order['add_time'],'prom_type'=>$order['prom_type']],
			'bankCodeList'	=> $bankCodeList,
			'pay_date'		=> date('Y-m-d', strtotime("+1 day"))
		];					
        $this->ajaxReturn(['status' => 0 , 'msg'=>'请求成功！','data'=>$data]);
    }
   
    /**
     * tpshop 提交支付方式
     */
    public function getCode(){        
            //C('TOKEN_ON',false); // 关闭 TOKEN_ON
            header("Content-type:text/html;charset=utf-8");            
            $order_id = I('order_id/d'); // 订单id
            session('order_id',$order_id); // 最近支付的一笔订单 id
            if(!session('user')) $this->error('请先登录',U('User/login'));
            $order = Db::name('Order')->where(['order_id' => $order_id])->find();
            if(empty($order) || $order['order_status'] > 1){
                $this->error('非法操作！',U("Home/Index/index"));
            }
            if($order['pay_status'] == 1){
                $this->error('此订单，已完成支付!');
            }
        	// 修改订单的支付方式
            $payment_arr = M('Plugin')->where("`type` = 'payment'")->getField("code,name");
            M('order')->where("order_id",$order_id)->save(array('pay_code'=>$this->pay_code,'pay_name'=>$payment_arr[$this->pay_code]));

            // tpshop 订单支付提交
            $pay_radio = $_REQUEST['pay_radio'];
            $config_value = parse_url_param($pay_radio); // 类似于 pay_code=alipay&bank_code=CCB-DEBIT 参数
            $payBody = getPayBody($order_id);
            $config_value['body'] = $payBody;
            
            //微信JS支付
           if($this->pay_code == 'weixin' && $_SESSION['openid'] && strstr($_SERVER['HTTP_USER_AGENT'],'MicroMessenger')){
               $code_str = $this->payment->getJSAPI($order,$config_value);
               exit($code_str);
           }else{
           	    $code_str = $this->payment->get_code($order,$config_value);
           }
           $this->assign('code_str', $code_str); 
           $this->assign('order_id', $order_id);           
           return $this->fetch('payment');  // 分跳转 和不 跳转 
    }

    public function pay_order(){		
		//alipay,alipayMobile,cod【货到付款】，tenpay【PC端财付通】， weixin 【PC端+微信公众号支付】， weixinH5 【微信支付H5】
        //$user_id = $this->get_user_id();
		$user_id = 12;
        if(!$user_id){
            $this->ajaxReturn(['status' => -1 , 'msg'=>'用户不存在','data'=>'']);
        }
        $order_id = I('post.id/d',1349);
        //验证是否本人的
        $order = Db::name('order')->where('order_id',$order_id)->select();
        if(!$order){
            $this->ajaxReturn(['status' => -3 , 'msg'=>'订单不存在','data'=>'']);
        }
        if($order['0']['user_id']!=$user_id){
            $this->ajaxReturn(['status' => -2 , 'msg'=>'非本人订单','data'=>'']);
        }

		$pay_code = I('post.pay_code/s','weixinH5');
    	// 修改充值订单的支付方式
    	$payment_arr = M('Plugin')->where("`type` = 'payment'")->getField("code,name");
    	
    	M('recharge')->where("order_id", $order_id)->save(array('pay_code'=>$pay_code,'pay_name'=>$payment_arr[$pay_code]));
    	$order = M('recharge')->where("order_id", $order_id)->find();
    	if($order['pay_status'] == 1){
			$this->ajaxReturn(['status' => -4 , 'msg'=>'此订单，已完成支付!','data'=>'']);
    	}
    	$pay_radio = $_REQUEST['pay_radio'];
    	$config_value = parse_url_param($pay_radio); // 类似于 pay_code=alipay&bank_code=CCB-DEBIT 参数
        $order['order_amount'] = $order['account'];
        $config_value['body'] = ($order['buy_vip'] == 1) ? 'VIP充值' : '充值到余额';

    	//微信JS支付
    	if($pay_code == 'weixin' && $_SESSION['openid'] && strstr($_SERVER['HTTP_USER_AGENT'],'MicroMessenger')){
    		$code_str = $this->payment->getJSAPI($order,$config_value);
    		exit($code_str);
    	} else {  
            $code_str = $this->payment->get_code($order,$config_value); 
        } 
		$code_str['status'] = ($code_str['status'] === 1) ? 0 : $code_str['status'];
		$this->ajaxReturn(['status' => $code_str['status'] , 'msg'=>$code_str['msg'],'data'=>'']);
    }
    
    // 服务器点对点 // http://www.dchqzg1688.com/index.php/Home/Payment/notifyUrl        
    public function notifyUrl(){            
        $this->payment->response();            
        exit();
    }

    // 页面跳转 // http://www.dchqzg1688.com/index.php/Home/Payment/returnUrl        
    public function returnUrl(){
        $result = $this->payment->respond2(); // $result['order_sn'] = '201512241425288593';
        
        if(stripos($result['order_sn'],'recharge') !== false)
        {
            $order = M('recharge')->where("order_sn", $result['order_sn'])->find();
            $this->assign('order', $order);
            if($result['status'] == 1)
                return $this->fetch('recharge_success');   
            else
                return $this->fetch('recharge_error');   
            exit();            
        }
                
        $order = M('order')->where("order_sn", $result['order_sn'])->find();
        if(empty($order)) // order_sn 找不到 根据 order_id 去找
        {
            $order_id = session('order_id'); // 最近支付的一笔订单 id        
            $order = M('order')->where("order_id", $order_id)->find();
        }
                
        $this->assign('order', $order);
        if($result['status'] == 1)
            return $this->fetch('success');   
        else
            return $this->fetch('error');   
    }  

    public function refundBack(){
    	$this->payment->refund_respose();
    	exit();
    }
    
    public function transferBack(){
    	$this->payment->transfer_response();
    	exit();
    }

    //微信支付
	public function GetWxAppPaySign(){
        $order_sn = I('post.order_sn/s','');
        $user_id = $this->get_user_id();    
        if(!$order_sn || !$user_id){
            $this->ajaxReturn(['status' => -1 , 'msg'=>'参数错误','data'=>null]); 
        }
        $info = M('Order')->field('order_amount')->where(['order_sn'=>$order_sn,'user_id'=>$user_id])->find();
        if(!$info){
            $this->ajaxReturn(['status' => -2 , 'msg'=>'订单不存在','data'=>null]); 
        }   
        $arr = $this->GetPay(['ordernum'=>$order_sn,'price'=>$info['order_amount']]);
        $arr['packageValue'] = $arr['package'];
        unset($arr['package']);
        $this->ajaxReturn(['status' => 0 , 'msg'=>'请求成功','data'=>$arr]); 
    }   

    //竞拍保证金支付
    public function payBond(){
        $user_id = $this->get_user_id();  
        $id = I("post.id/d",0);
        if(!$id || !$user_id){
            $this->ajaxReturn(['status' => -1 , 'msg'=>'参数错误','data'=>null]); 
        }

        $money = Db::name('Auction')->where('id',$id)->value('deposit');
		$OrderLogic = new OrderLogic();
        $data['deposit'] = $money;
        $data['user_id'] = $user_id;
        $data['auction_id'] = $id;
        $data['order_sn'] = $OrderLogic->get_order_sn_auction_deposit();  
        $data['create_time'] = time(); 
        $order_id = M('auctionDeposit')->add($data);

        if($order_id){
            $order = M('auctionDeposit')->where("id", $order_id)->find();
            if(is_array($order) && $order['status']==0){
                $arr = $this->GetPay(['ordernum'=>$order['order_sn'],'price'=>$order['deposit']]);
                $arr['packageValue'] = $arr['package'];
                unset($arr['package']);
                $this->ajaxReturn(['status' => 0 , 'msg'=>'请求成功','data'=>$arr]); 
            }else{
                $this->ajaxReturn(['status' => -2 , 'msg'=>'您已交过保证金','data'=>$arr]); 
            }
        }else{
            $this->ajaxReturn(['status' => -3 , 'msg'=>'提交失败,参数有误!','data'=>$arr]); 
        }
    }    

//=======================================================================
    private function GetPay($data){       
        if($data['price'] <= 0){
            $this->ajaxReturn(['status' => -2 , 'msg'=>'价格异常','data'=>null]); 
        }

		//include_once "plugins/payment/appWeixinPay/WxPay.Api.php";

        $input = new \WxAppPayUnifiedOrder(['APPID'=>C('customize.WX_APP_LOGIN_APPID'),'APPSECRET'=>C('customize.WX_APP_LOGIN_SECRET'),'KEY'=>C('customize.KEY'),'MCHID'=>C('customize.MCHID')]); 
        $input->SetBody('DC商城订单支付');
        $input->SetOut_trade_no($data['ordernum'].time());     //订单号
        //$input->SetTime_expire(date('yyyyMMddHHmmss',time()+20));     //订单号
        $input->SetTotal_fee($data['price']*100);   
        $input->SetNotify_url(SITE_URL.'/index.php/Home/Payment/notifyUrl/pay_code/AppWeixinPay');
        //$input->SetNotify_url(SITE_URL.'/index.php/api/Payment/WxAppNotifyUrl');
        $input->SetTrade_type("APP");
        $result = \AppWeixinPay :: unifiedOrder($input,15);   

        $arr = array( 
            'appid'         => $result['appid'],
            'partnerid'     => $result['mch_id'],
            'prepayid'      => $result['prepay_id'],
            'package'       => 'Sign=WXPay',
            'noncestr'      => $result['nonce_str'],
            'timestamp'     => time(),
            'sign'          => $result['sign']

        );

        $arr['sign'] = $this->MakeSign($arr);   
        return $arr;      
    }

	private function ToUrlParams($arr)
	{
		$buff = "";
		foreach ($arr as $k => $v)
		{
			if($k != "sign" && $v != "" && !is_array($v)){
				$buff .= $k . "=" . $v . "&";
			}
		}
		
		$buff = trim($buff, "&");
		return $buff;
	}

	private function MakeSign($arr)
	{
		//签名步骤一：按字典序排序参数
		ksort($arr);
		$string = $this->ToUrlParams($arr);        
		//签名步骤二：在string后加入KEY
		$string = $string . "&key=".C('customize.KEY');
		//签名步骤三：MD5加密
		$string = md5($string);
		//签名步骤四：所有字符转为大写
		$result = strtoupper($string);
		return $result;
	}

}
