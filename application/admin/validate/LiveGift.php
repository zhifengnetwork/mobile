<?php

namespace app\admin\validate;

use think\Validate;

class LiveGift extends Validate
{
    // 验证规则
    protected $rule = [
        ['name', 'require|unique:live_gift'],
        ['image', 'require'],
        ['price', 'require|regex:\d{1,10}(\.\d{1,2})?$'],
        ['is_show', 'between:0,1'],
        ['sort', 'number|between:0,10000'],
        ['desc', 'max:255'],

    ];
    //错误信息
    protected $message = [
        'name.require' => '名称必填',
        'name.unique' => '已存在相同标签名称',
        'image.require' => '图片必填',
        'price.require' => '价格必填',
        'price.regex' => '价格格式不对',
        'is_show.between' => '是否显示选择错误',
        'sort.number' => '排序必须是数字',
        'sort.between' => '排序在0-10000之间',
        'desc.max' => '描述不超过255个字符',
    ];
    //验证场景
    protected $scene = [
        'edit' => [
            'name',
            'image',
            'price',
            'is_show',
            'sort',
            'desc'
        ],
    ];
}