<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/6/14 0014
 * Time: 14:40
 */

namespace app\live\controller;


use app\mobile\controller\MobileBase;
use app\common\model\Users as UserModel;

class Base extends MobileBase
{
    public $level = [
        0=>'普通会员',
        1=>'总代',
        2=>'经理',
        3=>'总监',
        4=>'总裁',
        5=>'金牌合伙人'
    ];
    public $url = '';
    public $user_id = 0;
    public $user = array();

    public function _initialize(){
        parent::_initialize();
        if (session('?user')) {
            $User = new UserModel();
            $session_user = session('user');
            $this->user = $User->where('user_id', $session_user['user_id'])->find();
            if(!empty($this->user->auth_users)){
                $session_user = array_merge($this->user->toArray(), $this->user->auth_users[0]);
                session('user', $session_user);  //覆盖session 中的 user
            }
            $this->user_id = $this->user['user_id'];
            $this->assign('user', $this->user); //存储用户信息0
        }
        $nologin = array(
            'login', 'pop_login', 'do_login', 'logout', 'verify', 'set_pwd', 'finished',
            'verifyHandle', 'reg', 'send_sms_reg_code', 'find_pwd', 'check_validate_code',
            'forget_pwd', 'check_captcha', 'check_username', 'send_validate_code', 'express' , 'bind_guide', 'bind_account','bind_reg'
        );
        $is_bind_account = tpCache('basic.is_bind_account');
        if (!$this->user_id && !in_array(ACTION_NAME, $nologin)) {
            if(strstr($_SERVER['HTTP_USER_AGENT'],'MicroMessenger') && $is_bind_account){
                header("location:" . U('Mobile/User/bind_guide'));//微信浏览器, 调到绑定账号引导页面
            }else{
                header("location:" . U('Mobile/User/login'));
            }
            exit;
        }

        $order_status_coment = array(
            'WAITPAY' => '待付款 ', //订单查询状态 待支付
            'WAITSEND' => '待发货', //订单查询状态 待发货
            'WAITRECEIVE' => '待收货', //订单查询状态 待收货
            'WAITCCOMMENT' => '待评价', //订单查询状态 待评价
        );
        $this->assign('order_status_coment', $order_status_coment);


        //判断头像是否为空，空就补头像
        if( $this->user['head_pic'] == null || $this->user['head_pic'] == ''){

            $openid = $this->user['openid'];
            $access_token = access_token();
            $url = 'https://api.weixin.qq.com/cgi-bin/user/info?access_token='.$access_token.'&openid='.$openid.'&lang=zh_CN';
            $resp = httpRequest($url, "GET");
            $res = json_decode($resp, true);

            $head_pic = $res['headimgurl'];
            if($head_pic){
                //得到头像
                M('users')->where(['openid'=>$this->user['openid']])->update(['head_pic'=>$head_pic]);
                $this->user['head_pic'] = $head_pic;
            }

        }
        $http_type = ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https')) ? 'https://' : 'http://';
        $this->url=$http_type.$_SERVER['SERVER_NAME'];
    }

    public function successResult($data = [])
    {
        return $this->getResult(200, 'success', $data);
    }

    public function failResult($message, $status = 301)
    {
        return $this->getResult($status, $message, false);
    }

    public function getResult($status, $message, $data)
    {
        return json(
            [
                'status' => $status,
                'msg' => $message,
                'data' => $data,
            ]
        );exit;
    }
}