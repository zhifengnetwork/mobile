<?php


namespace app\common\model\saas;

use think\Model;

class Miniapp extends SaasModel
{

    public function userMiniapps()
    {
        return $this->hasMany('UserMiniapp', 'miniapp_id', 'miniapp_id');
    }

    public function appService()
    {
        return $this->belongsTo('appService', 'miniapp_id', 'miniapp_id');
    }

    public function user()
    {
        return $this->belongsTo('Users', 'user_id', 'user_id');
    }

    public function getDomainsAttr($value)
    {
        return json_decode($value, true);
    }

    public function setDomainsAttr($value)
    {
        $value = json_encode($value, JSON_UNESCAPED_UNICODE);
        return strtolower($value);
    }

    public function getCategoriesAttr($value)
    {
        return json_decode($value, true);
    }

    public function setCategoriesAttr($value)
    {
        return json_encode($value, JSON_UNESCAPED_UNICODE);
    }

    public function getTestersAttr($value)
    {
        if (!$value) {
            return [];
        }
        return explode(',', $value);
    }

    public function setTestersAttr($value)
    {
        if (!$value) {
            return '';
        }
        return implode(',', $value);
    }
}