<?php

use app\common\model\RebateLog;
use app\common\logic\PerformanceLogic;

function agent_level($agent_user){
	$level_name = M("user_level")->where(['level'=>$agent_user])->find();
	//如果找不到，就是分销商
	if(!$level_name){
		return '分销商';
	}
	if($level_name){
		return $level_name['level_name'];
	}
}

/**
 * 通过 user_id 获取 总业绩
 */
function get_zongyeji_bu_user_id($user_id){
	$logic = new PerformanceLogic();
	$money_total= $logic->distribut_caculate_by_user_id($user_id);

	return $money_total['money_total'];
}

/**
 * 通过 user_id 获取昵称
 */
function get_nickname($user_id){
	$nickname = M('users')->where('user_id', $user_id)->value('nickname');
	return $nickname;
}