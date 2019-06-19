<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/6/14 0014
 * Time: 11:31
 */

namespace app\live\controller;

use app\common\model\Users as UserModel;
use think\Db;

class Apply extends Base
{

    /**
     * 直播审核页面
     * @return mixed
     */
    public function index(){
        $user = $this->user;
        $user_id = $user->user_id;
        $verifyData = Db::name('user_verify_identity')->where(['user_id'=>$user_id])->find();
        $verify_state = 0;//认证状态
        if(empty($verifyData)){

        }else{
            if($verifyData['verify_state'] == 2){
                $verify_state = 2;
            }else{
                $verify_state = 1;
            }
        }
        $this->assign('verify_state',$verify_state);
        $this->assign('user',$user);
        return $this->fetch();
    }

    /**
     * 提交审核页面
     * @return mixed
     */
    public function upload(){
        $user_name = input('post.user_name/s');
        $phone = input('post.pone/s');
        $user_id = input('post.user_id/d');
        $user = $this->user;
        $level = input('post.level/d');

        $validate = $this->validate(input('post.'), 'User.name_phone');
        if (true !== $validate) {
            return $this->failResult($validate, 301);
        }


        $file = request()->file('pic_front');
        if($file) {
            //将传入的图片移动到框架应用根目录/public/uploads/ 目录下，ROOT_PATH是根目录下，DS是代表斜杠 /
            $info = $file->move(ROOT_PATH . 'public' . DS . 'static' . DS . 'uploads'.DS. 'apply');
            if ($info) {
                $pic_front = 'public' . DS . 'static' . DS . 'uploads'.DS. 'apply'.DS.$info->getSaveName();
            } else {
                // 上传失败获取错误信息
                return $this->failResult('正面上传失败', 301);
            }
        }else{
            return $this->failResult('正面不能为空', 301);
        }

        $file = request()->file('pic_back');
        if($file) {
            //将传入的图片移动到框架应用根目录/public/uploads/ 目录下，ROOT_PATH是根目录下，DS是代表斜杠 /
            $info = $file->move(ROOT_PATH . 'public' . DS . 'static' . DS . 'uploads'.DS. 'apply');
            if ($info) {
                $pic_back = 'public' . DS . 'static' . DS . 'uploads'.DS. 'apply'.DS.$info->getSaveName();
            } else {
                // 上传失败获取错误信息
                return $this->failResult('反面上传失败', 301);
            }
        }else{
            return $this->failResult('正面不能为空', 301);
        }

        //提交申请直播
        Db::startTrans();
        $user_id = $user->user_id;
        $verifyData = Db::name('user_verify_identity')->where(['user_id'=>$user_id])->find();
        $data = array(
            'verify_state' => 1,
            'state' => 1,
            'create_time' => time(),
        );
        if (empty($verifyData)) {
            //新增记录
            $data['user_id'] = $user_id;
            $result = Db::name('user_verify_identity')->insert($data);
        } else {
            //修改记录
            $result = Db::name('user_verify_identity')->where(['user_id'=>$user_id])->save($data);
        }
        if (!$result) {
            Db::rollback();
            return $this->failResult('设置失败', 301);
        }
        //新增实名认证信息
        $logData = array(
            'user_id' => $user_id,
            'level' => $level,
            'user_name' => $user_name,
            'phone' => $phone,
            'pic_front' => $pic_front,
            'pic_back' => $pic_back,
            'create_time' => time(),
        );
        $result = Db::name('verify_identity_info')->insert($logData);
        if ($result) {
            Db::commit();
            return $this->successResult('设置成功');
        } else {
            Db::rollback();
            return $this->failResult('设置失败', 301);
        }

        return $this->successResult('success');
    }


}