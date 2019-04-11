<?php
namespace app\common\logic;

use think\Db;
use think\Page;
use think\Session;
use think\Cache;
use app\common\logic\PerformanceLogic;

/**
 * 地区代理
 * 
 * 7. 区县代理
 * 
 * 
 * 8.地级市代理
 * 
 * 
 * 9.省代
 * 
 * 
 *  用户区域代理表：tp_user_regional_agency 这个表记录用户是 什么级别，哪个地区
 * 
 *  
 */

class RegionalAgencyLogic
{

    /**
     * 传入级别ID，获取 升级配置信息
     */
    public function get_config($level_id){
        $res = M('config_regional_agency')->where(['agency_level'=>$level_id])->find();
        return $res;
    }


    /**
     * 升级 并 返回 当前信息
     * 
     * 返回的 级别
     * 
     */
    public function upgrade(){
        $user_id = session('user.user_id');
        if(!$user_id){
            return ['status'=>0,'msg'=>'user_id不能为空'];
        }

        //统计业绩
        $per_logic =  new PerformanceLogic();
		$money_total = $per_logic->distribut_caculate();

        // array(4) {
        //     ["money_total"] => float(2019550.7)
        //     ["max_moneys"] => float(1780686)
        //     ["moneys"] => float(238864.7)
        //     ["oldPerformance"] => float(1510155.3)
        //   }
        //得到该用户的 目前条件
        
        $level = M('config_regional_agency')->order('agency_level DESC')->select();

        $can_level_id = 0;
        foreach($level as $k => $v){
            if( $money_total['money_total'] >= $v['team_sum'] && $money_total['moneys'] >= $v['other_sum'] ){
                $can_level_id = $v['agency_level'];
            }
        }
        //能升级的id
        if($can_level_id == 0){
            return ['status'=>0,'msg'=>'没有达到升级条件'];
        }

        //循环取名字
        foreach($level as $k => $v){
            if( $can_level_id == $v['agency_level'] ){
              
                $can_level_name = $v['agency_name'];
            }
        }
        //大于等于1

        //找出目前的 等级
        $now = M('user_regional_agency')->where(['user_id'=>$user_id])->find();
        if(!$now){
            //增加
            $data = [
                'user_id'=>$user_id,
                'agency_level'=>$can_level_id,
                'region_id'=>0
            ];
            M('user_regional_agency')->add($data);
           
            //写log
            $logdata = [
                'user_id'=>$user_id,
                'agency_level'=>$can_level_id,
                'region_id'=>0,
                'des'=>'成为'.$can_level_name.'级代理'
            ];
            M('user_regional_agency_log')->add($logdata);
        }

        $now_level_id = $now['agency_level'];
        //如果存在
        
        if($now_level_id < $can_level_id && $now_level_id != 0){
            //可以升级
            $update = [
                'agency_level'=>$can_level_id,
                'is_show'=>0
            ];
            M('user_regional_agency')->where(['user_id'=>$user_id])->update($update);
             //写log
             $logdata = [
                'user_id'=>$user_id,
                'agency_level'=>$can_level_id,
                'region_id'=>$now['region_id'],
                'des'=>'升级'.$can_level_name.'级代理'
            ];
            M('user_regional_agency_log')->add($logdata);
        }
      
        if($now['region_id'] == 0){
            return ['status'=>1,'msg'=>'升级到'.$can_level_name.'级别，地区为空'];
        }else{
            return ['status'=>2,'msg'=>'升级到'.$can_level_name.'级别，已选择地区'];
        }
    }
}