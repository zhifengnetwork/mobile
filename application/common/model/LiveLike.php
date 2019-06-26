<?php

namespace app\common\model;

use think\Model;

class LiveLike extends Model
{
    public function Video()
    {
        return $this->hasOne('UserVideo', 'room_id', 'room_id');
    }

    public function User()
    {
        return $this->hasOne('Users', 'user_id', 'user_id');
    }

}
