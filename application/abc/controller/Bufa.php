<?php
namespace app\abc\controller;

use think\Db;
use think\Controller;
use app\common\logic\DistributLogic;

class Bufa extends Controller
{
    /**
     * 补发
     */
    public function index(){

        exit;
        
        $con['pay_status'] = 1;
        $con['total_amount'] = array('gt',9.99);

        $con['order_id'] = array('between',[1750,1800]);

        $data = M('order')->where($con)->field('order_id')->select();

        dump($data);

        $logic = new DistributLogic();

        foreach($data as $k => $v){

            $logic->bufa($v['order_id']);

        }

    }

}