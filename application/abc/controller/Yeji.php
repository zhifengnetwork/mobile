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
        $where = [];
        if($before){
            $where['order_id'] = $before;
        }
        if($after){
            $where['order_id'] = $after;
        }
        if($before && $after){
            $where['order_id'] = array('between',[$before,$after]);
        }

        $all_order = M('order')->where(['pay_status'=>1])->where($where)->order('order_id asc')->field('order_id')->select();
        //dump($all_order);
        if(!empty($all_order)) {
            foreach ($all_order as $k => $v) {
                //dump($v['user_id']);
                jichadaili($v['order_id']);
                //业绩（包含个人+团队）
            }
        }
    }

}