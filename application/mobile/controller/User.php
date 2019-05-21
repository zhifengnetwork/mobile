<?php

namespace app\mobile\controller;

use app\common\logic\CartLogic;
use app\common\logic\Message;
use app\common\logic\UsersLogic;
use app\common\logic\DistributLogic;
use app\common\logic\PerformanceLogic;
use app\common\logic\OrderLogic;
use app\common\logic\LevelLogic;
use app\common\model\MenuCfg;
use app\common\model\UserAddress;
use app\common\model\Users as UserModel;
use app\common\model\UserMessage;
use app\common\util\TpshopException;
use app\common\logic\ShareLogic;
use think\Cache;
use think\Page;
use think\Verify;
use think\Loader;
use think\db;
use think\Image;

class User extends MobileBase
{

    public $user_id = 0;
    public $user = array();

    /*
    * 初始化操作
    */
    public function _initialize()
    {
        parent::_initialize();
        if (session('?user')) {
            $User = new UserModel();
            $session_user = session('user');
            $this->user = $User->where('user_id', $session_user['user_id'])->find();
            if(!empty($this->user->auth_users)){
                $session_user = array_merge($this->user->toArray(), $this->user->auth_users[0]);
                session('user', $session_user);  //覆盖session 中的 user
            }
            $this->user_id = $this->user['user_id'];
            $this->assign('user', $this->user); //存储用户信息0
        }
        $nologin = array(
            'login', 'pop_login', 'do_login', 'logout', 'verify', 'set_pwd', 'finished',
            'verifyHandle', 'reg', 'send_sms_reg_code', 'find_pwd', 'check_validate_code',
            'forget_pwd', 'check_captcha', 'check_username', 'send_validate_code', 'express' , 'bind_guide', 'bind_account','bind_reg'
        );
        $is_bind_account = tpCache('basic.is_bind_account');
        if (!$this->user_id && !in_array(ACTION_NAME, $nologin)) {
            if(strstr($_SERVER['HTTP_USER_AGENT'],'MicroMessenger') && $is_bind_account){
                header("location:" . U('Mobile/User/bind_guide'));//微信浏览器, 调到绑定账号引导页面
            }else{
                header("location:" . U('Mobile/User/login'));
            }
            exit;
        }

        $order_status_coment = array(
            'WAITPAY' => '待付款 ', //订单查询状态 待支付
            'WAITSEND' => '待发货', //订单查询状态 待发货
            'WAITRECEIVE' => '待收货', //订单查询状态 待收货
            'WAITCCOMMENT' => '待评价', //订单查询状态 待评价
        );
        $this->assign('order_status_coment', $order_status_coment);

       
        //判断头像是否为空，空就补头像
        if( $this->user['head_pic'] == null || $this->user['head_pic'] == ''){
           
            $openid = $this->user['openid']; 
            $access_token = access_token();
            $url = 'https://api.weixin.qq.com/cgi-bin/user/info?access_token='.$access_token.'&openid='.$openid.'&lang=zh_CN';
            $resp = httpRequest($url, "GET");
            $res = json_decode($resp, true);
           
            $head_pic = $res['headimgurl'];
            if($head_pic){
                //得到头像
                M('users')->where(['openid'=>$this->user['openid']])->update(['head_pic'=>$head_pic]);
                $this->user['head_pic'] = $head_pic;
            }
           
        }

    }


    public function distribut(){

        $user_id = session('user.user_id');
       
        $per_logic =  new PerformanceLogic();
        $money_total = $per_logic->distribut_caculate();
       
        //补业绩
        if($money_total['moneys'] < 0){
            $bu_moneys = -1 * $money_total['moneys'] * 2; //补 两倍 的 差值
            //这里重新
            $add_logic = new \app\common\logic\AgentPerformanceAddLogic();
            $add_logic->add($user_id,$bu_moneys);
           
            //重新来
            $per_logic =  new PerformanceLogic();
            $money_total = $per_logic->distribut_caculate();
        }

        $this->assign('money_total',$money_total);

        //上级用户信息
        $leader_id = M('users')->where(['user_id'=> $user_id])->value('first_leader');
        if($leader_id){
            $leader = M('users')->where(['user_id'=>$leader_id])->field('user_id, nickname')->find();
            if($leader){
                $this->assign('leader',$leader);
            }
        }
      
        $underling_number = M('users')->where(['user_id'=>$user_id])->value('underling_number');
        $underling_number == NULL ? $underling_number = '0' : $underling_number;
        $this->assign('underling_number', $underling_number);
 
        $this->assign('user_id',$user_id);

        $this->assign('time',date('Y-m-d H:i'));

        return $this->fetch();
    }


   


    public function distribut_42()
    {
        
        // $user = session('user');
        // $field = "user_id,first_leader,is_distribut,is_agent"; 
        // $user_agent_money = $this->child_agent($user['user_id']);
        // //个人,团队业绩之和
        // $money_array = $user_agent_money['ind_per']+$user_agent_money['agent_per'];
        // $users = M('users')->where(['first_leader'=>$user['user_id']])->field($field)->select();
        // if($users)
        // {
        //     if(empty($users)) return false;
        //     $money_array = [];
        //     foreach($users as $key=>$val){
        //         $get_child_agent = $this->child_agent($val['user_id']);
        //         if (!empty($get_child_agent['agent_per'])) {
        //             $money_array[]=$get_child_agent['agent_per'];
        //         }
        //     }
        //     if(!empty($money_array)){

        //         $moneys = array_filter($money_array);
        //         rsort($moneys);
        //         //最大业绩用户
        //         if(count($moneys) >= 2){
        //             $max_moneys = max($moneys);
        //         }else{
        //             $max_moneys = $moneys[0];
        //         }
        //         array_shift($moneys);
        //         //去掉最大业绩之和
        //         $moneys = array_sum($moneys);
        //         $agent = $this->child_agent($user['user_id']);
        //         $money_total1 = $agent['ind_per']+$agent['agent_per'];
        //         $money_total = array(
        //             'money_total'=>$money_total1,
        //             'max_moneys'=>$max_moneys,
        //             'moneys'=>$money_total1-$max_moneys
        //         );
        //     };
        // }
        // $money_total['money_total'] = (float)$money_total['money_total']+(float)$money_array;
        // $money_total['max_moneys'] = 0;
        // $money_total['moneys'] = 0;

        //古老的历史业绩
        $user = session('user');

        $logic = new \app\common\logic\AgentPerformanceOldLogic();
        $oldPerformance = $logic->getAllData($user['openid']);
        //这是老的历史业绩，加上新的
        $this->assign('oldPerformance',$oldPerformance);

        //dump($oldPerformance);
       
        $field = "user_id, first_leader, is_distribut, is_agent, openid";
        $user_agent_money = $this->child_agent($user['user_id']);

        //个人,团队业绩之和
        if($user_agent_money){
            $per_total = $user_agent_money['ind_per'] + $user_agent_money['agent_per'] + $oldPerformance;
        }else{
            $per_total = $oldPerformance;
        }
        $users = M('users')->alias('u')
            ->join('tp_agent_performance ag','ag.user_id = u.user_id')
            ->where(['u.first_leader'=>$user['user_id']])
            ->field('max(u.team + ag.agent_per) as team')
            ->find();

        $max_team_taotal = $users['team'];
        $money_total = array(
            'money_total' => (float)$per_total,
            'max_moneys'  => (float)$max_team_taotal,
            'moneys' => (float)$per_total  - $max_team_taotal,
        );

//        if($users)
//        {
//            if(empty($users)) return false;
//            $agent_array = [];
//            foreach($users as $key=>$val){
//                $get_child_agent = $this->child_agent($val['user_id']);
//                $get_childoldPerformance = $logic->getAllData($val['openid']);
//                /*if($get_child_agent){
//                    $agent_array[]= $get_child_agent['agent_per'] + $get_childoldPerformance;
//                }else{*/
//                    $agent_array[]= $get_childoldPerformance;
//                //}
//            }
//
//            if(!empty($agent_array)){
//                $moneys = array_filter($agent_array);
//                rsort($moneys);
//                //最大业绩用户
//                if(count($moneys) >= 2){
//                    $agent_max = max($moneys);
//                }else{
//                    $agent_max = $moneys[0];
//                }
//                array_shift($moneys);
//                //去掉最大业绩之和
//                $agent_money = array_sum($moneys);
//                $money_total = array(
//                    'money_total' => (float)$per_total,
//                    'max_moneys'  => (float)$agent_max,
//                    'moneys' => (float)$agent_money + $oldPerformance,
//                );
//            }else{
//                $money_total = array(
//                    'money_total' => (float)$per_total,
//                    'max_moneys' => 0,
//                    'moneys' => $oldPerformance,
//                );
//            }
//        }else{
//            $money_total = array(
//                'money_total' => (float)$per_total,
//                'max_moneys' => 0,
//                'moneys' => $oldPerformance,
//            );
//        }
        $this->assign('money_total',$money_total);
        //上级用户信息
        $leader_id = M('users')->where(['user_id'=> $user['user_id']])->value('first_leader');
        if($leader_id){
            $leader = M('users')->where(['user_id'=>$leader_id])->field('user_id, nickname')->find();
            if($leader){
                $this->assign('leader',$leader);
            }
        }
        
        $this->assign('user_id', $user['user_id']);
        $underling_number = M('users')->where(['user_id'=>$user['user_id']])->value('underling_number');
        $underling_number == NULL ? $underling_number = '0' : $underling_number;
        $this->assign('underling_number', $underling_number);

        $this->assign('user_id',$user['user_id']);

        return $this->fetch('distribut');
    }

    private function child_agent($user_id)
	{
		$performance = M('agent_performance')->where(['user_id'=>$user_id])->find();
		if(empty($performance)) return false;
		return $performance;
	}

