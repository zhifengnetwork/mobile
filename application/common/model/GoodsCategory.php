<?php

namespace app\common\model;
use think\Db;
use think\Model;
class GoodsCategory extends Model {

    public function getParentListAttr($value, $data)
    {
        $parent_ids = explode('_', $data['parent_id_path']);
        array_pop($parent_ids);
        array_shift($parent_ids);
        if(count($parent_ids) > 0){
            $parent_list = Db::name('goods_category')->where('id', 'in', $parent_ids)->select();
            if($parent_list){
                return $parent_list;
            }
        }
        return [];
    }

     /**
     * @param $categoryId  分类id
     * @return  不是返回数据 就是空数组
     * 获取 获取该分类的下一级子分类
     */
    public function get_children_category($categoryId){
        //判断如果分类id不存在默认为为你推荐的id
        if($categoryId==0 || $categoryId==''){
            return array();
        }
        return Db::name('goods_category')->where(['parent_id'=>$categoryId])->select();
    }
    /**
     * @return  不是返回数据 就是空数组
     * 获取 获取所有的一级分类
     */
    public function get_first_level_category(){
        return Db::name('goods_category')->where('level', 1)->where('is_show',1)->order('sort_order','asc')->column('id,name,mobile_name,parent_id,parent_id_path,level,image');
    }
}
