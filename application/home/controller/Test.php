<?php

namespace app\home\controller;

use app\common\logic\AddOneLogic;
use think\Db;

class Test {
    //团队总人数
    public function index(){
        set_time_limit(0);
        $addOneLogic = new AddOneLogic();

        //找出总人数为空的记录
        $leader = M('users')->where('underling_number_flag', 0)->field('user_id, first_leader')->find();
        dump($leader);
        //有上级则将所有上级的团队总人数加一
        if($leader['first_leader'] > 0){
            $flag = $addOneLogic->team_total($leader['first_leader']);
            if($flag){
                $flag_update = M('users')->where('user_id', $leader['user_id'])->update(['underling_number_flag'=>1]);
                return $flag_update.'完成:'.$leader['user_id'];
            }
        }else{
            $flag_update = M('users')->where('user_id', $leader['user_id'])->update(['underling_number_flag'=>1]);
            return $flag_update.'完成:'.$leader['user_id'];
        }
        dump($flag_update);
    }
}