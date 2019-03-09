<?php

namespace app\admin\validate;

use think\Validate;

class GoodsCategory extends Validate
{
    // 验证规则
    protected $rule = [
        'name' => 'require|unique:goods_category,name^parent_id',
        'mobile_name' => 'require|unique:goods_category,mobile_name^parent_id',
        'sort_order' => 'require|number',
        'commission_rate' => 'require|number|between:0,100',
    ];
    //错误信息
    protected $message = [
        'name.require' => '分类名称必须填写',
        'name.unique' => '分类名称重复',
        'mobile_name.require' => '手机分类名称必须填写',
        'mobile_name.unique' => '手机分类名称重复',
        'sort_order.number' => '排序必须为数字',
        'sort_order.require' => '排序必须填写',
        'commission_rate.number' => '分佣比例必须为数字',
        'commission_rate.require' => '分佣比例必须填写',
    ];

}
