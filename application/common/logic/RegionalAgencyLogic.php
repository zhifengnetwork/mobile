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
     * 分钱的
     * 
     * 支付后触发
     * 订单详情触发
     */
    public function fenqian($order_id){

        //判断是否支付


        //提取 地区的 省市区 3个数字

        //根据log防止重复 user_id  order_id

        //先分区，看 这个区  有没有人

        //分市，看 这个市  有没有人

        //分省，看 这个省  有没有人

        //log

        //accountLog 加钱


        //判断区域代理设置和分红设置是否开启
        $config = M('config')->where('name', ['in',['is_valid', 'is_divide']])
                ->column('name, value');
        if(!$config['is_valid'] || !$config['is_divide']){
            return false;
        }
        
        //判断是否已支付,是否为普通订单
        $data = array(
            'pay_status' => 1,
            'prom_type'  => 0,
            'order_id'   => $order_id,
        );
        $fir = 'order_id, order_amount, user_money, district, city, province';
        $order = M('order')->field($fir)->where($data)->find();
        if(!$order){
            return false;
        }

        //等于小于9.9的订单不分钱
        $total = bcadd($order['order_amount'], $order['user_money'], 2);
        if($total <= 9.9){
            return false;
        }


        //找区代理
        $district = $this->get_agent($order['district']);
        if($district){
            foreach($district as $k => $v){
                //防重复
                if(!$this->is_divide($order['order_id'], $v['user_id'])){
                    continue;
                }
                //分钱
                $result = $this->divide($order['order_id'], $v);
            }
        }
        
        //找市代理
        $city = $this->get_agent($order['city']);
        if($city){
            foreach($city as $k => $v){
                //防重复
                if(!$this->is_divide($order['order_id'], $v['user_id'])){
                    continue;
                }
                //分钱
                $result = $this->divide($order['order_id'], $v);
            }
        }
        
        //找省代理
        $province = $this->get_agent($order['province']);
        if($province){
            foreach($province as $k => $v){
                //防重复
                if(!$this->is_divide($order['order_id'], $v['user_id'])){
                    continue;
                }
                //分钱
                $result = $this->divide($order['order_id'], $v);
            }
        }
    }

    /**
     * 分钱
     */
    public function divide($order_id, $agent)
    {
        //分钱比例
        $config = M('config_regional_agency')->where('agency_level', $agent['agency_level'])
                ->find();
        
        //订单总价
        $order = M('order')->where('order_id', $order_id)
                ->field('order_amount, user_money, shipping_price, order_id, order_sn')
                ->find();
        $price = bcadd((float)$order['order_amount'], (float)$order['user_money'], 2);
        $price = bcsub($price, (float)$order['shipping_price'], 2);
        if($price <= 0){
            return false;
        }
        $rate  = bcdiv($config['rate'], 100, 2);
        $money = bcmul($price, $rate, 2);
        $desc  = $config['agency_name'] . '佣金';

        //分钱写记录
        $result = accountLog($agent['user_id'], +$money, 0, $desc, 0, $order['order_id'], $order['order_sn']);
        $data_log = array(
            'user_id'    => $agent['user_id'],
            'region_id'  => $agent['region_id'],
            'states'     => $agent['agency_level'],
            'order_id'   => $order['order_id'], 
            'user_money' => $money,
            'add_time'   => time(),
            'desc'       => $desc,
        );
        if($result){
            $flag = M('user_regional_divide_log')->insert($data_log);
            return true;
        }else{
            return false;
        }

    }

    /**
     * 防重复
     */
    public function is_divide($order_id, $user_id)
    {
        $data = array(
            'order_id' => $order_id,
            'user_id' =>$user_id,
        );
        $log = M('user_regional_divide_log')
                ->where($data)->find();
        if($log){
            return false;
        }else{
            return true;
        }
    }

    /**
     * 判断省市区是否有人
     */
    public function get_agent($region_id)
    {
        $agent = M('user_regional_agency')->where('region_id',$region_id)
                ->select();
        if($agent){
            return $agent;
        }else{
            return false;
        }
    }

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

        // $config = M('config')->where('name', 'is_valid')->value('value');
        // if(!$config){
        //     return ['status'=>0,'msg'=>'代理区域设置没有开启'];
        // }

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
                break;
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
                'region_id'=>0,//$now['region_id'],
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