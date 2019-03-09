<?php

namespace app\common\model;
use think\Model;
use think\Db;
class GoodsConsult extends Model {
    public function getReplyListAttr($value,$data)
    {
        return Db::name('GoodsConsult')->where(['parent_id' => $data['id'],'is_show'=>1])->order('add_time desc')->select();
    }
}
