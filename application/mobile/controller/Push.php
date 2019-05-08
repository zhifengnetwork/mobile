<?php

namespace app\mobile\controller;

use think\Page;
use think\db;

class Push extends MobileBase
{
    /**
     * 地推管理首页
     */
    public function index()
    {
        $user_id  = session('user.user_id');
        $integral = M('users')
                  ->where('user_id', $user_id)
                  ->value('integral_push');
        $this->assign('integral', $integral);
        return $this->fetch();
    }

    /**
     * 地推积分充值记录
     */
    public function recharge_record()
    {
        $user_id = session('user.user_id');
        $condiction = array('user_id' => $user_id);
        $record = M('recharge_points')
                ->where($condiction)
                ->field('create_time, user_money, status')
                ->order('id DESC')
                ->select();
        $this->assign('record', $record);
        return $this->fetch();
    }

    /**
     * 地推积分充值
     */
    public function recharge()
    {
        if(IS_POST){
            $data = I('post.');
            $data = $data['data'];

            $arr  = array(
                'inc_type' => 'recharge',
                'name' => ['in',['recharge_open', 'points_rate']],
            );
            $config = M('config')->where($arr)->column('name, value');
            //判断地推积分充值功能是否开启
            if(!$config['recharge_open']){
                $this->ajaxReturn(['status'=>0, 'msg'=>'积分充值功能已关闭']);
                exit;
            }
            
            //网站限制大小
            $max_size    = config('image_upload_limit_size');
            $base64_data = explode(',', $data['voucher_img']);
            $base64_data = $base64_data[1];
            $filepath    = UPLOAD_PATH.'recharge_pic/';
            $result = $this->upBase64($max_size, $base64_data, $filepath);
            if($result['status'] != 1){
                $this->ajaxReturn(['status'=>0, 'msg'=>$result['msg']]);
                exit;
            }
            
            $user_id = session('user.user_id');
            $insert_data['user_id'] = $user_id;
            $insert_data['create_time'] = time();
            $insert_data['recharge_pic'] = $result['data'];
            $insert_data['user_money'] = $data['choice_number'];
            $insert_data['exchange_integral'] = round($config['points_rate'] * $data['choice_number'], 2);
            $result = M('recharge_points')->insert($insert_data);
            if($result){
                //微信消息推送通知
                $this->ajaxReturn(['status'=>1, 'msg'=>'提交成功!']);
                exit;
            }else{
                $this->ajaxReturn(['status'=>0, 'msg'=>'提交失败!']);
                exit;
            }
        }
        return $this->fetch();
    }

    /*
	 * 处理base64编码的图片上传
	 * 例如：涂鸦图片上传
	*/
	private function upBase64($max_size, $base64_data, $dirname){
        $img = base64_decode($base64_data);

	    $file['filesize'] = strlen($img);
	    $file['oriName'] = date('YmdHis') . '.png';
	    $file['ext'] = strtolower(strrchr($file['oriName'],'.'));
	    $file['name'] = uniqid().$file['ext'];
	    $file['fullName'] = $dirname.$file['name'];
	    $fullName = $file['fullName'];

 	    //检查文件大小是否超出限制
	    if($file['filesize'] >= ($max_size)){
            $data = ['status'=>0, 'msg'=>'文件大小超出网站限制'];
            return $data;
	    }

	    //创建目录失败
	    if(!file_exists($dirname) && !mkdir($dirname,0777,true)){
            $data = ['status'=>0, 'msg'=>'目录创建失败'];
            return $data;
	    }else if(!is_writeable($dirname)){
	        $data = ['status'=>0, 'msg'=>'目录创建失败'];
            return $data;
	    }

	    //移动文件
        if(!(file_put_contents($fullName, $img) && file_exists($fullName))){ 
            //移动失败
            $data = ['status'=>0, 'msg'=>'写入文件内容错误'];
            return $data;
	    }else{ 
            $data = ['status'=>1, 'msg'=>'上传成功', 'data'=>substr($file['fullName'],1)];
            return $data;
	    }

	}
}
