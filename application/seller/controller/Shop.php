<?php
namespace app\seller\controller;

use app\common\logic\saas\AppLogic;
use think\AjaxPage;

class Shop extends Base
{

  
    public function index()
    {
      $this->redirect('index/index');
    }

   
}