2.44;

update `%DB_PREFIX%m_config` set `desc` = '用于网页应用微信登录' where `code` = 'wx_web_appid';
update `%DB_PREFIX%m_config` set `desc` = '用于网页应用微信登录' where `code` = 'wx_web_secrit';
update `%DB_PREFIX%m_config` set `desc` = '用于网页应用微博登录' where `code` = 'sina_web_app_key';
update `%DB_PREFIX%m_config` set `desc` = '用于网页应用微博登录' where `code` = 'sina_web_app_secret';

UPDATE `%DB_PREFIX%conf` SET `value`='2.24' WHERE (`name`='DB_VERSION');

INSERT INTO `%DB_PREFIX%role_node` (`action`, `name`, `is_effect`, `is_delete`, `group_id`, `module_id`) VALUES ('index', '列表', '1', '0', '0', (select id from `%DB_PREFIX%role_module` where module='PlugIn'));
INSERT INTO `%DB_PREFIX%role_node` (`action`, `name`, `is_effect`, `is_delete`, `group_id`, `module_id`) VALUES ('edit', '编辑', '1', '0', '0', (select id from `%DB_PREFIX%role_module` where module='PlugIn'));

INSERT INTO `%DB_PREFIX%m_config` VALUES (null, 'pc_has_private_chat', 'PC端允许私信', 'PC端设置', '1', 4, 0, '0,1', '否,是', '是否开启私信功能，1开启 0关闭');

ALTER TABLE `%DB_PREFIX%recharge_rule`
ADD COLUMN `iap_diamonds`  int(11) NULL DEFAULT 0 COMMENT '苹果支付获取钻石';

UPDATE `%DB_PREFIX%conf` SET `value`='2.25' WHERE (`name`='DB_VERSION');

INSERT INTO `%DB_PREFIX%m_config` (`code`, `title`, `group_id`, `val`, `type`, `sort`, `value_scope`, `title_scope`, `desc`) VALUES ('distribution_wx', '显示微信登录', '分销模块', '0', 4, 0, '0,1', '否,是', '屏蔽微信登录方式，不影响分享等其他微信相关功能 1是 0否');
INSERT INTO `%DB_PREFIX%m_config` (`code`, `title`, `group_id`, `val`, `type`, `sort`, `value_scope`, `title_scope`, `desc`) VALUES ('distribution_qq', '显示QQ登录', '分销模块', '0', 4, 0, '0,1', '否,是', '屏蔽QQ登录方式，不影响分享等其他QQ相关功能 1是 0否');
INSERT INTO `%DB_PREFIX%m_config` (`code`, `title`, `group_id`, `val`, `type`, `sort`, `value_scope`, `title_scope`, `desc`) VALUES ('distribution_sina', '显示微博登录', '分销模块', '0', 4, 0, '0,1', '否,是', '屏蔽微博登录方式，不影响分享等其他微博相关功能 1是 0否');

UPDATE `%DB_PREFIX%conf` SET `value`='2.26' WHERE (`name`='DB_VERSION');

ALTER TABLE `%DB_PREFIX%family`
ADD COLUMN `user_count` int(11) NOT NULL DEFAULT 1 COMMENT '成员数量' after `user_id`;

INSERT INTO `%DB_PREFIX%m_config` (`code`, `title`, `group_id`, `val`, `type`, `sort`, `value_scope`, `title_scope`, `desc`) VALUES ('live_page_size', '监控界面分页', '基础配置', '10', 0, 0, NULL, NULL, '(条) 监控界面分页数量，默认为10，最低数量不能低于10条，否则取默认值');
CREATE TABLE `%DB_PREFIX%pc_goods` (
`id`  int(11) NOT NULL AUTO_INCREMENT COMMENT '自增字段' ,
`user_id`  int(11) NOT NULL COMMENT '主播ID' ,
`name`  varchar(50) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '商品名称' ,
`imgs`  text CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL COMMENT '图片（JSON数据）' ,
`price`  decimal(20,2) NOT NULL COMMENT '商品价钱' ,
`url`  varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL COMMENT '商品详情URL地址' ,
`description`  text CHARACTER SET utf8 COLLATE utf8_general_ci NULL COMMENT '商品描述' ,
`is_delete`  tinyint(1) NOT NULL COMMENT '商品状态 0为正常,1为删除（值为1时不在前端展示）' ,
`kd_cost`  decimal(20,2) NOT NULL COMMENT '快递费用' ,
PRIMARY KEY (`id`)
)
;
UPDATE `%DB_PREFIX%m_config` SET val=NULL WHERE `code`='pc_download_slogan';
UPDATE `%DB_PREFIX%m_config` SET val=NULL WHERE `code`='pc_logo';
UPDATE `%DB_PREFIX%conf` SET `value`='2.27' WHERE (`name`='DB_VERSION');

ALTER TABLE `%DB_PREFIX%user`
ADD COLUMN `is_hot_on`  tinyint(1) NOT NULL DEFAULT 0 COMMENT '禁热门 0-正常；1-禁止';

CREATE TABLE `%DB_PREFIX%warning_msg` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `content` varchar(255) NOT NULL COMMENT '警告内容',
  `is_effect` tinyint(1) DEFAULT '1' COMMENT '是否有效 0:无效;1:有效',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

UPDATE `%DB_PREFIX%conf` SET `value`='2.29' WHERE (`name`='DB_VERSION');

INSERT INTO `%DB_PREFIX%conf` (`name`, `value`, `group_id`, `input_type`, `value_scope`, `is_effect`, `is_conf`, `sort`) VALUES ('COPYRIGHT', 'Copyright 2016-2017 sjzhsd.cn All rights reserved.', 1, 0, '', 1, 1, 24);

UPDATE `%DB_PREFIX%conf` SET `is_effect`='0' WHERE (`name`='USER_VERIFY_STATUS');

INSERT INTO `%DB_PREFIX%conf` (`name`, `value`, `group_id`, `input_type`, `value_scope`, `is_effect`, `is_conf`, `sort`) VALUES ('BG_PAGE', '', 1, 2, '', 1, 1, 24);

