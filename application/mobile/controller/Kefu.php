<?php

namespace app\mobile\controller;

use think\Controller;
use think\Db;

class Kefu extends Controller {
	
	public function index(){
		$url = 'https://web.jiaxincloud.com/big.html?id=dddznwwzndhidw&appName=nbcs529&appChannel=20001';
		header("Location.".$url);
	}
	
	
}