    public function index()
    {
        $MenuCfg = new MenuCfg();
        $menu_list = $MenuCfg->where('is_show', 1)->order('menu_id asc')->select();
        
        $user_id = session('user.user_id');

        //当前登录用户信息
        $logic = new UsersLogic();
        $user_info = $logic->get_info($user_id); 

        $order_info['waitPay'] = $user_info['result']['waitPay'];
        $order_info['waitSend'] = $user_info['result']['waitSend'];
        
        $order_info['waitReceive'] = $user_info['result']['waitReceive'];
        $order_info['uncomment_count'] = $user_info['result']['uncomment_count'];
        $order_info['return_count'] = Db::name('return_goods')->where("user_id", $user_id)->count();

        $user_money = M('users')->where(['user_id'=>$user_id])->value('user_money');
        $this->assign('user_money', $user_money);
        $this->assign('order_info', $order_info);
        $this->assign('menu_list', $menu_list);

        //更新团队总人数
        $url = SITE_URL."/api/distribut/get_team_num?user_id=".$user_id;
        httpRequest($url);

        $up_url = SITE_URL."/api/distribut/upgrade?user_id=".$user_id;
        httpRequest($up_url);

        //区域代理
        $area_agent = M('user_regional_agency')->where('user_id', $user_id)->find();
        if($area_agent){
            $agency_name = M('config_regional_agency')->where('agency_level', $area_agent['agency_level'])->value('agency_name');
            $this->assign('agency_name', $agency_name);
        }

        //省代：开关
        $regional_agency_is_valid = (int)M('config')->where(['name'=>'is_valid'])->value('value');
        if($regional_agency_is_valid == 1){
            //区域代理升级
            $regional_agency = new \app\common\logic\RegionalAgencyLogic();
            $regional_agency->upgrade();

            //省代、等等
            $user_regional_agency = M('user_regional_agency')->where(['user_id'=>$user_id,'is_show'=>0])->find();
            if($user_regional_agency){
                //名字
                $config_regional_agency = M('config_regional_agency')->where(['agency_level'=>$user_regional_agency['agency_level']])->find();
                $user_regional_agency['agency_name'] = $config_regional_agency['agency_name'];
                $user_regional_agency['rate'] = $config_regional_agency['rate'];
            }
            
            $this->assign('user_regional_agency', $user_regional_agency);
        }
        $this->assign('regional_agency_is_valid', $regional_agency_is_valid);

        

        return $this->fetch();
    }

    /**
     * 新的分享
     */
    public function fenxiang()
    {
        $user_id = session('user.user_id');
        if(!$user_id){
            $this->redirect('fenxiang_no');
            exit;
        }
        $userinfo = M('users')->where(['user_id'=>$user_id])->find();
        if(!$userinfo){
            $this->redirect('fenxiang_no');
            exit;
        }
        if($userinfo['is_distribut'] == 0 && $userinfo['is_agent'] == 0){
            $this->redirect('fenxiang_no');
            exit;
        }

        $this->redirect('fenxiang1');
        //正在跳转
    }
    
    /**
     * 没权限
     */
    public function fenxiang_no(){

        return $this->fetch();
    }

    /**
     * 新的分享
     */
    public function fenxiang1()
    {
    
        $user_id = session('user.user_id');

        if(!$user_id){
            $this->redirect('fenxiang_no');
            exit;
        }
        $userinfo = M('users')->where(['user_id'=>$user_id])->find();
        if(!$userinfo){
            $this->redirect('fenxiang_no');
            exit;
        }
        if($userinfo['is_distribut'] == 0 && $userinfo['is_agent'] == 0){
            $this->redirect('fenxiang_no');
            exit;
        }


        define('IMGROOT_PATH', str_replace("\\","/",realpath(dirname(dirname(__FILE__)).'/../../'))); //图片根目录（绝对路径）
       
        //加上 refresh == 1 , 强制重新获取海报
        if(I('refresh') == '1'){
            //删掉文件
            @unlink(IMGROOT_PATH.'/public/share/head/'.$user_id.'.jpg');//删除头像
            @unlink(IMGROOT_PATH."/public/share/picture_ok44/'.$user_id.'.jpg");//删除 44
            @unlink(IMGROOT_PATH."/public/share/picture_888/".$user_id.".jpg");

            //强制获取头像
            $openid = session('user.openid');
            $access_token = access_token();
            $url = 'https://api.weixin.qq.com/cgi-bin/user/info?access_token='.$access_token.'&openid='.$openid.'&lang=zh_CN';
            $resp = httpRequest($url, "GET");
            $res = json_decode($resp, true);
           
            $head_pic = $res['headimgurl'];
            if($head_pic){
                //得到头像
                M('users')->where(['openid'=>$openid])->update(['head_pic'=>$head_pic]);
            }
        }
        

        //没头像 默认头像
        $head_pic_url = M('users')->where(['user_id'=>$user_id])->value('head_pic');
        if(!$head_pic_url || $head_pic_url == ''){
            $head_pic_url = '/public/images/default.jpg';
        }

        $logic = new ShareLogic();
        $ticket = $logic->get_ticket($user_id);

        if( strlen($ticket) < 3){
            $this->error("ticket不能为空");
            exit;
        }
        $url= "https://mp.weixin.qq.com/cgi-bin/showqrcode?ticket=".$ticket;

        $url222 = IMGROOT_PATH.'/public/share/code/'.$user_id.'.jpg';
        if( @fopen( $url222, 'r' ) )
        {
            //已经有二维码了
        	$url_code = IMGROOT_PATH.'/public/share/code/'.$user_id.'.jpg';
        }else{
            //还没有二维码
            $re = $logic->getImage($url,IMGROOT_PATH.'/public/share/code', $user_id.'.jpg');
            $url_code = $re['save_path'];
        }
        
        //判断图片大小
        $logo_url = \think\Image::open($url_code);
        $logo_url_logo_width = $logo_url->height();
        $logo_url_logo_height = $logo_url->width();

        if($logo_url_logo_height > 420 || $logo_url_logo_width > 420){
            //压缩图片
            $url_code = IMGROOT_PATH.'/public/share/code/'.$user_id.'.jpg';
            $logo_url->thumb(410, 410)->save($url_code , null, 100);
        }

        $head_url = IMGROOT_PATH.'/public/share/head/'.$user_id.'.jpg';
        if( @fopen( $head_url, 'r' ) )
        {
            //已经有二维码了
        	$url_head_pp = IMGROOT_PATH.'/public/share/head/'.$user_id.'.jpg';
        }else{
            //还没有二维码
            $re = $logic->getImage($head_pic_url,IMGROOT_PATH.'/public/share/head', $user_id.'.jpg');
            $url_head_pp = $re['save_path'];
        }
        
        //判断图片大小
        $logo = \think\Image::open($url_head_pp);
        $logo_width = $logo->height();
        $logo_height = $logo->width();
 
        //头像变成200
        if($logo_height > 260 || $logo_width > 260){
            //压缩图片
             $url_head_file = IMGROOT_PATH.'/public/share/head/'.$user_id.'.jpg';
             $logo->thumb(240, 240)->save($url_head_file , null, 100);
        }
        
        //得到二维码的绝对路径

        $pic = IMGROOT_PATH."/public/share/picture_ok44/'.$user_id.'.jpg";
        if( @fopen( $pic, 'r' ) )
        {
        	$pic = "/share/picture_ok44/".$user_id.".jpg";
        }
        else
        {
        	$image = \think\Image::open(IMGROOT_PATH.'/public/share/bg1.jpg');
        	// 给原图左上角添加水印并保存water_image.png
        	$image->water($url_code,\think\Image::DCHQZG)->save(IMGROOT_PATH.'/public/share/picture_ok44/'.$user_id.'.jpg');
        	
        	$pic = "/public/share/picture_ok44/".$user_id.".jpg";
        }
    
        //再次叠加

        $pic111 = IMGROOT_PATH."/public/share/picture_888/".$user_id.".jpg";
        if( @fopen( $pic111, 'r' ) )
        {
        	$picture = "/public/share/picture_888/".$user_id.".jpg";
        }
        else
        {
        	$image = \think\Image::open(IMGROOT_PATH.'/public/share/picture_ok44/'.$user_id.'.jpg');
        	// 给原图左上角添加水印并保存water_image.png
        	$image->water($url_head_pp,\think\Image::TOUXIANG)->save(IMGROOT_PATH.'/public/share/picture_888/'.$user_id.'.jpg');
          
        	$picture = "/public/share/picture_888/".$user_id.".jpg";
        }

        $picture = $picture.'?v='.time();
        $this->assign('pic',$picture);

        return $this->fetch('fenxiang');
    }

    

   

    public function logout()
    {
        session_unset();
        session_destroy();
        setcookie('uname','',time()-3600,'/');
        setcookie('cn','',time()-3600,'/');
        setcookie('user_id','',time()-3600,'/');
        setcookie('PHPSESSID','',time()-3600,'/');
        //$this->success("退出成功",U('Mobile/Index/index'));
        header("Location:" . U('Mobile/Index/index'));
        exit();
    }

    /*
     * 账户资金
     */
    public function account()
    {
        $user = session('user');
        //获取账户资金记录
        $logic = new UsersLogic();
        $data = $logic->get_account_log($this->user_id, I('get.type'));
        $account_log = $data['result'];

        $this->assign('user', $user);
        $this->assign('account_log', $account_log);
        $this->assign('page', $data['show']);

        if ($_GET['is_ajax']) {
            return $this->fetch('ajax_account_list');
            exit;
        }

        $money = M('users')->where(['user_id'=>$user['user_id']])->value('user_money');
        $this->assign('money', $money);


        return $this->fetch();
    }

    public function account_list()
    {
    	$type = I('type','all');
    	$usersLogic = new UsersLogic;
    	$result = $usersLogic->account($this->user_id, $type);
    
    	$this->assign('type', $type);
    	$this->assign('account_log', $result['account_log']);
    	if ($_GET['is_ajax']) {
    		return $this->fetch('ajax_account_list');
    	}
    	return $this->fetch();
    }

    public function account_detail(){
        $log_id = I('log_id/d',0);
        $detail = Db::name('account_log')->where(['log_id'=>$log_id])->find();
        $this->assign('detail',$detail);
        return $this->fetch();
    }
    
    /**
     * 优惠券
     */
    public function coupon()
    {
        $logic = new UsersLogic();
        $data = $logic->get_coupon($this->user_id, input('type'));
        foreach($data['result'] as $k =>$v){
            $user_type = $v['use_type'];
            $data['result'][$k]['use_scope'] = C('COUPON_USER_TYPE')["$user_type"];
            if($user_type==1){ //指定商品
                $data['result'][$k]['goods_id'] = M('goods_coupon')->field('goods_id')->where(['coupon_id'=>$v['cid']])->getField('goods_id');
            }
            if($user_type==2){ //指定分类
                $data['result'][$k]['category_id'] = Db::name('goods_coupon')->where(['coupon_id'=>$v['cid']])->getField('goods_category_id');
            }
        }
        $coupon_list = $data['result'];
        $this->assign('coupon_list', $coupon_list);
        $this->assign('page', $data['show']);
        if (input('is_ajax')) {
            return $this->fetch('ajax_coupon_list');
            exit;
        }
        return $this->fetch();
    }

