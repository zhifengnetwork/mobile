<?php

namespace app\admin\controller; 

use app\common\model\UserMsgTpl;
use think\Controller;
use think\AjaxPage;
use think\Db;
use think\Page;
use think\Loader;

class MessageTemplate extends Base {
        
    public function index(){
        $userMsgTpl = new UserMsgTpl();
        $list = $userMsgTpl->select();
		$this->assign('list', $list);
        return $this->fetch();
    }
    /**
     * 修改或添加
     */
    public function editTemplate(){
        
        if(IS_POST)
        {  
            $data = I('post.');
            $userMsgTpl = Loader::validate('UserMsgTpl');
            if (!$userMsgTpl->batch()->scene($data['act'])->check($data)) {
                $return_arr = ['status' => 0, 'msg' => '所有输入项不能为空', 'data' => $userMsgTpl->getError()];
            } else {
                if($data['act'] == 'edit'){
                    $mmt_code = $data['mmt_code'];
                    unset($data['mmt_code']);
                    Db::name("user_msg_tpl")->where('mmt_code', $mmt_code)->update($data);
                }else{
                    $mmt_code = $data['mmt_code'];
                    $arr = Db::name("user_msg_tpl")->where('mmt_code', $mmt_code)->find();
                    if ($arr) {
                        $return_arr = ['status' => 0, 'msg' => '模板编号已存在'];
                        return $this->ajaxReturn($return_arr);
                    }
                    Db::name("user_msg_tpl")->insert($data);
                }
                $return_arr = array('status' => 1,'arr'=>$arr, 'msg' => '操作成功', 'url' => U('Admin/MessageTemplate/index'));
            }

            return $this->ajaxReturn($return_arr);
        }

        $mmt_code = I('mmt_code');
        if (!empty($mmt_code)) {
            $arr = Db::name("user_msg_tpl")->where('mmt_code', $mmt_code)->find();
            $this->assign('arr', $arr);
        }

        return $this->fetch();
    }    
    
    /**
     * 删除
     */
    public function delTemplate(){
       
        $row = Db::name("user_msg_tpl")->where('mmt_code', I('id'))->delete();
        $return_arr = array();
        if ($row){
            $return_arr = array('status' => 1,'msg' => '删除成功','data'  =>'');   
        }else{
            $return_arr = array('status' => -1,'msg' => '删除失败','data'  =>'');  
        } 
        return $this->ajaxReturn($return_arr);
    }
}