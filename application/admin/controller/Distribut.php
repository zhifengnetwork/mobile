<?php

namespace app\admin\controller;

use think\Page;
use think\Db;
use think\Loader;
use app\admin\logic\UsersLogic;
use app\common\model\Order as OrderModel;

use think\Validate;

class Distribut extends Base {


    /**
    * 订单详情 
     */
    public function detail(){
        $order_id = I('order_id');

        $order = M('order')->where(['order_id'=>$order_id])->find();
        if($order['pay_status'] == 0){
            $this->error('该订单未支付，没有返利');
        } 

        $first_leader = M('users')->where(['user_id'=>$order['user_id']])->value('first_leader');

        $leader = M('users')->where(['user_id'=>$first_leader])->find();
        $this->assign('leader', $leader);

        $log = M('account_log')->where(['order_sn'=>$order['order_sn']])->select();
        $this->assign('log', $log);

       $order_id = input('order_id', 0);
        $orderModel = new OrderModel();
        $order = $orderModel::get(['order_id'=>$order_id]);
        if(empty($order)){
            $this->error('订单不存在或已被删除');
        }
        if($order['pay_status'] == 1){
            $order['pay_status_des'] = '已支付';
        }else{
            $order['pay_status_des'] = '未支付';
        }
        $this->assign('order', $order);
        return $this->fetch();
    }




    /**
     * 分销商列表
     */
    public function distributor_list()
    {
        $count = M('users')->count();
        $pager = new Page($count, 10);
        $distributor = M('users')
                    ->where('is_lock', 0)
                    ->where('is_distribut', 1)
                    ->limit($pager->firstRow, $pager->listRows)
                    ->field('user_id, nickname, level, first_leader, province, mobile, email')
                    ->select();
        $this->assign('pager', $pager);
        $this->assign('distributor', $distributor);
        return $this->fetch();
    }

    /**
     * 分销商删除
     */
    public function distributor_del()
    {
        $id = I('del_id/d');
        if ($id) {
            $result = M('users')->where(['user_id' => $id])->update(['is_distribut' => 0]);
            if($result){
                exit(json_encode(1));
            }else{
                exit(json_encode(0));
            }
        } else {
            exit(json_encode(0));
        }
    }

    /**
     * 代理列表
     */
    public function agent_list()
    {
        $count = M('users')->count();
        $pager = new Page($count, 10);
        $distributor = M('users')
                    ->where('is_lock', 0)
                    ->where('is_distribut', 1)
                    ->limit($pager->firstRow, $pager->listRows)
                    ->field('user_id, nickname, level, first_leader, province, mobile, email')
                    ->select();
        $this->assign('pager', $pager);
        $this->assign('distributor', $distributor);
        return $this->fetch();
    }

    /**
     * 代理删除
     */
    public function agent_del()
    {
        $id = I('del_id/d');
        if ($id) {
            $result = M('users')->where(['user_id' => $id])->update(['is_distribut' => 0]);
            if($result){
                exit(json_encode(1));
            }else{
                exit(json_encode(0));
            }
        } else {
            exit(json_encode(0));
        }
    }
    
    //关系图
    public function tree()
    {
        $UsersLogic = new UsersLogic();    
        $cat_list = $UsersLogic->relation();
        // dump($cat_list);die;
        if($cat_list){
            $level = array_column($cat_list, 'level');
            $heightLevel = max($level);
        }
        $this->assign('heightLevel',$heightLevel);  
        $this->assign('cat_list',$cat_list);    
        
        return $this->fetch();
    }
    
    /**
    * 分销商设置
    **/
    public function grade_list()
    {
        $data = input('post.');
        $distribut = M('distribut')->find();

        //是否接收到数据
        if ($data) {
            if ($distribut) {
                $bool = M('distribut')->where('distribut_id',$distribut['distribut_id'])->update(['rate'=>$data['rate'],'time'=>$data['date'],'update_time'=>time()]);
            } else {
                $bool = M('distribut')->insert(['rate'=>$data['rate'],'time'=>$data['date'],'create_time'=>time(),'update_time'=>time()]);
            }

            if ($bool !== false) {
                $distribut['rate'] = $data['rate'];
            }
        }

        $config['qr_back'] = M('config')->where(['name'=>'qr_back'])->value('value');
        $this->assign('config',$config);


        $this->assign('rate', $distribut['rate']);

        return $this->fetch();
    }


    /**
    * 代理商设置
    **/
    public function agent_grade_list()
    {
        $Ad = M('user_level');
        $p = $this->request->param('p');
        $res = $Ad->order('level_id')->where("level_id <> 12")->page($p . ',10')->select();
        if ($res) {
            foreach ($res as $val) {
                $list[] = $val;
            }
        }
        $level_12 = $Ad->where(['level_id'=>12])->find();
        $this->assign('level_12', $level_12);
        $this->assign('list', $list);
        $count = $Ad->count();
        $Page = new Page($count, 10);
        $show = $Page->show();
        $this->assign('page', $show);

        return $this->fetch();
    }

    /**
     * 代理商等级编辑
     */
    public function level()
    {
        $act = I('get.act', 'add');
        $this->assign('act', $act);
        $level_id = I('get.level_id');
        if ($level_id) {
            $level_info = D('user_level')->where('level_id=' . $level_id)->find();
            $this->assign('info', $level_info);
        }
        return $this->fetch();
    }