   /**
     *  登录
     */
    public function login()
    {
        if ($this->user_id > 0) {
//            header("Location: " . U('Mobile/User/index'));
            $this->redirect('Mobile/User/index');
        }
        $referurl = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : U("Mobile/User/index");
        $this->assign('referurl', $referurl);
        // 新版支付宝跳转链接
        $this->assign('alipay_url', urlencode(SITE_URL.U("Mobile/LoginApi/login",['oauth'=>'alipaynew'])));
        return $this->fetch();
    }


    /**
     * 登录
     */
    public function do_login()
    {
        $username = trim(I('post.username'));
        $password = trim(I('post.password'));
        //验证码验证
        if (isset($_POST['verify_code'])) {
            $verify_code = I('post.verify_code');
            $verify = new Verify();
            if (!$verify->check($verify_code, 'user_login')) {
                $res = array('status' => 0, 'msg' => '验证码错误');
                exit(json_encode($res));
            }
        }
        $logic = new UsersLogic();
        $res = $logic->login($username, $password);
        if ($res['status'] == 1) {
            $res['url'] = htmlspecialchars_decode(I('post.referurl'));
            session('user', $res['result']);
            setcookie('user_id', $res['result']['user_id'], null, '/');
            setcookie('is_distribut', $res['result']['is_distribut'], null, '/');
            $nickname = empty($res['result']['nickname']) ? $username : $res['result']['nickname'];
            setcookie('uname', urlencode($nickname), null, '/');
            setcookie('cn', 0, time() - 3600, '/');
            $cartLogic = new CartLogic();
            $cartLogic->setUserId($res['result']['user_id']);
            $cartLogic->doUserLoginHandle();// 用户登录后 需要对购物车 一些操作
            $orderLogic = new OrderLogic();
            $orderLogic->setUserId($res['result']['user_id']);//登录后将超时未支付订单给取消掉
            $orderLogic->abolishOrder();
        }
        exit(json_encode($res));
    }

    /**
     *  注册
     */
    public function reg()
    {

        if($this->user_id > 0) {
            $this->redirect(U('Mobile/User/index'));
        }
        $reg_sms_enable = tpCache('sms.regis_sms_enable');
        $reg_smtp_enable = tpCache('sms.regis_smtp_enable');

        if (IS_POST) {
            $logic = new UsersLogic();
            //验证码检验
            //$this->verifyHandle('user_reg');
            $nickname = I('post.nickname', '');
            $username = I('post.username', '');
            if(!$username){
                $username = I('post.useriphone');
            }
            $password = I('post.password', '');
            $password2 = I('post.password2', '');
            $is_bind_account = tpCache('basic.is_bind_account');
            //是否开启注册验证码机制
            $code = I('post.mobile_code', '');
            $scene = I('post.scene', 1);
            
            $session_id = session_id();

            //是否开启注册验证码机制
            if(check_mobile($username)){
                if($reg_sms_enable){
                    //手机功能没关闭
                    $check_code = $logic->check_validate_code($code, $username, 'phone', $session_id, $scene);
                    if($check_code['status'] != 1){
                        $this->ajaxReturn($check_code);
                    }
                }
            }
            //是否开启注册邮箱验证码机制
            if(check_email($username)){
                if($reg_smtp_enable){
                    //邮件功能未关闭
                    $check_code = $logic->check_validate_code($code, $username);
                    if($check_code['status'] != 1){
                        $this->ajaxReturn($check_code);
                    }
                }
            }
            
            $invite = I('invite');
            if(!empty($invite)){
                $invite = get_user_info($invite,2);//根据手机号查找邀请人
                if(empty($invite)){
                    $this->ajaxReturn(['status'=>-1,'msg'=>'推荐人不存在','result'=>'']);
                }
            }else{
                $invite = array();
            }
            if($is_bind_account && session("third_oauth")){ //绑定第三方账号
                $thirdUser = session("third_oauth");
                $head_pic = $thirdUser['head_pic'];
                $data = $logic->reg($username, $password, $password2, 0, $invite ,$nickname , $head_pic);
                //用户注册成功后, 绑定第三方账号
                $userLogic = new UsersLogic();
                $data = $userLogic->oauth_bind_new($data['result']);
            }else{
                $data = $logic->reg($username, $password, $password2,0,$invite);
            }
             
            
            if ($data['status'] != 1) $this->ajaxReturn($data);
            
            //获取公众号openid,并保持到session的user中
            $oauth_users = M('OauthUsers')->where(['user_id'=>$data['result']['user_id'] , 'oauth'=>'weixin' , 'oauth_child'=>'mp'])->find();
            $oauth_users && $data['result']['open_id'] = $oauth_users['open_id'];
            
            session('user', $data['result']);
            setcookie('user_id', $data['result']['user_id'], null, '/');
            setcookie('is_distribut', $data['result']['is_distribut'], null, '/');
            $cartLogic = new CartLogic();
            $cartLogic->setUserId($data['result']['user_id']);
            $cartLogic->doUserLoginHandle();// 用户登录后 需要对购物车 一些操作
            $this->ajaxReturn($data);
            exit;
        }
        
        $this->assign('regis_sms_enable',$reg_sms_enable); // 注册启用短信：
        $this->assign('regis_smtp_enable',$reg_smtp_enable); // 注册启用邮箱：
        $sms_time_out = tpCache('sms.sms_time_out')>0 ? tpCache('sms.sms_time_out') : 120;
        $this->assign('sms_time_out', $sms_time_out); // 手机短信超时时间
        return $this->fetch();
    }

    public function bind_guide(){
        $data = session('third_oauth');
        //没有第三方登录的话就跳到登录页
        if(empty($data)){
            $this->redirect('User/login');
        }
        $first_leader = Cache::get($data['openid']);
        if($first_leader){
            //拿关注传时候过来来的上级id
            setcookie('first_leader',$first_leader);
        }
        $this->assign("nickname", $data['nickname']);
        $this->assign("oauth", $data['oauth']);
        $this->assign("head_pic", $data['head_pic']);
        $this->assign('store_name',tpCache('shop_info.store_name'));
        return $this->fetch();
    }

    /**
     * 绑定已有账号
     * @return \think\mixed
     */
    public function bind_account()
    {
        $mobile = input('mobile/s');
        $verify_code = input('verify_code/s');
        //发送短信验证码
        $logic = new UsersLogic();
        $check_code = $logic->check_validate_code($verify_code, $mobile, 'phone', session_id(), 1);
        if($check_code['status'] != 1){
            $this->ajaxReturn(['status'=>0,'msg'=>$check_code['msg'],'result'=>'']);
        }
        if(empty($mobile) || !check_mobile($mobile)){
            $this->ajaxReturn(['status' => 0, 'msg' => '手机格式错误']);
        }
        $users = Db::name('users')->where('mobile',$mobile)->find();
        if (empty($users)) {
            $this->ajaxReturn(['status' => 0, 'msg' => '账号不存在']);
        }
        $user = new \app\common\logic\User();
        $user->setUserById($users['user_id']);
        $cartLogic = new CartLogic();
        try{
            $user->checkOauthBind();
            $user->oauthBind();
            $user->doLeader();
            $user->refreshCookie();
            $cartLogic->setUserId($users['user_id']);
            $cartLogic->doUserLoginHandle();
            $orderLogic = new OrderLogic();//登录后将超时未支付订单给取消掉
            $orderLogic->setUserId($users['user_id']);
            $orderLogic->abolishOrder();
            $this->ajaxReturn(['status' => 1, 'msg' => '绑定成功']);
        }catch (TpshopException $t){
            $error = $t->getErrorArr();
            $this->ajaxReturn($error);
        }
    }
    /**
     * 先注册再绑定账号
     * @return \think\mixed
     */
    public function bind_reg()
    {
        $mobile = input('mobile/s');
        $verify_code = input('verify_code/s');
        $password = input('password/s');
        $nickname = input('nickname/s', '');
        if(empty($mobile) || !check_mobile($mobile)){
            $this->ajaxReturn(['status' => 0, 'msg' => '手机格式错误']);
        }
        if(empty($password)){
            $this->ajaxReturn(['status' => 0, 'msg' => '请输入密码']);
        }
        $logic = new UsersLogic();
        $check_code = $logic->check_validate_code($verify_code, $mobile, 'phone', session_id(), 1);
        if($check_code['status'] != 1){
            $this->ajaxReturn(['status'=>0,'msg'=>$check_code['msg'],'result'=>'']);
        }
        $thirdUser = session('third_oauth');
        $data = $logic->reg($mobile, $password, $password, 0, [], $nickname, $thirdUser['head_pic']);
        if ($data['status'] != 1) {
            $this->ajaxReturn(['status'=>0,'msg'=>$data['msg'],'result'=>'']);
        }
        $user = new \app\common\logic\User();
        $user->setUserById($data['result']['user_id']);
        try{
            $user->checkOauthBind();
            $user->oauthBind();
            $user->refreshCookie();
            $this->ajaxReturn(['status' => 1, 'msg' => '绑定成功']);
        }catch (TpshopException $t){
            $error = $t->getErrorArr();
            $this->ajaxReturn($error);
        }
    }

    public function ajaxAddressList()
    {
        $UserAddress = new UserAddress();
        $address_list = $UserAddress->where('user_id', $this->user_id)->order('is_default desc')->select();
        if($address_list){
            $address_list = collection($address_list)->append(['address_area'])->toArray();
        }else{
            $address_list = [];
        }
        $this->ajaxReturn($address_list);
    }

    /**
     * 用户地址列表
     */
    public function address_list()
    {
        $address_lists =  db('user_address')->where('user_id', $this->user_id)->select();
        $region_list = db('region')->cache(true)->getField('id,name');
        $this->assign('region_list', $region_list);
        $this->assign('lists', $address_lists);
        return $this->fetch();
    }

