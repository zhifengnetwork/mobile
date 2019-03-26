<?php
/**
 * 测试
 */
namespace app\abc\controller;

use think\Db;
use think\Controller;



class Yeji extends Controller
{

    public function index(){

        set_time_limit(0);

        $before = I('before');
        $after = I('after');

        $con['order_id'] = array('between',[$before,$after]);
        $all_order = M('order')->where(['pay_status'=>1])->where($con)->field('order_id')->select();

        dump($all_order);

        foreach($all_order as $k => $v){

            agent_performance($v['order_id']);
            //业绩（包含个人+团队）

        }
       
    }

}