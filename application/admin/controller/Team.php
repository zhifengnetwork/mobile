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
                dump($team_info);   
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
        {
                $team_id = Db::name('team_activity')->insertGetId($data);
                if($team_id){
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

}
