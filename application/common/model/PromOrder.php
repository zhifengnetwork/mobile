<?php

namespace app\common\model;
use think\Model;
class PromOrder extends Model {
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

    public function getPromDetailAttr($value,$data)
    {
        switch ($data['type']){
            case 1:
                $title = '订单满'.$data['money'].'元减'.$data['expression'].'元';
                break;
            case 2:
                $title = '订单满'.$data['money'].'元送'.$data['expression'].'积分';
                break;
            case 3:
                $coupon = db('coupon')->where('id', $data['expression'])->find();
                $title = '订单满' . $data['money'] . '元送' . $coupon['name'] . '优惠券￥' . $coupon['money'];
                break;
            default:
                $title = '订单满'.$data['money'].'元打'.$data['expression'].'折';
        }
        return $title;
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
}
