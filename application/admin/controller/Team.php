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

        $data['create_time'] = date('Y-m-d H:i:s',time());
        $data['start_time'] = strtotime($data['start_time']);
        $data['end_time'] = strtotime($data['end_time']);
        $data['time_limit'] = $data['end_time'];
        $result = '';


        #   数据验证
        $flag   = Validation::instance(request()->module(), request()->controller(), $data, $data['act'])->check();
        if ($flag !== true) $this->ajaxReturn(['status' => 0, 'msg' => $flag, 'result' => '']);

        #   是否需要删除已参团的数据
        if ($data['act'] == 'del')
        {
            if(false == Db::name('team_activity')->where('team_id', $data['team_id'])->update(['deleted' => 1])) {
                $this->ajaxReturn(['status' => 0, 'msg' => '删除失败']);
            };
            $this->ajaxReturn();
        }

        $data_goods = [];
        $data_ladder = [];

        if ($data['act'] == 'add')
        {
            try {
                Db::startTrans();
                $team_id = Db::name('team_activity')->insertGetId($data);
                if (!$team_id) throw new Exception('操作失败');

                if(!empty($data_goods))
                {
                    if(false == Db::table('team_goods_item')->insert($data_goods))
                        throw new Exception('添加商品数据失败');
                }
                if(!empty($data_ladder))
                {
                    if (false == Db::table('team_ladder')->insert($data_ladder))
                        throw new Exception('team_ladder失败');
                }

                Db::commit();
                $this->ajaxReturn(['status' => 1,'msg' =>'操作成功','result' => '']);
            } catch (Exception $e) {
                Db::rollback();
                $this->ajaxReturn($e->getData());
            }


        }
        if ($data['act'] == 'edit')
        {
            try {
                Db::startTrans();
                $data['update_time'] = date('Y-m-d H:i:s',time());
                if (false == Db::table('team_activity')->where('team_id', $data['team_id'])->update($data))
                    throw new Exception('更新拼团失败');
                if (false == Db::table('team_goods_item')->where('team_id', $data['team_id'])->insert($data_goods))
                    throw new Exception('更新商品失败');
                if (false == Db::table('team_ladder')->where('team_id', $data['team_id'])->insert($data_ladder))
                    throw new Exception('更新ladder失败');
                Db::commit();
                $this->ajaxReturn();
            } catch (Exception $e) {
                Db::rollback();
                $this->ajaxReturn($e->getData());
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
