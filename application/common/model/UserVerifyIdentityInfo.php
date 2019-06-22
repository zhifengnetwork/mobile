<?php

namespace app\common\model;

use think\Model;

class UserVerifyIdentityInfo extends Model
{
    /**
     * 最新审核日志
     * @var UserVerifyIdentityLog
     */
    private $_latestLog;

    protected static function init()
    {
        //TODO:自定义的初始化
    }

    public static function getNameByUserId($userId = 0)
    {
        if ($userId > 0 && ($data = self::get(['user_id' => $userId]))) {
            return $data['name'];
        }
        return '';
    }

    // 最新审核日志的原因
    public function getLogReasonAttr()
    {
        if ($log = $this->getLatestLog()) {
            return $log->reason_cn;
        }
    }

    // 最新审核日志的时间
    public function getVerifyTimeAttr()
    {
        if ($log = $this->getLatestLog()) {
            return $log->create_time;
        }
    }

    public function getLatestLog()
    {
        if (!isset($this->_latestLog)) {
            $this->_latestLog = (new UserVerifyIdentityLog)->where('verify_id', $this->id)->order('id desc')->find();
        }
        return $this->_latestLog;
    }

}
