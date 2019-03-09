<?php


namespace app\common\model\saas;

class App extends ExtendBase
{
    public function modules()
    {
        return $this->hasMany('Module', 'app_id', 'app_id');
    }

    public function baseAppService()
    {
        return $this->hasOne('AppService', 'service_id', 'base_service_id');
    }

    public function getBaseTerminalsAttr($value)
    {
        return $value ? json_decode($value, true) : [];
    }

    public function setBaseTerminalsAttr($value)
    {
        return $value ? json_encode($value, JSON_UNESCAPED_UNICODE) : '';
    }

    public function getBaseFeaturesAttr($value)
    {
        return $value ? json_decode($value, true) : [];
    }

    public function setBaseFeaturesAttr($value)
    {
        return $value ? json_encode($value, JSON_UNESCAPED_UNICODE) : '';
    }
}