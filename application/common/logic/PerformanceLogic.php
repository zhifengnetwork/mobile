<?php
namespace app\common\logic;

use think\Db;

/**
 * PerformanceLogic.
 */
class PerformanceLogic
{
  
    /**
     * 个人的业绩
     */
    public function person_yeji($user_id){
    
        $order = M('order')->where(['user_id'=>$user_id,'pay_status'=>1])->field('user_money,order_amount')->select();
        $total = 0;
        foreach($order as $k => $v){
        $total += (float)$v['user_money'] + (float)$v['order_amount'];
        }
        return $total;
    }
    
    /**
     * 一个人旗下  团队的  最大  的  那个业绩
     */
    public function tuandui_max_yeji($user_id){
        
        $user = M('users')->where(['first_leader'=>$user_id])->column('user_id');
        
        $yeji = M('agent_performance')->where('user_id', ['in', $user])->column('agent_per');
        rsort($yeji);

        $res = $yeji[0];
        $res = $res == 0 ? 0 : $res;

        return $res;
    }
    
}