    /**
     * 代理商等级添加编辑删除
     */
    public function levelHandle()
    {
        $data = I('post.');

        //验证规则
        $rules = [
            'level' => 'require|number|unique:user_level,level^level_id',
            'level_name' => 'require|unique:user_level',
            'max_money' => 'number',
            'remaining_money' => 'number',
            'rate' => 'require|between:0,100',
        ];

        //错误提示
        $msg = [
            'level.require'          => '等级必填',
            'level.number'           => '等级必须是数字',
            'level.unique'           => '已存在相同的等级',
            'level_name.require'     => '名称必填',
            'level_name.unique'      => '已存在相同等级名称',
            'max_money.number'       => '最大代理佣金必须是数字',
            'remaining_money.number' => '代理拥金总和必须是数字',
            'rate.require'           => '佣金占比必填',
            'rate.between'           => '佣金占比在0-100之间',
        ];

        $validate = new Validate($rules,$msg);

        $return = ['status' => 0, 'msg' => '参数错误', 'result' => ''];//初始化返回信息
        if ($data['act'] == 'add') {
            if (!$validate->batch()->check($data)) {
                $return = ['status' => 0, 'msg' => '添加失败', 'result' => $validate->getError()];
            } else {
                $rateCount = M('user_level')->sum('rate');
                if (($rateCount+$data['rate']) > 100) {
                    $return = ['status' => 0, 'msg' => '编辑失败，所有等级佣金比率总和在100内', 'result' => ''];
                } else {
                    $r = D('user_level')->add($data);
                    if ($r !== false) {
                        $return = ['status' => 1, 'msg' => '添加成功', 'result' => $validate->getError()];
                    } else {
                        $return = ['status' => 0, 'msg' => '添加失败，数据库未响应', 'result' => ''];
                    }
                }
            }
        }
        if ($data['act'] == 'edit') {
            if($data['level_id'] == 12){
                if ($data['rate'] > 100) {
                    $return = ['status' => 0, 'msg' => '编辑失败，所有等级佣金比率总和在100内', 'result' => ''];
                } else {
                    $r = D('user_level')->where('level_id=' . $data['level_id'])->save($data);
                    if ($r !== false) {
                        $data['rate'] = $data['rate'] / 100;
                        D('users')->where(['level' => $data['level_id']])->save($data);
                        $return = ['status' => 1, 'msg' => '编辑成功', 'result' => $validate->getError()];
                    } else {
                        $return = ['status' => 0, 'msg' => '编辑失败，数据库未响应', 'result' => ''];
                    }
                }
            }else{
                if (!$validate->batch()->check($data)) {
                    $return = ['status' => 0, 'msg' => '编辑失败', 'result' => $validate->getError()];
                } else {
                    $rateCount = M('user_level')->where('level_id','neq',$data['level_id'])->sum('rate');
                    if (($rateCount+$data['rate']) > 100) {
                        $return = ['status' => 0, 'msg' => '编辑失败，所有等级佣金比率总和在100内', 'result' => ''];
                    } else {
                        $r = D('user_level')->where('level_id=' . $data['level_id'])->save($data);
                        if ($r !== false) {
                            $data['rate'] = $data['rate'] / 100;
                            D('users')->where(['level' => $data['level_id']])->save($data);
                            $return = ['status' => 1, 'msg' => '编辑成功', 'result' => $validate->getError()];
                        } else {
                            $return = ['status' => 0, 'msg' => '编辑失败，数据库未响应', 'result' => ''];
                        }
                    }
                }
            }
        }
        if ($data['act'] == 'del') {
            $r = D('user_level')->where('level_id=' . $data['level_id'])->delete();
            if ($r !== false) {
                $return = ['status' => 1, 'msg' => '删除成功', 'result' => ''];
            } else {
                $return = ['status' => 0, 'msg' => '删除失败，数据库未响应', 'result' => ''];
            }
        }
        $this->ajaxReturn($return);
    }
    
    /**
     * 分成日志列表
     */
    public function rebate_log()
    {
        $start_time = strtotime(0);
        $end_time = time();
        if(IS_POST){
            $start_time = strtotime(I('start_time'));
            $end_time = strtotime(I('end_time'));
        }
        $count = M('account_log')->alias('acount')->join('users', 'users.user_id = acount.user_id')
                    ->whereTime('acount.change_time', 'between', [$start_time, $end_time])
                    ->where("acount.states = 101 or acount.states = 102")->count();
        $page = new Page($count, 10);
        $log = M('account_log')->alias('acount')->join('users', 'users.user_id = acount.user_id')
                               ->field('users.nickname, acount.*')->order('log_id DESC')
                               ->whereTime('acount.change_time', 'between', [$start_time, $end_time])
                               ->where("acount.states = 101 or acount.states = 102")
                               ->limit($page->firstRow, $page->listRows)
                               ->select();
        
        $this->assign('start_time', $start_time);
        $this->assign('end_time', $end_time);
        $this->assign('pager', $page);
        $this->assign('log',$log);
        return $this->fetch();
    }
    /**
    *消费日志列表
    */
    public function consume_log()
    {
        $count = M('account_log')->alias('acount')->join('users', 'users.user_id = acount.user_id')
                ->where("acount.states = 0")->count();
        $page = new Page($count, 10);
        $log = M('account_log')->alias('acount')->join('users', 'users.user_id = acount.user_id')
            ->field('users.nickname, acount.*')->order('log_id DESC')
            ->where("acount.states = 0")
            ->limit($page->firstRow, $page->listRows)
            ->select();
        // dump($log);die;
        $this->assign('pager', $page);
        $this->assign('log',$log);
        return $this->fetch();
    }
    
    /**
     * 分成日志详情
     */
    public function log_detail()
    {
        $logId = I('id');
        $detail = M('account_log')->alias('acount')->join('users', 'users.user_id = acount.user_id')
                                  ->field('users.nickname, acount.*')->where('acount.log_id', $logId)->find();
        $this->assign('detail', $detail);
        return $this->fetch();
    }
}