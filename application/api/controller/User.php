<?php
/**
 * 用户API
 */
namespace app\api\controller;
use app\common\model\Users;
use think\Db;
use think\Controller;


class User extends Controller
{

    public function ajaxReturn($data){
        header('Content-Type:application/json; charset=utf-8');
        exit(json_encode($data,JSON_UNESCAPED_UNICODE));
    }

   
    public function login()
    {
            $mobile = I('mobile');
            $password1 = I('password');
            $password = md5('TPSHOP'.$password1);

            $data = Db::name("users")->where('mobile',$mobile)->find();
        
            if(!$data){
                $this->ajaxReturn(['status' => -1 , 'msg'=>'手机不存在或错误','data'=>'']);
            }
            if ($password != $data['password']) {
                $this->ajaxReturn(['status' => -1 , 'msg'=>'登录密码错误','data'=>'']);
            }
            
            //重写
            $data['token'] = md5(time().$data['openid']);

            Db::name("users")->where('user_id',$data['user_id'])->update(['token'=>$data['token']]);

            $this->ajaxReturn(['status' => 1 , 'msg'=>'登录成功','data'=>$data]);
       
        
    }
}
