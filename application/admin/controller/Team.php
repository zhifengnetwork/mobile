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
        $groupbuy_id = I('get.id/d');
        $group_info = array();
        $group_info['start_time'] = date('Y-m-d H:i:s');
        $group_info['end_time'] = date('Y-m-d H:i:s', time() + 3600 * 365);
        $group_info['is_edit'] = 1;
        if ($groupbuy_id) {
            $GroupBy = new GroupBuy();
            $group_info = $GroupBy->with('specGoodsPrice,goods')->find($groupbuy_id);
            $group_info['start_time'] = date('Y-m-d H:i:s', $group_info['start_time']);
            $group_info['end_time'] = date('Y-m-d H:i:s', $group_info['end_time']);
            $act = 'edit';
        }

        $this->assign('min_date', date('Y-m-d H:i:s'));
        $this->assign('info', $group_info);
        $this->assign('act', $act);

		return $this->fetch();
	}

	/*
	 * 添加拼团
	 *
	 */
	public function teamHandle()
    {
        $data = I('post.');
        dump($data);
        $data['create_time'] = date('Y-m-d H:i:s',time());
        $data['start_time'] = strtotime($data['start_time']);
        $data['end_time'] = strtotime($data['end_time']);
        $result = '';
        if ($data['act'] == 'del')
        {
            $result = Db::name('team_activity')->where('team_id', $data['team_id'])->update(['deleted' => 1]);
            if($result){
                $this->success('删除成功', 'team/index');
            } else {
                //错误页面的默认跳转页面是返回前一页，通常不需要设置
                $this->error('删除失败');
            }
        }

        $data_goods = [];
        $data_ladder = [];

        if ($data['act'] == 'add')
        {
            $team_id = Db::name('team_activity')->insertGetId($data);
            if($team_id)
            {
                if(!empty($data_goods))
                {
                    Db::table('team_goods_item')->insert($data_goods);
                }
                if(!empty($data_ladder))
                {
                    Db::table('team_ladder')->insert($data_ladder);
                }

            }
            if ($team_id !== false) {

                $this->ajaxReturn(['status' => 1,'msg' =>'操作成功','result' => '']);
            } else {
                $this->ajaxReturn(['status' => 0,'msg' =>'操作失败','result' =>'']);
            }

        }
        if ($data['act'] == 'edit')
        {
            $data['update_time'] = date('Y-m-d H:i:s',time());
            Db::table('team_activity')->where('team_id', $data['team_id'])->update($data);
            Db::table('team_goods_item')->where('team_id', $data['team_id'])->insert($data_goods);
            Db::table('team_ladder')->where('team_id', $data['team_id'])->insert($data_ladder);
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
