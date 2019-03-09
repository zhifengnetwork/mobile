<?php

namespace app\common\model;

use app\common\logic\FlashSaleLogic;
use app\common\logic\GroupBuyLogic;
use think\Model;
use app\common\logic\PromGoodsLogic;

class CombinationGoods extends Model
{
    //自定义初始化
    protected static function init()
    {
        //TODO:自定义的初始化
    }

    public function goods()
    {
        return $this->hasOne('Goods', 'goods_id', 'goods_id');
    }

    public function specGoodsPrice()
    {
        return $this->hasOne('SpecGoodsPrice', 'item_id', 'item_id');
    }
    public function combination()
    {
        return $this->hasOne('Combination','combination_id','combination_id');
    }
    public function getIsMasterTextAttr($value,$data)
    {
        if($data['is_master'] == 1){
            return '主商品';
        }else{
            return '副商品';
        }
    }
}