    /**
     * 保存地址
     */
    public function addressSave()
    {
        $address_id = input('address_id/d',0);
        $data = input('post.');
        $userAddressValidate = Loader::validate('UserAddress');
        if (!$userAddressValidate->batch()->check($data)) {
            $this->ajaxReturn(['status' => 0, 'msg' => '操作失败', 'result' => $userAddressValidate->getError()]);
        }
        if (!empty($address_id)) {
            //编辑
            $userAddress = UserAddress::get(['address_id'=>$address_id,'user_id'=> $this->user_id]);
            if(empty($userAddress)){
                $this->ajaxReturn(['status' => 0, 'msg' => '参数错误']);
            }
        } else {
            //新增
            $userAddress = new UserAddress();
            $user_address_count = Db::name('user_address')->where("user_id", $this->user_id)->count();
            if ($user_address_count >= 20) {
                $this->ajaxReturn(['status' => 0, 'msg' => '最多只能添加20个收货地址']);
            }
            $data['user_id'] = $this->user_id;
        }
        $userAddress->data($data);
        $userAddress['longitude'] = true;
        $userAddress['latitude'] = true;
        $row = $userAddress->save();
        if ($row !== false) {
            $this->ajaxReturn(['status' => 1, 'msg' => '操作成功', 'result'=>['address_id'=>$userAddress->address_id]]);
        } else {
            $this->ajaxReturn(['status' => 0, 'msg' => '操作失败']);
        }
    }
    /*
         * 添加地址
         */
    public function add_address()
    {
        $source = input('source');
        if (IS_POST) {
            $post_data = input('post.');
            $logic = new UsersLogic();
            $data = $logic->add_address($this->user_id, 0, $post_data);
            if ($data['status'] != 1){
                $this->ajaxReturn($data);
            } else {
                $data['url']= U('/Mobile/User/address_list');
                $this->ajaxReturn($data);
            }
        }
        $p = M('region')->where(array('parent_id' => 0, 'level' => 1))->select();
        $this->assign('province', $p);
        $this->assign('source', $source);
        return $this->fetch();

    }

    /*
     * 地址编辑
     */
    public function edit_address()
    {
        $id = I('id/d');
        $address = M('user_address')->where(array('address_id' => $id, 'user_id' => $this->user_id))->find();
        if (IS_POST) {
            $post_data = input('post.');
            $source = $post_data['source'];
            $logic = new UsersLogic();
            $data = $logic->add_address($this->user_id, $id, $post_data);
            if ($source == 'cart2') {
                $data['url']=U('/Mobile/Cart/cart2', array('address_id' => $data['result'],'goods_id'=>$post_data['goods_id'],'goods_num'=>$post_data['goods_num'],'item_id'=>$post_data['item_id'],'action'=>$post_data['action']));
                $this->ajaxReturn($data);
            } elseif ($source == 'integral') {
                $data['url'] = U('/Mobile/Cart/integral', array('address_id' => $data['result'],'goods_id'=>$post_data['goods_id'],'goods_num'=>$post_data['goods_num'],'item_id'=>$post_data['item_id']));
                $this->ajaxReturn($data);
            } elseif($source == 'pre_sell_cart'){
                $data['url'] = U('/Mobile/Cart/pre_sell_cart', array('address_id' => $data['result'],'act_id'=>$post_data['act_id'],'goods_num'=>$post_data['goods_num']));
                $this->ajaxReturn($data);
            } elseif($source == 'team'){
                $data['url']= U('/Mobile/Team/order', array('address_id' => $data['result'],'order_id'=>$post_data['order_id']));
                $this->ajaxReturn($data);
            } elseif ($_POST['source'] == 'pre_sell') {
                $prom_id = input('prom_id/d');
                $data['url'] = U('/Mobile/Cart/pre_sell', array('address_id' => $data['result'],'goods_num' => $goods_num,'prom_id' => $prom_id));
                $this->ajaxReturn($data);
            } else {
                $data['url']= U('/Mobile/User/address_list');
                $this->ajaxReturn($data);
            }
        }
        //获取省份
        $p = M('region')->where(array('parent_id' => 0, 'level' => 1))->select();
        $c = M('region')->where(array('parent_id' => $address['province'], 'level' => 2))->select();
        $d = M('region')->where(array('parent_id' => $address['city'], 'level' => 3))->select();
        if ($address['twon']) {
            $e = M('region')->where(array('parent_id' => $address['district'], 'level' => 4))->select();
            $this->assign('twon', $e);
        }
        $this->assign('province', $p);
        $this->assign('city', $c);
        $this->assign('district', $d);
        $this->assign('address', $address);
        return $this->fetch();
    }

    /*
     * 设置默认收货地址
     */
    public function set_default()
    {
        $id = I('get.id/d');
        $source = I('get.source');
        M('user_address')->where(array('user_id' => $this->user_id))->save(array('is_default' => 0));
        $row = M('user_address')->where(array('user_id' => $this->user_id, 'address_id' => $id))->save(array('is_default' => 1));
        if ($source == 'cart2') {
            header("Location:" . U('Mobile/Cart/cart2'));
            exit;
        } else {
            header("Location:" . U('Mobile/User/address_list'));
        }
    }

    /*
     * 地址删除
     */
    public function del_address()
    {
        $id = I('get.id/d');

        $address = M('user_address')->where("address_id", $id)->find();
        $row = M('user_address')->where(array('user_id' => $this->user_id, 'address_id' => $id))->delete();
        // 如果删除的是默认收货地址 则要把第一个地址设置为默认收货地址
        if ($address['is_default'] == 1) {
            $address2 = M('user_address')->where("user_id", $this->user_id)->find();
            $address2 && M('user_address')->where("address_id", $address2['address_id'])->save(array('is_default' => 1));
        }
        if (!$row)
            $this->error('操作失败', U('User/address_list'));
        else
            $this->success("操作成功", U('User/address_list'));
    }


    /*
     * 个人信息
     */
    public function userinfo()
    {
        $userLogic = new UsersLogic();
        $user_info = $userLogic->get_info($this->user_id); // 获取用户信息
        $user_info = $user_info['result'];
        if (IS_POST) {
        	if ($_FILES['head_pic']['tmp_name']) {
        		$file = $this->request->file('head_pic');
                $image_upload_limit_size = config('image_upload_limit_size');
        		$validate = ['size'=>$image_upload_limit_size,'ext'=>'jpg,png,gif,jpeg'];
        		$dir = UPLOAD_PATH.'head_pic/';
        		if (!($_exists = file_exists($dir))){
        			$isMk = mkdir($dir);
        		}
        		$parentDir = date('Ymd');
        		$info = $file->validate($validate)->move($dir, true);
        		if($info){
        			$post['head_pic'] = '/'.$dir.$parentDir.'/'.$info->getFilename();
        		}else{
        			$this->error($file->getError());//上传错误提示错误信息
        		}
        	}
            I('post.nickname') ? $post['nickname'] = I('post.nickname') : false; //昵称
            I('post.qq') ? $post['qq'] = I('post.qq') : false;  //QQ号码
            I('post.head_pic') ? $post['head_pic'] = I('post.head_pic') : false; //头像地址
            I('post.sex') ? $post['sex'] = I('post.sex') : $post['sex'] = 0;  // 性别
            I('post.birthday') ? $post['birthday'] = strtotime(I('post.birthday')) : false;  // 生日
            I('post.province') ? $post['province'] = I('post.province') : false;  //省份
            I('post.city') ? $post['city'] = I('post.city') : false;  // 城市
            I('post.district') ? $post['district'] = I('post.district') : false;  //地区
            I('post.email') ? $post['email'] = I('post.email') : false; //邮箱
            I('post.mobile') ? $post['mobile'] = I('post.mobile') : false; //手机

            $email = I('post.email');
            $mobile = I('post.mobile');
            $code = I('post.mobile_code', '');
            $scene = I('post.scene', 6);

            if (!empty($email)) {
                $c = M('users')->where(['email' => input('post.email'), 'user_id' => ['<>', $this->user_id]])->count();
                $c && $this->error("邮箱已被使用");
            }
            if (!empty($mobile)) {
                $c = M('users')->where(['mobile' => input('post.mobile'), 'user_id' => ['<>', $this->user_id]])->count();
                $c && $this->error("手机已被使用");
                if (!$code)
                    $this->error('请输入验证码');
                $check_code = $userLogic->check_validate_code($code, $mobile, 'phone', $this->session_id, $scene);
                if ($check_code['status'] != 1)
                    $this->error($check_code['msg']);
            }

            if (!$userLogic->update_info($this->user_id, $post))
                $this->error("保存失败");
            setcookie('uname',urlencode($post['nickname']),null,'/');
            $this->success("操作成功",U('User/userinfo'));
            exit;
        }
        //  获取省份
        $province = M('region')->where(array('parent_id' => 0, 'level' => 1))->select();
        //  获取订单城市
        $city = M('region')->where(array('parent_id' => $user_info['province'], 'level' => 2))->select();
        //  获取订单地区
        $area = M('region')->where(array('parent_id' => $user_info['city'], 'level' => 3))->select();
        $this->assign('province', $province);
        $this->assign('city', $city);
        $this->assign('area', $area);
        $this->assign('user', $user_info);

        $this->assign('sex', C('SEX'));
        //从哪个修改用户信息页面进来，
        $dispaly = I('action');
        if ($dispaly != '') {
            return $this->fetch("$dispaly");
        }
        return $this->fetch();
    }

    /**
     * 修改绑定手机
     * @return mixed
     */
    public function setMobile(){
        $userLogic = new UsersLogic();
        if (IS_POST) {
            $mobile = input('mobile');
            $mobile_code = input('mobile_code');
            $scene = input('post.scene', 6);
            $validate = I('validate',0);
            $status = I('status',0);
            $c = Db::name('users')->where(['mobile' => $mobile, 'user_id' => ['<>', $this->user_id]])->count();
            $c && $this->error('手机已被使用');
            if (!$mobile_code)
                $this->error('请输入验证码');
            $check_code = $userLogic->check_validate_code($mobile_code, $mobile, 'phone', $this->session_id, $scene);
            if($check_code['status'] !=1){
                $this->error($check_code['msg']);
            }

            if($validate == 1 && $status == 0){
                $res = Db::name('users')->where(['user_id' => $this->user_id])->update(['mobile'=>$mobile,'mobile_validated'=>1]);

                if($res!==false){
                    $source = I('source');
                    !empty($source) && $this->success('绑定成功', U("User/$source"));
                    $this->success('修改成功',U('User/userinfo'));
                }
                $this->error('修改失败');
            }
        }
        $this->assign('status',$status);
        return $this->fetch();
    }

