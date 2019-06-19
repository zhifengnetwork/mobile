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

/**
 * 返回json格式的提示或者数据
 * @param1 int 状态  0为默认 1为成功 2为验证错误,3为数据库错误,4存在数据,5发送类型错误,6为重复提交数据
 * @param2 string 提示内容
 * @param3 array   数组类型,要求传出的数据类型
 */
function json_return($code = 0, $msg = '', $data = '') {
	$code = (int) $code;
	$return = array(
		'code' => $code,
		'msg' => $msg,
	);
	//不为空,同时是数组
	if ($data != '' && is_array($data)) {
		$return['data'] = $data;
	}
	return json($return);exit;
}