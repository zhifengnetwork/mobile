<?php

namespace app\common\model;

use think\Model;
use traits\model\SoftDelete;

class LiveGift extends Model
{
    use SoftDelete;
    protected static $deleteTime = 'delete_time';  // 5.2版本之前必须用static定义
    protected $type = [
        'id' => 'integer',
        'name' => 'string',
        'image' => 'string',
        'price' => 'float',
        'is_show' => 'integer',
        'desc' => 'string'
    ];

    protected static function init()
    {
        self::beforeInsert(function ($data) {
            $data->create_time = time();
        });
    }

}
