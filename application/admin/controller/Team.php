<?php

namespace app\admin\controller;

use app\admin\logic\OrderLogic;
use app\common\model\Order;
use app\common\model\TeamActivity;
use app\common\model\TeamFollow;
use app\common\model\TeamFound;
use app\common\logic\MessageFactory;
use app\common\util\Exception;
use app\common\util\safe\Validation;
use think\Loader;
use think\Db;
use think\Page;
use app\common\model\GoodsActivity;
use app\common\model\GroupBuy;
use app\common\logic\MessageTemplateLogic;


class Team extends Base
{
	public function index(){
        $teamAct = new TeamActivity();
        $count = $teamAct->where('deleted',0)->count();
        $Page = new Page($count, 10);
        $list = $teamAct->where('deleted',0)->order('team_id desc')->limit($Page->firstRow . ',' . $Page->listRows)->select();
        $this->assign('list', $list);
        $this->assign('page', $Page);
		return $this->fetch();
	}

	public function info(){
            $act = I('GET.act', 'add');       
            $team_id = I('get.team_id/d');
            $list = Db::table('tp_users')->find();
            if($team_id){
                $TeamActivity = new TeamActivity();
                $team_info = $TeamActivity->with('goods')->find($team_id);
                // $list = Db::name('team_activity')->where('team_id',$team_id)->select();
                $this->assign('list',$team_info);
                // dump($list);
                $act = 'edit';
            }
            
        $this->assign('act', $act);
		return $this->fetch();
	}   
	/*
	 * 添加拼团
	 */
	public function teamHandle()
    {
        $data = input();

        $data['start_time'] = strtotime($data['start_time']);        
        $data['end_time'] = strtotime($data['end_time']);

        #   数据验证
        $flag   = Validation::instance(request()->module(), request()->controller(), $data, $data['act'])->check();
        if ($flag !== true) $this->ajaxReturn(['status' => 0, 'msg' => $flag, 'result' => '']);
        #   是否需要删除已参团的数据
        if ($data['act'] == 'del')
        {            
            $result = Db::name('team_activity')->where('team_id', $data['team_id'])->update(['deleted' => 1]);
            if($result){
                ajaxReturn(['status' => 1, 'msg' =>'删除成功', 'result' => $result]);
            } else {
                //错误页面的默认跳转页面是返回前一页，通常不需要设置
                ajaxReturn(['status' => 0, 'msg' =>'删除成功', 'result' => $result]);
            }
        }

        $data_goods = [];
        $data_ladder = [];

        if ($data['act'] == 'add')
        {		$data['needer'] = $data['group_number'];
                $team_id = Db::name('team_activity')->insertGetId($data);
                if($team_id){
					//M('goods')->update(['goods_id'=>$data['goods_id'],'prom_type'=>6,'prom_id'=>$team_id]);
                    $this->ajaxReturn(['status' => 1,'msg' =>'操作成功','result' => '']);
                }else{
                    $this->ajaxReturn(['status' => 0,'msg' =>'操作失败','result' => '']);
                }
        }
        if ($data['act'] == 'edit')
        {
                $res = Db::name('team_activity')->where('team_id', $data['team_id'])->update($data);
                if($res){
                    $this->ajaxReturn(['status' => 1,'msg' =>'操作成功','result' => '']);
                }else{
                    $this->ajaxReturn(['status' => 0,'msg' =>'操作失败','result' => '']);
                }

        }


    }


    public function selectLevel()
    {
        $status = input('status');

        // $tpl = input('tpl',);
        if($status=='add'){
            $h_val = input('h_val');
            $h_val = $h_val+1;
            // 如果删除成功，h_var=h_var-1;  
        }else{
            $h_val = input('h_val');
        }    
        $this->assign('h_val',$h_val);    
        return $this->fetch('add_level');
    }
    public function search_goods2()
    {
        $tpl = input('tpl', 'search_goods');
        return $this->fetch($tpl);
    }

    public function team_list(){       
        $count = M('team_found')->where(['status'=>2])->count();
        $Page = new Page($count, 10);
        $list = M('team_found')->field('discount_price,status,bonus_status',true)->where(['status'=>2])->order('found_end_time desc')->limit($Page->firstRow . ',' . $Page->listRows)->select();

        $TeamActivity = M('team_activity');
        $Goods = M('Goods');
        foreach($list as $k=>$v){
            $info = $TeamActivity->field('act_name,goods_name,goods_id,group_price,group_number')->find($v['team_id']);
            $list[$k]['act_name'] = $info['act_name'];
            $list[$k]['goods_name'] = $info['goods_name'];
            $list[$k]['goods_id'] = $info['goods_id'];
            $list[$k]['group_price'] = $info['group_price'];
            $list[$k]['group_number'] = $info['group_number'];
            $list[$k]['group_number'] = $Goods->where(['goods_id'=>$v['goods_id']])->value('goods_name');
        }

        $this->assign('list', $list);
        $this->assign('page', $Page);
		return $this->fetch();        
    }

