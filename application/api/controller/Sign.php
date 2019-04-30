<?php
/**
 * 签到API
 */
namespace app\api\controller;
use app\common\model\Users;
use think\Db;
use think\Controller;


class Sign extends ApiBase
{

    public function ajaxReturn($data){
        header('Content-Type:application/json; charset=utf-8');
        exit(json_encode($data,JSON_UNESCAPED_UNICODE));
    }

    /**
     * 签到.
     */
    public function sign()
    {
		$user_id = I('user_id');
		return $this->ajaxReturn($this->sign_in($user_id));

    }

    /**
     * APP签到.
     */
    public function AppSign()
    {	
		$user_id = $this->get_user_id();
        if(!$user_id){
            $this->ajaxReturn(['status' => -1 , 'msg'=>'用户不存在','data'=>null]);
        }

        //当前积分
        $points = M('users')->where(['user_id' => $user_id])->value('pay_points');
        //连续签到几天
        $continue_sign = continue_sign($user_id);

        //签到积分
        $add_point = (int) M('config')->where(['name' => 'sign_integral'])->value('value');

		$data = $this->sign_in($user_id);
		$data['status'] = ($data['status'] == 1) ? 0 : $data['status'];
		$data['data'] = ['time'=>$data['date'],'points'=>$points,'continue_sign'=>$continue_sign,'add_point'=>$add_point];
		unset($data['date']);
		return $this->ajaxReturn($data);

    }

    /**
     * 获取签到的日期列表.
     */
    public function get_sign_day()
    {
        $user_id = I('user_id',0);
		$data = $this->get_sign_days($user_id);
        return $this->ajaxReturn($data);
    }

    /**
     * APP获取签到的日期列表.
     */
    public function AppGetSignDay()
    {	
		$user_id = $this->get_user_id();
        if(!$user_id){
            $this->ajaxReturn(['status' => -1 , 'msg'=>'用户不存在','data'=>null]);
        }
		$data = $this->get_sign_days($user_id);
		$data = [
			'date'	=> $data['data'],
			'today_sign'	=> $data['today_sign'],
			'points'	=> $data['points'],
			'add_point'	=> $data['add_point'],
			'continue_sign'	=> $data['continue_sign'],
			'accumulate_day'	=> $data['accumulate_day'],
			'note'	=> $data['note'],
			'auth'	=> $data['auth'],
		];
        return $this->ajaxReturn(['status' => 0 , 'msg'=>'请求成功','data'=>$data]);
    }

    /**
     * 检查签到权限.
     */
    private function check_auth($user_id)
    {
        //检查身份
        //只有  分销 和 （购买399可以签到） 可以签到
        //   super_nsign   用户表  = 1
        $is_ok = M('users')->where(['user_id' => $user_id])->field('is_distribut,super_nsign')->find();
        if ($is_ok['is_distribut'] == 1 || $is_ok['super_nsign'] == 1) {
            return 1;
        } else {
            return 0;
        }
    }

    /**
     * 处理时间.
     */
    private function deal_time($time)
    {
        //
        $m=date('m',strtotime($time))-1;
        $y=date('Y',strtotime($time));
        $d=date('d',strtotime($time));
        $newtime="$y-$m-$d";
//        $time = strtotime("$time -1 month");
        //前端要求  减去 1个月
//        $time = date('Y-m-d', $time);

        return str_replace('-', '/', $newtime);
    }

    //仅供生成连续签到奖品次数用的获取连续签到数
    public function goods_continue_sign($user_id, $sign_mark)
    {
        //定义时间戳
        date_default_timezone_set('Asia/Shanghai');
        //先看一下今天有没有签到
        $con['sign_day'] = array('like', date('Y-m-d', time()).'%');
        $cunzai = M('sign_log')->where(['user_id' => $user_id])->where($con)->find();
        if ($cunzai) {
            $todaySign = 1;
        } else {
            $todaySign = 0;
        }
        //再看之前的签到时间
        //只查询签到标志为0的记录
        $list = M('sign_log')->where(['user_id' => $user_id, "$sign_mark" => 0])->order('sign_day desc')->field('sign_day')->select();
        //对所有的签到时间进行时间戳然后倒序排序
        $array = array();
        foreach ($list as $key => $value) {
            $array[] = strtotime($value['sign_day']);
        }

        //定义连续签到次数
        $countSign = $todaySign;
        //依次判断所有的时间戳是否在指定范围内，例如第一个应该在昨天00:00:00-23:59:59之前，如果在则$countSign+1,否则跳出循环
        //定义昨天的时间戳范围
        $begintime = strtotime(date('Y-m-d 00:00:00', time() - 86400));
        $endtime = strtotime(date('Y-m-d 23:59:59', time() - 86400));
        if ($todaySign == 1) {
            for ($i = 1; $i < count($array);) {
                //                echo $begintime."------".$array[$i]."---------".$endtime."+++++";
                if ($array[$i] >= $begintime && $array[$i] <= $endtime) {
                    ++$countSign;
                    $begintime -= 86400;
                    $endtime -= 86400;
                } else {
                    break;
                }
                ++$i;
            }
        } else {
            for ($k = 0; $k < count($array);) {
                if ($array[$k] >= $begintime && $array[$k] <= $endtime) {
                    ++$countSign;
                    $begintime -= 86400;
                    $endtime -= 86400;
                } else {
                    break;
                }
                ++$k;
            }
        }

        return $countSign;
    }