    /*
     * 邮箱验证
     */
    public function email_validate()
    {
        $userLogic = new UsersLogic();
        $user_info = $userLogic->get_info($this->user_id); // 获取用户信息
        $user_info = $user_info['result'];
        $step = I('get.step', 1);
        //验证是否未绑定过
        if ($user_info['email_validated'] == 0)
            $step = 2;
        //原邮箱验证是否通过
        if ($user_info['email_validated'] == 1 && session('email_step1') == 1)
            $step = 2;
        if ($user_info['email_validated'] == 1 && session('email_step1') != 1)
            $step = 1;
        if (IS_POST) {
            $email = I('post.email');
            $code = I('post.code');
            $info = session('email_code');
            if (!$info)
                $this->error('非法操作');
            if ($info['email'] == $email || $info['code'] == $code) {
                if ($user_info['email_validated'] == 0 || session('email_step1') == 1) {
                    session('email_code', null);
                    session('email_step1', null);
                    if (!$userLogic->update_email_mobile($email, $this->user_id))
                        $this->error('邮箱已存在');
                    $this->success('绑定成功', U('Home/User/index'));
                } else {
                    session('email_code', null);
                    session('email_step1', 1);
                    redirect(U('Home/User/email_validate', array('step' => 2)));
                }
                exit;
            }
            $this->error('验证码邮箱不匹配');
        }
        $this->assign('step', $step);
        return $this->fetch();
    }

    /*
    * 手机验证
    */
    public function mobile_validate()
    {
        $userLogic = new UsersLogic();
        $user_info = $userLogic->get_info($this->user_id); // 获取用户信息
        $user_info = $user_info['result'];
        $step = I('get.step', 1);
        //验证是否未绑定过
        if ($user_info['mobile_validated'] == 0)
            $step = 2;
        //原手机验证是否通过
        if ($user_info['mobile_validated'] == 1 && session('mobile_step1') == 1)
            $step = 2;
        if ($user_info['mobile_validated'] == 1 && session('mobile_step1') != 1)
            $step = 1;
        if (IS_POST) {
            $mobile = I('post.mobile');
            $code = I('post.code');
            $info = session('mobile_code');
            if (!$info)
                $this->error('非法操作');
            if ($info['email'] == $mobile || $info['code'] == $code) {
                if ($user_info['email_validated'] == 0 || session('email_step1') == 1) {
                    session('mobile_code', null);
                    session('mobile_step1', null);
                    if (!$userLogic->update_email_mobile($mobile, $this->user_id, 2))
                        $this->error('手机已存在');
                    $this->success('绑定成功', U('Home/User/index'));
                } else {
                    session('mobile_code', null);
                    session('email_step1', 1);
                    redirect(U('Home/User/mobile_validate', array('step' => 2)));
                }
                exit;
            }
            $this->error('验证码手机不匹配');
        }
        $this->assign('step', $step);
        return $this->fetch();
    }

    /**
     * 用户收藏列表
     */
    public function collect_list()
    {
        $userLogic = new UsersLogic();
        $data = $userLogic->get_goods_collect($this->user_id);
        $this->assign('page', $data['show']);// 赋值分页输出
        $this->assign('goods_list', $data['result']);
        if (IS_AJAX) {      //ajax加载更多
            return $this->fetch('ajax_collect_list');
            exit;
        }
        return $this->fetch();
    }

    /*
     *取消收藏
     */
    public function cancel_collect()
    {
        $collect_id = I('collect_id/d');
        $user_id = $this->user_id;
        if (M('goods_collect')->where(['collect_id' => $collect_id, 'user_id' => $user_id])->delete()) {
            $this->success("取消收藏成功", U('User/collect_list'));
        } else {
            $this->error("取消收藏失败", U('User/collect_list'));
        }
    }

    /**
     * 我的留言
     */
    public function message_list()
    {
        C('TOKEN_ON', true);
        if (IS_POST) {
            if(!$this->verifyHandle('message')){
                $this->error('验证码错误', U('User/message_list'));
            };

            $data = I('post.');
            $data['user_id'] = $this->user_id;
            $user = session('user');
            $data['user_name'] = $user['nickname'];
            $data['msg_time'] = time();
            if (M('feedback')->add($data)) {
                $this->success("留言成功", U('User/message_list'));
                exit;
            } else {
                $this->error('留言失败', U('User/message_list'));
                exit;
            }
        }
        $msg_type = array(0 => '留言', 1 => '投诉', 2 => '询问', 3 => '售后', 4 => '求购');
        $count = M('feedback')->where("user_id", $this->user_id)->count();
        $Page = new Page($count, 100);
        $Page->rollPage = 2;
        $message = M('feedback')->where("user_id", $this->user_id)->limit($Page->firstRow . ',' . $Page->listRows)->select();
        $showpage = $Page->show();
        header("Content-type:text/html;charset=utf-8");
        $this->assign('page', $showpage);
        $this->assign('message', $message);
        $this->assign('msg_type', $msg_type);
        return $this->fetch();
    }

    /**账户明细*/
    public function points()
    {
        $type = I('type', 'all');    //获取类型
        $this->assign('type', $type);
        if ($type == 'recharge') {
            //充值明细
            $count = M('recharge')->where("user_id", $this->user_id)->count();
            $Page = new Page($count, 16);
            $account_log = M('recharge')->where("user_id", $this->user_id)->order('order_id desc')->limit($Page->firstRow . ',' . $Page->listRows)->select();
        } else if ($type == 'points') {
            //积分记录明细
            $count = M('account_log')->where(['user_id' => $this->user_id, 'pay_points' => ['<>', 0]])->count();
            $Page = new Page($count, 16);
            $account_log = M('account_log')->where(['user_id' => $this->user_id, 'pay_points' => ['<>', 0]])->order('log_id desc')->limit($Page->firstRow . ',' . $Page->listRows)->select();
        } else {
            //全部
            $count = M('account_log')->where(['user_id' => $this->user_id])->count();
            $Page = new Page($count, 16);
            $account_log = M('account_log')->where(['user_id' => $this->user_id])->order('log_id desc')->limit($Page->firstRow . ',' . $Page->listRows)->select();
        }
        $show = $Page->show();
        $this->assign('account_log', $account_log);
        $this->assign('page', $show);
        $this->assign('listRows', $Page->listRows);
        if ($_GET['is_ajax']) {
            return $this->fetch('ajax_points');
            exit;
        }
        return $this->fetch();
    }

    
    public function points_list()
    {
    	$type = I('type','all');
    	$usersLogic = new UsersLogic;
    	$result = $usersLogic->points($this->user_id, $type);
    
    	$this->assign('type', $type);
    	$showpage = $result['page']->show();
    	$this->assign('account_log', $result['account_log']);
    	$this->assign('page', $showpage);
    	if ($_GET['is_ajax']) {
    		 return $this->fetch('ajax_points');
    	}
    	return $this->fetch();
    }
    
    
    /*
     * 密码修改
     */
    public function password()
    {
        if (IS_POST) {
            $logic = new UsersLogic();
            $data = $logic->get_info($this->user_id);
            $user = $data['result'];
            if ($user['mobile'] == '' && $user['email'] == '')
                $this->ajaxReturn(['status'=>-1,'msg'=>'请先绑定手机或邮箱','url'=>U('/Mobile/User/index')]);
            $userLogic = new UsersLogic();
            $data = $userLogic->password($this->user_id, I('post.old_password'), I('post.new_password'), I('post.confirm_password'));
            if ($data['status'] == -1)
                $this->ajaxReturn(['status'=>-1,'msg'=>$data['msg']]);
            $this->ajaxReturn(['status'=>1,'msg'=>$data['msg'],'url'=>U('/Mobile/User/index')]);
            exit;
        }
        return $this->fetch();
    }

    function forget_pwd()
    {
        if ($this->user_id > 0) {
            $this->redirect("User/index");
        }
        $username = I('username');
        if (IS_POST) {
            if (!empty($username)) {
                if(!$this->verifyHandle('forget')){
                    $this->ajaxReturn(['status'=>-1,'msg'=>"验证码错误"]);
                };
                $field = 'mobile';
                if (check_email($username)) {
                    $field = 'email';
                }
                $user = M('users')->where("email", $username)->whereOr('mobile', $username)->find();
                if ($user) {
                    $sms_status = checkEnableSendSms(2);
                    session('find_password', array('user_id' => $user['user_id'], 'username' => $username,
                        'email' => $user['email'], 'mobile' => $user['mobile'], 'type' => $field,'sms_status'=>$sms_status['status']));
                    $regis_smtp_enable = $this->tpshop_config['smtp_regis_smtp_enable'];
                    if(($field=='mobile' && $this->tpshop_config['sms_forget_pwd_sms_enable']==1)){
                        $this->ajaxReturn(['status'=>1,'msg'=>"用户验证成功",'url'=>U('User/find_pwd')]);
                    }

                    if(($field=='email' && $regis_smtp_enable==0) || ($field=='mobile' && $sms_status['status']<1)){
                        $this->ajaxReturn(['status'=>1,'msg'=>"用户验证成功",'url'=>U('User/set_pwd')]);
                    }
                    exit;
                } else {
                    $this->ajaxReturn(['status'=>-1,'msg'=>"用户名不存在，请检查"]);
                }
            }
        }
        return $this->fetch();
    }

    function find_pwd()
    {
        if ($this->user_id > 0) {
            header("Location: " . U('User/index'));
        }
        $user = session('find_password');
        if (empty($user)) {
            $this->error("请先验证用户名", U('User/forget_pwd'));
        }
        $this->assign('user', $user);
        return $this->fetch();
    }


    public function set_pwd()
    {
        if ($this->user_id > 0) {
            $this->redirect('Mobile/User/index');
        }
        $check = session('validate_code');
        $find_password = session('find_password');
        $field = $find_password['field'];
        $sms_status = session('find_password')['sms_status'];
        $regis_smtp_enable = $this->tpshop_config['smtp_regis_smtp_enable'];
        $is_check_code=false;
        //需要验证邮箱或者手机
        if($field=='email' && $regis_smtp_enable==1)$is_check_code = true;
        if($field=='mobile' && $sms_status['status']==1)$is_check_code = true;
        if ((empty($check) || $check['is_check'] == 0) && $is_check_code) {
            $this->error('验证码还未验证通过',U('User/forget_pwd'));
        }
        if (IS_POST) {
            $data['password'] = $password = I('post.password');
            $data['password2'] = $password2 = I('post.password2');
            $UserRegvalidate = Loader::validate('User');
            if(!$UserRegvalidate->scene('set_pwd')->check($data)){
                $this->error($UserRegvalidate->getError(),U('User/forget_pwd'));
            }
            M('users')->where("user_id", $find_password['user_id'])->save(array('password' => encrypt($password)));
            session('validate_code', null);
            return $this->fetch('reset_pwd_sucess');
        }
        $is_set = I('is_set', 0);
        $this->assign('is_set', $is_set);
        return $this->fetch();
    }

    /**
     * 验证码验证
     * $id 验证码标示
     */
    private function verifyHandle($id)
    {
        $verify = new Verify();
        if (!$verify->check(I('post.verify_code'), $id ? $id : 'user_login')) {
            return false;
        }
        return true;
    }

