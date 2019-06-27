<?php

namespace app\common\model;

use think\Model;

class RedDetail extends Model
{
    private $_type = [
        0 => '未领取', 1 => '已领取', 2 => '超时退回'
    ];
    protected $type = [
        'm_id' => 'integer',
        'get_uid' => 'integer',
        'room_id' => 'string',
        'money' => 'float',
        'get_time' => 'integer',
        'type' => 'integer',
        'out_time' => 'integer',
        'get_award_money' => 'float',
        'status' => 'integer'
    ];

    protected static function init()
    {
        //TODO:自定义的初始化
    }

    public function getTypeTextAttr($value, $data)
    {
        return $this->_type[$data['type']];
    }

    public function Master()
    {
        return $this->hasOne('RedMaster', 'id', 'm_id');
    }

    public function User()
    {
        return $this->hasOne('Users', 'user_id', 'get_uid');
    }

}
