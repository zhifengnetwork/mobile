<?php


namespace app\common\model\saas;

class AppService extends ServiceBase
{
    //安装状态
    const INSTALL_UNDO      = 0;    //未安装
    const INSTALL_DONE      = 1;    //已安装
    const INSTALL_DOING     = 2;    //正在安装
    const INSTALL_FAIL      = 3;    //安装失败

    static public function getAllInstallStatus()
    {
        return [
            self::INSTALL_UNDO  => '未安装',
            self::INSTALL_DONE  => '已安装',
            self::INSTALL_DOING => '正在安装',
            self::INSTALL_FAIL  => '安装失败',
        ];
    }

    public function getInstallStatusNameAttr($value, $data)
    {
        $statuses = self::getAllInstallStatus();
        if (key_exists($data['install_status'], $statuses)) {
            return $statuses[$data['install_status']];
        }

        return '未知安装状态';
    }

    public function app()
    {
        return $this->belongsTo('App', 'app_id', 'app_id');
    }

    public function miniapp()
    {
        return $this->hasOne('Miniapp', 'miniapp_id', 'miniapp_id');
    }

    public function server()
    {
        return $this->belongsTo('Server', 'server_id', 'server_id');
    }

    public function serverSpec()
    {
        return $this->belongsTo('ServerSpec', 'server_spec_id', 'spec_id');
    }

    public function user()
    {
        return $this->belongsTo('app\common\model\saas\Users', 'user_id', 'user_id');
    }
}