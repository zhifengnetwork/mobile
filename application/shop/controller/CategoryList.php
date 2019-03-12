<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/3/8 0008
 * Time: 14:04
 */

// namespace app\mobile\controller;
namespace app\shop\controller;
use app\common\logic\ActivityLogic;
use app\common\model\Combination;
use app\common\model\Category;
use app\common\util\TpshopException;
use think\AjaxPage;
use think\Page;
use think\Db;

class CategoryList extends MobileBase
{
    public function index(){

        return $this->fetch();

    }

    /**
    *分类列表页
    */
    public function categoryList(){

        //获取要访问的一级分类的ID  如果没有传ID默认展示为你推荐栏目
        $id=I(id,31);

        $category=new Category();

        //获取所有要展示的一级分类
        $categoryList = $category->get_first_level_category();
        //获取当前要展示的分类的2，3级信息
        $categorys=$category->get_children_category($id);
        foreach($categorys as $key=>$value){
            $categorys[$key]['children']=$category->get_children_category($value['id']);
        }

        $this->assign('categoryList',$categoryList);
        $this->assign('categorys',$categorys);

        $this->fetch();
    }
}