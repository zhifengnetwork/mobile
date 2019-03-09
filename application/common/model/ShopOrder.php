<?php

namespace app\common\model;

use think\Db;
use think\Model;

class ShopOrder extends Model
{
    //自定义初始化
    protected static function init()
    {
        //TODO:自定义的初始化
    }

    public function order()
    {
        return $this->hasOne('Order', 'order_id', 'order_id');
    }

    public function shop()
    {
        return $this->hasOne('Shop', 'shop_id', 'shop_id');
    }
}
