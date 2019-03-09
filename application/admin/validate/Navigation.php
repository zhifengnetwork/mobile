<?php


namespace app\admin\validate;

use think\Validate;

/**
 * Description of Article
 *
 * @author Administrator
 */
class Navigation extends Validate
{
    //验证规则
    protected $rule = [
        'name'      => 'require',
        'url'       => 'require',
        'sort'      => 'require|number',
    ];
    
    //错误消息
    protected $message = [
        'name.require'      => '导航名称不能为空',
        'cat_id.checkName'  => '所属分类必须选择',
        'sort.require'      => '排序不能为空',
        'sort.number'       => '排序值错误',
        'url.require'       => '链接地址不能为空',
        'url.url'           => '链接格式错误'
    ];
    
    //验证场景
    protected $scene = [
        'edit' => ['name', 'url', 'sort'],
        'add'  => ['name', 'url', 'sort'],
    ];

}
