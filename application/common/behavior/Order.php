<?php


namespace app\common\behavior;
use app\common\logic\wechat\WechatUtil;
use think\Db;
class Order
{
    public function userAddOrder(&$order)
    {
        $time = date('Y-m-d H:i:s',time());
        // 记录订单操作日志
        $action_info = array(
            'order_id'        =>$order['order_id'],
            'action_user'     =>0,
            'action_note'     => '您提交了订单，请等待系统确认',
            'status_desc'     =>'提交订单', //''
            'log_time'        =>time(),
        );
        Db::name('order_action')->add($action_info);

        //分销开关全局
        // $distribut_switch = tpCache('distribut.switch');
        // if ($distribut_switch == 1 && file_exists(APP_PATH . 'common/logic/DistributLogic.php')) {
        //     $distributLogic = new \app\common\logic\DistributLogic();
        //     $distributLogic->rebateLog($order); // 生成分成记录
        // }
        // 如果有微信公众号 则推送一条消息到微信.微信浏览器才发消息，否则下单超时。by清华
        if(is_weixin()){
            $user = Db::name('OauthUsers')->where(['user_id'=>$order['user_id'] , 'oauth'=>'weixin' , 'oauth_child'=>'mp'])->find();

            if ($user) {
                $goods = Db::name('OrderGoods')->where(['order_id'=>$order['order_id']])->select();
                $text = '';
                foreach ($goods as $key => $value) {
                    $text .= $value['goods_name'].'(规格：'.$value['spec_key_name'].',数量：'.$value['goods_num'].',价格：'.$value['final_price'].');';
                }
                $wx_contents = "您的订单已提交成功！\n
                                \n
                                店铺：DC环球直供\n
                                下单时间：{$time}\n
                                商品：{$text}\n
                                金额：{$order['order_amount']}\n
                                \n
                                您的订单我们已经收到，支付后我们将尽快配送~~";
                // $wx_content = "您刚刚下了一笔订单:{$order['order_sn']}!";
                $wechat = new WechatUtil();
                $wechat->sendMsg($user['openid'], 'text', $wx_content);
            }
        }

        //用户下单, 发送短信给商家
        $res = checkEnableSendSms("3");
        if($res && $res['status'] ==1){
            $sender = tpCache("shop_info.mobile");
            $params = array('consignee'=>$order['consignee'] , 'mobile' => $order['mobile']);
            sendSms("3", $sender, $params);
        }
    }

}