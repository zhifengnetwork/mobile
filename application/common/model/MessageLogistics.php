<?php

namespace app\common\model;
use think\Model;
use think\Db;
class MessageLogistics extends Model
{
    public function userMessage()
    {
        return $this->hasOne('userMessage', 'message_id', 'message_id');
    }

    public function getSendTimeTextAttr($value, $data)
    {
        return time_to_str($data['send_time']);
    }
    public function getHomeUrlAttr($value, $data)
    {
        if ($data['mmt_code'] == 'evaluate_logistics'){
            $uri = U("Home/Order/comment", ['status' => 0]);
        }else{
            $prom_type = Db::name('order')->where('order_id', $data['order_id'])->value('prom_type');
            if($prom_type == 6){
                // 0普通订单4预售订单5虚拟订单6拼团订单',
                $uri = '';
            }else{
                $uri = U("Home/Order/order_detail", ['id' => $data['order_id']]);
            }
        }
        return $uri;
    }
    public function getFinishedAttr($value, $data)
    {

        return false;
    }

    public function getMobileUrlAttr($value, $data)
    {
        if ($data['mmt_code'] == 'evaluate_logistics'){
            $uri = U("Mobile/Order/comment", ['status' => 0]);
        }else{
            $prom_type = Db::name('order')->where('order_id', $data['order_id'])->value('prom_type');
            if($prom_type == 6){
                // 0普通订单4预售订单5虚拟订单6拼团订单',
                $uri = U("Mobile/Order/team_detail", ['order_id' => $data['order_id']]);
            }elseif($prom_type == 5){
                $uri = U("Mobile/Virtual/virtual_order", ['order_id' => $data['order_id']]);
            }else{
                $uri = U("Mobile/Order/order_detail", ['id' => $data['order_id']]);
            }
        }
        return $uri;
    } 

    public function getOrderTextAttr($value, $data)
    {
        switch ($data['mmt_code']) {
            case 'virtual_order_logistics':
            case 'deliver_goods_logistics':
                //发货提醒
                $invoice_no = Db::name('delivery_doc')->where('order_id', $data['order_id'])->value('invoice_no');
                if (empty($invoice_no)) {
                    $text = '无物流';
                }else{
                    $text = '运单编号 : '. $invoice_no;
                }
                break;
            case 'evaluate_logistics':
                // 待评价
                $order_sn = Db::name('order')->where('order_id', $data['order_id'])->value('order_sn');
                $text = '订单编号 : '. $order_sn;
                break;
            default:
                $text = '订单编号 : '. $data['order_sn'];
                break;
        }
        return $text;
    }
    public function getStartTimeAttr($value, $data)
    {
        return true;
    }

    /**
     * 手机端需要订单类型
     * @param $value
     * @param $data
     * @return mixed
     */
    public function getOrderTypeAttr($value, $data)
    {
        $order_type = Db::name('order')->where('order_id', $data['order_id'])->value('prom_type');
        return $order_type;
    }
}
