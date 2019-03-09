<?php

namespace app\common\model;
use think\Model;
class Invoice extends Model {
    //自定义初始化
    protected static function init()
    {
        //TODO:自定义的初始化
    }

    public function users()
    {
        return $this->hasOne('Users', 'user_id', 'user_id');
    }

    public function order()
    {
        return $this->hasOne('Order', 'order_id', 'order_id');
    }

    public function getInvoiceTypeAttr($value, $data)
    {
        $invoice_type=C('INVOUCE_TYPE');
        $order_invoice_type = $data['invoice_type'];
        return $invoice_type["$order_invoice_type"];
    }
}
