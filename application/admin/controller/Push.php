<?php


namespace app\admin\controller;

use think\Exception;
use think\Page;
use think\Db;

class Push extends Base
{
    /**
     * 地推设置
     */
    public function set_recharge()
    {
        if(IS_POST){
            $data = I('post.');
            Db::startTrans();
            try{
                Db::name('config')->where('name', 'recharge_open')->where('inc_type', 'recharge')
                    ->update(['value'=>$data['recharge_open']]);
                Db::name('config')->where('name', 'points_rate')->where('inc_type', 'recharge')
                    ->update(['value'=>$data['points_rate']]);
                Db::name('config')->where('name', 'recharge_card')->where('inc_type', 'recharge')
                    ->update(['value'=>$data['recharge_card']]);
                // 提交事务
                Db::commit();
                $this->ajaxReturn(['status'=>1,'msg'=>"修改成功"]);    
            } catch (\Exception $e) {
                // 回滚事务
                Db::rollback();
                $this->ajaxReturn(['status'=>0,'msg'=>"修改失败"]);
            }
        }
        $condition = array(
            'name' => ['in', ['recharge_open', 'points_rate', 'recharge_card']],
            'inc_type' => 'recharge',
        );
        $config = M('config')->where($condition)->column('name, value');
        $this->assign('config', $config);
        return $this->fetch();
    }

    /**
     * 地推申请记录
     */
    public function apply_recharge()
    {
        //时间搜素
        $add_time = urldecode(I('create_time'));
        $add_time = $add_time ? $add_time : date('Y-m-d H:i:s', strtotime('-1 year')) . ',' . date('Y-m-d H:i:s', strtotime('+1 day'));
        $create_time = explode(',', $add_time);
        $this->assign('start_time', $create_time[0]);
        $this->assign('end_time', $create_time[1]);
        $where['recharge.create_time'] = array(array('gt', strtotime($create_time[0])), array('lt', strtotime($create_time[1])));
        
        //状态搜素
        $status = I('status');
        if ($status !== '') {
            $where['recharge.status'] = $status;
        } else {
            $where['recharge.status'] = ['lt', 2];
        }

        //会员信息搜索
        $search_type = I('search_type');
        $search_value = I('search_value');
        if($search_type == 'user_id'){
            $where['recharge.user_id'] = $search_value ? $search_value : array('like', "%$search_value%");                
        }else if($search_type == 'nickname'){
            $where['users.nickname'] = array('like', "%$search_value%");
        }else if($search_type == 'mobile'){
            $where['users.mobile'] = array('like', "%$search_value%");
        }

        $count = M('recharge_points')->alias('recharge')->where($where)
                ->join('users', 'users.user_id = recharge.user_id')
                ->count();
        $Page = new Page($count, 10);
        $recharge = M('recharge_points')->alias('recharge')->where($where)
                ->join('users', 'users.user_id = recharge.user_id')
                ->field('recharge.*, users.nickname')
                ->order('id DESC')
                ->limit($Page->firstRow, $Page->listRows)
                ->select();
        $this->assign('pager', $Page);
        $this->assign('recharge', $recharge);
        return $this->fetch();
    }

    /**
     * 地推记录查看
     */
    public function edit_recharge()
    {
        $id = I('id');
        $recharge = M('recharge_points')->alias('recharge')
                ->join('users', 'users.user_id = recharge.user_id')
                ->field('recharge.*, users.nickname, users.integral_push')
                ->where('id', $id)->find();
        $this->assign('recharge', $recharge);
        return $this->fetch();
    }

    /**
     * 处理地推申请
     */
    public function update_recharge()
    {
        $data = I('post.');
        $id = $data['id'];
        if($id){
            if(!is_array($id)){
                $ids[] = $id;
            }else{
                $ids = $id;
            }
            $status = $data['status'];
            $remark = $data['remark'];
            unset($data);

            $data = array(
                'status' => $status,
                'remark' => $remark,
                'check_time' => time(),
            );
            if($status == '1'){
                foreach ($ids as $key => $value) {
                    $recharge = M('recharge_points')->where('id', $value)->find();
                    $integral_push = M('users')->where('user_id', $recharge['user_id'])->value('integral_push');
                    $integral_push = bcadd($recharge['exchange_integral'], $integral_push, 2);
                    $result = M('users')->where('user_id', $recharge['user_id'])->update(['integral_push'=>$integral_push]);
                    if($result){
                        M('recharge_points')->where('id', $value)->update($data);
                    }
                }
                $this->ajaxReturn(['status'=>1,'msg'=>"操作成功"]);
                exit;
            }else{
                $result = M('recharge_points')->where('id',['in',$ids])->update($data);
                if($result){
                    $this->ajaxReturn(['status'=>1,'msg'=>"操作成功"]);
                }else{
                    $this->ajaxReturn(['status'=>0,'msg'=>"操作失败"]);
                }
            }
        }
    }

    /**
     * 删除地推记录
     */
    public function del_recharge()
    {
        $id = I('post.del_id');
        $result = M('recharge_points')->where('id', $id)->delete();
        if($result){
            $this->ajaxReturn(['status'=>1,'msg'=>"删除成功"]);
        }else{
            $this->ajaxReturn(['status'=>0,'msg'=>"删除失败"]);
        }
    }
}