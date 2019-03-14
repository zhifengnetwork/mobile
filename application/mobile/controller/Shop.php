<?php

namespace app\mobile\controller;

use think\Db;
use think\db\Query;

class Shop extends MobileBase
{
    /**
     * 用户个人自提过商品的门店列表
     */
    public function shop_list()
    {
        /*$orderList = Db::query("select distinct o.shop_id,s.shop_name from tp_order as o
                      inner join tp_shop as s on s.shop_id = o.shop_id  where o.user_id = ".cookie('user_id'));*/
        $shopList = Db::name('order')->alias('o')
            ->join('tp_shop s','s.shop_id = o.shop_id','inner')
            ->field('distinct o.shop_id,s.shop_name')
            ->where(['o.user_id'=>cookie('user_id')])
            ->select();
        if(!empty($shopList)){
            foreach ($shopList as $key => $value){

                /*echo "<pre>";
                print_r($value);
                echo "</pre>";
                continue;*/
                $shopGoodsList = Db::name('shop_goods')->alias('sg')
                    ->join('tp_goods g','g.goods_id = sg.goods_id','inner')
                    //->field('distinct o.shop_id,s.shop_name')
                    ->where(['sg.shop_id'=>$value['shop_id']])
                    ->select();
                $shopList[$key]['item'] = $shopGoodsList;
            }
        }

        /*echo "<pre>";
        print_r($shopList);
        echo "</pre>";
        exit;*/
        $this->assign('shop_title', '门店管理');
        $this->assign('shopList', $shopList);
        return $this->fetch();
    }

    /**
     * 用户个人成为核销员后自提核销功能
     */
    public function shop_order()
    {
        $topic_id = I('topic_id/d', 1);
        $topic = Db::name('topic')->where("topic_id", $topic_id)->find();
        $this->assign('topic', $topic);
        return $this->fetch();
    }

    public function info()
    {
        $topic_id = I('topic_id/d', 1);
        $topic = Db::name('topic')->where("topic_id", $topic_id)->find();
        echo htmlspecialchars_decode($topic['topic_content']);
        exit;
    }
}