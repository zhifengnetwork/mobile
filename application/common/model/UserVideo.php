<?php

namespace app\common\model;

use think\Model;

class UserVideo extends Model
{
    protected static function init()
    {
        //TODO:自定义的初始化
    }

    // 主播姓名
    public function getInfoNameAttr($value, $data)
    {
        return UserVerifyIdentityInfo::getNameByUserId($data['user_id']);
    }
}
