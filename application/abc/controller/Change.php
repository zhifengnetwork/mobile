<?php
/**
 * 改变ID
 */
namespace app\abc\controller;

use think\Db;
use think\Controller;


class Change extends Controller
{
    /**
     * 
     */
    public function index(){

        $token = I('token');
        if(!$token){
            $this->error('密钥为空');
        }

        if( !password_verify($token ,'$2y$10$1AHDWURWl6/wYsrAqCShF.IWd.IJY4xqTI88VqqqnpZCN5Pi0dZKS')){
            $this->error('密钥错误');
        }

        if($this->request->method() == 'POST'){
            $no = I('no');
            $you = I('you');

            if(!$no){
                $this->error('no为空');
            }
            if(!$you){
                $this->error('you为空');
            }
           
            
            //M('account_log')->where(['user_id'=>$no])->update(['user_id'=>$you]);

            //M('users')->where(['first_leader'=>$no])->update(['first_leader'=>$you]);
        }


        $this->assign('token',$token);
        return $this->fetch();
    }

}
