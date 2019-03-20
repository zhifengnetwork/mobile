<?php

namespace app\home\controller;

use app\common\logic\CronLogic;
use think\Db;

class Test {
    //团队总人数
    public function index(){
        global $is_finish;
        $is_finish = false;
        set_time_limit(0);

        //找出总人数为空的记录
        $leader = M('users')->where('underling_number_flag', 0)->field('user_id, first_leader')->find();
        
        if($leader['first_leader'] > 0){
            $this->addOne($leader['first_leader']);
        }else{
            M('users')->where('user_id', $leader['user_id'])->update(['underling_number_flag'=>1]);
        }
        
        //完成给所有上级加一则改为总人数不为空
        if($is_finish){
            M('users')->where('user_id', $leader['user_id'])->update(['underling_number_flag'=>1]);
        }
    }

    //团队所有上级的总人数加一
    public function addOne($leader_id)
    {
        global $is_finish;
        M('users')->where('user_id', $leader_id)->setInc('underling_number');
        $top_leader = M('users')->where('user_id', $leader_id)->value('first_leader');
        if((int)$top_leader > 0){
            $this->addOne($top_leader);
        }else{
            $is_finish = true;
        }
    }



}