	//------------------------------------------------------------------
	private function sign_in($user_id){
        $user_model = new Users();
        if (!$user_id) {
            return ['status' => -1, 'msg' => '签到user_id不能为空'];
        }
        $con['sign_day'] = array('like', date('Y-m-d', time()).'%');
        $cunzai = M('sign_log')->where(['user_id' => $user_id])->where($con)->find();
        $date = $this->deal_time(date('Y-m-d H:i:s', time()));
        if ($cunzai) {
            return ['status' => 1, 'msg' => '今日已签到', 'date' => $date];
        }
		
		$auth = $this->check_auth($user_id);
		if(!$auth)return ['status' => -1, 'msg' => '您还没有签到权限', 'date' => null];
        Db::startTrans();
        try{

            $r = M('sign_log')->save(['user_id' => $user_id, 'sign_day' => date('Y-m-d H:i:s')]);
            $user = $user_model->where(['user_id' => $user_id])->field('is_agent,super_nsign,is_distribut')->find();
            //获取后台设置的签到天数
            $sign_distribut_days = M('config')->where(['name' => 'sign_distribut_days'])->value('value');
            $sign_agent_days = M('config')->where(['name' => 'sign_agent_days'])->value('value');
            //代理类型

            //更改成是否  有资格
            // is_agent
            if ($user['super_nsign'] == 1) {
                //查询签到记录看已经连续签到是次数是否达到了设置的值
                $agent_continue_sign_num = $this->goods_continue_sign($user_id, 'sign_agent');
                if ($agent_continue_sign_num >= $sign_agent_days) {

                    //使得user表中代理领礼物次数+1
                    $agent_free_num = $user_model->where(['user_id' => $user_id])->value('agent_free_num');
                   
                    $agent_free_num = (int) $agent_free_num + 1;
                    $user_model->where(['user_id' => $user_id])->update(['agent_free_num' => $agent_free_num]);

                    // //变更这几次的签到记录中的标志值
                    M('sign_log')->where(['user_id' => $user_id])->order('sign_day desc')->limit($sign_agent_days)->update(['sign_agent' => 1]);

                    // 写日志
                    $log = array(
                        'user_id' => $user_id,
                        'type' => 'AGENT'
                    );
                    M('log_receive_sign_free')->add($log);
                }
            }

            //分销员类型
            if ($user['is_distribut'] == 1) {
                //查询签到记录看已经连续签到是次数是否达到了设置的值
                $distribut_continue_sign_num = $this->goods_continue_sign($user_id, 'sign_distribut');
               
                if ($distribut_continue_sign_num >= $sign_distribut_days) {
                    //使得user表中代理领礼物次数+1
                    //M('user')->where(['user_id'=>$user_id])->save(['distribut_free_num'=>'distribut_free_num+1']);

                    $distribut_free_num = M('users')->where(['user_id' => $user_id])->value('distribut_free_num');

                    $distribut_free_num = (int) $distribut_free_num + 1;

                    $user_model->where(['user_id' => $user_id])->save(['distribut_free_num' => $distribut_free_num]);

                    // //变更这几次的签到记录中的标志值
                    M('sign_log')->where(['user_id' => $user_id])->order('sign_day desc')->limit($sign_distribut_days)->save(['sign_distribut' => 1]);

                    // 写日志
                    $log = array(
                        'user_id' => $user_id,
                        'type' => 'DISTRIBUT'
                    );
                    M('log_receive_sign_free')->add($log);
                }
            }

            // 提交事务
            Db::commit();    
            return ['status' => 1, 'msg' => '签到成功', 'date' => $date,'a'=> $agent_free_num,'d'=>$distribut_free_num];

        } catch (\Exception $e) {
            // 回滚事务
            Db::rollback();
            return ['status' => -1, 'msg' => '签到失败', 'date' => $date];

        }	
	}

	private function get_sign_days($user_id){
        if (!$user_id) {
            return ['status' => -1, 'msg' => 'user_id不能为空', 'data' => null];
        }
        $list = M('sign_log')->where(['user_id' => $user_id])->field('sign_day')->select();
        foreach ($list as $k => $v) {
            $data[$k] = $this->deal_time($v['sign_day']);
        }

        $con['sign_day'] = array('like', date('Y-m-d', time()).'%');
        $cunzai = M('sign_log')->where(['user_id' => $user_id])->where($con)->find();
        if ($cunzai) {
            $today_sign = true;
        } else {
            $today_sign = false;
        }

        //当前积分
        $points = M('users')->where(['user_id' => $user_id])->value('pay_points');
        //连续签到几天
        $continue_sign = continue_sign($user_id);

        //签到积分
        $add_point = (int) M('config')->where(['name' => 'sign_integral'])->value('value');

        //签到规则

        $rule = M('config')->where(['name' => 'sign_rule'])->value('value');

        //拢共签到几天
        $accumulate_day = count($data);

        //检查权限
        $auth = $this->check_auth($user_id);

        return ['status' => 1,
            'msg' => '获取成功',
            'data' => $data,
            'today_sign' => $today_sign,
            'points' => $points,
            'add_point' => $add_point,
            'continue_sign' => $continue_sign,
            'accumulate_day' => $accumulate_day,
            'note' => $rule,
            'auth' => $auth,
            ];	
	}
}
