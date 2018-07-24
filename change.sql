ALTER TABLE hunuo_attribute ADD `attr_form_name` varchar(60) NOT NULL DEFAULT '' COMMENT '属性字段名称';
ALTER TABLE hunuo_attribute ADD `is_diy` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '是否显示';
ALTER TABLE hunuo_font_type ADD `type_flag` tinyint(1) unsigned NOT NULL DEFAULT '2' COMMENT '1为顶级分类，2为二级分类';
ALTER TABLE hunuo_goods_type ADD `edit_area_x` varchar(100) NOT NULL DEFAULT '232' COMMENT '编辑区域X轴偏移值';
ALTER TABLE hunuo_goods_type ADD `edit_area_y` varchar(100) NOT NULL DEFAULT '181' COMMENT '编辑区域Y轴偏移值';
ALTER TABLE hunuo_goods_type ADD `edit_area_w` varchar(100) NOT NULL DEFAULT '457' COMMENT '编辑区域宽度';
ALTER TABLE hunuo_goods_type ADD `edit_area_h` varchar(100) NOT NULL DEFAULT '585' COMMENT '编辑区域高度';
ALTER TABLE hunuo_users ADD `nickname` varchar(60) NOT NULL DEFAULT '' COMMENT '昵称';
ALTER TABLE hunuo_users ADD `weixin` varchar(60) NOT NULL COMMENT '微信';
ALTER TABLE hunuo_users ADD `wx_open` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT '0不公开微信，1公开微信';
ALTER TABLE hunuo_users ADD `qq_open` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT '0不公开QQ，1公开QQ' AFTER qq;
ALTER TABLE hunuo_users ADD `profile` text NOT NULL COMMENT '个性签名';
ALTER TABLE hunuo_users ADD `weibo` text NOT NULL COMMENT '微博地址';
ALTER TABLE hunuo_users ADD `facebook` text NOT NULL COMMENT 'facebook地址';
ALTER TABLE hunuo_users ADD `instagram` text NOT NULL COMMENT 'instagram地址';
ALTER TABLE hunuo_users ADD `website` text NOT NULL COMMENT '个人网站';
ALTER TABLE hunuo_users ADD `fields` text NOT NULL COMMENT '领域';
ALTER TABLE hunuo_users ADD `sh_province` int(11) NOT NULL COMMENT '学校（省）';
ALTER TABLE hunuo_users ADD `sh_city` int(11) NOT NULL COMMENT '学校（市）';
ALTER TABLE hunuo_users ADD `sh_school` int(11) NOT NULL COMMENT '学校';
ALTER TABLE hunuo_users ADD `is_renzheng` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT '是否已实名认证';
ALTER TABLE hunuo_field ADD `is_common` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '是否常用';
ALTER TABLE hunuo_personal_letter ADD `is_read` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '是否已读：0未读，1已读';
ALTER TABLE hunuo_personal_letter ADD `report_reason` text NOT NULL COMMENT '举报原因';
ALTER TABLE hunuo_personal_letter ADD `report_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '举报时间';
ALTER TABLE hunuo_member_message ADD `msg_type` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT '1公告，2系统消息，3交易消息';
ALTER TABLE hunuo_article_cat ADD `file_url` varchar(255) NOT NULL DEFAULT '' COMMENT '文件地址';
ALTER TABLE hunuo_goods_gallery ADD `goods_attr_id2` int(10) unsigned NOT NULL DEFAULT '0' AFTER goods_attr_id;
ALTER TABLE hunuo_goods ADD `best_img` varchar(255) NOT NULL DEFAULT '' COMMENT '精品图片';
ALTER TABLE hunuo_goods ADD `design_img` varchar(255) NOT NULL DEFAULT '' COMMENT '设计图' AFTER original_img;
ALTER TABLE hunuo_goods ADD `design_session` varchar(36) DEFAULT NULL COMMENT '设计ID';
ALTER TABLE hunuo_goods ADD `goods_total` smallint(5) unsigned NOT NULL DEFAULT '0' COMMENT '限购量';
ALTER TABLE hunuo_goods ADD `goods_status` tinyint(1) NOT NULL DEFAULT '0' COMMENT '0：后台添加的商品，1：diy商品未出售状态，2：diy商品申请出售审核中状态，3：审核不通过状态，4：diy商品已出售状态';
ALTER TABLE hunuo_goods ADD `user_id` mediumint(8) unsigned NOT NULL DEFAULT '0' COMMENT '用户ID';
ALTER TABLE hunuo_goods ADD `goods_rank` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT '商品等级（跟设计商品时用户的等级同步）';
ALTER TABLE hunuo_goods ADD `commision1` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '第1阶段佣金';
ALTER TABLE hunuo_goods ADD `commision2` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '第2阶段佣金';
ALTER TABLE hunuo_goods ADD `goods_view` smallint(5) unsigned NOT NULL DEFAULT '0' COMMENT '查看次数';
ALTER TABLE hunuo_order_info ADD `inv_consignee_email` varchar(60) NOT NULL DEFAULT '' COMMENT '收票人邮箱' AFTER inv_consignee_phone;
ALTER TABLE hunuo_order_info ADD `open_inv_type` varchar(120) NOT NULL DEFAULT '' COMMENT '开票方式' AFTER pickup_point;
ALTER TABLE hunuo_cart ADD `is_design` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT '1为设计库商品，不显示在前端购物车里面';
ALTER TABLE hunuo_users ADD `rank_sale_number` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '销量';
ALTER TABLE hunuo_user_rank ADD `min_sale_number` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '销量下限';
ALTER TABLE hunuo_user_rank ADD `max_sale_number` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '销量上限';
ALTER TABLE hunuo_user_rank ADD `commision_scale1` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT '第1阶段佣金比例';
ALTER TABLE hunuo_user_rank ADD `commision_scale2` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT '第2阶段佣金比例';
ALTER TABLE hunuo_user_rank ADD `sale_number` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '可销售数量';
ALTER TABLE hunuo_user_rank ADD `sale_price` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '售价';
ALTER TABLE hunuo_goods_attr ADD `is_sale` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT '1为diy商品出售的属性';
ALTER TABLE hunuo_diy ADD `design_img_t` varchar(255) NOT NULL DEFAULT '' COMMENT '带衣服设计图' AFTER design_img;
ALTER TABLE hunuo_goods_details ADD `sort_order` int(11) DEFAULT '1000' COMMENT '排序';
ALTER TABLE hunuo_order_info ADD `is_design` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT '0：购买出售的商品，1：购买设计作品';
ALTER TABLE hunuo_bank_card ADD `bank_icon` varchar(255) NOT NULL DEFAULT '' COMMENT '银行图标';
ALTER TABLE hunuo_bank_card ADD `card` varchar(255) NOT NULL DEFAULT '' COMMENT '身份证号码';
ALTER TABLE hunuo_user_account ADD `poundage` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '手续费';
ALTER TABLE hunuo_users ADD `is_recommend` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '是否推荐';
ALTER TABLE hunuo_back_order ADD COLUMN `back_shipping_name`  varchar(120) NULL COMMENT '退款订单,平台换回商品快递信息公司名称';
ALTER TABLE hunuo_back_order ADD COLUMN `back_invoice_no`  varchar(50) NULL COMMENT '退款订单,平台换回商品快递信息快递单号';
ALTER TABLE hunuo_users ADD `sale_amount` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '销售额';

-- diy商品属性图片
CREATE TABLE `hunuo_attribute_img` (
  `img_id` smallint(5) unsigned NOT NULL AUTO_INCREMENT,
  `cat_id` smallint(5) unsigned NOT NULL DEFAULT '0' COMMENT '商品类型ID',
  `attr_group` varchar(60) NOT NULL DEFAULT '' COMMENT '属性组合',
  `file_url` varchar(255) NOT NULL DEFAULT '' COMMENT '图片地址',
  PRIMARY KEY (`img_id`),
  KEY `img_id` (`img_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- 字体分类
