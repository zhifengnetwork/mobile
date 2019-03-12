<?php

namespace app\admin\controller;

use think\Page;
use think\Db;


use app\common\logic\BonusLogic;

class Distribut extends Base {

    public function goods_list(){
       
     
        return $this->fetch();
    }

    public function distributor_list()
    {
      
        return $this->fetch();
    }
    
    public function tree()
    {
      
        return $this->fetch();
    }
    
    /**
    * 分销商设置
    **/
    public function grade_list()
    {
        $data = input('post.');

        $distribut = M('distribut')->find();

        if ($data) {
            if ($distribut) {
                M('distribut')->where('distribut_id',$distribut['distribut_id'])->update(['rate'=>$data['rate'],'update_time'=>time()]);
            } else {
                M('distribut')->insert(['rate'=>$data['rate'],'create_time'=>time(),'update_time'=>time()]);
            }
        }

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
        $res = $Ad->order('level_id')->page($p . ',10')->select();
        if ($res) {
            foreach ($res as $val) {
                $list[] = $val;
            }
        }
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
    public function level(){
        return $this->fetch();
    }


    public function grade_list()
    {
      
        return $this->fetch();
    }
    

    public function rebate_log()
    {
      
        return $this->fetch();
    }
    
    
    /**
     * 分成日志详情
     */
    public function log_detail()
    {
        $logId = I('id');
        $detail = Db::name('agent_log')->alias('log')
                ->join('users', 'users.user_id = log.user_id')
                ->join('goods', 'goods.goods_id = log.goods_id')
                ->field('log.*, users.nickname, goods.goods_name')
                ->where('log.log_id', $logId)
                ->find();
        $this->assign('detail', $detail);
        return $this->fetch();
    }

}