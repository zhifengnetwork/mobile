<?php

namespace app\common\model;

use think\Db;
use think\Model;

class UserMessage extends Model
{
    public function messageActivity()
    {
        return $this->hasOne('messageActivity', 'message_id', 'message_id');
    }
    public function messageLogistics()
    {
        return $this->hasOne('messageLogistics', 'message_id', 'message_id');
    }
    public function messageNotice()
    {
        return $this->hasOne('messageNotice', 'message_id', 'message_id');
    }
    public function messagePrivate()
    {
        return $this->hasOne('messagePrivate', 'message_id', 'message_id');
    }           
}
