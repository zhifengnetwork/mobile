<?php
/**
 * 用户API
 */
namespace app\api\controller;
use app\common\model\Users;
use app\common\logic\UsersLogic;
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
            ->field('password,user_id')
            ->find();

            if(!$data){
                $this->ajaxReturn(['status' => -1 , 'msg'=>'手机不存在或错误','data'=>null]);
            }
            if ($password != $data['password']) {
                $this->ajaxReturn(['status' => -2 , 'msg'=>'登录密码错误','data'=>null]);
            }
            unset($data['password']);
            //重写
            $data['token'] = $this->create_token($data['user_id']);
            $this->ajaxReturn(['status' => 0 , 'msg'=>'登录成功','data'=>$data]);
    }


    public function userinfo(){
        //解密token
        $user_id = $this->get_user_id();
        if($user_id!=""){
            $data = Db::name("users")->where('user_id',$user_id)->field('user_id,nickname,user_money,head_pic,agent_user,first_leader,realname,mobile,is_distribut,is_agent')->find();
        }else{
            $this->ajaxReturn(['status' => -1 , 'msg'=>'用户不存在','data'=>'']);
        }
        $this->ajaxReturn(['status' => 0 , 'msg'=>'获取成功','data'=>$data]);

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

    /*
    注册接口
     */
    // public function reg(){
    //     if (IS_POST) {
    //         $mobile = I('useriphone');
    //         $password = I('password');
    //         $user = Db::name('user')->where('mobile',$mobile)->find();
    //         if($user){
    //             $this->ajaxReturn(['status' => -1 , 'msg'=>'手机号码已存在','data'=>'']);
    //         }else{

    //         }
    //         $this->ajaxReturn($data);
    //     }
    // }
    // 
    // 
    
      public function update_head_pic(){//头像上传

            $user_id = $this->get_user_id();
            if($user_id!=""){
                // 获取表单上传文件 例如上传了001.jpg
                $file = request()->file('file');
                // 移动到框架应用根目录/uploads/ 目录下
                $info = $file->validate(['size'=>204800,'ext'=>'jpg,png,gif']);
                $info = $file->rule('md5')->move(ROOT_PATH . DS.'public/upload');
                if($info){
                    // 成功上传后 获取上传信息
                    // 输出 jpg
                    // echo $info->getExtension();
                    // 输出 20160820/42a79759f284b767dfcb2a0197904287.jpg
                    // echo $info->getSaveName();
                    // 输出 42a79759f284b767dfcb2a0197904287.jpg
                    $data = ROOT_PATH . DS.'public/upload/'.$info->getSaveName(); 
                    
                }else{
                    $this->ajaxReturn(['status' => -2 , 'msg'=>'上传失败','data'=>$file->getError()]);
                }

            }
            $this->ajaxReturn(['status' => 0 , 'msg'=>'获取成功','data'=>$data]);
        
    }
}
