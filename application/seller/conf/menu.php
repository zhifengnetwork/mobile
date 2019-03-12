<?php
return array(
    'index' => array('name' => '首页', 'child' => array(
        array('name' => '概览', 'child' => array(
            array('name' => '模板设置', 'act' => 'index', 'op' => 'index'),
        )),
    )),

    'shop' => array('name' => '商城', 'child' => array(
        /*array('name' => '门店', 'child' => array(
            array('name' => '门店管理', 'act' => 'store_list', 'op' => 'Goods'),
        )),*/
        array('name' => '商品', 'child' => array(
            array('name' => '商品列表', 'act' => 'goodsList', 'op' => 'Goods'),
            // array('name' => '淘宝导入', 'act'=>'index', 'op'=>'Import'),
            /*array('name' => '商品分类', 'act' => 'categoryList', 'op' => 'Goods'),
            // array('name' => '库存管理', 'act'=>'stockList', 'op'=>'Goods'),
            array('name' => '商品模型', 'act' => 'type_list', 'op' => 'Goods'),
            array('name' => '品牌列表', 'act' => 'brandList', 'op' => 'Goods'),*/
            // array('name' => '评论列表', 'act'=>'index', 'op'=>'Comment'),
            // array('name' => '商品咨询', 'act'=>'ask_list', 'op'=>'Comment'),
        )),
        array('name' => '订单', 'child' => array(
            array('name' => '订单列表', 'act' => 'index', 'op' => 'Order'),
            // array('name' => '虚拟订单', 'act'=>'virtual_list', 'op'=>'Order'),
            array('name' => '发货单', 'act' => 'delivery_list', 'op' => 'Order'),
            array('name' => '退款单', 'act' => 'refund_order_list', 'op' => 'Order'),
            array('name' => '退换货', 'act' => 'return_list', 'op' => 'Order'),
            // array('name' => '添加订单', 'act'=>'add_order', 'op'=>'Order'),
            // array('name' => '订单日志','act'=>'order_log','op'=>'Order'),
            // array('name' => '发票管理','act'=>'index', 'op'=>'Invoice'),
            /* array('name' => '拼团列表','act'=>'team_list','op'=>'Team'),
            array('name' => '拼团订单','act'=>'order_list','op'=>'Team'),
            array('name' => '上门自提','act'=>'index','op'=>'ShopOrder'), */
        )),
    )),

    'data' => array('name' => '数据', 'child' => array(
        array('name' => '统计', 'child' => array(
            array('name' => '销售概况', 'act' => 'index', 'op' => 'Report'),
            // array('name' => '销售排行', 'act'=>'saleTop', 'op'=>'Report'),
            // array('name' => '会员排行', 'act'=>'userTop', 'op'=>'Report'),
            array('name' => '销售明细', 'act' => 'saleList', 'op' => 'Report'),
            // array('name' => '会员统计', 'act'=>'user', 'op'=>'Report'),
            array('name' => '运营概览', 'act' => 'finance', 'op' => 'Report'),
            array('name' => '平台支出记录', 'act' => 'expense_log', 'op' => 'Report'),
        )),
    )),

     /*'pickup'=>array('name'=>'门店','child'=>array(
     		array('name' => '门店管理','child' => array(
     				array('name'=>'门店管理','act'=>'store','op'=>'Pickup'),
     		)),
     )),*/
);