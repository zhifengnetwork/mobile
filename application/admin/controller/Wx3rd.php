<?php

namespace app\admin\controller;

use app\common\logic\saas\AppLogic;
use app\common\model\saas\Miniapp;
use think\Session;
use think\Controller;
use app\common\logic\saas\Wx3rdLogic;

/**
 * 微信第三方平台回调相关
 */
class Wx3rd extends controller
{
    private $wx3rdLogic = null;
    
    public function __construct()
    {
        parent::__construct();
        ob_clean();
        $this->wx3rdLogic = new Wx3rdLogic();
    }
    
    /**
     * 处理：授权事件接收，配置在：第三方平台的授权事件接收URL
     */
    public function handle_auth_msg()
    {
        //file_put_contents('test.log', date('Y-m-d H:i:s')." -- test \n", FILE_APPEND);
        $this->wx3rdLogic->handleAuthMessage();

        $this->wx3rdLogic->echoMsgSuccess();
    }

    /**
     * 普通消息推送,配置在：第三方平台的公众号等事件接收URL
     */
    public function handle_public_account_msg()
    {
        $appid = input('appid', '');
        //file_put_contents('./wechat-msg-appid.log', date('Y-m-d H:i:s').' -- '.$appid."\n", FILE_APPEND);

        $this->wx3rdLogic->handleCommonMessage($appid);

        $this->wx3rdLogic->echoMsgSuccess();
    }
    
    /**
     * 进入授权后的页面
     */
    public function authorization()
    {
        Session::start();
        $userId = session('client_id');
        $authCode = input('auth_code');
        //$authCodeExpires = input('expires_in');

        $return = $this->wx3rdLogic->authByUser($userId, $authCode);
        if ($return['status'] != 1) {
            $this->error($return['msg'], url('Miniapp/server'));
        }
        $saas_cfg = $GLOBALS['SAAS_CONFIG'];
        $appLogic = new AppLogic();
        $miniapp = Miniapp::get(['user_id' => $userId]);
        $return = $appLogic->bindMiniapp($userId, $saas_cfg['service_id'], $miniapp['miniapp_id']);
        
        $this->success($return['msg'], url('Miniapp/server'), '', 1);
    }
}
