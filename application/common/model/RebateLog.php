<?php


namespace app\common\model;
use think\Model;
use think\Db;

class RebateLog extends Model
{
    public function getUser(){
        return $this->hasOne('users','user_id','user_id')->bind('user_id,mobile,nickname,email');
    }
    public function buyUser(){
        return $this->hasOne('users','user_id','buy_user_id')->bind('user_id,mobile,nickname,email');
    }
}