INSERT INTO `%DB_PREFIX%conf` (`name`, `value`, `group_id`, `input_type`, `value_scope`, `is_effect`, `is_conf`, `sort`) VALUES ('BG_APP', '', 1, 2, '', 1, 1, 24);

UPDATE `%DB_PREFIX%conf` SET `value`='2.291' WHERE (`name`='DB_VERSION');

UPDATE `%DB_PREFIX%m_config` SET `desc`='默认：否；IOS版本只有苹果支付；（开启其他支付(微信,支付宝等)选择：是；注：若开启，则IOS会有被苹果下架风险）' WHERE (`code`='ios_open_pay');

INSERT INTO `%DB_PREFIX%m_config` (`code`, `title`, `group_id`, `val`, `type`, `sort`, `value_scope`, `title_scope`, `desc`) VALUES ('domain_list', '备用域名', '应用设置', '', 3, 100, NULL, NULL, '备用域名列表，每行填写一个域名');

UPDATE `%DB_PREFIX%conf` SET `value`='2.292' WHERE (`name`='DB_VERSION');

ALTER TABLE `%DB_PREFIX%video`
ADD COLUMN `len_time`  int(11) NOT NULL COMMENT '直播的时长';

ALTER TABLE `%DB_PREFIX%video_history`
ADD COLUMN `len_time`  int(11) NOT NULL COMMENT '直播的时长';

UPDATE `%DB_PREFIX%conf` SET `value`='2.3' WHERE (`name`='DB_VERSION');

ALTER TABLE `%DB_PREFIX%video`
ADD COLUMN `is_concatvideo`  tinyint(1) NOT NULL COMMENT '视频是否合并 0 未合并，1 已合并';

ALTER TABLE `%DB_PREFIX%video_history`
ADD COLUMN `is_concatvideo`  tinyint(1) NOT NULL COMMENT '视频是否合并 0 未合并，1 已合并';

UPDATE `%DB_PREFIX%m_config` SET `desc`='备用域名列表，每行填写一个域名，头部要包含http或https，例如 http://www.xx.com' WHERE (`code`='domain_list');

