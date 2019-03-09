<?php

namespace app\common\model;

use think\Model;

class Shopper extends Model
{
    public function users(){
        return $this->hasOne('users','user_id','user_id');
    }

    public function shop()
    {
        return $this->hasOne('shop','shop_id','shop_id');
    }
}
