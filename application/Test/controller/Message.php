<?php
/**
 * 测试
 */
namespace app\test\controller;


use think\Db;
use think\Controller;


class Message extends Controller
{

    public function index(){


        $data = M('wx_message')->limit(10)->select();


        

    }

}