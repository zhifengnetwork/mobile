<?php
/**
 * 智丰网络
 * ============================================================================
 * 版权所有 2015-2027 深圳搜豹网络科技有限公司，并保留所有权利。
 * 网站地址: http://www.tp-shop.cn
 * ----------------------------------------------------------------------------
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和使用 .
 * 不允许对程序代码以任何形式任何目的的再发布。
 * 采用TP5助手函数可实现单字母函数M D U等,也可db::name方式,可双向兼容
 * ============================================================================
 * Author: lhb
 * Date: 2017-05-15
 */

namespace app\common\logic;

use think\Model; 
use think\Db;

/**
 * 活动逻辑类
 */
class LevelLogic extends Model
{
	public function user_in($leaderId)
	{
		$top_user = $this->user_info_agent($leaderId);
		//判断是否购买指定产品
		if($top_user['is_agent'] != 1){
			return false;
		}
		if($top_user == false){
			return false;
		}

		//判断是否为代理
		$agentGrade = $this->is_agent_user($top_user['user_id']);

		if($agentGrade['level_id']==6) return true;
		if($agentGrade)
		{
			$flag = $this->upgrade_agent($agentGrade['level_id'],$top_user);
		}else
		{	
			//没有购买代理指定商品不能成为代理
			//查询用户购买的所有商品ID
			// $goods = M('order')->alias('order')->join('order_goods', 'order.order_id = order_goods.order_id')
			// 			->where('order.user_id', $leaderId)->field('order_goods.goods_id')->select();
			// $goodsId = array_column($goods, 'goods_id');
			// //查询是否购买有代理指定商品
			// $agentGood = M('goods')->where(array('goods_id' => array('in', $goodsId)))->where('is_agent', 1)->find();
			// if(!$agentGood) return false;

			//有购买代理商品则成为代理
			$flag = $this->add_agent($top_user);
		}
		
		return false;

	}

	/**
	 * 获取上级用户信息
	 */
	private function user_info_agent($userId)
	{
		$top_user = M('users')->where('user_id',$userId)->find();
		return $top_user?$top_user:false;
	}

	/**
	 * 代理升级
	 */
	private function upgrade_agent($grade,$user)
	{
		if($grade<=0)
		{
			$money = $this->user_level($grade);
			$is_satisfy = $this->get_child_agent($user['user_id'],$money['max_money'],$money['remaining_money']);
		}else if($grade<=1)
		{
			$money = $this->user_level($grade);
			$is_satisfy = $this->get_child_agent($user['user_id'],$money['max_money'],$money['remaining_money']);
		}else if($grade<=2)
		{
			$money = $this->user_level($grade);
			$is_satisfy = $this->get_child_agent($user['user_id'],$money['max_money'],$money['remaining_money']);
		}else if($grade<=3)
		{
			$money = $this->user_level($grade);
			$is_satisfy = $this->get_child_agent($user['user_id'],$money['max_money'],$money['remaining_money']);
		}else if($grade<=4)
		{
			$money = $this->user_level($grade);
			$is_satisfy = $this->get_child_agent($user['user_id'],$money['max_money'],$money['remaining_money']);
		}else if($grade<=5)
		{
			$money = $this->user_level($grade);
			$is_satisfy = $this->get_child_agent($user['user_id'],$money['max_money'],$money['remaining_money']);
		}
		
		if(!$is_satisfy) return false;
		$newGrade 	= $grade + 1;
		$data       = array('level_id'=>$newGrade);
		$flag       = M('agent_info')->where(['uid'=>$user['user_id']])->update($data);
		$data1 = array('agent_user'=>$newGrade);
		M('users')->where('user_id',$user['user_id'])->update($data1);
		return $flag;
	}

	/**
	 * 获取用户升级条件
	 */
	private function user_level($grade)
	{
		$grade_level = M('user_level')->where(['level'=>$grade])->find();
		return $grade_level;
	}

	//判断直推条件是否满足
	private function get_child_agent($userId,$max_money,$remaining_money)
	{
		$leader_find = M('users')->where(['first_leader'=>$userId])->select();
		$money_array = [];
		foreach($leader_find as $v){
			$get_child_agent = $this->child_agent($v['user_id']);
			$money_array[] = $get_child_agent;
		}
		$moneys = array_filter($money_array);
		rsort($moneys);
		
		//最大
		if(count($moneys) >= 2){
			$max_moneys = max($moneys);
		}else{
			$max_moneys = $moneys[0];
		}
		array_shift($moneys);
		$moneys = array_sum($moneys);
		if($max_moneys>=$max_money && $moneys>=$remaining_money){
			return $moneys;
		}else{
			return false;
		}
	}

	/**
	*	查询用户业绩
	*/
	public function child_agent($user_id)
	{
		$performance = M('agent_performance')->where(['user_id'=>$user_id])->find();
		if(empty($performance)) return false;
		return $performance['agent_per'];
	}
	
	/**
	 * 判断用户是否为代理
	 */
	private function is_agent_user($user_id)
	{		
		$agent = M('agent_info')->where(['uid'=>$user_id])->find();
		return $agent;
	}

	/**
	 * 添加代理记录
	 */
	 public function add_agent($user)
	 {

		if($user['user_id'] == false){
			return false;
		}

		if($user['first_leader'] == false){
			$user['first_leader'] = 0;
		}

		 $data = array(
			 'uid'=>$user['user_id'],
			 'head_id'=>$user['first_leader'],
			 'level_id'=>0,
			 'create_time'=>time(),
			 'update_time'=>time()
		 );

		 return M('agent_info')->add($data);
	 }
}