<?php

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
      
        $url = '';
        $this->assign('url',$url);
        return $this->fetch();
    }

 
    
}