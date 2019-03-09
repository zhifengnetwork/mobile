<?php

namespace app\common\model;
use think\Model;
class GroupBuy extends Model {
    public function goods(){
        return $this->hasOne('goods','goods_id','goods_id');
    }
    public function specGoodsPrice(){
        return $this->hasOne('specGoodsPrice','item_id','item_id');
    }
    //剩余团购库存
    public function getStoreCountAttr($value, $data)
    {
        return $data['goods_num'] - $data['buy_num'];
    }
    //状态描述
    public function getStatusDescAttr($value, $data)
    {
        if($data['is_end'] == 1){
            return '已结束';
        }else{
            if($data['start_time'] > time()){
                return '未开始';
            }else if ($data['start_time'] < time() && $data['end_time'] > time()) {
                return '进行中';
            }else{
                return '已过期';
            }
        }
    }

    /**
     * 是否编辑
     * @param $value
     * @param $data
     * @return int
     */
    public function getIsEditAttr($value, $data)
    {
        if ($data['is_end'] == 1 || $data['start_time'] < time()){
            return 0;
        }
        return 1;
    }
}
