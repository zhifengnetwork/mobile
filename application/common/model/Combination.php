<?php

namespace app\common\model;

use app\common\logic\FlashSaleLogic;
use app\common\logic\GroupBuyLogic;
use think\Model;
use app\common\logic\PromGoodsLogic;

class Combination extends Model
{
    //自定义初始化
    protected static function init()
    {
        //TODO:自定义的初始化
    }

    public function CombinationGoods()
    {
        return $this->hasMany('CombinationGoods', 'combination_id', 'combination_id')->order('is_master desc');
    }
    public function CombinationGoodsCount()
    {
        return $this->hasMany('CombinationGoods', 'combination_id', 'combination_id');
    }
    public function setStartTimeAttr($value)
    {
        return strtotime($value);
    }

    public function setEndTimeAttr($value)
    {
        return strtotime($value);
    }
}
