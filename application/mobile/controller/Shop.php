<?php

namespace app\mobile\controller;

use think\Db;

class Shop extends MobileBase
{
    /**
     * 用户个人自提过商品的门店列表
     */
    public function shop_list()
    {
        $topicList = M('topic')->where("topic_state=2")->select();
        $this->assign('topicList', $topicList);
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