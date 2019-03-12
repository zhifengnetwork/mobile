<?php

/*
 精选地区馆
 And
 站内信息
*/
// namespace app\mobile\controller;
namespace app\shop\controller;

use think\Db;
 
class Handpick extends MobileBase
{
   
    public function index()
    {
       
        return $this->fetch();
    }

    public function msg()
    {
      
        return $this->fetch();
    }
   
    
}