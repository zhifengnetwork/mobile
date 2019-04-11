<?php
namespace app\admin\controller;

use think\Page;
use think\Db;

class Virtual extends Base
{
    /**
     * 首页滚动展示列表
     */
    public function index()
    {
        //时间搜索条件
        $timegap = urldecode(I('timegap'));
        $condition = array();
        if ($timegap) {
            $gap = explode(',', $timegap);
            $begin = $gap[0];
            $end = $gap[1];
            $condition['create_time'] = array('between', array(strtotime($begin), strtotime($end)));
            $this->assign('begin', $begin);
            $this->assign('end', $end);
        }
        $count = M('virtual_order')->where($condition)->count();
        $Page = new Page($count, 20);
        $list = M('virtual_order')->where($condition)->order('id DESC')
                ->limit($Page->firstRow, $Page->listRows)
                ->select();
        $this->assign('list', $list);
        $this->assign('pager', $Page);
        return $this->fetch();
    }

    /**
     * 首页展示内容操作
     */
    public function handle_order()
    {
        $act = I('act');
        if(IS_POST){
            $temp = I('post.');
            $data = array(
                'content'  => $temp['content'],
                'is_show'  => $temp['is_show'],
                'head_ico' => $temp['head_ico'],
            );
            if($temp['act'] == 'add'){
                //添加
                $data['create_time'] = time();
                M('virtual_order')->insert($data); 
            }else if($temp['act'] == 'edit'){
                //编辑修改
                M('virtual_order')->where('id', $temp['id'])->update($data);
            }
        }
        if($act == 'edit'){
            $id = I('id');
            $info = M('virtual_order')->where('id', $id)->find();
            $this->assign('info', $info);
        }
        $this->assign('act', $act);
        return $this->fetch();
    }

}