<?php

namespace app\home\controller;

use app\common\logic\AddOneLogic;
use think\Db;

class Test {


    /**
     * 100 个
     */
    public function big(){
        set_time_limit(0);
        $addOneLogic = new AddOneLogic();


        $leader = M('users')->where('underling_number_flag', 0)->order('user_id desc')->field('user_id, first_leader')->limit(100)->select();

        foreach($leader as $k => $v){

            if($v['first_leader'] > 0){
                $flag = $addOneLogic->team_total($v['first_leader']);
               
                //if($flag){
                    $flag_update = M('users')->where('user_id', $v['user_id'])->update(['underling_number_flag'=>1]);
                    echo $flag_update.'完成:'.$v['user_id'];
                //}
            }else{
                $flag_update = M('users')->where('user_id', $v['user_id'])->update(['underling_number_flag'=>1]);
                echo $flag_update.'完成:'.$v['user_id'];
            }

        }
    }




    //团队总人数
    public function index(){
        set_time_limit(0);
        $addOneLogic = new AddOneLogic();

        //找出总人数为空的记录
        $leader = M('users')->where('underling_number_flag', 0)->order('user_id desc')->field('user_id, first_leader')->find();
        dump($leader);
        //有上级则将所有上级的团队总人数加一
        if($leader['first_leader'] > 0){
            $flag = $addOneLogic->team_total($leader['first_leader']);
            dump($flag);
            //if($flag){
                $flag_update = M('users')->where('user_id', $leader['user_id'])->update(['underling_number_flag'=>1]);
                return $flag_update.'完成:'.$leader['user_id'];
            //}
        }else{
            $flag_update = M('users')->where('user_id', $leader['user_id'])->update(['underling_number_flag'=>1]);
            return $flag_update.'完成:'.$leader['user_id'];
        }
        dump($flag_update);
    }


    /**
     * 处理 级别
     */
    public function update(){

    
        $user = M('agent_info')->limit(100)->field('uid,level_id')->order('uid desc')->where(['flag'=>0])->select();

        foreach($user as $k => $v){
           
            $level_id = M('users')->where(['user_id'=>$v['uid']])->value('agent_user');

            if($level_id != $v['level_id']){
                dump($v['uid']);
                M('agent_info')->where(['uid'=>$v['uid']])->update(['level_id'=>$level_id,'flag'=>1]);
            }else{
                dump('不用修改');
                M('agent_info')->where(['uid'=>$v['uid']])->update(['flag'=>1]);
            }
        }
    }

}