    /**
     * 验证码获取
     */
    public function verify()
    {
        //验证码类型
        $type = I('get.type') ? I('get.type') : 'user_login';
        $config = array(
            'fontSize' => 30,
            'length' => 4,
            'imageH' =>  60,
            'imageW' =>  300,
            'fontttf' => '5.ttf',
            'useCurve' => false,
            'useNoise' => false,
        );
        $Verify = new Verify($config);
        $Verify->entry($type);
		exit();
    }

    /**
     * 账户管理
     */
    public function accountManage()
    {
        return $this->fetch();
    }

    public function recharge()
    {
        $order_id = I('order_id/d');
        $paymentList = M('Plugin')->where(['type'=>'payment' ,'code'=>['neq','cod'],'status'=>1,'scene'=> ['in','0,1']])->select();
        $paymentList = convert_arr_key($paymentList, 'code');
        //微信浏览器
        if (strstr($_SERVER['HTTP_USER_AGENT'], 'MicroMessenger')) {
            unset($paymentList['weixinH5']);
        }else{
            unset($paymentList['weixin']);
        }
        foreach ($paymentList as $key => $val) {
            $val['config_value'] = unserialize($val['config_value']);
            if ($val['config_value']['is_bank'] == 2) {
                $bankCodeList[$val['code']] = unserialize($val['bank_code']);
            }
        }
        $bank_img = include APP_PATH . 'home/bank.php'; // 银行对应图片
        $this->assign('paymentList', $paymentList);
        $this->assign('bank_img', $bank_img);
        $this->assign('bankCodeList', $bankCodeList);

        // 查找最近一次充值方式
        $recharge_arr = Db::name('Recharge')->field('pay_code')->where('user_id', $this->user_id)
            ->order('order_id desc')->find();
        $alipay = 'alipayMobile'; //默认支付宝支付
        if($recharge_arr){
            foreach ($paymentList as  $key=>$item) {
                if($key == $recharge_arr['pay_code']){
                    $alipay = $recharge_arr['pay_code'];
                }
            }
        }
        $this->assign('alipay', $alipay);

        if ($order_id > 0) {
            $order = M('recharge')->where("order_id", $order_id)->find();
            $this->assign('order', $order);
        }
        return $this->fetch();
    }
    
    public function recharge_list(){
    	$usersLogic = new UsersLogic;
        $result= $usersLogic->get_recharge_log($this->user_id);  //充值记录
    	$this->assign('page', $result['show']);
    	$this->assign('lists', $result['result']);
    	if (I('is_ajax')) {
    		return $this->fetch('ajax_recharge_list');
    	}
    	return $this->fetch();
    }


    public function performance_log(){
    	$DistributLogic = new DistributLogic;
        $result= $DistributLogic->get_recharge_log($this->user_id,'','agent_performance_log');  //业务记录
        // dump($this->user_id);
    	$this->assign('page', $result['show']);
        $this->assign('lists', $result['result']);
        if (I('is_ajax')) {
    		return $this->fetch('ajax_log_list');
    	}
    	return $this->fetch();
    }

    public function commision(){
    	$DistributLogic = new DistributLogic;
        $result= $DistributLogic->get_commision_log($this->user_id);  //佣金明细
        $this->assign('page', $result['show']);
        $this->assign('lists', $result['result']);
        if (I('is_ajax')) {
            return $this->fetch('ajax_commision_list');
        }

        return $this->fetch();
    }

    //团队列表
    public function team_list(){
        $first_leader = I('first_leader');
        if(!$first_leader){
            $first_leader = session('user.user_id');
        }
        //用户信息
        $user = M('users')->field('user_id,nickname,mobile')->where(['user_id'=>$first_leader])->find();
        //下级信息
        $users = M('users')->field('user_id,nickname,mobile')->order('user_id DESC')->where(['first_leader'=>$first_leader])->select();
        
        $this->assign('user', $user);
        $this->assign('lists', $users);

    	return $this->fetch();
    }

    //团队订单列表
    public function order_list(){  
        $user_id = I('user_id');

        // $states['log.states'] = array('in', '101, 102');
        $order = M('order')->field('order_sn, consignee, add_time, total_amount')->where(['user_id'=>$user_id,'pay_status'=>1])
                            ->limit(20)->order('order_id DESC')->select();

        $user = M('users')->field('user_id,nickname,mobile')->where(['user_id'=>$user_id])->find();
        $this->assign('user', $user);
        $this->assign('order', $order);
        return $this->fetch();   
    }

    //下级分销订单
    public function distribut_order()
    {
        $user_id = session('user.user_id');

        // $lower_id = M('users')->where('first_leader', $user_id)->column('user_id');
        // $lower = M('users')->where('first_leader', $user_id)->column('user_id, nickname');
        $data = array(
            'user_id' => $user_id,
            'states' => 102,
            'deleted_at' => 0,
        );

        $divide_order = M('order_divide')->where($data)->group('order_id')
                   ->limit(30)->column('order_id');

        $orders = M('order')->where('order_id', ['in', $divide_order])->order('order_id DESC')
                ->field('user_id, order_id, pay_time')->select();
        $user_ids = array_column($orders, 'user_id');
        $lower = M('users')->where('user_id', ['in', $user_ids])->column('user_id, nickname');

        //添加下级昵称
        foreach($orders as $key => $value){
            $orders[$key]['nickname'] = $lower[$value['user_id']];
        }
    
        $this->assign('user_id', $user_id);
        $this->assign('result', $orders);
        return $this->fetch();  
    }

    //下级分销订单商品信息
    public function distribut_order_detail()
    {
        $order_id = I('order_id');
        $user_id = I('user_id');
        $pay_time = I('pay_time');
        $data = array(
            'divide.order_id' => $order_id,
            'divide.user_id' => $user_id,
            'divide.states' => '102',
        );
        //关联分钱表和订单商品表找分销类型的商品
        $result = M('order_divide')->alias('divide')
                ->join('order_goods goods', 'divide.goods_id = goods.goods_id and divide.order_id = goods.order_id') 
                ->where($data)->field('goods.goods_name, goods.goods_num, goods.goods_price')
                ->group('divide.goods_id')->select();
        
        //商品数量*单价获取总额
        foreach($result as $key => $value){
            $result[$key]['goods_prizce'] = $value['goods_price'] * $value['goods_num'];
            $result[$key]['pay_time'] = $pay_time;
        }
        $this->assign('result', $result);
        return $this->fetch(); 
    }


    //添加、编辑提现支付宝账号
    public function add_card(){
        $user_id=$this->user_id;
        $data=I('post.');
        if($data['type']==0){
            $info['cash_alipay']=$data['card'];
            $info['realname']=$data['cash_name'];
            $info['user_id']=$user_id;
            $res=DB::name('user_extend')->where('user_id='.$user_id)->count();
            if($res){
                $res2=Db::name('user_extend')->where('user_id='.$user_id)->save($info);
            }else{
                $res2=Db::name('user_extend')->add($info);
            }
            $this->ajaxReturn(['status'=>1,'msg'=>'操作成功']);
        }elseif($data['type']==2){
            $info['bank_card_number']=$data['card'];
            $info['user_id']=$user_id;
            $res=DB::name('user_extend')->where('user_id='.$user_id)->count();
            if($res){
                $res2=Db::name('user_extend')->where('user_id='.$user_id)->save($info);
            }else{
                $res2=Db::name('user_extend')->add($info);
            }
            $this->ajaxReturn(['status'=>1,'msg'=>'操作成功']);
            
        }else{
            //防止非支付宝类型的表单提交
            $this->ajaxReturn(['status'=>0,'msg'=>'不支持的提现方式']);
        }

    }



    /**
     * 申请提现记录
     */
    public function withdrawals()
    {
        C('TOKEN_ON', true);
        $cash_open=tpCache('cash.cash_open');
        if($cash_open!=1){
            $this->error('提现功能已关闭,请联系商家');
        }
        if (IS_POST) {
            $cash_open=tpCache('cash.cash_open');
            if($cash_open!=1){
                $this->ajaxReturn(['status'=>0, 'msg'=>'提现功能已关闭,请联系商家']);
            }

            $data = I('post.');
            $data['user_id'] = $this->user_id;
            $data['create_time'] = time();
            $cash = tpCache('cash');

            if(encrypt($data['paypwd']) != $this->user['paypwd']){
                $this->ajaxReturn(['status'=>0, 'msg'=>'支付密码错误']);
            }
            if ($data['money'] > $this->user['user_money']) {
                $this->ajaxReturn(['status'=>0, 'msg'=>"本次提现余额不足"]);
            } 
            if ($data['money'] <= 0) {
                $this->ajaxReturn(['status'=>0, 'msg'=>'提现额度必须大于0']);
            }

            // 统计所有0，1的金额
            //$status = ['in','0,1'];   
            // $status
            $total_money = Db::name('withdrawals')->where(array('user_id' => $this->user_id, 'status' => 0))->sum('money');
            if ($total_money + $data['money'] > $this->user['user_money']) {
                $this->ajaxReturn(['status'=>0, 'msg'=>"您有提现申请待处理，本次提现余额不足"]);
            }

            if ($cash['cash_open'] == 1) {
                $taxfee =  round($data['money'] * $cash['service_ratio'] / 100, 2);
                // 限手续费
                if ($cash['max_service_money'] > 0 && $taxfee > $cash['max_service_money']) {
                    $taxfee = $cash['max_service_money'];
                }
                if ($cash['min_service_money'] > 0 && $taxfee < $cash['min_service_money']) {
                    $taxfee = $cash['min_service_money'];
                }
                if ($taxfee >= $data['money']) {
                    $this->ajaxReturn(['status'=>0, 'msg'=>'提现额度必须大于手续费！']);
                }
                $data['taxfee'] = $taxfee;

                // 每次限最多提现额度
                if ($cash['min_cash'] > 0 && $data['money'] < $cash['min_cash']) {
                    $this->ajaxReturn(['status'=>0, 'msg'=>'每次最少提现额度' . $cash['min_cash']]);
                }
                if ($cash['max_cash'] > 0 && $data['money'] > $cash['max_cash']) {
                    $this->ajaxReturn(['status'=>0, 'msg'=>'每次最多提现额度' . $cash['max_cash']]);
                }

                $status = ['in','0,1,2,3'];
                $create_time = ['gt',strtotime(date("Y-m-d"))];
                // 今天限总额度
                if ($cash['count_cash'] > 0) {
                    $total_money2 = Db::name('withdrawals')->where(array('user_id' => $this->user_id, 'status' => $status, 'create_time' => $create_time))->sum('money');
                    if (($total_money2 + $data['money'] > $cash['count_cash'])) {
                        $total_money = $cash['count_cash'] - $total_money2;
                        if ($total_money <= 0) {
                            $this->ajaxReturn(['status'=>0, 'msg'=>"你今天累计提现额为{$total_money2},金额已超过可提现金额."]);
                        } else {
                            $this->ajaxReturn(['status'=>0, 'msg'=>"你今天累计提现额为{$total_money2}，最多可提现{$total_money}账户余额."]);
                        }
                    }
                }
                // 今天限申请次数
                if ($cash['cash_times'] > 0) {
                    $total_times = Db::name('withdrawals')->where(array('user_id' => $this->user_id, 'status' => $status, 'create_time' => $create_time))->count();
                    if ($total_times >= $cash['cash_times']) {
                        $this->ajaxReturn(['status'=>0, 'msg'=>"今天申请提现的次数已用完."]);
                    }
                }
            }else{
                $data['taxfee'] = 0;
            }

            if (M('withdrawals')->add($data)) {
                
                accountLog($this->user['user_id'], -$data['money'] , 0, '提现扣款',  0, 0, '');

                // 发送公众号消息给用户
                $user = Db::name('OauthUsers')->where(['user_id'=>$this->user['user_id'] ])->find();
                if ($user) {
                    $wx_content = "您的提现申请已提交，正在处理...";
                    $wechat = new \app\common\logic\wechat\WechatUtil();
                    $wechat->sendMsg($user['openid'], 'text', $wx_content);
                }
                
                $this->ajaxReturn(['status'=>1,'msg'=>"已提交申请",'url'=>U('User/account',['type'=>2])]);
            } else {
                $this->ajaxReturn(['status'=>0,'msg'=>'提交失败,联系客服!']);
            }
        }
        $user_extend=Db::name('user_extend')->where('user_id='.$this->user_id)->find();

        //获取用户绑定openId
        $oauthUsers = M("OauthUsers")->where(['user_id'=>$this->user_id, 'oauth'=>'wx'])->find();
        $openid = $oauthUsers['openid'];
        if(empty($oauthUsers)){
            $openid = Db::name('oauth_users')->where(['user_id'=>$this->user_id])->value('openid');
        }

        $this->assign('user_extend',$user_extend);
        $this->assign('cash_config', tpCache('cash'));//提现配置项
        $this->assign('user_money', $this->user['user_money']);    //用户余额
        $this->assign('openid',$openid);    //用户绑定的微信openid
        return $this->fetch();
    }

