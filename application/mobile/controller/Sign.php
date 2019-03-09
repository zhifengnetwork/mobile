<?php
/**
 * 签到
 */
namespace app\mobile\controller;

use think\Db;
use think\Page;
use app\common\logic\ActivityLogic;

class Sign extends MobileBase {


    /*
    * 初始化操作
    */
    public function _initialize()
    {
        parent::_initialize();
        if (!session('user')) {
            header("location:" . U('Mobile/User/login'));
            exit;
        }
    }


    public function index(){     
        
        $user_id = session('user.user_id');
        $this->assign('user_id',$user_id);

        return $this->fetch();
    }

    public function integral(){     
        
        

        return $this->fetch();
    }
  

    /**
     * 判断今天是否签到
     */
    // public function check_sign(){
    //     $user_id = I('user_id');
    //     if(!$user_id){
    //         return $this->ajaxReturn(['status'=>-1,'msg'=>'user_id不能为空']);
    //     }
        
    //     $con['sign_day'] = array('like',date('Y-m-d',time()).'%');
    //     $cunzai = M('sign_log')->where(['user_id'=>$user_id])->where($con)->find();

    //     if($cunzai){
    //         return $this->ajaxReturn(['status'=>1,'msg'=>'今日已签到','data' => true]);
    //     }else{
    //         return $this->ajaxReturn(['status'=>-1,'msg'=>'今日未签到','data' => false]);

    //     }

    // }

    /**
     * 签到
     */
    public function sign(){
        $user_id = I('user_id');
        if(!$user_id){
            return $this->ajaxReturn(['status'=>-1,'msg'=>'签到user_id不能为空']);
        }

        $con['sign_day'] = array('like',date('Y-m-d',time()).'%');
        $cunzai = M('sign_log')->where(['user_id'=>$user_id])->where($con)->find();

        $date = $this->deal_time(date('Y-m-d H:i:s',time()));

        if($cunzai){
            return $this->ajaxReturn(['status'=>1,'msg'=>'今日已签到','date'=>$date]);
        }else{
            $r = M('sign_log')->save(['user_id'=>$user_id,'sign_day'=>date('Y-m-d H:i:s')]);
            if($r){
                if($r){
                    return $this->ajaxReturn(['status'=>1,'msg'=>'签到成功','date'=>$date]);
                }else{
                    return $this->ajaxReturn(['status'=>-1,'msg'=>'签到失败','date'=>$date]);
                }
            }
        }
    }


    /**
     * 获取签到的日期列表
     */
    public function get_sign_day(){
        $user_id = I('user_id');
        if(!$user_id){
            return $this->ajaxReturn(['status'=>-1,'msg'=>'user_id不能为空','data'=>'']);
        }
        $list = M('sign_log')->where(['user_id'=>$user_id])->field('sign_day')->select();
        foreach($list as $k => $v){
            $data[$k] = $this->deal_time($v['sign_day']);
        }


        $con['sign_day'] = array('like',date('Y-m-d',time()).'%');
        $cunzai = M('sign_log')->where(['user_id'=>$user_id])->where($con)->find();

        if($cunzai){
            $today_sign = true;
        }else{
            $today_sign = false;
        }

        //当前积分
        $points = M('users')->where(['user_id'=>$user_id])->value('pay_points');
        $continue_sign = $this->continue_sign($user_id);
        //签到积分
        $add_point = M('config')->where(['name'=>'sign_integral'])->value('value');

        //签到规则
        //连续签到几天	        
        $rule = M('config')->where(['name'=>'sign_rule'])->value('value');

        //连续签到几天
        $accumulate_day = count($data);

        return $this->ajaxReturn(
            ['status'=>1,
            'msg'=>'获取成功',
            'data'=>$data,
            'today_sign'=>$today_sign,
            'points'=>$points,
            'add_point'=>$add_point,
            'continue_sign'=> $continue_sign,
            'accumulate_day'=>$accumulate_day,
            'note'=>$rule
            ]);
    }

    /**
     * 处理时间
     */
    private function deal_time($time){
       $time = strtotime("$time -1 month") ;
        //前端要求  减去 1个月
        $time = date('Y-m-d', $time);
        return str_replace('-','/',$time);
    }

    /**
     * 连续签到次数
     */
    private function continue_sign($user_id){

        //定义时间戳
        date_default_timezone_set("Asia/Shanghai");
        //先看一下今天有没有签到
        $con['sign_day'] = array('like',date('Y-m-d',time()).'%');
        $cunzai = M('sign_log')->where(['user_id'=>$user_id])->where($con)->find();
        if($cunzai){
            $todaySign=1;
        }else{
            $todaySign=0;
        }
        //再看之前的签到时间
        $list = M('sign_log')->where(['user_id'=>$user_id])->order('sign_day desc')->field('sign_day')->select();
        //对所有的签到时间进行时间戳然后倒序排序
        $array=array();
        foreach($list as $key=>$value){
            $array[]=strtotime($value['sign_day']);
        }

         //定义连续签到次数
         $countSign=$todaySign;
         //依次判断所有的时间戳是否在指定范围内，例如第一个应该在昨天00:00:00-23:59:59之前，如果在则$countSign+1,否则跳出循环
         //定义昨天的时间戳范围
         $begintime=strtotime(date('Y-m-d 00:00:00',time()-86400));
         $endtime=strtotime(date('Y-m-d 23:59:59',time()-86400));
         if($todaySign==1){
             for($i=1;$i<count($array);){
            //                echo $begintime."------".$array[$i]."---------".$endtime."+++++";
                 if($array[$i]>=$begintime && $array[$i]<=$endtime){
                     $countSign++;
                     $begintime-=86400;
                     $endtime-=86400;
                 }else{
                     break;
                 }
                 $i++;
             }
         }else{
             for($k=0;$k<count($array);){
                 if($array[$k]>=$begintime && $array[$k]<=$endtime){
                     $countSign++;
                     $begintime-=86400;
                     $endtime-=86400;
                 }else{
                     break;
                 }
                 $k++;
             }
         }

        return $countSign;
    }
}