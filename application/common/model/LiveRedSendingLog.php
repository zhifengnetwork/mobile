<?php

namespace app\common\model;

use think\Model;

class LiveRedSendingLog extends Model
{
    protected $type = [
        'user_id' => 'integer',
        'to_user_id' => 'integer',
        'room_id' => 'string',
        'money' => 'float',
        'data' => 'string'
    ];

    protected static function init()
    {
        //TODO:自定义的初始化
    }

    public function Video()
    {
        return $this->hasOne('UserVideo', 'room_id', 'room_id');
    }

    public function User()
    {
        return $this->hasOne('Users', 'user_id', 'user_id');
    }

    public function ToUser()
    {
        return $this->hasOne('Users', 'user_id', 'to_user_id');
    }

}
