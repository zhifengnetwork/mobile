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

        $log = M('account_log')->alias('log')->join('users', 'users.user_id = log.user_id')
                ->field('log.*, users.agent_user')->where('log.states', ['in', ['101', '102']])
                ->where(['order_sn'=>$order['order_sn']])->select();

        $chinese = ['无', '一', '二', '三', '四', '五'];
        foreach($log as $k => $v){
            $log[$k]['agent_user'] = $chinese[$v['agent_user']];
        }

        $this->assign('log', $log);

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

        if($order['total_amount'] <= 9.9 ){
            $this->error('该订单小于9.9元，没有返利');
        } 


        $this->assign('order', $order);
        return $this->fetch();
    }


    /**
     * 补发
     */
    public function bufa(){
    
        $order_id = I('post.order_id/d',0);
    	$bufa = new \app\common\logic\DistributLogic();
      
        try{
            $bufa->bufa($order_id,1);

            $this->ajaxReturn(['status' => 1, 'msg' => '操作成功，请刷新看结果']);

        }catch (TpshopException $t){
            $error = $t->getErrorArr();
            $this->ajaxReturn($error);
        }

    }


    // /**
    //  * 分销商列表
    //  */
    // public function distributor_list()
    // {
    //     $count = M('users')->count();
    //     $pager = new Page($count, 10);
    //     $distributor = M('users')
    //                 ->where('is_lock', 0)
    //                 ->where('is_distribut', 1)
    //                 ->limit($pager->firstRow, $pager->listRows)
    //                 ->field('user_id, nickname, level, first_leader, province, mobile, email')
    //                 ->select();
    //     $this->assign('pager', $pager);
    //     $this->assign('distributor', $distributor);
    //     return $this->fetch();
    // }

    // /**
    //  * 分销商删除
    //  */
    // public function distributor_del()
    // {
    //     $id = I('del_id/d');
    //     if ($id) {
    //         $result = M('users')->where(['user_id' => $id])->update(['is_distribut' => 0]);
    //         if($result){
    //             exit(json_encode(1));
    //         }else{
    //             exit(json_encode(0));
    //         }
    //     } else {
    //         exit(json_encode(0));
    //     }
    // }

    // /**
    //  * 代理列表
    //  */
    // public function agent_list()
    // {
    //     $count = M('users')->count();
    //     $pager = new Page($count, 10);
    //     $distributor = M('users')
    //                 ->where('is_lock', 0)
    //                 ->where('is_distribut', 1)
    //                 ->limit($pager->firstRow, $pager->listRows)
    //                 ->field('user_id, nickname, level, first_leader, province, mobile, email')
    //                 ->select();
    //     $this->assign('pager', $pager);
    //     $this->assign('distributor', $distributor);
    //     return $this->fetch();
    // }

    // /**
    //  * 代理删除
    //  */
    // public function agent_del()
    // {
    //     $id = I('del_id/d');
    //     if ($id) {
    //         $result = M('users')->where(['user_id' => $id])->update(['is_distribut' => 0]);
    //         if($result){
    //             exit(json_encode(1));
    //         }else{
    //             exit(json_encode(0));
    //         }
    //     } else {
    //         exit(json_encode(0));
    //     }
    // }
    
    // //关系图
    // public function tree()
    // {
    //     $UsersLogic = new UsersLogic();    
    //     $cat_list = $UsersLogic->relation();
  
    //     if($cat_list){
    //         $level = array_column($cat_list, 'level');
    //         $heightLevel = max($level);
    //     }
    //     $this->assign('heightLevel',$heightLevel);  
    //     $this->assign('cat_list',$cat_list);    
        
    //     return $this->fetch();
    // }
    
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
        $timegap = urldecode(I('timegap'));
        $search_type = I('search_type');
        $search_value = I('search_value');
        $map = array();
        if ($timegap) {
            $gap = explode(',', $timegap);
            $begin = $gap[0];
            $end = $gap[1];
            $map['change_time'] = array('between', array(strtotime($begin), strtotime($end)));
            $this->assign('begin', $begin);
            $this->assign('end', $end);
        }
        if ($search_value) {
            if($search_type == 'user_id'){
                $map['users.'.$search_type] = $search_value;
            }else{
                $map['users.'.$search_type] = array('like', "%$search_value%");
            }
            
            $this->assign('search_type', $search_type);
            $this->assign('search_value', $search_value);
        }

        $count = M('account_log')->alias('acount')->join('users', 'users.user_id = acount.user_id')
                    ->where("acount.states = 101 or acount.states = 102")
                    ->where($map)->count();
        $page = new Page($count, 20);
        $log = M('account_log')->alias('acount')->join('users', 'users.user_id = acount.user_id')
                               ->field('users.nickname, users.user_id, users.mobile, acount.*')->order('log_id DESC')
                               ->where("acount.states = 101 or acount.states = 102")->where($map)
                               ->limit($page->firstRow, $page->listRows)->select();
        
        $this->assign('pager', $page);
        $this->assign('log',$log);
        return $this->fetch();
    }

    /**
    *消费日志列表
    */
    public function consume_log()
    {
        $timegap = urldecode(I('timegap'));
        $search_type = I('search_type');
        $search_value = I('search_value');
        $map = array();
        if ($timegap) {
            $gap = explode(',', $timegap);
            $begin = $gap[0];
            $end = $gap[1];
            $map['change_time'] = array('between', array(strtotime($begin), strtotime($end)));
            $this->assign('begin', $begin);
            $this->assign('end', $end);
        }
        if ($search_value) {
            if($search_type == 'user_id'){
                $map['users.'.$search_type] = $search_value;
            }else{
                $map['users.'.$search_type] = array('like', "%$search_value%");
            }
            
            $this->assign('search_type', $search_type);
            $this->assign('search_value', $search_value);
        }

        $count = M('account_log')->alias('acount')->join('users', 'users.user_id = acount.user_id')
                ->where("acount.states = 0")->where($map)->count();
        $page = new Page($count, 20);
        $log = M('account_log')->alias('acount')->join('users', 'users.user_id = acount.user_id')
            ->field('users.user_id, users.nickname, users.mobile, acount.*')->order('log_id DESC')
            ->where("acount.states = 0")->where($map)
            ->limit($page->firstRow, $page->listRows)
            ->select();

        $this->assign('pager', $page);
        $this->assign('log',$log);
        return $this->fetch();
    }

    /**
     * 导出日志
     */
    public function export_log()
    {
        $strTable = '<table width="500" border="1">';
        $strTable .= '<tr>';
        $strTable .= '<td style="text-align:center;font-size:12px;width:120px;">日志ID</td>';
        $strTable .= '<td style="text-align:center;font-size:12px;width:120px;">会员ID</td>';
        $strTable .= '<td style="text-align:center;font-size:12px;" width="100">会员昵称</td>';
        $strTable .= '<td style="text-align:center;font-size:12px;" width="*">手机号码</td>';
        $strTable .= '<td style="text-align:center;font-size:12px;" width="*">金额</td>';
        $strTable .= '<td style="text-align:center;font-size:12px;" width="*">时间</td>';
        $strTable .= '<td style="text-align:center;font-size:12px;" width="*">描述</td>';
        $strTable .= '</tr>';

        //区分日志类型
        $condition = array();
        $type = I('type');
        if($type == 'rebate_log'){
            $status = array(101, 102);
            $condition['states'] = ['in', $status];
        }else{
            $condition['states'] = 0;
        }

        //接收搜索条件,按搜索条件导出表格
        $timegap = urldecode(I('timegap'));
        $search_type = I('search_type');
        $search_value = I('search_value');
        if ($timegap) {
            $gap = explode(',', $timegap);
            $begin = $gap[0];
            $end = $gap[1];
            $condition['change_time'] = array('between', array(strtotime($begin), strtotime($end)));
        }
        if ($search_value) {
            if($search_type == 'user_id'){
                $condition['users.user_id'] = $search_value;
            }else{
                $condition['users.'.$search_type] = array('like', "%$search_value%");
            }
        }

        $count = M('account_log')->alias('acount')->join('users', 'users.user_id = acount.user_id')
                ->where($condition)->count();
        $p = ceil($count / 5000);
        for ($i = 0; $i < $p; $i++) {
            $start = $i * 5000;
            $end = ($i + 1) * 5000;
            $userList = M('account_log')->alias('acount')->join('users', 'users.user_id = acount.user_id')
                        ->field('users.nickname, users.user_id, users.mobile, acount.*')->order('log_id DESC')
                        ->where($condition)->limit($start,5000)->select();
            if (is_array($userList)) {
                foreach ($userList as $k => $val) {
                    $strTable .= '<tr>';
                    $strTable .= '<td style="text-align:center;font-size:12px;">' . $val['log_id'] . '</td>';
                    $strTable .= '<td style="text-align:center;font-size:12px;">' . $val['user_id'] . '</td>';
                    $strTable .= '<td style="text-align:left;font-size:12px;">' . $val['nickname'] . ' </td>';
                    $strTable .= '<td style="text-align:left;font-size:12px;">' . $val['mobile'] . '</td>';
                    $strTable .= '<td style="text-align:left;font-size:12px;">' . $val['user_money'] . '</td>';
                    $strTable .= '<td style="text-align:left;font-size:12px;">' . date('Y-m-d H:i', $val['change_time']) . '</td>';
                    $strTable .= '<td style="text-align:left;font-size:12px;">' . $val['desc'] . '</td>';
                    $strTable .= '</tr>';
                }
                unset($userList);
            }
        }
        $strTable .= '</table>';
        downloadExcel($strTable, $type . $i);
        exit();
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

    /**
     * 区域代理设置
     */
    public function agent_area()
    {   
        $data = array('is_valid', 'is_divide');
        //开启或关闭区域代理 
        if(IS_POST){
            $is_valid = I('valid');
            $is_divide = I('divide');
            M('config')->where('name', 'is_valid')->update(['value'=>$is_valid]);
            M('config')->where('name', 'is_divide')->update(['value'=>$is_divide]);
        }
        
        $count = M('config_regional_agency')->count();
        $agent_area = M('config_regional_agency')->select();
        $config = M('config')->where('name', ['in', $data])->column('name, value');
        $this->assign('is_valid', $config['is_valid']);
        $this->assign('is_divide', $config['is_divide']);
        $this->assign('list', $agent_area);
        $this->assign('count', $count);
        return $this->fetch();
    }

    /**
     * 区域代理添加、等级编辑
     */
    public function agent_area_level()
    {
        $id = I('get.id');
        if($id){
            $agent = M('config_regional_agency')->where('id', $id)->find();
            if($agent){
                $this->assign('agent', $agent);
                $this->assign('act', 'edit');
            }
        }else{
            $this->assign('act', 'add');
        }
        return $this->fetch();
    }

    /**
     * 区域代理操作
     */
    public function agent_area_handle()
    {
        $data = I('post.');

        //验证规则
        $rules = [
            'agency_level' => 'require|number|unique:config_regional_agency,agency_level^id',
            'agency_name'  => 'require|unique:config_regional_agency',
            'team_sum'     => 'number',
            'other_sum'    => 'number',
            'rate'         => 'require|between:0,100',
        ];

        //错误提示
        $msg = [
            'agency_level.require' => '等级必填',
            'agency_level.number'  => '等级必须是数字',
            'agency_level.unique'  => '已存在相同的等级',
            'agency_name.require'  => '名称必填',
            'agency_name.unique'   => '已存在相同等级名称',
            'team_sum.number'      => '最大代理佣金必须是数字',
            'other_sum.number'     => '代理拥金总和必须是数字',
            'rate.require'         => '佣金占比必填',
            'rate.between'         => '佣金占比在0-100之间',
        ];

        $validate = new Validate($rules,$msg);

        //初始化返回信息
        $return = ['status' => 0, 'msg' => '参数错误', 'result' => ''];
        //添加代理等级
        if ($data['act'] == 'add') {
            if (!$validate->batch()->check($data)) {
                $return = ['status' => 0, 'msg' => '添加失败', 'result' => $validate->getError()];
            } else {
                $rateCount = M('config_regional_agency')->sum('rate');
                if (($rateCount+$data['rate']) > 100) {
                    $return = ['status' => 0, 'msg' => '编辑失败，所有等级佣金比率总和在100内', 'result' => ''];
                } else {
                    $r = D('config_regional_agency')->add($data);
                    if ($r !== false) {
                        $return = ['status' => 1, 'msg' => '添加成功', 'result' => $validate->getError()];
                    } else {
                        $return = ['status' => 0, 'msg' => '添加失败，数据库未响应', 'result' => ''];
                    }
                }
            }
        }
        //修改代理等级
        if ($data['act'] == 'edit') {
            if (!$validate->batch()->check($data)) {
                $return = ['status' => 0, 'msg' => '编辑失败', 'result' => $validate->getError()];
            } else {
                $rateCount = M('config_regional_agency')->where('id','neq',$data['id'])->sum('rate');
                if (($rateCount+$data['rate']) > 100) {
                    $return = ['status' => 0, 'msg' => '编辑失败，所有等级佣金比率总和在100内', 'result' => ''];
                } else {
                    $r = D('config_regional_agency')->where('id=' . $data['id'])->save($data);
                    if ($r !== false) {
                        // $data['rate'] = $data['rate'] / 100;
                        // D('users')->where(['level' => $data['level_id']])->save($data);
                        $return = ['status' => 1, 'msg' => '编辑成功', 'result' => $validate->getError()];
                    } else {
                        $return = ['status' => 0, 'msg' => '编辑失败，数据库未响应', 'result' => ''];
                    }
                }
            }    
        }
        $this->ajaxReturn($return);
    }
}