<?php

namespace app\common\model;

use think\Model;

/**
 * Class UserVideo
 * @package app\common\model
 */
class UserVideo extends Model
{
    private $_status = [
        0 => '未开始',
        1 => '直播中',
        2 => '已结束'
    ];

    protected static function init()
    {
    }

    // 认证信息
    public function Info()
    {
        return $this->hasOne('UserVerifyIdentityInfo', 'user_id', 'user_id');
    }

    public function getStatusTextAttr($value, $data)
    {
        return $this->_status[$data['status']];
    }


    public function getGoodsAttr()
    {
        $list = [];
        $ids = $this->good_ids ? json_decode($this->good_ids) : [];
        if (count($ids) > 0) {
            foreach ($ids as $id) {
                $list[] = Goods::get(['goods_id' => $id])->column('goods_id,goods_name,original_img,store_count,market_price,shop_price,cost_price');
            }
        }
        return $list;
    }
}