CREATE TABLE `hunuo_font_type` (
  `type_id` smallint(5) NOT NULL AUTO_INCREMENT,
  `parent_id` smallint(5) unsigned NOT NULL DEFAULT '0' COMMENT '父级分类',
  `type_name` varchar(255) NOT NULL DEFAULT '' COMMENT '字体分类名称',
  `type_short_name` varchar(100) NOT NULL DEFAULT '' COMMENT '英文缩写',
  `sort_order` tinyint(3) unsigned NOT NULL DEFAULT '50' COMMENT '排序',
  `is_show` tinyint(1) unsigned NOT NULL DEFAULT '1' COMMENT '是否显示',
  PRIMARY KEY (`type_id`),
  KEY `sort_order` (`sort_order`),
  KEY `parent_id` (`parent_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- 字体
CREATE TABLE `hunuo_font` (
  `font_id` mediumint(8) unsigned NOT NULL AUTO_INCREMENT,
  `type_id` smallint(5) NOT NULL DEFAULT '0' COMMENT '分类ID',
  `font_name` varchar(150) NOT NULL DEFAULT '' COMMENT '字体名称（自动生成）',
  `font_img` varchar(255) NOT NULL DEFAULT '' COMMENT '字体图片',
  `font_file` varchar(255) NOT NULL DEFAULT '' COMMENT '字体文件，ttf格式',
  `font_file_ie` varchar(255) NOT NULL DEFAULT '' COMMENT '兼容IE字体文件，eot格式',
  `is_show` tinyint(1) unsigned NOT NULL DEFAULT '1' COMMENT '是否显示',
  `sort_order` int(11) NOT NULL DEFAULT '100' COMMENT '排序',
  PRIMARY KEY (`font_id`),
  KEY `type_id` (`type_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- 图形分类
CREATE TABLE `hunuo_graph_type` (
  `type_id` smallint(5) NOT NULL AUTO_INCREMENT,
  `type_name` varchar(255) NOT NULL DEFAULT '' COMMENT '图形分类名称',
  `sort_order` tinyint(3) unsigned NOT NULL DEFAULT '50' COMMENT '排序',
  `is_show` tinyint(1) unsigned NOT NULL DEFAULT '1' COMMENT '是否显示',
  PRIMARY KEY (`type_id`),
  KEY `sort_order` (`sort_order`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- 图形
CREATE TABLE `hunuo_graph` (
  `graph_id` mediumint(8) unsigned NOT NULL AUTO_INCREMENT,
  `type_id` smallint(5) NOT NULL DEFAULT '0' COMMENT '分类ID',
  `graph_name` varchar(150) NOT NULL DEFAULT '' COMMENT '图形名称',
  `graph_file` varchar(255) NOT NULL DEFAULT '' COMMENT '图形文件，svg格式',
  `is_show` tinyint(1) unsigned NOT NULL DEFAULT '1' COMMENT '是否显示',
  `sort_order` int(11) NOT NULL DEFAULT '100' COMMENT '排序',
  PRIMARY KEY (`graph_id`),
  KEY `type_id` (`type_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- 属性图标
CREATE TABLE `hunuo_attribute_icon` (
  `attr_icon_id` mediumint(8) unsigned NOT NULL AUTO_INCREMENT,
  `attr_id` mediumint(8) unsigned NOT NULL DEFAULT '0' COMMENT '属性ID',
  `attr_value_name` varchar(30) NOT NULL DEFAULT '' COMMENT '属性值',
  `default_icon` varchar(255) NOT NULL DEFAULT '' COMMENT '默认icon',
  `select_icon` varchar(255) NOT NULL DEFAULT '' COMMENT '选中icon',
  PRIMARY KEY (`attr_icon_id`),
  KEY `attr_id` (`attr_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- 蒙版
CREATE TABLE `hunuo_mask` (
  `mask_id` mediumint(8) unsigned NOT NULL AUTO_INCREMENT,
  `mask_name` varchar(30) NOT NULL DEFAULT '' COMMENT '蒙版名称',
  `mask_img` varchar(255) NOT NULL DEFAULT '' COMMENT '蒙版图片',
  `mask_code` text NOT NULL DEFAULT '' COMMENT '蒙版JS代码',
  `is_show` tinyint(1) unsigned NOT NULL DEFAULT '1' COMMENT '是否显示',
  `sort_order` int(11) NOT NULL DEFAULT '100' COMMENT '排序',
  PRIMARY KEY (`mask_id`),
  KEY `sort_order` (`sort_order`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- 领域
CREATE TABLE `hunuo_field` (
  `field_id` mediumint(8) unsigned NOT NULL AUTO_INCREMENT,
  `field_name` varchar(30) NOT NULL DEFAULT '' COMMENT '领域名称',
  `field_pin` varchar(30) NOT NULL DEFAULT '' COMMENT '首字母',
  `is_show` tinyint(1) unsigned NOT NULL DEFAULT '1' COMMENT '是否显示',
  `is_common` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '是否常用',
  `sort_order` int(11) NOT NULL DEFAULT '100' COMMENT '排序',
  PRIMARY KEY (`field_id`),
  KEY `sort_order` (`sort_order`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- DIY设计素材图片
CREATE TABLE `hunuo_diy_file` (
  `file_id` mediumint(8) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` mediumint(8) unsigned NOT NULL DEFAULT '0',
  `design_id` varchar(36) DEFAULT NULL COMMENT '设计ID',
  `file_url` varchar(255) NOT NULL DEFAULT '' COMMENT '文件地址',
  `add_time` int(11) NOT NULL COMMENT '上传时间',
  PRIMARY KEY (`file_id`),
  KEY `user_id` (`user_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- 私信
CREATE TABLE `hunuo_personal_letter` (
  `msg_id` mediumint(8) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` mediumint(8) unsigned NOT NULL DEFAULT '0' COMMENT '发送者用户ID',
  `receive_user_id` mediumint(8) unsigned NOT NULL DEFAULT '0' COMMENT '接收者用户ID',
  `msg_status` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '消息状态：0正常，1举报',
  `is_read` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '是否已读：0未读，1已读',
  `msg_content` text NOT NULL COMMENT '消息内容',
  `msg_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '时间',
  `report_reason` text NOT NULL COMMENT '举报原因',
  `report_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '举报时间',
  PRIMARY KEY (`msg_id`),
  KEY `user_id` (`user_id`),
  KEY `receive_user_id` (`receive_user_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- 私信用户关联表
CREATE TABLE `hunuo_personal_letter_temp` (
  `temp_id` mediumint(8) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` mediumint(8) unsigned NOT NULL DEFAULT '0' COMMENT '发送者用户ID',
  `receive_user_id` mediumint(8) unsigned NOT NULL DEFAULT '0' COMMENT '接收者用户ID',
  `msg_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '时间',
  PRIMARY KEY (`temp_id`),
  KEY `user_id` (`user_id`),
  KEY `receive_user_id` (`receive_user_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- 私信举报原因
CREATE TABLE `hunuo_letter_report_reason` (
  `reason_id` mediumint(8) unsigned NOT NULL AUTO_INCREMENT,
  `content` text NOT NULL COMMENT '内容',
  `is_show` tinyint(1) unsigned NOT NULL DEFAULT '1' COMMENT '是否显示',
  `sort_order` int(11) NOT NULL DEFAULT '100' COMMENT '排序',
  PRIMARY KEY (`reason_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- 意见反馈
CREATE TABLE `hunuo_research` (
  `research_id` mediumint(8) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` mediumint(8) unsigned NOT NULL DEFAULT '0' COMMENT '发送者用户ID，0为匿名',
  `content` text NOT NULL COMMENT '反馈内容',
  `status` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '状态',
  `add_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '时间',
  PRIMARY KEY (`research_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- 用户单位发票抬头
CREATE TABLE `hunuo_user_inv_title` (
  `title_id` mediumint(8) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` mediumint(8) unsigned NOT NULL DEFAULT '0' COMMENT '用户ID',
  `inv_title` varchar(60) DEFAULT NULL COMMENT '发票抬头',
  `add_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '添加时间',
  PRIMARY KEY (`title_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- 商品点赞记录
CREATE TABLE `hunuo_goods_zan` (
  `zan_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `goods_id` int(11) NOT NULL COMMENT '商品id',
  `user_id` int(11) NOT NULL COMMENT '给商品点赞的用户',
  `add_time` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`zan_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- 商品举报
CREATE TABLE `hunuo_goods_report` (
  `report_id` mediumint(8) unsigned NOT NULL AUTO_INCREMENT,
  `goods_id` int(11) NOT NULL COMMENT '商品id',
  `goods_name` varchar(120) NOT NULL DEFAULT '',
  `user_id` mediumint(8) unsigned NOT NULL DEFAULT '0' COMMENT '举报人用户id',
  `designer_id` mediumint(8) unsigned NOT NULL DEFAULT '0' COMMENT '设计师用户id',
  `report_reason` text NOT NULL COMMENT '举报原因',
  `report_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '举报时间',
  PRIMARY KEY (`report_id`),
  KEY `user_id` (`user_id`),
  KEY `designer_id` (`designer_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;