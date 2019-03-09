<?php


namespace app\admin\controller;

use app\admin\logic\OrderLogic;
use app\common\model\Order;
use app\common\model\TeamActivity;
use app\common\model\TeamFollow;
use app\common\model\TeamFound;
use app\common\logic\MessageFactory;
use think\Loader;
use think\Db;
use think\Page;

class Team extends Base
{
	public function index()
	{
	header("Content-type: text/html; charset=utf-8");
exit("请联系TPshop官网客服购买高级版支持此功能");	
	}

	/**
	 * 拼团详情
	 * @return mixed
	 */
	public function info()
	{
	header("Content-type: text/html; charset=utf-8");
exit("请联系TPshop官网客服购买高级版支持此功能");	
	}

	/**
	 * 保存
	 * @throws \think\Exception
	 */
	public function save(){
	header("Content-type: text/html; charset=utf-8");
exit("请联系TPshop官网客服购买高级版支持此功能");	
	}

	/**
	 * 删除拼团
	 */
	public function delete(){
	header("Content-type: text/html; charset=utf-8");
exit("请联系TPshop官网客服购买高级版支持此功能");
	}

	/**
	 * 确认拼团
	 * @throws \think\Exception
	 */
	public function confirmFound(){
	header("Content-type: text/html; charset=utf-8");
exit("请联系TPshop官网客服购买高级版支持此功能");	
	}

	/**
	 * 拼团退款
	 */
	public function refundFound(){
	header("Content-type: text/html; charset=utf-8");
exit("请联系TPshop官网客服购买高级版支持此功能");	
	}

	/**
	 * 拼团抽奖
	 */
	public function lottery(){
	header("Content-type: text/html; charset=utf-8");
exit("请联系TPshop官网客服购买高级版支持此功能");	
	}

	/**
	 * 拼团订单
	 */
	public function team_list()
	{
	header("Content-type: text/html; charset=utf-8");
exit("请联系TPshop官网客服购买高级版支持此功能");	
	}

	/**
	 * 拼团订单详情
	 * @return mixed
	 */
	public function team_info()
	{
	header("Content-type: text/html; charset=utf-8");
exit("请联系TPshop官网客服购买高级版支持此功能");	
	}

	//拼团订单
	public function order_list(){
	header("Content-type: text/html; charset=utf-8");
exit("请联系TPshop官网客服购买高级版支持此功能");	
	}

	/**
	 * 团长佣金
	 */
	public function bonus(){
	header("Content-type: text/html; charset=utf-8");
exit("请联系TPshop官网客服购买高级版支持此功能");	
	}

	public function doBonus(){
	header("Content-type: text/html; charset=utf-8");
exit("请联系TPshop官网客服购买高级版支持此功能");	
	}
}
