<?php


namespace app\admin\validate;

use think\Validate;

/**
 * Description of Article
 *
 * @author Administrator
 */
class News extends Validate
{
    //验证规则
    protected $rule = [
        'title'     => 'require|checkEmpty',
        'cat_id'    => 'require|checkEmpty',
        'content'   => 'require|checkContent',
        'tags'      => 'require',
        'link'      => 'url'
    ];

    //错误消息
    protected $message = [
        'title'    => '标题不能为空',
        'content'  => '内容不能为空',
        'tags'     => '标签不能为空',
        'content.checkContent'  => '内容不能为空',
        'cat_id.require'   => '所属分类缺少参数',
        'cat_id.checkEmpty' => '所属分类必须选择',
        'article_id.checkArtcileId' => '系统预定义的文章不能删除',
        'link.url' => '链接格式错误'
    ];

    //验证场景
    protected $scene = [
        'add'  => ['title', 'cat_id', 'content','link','tags'],
        'edit' => ['title', 'cat_id', 'content','link','tags'],
        'del'  => ['article_id']
    ];

    protected function checkEmpty($value)
    {
        if (is_string($value)) {
            $value = trim($value);
        }
        if (empty($value)) {
            return false;
        }
        return true;
    }

    protected function checkContent($value,$rule,$data)
    {
        $value = strip_tags($value);
        $value = str_replace('&nbsp;', '', $value);
        $value = trim($value);
        if(empty($data['link']) && empty($value)) {
            return false;
        }
        return true;
    }

}
