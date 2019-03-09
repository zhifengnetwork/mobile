<?php

namespace app\common\model;

use think\Db;
use think\Model;

class FreightConfig extends Model
{
    //自定义初始化
    protected static function init()
    {
        //TODO:自定义的初始化
    }

    public function freightRegion()
    {
        return $this->hasMany('FreightRegion', 'config_id', 'config_id');
    }

}