    //手机端是通过扫码PC端来绑定微信,需要ajax获取一下openID
    public function get_openid(){
        //halt($this->user_id); 22
        $oauthUsers = M("OauthUsers")->where(['user_id'=>$this->user_id])->find();
        $openid = $oauthUsers['openid'];
        if(empty($oauthUsers)){
            $openid = Db::name('users')->where(['user_id'=>$this->user_id])->value('openid');
        }
        if($openid){
            if(strpos($openid, 'oqy') === 0){
                $this->ajaxReturn(['status'=>1,'result'=>$openid]);
            }else{
                $this->ajaxReturn(['status'=>0,'result'=>'']);
            }
        }else{
            $this->ajaxReturn(['status'=>0,'result'=>'']);   
        }
    }

    /**
     * 申请记录列表
     */
    public function withdrawals_list()
    {
        $withdrawals_where['user_id'] = $this->user_id;
        $count = M('withdrawals')->where($withdrawals_where)->count();
        // $pagesize = C('PAGESIZE'); //10条数据，不显示滚动效果
        // $page = new Page($count, $pagesize);
        $page = new Page($count, 15);
        $list = M('withdrawals')->where($withdrawals_where)->order("id desc")->limit("{$page->firstRow},{$page->listRows}")->select();

        $this->assign('page', $page->show());// 赋值分页输出
        $this->assign('list', $list); // 下线
        if (I('is_ajax')) {
            return $this->fetch('ajax_withdrawals_list');
        }
        return $this->fetch();
    }

    /**
     * 我的关注
     * @author lxl
     * @time   2017/1
     */
    public function myfocus()
    {
        return $this->fetch();
    }

    /**
     *  用户消息通知
     * @author yhj
     * @time 2018/07/10
     */
    public function message_notice()
    {
        $message_logic = new Message();
        $message_logic->checkPublicMessage();
        $where = array(
            'user_id' => $this->user_id,
            'deleted' => 0,
            'category' => 0
        );
        $userMessage = new UserMessage();
        $data['message_notice'] = $userMessage->where($where)->LIMIT(1)->order('rec_id desc')->find();

        $where['category'] = 1;
        $data['message_activity'] = $userMessage->where($where)->LIMIT(1)->order('rec_id desc')->find();

        $where['category'] = 2;
        $data['message_logistics'] = $userMessage->where($where)->LIMIT(1)->order('rec_id desc')->find();

        //$where['category'] = 3;
        //$data['message_private'] = $userMessage->where($where)->LIMIT(1)->order('rec_id desc')->find();

        $data['no_read'] = $message_logic->getUserMessageCount();

        // 最近消息，日期，内容
        $this->assign($data);        
        return $this->fetch();
    }


    /**
     * 查看通知消息详情
     */
    public function message_notice_detail()
    {

        $type = I('type', 0);
        // $type==3私信，暂时没有

        $message_logic = new Message();
        $message_logic->checkPublicMessage();

        $where = array(
            'user_id' => $this->user_id,
            'deleted' => 0,
            'category' => $type
        );
        $userMessage = new UserMessage();
        $count = $userMessage->where($where)->count();
        $page = new Page($count, 10);
        //$lists = $userMessage->where($where)->order("rec_id DESC")->limit($page->firstRow . ',' . $page->listRows)->select();

        $rec_id = $userMessage->where( $where)->LIMIT($page->firstRow.','.$page->listRows)->order('rec_id desc')->column('rec_id');
        $lists = $message_logic->sortMessageListBySendTime($rec_id, $type);

        $this->assign('lists', $lists);
        if ($_GET['is_ajax']) {
            return $this->fetch('ajax_message_detail');
        }
        if (empty($lists)) {
            return $this->fetch('user/message_none');
        }
        return $this->fetch();
    }

    /**
     * 通知消息详情
     */
    public function message_notice_info(){
        $message_logic = new Message();
        $message_details = $message_logic->getMessageDetails(I('msg_id'), I('type', 0));
        $this->assign('message_details', $message_details);  
        return $this->fetch();
    }

    /**
     * 浏览记录
     */
    public function visit_log()
    {
        $count = M('goods_visit')->where('user_id', $this->user_id)->count();
        $Page = new Page($count, 20);
        $visit = M('goods_visit')->alias('v')
            ->field('v.visit_id, v.goods_id, v.visittime, g.goods_name, g.shop_price, g.cat_id')
            ->join('__GOODS__ g', 'v.goods_id=g.goods_id')
            ->where('v.user_id', $this->user_id)
            ->order('v.visittime desc')
            ->limit($Page->firstRow, $Page->listRows)
            ->select();

        /* 浏览记录按日期分组 */
        $curyear = date('Y');
        $visit_list = [];
        foreach ($visit as $v) {
            if ($curyear == date('Y', $v['visittime'])) {
                $date = date('m月d日', $v['visittime']);
            } else {
                $date = date('Y年m月d日', $v['visittime']);
            }
            $visit_list[$date][] = $v;
        }

        $this->assign('visit_list', $visit_list);
        if (I('get.is_ajax', 0)) {
            return $this->fetch('ajax_visit_log');
        }
        return $this->fetch();
    }

    /**
     * 删除浏览记录
     */
    public function del_visit_log()
    {
        $visit_ids = I('get.visit_ids', 0);
        $row = M('goods_visit')->where('visit_id','IN', $visit_ids)->delete();

        if(!$row) {
            $this->error('操作失败',U('User/visit_log'));
        } else {
            $this->success("操作成功",U('User/visit_log'));
        }
    }

    /**
     * 清空浏览记录
     */
    public function clear_visit_log()
    {
        $row = M('goods_visit')->where('user_id', $this->user_id)->delete();

        if(!$row) {
            $this->error('操作失败',U('User/visit_log'));
        } else {
            $this->success("操作成功",U('User/visit_log'));
        }
    }

    /**
     * 支付密码
     * @return mixed
     */
    public function paypwd()
    {
        //检查是否第三方登录用户
        $user = M('users')->where('user_id', $this->user_id)->find();
        if ($user['mobile'] == '')
            $this->error('请先绑定手机号',U('User/setMobile',['source'=>'paypwd']));
        $step = I('step', 1);
        if ($step > 1) {
            $check = session('validate_code');
            if (empty($check)) {
                $this->error('验证码还未验证通过', U('mobile/User/paypwd'));
            }
        }
        if (IS_POST && $step == 2) {
            $new_password = trim(I('new_password'));
            $confirm_password = trim(I('confirm_password'));
            $oldpaypwd = trim(I('old_password'));
            //以前设置过就得验证原来密码
            if(!empty($user['paypwd']) && ($user['paypwd'] != encrypt($oldpaypwd))){
                $this->ajaxReturn(['status'=>-1,'msg'=>'原密码验证错误！','result'=>'']);
            }
            $userLogic = new UsersLogic();
            $data = $userLogic->paypwd($this->user_id, $new_password, $confirm_password);
            $this->ajaxReturn($data);
            exit;
        }
        $this->assign('step', $step);
        return $this->fetch();
    }

    /**
     * 重置支付密码
     * @return mixed
     */
    public function paypwd_reset()
    {
        //检查是否第三方登录用户
        $user = M('users')->where('user_id', $this->user_id)->find();
        if ($user['mobile'] == '')
            $this->error('请先绑定手机号',U('User/setMobile',['source'=>'paypwd']));
        $step = I('step', 1);
        if ($step > 1) {
            $check = session('validate_code');
            if (empty($check)) {
                $this->error('验证码还未验证通过', U('mobile/User/paypwd'));
            }
        }
        if (IS_POST && $step == 2) {
            $new_password = trim(I('new_password'));
            $confirm_password = trim(I('confirm_password'));
         
            $userLogic = new UsersLogic();
            $data = $userLogic->paypwd($this->user_id, $new_password, $confirm_password);
            $this->ajaxReturn($data);
            exit;
        }
        $this->assign('step', $step);
        return $this->fetch();
    }


