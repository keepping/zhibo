<?php 
define("IS_DEBUG",0);
define("DE_BUGE",0);//AES加密调试
define("DE_BUGE_AES",0);//AES加密调试
define("SHOW_DEBUG",0);
define("SHOW_LOG",0);
define("MAX_DYNAMIC_CACHE_SIZE",1000);  //动态缓存最数量
define("SMS_TIMESPAN",60);  //短信验证码发送的时间间隔
define("SMS_EXPIRESPAN",300);  //短信验证码失效时间

define("PAI_PAGE_SIZE",10);
define("PIN_PAGE_SIZE",80);
define("PIN_SECTOR",10);
define("MAX_SP_IMAGE",20); //商家的最大图片量
define("MAX_LOGIN_TIME",0);  //登录的过期时间,单位：秒
define("ORDER_DELIVERY_EXPIRE",7);  //延期收货天

define("AES_DECRYPT_KEY",'fanwe');
define("SESSION_TIME",3600*1); //session超时时间
define("SMS_MOBILE_SEND_COUNT",9); //每个手机号每天最多只能发XX条
define("SMS_IP_SEND_COUNT",9); //每个IP每小时最多只能发XX条
/*
 * 是否开启 MYSQL 和 REDIS的长链接
 * 在高并发的情况下开启，链接数 会和 php进程数一致，nginx要重启服务后生效
 * 默认是关闭的
 */
define('IS_LONG_LINK',false);

define('IS_LONG_LINK_MYSQL',false);
/*
 * 是否开启事务
 */
define('IS_REDIS_WORK',false);


//关于竞拍的一些配置
define('OPEN_PAI_MODULE',0);//是否开启竞拍
define('PAI_REAL_BTN',0);//是否开启实物竞拍
define('PAI_VIRTUAL_BTN',0);//是否开启虚拟竞拍
define('SHOPPING_GOODS',0);//是否开启购物
define('SHOP_SHOPPING_CART',0);//是否开启购物车

define('PAI_MAX_VIOLATIONS',2);//竞拍 一个月最大违规次数(手动退出竞拍，或者直播掉线)
define('PAI_CLOSE_VIOLATIONS',15);//多久之后解禁(天)
define('SHOW_USER_ORDER',0);//是否显示【我的订单】 0否 1是
define('SHOW_USER_PAI',0);//是否显示【我的竞拍】 0否 1是
define('SHOW_PODCAST_ORDER',0);//是否显示星店订单(主播) 0否 1是
define('SHOW_PODCAST_PAI',0);//是否显示竞拍管理(主播) 0否 1是
define('SHOW_PODCAST_GOODS',0);//是否显示 商品管理（主播） 0否 1是
define('MAX_PAI_PAY_TIME',15*60);//竞拍付款倒计时时长，单位秒
define('MAX_USER_CONFIRM_TIME',3*24*3600);//用户确认约会完成倒计时时长，单位秒
define('MAX_PODCAST_CONFIRM_TIME',1*24*3600);//主播确认约会完成倒计时时长，单位秒
define('MAX_USER_CONFIRM_VIRTUAL_TIME',10*24*3600);//用户确认收货倒计时时长，单位秒
define('MAX_PODCAST_CONFIRM_VIRTUAL_TIME',3*24*3600);//主播确认发货倒计时时长，单位秒
define("FANWE_APP_ID_YM",'');//源码用户的app_id；需手动填写
define("FANWE_AES_KEY_YM",'');//源码用户的aes_key；需手动填写

define('PAI_YANCHI_MODULE',0);//延迟模式：0：添加延迟时长，1：更新延迟时长

//关于家族的一些配置
define('OPEN_FAMILY_MODULE',1);//是否开启家族

//靓号开关配置
define('OPEN_LUCK_NUM',1);//是否开启靓号

//每日首次登录赠送积分配置
define('OPEN_LOGIN_SEND_SCORE',1);//是否开启每日首次登录赠送积分

//每次登录时升级提示
define('OPEN_UPGRADE_PROMPT',1);//是否开启每次登录时升级提示

//是否开启排行榜
define('OPEN_RANKING_LIST',1);//是否开启排行榜

// PC是否开启观看历史
define('OPEN_PC_HISTORY',1);//是否开启PC版本

//是否开启分享加印票或是钻石
define('OPEN_SHARE_EXPERIENCE',1);//是否开启分享加印票或是钻石

//支付宝一键认证
define('OPEN_AUTHENT_ALIPAY',1);//支付宝一键认证  0 关闭 1 开启

//房间隐藏
define('OPEN_ROOM_HIDE',1);//房间隐藏  0 关闭 1 开启

//监控页面发送警告
define('OPEN_WARNING',1);//监控页面发送警告  0 关闭 1 开启

//后台手机验证码
define("OPEN_CHECK_ACCOUNT",1); //后台手机验证码功能

//是否游客登录
define('VISITORS',1);// 0 关闭 1 开启

//主播单独设置提现比例
define('OPEN_SCALE',1);// 0 关闭 1 开启

//是否开启腾讯云视频
define('TECENT_VIDEO',1);//是否开启腾讯云视频

// 强制开启手机绑定
define('OPEN_FORCE_MOBILE', 1); // 0 关闭 1 开启

define('CHECK_VIDEO',0); ;// 0 关闭 1 开启; //审核视频
?>