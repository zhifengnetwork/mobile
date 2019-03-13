<?php

namespace app\mobile\controller;
use app\common\logic\UsersLogic;
use app\common\logic\CartLogic;
use app\common\logic\wechat\WechatUtil;
use think\Request;

class LoginApi extends MobileBase{
    public $config;
    public $oauth;
    public $class_obj;

    public function __construct(){
        parent::__construct();    
        $this->oauth = I('get.oauth');
        //获取配置

        //注释

        // $data = M('Plugin')->where("code=:code and  type = 'login' ")->bind(['code'=>$this->oauth])->find();
        // $this->config = unserialize($data['config_value']); // 配置反序列化
        // if(!$this->oauth)
        //     $this->error('非法操作',U('Mobile/User/login'));
        // include_once  "plugins/login/{$this->oauth}/{$this->oauth}.class.php";
        // $class = '\\'.$this->oauth; //
        // $login = new $class($this->config); //实例化对应的登陆插件
        // $this->class_obj = $login;


        $this->weixin_config = M('wx_user')->find(); //取微获信配置
        $this->assign('wechat_config', $this->weixin_config);      

    }

    public function login(){
        if(!$this->oauth)
            $this->error('非法操作',U('Mobile/User/login'));
        // include_once  "plugins/login/{$this->oauth}/{$this->oauth}.class.php";
        // $this->class_obj->login();

        $d = $this->GetOpenid();
        
        $data = $logic->thirdLogin($d);
        //直接去登录，空 就注册
        if($data['status'] == 1){
            session('user',$data['result']);
            setcookie('user_id',$data['result']['user_id'],null,'/');
            setcookie('is_distribut',$data['result']['is_distribut'],null,'/');
            setcookie('uname',$data['result']['nickname'],null,'/');
            // 登录后将购物车的商品的 user_id 改为当前登录的id
            M('cart')->where("session_id" ,$this->session_id)->save(array('user_id'=>$data['result']['user_id']));
            $cartLogic = new CartLogic();
            $cartLogic->setUserId($data['result']['user_id']);
            $cartLogic->doUserLoginHandle();  //用户登录后 需要对购物车 一些操作
        }


    }

   // 网页授权登录获取 OpendId
    public function GetOpenid()
    {
       
        //通过code获得openid
        if (!isset($_GET['code'])){
            //触发微信返回code码
            //$baseUrl = urlencode('http://'.$_SERVER['HTTP_HOST'].$_SERVER['PHP_SELF'].'?'.$_SERVER['QUERY_STRING']);
            $baseUrl = urlencode($this->get_url());
            $url = $this->__CreateOauthUrlForCode($baseUrl); // 获取 code地址
            Header("Location: $url"); // 跳转到微信授权页面 需要用户确认登录的页面
            exit();
        } else {
            //上面获取到code后这里跳转回来
            $code = $_GET['code'];
            $data = $this->getOpenidFromMp($code);//获取网页授权access_token和用户openid
            $data2 = $this->GetUserInfo($data['access_token'],$data['openid']);//获取微信用户信息
            $data['nickname'] = empty($data2['nickname']) ? '微信用户' : trim($data2['nickname']);
            $data['sex'] = $data2['sex'];
            $data['head_pic'] = $data2['headimgurl']; 
            $data['subscribe'] = $data2['subscribe'];      
            $data['oauth_child'] = 'mp';
            $_SESSION['openid'] = $data['openid'];
            $data['oauth'] = 'weixin';
            if(isset($data2['unionid'])){
            	$data['unionid'] = $data2['unionid'];
            }
            $_SESSION['data'] =$data;
            return $data;
        }
    }

    /**
     * 获取当前的url 地址
     * @return type
     */
    private function get_url() {
        $sys_protocal = isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == '443' ? 'https://' : 'http://';
        $php_self = $_SERVER['PHP_SELF'] ? $_SERVER['PHP_SELF'] : $_SERVER['SCRIPT_NAME'];
        $path_info = isset($_SERVER['PATH_INFO']) ? $_SERVER['PATH_INFO'] : '';
        $relate_url = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : $php_self.(isset($_SERVER['QUERY_STRING']) ? '?'.$_SERVER['QUERY_STRING'] : $path_info);
        return $sys_protocal.(isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : '').$relate_url;
    }    
    
