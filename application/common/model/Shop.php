<?php

namespace app\common\model;

use app\common\logic\FlashSaleLogic;
use app\common\logic\GroupBuyLogic;
use think\Db;
use think\Model;
use app\common\logic\PromGoodsLogic;

class Shop extends Model
{
    //自定义初始化
    protected static function init()
    {
        //TODO:自定义的初始化
    }
    public function suppliers()
    {
        return $this->hasOne('Suppliers','suppliers_id','suppliers_id');
    }
    public function shopImages()
    {
        return $this->hasMany('shopImages','shop_id','shop_id');
    }
    public function getAreaListAttr($value, $data)
    {
        $area_list = Db::name('region')->where('id', 'IN', [$data['province_id'], $data['city_id'], $data['district_id']])->order('level asc')->select();
        return $area_list;
    }

    public function getWorkDayAttr($value, $data)
    {
        $arr = [];
        if ($data['monday'] == 1) {
            array_push($arr, '周一');
        }
        if ($data['tuesday'] == 1) {
            array_push($arr, '周二');
        }
        if ($data['wednesday'] == 1) {
            array_push($arr, '周三');
        }
        if ($data['thursday'] == 1) {
            array_push($arr, '周四');
        }
        if ($data['friday'] == 1) {
            array_push($arr, '周五');
        }
        if ($data['saturday'] == 1) {
            array_push($arr, '周六');
        }
        if ($data['sunday'] == 1) {
            array_push($arr, '周日');
        }
        $desc = implode('、', $arr);
        return $desc;
    }
    /**
     * 设置添加时间
     * @param $value
     * @return string
     */
    public function setAddTimeAttr($value){
        return time();
    }
    public function getPhoneAttr($value, $data){
        if($data['shop_phone_code'] == '' || empty($data['shop_phone_code'])){
            return $data['shop_phone'];
        }else{
            return $data['shop_phone_code'] . '-' . $data['shop_phone'];
        }
    }
    public function getWorkTimeAttr($value, $data){
       return $data['work_start_time'] . '-' .$data['work_end_time'];
    }
}
