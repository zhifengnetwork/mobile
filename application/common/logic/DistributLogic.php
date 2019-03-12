<?php


namespace app\common\logic;

use think\Db;
use think\Page;
use think\Session;

class DistributLogic
{

    /**
     * 用户充值记录
     * $author lxl 2017-4-26
     * @param $user_id 用户ID
     * @param int $pay_status 充值状态0:待支付 1:充值成功 2:交易关闭
     *  @param $table 指定查询那张表
     * @return mixed
     */
    public function get_recharge_log($user_id,$pay_status=0,$table='recharge'){
        $recharge_log_where = ['user_id'=>$user_id];
        if($pay_status){
            $pay_status['status']=$pay_status;
        }
        if($table='agent_performance_log'){
            $count = M('agent_performance_log')->where($recharge_log_where)->count();
            $Page = new Page($count, 15);
            $recharge_log = M('agent_performance_log')->where($recharge_log_where)
                ->limit($Page->firstRow . ',' . $Page->listRows)
                ->select(); 
        }else{
            $count = M('recharge')->where($recharge_log_where)->count();
            $Page = new Page($count, 15);
            $recharge_log = M('recharge')->where($recharge_log_where)
                ->order('order_id desc')
                ->limit($Page->firstRow . ',' . $Page->listRows)
                ->select(); 
        }

        $return = [
            'status'    =>1,
            'msg'       =>'',
            'result'    =>$recharge_log,
            'show'      =>$Page->show()
        ];
        return $return;
    }    
     /*
     * 获取佣金明细
     */
    public function get_commision_log($user_id,$pay_status=0){
        $recharge_log_where = ['user_id'=>$user_id];
        if($pay_status){
            $pay_status['status']=$pay_status;
        }
        $count = M('account_log')->where($recharge_log_where)->count();
        $Page = new Page($count, 15);
        $recharge_log = M('account_log')->where($recharge_log_where)
            ->limit($Page->firstRow . ',' . $Page->listRows)
            ->select(); 
            // dump($recharge_log);
        $return = [
            'status'    =>1,
            'msg'       =>'',
            'result'    =>$recharge_log,
            'show'      =>$Page->show()
        ];
        return $return;
    }

    /*
     * 获取团队列表
     */
    public function get_team_list($user_id){
        // 查询上级id是否等于用户id
        $count = M('users')->where('first_leader|second_leader|third_leader',$user_id)->count();
        // 分页
        $Page = new Page($count, 15);
        // 下级
        $next = M('users')->where('first_leader|second_leader|third_leader',$user_id)
            ->limit($Page->firstRow . ',' . $Page->listRows)
            ->select(); 
        // dump($next);
        $return = [
            'status'    =>1,
            'msg'       =>'',
            'result'    =>$next,
            'show'      =>$Page->show()
        ];
        return $return;
    }

    public function auto_confirm(){
        return null;
    }
}






