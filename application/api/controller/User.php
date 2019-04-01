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
    
    public function reset_pwd(){//重置密码
        $user_id = $this->get_user_id();
        if(!$user_id){
            $this->ajaxReturn(['status' => -1 , 'msg'=>'用户不存在','data'=>'']);
        }
        $password1 = I('password');
        $password = md5('TPSHOP'.$password1);
        $data = array('password'=>$password);
        $data = Db::name('users')->data($data)->where('user_id',$user_id)->save();
        if($data){
            $this->ajaxReturn(['status' => 0 , 'msg'=>'修改成功','data'=>$data]);
        }else{
            $this->ajaxReturn(['status' => -1 , 'msg'=>'修改失败','data'=>$data]);
        }
        
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
    
    /**
     * 头像上传
     */
      public function update_head_pic(){

            $user_id = $this->get_user_id();
            if($user_id!=""){
                // 获取表单上传文件 例如上传了001.jpg
                $file = request()->file('image');
                // 移动到框架应用根目录/uploads/ 目录下
                $info = $file->validate(['size'=>204800,'ext'=>'jpg,png,gif']);
                $info = $file->rule('md5')->move(ROOT_PATH . DS.'public/upload');//加密->保存路径
                if($info){
                    // 成功上传后 获取上传信息
                    // 输出 jpg
                    // echo $info->getExtension();
                    // 输出 20160820/42a79759f284b767dfcb2a0197904287.jpg
                    // echo $info->getSaveName();
                    // 输出 42a79759f284b767dfcb2a0197904287.jpg
                    $data = SITE_URL.'/public/upload/'.$info->getSaveName(); //输出路径
                    // ROOT_PATH . DS.
                    
                }else{
                    $this->ajaxReturn(['status' => -2 , 'msg'=>'上传失败','data'=>$file->getError()]);
                }

            }
            $this->ajaxReturn(['status' => 0 , 'msg'=>'获取成功','data'=>$data]);
    }

    public function address_list(){//地址管理列表
        $user_id = $this->get_user_id();
        if(!$user_id){
            $this->ajaxReturn(['status' => -1 , 'msg'=>'用户不存在','data'=>'']);
        }
        $data =  db('user_address')->where('user_id', $user_id)->select();
        $region_list = db('region')->cache(true)->getField('id,name');
        foreach ($data as $k => $v) {
            $v['province']=$region_list[$v['province']];
            $v['city']=$region_list[$v['city']];
            $v['district'] = $region_list[$v['district']];
            $v['twon']=$region_list[$v['twon']];
        }
        $this->ajaxReturn(['status' => 0 , 'msg'=>'获取成功','data'=>$data]);
    }


}
