<?php


namespace app\admin\controller;

use app\common\logic\saas\AppLogic;
use think\AjaxPage;

class App extends Base
{

    /**
     * 关联小程序
     */
    public function bind_miniapp()
    {
        $serviceId = input('service_id/d', 0);
        $miniappId = input('miniapp_id/d', 0);

        $appLogic = new AppLogic;
        $return = $appLogic->bindMiniapp($this->user_id, $serviceId, $miniappId);
        $this->ajaxReturn($return);
    }

    /**
     * 解绑小程序
     */
    public function unbind_miniapp()
    {
        $saas_cfg = $GLOBALS['SAAS_CONFIG'];
        $serviceId = $saas_cfg['service_id'];
        $appLogic = new AppLogic();
        $return = $appLogic->unbindMiniapp($serviceId);
        $this->ajaxReturn($return);
    }
}