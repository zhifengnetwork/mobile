<?php
/**
 * 用户API
 */
namespace app\api\controller;
use app\common\model\Users;
use think\Db;

class User extends ApiBase
{

   /**
    * 登录接口
    */
    public function login()
    {
            $mobile = I('mobile');
            $password1 = I('password');
            $password = md5('TPSHOP'.$password1);

            $data = Db::name("users")->where('mobile',$mobile)
            ->field('password,user_id,nickname,user_money,head_pic,agent_user,first_leader,realname,mobile,is_distribut,is_agent')
            ->find();

            if(!$data){
                $this->ajaxReturn(['status' => -1 , 'msg'=>'手机不存在或错误','data'=>'']);
            }
            if ($password != $data['password']) {
                $this->ajaxReturn(['status' => -1 , 'msg'=>'登录密码错误','data'=>'']);
            }
            unset($data['password']);
            //重写
            $data['token'] = $this->create_token($data['user_id']);
            $this->ajaxReturn(['status' => 0 , 'msg'=>'登录成功','data'=>$data]);
    }


    /**
     * 解密
     */
    public function jiemi(){

        //解密token
        $token = '';
        
        $rere = $this->decode_token($token);

        dump($rere);
    }
}
