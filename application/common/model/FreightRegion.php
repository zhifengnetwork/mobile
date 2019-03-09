<?php

namespace app\common\model;

use think\Db;
use think\Model;

class FreightRegion extends Model
{
    //自定义初始化
    protected static function init()
    {
        //TODO:自定义的初始化
    }
    public function region()
    {
        return $this->hasOne('region', 'id', 'region_id');
    }
    public function freightConfig()
    {
        return $this->hasOne('FreightConfig', 'config_id', 'config_id');
    }

}
