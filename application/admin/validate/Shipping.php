<?php

namespace app\admin\validate;

use think\Validate;

/**
 * Description of Article
 *
 * @author Administrator
 */
class Shipping extends Validate
{
    //验证规则
    protected $rule = [
        'shipping_name'                 => 'require',
        'shipping_code'                 => ['require','unique'=>'shipping,shipping_code','regex'=>'/[a-zA-Z]{2,20}/'],
        'shipping_desc'                 => 'max:255',
        'shipping_logo'                 => 'require',
        'template_width'                => 'number',
        'template_height'               => 'number',
        'template_offset_x'             => 'number',
        'template_offset_y'             => 'number',
        'template_img'                  => 'require',
    ];

    //错误消息
    protected $message = [
        'shipping_name.require'         => '物流公司名称不能为空',
        'shipping_code.require'         => '物流公司编码不能为空',
        'shipping_code.unique'          => '已有相同物流公司编码',
        'shipping_code.regex'           => '物流编码必须2-20位字母组成',
        'shipping_desc.max'             => '字符不能大于255个',
        'shipping_logo.require'         => '请上传物流公司logo',
        'template_width.number'         => '运单模板宽度请输入数字',
        'template_height.number'        => '运单模板高度度请输入数字',
        'template_offset_x.number'      => '运单模板左偏移量请输入数字',
        'template_offset_y.number'      => '运单模板上偏移量请输入数字',
        'template_img.require'          => '请上传运单模板',
    ];

}
