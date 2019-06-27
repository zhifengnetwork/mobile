
-- add by zgp 直播相关表 --
CREATE TABLE `tp_user_video` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '用户ID',
  `room_id` varchar(80) DEFAULT '',
  `pic_fengmian` varchar(255) DEFAULT NULL COMMENT '直播封面',
  `good_ids`  text CHARACTER SET utf8 COLLATE utf8_general_ci NULL COMMENT '商品id',
  `location` varchar(60) DEFAULT NULL COMMENT '位置',
  `start_time` int(10) DEFAULT '0' COMMENT '直播开始时间',
  `money` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '打赏金额',
  `look_amount` int(11) unsigned DEFAULT '0' COMMENT '直播间观看人数',
  `top_amount` int(11) unsigned DEFAULT '0' COMMENT '点赞人数',
  `status` tinyint(4) NOT NULL DEFAULT '0' COMMENT '0 未开始 ； 1  已开始 ； 2 已结束',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

-- add by zgp 直播相关表 --
CREATE TABLE `tp_user_verify_identity_log` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `verify_id` int(10) unsigned DEFAULT NULL COMMENT '认证ID',
  `verify_state` tinyint(1) DEFAULT NULL COMMENT '记录上一次认证的审核状态',
  `reason_cn` varchar(255) NOT NULL COMMENT '认证失败原因（中文）',
  `admin_id` int(10) unsigned NOT NULL COMMENT '审核人员ID',
  `create_time` int(10) unsigned NOT NULL COMMENT '创建时间',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 ROW_FORMAT=COMPACT COMMENT='实名认证审核日志表';

