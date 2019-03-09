<?php

namespace app\common\model;

use think\Model;

class WxKeyword extends Model
{
    //关键字类型
    const TYPE_AUTO_REPLY = 'auto_reply';

    public function wxReply()
    {
        return $this->belongsTo('WxReply', 'pid', 'id');
    }
}