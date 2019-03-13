<?php

namespace app\seller\controller; 

use think\AjaxPage;
use think\Controller;
use think\Url;
use think\Config;
use think\Page;
use think\Verify;
use app\common\logic\MessageFactory;
use think\Db;
class Index extends Base {

    public function index(){
		// dump(session(''));
		$this->assign('menu',getMenuArr());//首页导航查询
		
		
        return $this->fetch();
    }
	public function welcome(){
	    	// $this->assign('sys_info',$this->get_sys_info());
	//    	$today = strtotime("-1 day");
	    	$today = strtotime(date("Y-m-d"));
	    	$count['handle_order'] = M('order')->where("order_status=0 and (pay_status=1 or pay_code='cod')")->count();//待处理订单
	    	$count['new_order'] = M('order')->where("add_time>=$today")->count();//今天新增订单
	    	$count['goods'] =  M('goods')->where("1=1")->count();//商品总数
	    	$count['article'] =  M('article')->where("1=1")->count();//文章总数
	    	$count['users'] = M('users')->where("1=1")->count();//会员总数
	    	$count['today_login'] = M('users')->where("last_login>=$today")->count();//今日访问
	    	$count['new_users'] = M('users')->where("reg_time>=$today")->count();//新增会员
	    	$count['comment'] = M('comment')->where("is_show=0")->count();//最新评论
	    	$this->assign('count',$count);
	        return $this->fetch();
	    }

}