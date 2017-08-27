<?php
$distribution_cfg = array(
	"IS_REDIS_JQ"  => false,//redis是否是集群状态 

	"RDB_CLIENT"	=>	"47.93.248.81", //必填,redis链接
	"RDB_PORT"	=>	"6379", //必填（redis使用的端口，默认为6379）
	"RDB_PASSWORD"	=>	"qingketv",  //必填 redis密码

	"SESSION_CLIENT"	=>	"47.93.248.81",  //必填,redis链接
	"SESSION_PORT"	=>	"6379", //必填（redis使用的端口，默认为6379）
	"SESSION_PASSWORD"	=>	"qingketv",   //必填 redis密码

    "CACHE_CLIENT"	=>	"47.93.248.81", //必填,redis链接
    "CACHE_PORT"	=>	"6379", //必填（redis使用的端口，默认为6379）
    "CACHE_PASSWORD"	=>	"qingketv",  //必填 redis密码




    "SESSION_FILE_PATH"	=>	"", //session保存路径(为空表示web环境默认路径)
    //"SESSION_FILE_PATH"	=>	"",
    "DB_CACHE_APP"	=>	array(
        "index"
    ),
    "DB_CACHE_TABLES"	=>	array(
        "admin",


    ),   //支持查询缓存的表

    "DB_DISTRIBUTION" => array(
      

    ),   //数据只读查询的分布

    "OSS_DOMAIN"	=>	"http://liveimage.fanwe.net",  //远程存储域名
	"OSS_DOMAIN_HTTPS"	=>	"",  //远程https存储域名 例如：https://osshttps.fanwe.net
    "OSS_FILE_DOMAIN"	=>	"http://ilvbfile.fanwe.net",	//远程存储文件域名(主要指脚本与样式)
    "OSS_BUCKET_NAME"	=>	"ilvbfanwe", //针对阿里oss的bucket_name
    "OSS_ACCESS_ID"	=>	"",
    "OSS_ACCESS_KEY"	=>	"",
	"OSS_ENDPOINT"  =>  "",  //OSS的endpoint
    "OSS_INTERNAL_ENDPOINT" => "", //OSS内网地址 oss-cn-shanghai.internal.aliyuncs.com
	"OSS_ENDPOINT_WITH_BUCKET_NAME"  =>  true, //域名中是否已带有bucket属性，一般为三级域名将有此特性
	"NEW_OSS"	=>	true,  //使用新版OSS功能，设置为 true
	"OSS_NO_SAVE_LOCALHOST"	=>	0,  //上传OSS是否保存图片在本地，不保存：0 ；保存：1
	
	"REDIS_DISTRIBUTION_FUN" => array(
		'index#index','index#new_video','index#search_area','video#viewer'
	),

	"AES_EXTRA_FUN" =>array(
		'app#init','login#do_login','user#usersig','login#wx_login','login#qq_login','login#sina_login','login#visitors_login','login#send_mobile_verify','login#do_update','app#aliyun_sts','login#is_user_verify'
	),
);
//关于分布式配置
$distribution_cfg["CACHE_TYPE"]	=	"Rediscache";	//File,Memcached,MemcacheSASL,Xcache,Db,Rediscache
$distribution_cfg["CACHE_LOG"]	=	false;  //是否需要在本地记录cache的key列表
$distribution_cfg["SESSION_TYPE"]	=	"Rediscache"; //"Db/MemcacheSASL/File,Rediscache"
$distribution_cfg['ALLOW_DB_DISTRIBUTE']	=	false;  //是否支持读写分离

$distribution_cfg['IS_JQ']	=	false;  //是否是集群状态
$distribution_cfg['JQ_URL']	=	'';  //集群的网址 http://开头，不带斜杠结尾

$distribution_cfg["REDIS_PREFIX"]	= "fanwe0000001:";
$distribution_cfg["REDIS_PREFIX_DB"]	= 0; //选择redis的db，默认是0

$distribution_cfg["CSS_JS_OSS"]	=	true; //脚本样式是否同步到oss
$distribution_cfg["OSS_TYPE"]	=	"ALI_OSS"; //同步文件存储的类型: ES_FILE,ALI_OSS,NONE 分别为原es_file.php同步,阿里云OSS,以及无OSS分布
$distribution_cfg["LOCAL_IMAGE_URL"]	=	"";//当同步文件存储的类型:NONE，开启第三方图片服务器时候 必须配置此参数，为图片服务器的域名 例如:http://ilvb.fanwe.net

$distribution_cfg["ORDER_DISTRIBUTE_COUNT"]	=	"5"; //订单表分片数量
$distribution_cfg['DOMAIN_ROOT']	=	'';  //域名根
$distribution_cfg['COOKIE_PATH']	=	'/';
return $distribution_cfg;