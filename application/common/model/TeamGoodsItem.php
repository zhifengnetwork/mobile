<?php

namespace app\common\model;

use think\Model;
use think\Db;

class TeamGoodsItem extends Model
{
    public function specGoodsPrice()
    {
        return $this->hasOne('specGoodsPrice', 'item_id', 'item_id');
    }
    public function goods(){
        return $this->hasOne('goods', 'goods_id', 'goods_id');
    }
    public function teamActivity(){
        return $this->hasOne('teamActivity', 'team_id', 'team_id');
    }
}