    /**
     *
     * 通过code从工作平台获取openid机器access_token
     * @param string $code 微信跳转回来带上的code
     *
     * @return openid
     */
    public function GetOpenidFromMp($code)
    {
        //通过code获取网页授权access_token 和 openid 。网页授权access_token是一次性的，而基础支持的access_token的是有时间限制的：7200s。
    	//1、微信网页授权是通过OAuth2.0机制实现的，在用户授权给公众号后，公众号可以获取到一个网页授权特有的接口调用凭证（网页授权access_token），通过网页授权access_token可以进行授权后接口调用，如获取用户基本信息；
    	//2、其他微信接口，需要通过基础支持中的“获取access_token”接口来获取到的普通access_token调用。
        $url = $this->__CreateOauthUrlForOpenid($code);       
        $ch = curl_init();//初始化curl        
        curl_setopt($ch, CURLOPT_TIMEOUT, 300);//设置超时
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER,FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST,FALSE);
        curl_setopt($ch, CURLOPT_HEADER, FALSE);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);         
        $res = curl_exec($ch);//运行curl，结果以jason形式返回            
        $data = json_decode($res,true);         
        curl_close($ch);
        return $data;
    }
    
    
        /**
     *
     * 通过access_token openid 从工作平台获取UserInfo      
     * @return openid
     */
    public function GetUserInfo($access_token,$openid)
    {         
        // 获取用户 信息
        $url = $this->__CreateOauthUrlForUserinfo($access_token,$openid);
        $ch = curl_init();//初始化curl        
        curl_setopt($ch, CURLOPT_TIMEOUT, 300);//设置超时
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER,FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST,FALSE);
        curl_setopt($ch, CURLOPT_HEADER, FALSE);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);         
        $res = curl_exec($ch);//运行curl，结果以jason形式返回            
        $data = json_decode($res,true);            
        curl_close($ch);
        //获取用户是否关注了微信公众号， 再来判断是否提示用户 关注
        //if(!isset($data['unionid'])){
            $wechat = new WechatUtil($this->weixin_config);
            $fan = $wechat->getFanInfo($openid);//获取基础支持的access_token
            if ($fan !== false) {
                $data['subscribe'] = $fan['subscribe'];
            }
        //}
        return $data;
    }

    /**
     *
     * 构造获取code的url连接
     * @param string $redirectUrl 微信服务器回跳的url，需要url编码
     *
     * @return 返回构造好的url
     */
    private function __CreateOauthUrlForCode($redirectUrl)
    {
        $urlObj["appid"] = $this->weixin_config['appid'];
        $urlObj["redirect_uri"] = "$redirectUrl";
        $urlObj["response_type"] = "code";
//        $urlObj["scope"] = "snsapi_base";
        $urlObj["scope"] = "snsapi_userinfo";
        $urlObj["state"] = "STATE"."#wechat_redirect";
        $bizString = $this->ToUrlParams($urlObj);
        return "https://open.weixin.qq.com/connect/oauth2/authorize?".$bizString;
    }

    /**
     *
     * 构造获取open和access_toke的url地址
     * @param string $code，微信跳转带回的code
     *
     * @return 请求的url
     */
    private function __CreateOauthUrlForOpenid($code)
    {
        $urlObj["appid"] = $this->weixin_config['appid'];
        $urlObj["secret"] = $this->weixin_config['appsecret'];
        $urlObj["code"] = $code;
        $urlObj["grant_type"] = "authorization_code";
        $bizString = $this->ToUrlParams($urlObj);
        return "https://api.weixin.qq.com/sns/oauth2/access_token?".$bizString;
    }

    /**
     *
     * 构造获取拉取用户信息(需scope为 snsapi_userinfo)的url地址     
     * @return 请求的url
     */
    private function __CreateOauthUrlForUserinfo($access_token,$openid)
    {
        $urlObj["access_token"] = $access_token;
        $urlObj["openid"] = $openid;
        $urlObj["lang"] = 'zh_CN';        
        $bizString = $this->ToUrlParams($urlObj);
        return "https://api.weixin.qq.com/sns/userinfo?".$bizString;                    
    }    
    
    /**
     *
     * 拼接签名字符串
     * @param array $urlObj
     *
     * @return 返回已经拼接好的字符串
     */
    private function ToUrlParams($urlObj)
    {
        $buff = "";
        foreach ($urlObj as $k => $v)
        {
            if($k != "sign"){
                $buff .= $k . "=" . $v . "&";
            }
        }

        $buff = trim($buff, "&");
        return $buff;
    }



    public function callback(){
        
        $data = $this->class_obj->respon();
        $redata = $data;
        $logic = new UsersLogic();
        
        //手机端登录, 标识该openid来微信自公众号
        if($data['oauth'] == 'weixin')$data['oauth_child'] = 'mp';
         
        //过滤昵称中的特殊字符
        $data['nickname'] && $data['nickname'] = replaceSpecialStr($data['nickname']);
        
        $is_bind_account = tpCache('basic.is_bind_account');
        
        if($is_bind_account){
            
            if($data['unionid']){
                $thirdUser = M('OauthUsers')->where(['unionid'=>$data['unionid'], 'oauth'=>$data['oauth']])->find();
            }else{
                $thirdUser = M('OauthUsers')->where(['openid'=>$data['openid'], 'oauth'=>$data['oauth']])->find();
            }
            
            //1. 第二种方式:第三方账号首次登录必须绑定账号
            if(empty($thirdUser)){
                //用户未关联账号, 跳到关联账号页
                session('third_oauth',$data);
                return $this->redirect(U('Mobile/User/bind_guide'));
            }else{
                //微信自动登录
                $data = $logic->thirdLogin_new($data);
            }
        }else{
            //2.第一种方式:第三方账号首次直接创建账号, 不需要额外绑定账号
            $data = $logic->thirdLogin($data);
        }
        
        if($data['status'] == 1){
            session('user',$data['result']);
            setcookie('user_id',$data['result']['user_id'],null,'/');
            setcookie('is_distribut',$data['result']['is_distribut'],null,'/');
            setcookie('uname',$data['result']['nickname'],null,'/');
            // 登录后将购物车的商品的 user_id 改为当前登录的id
            M('cart')->where("session_id" ,$this->session_id)->save(array('user_id'=>$data['result']['user_id']));
            $cartLogic = new CartLogic();
            $cartLogic->doUserLoginHandle($this->session_id,$data['result']['user_id']);  //用户登录后 需要对购物车 一些操作
            $this->success('登陆成功',U('Mobile/User/index'));
        }else{
            if ($redata['openid'] && ($redata['oauth'] == 'alipay' or $redata['oauth'] == 'alipaynew')) {
                //支付宝浏览商城，重复登录
                $this->success('登陆成功',U('User/index'));
            }else{
                $this->success('登陆失败: '.$data['msg']);
            }
        }
    }
}