CREATE TABLE `%DB_PREFIX%login_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `user_id` int(11) NOT NULL COMMENT '会员ID',
  `create_time` varchar(20) NOT NULL COMMENT '登录时间',
  `ip` varchar(20) NOT NULL COMMENT 'ip',
  `login_time` int(11) NOT NULL COMMENT '登录时间',
  `login_date` datetime NOT NULL COMMENT '登录时间',
  `login_type` tinyint(1) NOT NULL COMMENT '登录方式',
  `request` text NOT NULL COMMENT '请求参数',
  `ctl_act` varchar(20) NOT NULL COMMENT '请求接口',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;

UPDATE `%DB_PREFIX%conf` SET `value`='2.31' WHERE (`name`='DB_VERSION');

ALTER TABLE `%DB_PREFIX%video`
ADD COLUMN `stick`  tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否置顶 0 不置顶 1 置顶';

ALTER TABLE `%DB_PREFIX%video_history`
ADD COLUMN `stick`  tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否置顶 0 不置顶 1 置顶';

ALTER TABLE `%DB_PREFIX%user`
ADD COLUMN `investor_time`  int(10) NOT NULL COMMENT '审核失败时间';

ALTER TABLE `%DB_PREFIX%user`
ADD COLUMN `alone_ticket_ratio`  varchar(255) NOT NULL COMMENT '设置主播提现比例,如果为空,则使用后台通用比例';

ALTER TABLE `%DB_PREFIX%user`
ADD COLUMN `open_game`  tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否开启游戏  0为 不禁用 1 为禁用';
ALTER TABLE `%DB_PREFIX%user`
ADD COLUMN `open_pay`  tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否开启付费  0为 不禁用 1 为禁用';
ALTER TABLE `%DB_PREFIX%user`
ADD COLUMN `open_auction`  tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否开启竞拍  0为 不禁用 1 为禁用';
ALTER TABLE `%DB_PREFIX%user`
ADD COLUMN `family_recom`  varchar(6) NOT NULL COMMENT '家族推荐号 填写后审核通过自动加入相对应的家族';
ALTER TABLE `%DB_PREFIX%fanwe_family`
ADD COLUMN `family_recom`  varchar(6) NOT NULL COMMENT '家族推荐号 创建家族后随机生成，用于主播审核时填写';

INSERT INTO `%DB_PREFIX%m_config` (`code`, `title`, `group_id`, `val`, `type`, `sort`, `value_scope`, `title_scope`, `desc`) VALUES ('attestation_time', '认证审核时间', '应用设置', '0', 0, 2, '', '', '审核失败后下次可申请的时间（单位：秒）');

INSERT INTO `%DB_PREFIX%m_config` (`code`, `title`, `group_id`, `val`, `type`, `sort`, `value_scope`, `title_scope`, `desc`) VALUES ('top_weight', '置顶权重值', '应用设置', '0', 0, 2, '', '', '设置视频置顶时增加的权重(单位：亿)');

INSERT INTO `%DB_PREFIX%m_config` (`code`, `title`, `group_id`, `val`, `type`, `sort`, `value_scope`, `title_scope`, `desc`) VALUES ('refund_explain', '提现说明', '提现设置', '', 3, 100, '', '', '');

INSERT INTO `%DB_PREFIX%m_config` (`code`, `title`, `group_id`, `val`, `type`, `sort`, `value_scope`, `title_scope`, `desc`) VALUES ('speak_level', '发言等级', '应用设置', '0', 0, 2, '', '', '设置多少级才可以发言');

CREATE TABLE `%DB_PREFIX%video_classified` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `title` varchar(100) NOT NULL COMMENT '分类名称',
  `is_effect` tinyint(1) NOT NULL DEFAULT '1' COMMENT '是否有效 1-有效 0-无效',
  `sort` int(11) NOT NULL DEFAULT '0' COMMENT '从大到小排',
  PRIMARY KEY (`id`),
  KEY `idx_vc_001` (`title`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COMMENT='分类表';

ALTER TABLE `%DB_PREFIX%video`
ADD COLUMN `classified_id`  int(11) NOT NULL DEFAULT 0 COMMENT '分类id';

ALTER TABLE `%DB_PREFIX%video_history`
ADD COLUMN `classified_id`  int(11) NOT NULL DEFAULT 0 COMMENT '分类id';

ALTER TABLE `%DB_PREFIX%user`
ADD COLUMN `classified_id`  int(11) NOT NULL DEFAULT 0 COMMENT '分类id';

ALTER TABLE `%DB_PREFIX%user_refund`
ADD COLUMN `confirm_cash_ip`  varchar(255) NOT NULL  COMMENT '确认提现操作IP';

UPDATE `%DB_PREFIX%conf` SET `value`='2.32' WHERE (`name`='DB_VERSION');

ALTER TABLE `%DB_PREFIX%login_log`
MODIFY COLUMN `id`  int(11) NOT NULL AUTO_INCREMENT COMMENT 'ID' FIRST ;

INSERT INTO `%DB_PREFIX%m_config` (`code`, `title`, `group_id`, `val`, `type`, `sort`, `value_scope`, `title_scope`, `desc`) VALUES ('forced_upgrade', '客户端是否强制升级', 'APP版本管理', '0', 4, 99, '0,1', '否,是', '开启强制升级，不升级无法进入直播间 0:否;1:是');
INSERT INTO `%DB_PREFIX%m_config` (`code`, `title`, `group_id`, `val`, `type`, `sort`, `value_scope`, `title_scope`, `desc`) VALUES ('forced_upgrade_tips', '强制升级', 'APP版本管理', '请升级后,观看视频【我的==>设置==>检查版本】', 3, 100, '', '', '开启强制升级的提醒');

INSERT INTO `%DB_PREFIX%m_config` (`code`, `title`, `group_id`, `val`, `type`, `sort`, `value_scope`, `title_scope`, `desc`) VALUES ('agora_app_id', '声网AppID', '应用设置', '', 0, 111, '', '', '声网AppID');
INSERT INTO `%DB_PREFIX%m_config` (`code`, `title`, `group_id`, `val`, `type`, `sort`, `value_scope`, `title_scope`, `desc`) VALUES ('agora_app_certificate', '声网AppCertificate', '应用设置', '', 0, 112, '', '', '声网AppCertificate');
INSERT INTO `%DB_PREFIX%m_config` (`code`, `title`, `group_id`, `val`, `type`, `sort`, `value_scope`, `title_scope`, `desc`) VALUES ('agora_anchor_resolution', '主播分辨率', '应用设置', '0', 4, 113, '0,1,2,3', '240*424,360*640,480*848,720*1280', '主播分辨率');
INSERT INTO `%DB_PREFIX%m_config` (`code`, `title`, `group_id`, `val`, `type`, `sort`, `value_scope`, `title_scope`, `desc`) VALUES ('agora_audience_resolution', '连麦观众分辨率', '应用设置', '0', 4, 114, '0,1,2,3', '180*320,240*424,360*640,480*848', '连麦观众分辨率');

ALTER TABLE `%DB_PREFIX%user`
ADD COLUMN `allinpay_user_id`  VARCHAR (20) NOT NULL COMMENT '通联支付的用户ID(在通联网站的注册的userID)';

UPDATE `%DB_PREFIX%conf` SET `value`='2.33' WHERE (`name`='DB_VERSION');


UPDATE `%DB_PREFIX%m_config` SET `title`='腾讯云直播appid', `desc`='腾讯云直播APP_ID' WHERE (`code`='vodset_app_id');

UPDATE `%DB_PREFIX%m_config` SET `sort`='1',`desc`='腾讯云云通信SdkAppId' WHERE (`code`='tim_sdkappid');
UPDATE `%DB_PREFIX%m_config` SET `sort`='2',`desc`='腾讯云云通信账号管理员' WHERE (`code`='tim_identifier');
UPDATE `%DB_PREFIX%m_config` SET `sort`='3',`desc`='腾讯云云通信accountType' WHERE (`code`='tim_account_type');

UPDATE `%DB_PREFIX%m_config` SET `sort`='10',`desc`='腾讯云直播管理推流防盗key' WHERE (`code`='qcloud_security_key');
UPDATE `%DB_PREFIX%m_config` SET `sort`='11',`desc`='腾讯云直播管理API鉴权key' WHERE (`code`='qcloud_auth_key');
UPDATE `%DB_PREFIX%m_config` SET `sort`='12',`desc`='腾讯云直播APP_ID' WHERE (`code`='vodset_app_id');
UPDATE `%DB_PREFIX%m_config` SET `sort`='13',`desc`='腾讯云直播bizid' WHERE (`code`='qcloud_bizid');

UPDATE `%DB_PREFIX%m_config` SET `title`='云API帐户SecretId', `sort`='37',`desc`='腾讯【云API帐户SecretId】' WHERE (`code`='qcloud_secret_id');
UPDATE `%DB_PREFIX%m_config` SET `title`='云API密钥SecretKey', `sort`='37',`desc`='腾讯【云API密钥SecretKey】' WHERE (`code`='qcloud_secret_key');
UPDATE `%DB_PREFIX%m_config` SET `sort`='38',`desc`='保存视频（可用于回播）;0:否;1:是' WHERE (`code`='has_save_video');
UPDATE `%DB_PREFIX%m_config` SET `sort`='38',`desc`='清晰度越高,流量费用越高' WHERE (`code`='video_resolution_type');

INSERT INTO `%DB_PREFIX%m_config` (`code`, `title`, `group_id`, `val`, `type`, `sort`, `value_scope`, `title_scope`, `desc`) VALUES ('open_usersig_cache', '强制更新usersig', '腾讯直播', '0', 4, 4, '0,1', '否,是', '开启强制更新usersig缓存，0关闭 1开启 默认不开启');

UPDATE `%DB_PREFIX%conf` SET `value`='2.35' WHERE (`name`='DB_VERSION');

UPDATE `%DB_PREFIX%m_config` SET `desc`='手机端配置版本号格式(yyyymmddnn)(用接口初始化)' WHERE (`code`='init_version');


UPDATE `%DB_PREFIX%conf` SET `value`='2.36' WHERE (`name`='DB_VERSION');

CREATE TABLE `%DB_PREFIX%key_list` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `type` varchar(255) NOT NULL COMMENT '手机端类型',
  `aes_key` text NOT NULL COMMENT '加密KEY',
  `is_delete` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否删除 1：是 0：否',
  `is_init` tinyint(1) NOT NULL COMMENT '是否打包填写 1是 、0否',
  `version` varchar(255) NOT NULL COMMENT '版本',
  `is_effect` varchar(255) NOT NULL COMMENT '是否有效',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COMMENT='加密KET列表';

UPDATE `%DB_PREFIX%conf` SET `value`='2.36' WHERE (`name`='DB_VERSION');

ALTER TABLE `%DB_PREFIX%video_lianmai`
ADD COLUMN `channelid`  varchar(255) NOT NULL COMMENT '有些早期提供的API中直播码参数被定义为channel_id，新的API则称直播码为stream_id，仅历史原因而已';
ALTER TABLE `%DB_PREFIX%video_lianmai`
ADD COLUMN `play_rtmp`  varchar(255) NOT NULL COMMENT '小主播的rtmpAcc地址';
ALTER TABLE `%DB_PREFIX%video_lianmai`
ADD COLUMN `play_rtmp_acc`  varchar(255) NOT NULL COMMENT '';
ALTER TABLE `%DB_PREFIX%video_lianmai`
ADD COLUMN `v_play_rtmp_acc`  varchar(255) NOT NULL COMMENT '';

ALTER TABLE `%DB_PREFIX%video_lianmai_history`
ADD COLUMN `channelid`  varchar(255) NOT NULL COMMENT '有些早期提供的API中直播码参数被定义为channel_id，新的API则称直播码为stream_id，仅历史原因而已';
ALTER TABLE `%DB_PREFIX%video_lianmai_history`
ADD COLUMN `play_rtmp`  varchar(255) NOT NULL COMMENT '小主播的rtmpAcc地址';
ALTER TABLE `%DB_PREFIX%video_lianmai_history`
ADD COLUMN `play_rtmp_acc`  varchar(255) NOT NULL COMMENT '';
ALTER TABLE `%DB_PREFIX%video_lianmai_history`
ADD COLUMN `v_play_rtmp_acc`  varchar(255) NOT NULL COMMENT '';

UPDATE `%DB_PREFIX%conf` SET `value`='2.37' WHERE (`name`='DB_VERSION');

INSERT INTO `%DB_PREFIX%m_config` (`code`, `title`, `group_id`, `val`, `type`, `sort`, `value_scope`, `title_scope`, `desc`) VALUES
('open_visitors_login', '游客登录', '应用设置', '0', 4, 50, '0,1', '否,是', '是否开启游客登录 0:否;1:是');

ALTER TABLE `%DB_PREFIX%user`
MODIFY COLUMN `login_type`  tinyint(1) NOT NULL COMMENT '0：微信；1：QQ；2：手机；3：微博 ;4 : 游客登录';

UPDATE `%DB_PREFIX%conf` SET `value`='2.38' WHERE (`name`='DB_VERSION');

INSERT INTO `%DB_PREFIX%m_config` VALUES ('', 'search_change', 'APP搜索类型', '应用设置', '0', '4', '150', '0,1', '精确搜索,模糊搜索', '设置APP搜索类型 0精确 1模糊');

UPDATE `%DB_PREFIX%conf` SET `value`='2.39' WHERE (`name`='DB_VERSION');

UPDATE `%DB_PREFIX%m_config` SET `value_scope`= '1,2,5', `title_scope`= '腾讯云直播,金山云,阿里云' WHERE (`code`='video_type');

INSERT INTO `%DB_PREFIX%m_config` (`code`, `title`, `group_id`, `val`, `type`, `sort`, `value_scope`, `title_scope`, `desc`) VALUES ('aliyun_access_key', 'Access Key ID', '阿里云', '', 0, 0, NULL, NULL, NULL);
INSERT INTO `%DB_PREFIX%m_config` (`code`, `title`, `group_id`, `val`, `type`, `sort`, `value_scope`, `title_scope`, `desc`) VALUES ('aliyun_access_secret', 'Access Key Secret', '阿里云', '', 0, 0, NULL, NULL, NULL);
INSERT INTO `%DB_PREFIX%m_config` (`code`, `title`, `group_id`, `val`, `type`, `sort`, `value_scope`, `title_scope`, `desc`) VALUES ('aliyun_region', '阿里云节点', '阿里云', 'cn-shanghai', 0, 0, NULL, NULL, NULL);
INSERT INTO `%DB_PREFIX%m_config` (`code`, `title`, `group_id`, `val`, `type`, `sort`, `value_scope`, `title_scope`, `desc`) VALUES ('aliyun_private_key', '推流鉴权key', '阿里云', '', 0, 0, NULL, NULL, NULL);
INSERT INTO `%DB_PREFIX%m_config` (`code`, `title`, `group_id`, `val`, `type`, `sort`, `value_scope`, `title_scope`, `desc`) VALUES ('aliyun_vhost', '加速域名', '阿里云', '', 3, 0, NULL, NULL, '一行一个域名');

CREATE TABLE `%DB_PREFIX%video_aliyun` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `vhost` varchar(255) NOT NULL,
  `stream_id` varchar(255) NOT NULL DEFAULT '',
  PRIMARY KEY (`id`),
  KEY `video_num` (`stream_id`),
  KEY `vhost` (`vhost`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;

UPDATE `%DB_PREFIX%conf` SET `value`='2.4' WHERE (`name`='DB_VERSION');

INSERT INTO `%DB_PREFIX%m_config` (`code`, `title`, `group_id`, `val`, `type`, `sort`, `value_scope`, `title_scope`, `desc`) VALUES ('account_ip', '后台登录IP', '基础配置', '', 3, 120, '', '', '后台允许登录的ip,每行填写一个ip');
UPDATE `%DB_PREFIX%m_config` SET `type`='3',`desc`= '后台允许登录的ip,每行填写一个ip' WHERE (`code`='account_ip' and `type`<>'3');


INSERT INTO `%DB_PREFIX%m_config` (`code`, `title`, `group_id`, `val`, `type`, `sort`, `value_scope`, `title_scope`, `desc`) VALUES ('account_mobile', '后台登录绑定手机号', '基础配置', '', 0, 121, '', '', '后台登录接收验证码手机');

ALTER TABLE `%DB_PREFIX%user`
ADD COLUMN `alone_ticket_ratio`  varchar(255) NOT NULL COMMENT '设置主播提现比例,如果为空,则使用后台通用比例';

INSERT INTO `%DB_PREFIX%m_config` (`code`, `title`, `group_id`, `val`, `type`, `sort`, `value_scope`, `title_scope`, `desc`) VALUES ('alipay_cache_time', '支付宝更换缓存', '应用设置', '60', 0, 130, '', '', '（秒）多支付宝账号更新间隔，时间最少不能低于60秒');

UPDATE `%DB_PREFIX%conf` SET `value`='2.41' WHERE (`name`='DB_VERSION');

INSERT INTO `%DB_PREFIX%m_config` (`code`, `title`, `group_id`, `val`, `type`, `sort`, `value_scope`, `title_scope`, `desc`) VALUES ('send_msg_lv', '发言等级限制', '应用设置', '1', 0, 160, NULL, NULL, '会员等级>=当前设定的等级时,才能进行发言');
INSERT INTO `%DB_PREFIX%m_config` (`code`, `title`, `group_id`, `val`, `type`, `sort`, `value_scope`, `title_scope`, `desc`) VALUES ('is_show_identify_number', '是否需要身份验证', '应用设置', '1', 4, 160, '0,1', '否,是', '认证时是否需要输入身份证号码 0否 1是');
INSERT INTO `%DB_PREFIX%m_config` (`code`, `title`, `group_id`, `val`, `type`, `sort`, `value_scope`, `title_scope`, `desc`) VALUES ('identify_hold_example', '手持身份证示例图片', '应用设置', '', 2, 160, NULL, NULL, '手持身份证示例图片');
INSERT INTO `%DB_PREFIX%m_config` (`code`, `title`, `group_id`, `val`, `type`, `sort`, `value_scope`, `title_scope`, `desc`) VALUES ('show_follow_msg', '是否显示关注提示信息', '应用设置', '1', 4, 165, '0,1', '否,是', '是否发送用户关注提示信息到直播间 0否 1是');
INSERT INTO `%DB_PREFIX%m_config` (`code`, `title`, `group_id`, `val`, `type`, `sort`, `value_scope`, `title_scope`, `desc`) VALUES ('show_follow_msg_lv', '显示关注提示所需等级', '应用设置', '1', 0, 166, NULL, NULL, '会员等级>=当前设定的等级时,才显示关注信息到直播间');



INSERT INTO `%DB_PREFIX%m_config` VALUES (null, 'ksyun_app', 'Ksyun App', '金山云', '', '0', '0', null, null, null);
INSERT INTO `%DB_PREFIX%m_config` VALUES (null, 'ksyun_domain', 'Ksyun Domain', '金山云', '', '0', '0', null, null, null);
INSERT INTO `%DB_PREFIX%m_config` VALUES (null, 'ks3_accesskey', 'ks3 Accesskey', '金山云', '', '0', '0', null, null, null);
INSERT INTO `%DB_PREFIX%m_config` VALUES (null, 'ks3_secretkey', 'ks3 Secretkey', '金山云', '', '0', '0', null, null, null);

ALTER TABLE `%DB_PREFIX%video_aliyun`
ADD COLUMN `create_time` int(11) NOT NULL;

ALTER TABLE `%DB_PREFIX%live_pay_log`
ADD COLUMN `pay_type`  tinyint(1) NOT NULL COMMENT '是否为公屏收费记录 0 否； 1 是；';

ALTER TABLE `%DB_PREFIX%live_pay_log_history`
ADD COLUMN `pay_type`  tinyint(1) NOT NULL COMMENT '是否为公屏收费记录 0 否； 1 是；';

ALTER TABLE `%DB_PREFIX%video_lianmai`
ADD COLUMN `push_rtmp`  varchar(255) NOT NULL COMMENT '小主播推流地址' AFTER `channelid`;

ALTER TABLE `%DB_PREFIX%video_lianmai_history`
ADD COLUMN `push_rtmp`  varchar(255) NOT NULL COMMENT '小主播推流地址' AFTER `channelid`;

UPDATE `%DB_PREFIX%conf` SET `value`='2.42' WHERE (`name`='DB_VERSION');

UPDATE `%DB_PREFIX%m_config` SET `desc`='家族收取主播收益的比例(如 10% 则填10)' WHERE (`code`='profit_ratio');

UPDATE `%DB_PREFIX%conf` SET `value`='2.42' WHERE (`name`='DB_VERSION');

CREATE TABLE `%DB_PREFIX%video_check` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'id,也是房间room_id',
  `title` varchar(255) NOT NULL COMMENT '直播标题',
  `user_id` int(11) NOT NULL COMMENT '项目id',
  `live_in` tinyint(1) DEFAULT '0' COMMENT '是否直播中 1-直播中 0-已停止;2:正在创建直播;3:回看',
  `watch_number` int(11) DEFAULT '0' COMMENT '当前实时观看人数（实际,不含虚拟人数,不包含机器人)',
  `virtual_watch_number` int(10) NOT NULL DEFAULT '0' COMMENT '当前虚拟观看人数',
  `vote_number` int(11) DEFAULT '0' COMMENT '获得票数',
  `cate_id` int(11) NOT NULL DEFAULT '0' COMMENT '话题id',
  `province` varchar(20) NOT NULL COMMENT '省份',
  `city` varchar(20) NOT NULL COMMENT '城市',
  `create_time` int(11) NOT NULL DEFAULT '0' COMMENT '创建时间',
  `begin_time` int(11) NOT NULL DEFAULT '0' COMMENT '开始时间',
  `end_time` int(11) NOT NULL DEFAULT '0' COMMENT '结束时间',
  `end_date` date NOT NULL COMMENT '结束日期',
  `group_id` varchar(50) NOT NULL COMMENT '群组ID,通过create_group后返回的值;直播结束后解散群',
  `destroy_group_status` tinyint(1) NOT NULL DEFAULT '1' COMMENT '1：未解散;0:已解散;其它为ErrorCode错码',
  `long_polling_key` varchar(255) NOT NULL COMMENT '通过create_group后返回的LongPollingKey值',
  `max_watch_number` int(10) NOT NULL DEFAULT '0' COMMENT '最大观看人数(每进来一人次加1）',
  `room_type` tinyint(1) NOT NULL COMMENT '房间类型 : 1私有群（Private）,0公开群（Public）,2聊天室（ChatRoom）,3互动直播聊天室（AVChatRoom）',
  `is_playback` tinyint(1) DEFAULT '0' COMMENT '是否可回放 0-否 ；1-是',
  `video_vid` varchar(255) NOT NULL COMMENT '视频地址',
  `monitor_time` datetime NOT NULL COMMENT '最后心跳监听时间；如果超过监听时间，则说明主播已经掉线了',
  `is_delete` tinyint(1) NOT NULL DEFAULT '0' COMMENT '1:删除;0:未删除;私有聊天或小于5分钟的视频，不保存',
  `robot_num` int(10) NOT NULL DEFAULT '0' COMMENT '聊天群中机器人数量',
  `robot_time` int(10) NOT NULL DEFAULT '0' COMMENT '添加机器人时间（每隔20秒左右加几个人）',
  `channelid` varchar(50) NOT NULL COMMENT '旁路直播,频道ID',
  `is_aborted` tinyint(1) NOT NULL DEFAULT '0' COMMENT '1:被服务器异常终止结束(主要是心跳超时)',
  `is_del_vod` tinyint(1) NOT NULL DEFAULT '0' COMMENT '1:表示已经清空了,录制视频;0:未做清空操作',
  `online_status` tinyint(1) NOT NULL DEFAULT '0' COMMENT '主播在线状态;1:在线(默认); 0:离开',
  `tipoff_count` int(10) NOT NULL DEFAULT '0' COMMENT '举报次数',
  `private_key` varchar(32) NOT NULL COMMENT '私密直播key',
  `share_type` varchar(30) NOT NULL COMMENT '分享类型WEIXIN,WEIXIN_CIRCLE,QQ,QZONE,SINA',
  `sort` int(11) NOT NULL DEFAULT '0' COMMENT '热门排序',
  `pai_id` int(11) NOT NULL DEFAULT '0' COMMENT '竞拍id',
  `sex` tinyint(1) NOT NULL DEFAULT '0' COMMENT '性别 0:未知, 1-男，2-女',
  `video_type` tinyint(1) NOT NULL DEFAULT '0' COMMENT '0:腾讯云互动直播;1:腾讯云直播',
  `sort_num` int(10) NOT NULL DEFAULT '0' COMMENT 'sort_init + share_count * 分享权重 + like_count * 点赞权重 + fans_count * 关注权重 + sort * 排序权重 + ticket(本场收到的印票) * 印票权重',
  `create_type` tinyint(1) NOT NULL DEFAULT '0' COMMENT '0:APP端创建的直播;1:PC端创建的直播',
  `max_robot_num` int(10) NOT NULL DEFAULT '0' COMMENT '默认最大机器人头像数',
  `share_count` int(10) NOT NULL DEFAULT '0' COMMENT '分享数',
  `like_count` int(10) NOT NULL DEFAULT '0' COMMENT '点赞数,每个用户只记录一次',
  `fans_count` int(10) NOT NULL DEFAULT '0' COMMENT '本场直播净添加的粉丝数即：被关注数，关注加1，取消减1',
  `sort_init` int(10) NOT NULL DEFAULT '0' COMMENT 'sort_init(初始排序权重) = (用户可提现印票：fanwe_user.ticket - fanwe_user.refund_ticket) * 保留印票权重+ 直播/回看[回看是：0; 直播：9000000000 直播,需要排在最上面 ]+ fanwe_user.user_level * 等级权重+ fanwe_user.fans_count * 当前有的关注数权重',
  `push_rtmp` varchar(255) NOT NULL COMMENT '推流地址',
  `play_flv` varchar(255) NOT NULL COMMENT '播放地址；当video_type=0时，记录：傍路直播地址',
  `play_rtmp` varchar(255) NOT NULL COMMENT '播放地址；当video_type=0时，记录：傍路直播地址',
  `play_mp4` varchar(255) NOT NULL COMMENT '播放地址；当video_type=0时，记录：傍路直播地址',
  `play_hls` varchar(255) NOT NULL COMMENT '播放地址；当video_type=0时，记录：傍路直播地址',
  `xpoint` decimal(10,6) NOT NULL DEFAULT '0.000000' COMMENT 'x座标(用来计算：附近)',
  `ypoint` decimal(10,6) NOT NULL DEFAULT '0.000000' COMMENT 'y座标(用来计算：附近)',
  `head_image` varchar(255) NOT NULL COMMENT '直播时，可自定义封面图; 如果不存在,则取会员头像',
  `thumb_head_image` varchar(255) NOT NULL COMMENT '模糊图片',
  `play_url` varchar(255) NOT NULL COMMENT '播放地址',
  `live_image` varchar(255) NOT NULL COMMENT '视频封面',
  `is_recommend` int(1) NOT NULL COMMENT '推荐视频 0不推荐、1推荐',
  `virtual_number` int(11) NOT NULL COMMENT '最大虚拟人数',
  `room_title` varchar(100) DEFAULT NULL COMMENT '直播间名称',
  `live_pay_time` int(11) NOT NULL COMMENT '开始收费时间',
  `is_live_pay` tinyint(1) NOT NULL COMMENT '是否收费模式  1是 0否',
  `live_fee` int(11) NOT NULL COMMENT '付费直播 收取多少费用； 每分钟收取多少钻石，主播端设置',
  `live_is_mention` tinyint(1) NOT NULL COMMENT '是否已经提档 1是、0否',
  `live_pay_count` tinyint(1) NOT NULL COMMENT '付费人数',
  `pay_room_id` int(11) NOT NULL COMMENT '付费直播的ID , 用于标示直播间付费 模式 ',
  `prop_table` varchar(255) NOT NULL DEFAULT 'fanwe_video_prop' COMMENT '直播礼物表',
  `live_pay_type` tinyint(1) NOT NULL DEFAULT '2' COMMENT '收费类型 0按时收费，1按场次收费,未开启收费模式,默认为2',
  `len_time` int(11) NOT NULL COMMENT '直播的时长',
  `is_concatvideo` tinyint(1) NOT NULL COMMENT '视频是否合并 0 未合并，1 已合并',
  `stick` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否置顶 0 不置顶 1 置顶',
  `classified_id` int(11) NOT NULL DEFAULT '0' COMMENT '分类id',
  `tags` text NOT NULL COMMENT '标签',
  `video_status` tinyint(1) NOT NULL COMMENT '//视频当前状态, 0:无状态 、1：已保存、2：有分片、3：分片已合并、4：合并完成已删除分片，5：拉取完成',
  `vodtaskid` varchar(50) NOT NULL COMMENT '//任务id，用户根据此字段匹配服务端事件通知',
  `file_id` varchar(50) NOT NULL COMMENT '//腾讯云 的 视频ID',
  `source_url` varchar(255) NOT NULL COMMENT '//拉流原视频地址',
  PRIMARY KEY (`id`),
  KEY `idx_v_001` (`user_id`) USING BTREE,
  KEY `idx_v_003` (`live_in`) USING BTREE,
  KEY `idx_v_002` (`group_id`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COMMENT='直播历史表';

INSERT INTO `%DB_PREFIX%video_check` (`id`, `title`, `user_id`, `live_in`, `watch_number`, `virtual_watch_number`, `vote_number`, `cate_id`, `province`, `city`, `create_time`, `begin_time`, `end_time`, `end_date`, `group_id`, `destroy_group_status`, `long_polling_key`, `max_watch_number`, `room_type`, `is_playback`, `video_vid`, `monitor_time`, `is_delete`, `robot_num`, `robot_time`, `channelid`, `is_aborted`, `is_del_vod`, `online_status`, `tipoff_count`, `private_key`, `share_type`, `sort`, `pai_id`, `sex`, `video_type`, `sort_num`, `create_type`, `max_robot_num`, `share_count`, `like_count`, `fans_count`, `sort_init`, `push_rtmp`, `play_flv`, `play_rtmp`, `play_mp4`, `play_hls`, `xpoint`, `ypoint`, `head_image`, `thumb_head_image`, `play_url`, `live_image`, `is_recommend`, `virtual_number`, `room_title`, `live_pay_time`, `is_live_pay`, `live_fee`, `live_is_mention`, `live_pay_count`, `pay_room_id`, `prop_table`, `live_pay_type`, `len_time`, `is_concatvideo`, `stick`, `classified_id`, `tags`, `video_status`, `vodtaskid`, `file_id`, `source_url`) VALUES (1, '新人直播', 167222, 0, 0, 0, 0, 740, '其他', '福州', 1472663047, 1472663047, 1472663149, '2016-9-1', '@TGS#aG6EBTBEO', 0, '', 23, 3, 0, '', '0000-0-0 00:00:00', 0, 23, 0, '9896587163584396075', 0, 0, 1, 0, '', '', 0, 0, 1, 0, 0, 0, 0, 0, 0, 0, 0, '', '', '', '', '', 0.000000, 0.000000, './public/attachment/test/noavatar_2.JPG', '', './public/flash/2.mp4', '', 0, 0, '', 0, 0, 0, 0, 0, 0, 'fanwe_video_prop', 2, 0, 0, 0, 0, '', 0, '', '', '');
INSERT INTO `%DB_PREFIX%video_check` (`id`, `title`, `user_id`, `live_in`, `watch_number`, `virtual_watch_number`, `vote_number`, `cate_id`, `province`, `city`, `create_time`, `begin_time`, `end_time`, `end_date`, `group_id`, `destroy_group_status`, `long_polling_key`, `max_watch_number`, `room_type`, `is_playback`, `video_vid`, `monitor_time`, `is_delete`, `robot_num`, `robot_time`, `channelid`, `is_aborted`, `is_del_vod`, `online_status`, `tipoff_count`, `private_key`, `share_type`, `sort`, `pai_id`, `sex`, `video_type`, `sort_num`, `create_type`, `max_robot_num`, `share_count`, `like_count`, `fans_count`, `sort_init`, `push_rtmp`, `play_flv`, `play_rtmp`, `play_mp4`, `play_hls`, `xpoint`, `ypoint`, `head_image`, `thumb_head_image`, `play_url`, `live_image`, `is_recommend`, `virtual_number`, `room_title`, `live_pay_time`, `is_live_pay`, `live_fee`, `live_is_mention`, `live_pay_count`, `pay_room_id`, `prop_table`, `live_pay_type`, `len_time`, `is_concatvideo`, `stick`, `classified_id`, `tags`, `video_status`, `vodtaskid`, `file_id`, `source_url`) VALUES (2, '新人直播', 167222, 0, 0, 0, 0, 740, '其他', '福州市', 1472663136, 1472663136, 1472663151, '2016-9-1', '@TGS#aALFBTBEA', 0, '', 24, 3, 0, '', '0000-0-0 00:00:00', 0, 24, 0, '9896587163584396381', 0, 0, 1, 0, '', '', 0, 0, 1, 0, 0, 0, 0, 0, 0, 0, 0, '', '', '', '', '', 0.000000, 0.000000, './public/attachment/test/noavatar_2.JPG', '', './public/flash/3.mp4', '', 0, 0, '', 0, 0, 0, 0, 0, 0, 'fanwe_video_prop', 2, 0, 0, 0, 0, '', 0, '', '', '');
INSERT INTO `%DB_PREFIX%video_check` (`id`, `title`, `user_id`, `live_in`, `watch_number`, `virtual_watch_number`, `vote_number`, `cate_id`, `province`, `city`, `create_time`, `begin_time`, `end_time`, `end_date`, `group_id`, `destroy_group_status`, `long_polling_key`, `max_watch_number`, `room_type`, `is_playback`, `video_vid`, `monitor_time`, `is_delete`, `robot_num`, `robot_time`, `channelid`, `is_aborted`, `is_del_vod`, `online_status`, `tipoff_count`, `private_key`, `share_type`, `sort`, `pai_id`, `sex`, `video_type`, `sort_num`, `create_type`, `max_robot_num`, `share_count`, `like_count`, `fans_count`, `sort_init`, `push_rtmp`, `play_flv`, `play_rtmp`, `play_mp4`, `play_hls`, `xpoint`, `ypoint`, `head_image`, `thumb_head_image`, `play_url`, `live_image`, `is_recommend`, `virtual_number`, `room_title`, `live_pay_time`, `is_live_pay`, `live_fee`, `live_is_mention`, `live_pay_count`, `pay_room_id`, `prop_table`, `live_pay_type`, `len_time`, `is_concatvideo`, `stick`, `classified_id`, `tags`, `video_status`, `vodtaskid`, `file_id`, `source_url`) VALUES (3, '新人直播', 167222, 0, 0, 0, 0, 740, '其他', '福州', 1472663779, 1472663779, 1472663848, '2016-9-1', '639857', 1, '', 22, 3, 0, '', '0000-0-0 00:00:00', 0, 22, 0, '9896587163584398658', 0, 0, 1, 0, '', '', 0, 0, 1, 0, 0, 0, 0, 0, 0, 0, 0, '', '', '', '', '', 0.000000, 0.000000, './public/attachment/test/noavatar_2.JPG', '', './public/flash/5.mp4', '', 0, 0, '', 0, 0, 0, 0, 0, 0, 'fanwe_video_prop', 2, 0, 0, 0, 0, '', 0, '', '', '');
INSERT INTO `%DB_PREFIX%video_check` (`id`, `title`, `user_id`, `live_in`, `watch_number`, `virtual_watch_number`, `vote_number`, `cate_id`, `province`, `city`, `create_time`, `begin_time`, `end_time`, `end_date`, `group_id`, `destroy_group_status`, `long_polling_key`, `max_watch_number`, `room_type`, `is_playback`, `video_vid`, `monitor_time`, `is_delete`, `robot_num`, `robot_time`, `channelid`, `is_aborted`, `is_del_vod`, `online_status`, `tipoff_count`, `private_key`, `share_type`, `sort`, `pai_id`, `sex`, `video_type`, `sort_num`, `create_type`, `max_robot_num`, `share_count`, `like_count`, `fans_count`, `sort_init`, `push_rtmp`, `play_flv`, `play_rtmp`, `play_mp4`, `play_hls`, `xpoint`, `ypoint`, `head_image`, `thumb_head_image`, `play_url`, `live_image`, `is_recommend`, `virtual_number`, `room_title`, `live_pay_time`, `is_live_pay`, `live_fee`, `live_is_mention`, `live_pay_count`, `pay_room_id`, `prop_table`, `live_pay_type`, `len_time`, `is_concatvideo`, `stick`, `classified_id`, `tags`, `video_status`, `vodtaskid`, `file_id`, `source_url`) VALUES (4, '新人直播', 167222, 0, 0, 0, 0, 740, '其他', '福州市', 1472629428, 1472629428, 1472629706, '2016-8-31', '639863', 1, '', 22, 3, 0, '', '0000-0-0 00:00:00', 0, 22, 0, '9896587163584309061', 0, 0, 1, 0, '', '', 0, 0, 1, 0, 0, 0, 0, 0, 0, 0, 0, '', '', '', '', '', 0.000000, 0.000000, './public/attachment/test/noavatar_2.JPG', '', './public/flash/1.mp4', '', 0, 0, '', 0, 0, 0, 0, 0, 0, 'fanwe_video_prop', 2, 0, 0, 0, 0, '', 0, '', '', '');
INSERT INTO `%DB_PREFIX%video_check` (`id`, `title`, `user_id`, `live_in`, `watch_number`, `virtual_watch_number`, `vote_number`, `cate_id`, `province`, `city`, `create_time`, `begin_time`, `end_time`, `end_date`, `group_id`, `destroy_group_status`, `long_polling_key`, `max_watch_number`, `room_type`, `is_playback`, `video_vid`, `monitor_time`, `is_delete`, `robot_num`, `robot_time`, `channelid`, `is_aborted`, `is_del_vod`, `online_status`, `tipoff_count`, `private_key`, `share_type`, `sort`, `pai_id`, `sex`, `video_type`, `sort_num`, `create_type`, `max_robot_num`, `share_count`, `like_count`, `fans_count`, `sort_init`, `push_rtmp`, `play_flv`, `play_rtmp`, `play_mp4`, `play_hls`, `xpoint`, `ypoint`, `head_image`, `thumb_head_image`, `play_url`, `live_image`, `is_recommend`, `virtual_number`, `room_title`, `live_pay_time`, `is_live_pay`, `live_fee`, `live_is_mention`, `live_pay_count`, `pay_room_id`, `prop_table`, `live_pay_type`, `len_time`, `is_concatvideo`, `stick`, `classified_id`, `tags`, `video_status`, `vodtaskid`, `file_id`, `source_url`) VALUES (5, '新人直播', 167222, 0, 0, 40, 0, 740, '其他', '福州', 1472663301, 1472663301, 1472663409, '2016-9-1', '639858', 1, '', 67, 3, 0, '', '0000-0-0 00:00:00', 0, 26, 0, '9896587163584396075', 0, 0, 1, 0, '', '', 0, 0, 1, 0, 0, 0, 0, 0, 0, 0, 0, '', '', '', '', '', 0.000000, 0.000000, './public/attachment/test/noavatar_2.JPG', '', './public/flash/4.mp4', '', 0, 0, '', 0, 0, 0, 0, 0, 0, 'fanwe_video_prop', 2, 0, 0, 0, 0, '', 0, '', '', '');

UPDATE `%DB_PREFIX%conf` SET `value`='2.44' WHERE (`name`='DB_VERSION');
