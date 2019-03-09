<?php

namespace app\common\model;

use think\Db;
use think\Model;

class UserMsgTpl extends Model
{
    public function getEditButtonAttr($value, $data)
    {
        if (strpos($data['mmt_code'], 'activity')){
            return false;
        }
        $return_flag = true;
        switch ($data['mmt_code']) {
            case 'coupon_will_expire_notice':
            case 'coupon_use_notice':
            case 'coupon_get_notice':
            case 'deliver_goods_logistics':
            case 'evaluate_logistics':
            case 'virtual_order_logistics':
                $return_flag = false;
                break;
            default:
                $return_flag = true;
                break;
        }
        return $return_flag;

    }
}
