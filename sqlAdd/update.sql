
-- add by zgp 直播相关表 --
CREATE TABLE `tp_user_video` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '用户ID',
  `room_id` varchar(30) DEFAULT '',
  `pic_fengmian` varchar(255) DEFAULT NULL COMMENT '直播封面',
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





