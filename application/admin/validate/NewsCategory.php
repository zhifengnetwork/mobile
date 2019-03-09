<?php


namespace app\admin\validate;

use think\Validate;

/**
 * Description of Article
 *
 * @author Administrator
 */
class NewsCategory extends Validate
{
    //验证规则
    protected $rule = [
        'cat_name' => 'require|checkEmpty',
    ];
    
    //错误消息
    protected $message = [
        'cat_name.require'  => '分类名不能为空',
        'cat_id.require' => '分类id不能为空'
    ];
    
    //验证场景
    protected $scene = [
        'add'  => ['cat_name'],
        'edit' => ['cat_name', 'cat_id' => 'require|checkEdit'],
        'del'  => ['cat_id' => 'require|checkDel']
    ];
    
    protected function checkEmpty($value,$rule, $data)
    {
        if (is_string($value)) {
            $value = trim($value);
        }
        if (empty($value)) {
            return '分类名不能为空白';
        }
        $name_num = M('news_cat')->where(['cat_name'=>$value])->whereNotIn('cat_id',$data['cat_id'])->count();
        if($name_num > 0){
            return '分类名不能重复';
        }
        return true;
    }
    
    protected function checkEdit($value, $rule, $data)
    {
/*        $article_system_id = \app\admin\controller\Article::$article_system_id;
        if (array_key_exists($value, $article_system_id) && $data['parent_id'] > 1) {
            return '不可更改系统预定义分类的上级分类';
        }*/
        
        if ($value == $data['parent_id']) {
            return '所选分类的上级分类不能是当前分类';
        }
        
        $ArticleCat = new \app\admin\logic\NewsCatLogic;
        $children = array_keys($ArticleCat->article_cat_list($value, 0, false)); // 获得当前分类的所有下级分类
        if (in_array($data['parent_id'], $children)) {
            return '所选分类的上级分类不能是当前分类的子分类';
        }
        //错误时跳转到：U('Admin/Article/category',array('cat_id'=>$data['cat_id']));
        
        return true;
    }
    
    protected function checkDel($value)
    {
/*        $article_system_id = \app\admin\controller\Article::$article_system_id;
        if (array_key_exists($value, $article_system_id)){
            return '系统预定义的分类不能删除';
        }*/
        
        $res = D('news_cat')->where('parent_id', $value)->select();
        if ($res) {
            return '还有子分类，不能删除';
        }
        
        $res = D('article')->where('cat_id', $value)->select();
        if ($res) {
            return '非空的分类不允许删除';
        }
        
        return true;
    }
}