    public function foundinfo(){
        $found_id = I('get.found_id/d',0);
        $foundinfo = M('team_found')->field('found_time,user_id,nickname,head_pic')->order('found_end_time desc')->find($found_id);
        $follow = M('team_follow')->field('follow_user_id,follow_user_nickname,follow_user_head_pic,follow_time')->where(['found_id'=>$found_id])->select();  

        $this->assign('foundinfo', $foundinfo);
        $this->assign('follow', $follow);
        return $this->fetch();     
    }

    public function order_list(){
        return $this->fetch();
    }

    public function ajax_order_list(){
        $begin = $this->begin;
        $end = $this->end;
        // 搜索条件
        $condition = array('order.shop_id'=>0);
        $keyType = I("key_type");
        $keywords = I('keywords','','trim');
        
        $consignee =  ($keyType && $keyType == 'consignee') ? $keywords : I('consignee','','trim');
        $consignee ? $condition['order.consignee'] = trim($consignee) : false;

        if($begin && $end){
        	$condition['order.add_time'] = array('between',"$begin,$end");
        }
        $condition['order.prom_type'] = 6;
        $condition['order.pay_status'] = 1;
        $condition['order.order_status'] = ['not in',[3,5]];
        $order_sn = ($keyType && $keyType == 'order_sn') ? $keywords : I('order_sn') ;
        $order_sn ? $condition['order.order_sn'] = trim($order_sn) : false;

        //搜索昵称,手机号码,快递单号
        $users_id = ($keyType && $keyType == 'users_id') ? $keywords : I('users_id') ;
        $nickname = ($keyType && $keyType == 'nickname') ? $keywords : I('nickname') ;
        $mobile = ($keyType && $keyType == 'mobile') ? $keywords : I('mobile') ;
        $invoice_no = ($keyType && $keyType == 'invoice_no') ? $keywords : I('invoice_no') ;
        $users_id ? $condition['users.user_id'] = trim($users_id) : false;
        $nickname ? $condition['users.nickname'] = ['like', '%' . trim($nickname) . '%'] : false;
        $mobile ? $condition['order.mobile'] = ['like', '%' . trim($mobile) . '%'] : false;
        $invoice_no ? $condition['delivery.invoice_no'] = trim($invoice_no) : false;
        
        I('order_status') != '' ? $condition['order.order_status'] = I('order_status') : false;
        I('pay_status') != '' ? $condition['order.pay_status'] = I('pay_status') : false;
        //I('pay_code') != '' ? $condition['pay_code'] = I('pay_code') : false;
        if(I('pay_code')){
            switch (I('pay_code')){
                case '余额支付':
                    $condition['order.pay_name'] = I('pay_code');
                    break;
                case '积分兑换':
                    $condition['order.pay_name'] = I('pay_code');
                    break;
                case 'alipay':
                    $condition['order.pay_code'] = ['in',['alipay','alipayMobile']];
                    break;
                case 'weixin':
                    $condition['order.pay_code'] = ['in',['weixin','weixinH5','miniAppPay']];
                    break;
                case '其他方式':
                    $condition['order.pay_name'] = '';
                    $condition['order.pay_code'] = '';
                    break;
                default:
                    $condition['order.pay_code'] = I('pay_code');
                    break;
            }
        }

        $condition['team_found.status'] = 2;
        I('shipping_status') != '' ? $condition['order.shipping_status'] = I('shipping_status') : false;
        I('user_id') ? $condition['order.user_id'] = trim(I('user_id')) : false;
        $sort_order = I('order_by','DESC').' '.I('sort');
        $sort_order = 'order.'.$sort_order;
        $count = Db::name('order')->alias('order')
                ->join('users', 'users.user_id = order.user_id', 'LEFT')
                ->join('delivery_doc delivery', 'delivery.order_id = order.order_id', 'LEFT')
                ->join('team_found', 'users.user_id = team_found.user_id', 'LEFT')
                ->where($condition)->count();
        $Page  = new \think\AjaxPage($count,20);
        $show = $Page->show();
        $orderList = Db::name('order')->alias('order')
                    ->join('users', 'users.user_id = order.user_id', 'LEFT')
                    ->join('delivery_doc delivery', 'delivery.order_id = order.order_id', 'LEFT')
                    ->join('team_found', 'users.user_id = team_found.user_id', 'LEFT')
                    ->where($condition)
                    ->limit($Page->firstRow,$Page->listRows)
                    ->field('order.*')
                    ->order($sort_order)->select();

        $this->assign('orderList',$orderList);
        $this->assign('page',$show);// 赋值分页输出
        $this->assign('pager',$Page);
        return $this->fetch();        
    }

}
