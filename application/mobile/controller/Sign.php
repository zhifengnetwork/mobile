<?php
/**
 * 签到
 */
namespace app\mobile\controller;

use think\Db;
use think\Page;
use app\common\logic\ActivityLogic;

class Sign extends MobileBase {

    public $user_id = 0;
    public $user = array();

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
        
        $user = session('user');
        session('user', $user);  //覆盖session 中的 user
        $this->user = $user;
        $this->user_id = $user['user_id'];
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
                    //签到积分
                    //$add_point = (int)M('config')->where(['name'=>'sign_integral'])->value('value');
                    //accountLog($user_id, 0, $add_point , '签到送积分',0,0 ,'');
                    //添加积分   有两种用户   在user表中有两个字段is_agent（代理商）和is_distribut（经销商）  也可能即使代理商又是经销商  user表添加两个字段分别存储代理商抽奖次数agent_free_number和经销商抽奖次数distribut_free_number   读取设置表config中代理商签到次数agent_sign_num和经销商签到次数distribut_sign_num   在签到成功的时候  判断签到记录表中是否达到预定的次数   达到后改变user表中对应的次数+1

                    //先查询用户类型
                    $user = M('users')->where(['user_id'=>$user_id])->field('is_agent,is_distribut')->find();
                    //获取后台设置的签到天数
                    $sign_distribut_days = M ('config')->where(['name'=>'sign_distribut_days'])->value('value');
                    $sign_agent_days = M('config')->where(['name'=>'sign_agent_days'])->value('value');
                    //代理类型
                //   var_dump($sign_distribut_days);
//                    echo '``````````````````';
//                    var_dump($user);
//                    echo '``````````````````';
                    if($user['is_agent'] == 1){
                        //查询签到记录看已经连续签到是次数是否达到了设置的值
                        $agent_continue_sign_num = $this->goods_continue_sign($user_id,'sign_agent');
//                        echo $agent_continue_sign_num.'````````````'.$sign_agent_days;
                        if($agent_continue_sign_num>=$sign_agent_days){
                            //使得user表中代理领礼物次数+1
//                            M('user')->where(['user_id'=>$user_id])->save(['agent_free_num'=>'agent_free_num+1']);
                            M('users')->where(['user_id'=>$user_id])->setInc('agent_free_num');
                            //变更这几次的签到记录中的标志值
                            M('sign_log')->where(['user_id'=>$user_id])->order('sign_day desc')->limit($sign_agent_days)->save(['sign_agent'=>1]);
                        }
                    }
                    //分销员类型
                    if($user['is_distribut'] == 1){
                        //查询签到记录看已经连续签到是次数是否达到了设置的值
                        $distribut_continue_sign_num=$this->goods_continue_sign($user_id,'sign_distribut');
//                        echo '|||||||||||||||||'.$distribut_continue_sign_num;die;
                        if($distribut_continue_sign_num>=$sign_distribut_days){
                            //使得user表中代理领礼物次数+1
//                            M('user')->where(['user_id'=>$user_id])->save(['distribut_free_num'=>'distribut_free_num+1']);
                            M('users')->where(['user_id'=>$user_id])->setInc('distribut_free_num');
                            //变更这几次的签到记录中的标志值
                            M('sign_log')->where(['user_id'=>$user_id])->order('sign_day desc')->limit($sign_distribut_days)->save(['sign_distribut'=>1]);
                        }
                    }
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
        //连续签到几天
        $continue_sign = continue_sign($user_id);

        //签到积分
        $add_point = (int)M('config')->where(['name'=>'sign_integral'])->value('value');
      
        //签到规则

        $rule = M('config')->where(['name'=>'sign_rule'])->value('value');

        //拢共签到几天
        $accumulate_day = count($data);

        //检查权限
        $auth = $this->check_auth($user_id);
        

        return $this->ajaxReturn(
            ['status'=>1,
            'msg'=>'获取成功',
            'data'=>$data,
            'today_sign'=>$today_sign,
            'points'=>$points,
            'add_point'=>$add_point,
            'continue_sign'=> $continue_sign,
            'accumulate_day'=>$accumulate_day,
            'note'=>$rule,
            'auth'=>$auth
            ]);
    }


    /**
     * 检查签到权限
     */
    private function check_auth($user_id){
        //检查身份
        //只有  分销 和 代理 可以签到
        $is_ok = M('users')->where(['user_id'=>$user_id])->field('is_distribut,is_agent')->find();
        if($is_ok['is_distribut'] == 1 || $is_ok['is_agent'] == 1){
            return 1;
        }else{
            return 0;
        }
        
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

    //仅供生成连续签到奖品次数用的获取连续签到数
    function goods_continue_sign($user_id,$sign_mark){

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
        //只查询签到标志为0的记录
        $list = M('sign_log')->where(['user_id'=>$user_id,"$sign_mark"=>0])->order('sign_day desc')->field('sign_day')->select();
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