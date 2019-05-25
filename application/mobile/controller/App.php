<?php

namespace app\mobile\controller;

use think\Db;
use app\common\model\WxNews;
 
class App extends MobileBase
{
    /**
     * 下载
     */
    public function index()
    {
        
        return $this->fetch();
    }

    
    
}