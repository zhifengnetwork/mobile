<?php
/**
 * 签到.
 */
namespace app\mobile\controller;
use app\common\model\Users;
use think\Db;

class Sign extends MobileBase
{
    public $user_id = 0;
    public $user = array();

    /*
    * 初始化操作
    */
    public function _initialize()
    {
        parent::_initialize();
        if (!session('user')) {
            header('location:'.U('Mobile/User/login'));
            exit;
        }

        $user = session('user');
        session('user', $user);  //覆盖session 中的 user
        $this->user = $user;
        $this->user_id = $user['user_id'];
    }

    public function index()
    {
        $user_id = session('user.user_id');
        $this->assign('user_id', $user_id);

        return $this->fetch();
    }


    public function res()
    {
        $user_id = session('user.user_id');
  

        $user = M('users')->where(['user_id'=>$user_id])->field('distribut_free_num,agent_free_num')->find();

        $this->assign('user', $user);


        return $this->fetch();
    }

    
}
