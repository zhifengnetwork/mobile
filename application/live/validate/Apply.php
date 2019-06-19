<?php

namespace app\api\validate;

use	think\Validate;

class Apply extends Validate
{
    protected $rule = [
        'phone'=>'require|length:11',
        'user_name'=>'require|max:50',
        'level' => 'require|length:1,2|regex:/^.*(?=.*[0-9]).*$/',
        'img_front' => 'fileExt:jpg,jpeg,png,gif,JPG,JPEG,PNG|fileSize:1048576',
        'img_back' => 'fileExt:jpg,jpeg,png,gif,JPG,JPEG,PNG|fileSize:1048576',
    ];

    protected $message = [
        'phone.require' => '手机号码必填',
        'phone.length' => '长度不能超过11位',
        'user_name.require' => '用户名必填',
        'user_name.length' => '长度不能超过50位',
        'level.require' => '等级必须选择',
        'level.length' => '长度不能超过2位',
        'img_front.fileExt' => '正面不能为空',
        'img_back.fileExt' => '反面不能为空',
    ];

    protected $scene = [
        'upload' => ['phone', 'level', 'user_name','img_front','img_back'],

    ];

}