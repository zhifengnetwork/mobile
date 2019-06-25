<?php

namespace app\live\validate;

use    think\Validate;

class Apply extends Validate
{
    protected $rule = [
        'mobile' => 'require|length:11|checkMobile',
        'name' => 'require|max:50|unique:user_verify_identity_info',
    ];

    protected $message = [
        'mobile.require' => '手机号码必填',
        'mobile.length' => '手机号码长度不能超过11位',
        'mobile.checkMobile' => '手机号码格式错误',
        'name.require' => '用户名必填',
        'name.length' => '长度不能超过50位',
        'name.unique' => '用户名已存在',
    ];

    protected $scene = [
        'upload' => ['mobile', 'name'],
    ];

    /**
     * 检查手机格式
     * @param $value |验证数据
     * @return bool
     */
    protected function checkMobile($value)
    {
        return check_mobile($value);
    }
}