<?php

namespace app\mobile\controller;

use think\Page;
use think\db;

class Push extends MobileBase
{
    /**
     * 地推积分充值
     */
    public function recharge()
    {
        $user_id = session('user.user_id');
        if(IS_POST){
            $data = array('recharge_open', 'points_rate');
            $config = M('config')->where('name', ['in', $data])->where('inc_type', 'recharge')
                    ->column('name, value');
            if(!$config['recharge_open']){
                $this->error('积分充值功能已关闭');
                exit;
            }
            if ($_FILES['recharge_input']['tmp_name']) {
                $file = $this->request->file('recharge_input');
                $image_upload_limit_size = config('image_upload_limit_size');
        		$validate = ['size'=>$image_upload_limit_size,'ext'=>'jpg,png,gif,jpeg'];
                $dir = UPLOAD_PATH.'recharge_pic/';
        		if (!($_exists = file_exists($dir))){
                    if (!($_exists = file_exists(UPLOAD_PATH))){
                        mkdir(UPLOAD_PATH);
                    }
        			$isMk = mkdir($dir);
                }
        		$parentDir = date('Ymd');
        		$info = $file->validate($validate)->move($dir, true);
        		if($info){
        			$post['recharge_pic'] = '/'.$dir.$parentDir.'/'.$info->getFilename();
        		}else{
                    //上传错误提示错误信息
                    $this->error($file->getError());
                    exit;
        		}
            }
            $post['user_id'] = $user_id;
            $post['user_money'] = I('account');
            $post['create_time'] = time();
            $post['exchange_integral'] = bcmul($config['points_rate'], $post['user_money'], 2);
            $result = M('recharge_points')->insert($post);
            if($result){
                //微信消息推送通知
                $this->success('提交成功!');
                exit;
            }else{
                $this->error('提交失败!');
                exit;
            }
        }
        $integral_push = M('users')->where('user_id', $user_id)->value('integral_push');
        $this->assign('integral_push', $integral_push);
        return $this->fetch();
    }
}
