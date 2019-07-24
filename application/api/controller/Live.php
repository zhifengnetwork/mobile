<?php
/**
 * 用户API
 */
namespace app\api\controller;
use app\common\model\Users;
use app\common\logic\UsersLogic;
use think\Db;
use think\Page;
use app\common\logic\Message;
use app\common\model\UserMessage;
use app\common\logic\wechat\WechatUtil;
use app\common\logic\ShareLogic;

class Live extends ApiBase
{

   /**
    * 开始直播
    */
    public function beginlive()
    {
//        解密token
        $user_id = $this->get_user_id();
        if(!$user_id){
            $this->ajaxReturn(['status' => -1 , 'msg'=>'用户不存在','data'=>'']);
        }

       //设置封面
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

                // 存着 地址
                $rel = M('user_video')->field('pic_fengmian')->where(['user_id'=>$user_id])->find();
                if($rel){
                    $res = M('user_video')->where(['user_id'=>$user_id])->update(['pic_fengmian'=>$data]);
                }   else{
                    $res = M('user_video')->where(['user_id'=>$user_id])->insert(['pic_fengmian'=>$data]);
                }


                $img['pic_fengmian'] = $data;
                if($res){
                    $this->ajaxReturn(['status' => 0 , 'msg'=>'上传成功','data'=>$img]);
                }else{
                    $this->ajaxReturn(['status' => -2 , 'msg'=>'上传失败','data'=>$file->getError()]);
                }
            }else{
                $this->ajaxReturn(['status' => -2 , 'msg'=>'上传失败','data'=>$file->getError()]);
            }

        }

    }

    /**
     * 申请直播
     */
    public function apply()
    {
        //解密token
//        $user_id = $this->get_user_id();
        $user_id = 57601;
        if(!$user_id){
            $this->ajaxReturn(['status' => -1 , 'msg'=>'用户不存在','data'=>'']);
        }

        if($user_id!="") {
//            //选择会员
//            $levelname = M('user_level')->field('level_name')->select();
//
//            foreach ($levelname as $k=>$v){
//                $levelname[$k]=$v['level_name'];
//            }
//
//            foreach ($levelname as $k=>$v){
//                $levelname['name'][$k] = $v;
//            }
//
//            $data['level_name'] = $levelname;


        if (IS_POST) {

            $mobile = I('post.mobile', '');
            $username = I('post.username', '');
            $id = I('post.id', '');
            $level = I('post.level_id', '');

            $data['mobile'] = $mobile;
            $data['username'] = $username;
            $data['user_id'] = $id;
            $data['level_id'] = $level;


            // 身份证
            $files = request()->file('idcardpic');
            $save_url = UPLOAD_PATH.'idcard/' . date('Y', time()) . '/' . date('m-d', time());
            if($files) {
                foreach ($files as $file) {
                    // 移动到框架应用根目录/public/uploads/ 目录下
                    $image_upload_limit_size = config('image_upload_limit_size');
                    $info = $file->rule('uniqid')->validate(['size' => $image_upload_limit_size, 'ext' => 'jpg,png,gif,jpeg'])->move($save_url);
                    if ($info) {
                        // 成功上传后 获取上传信息
                        // 输出 jpg
                        $comment_img[] = '/' . $save_url . '/' . $info->getFilename();
                    } else {
                        // 上传失败获取错误信息
                        $this->ajaxReturn(['status' =>-1,'msg' =>$file->getError()]);
                    }
                }
            }
            if (!empty($comment_img)) {
                $data['idcardpic'] = serialize($comment_img);
            }
        }
//        存入表
            $rel = M('user_videoinfo')->field('user_id')->where(['user_id'=>$user_id])->find();

            if($rel){
                $res = M('user_videoinfo')->where(['user_id'=>$user_id])->update($data);
            }else{
                $res = M('user_videoinfo')->insert($data);
            }
        }
        if ($res){
            $this->ajaxReturn(['status' => 0, 'msg' => '提交成功', 'data' => $data]);
        }else{
            $this->ajaxReturn(['status' => -2 , 'msg'=>'提交失败','data'=>$file->getError()]);
        }
    }
}
