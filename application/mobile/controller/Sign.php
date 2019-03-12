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

        $continue_sign = continue_sign($user_id);

        //签到积分
        $add_point = (int)M('config')->where(['name'=>'sign_integral'])->value('value');
      
        //签到规则
        //连续签到几天	        
        $rule = M('config')->where(['name'=>'sign_rule'])->value('value');

        //连续签到几天
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

    /**
     * 是否可以免费领取
     */
    public function receive()
    {
        $cat_id = I('cat_id/d');

        if ($this->user['is_distribut'] == 0 && $cat_id == 584) {
            $result = array('status'=>0,'msg'=>'成为分销商才可领取','result'=>array());
            return $this->ajaxReturn($result);
        }

        if ($this->user['is_agent'] == 0 && $cat_id == 585) {
            $result = array('status'=>0,'msg'=>'成为代理商才可领取','result'=>array());
            return $this->ajaxReturn($result);
        }

        $data = M('order_sign_receive')->where('uid',$this->user_id)->order('addend_time desc')->select();

        if ($this->user['is_agent'] == 1 && count($data)  == 12 ) {
            $result = array('status'=>0,'msg'=>'已超出领取次数','result'=>array());
            return $this->ajaxReturn($result);
        }
        
        if(!empty($data)){

            $newTimeM = date('m', time());//当前月份
            $addTimeM = date('m', $data[0]['addend_time']); //最近下单月份
            $addTimeD = strtotime(date('Y-m-d', $data[0]['addend_time'])); //最近下单天份

            if ($newTime == $addTimeM && $this->user['is_agent'] == 1 ) {
                $result = array('status'=>0,'msg'=>'本月已领取过了','result'=>array());
                return $this->ajaxReturn($result);
            }

            if ($addTimeD+259200 < time() && $this->user['is_agent'] == 1 ) {
                $result = array('status'=>0,'msg'=>'3天内只能领取一次哦','result'=>array());
                return $this->ajaxReturn($result);
            }

        }

        $result = array('status'=>1,'msg'=>'可领取','result'=>array());
        $this->ajaxReturn($result);
    }

}