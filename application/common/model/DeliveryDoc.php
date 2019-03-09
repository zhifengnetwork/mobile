<?php

namespace app\common\model;
use think\Db;
use think\Model;
class DeliveryDoc extends Model {

    public function getFullAddressAttr($value, $data)
    {
        $region = Db::name('region')->where('id', 'IN', [$data['store_address_province_id'], $data['store_address_city_id'], $data['store_address_district_id']])->column('name');
        return implode('', $region) . $data['store_address'];
    }
}