-- add by zgp 直播相关表 --
CREATE TABLE `tp_user_verify_identity_info` (
  `id` int(10) NOT NULL AUTO_INCREMENT COMMENT '自增ID',
  `user_id` int(10) unsigned NOT NULL COMMENT '用户ID',
  `name` char(30) DEFAULT NULL COMMENT '身份证姓名',
  `mobile` char(11) DEFAULT NULL COMMENT '用户手机号',
  `pic_front` varchar(255) DEFAULT NULL COMMENT '证件正面照',
  `pic_back` varchar(255) DEFAULT NULL COMMENT '证件反面照',
  `verify_state` tinyint(1) DEFAULT '0' COMMENT '0审核中 1 通过 2未通过',
  `state` tinyint(1) DEFAULT '1' COMMENT '状态，1为正常，0为异常（删除）',
  `create_time` int(10) NOT NULL COMMENT '创建时间',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC COMMENT='实名认证信息表';

-- add by zgp 直播相关表 --
CREATE TABLE `tp_user_verify_identity` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL COMMENT '关联user表',
  `verify_state` tinyint(4) DEFAULT '0' COMMENT '验证状态，0为一级验证不通过，1为一级正在验证，2为通过一级验证',
  `state` tinyint(4) DEFAULT '1' COMMENT '状态，1为正常，0为异常（删除）',
  `create_time` int(11) DEFAULT NULL COMMENT '创建时间',
  PRIMARY KEY (`id`) USING BTREE,
  KEY `user_id` (`user_id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 ROW_FORMAT=FIXED;

--add by lyx 直播相关表 --
CREATE TABLE `tp_red_detail` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT '红包从表id',
  `m_id` int(11) NOT NULL COMMENT '红包主表id',
  `room_id` int(11) NOT NULL COMMENT '群id',
  `get_uid` int(11) NOT NULL COMMENT '获取红包用户uid',
  `money` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '红包金额（最小单位）',
  `get_time` int(11) DEFAULT NULL COMMENT '领取时间',
  `type` tinyint(4) DEFAULT '0' COMMENT '默认0，1领取，2超时退回',
  `out_time` int(11) DEFAULT NULL COMMENT '超时红包退回时间',
  `get_award_money` decimal(10,2) DEFAULT '0.00' COMMENT '记录获得奖励金额',
  `status` tinyint(4) DEFAULT '0' COMMENT '默认0，禁用1',
  PRIMARY KEY (`id`) USING BTREE,
  KEY `id` (`id`) USING BTREE,
  KEY `m_id` (`m_id`) USING BTREE,
  KEY `get_uid` (`get_uid`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='红包从表';

--add by lyx 直播相关表 --
CREATE TABLE `tp_red_master` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '红包主表id',
  `uid` int(10) NOT NULL COMMENT '用户id',
  `room_id` int(6) NOT NULL COMMENT '群id',
  `num` int(6) NOT NULL DEFAULT '0' COMMENT '红包个数',
  `money` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '红包金额',
  `create_time` int(11) NOT NULL COMMENT '创建时间',
  `time_out` tinyint(2) DEFAULT '0' COMMENT '默认0,1有红包超时已退回过',
  `all_get` tinyint(2) DEFAULT '0' COMMENT '默认0, 1标识红包被抢完',
  `status` tinyint(4) DEFAULT '0' COMMENT '默认0，禁用1',
  PRIMARY KEY (`id`) USING BTREE,
  KEY `id` (`id`) USING BTREE,
  KEY `uid` (`uid`) USING BTREE,
  KEY `room_id` (`room_id`) USING BTREE,
  KEY `time_out` (`time_out`) USING BTREE,
  KEY `all_get` (`all_get`) USING BTREE,
  KEY `is_award` (`is_award`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='红包主表';

--add by zxl --
 CREATE TABLE `tp_live_gift` (
 `id`  int(10) UNSIGNED NOT NULL AUTO_INCREMENT ,
 `name`  varchar(100) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '名称' ,
 `image`  varchar(120) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '图片' ,
 `price`  decimal(10,2) UNSIGNED NULL DEFAULT 0.00 COMMENT '价格' ,
 `is_show`  tinyint(1) UNSIGNED NULL DEFAULT 1 COMMENT '是否显示' ,
 `desc`  varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT '描述' ,
 `sort`  smallint(5) UNSIGNED NULL DEFAULT 0 COMMENT '排序' ,
 `create_time`  int(11) NULL DEFAULT NULL ,
 `delete_time`  int(11) NULL DEFAULT NULL , PRIMARY KEY (`id`)
 ) ENGINE=InnoDB DEFAULT CHARACTER SET=utf8 COMMENT='直播礼物表';

--add by zxl --
CREATE TABLE `tp_live_gift_sending_log` (
`id`  int(10) UNSIGNED NOT NULL AUTO_INCREMENT ,
`gift_id`  int(10) UNSIGNED NULL DEFAULT 0 COMMENT '礼物ID' ,
`user_id`  int(10) UNSIGNED NULL DEFAULT 0 COMMENT '送礼物的用户ID' ,
`to_user_id`  int(10) UNSIGNED NULL DEFAULT NULL COMMENT '得到礼物的用户ID' ,
`room_id`  varchar(80) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT '' COMMENT '直播房间ID' ,
`data`  text CHARACTER SET utf8 COLLATE utf8_general_ci NULL COMMENT '礼物数据' ,
`create_time`  int(11) NULL DEFAULT NULL ,PRIMARY KEY (`id`)
)ENGINE=InnoDB DEFAULT CHARACTER SET=utf8 COMMENT='直播礼物发送日志表';


--add by xiaozhi
CREATE TABLE `tp_commission_rate` (
  `id` tinyint(1) unsigned NOT NULL AUTO_INCREMENT COMMENT '主键ID',
  `rate` decimal(8,2) unsigned NOT NULL DEFAULT '0.00' COMMENT '佣金比例',
  `type` tinyint(1) unsigned NOT NULL DEFAULT '1' COMMENT '返佣方式 1:付款返佣,2:收货返佣',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8 COMMENT='佣金比例表';
ALTER TABLE  `tp_cart` ADD  `zhubo_id` INT( 11 ) UNSIGNED NOT NULL DEFAULT  '0' COMMENT  '主播ID（用户ID）' AFTER  `user_id`
ALTER TABLE  `tp_order` ADD  `zhubo_id` INT( 11 ) UNSIGNED NOT NULL DEFAULT  '0' COMMENT  '主播ID（用户ID）' AFTER  `user_id`