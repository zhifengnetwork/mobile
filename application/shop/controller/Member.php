<?php
/**
 * 用户 的 PHP 后端
 */
// namespace app\mobile\controller;
namespace app\shop\controller;

use think\Db;
use app\common\model\WxNews;
 
class Member extends MobileBase
{
     
    /**
     * 用户首页
     */
    public function index()
    {
       
        return $this->fetch();
    }



    /**
     * 个人资料
     */
    public function userinfo()
    {
       
        return $this->fetch();
    }
    


    /**
     * 优惠券
     */
    public function coupon()
    {
       
        return $this->fetch();
    }
  
    
}