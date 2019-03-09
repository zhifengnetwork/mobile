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
}