    /**
     * 会员签到积分奖励
     * 2017/9/28
     */
    public function sign()
    {
        $userLogic = new UsersLogic();
        $user_id = $this->user_id;
        $info = $userLogic->idenUserSign($user_id);//标识签到
        $this->assign('info', $info);
        return $this->fetch();
    }

    /**
     * Ajax会员签到
     * 2017/11/19
     */
    public function user_sign()
    {
        $userLogic = new UsersLogic();
        $user_id   = $this->user_id;
        $config    = tpCache('sign');
        $date      = I('date'); //2017-9-29
        //是否正确请求
        (date("Y-n-j", time()) != $date) && $this->ajaxReturn(['status' => false, 'msg' => '签到失败！', 'result' => '']);
        //签到开关
        if ($config['sign_on_off'] > 0) {
            $map['sign_last'] = $date;
            $map['user_id']   = $user_id;
            $userSingInfo     = Db::name('user_sign')->where($map)->find();
            //今天是否已签
            $userSingInfo && $this->ajaxReturn(['status' => false, 'msg' => '您今天已经签过啦！', 'result' => '']);
            //是否有过签到记录
            $checkSign = Db::name('user_sign')->where(['user_id' => $user_id])->find();
            if (!$checkSign) {
                $result = $userLogic->addUserSign($user_id, $date);            //第一次签到
            } else {
                $result = $userLogic->updateUserSign($checkSign, $date);       //累计签到
            }
            $return = ['status' => $result['status'], 'msg' => $result['msg'], 'result' => ''];
        } else {
            $return = ['status' => false, 'msg' => '该功能未开启！', 'result' => ''];
        }
        $this->ajaxReturn($return);
    }


    /**
     * vip充值
     */
    public function rechargevip(){
        $paymentList = M('Plugin')->where("`type`='payment' and code!='cod' and status = 1 and  scene in(0,1)")->select();
        //微信浏览器
        if (strstr($_SERVER['HTTP_USER_AGENT'], 'MicroMessenger')) {
            $paymentList = M('Plugin')->where("`type`='payment' and status = 1 and code='weixin'")->select();
        }
        $paymentList = convert_arr_key($paymentList, 'code');

        foreach ($paymentList as $key => $val) {
            $val['config_value'] = unserialize($val['config_value']);
            if ($val['config_value']['is_bank'] == 2) {
                $bankCodeList[$val['code']] = unserialize($val['bank_code']);
            }
        }
        $bank_img = include APP_PATH . 'home/bank.php'; // 银行对应图片
        $payment = M('Plugin')->where("`type`='payment' and status = 1")->select();
        $this->assign('paymentList', $paymentList);
        $this->assign('bank_img', $bank_img);
        $this->assign('bankCodeList', $bankCodeList);
        return $this->fetch();
    }


    /**
     * 个人海报推广二维码 （我的名片）
     */
    public function qr_code()
    {
        $user_id = $this->user['user_id'];
        if (!$user_id) {
            return $this->fetch();
        }
        //判断是否是分销商
          $user = M('users')->where('user_id', $user_id)->find();
//        if (!$user && $user['is_distribut'] != 1) {
//            return $this->fetch();
//        }

        //判断是否存在海报背景图
        if(!DB::name('poster')->where(['enabled'=>1])->find()){
            echo "<script>alert('请上传海报背景');</script>";
            return $this->fetch();
        }

            //分享数据来源
            $shareLink = urlencode("http://{$_SERVER['HTTP_HOST']}/index.php?m=Mobile&c=Index&a=index&first_leader={$user['user_id']}");

        $head_pic = $user['head_pic'] ?: '';
        if ($head_pic && strpos($head_pic, 'http') !== 0) {
            $head_pic = '.'.$head_pic;
        }

        $this->assign('user',  $user);
        $this->assign('head_pic', $head_pic);
        $this->assign('ShareLink', $shareLink);
        return $this->fetch();
    }

    // 用户海报二维码
    public function poster_qrcode()
    {
        ob_end_clean();
        vendor('topthink.think-image.src.Image');
        vendor('phpqrcode.phpqrcode');

        error_reporting(E_ERROR);
        $url = isset($_GET['data']) ? $_GET['data'] : '';
        $url = urldecode($url);

        $poster = DB::name('poster')->where(['enabled'=>1])->find();
        define('IMGROOT_PATH', str_replace("\\","/",realpath(dirname(dirname(__FILE__)).'/../../'))); //图片根目录（绝对路径）
        $project_path = '/public/images/poster/'.I('_saas_app','all');
        $file_path = IMGROOT_PATH.$project_path;

        if(!is_dir($file_path)){
            mkdir($file_path,777,true);
        }

        $head_pic = input('get.head_pic', '');                   //个人头像
        $back_img = IMGROOT_PATH.$poster['back_url'];            //海报背景
        $valid_date = input('get.valid_date', 0);                //有效时间

        $qr_code_path = UPLOAD_PATH.'qr_code/';
        if (!file_exists($qr_code_path)) {
            mkdir($qr_code_path,777,true);
        }

        /* 生成二维码 */
        $qr_code_file = $qr_code_path.time().rand(1, 10000).'.png';
        \QRcode::png($url, $qr_code_file, QR_ECLEVEL_M,1.8);

        /* 二维码叠加水印 */
        $QR = Image::open($qr_code_file);
        $QR_width = $QR->width();
        $QR_height = $QR->height();

        /* 添加头像 */
        if ($head_pic) {
            //如果是网络头像
            if (strpos($head_pic, 'http') === 0) {
                //下载头像
                $ch = curl_init();
                curl_setopt($ch,CURLOPT_URL, $head_pic);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                $file_content = curl_exec($ch);
                curl_close($ch);
                //保存头像
                if ($file_content) {
                    $head_pic_path = $qr_code_path.time().rand(1, 10000).'.png';
                    file_put_contents($head_pic_path, $file_content);
                    $head_pic = $head_pic_path;
                }
            }
            //如果是本地头像
            if (file_exists($head_pic)) {
                $logo = Image::open($head_pic);
                $logo_width = $logo->height();
                $logo_height = $logo->width();
                $logo_qr_width = $QR_width / 4;
                $scale = $logo_width / $logo_qr_width;
                $logo_qr_height = $logo_height / $scale;
                $logo_file = $qr_code_path.time().rand(1, 10000);
                $logo->thumb($logo_qr_width, $logo_qr_height)->save($logo_file, null, 100);
                $QR = $QR->thumb($QR_width, $QR_height)->water($logo_file, \think\Image::WATER_CENTER);
                $logo_file && unlink($logo_file);
            }
            $head_pic_path && unlink($head_pic_path);
        }

        if ($valid_date && strpos($url, 'weixin.qq.com') !== false) {
            $QR = $QR->text('有效时间 '.$valid_date, "./vendor/topthink/think-captcha/assets/zhttfs/1.ttf", 7, '#00000000', Image::WATER_SOUTH);
        }
        $QR->save($qr_code_file, null, 100);

        $canvas_maxWidth = $poster['canvas_width'];
        $canvas_maxHeight = $poster['canvas_height'];
        $info = getimagesize($back_img);                                                           //取得一个图片信息的数组
        $im = checkPosterImagesType($info,$back_img);                                              //根据图片的格式对应的不同的函数
        $rate_poster_width = $canvas_maxWidth/$info[0];                                            //计算绽放比例
        $rate_poster_height = $canvas_maxHeight/$info[1];
        $maxWidth =  floor($info[0]*$rate_poster_width);
        $maxHeight = floor($info[1]*$rate_poster_height);                                          //计算出缩放后的高度
        $des_im = imagecreatetruecolor($maxWidth,$maxHeight);                                      //创建一个缩放的画布
        imagecopyresized($des_im,$im,0,0,0,0,$maxWidth,$maxHeight,$info[0],$info[1]);              //缩放
        $news_poster = $file_path.'/'.createImagesName() . ".png";                                 //获得缩小后新的二维码路径
        inputPosterImages($info,$des_im,$news_poster);                                             //输出到png即为一个缩放后的文件
        $QR = imagecreatefromstring(file_get_contents($qr_code_file));
        $background_img = imagecreatefromstring ( file_get_contents ( $news_poster ) );

        imagecopyresampled ( $background_img, $QR,$poster['canvas_x'],$poster['canvas_y'],0,0,80,92,80, 78 );      //合成图片
        $result_png = '/'.createImagesName(). ".png";
        $file = $file_path . $result_png;
        imagepng ($background_img, $file);                                                          //输出合成海报图片
        $final_poster = imagecreatefromstring ( file_get_contents (  $file ) );                     //获得该图片资源显示图片
        header("Content-type: image/png");
        imagepng ( $final_poster);
        imagedestroy( $final_poster);
        $news_poster && unlink($news_poster);
        $qr_code_file && unlink($qr_code_file);
        $file && unlink($file);
        exit;
    }

    //区域代理地区选择
    public function regional_agency()
    {
        if(IS_POST){
            $data = I('post.');
            if(isset($data['province'])){
                $area = $data['province'];
            }else if(isset($data['city'])){
                $area = $data['city'];
            }else{
                $area = $data['district'];
            }
            if($area == ''){
                $this->ajaxReturn(['status'=>0, 'msg'=>'地址不能为空']);
            }
            $result = M('user_regional_agency')->where('user_id', $data['user_id'])
                    ->update(['region_id'=>$area, 'is_show'=>1]);
            if($result){
                $agent = M('user_regional_agency')->where('user_id', $data['user_id'])->find();
                $temp = array(
                    'user_id' => $data['user_id'],
                    'agency_level' => $agent['agency_level'], 
                    'region_id' => 0,
                );
                M('user_regional_agency_log')->where($temp)->update(['region_id'=>$area]);
                $this->ajaxReturn(['status'=>1, 'msg'=>'保存成功']);
            }else{
                $this->ajaxReturn(['status'=>0, 'msg'=>'保存失败']);
            }
        }
        $user_id = session('user.user_id');
        $desc = array(
            '1' => '区县代理： (团队业绩达500万， 除开最大代理， 其他业绩大于200万)可以自选一个区县， 享受地区单5%分红， 先到先得, 领完为止。',
            '2' => '地级市代理： (团队业绩达2000万， 除开最大代理， 其他业绩大于1000万)可以自选一个地级市， 享受地区单3%分红。',
            '3' => '省代： (团队业绩达8000万， 除开最大代理， 其他业绩大于3000万)可以自选一个省， 享受地区单2%分红。',
        );
        $result = M('user_regional_agency')->where('user_id', $user_id)->find();
        $this->assign('result', $result);
        $this->assign('desc', $desc[$result['agency_level']]);
        return $this->fetch();
    }

}
