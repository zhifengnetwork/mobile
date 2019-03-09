<?php

namespace app\common\model\saas;

use think\Model;

abstract class ServiceBase extends SaasModel
{
    //服务状态
    const STATUS_NORMAL     = 0;
    const STATUS_EXPIRED    = 1;
    const STATUS_FROZEN     = 2;

    /**
     * 获取所有订单状态
     * @return array
     */
    static public function getAllStatus()
    {
        return [
            static::STATUS_NORMAL   => '正常',
            static::STATUS_EXPIRED  => '已到期',
            static::STATUS_FROZEN   => '冻结中'
        ];
    }

    public function getStatusNameAttr($value, $data)
    {
        $statuses = static::getAllStatus();
        if (key_exists($data['status'], $statuses)) {
            return $statuses[$data['status']];
        }

        return '未知状态';
    }

    public function getExtendTypeNameAttr($value, $data)
    {
        $types = ExtendBase::getAllExtendType();
        if (key_exists($data['extend_type'], $types)) {
            return $types[$data['extend_type']];
        }

        return '未知类型';
    }
}