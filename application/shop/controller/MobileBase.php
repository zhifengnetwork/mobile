<?php
// namespace app\mobile\controller;
namespace app\shop\controller;

use think\Controller;
use think\Db;
use app\common\logic\CartLogic;
use app\common\logic\UsersLogic;
use app\common\logic\wechat\WechatUtil;

class MobileBase extends Controller {
    public $session_id;
    public $weixin_config;
    public $cateTrre = array();
    public $tpshop_config = array();
    /*
     * 初始化操作
     */
    public function _initialize() {
        session('user'); //不用这个在忘记密码不能获取session('validate_code');
//        Session::start();
        header("Cache-control: private");  // history.back返回后输入框值丢失问题 参考文章 http://www.tp-shop.cn/article_id_1465.html  http://blog.csdn.net/qinchaoguang123456/article/details/29852881
        $this->session_id = session_id(); // 当前的 session_id
        define('SESSION_ID',$this->session_id); //将当前的session_id保存为常量，供其它方法调用
        // 判断当前用户是否手机                
        if(isMobile())
            cookie('is_mobile','1',3600); 
        else 
            cookie('is_mobile','0',3600);
        
        //$this->public_assign();
    }
    
    /**
     * 保存公告变量到 smarty中 比如 导航 
     */   
    public function public_assign()
    {
        $first_login = session('first_login');
        $this->assign('first_login', $first_login);
        if (!$first_login && ACTION_NAME == 'login') {
            session('first_login', 1);
        }
       $tp_config = Db::name('config')->cache(true, TPSHOP_CACHE_TIME, 'config')->select();
       foreach($tp_config as $k => $v)
       {
       	  if($v['name'] == 'hot_keywords'){
       	  	 $this->tpshop_config['hot_keywords'] = explode('|', $v['value']);
       	  }
           $this->tpshop_config[$v['inc_type'].'_'.$v['name']] = $v['value'];
       }
       $goods_category_tree = get_goods_category_tree();
       $this->cateTrre = $goods_category_tree;
       $this->assign('goods_category_tree', $goods_category_tree);                     
       $brand_list = M('brand')->cache(true,TPSHOP_CACHE_TIME)->field('id,cat_id,logo,is_hot')->where("cat_id>0")->select();
       $this->assign('brand_list', $brand_list);
       $this->assign('tpshop_config', $this->tpshop_config);
       /** 修复首次进入微商城不显示用户昵称问题 **/
       $user_id = cookie('user_id');
       $uname = cookie('uname');
       if(empty($user_id) && ($users = session('user')) ){
           $user_id = $users['user_id'];
           $uname = $users['nickname'];
       }
       $this->assign('user_id',$user_id);
       $this->assign('uname',$uname);
      
    }      

   
    public function ajaxReturn($data){
        header('Content-Type:application/json; charset=utf-8');
        exit(json_encode($data,JSON_UNESCAPED_UNICODE));
    }

}