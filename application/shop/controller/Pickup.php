<?php

// namespace app\mobile\controller;
namespace app\shop\controller;

use think\Db;
use app\common\model\WxNews;
 
class Pickup extends MobileBase
{
    /**
     * 二维码
     */
    public function qrcode()
    {
      
        $url = 'http://shop.zhifengwangluo.com.c3w.cc/mobile';
        $this->assign('url',$url);
        return $this->fetch();
    }

 
    
}