<?php



namespace app\admin\logic;

use think\Db;
use think\Model;

class InvoiceLogic extends Model
{
    //发票创建
	function createInvoice($order){
        $data = [
            'order_id'       => $order['order_id'],  //订单id
            'user_id'        => $order['user_id'],  //用户id
            'ctime'          => time(),              //创建时间
            'invoice_money'  => $order['total_amount']-$order['shipping_price'],
        ];
        $invoiceInfo = Db::name('Invoice')->where(['order_id'=>$order['order_id']])->find();
        $userExtend = Db::name('user_extend')->where(['user_id'=>$order['user_id']])->find();
        if($userExtend['invoice_desc'] != '不开发票' && $userExtend['invoice_desc'] != ''){
            if(empty($invoiceInfo)) {
                $data['invoice_desc'] = $order['invoice_desc'];//发票内容
                $data['taxpayer'] = $order['taxpayer'];//纳税人识别号
                $data['invoice_title'] = $order['invoice_title'];// 发票抬头
                Db::name('invoice')->add($data);
            }
        }
    }

}