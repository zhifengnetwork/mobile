<?php
// 面包屑导航配置
return array(
    'home/seller' => array(
        'name' => '用户中心',
        'action' => array(
            'index' => '我的商城',
            'order_list' => '我的订单',
            'order_detail' => '订单详情',
            'goods_collect' => '我的收藏',
            'info' => '个人信息',
            'password' => '修改密码',
            'account' => '我的资金',
            'coupon' => '优惠券',
            'address_list' => '收货地址管理',
            'comment' => '订单评价',
        )
    ),
    'seller/index' => array(
        'name' => 'TPshop系统管理',
        'action' => array(
            'index' => '欢迎页面',
        )
    ),
    'seller/system' => array(
        'name' => '系统设置',
        'action' => array(
            'index' => '网站设置',
            'navigationList' => '导航设置',
            'menu' => '菜单管理',
            'module' => '模块管理',
        )
    ),

    'seller/goods' => array(
        'name' => '商品管理',
        'action' => array(
            'categoryList' => '商品分类',
            'addEditCategory' => '添加修改分类',
            'goodsList' => '商品列表',
            'addEditGoods' => '添加修改商品',
            'goodsTypeList' => '商品类型',
            'addEditGoodsType' => '编辑商品类型',
            'specList' => '商品规格',
            'addEditSpec' => '添加修改规格',
            'goodsAttributeList' => '商品属性',
            'addEditGoodsAttribute' => '添加修改属性',
            'brandList' => '商品品牌',
            'addEditBrand' => '添加修改品牌',
        )
    ),
    'seller/order' => array(
        'name' => '订单管理',
        'action' => array(
            'index' => '订单列表',
            'edit_order' => '编辑订单',
            'delivery_list' => '发货单列表',
            'delivery_info' => '订单发货',
            'add_order' => '添加订单',
            'split_order' => '拆分订单',
            'detail' => '订单详情',
            'return_list' => '退货申请列表',
        )
    ),
    'seller/seller' => array(
        'name' => '会员管理',
        'action' => array(
            'index' => '用户列表',
            'address' => '收货地址',
            'account_log' => '用户资金',
            'levelList' => '等级列表',
            'level' => '添加等级',
        )
    ),
    'seller/ad' => array(
        'name' => '广告管理',
        'action' => array(
            'adList' => '广告列表',
            'edit' => '编辑广告',
            'ad' => '新增广告',
            'adList' => '广告列表',
            'positionList' => '广告位置',
            'position' => '编辑广告位',
        )
    ),
    'seller/article' => array(
        'name' => '文章管理',
        'action' => array(
            'categorylist' => '分类列表',
            'category' => '编辑分类',
            'articlelist' => '文章列表',
            'article' => '编辑文章',
            'linkList' => '友情链接列表',
            'link' => '编辑友情链接',
        )
    ),
    'seller/seller' => array(
        'name' => '权限管理',
        'action' => array(
            'index' => '管理员列表',
            'seller_info' => '编辑管理员',
            'log' => '管理员日志',
            'role' => '角色管理',
            'role_info' => '创建编辑角色',
        )
    ),
    'seller/comment' => array(
        'name' => '评论管理',
        'action' => array(
            'index' => '评论列表',
            'detail' => '评论回复',
        )
    ),
    'seller/template' => array(
        'name' => '模板管理',
        'action' => array(
            'templatelist' => '模板选择',
        )
    ),
    'seller/coupon' => array(
        'name' => '优惠券管理',
        'action' => array(
            'index' => '优惠券列表',
            'add_coupon' => '添加优惠券',
            'edit_coupon' => '编辑优惠券',
        )
    ),
    'seller/plugin' => array(
        'name' => '插件管理',
        'action' => array(
            'index' => '插件列表',
            'setting' => '插件配置',
        )
    ),
    'seller/topic' => array(
        'name' => '专题管理',
        'action' => array(
            'topicList' => '专题列表',
            'topic' => '添加专题',
        )
    ),
    'seller/promotion' => array(
        'name' => '团购管理',
        'action' => array(
            'group_buy_list' => '团购列表',
            'group_buy' => '编辑团购',
        )
    ),
    'seller/tools' => array(
        'name' => '工具管理',
        'action' => array(
            'index' => '数据备份',
            'restore' => '数据还原',
        )
    ),
    'seller/report' => array(
        'name' => '报表统计',
        'action' => array(
            'index' => '销售概况',
            'saleTop' => '销售排行',
            'sellerTop' => '会员排行',
            'saleList' => '销售明细',
            'seller' => '会员统计',
            'finance' => '财务统计',
        )
    ),
    'seller/distribut' => array(
        'name' => '分销管理',
        'action' => array(
            'tree' => '分销关系',
            'set' => '分销设置',
            'withdrawals' => '提现申请记录',
            'remittance' => '汇款记录',
            'rebate_log' => '分成记录',
        )
    ),

);
?>
