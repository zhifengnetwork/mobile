<?php

namespace app\common\model;
use think\Model;
use think\Db;
class Withdrawals extends Model {
    /**
     * 用户名
     */
    public function users()
    {
        return $this->hasOne('Users','user_id','user_id')->field('nickname');
    }

    /**
     * 用户名
     */
    public function getNickNameAttr($value, $data){
        return DB::name('users')->where(['user_id'=>$data['user_id']])->getField('nickname');
    }

}