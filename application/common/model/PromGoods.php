<?php

namespace app\common\model;
use think\Model;
class PromGoods extends Model {

    public function PromGoodsItem()
    {
        return parent::hasMany('PromGoodsItem', 'prom_id', 'id');
    }

    public function getPromDetailAttr($value,$data)
    {
        switch ($data['type']){
            case 1:
                $title = '优惠￥'.$data['expression'];
                break;
            case 2:
                $title = '促销价￥'.$data['expression'];
                break;
            case 3:
                $coupon = db('coupon')->where('id', $data['expression'])->find();
                $title = '买就送'.$coupon['name'].'优惠券￥'.$coupon['money'];
                break;
            default:
                $discount = $data['expression']/10;
                $title = $discount.'折';
        }
        return $title;
    }

    public function getPromDescAttr($value,$data)
    {
        $parse_type = array('0' => '直接打折', '1' => '减价优惠', '2' => '固定金额出售', '3' => '买就赠优惠券');
        return $parse_type[$data['type']];
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
