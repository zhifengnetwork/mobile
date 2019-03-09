<?php

namespace app\common\model;
use think\Model;
class GoodsAttr extends Model {

    public function goodsAttribute(){
        return $this->hasOne('goodsAttribute','attr_id','attr_id');
    }
}
