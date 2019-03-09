<?php

namespace app\common\model;
use think\Model;
class MessagePrivate extends Model
{
    public function userMessage()
    {
        return $this->hasOne('userMessage', 'message_id', 'message_id');
    }

    public function getSendTimeTextAttr($value, $data)
    {
        return time_to_str($data['send_time']);
    }
    public function getHomeUrlAttr($value, $data)
    {
        return '';
    }
    public function getFinishedAttr($value, $data)
    {
        return false;
    }
    public function getMobileUrlAttr($value, $data)
    {
        return '';
    }
    public function getOrderTextAttr($value, $data)
    {
        return '';
    }
    public function getStartTimeAttr($value, $data)
    {
        return true;
    }
}
