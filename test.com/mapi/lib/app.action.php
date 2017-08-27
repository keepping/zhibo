<?php
// +----------------------------------------------------------------------
// | Fanwe 方维p2p借贷系统
// +----------------------------------------------------------------------
// | Copyright (c) 2011 http://www.fanwe.com All rights reserved.
// +----------------------------------------------------------------------
// | Author: 云淡风轻(88522820@qq.com)
// +----------------------------------------------------------------------

class appModule extends baseModule
{

	public function init()
	{

		//客服端手机类型dev_type=android;dev_type=ios
		$dev_type = strim($_REQUEST['sdk_type']);
		//开始定义IOS/android的客户端版本号
		//define("IOS_CLIENT_VERSION","3.03.01");
		//define("ANDROID_CLIENT_VERSION","4.5.2");
		$sdk_version_name = strim($_REQUEST['sdk_version_name']);
		$sdk_version = strim($_REQUEST['sdk_version']);//升级版本号yyyymmddnn： 2016021601
		//es_session::set("dev_type",$dev_type);
		//es_session::set("dev_version",$version);

		$root = array();

		$root['status'] = 1;

		$m_config =  load_auto_cache("m_config");//初始化手机端配置
		//直播公告消息
		$root['listmsg'] = load_auto_cache("article_notice");
		//API接口地址
		$root['api_link'] = load_auto_cache("api_list");


		$root['sina_app_api'] = intval($m_config['sina_app_api']);
		$root['wx_app_api'] = intval($m_config['wx_app_api']);
		$root['qq_app_api'] = intval($m_config['qq_app_api']);
		$root['selected_app_api'] =  0;//创建直播时 默认选中分享按钮，0:不选中;1:微信;2:微信朋友圈;3:QQ;4:微博;

		$root['has_sdk_login'] = 1;//使用sdk登陆,如果使用sdk登陆时,在访问webveiw时,需要传输session_id参数;

		$root['has_sina_login'] = intval($m_config['has_sina_login']);//支持新浪登陆
		$root['has_wx_login'] = intval($m_config['has_wx_login']);//支持微信登陆
		$root['has_qq_login'] = intval($m_config['has_qq_login']);//支持qq登陆
		$root['has_mobile_login'] = intval($m_config['has_mobile_login']);//支持手机登陆(注册)
		//兼容分销屏蔽登录功能
		if(intval('OPEN_DISTRIBUTION')==1&&intval($m_config['distribution'])==1){
			$root['has_wx_login'] = intval($m_config['distribution_wx']);//支持微信登陆
			$root['has_sina_login'] = intval($m_config['distribution_sina']);//支持新浪登陆
			$root['has_qq_login'] = intval($m_config['distribution_qq']);//支持qq登陆
		}

		$theme = intval($m_config['app_theme']);
        if($theme == 1){//1: green #1cd39b
            $color_code = '#1cd39b';
        }else {//0或其他: default #ff7551
            $color_code = '#ff7551';
        }
		$root['statusbar_color'] = $color_code;// '#55ACEF';//状态栏,颜色
		$root['topnav_color'] = $color_code;//'#55ACEF';//顶部导航栏,颜色
		
		$root['statusbar_hide'] = 0;//0:显示状态栏;1隐藏状态栏

		$root['ad_img'] = '';//启动时的广告图
		$root['ad_http'] = '';//启动时的广告连接内容
		$root['ad_open'] = 0;//点击广告内容，打开方式：0:在第一个webveiw中打开;1:新建一个webview打开连接

		$root['site_url'] = SITE_DOMAIN;//.'/theme/index.php';//h5首页地址
		//$root['api'] = SITE_DOMAIN.APP_ROOT.'/service/index.php';//api地址

		$root['reload_time'] = 60;//秒；程序暂停，超过 60 秒，再进去时，需要重新清空程序加载


		$root['monitor_second'] = 10;//主播心跳监听，每10秒监听一次;监听数据：时间点，印票数，房间人数
		$root['jr_user_level'] = intval($m_config['jr_user_level']);//加入房间时,如果用户等级超过或等于jr_user_level时，有用户进入房间提醒操作
		$root['bullet_screen_diamond'] = 1;//弹幕一次消费的金币

		//SPEAR引擎配置
		$root['spear_live'] = 'LiveHost';//主播
		$root['spear_normal'] = 'NormalGuest';//观众
		$root['spear_interact'] = 'InteractUser';//连麦

		$root['privacy_title'] = "《用户隐私政策》";
		$root['privacy_link'] = SITE_DOMAIN.APP_ROOT.'/mapi/index.php?ctl=app&act=privacy';

		//$root['agreement_title'] = "主播协议";
		$root['agreement_link'] = SITE_DOMAIN.APP_ROOT.'/mapi/index.php?ctl=app&act=agreement';

		$root['short_name'] =strim($m_config['short_name']);
		$root['ticket_name'] =strim($m_config['ticket_name']);

		$root['beauty_ios'] =intval($m_config['beauty_ios']);//IOS美颜度，默认值
		$root['beauty_android'] =intval($m_config['beauty_android']);//ANDROID美颜度，默认值

		$root['beauty_close'] =intval($m_config['beauty_close']);//4、客户端不许自义美颜度; 0:开;1:关; 当beauty_close=1时,美颜功能只有 开/关；美颜值直接取：服务端返回的值
        $root['beauty_close'] = 0;
        //$root['service_push'] =intval($m_config['service_push']);//是否开启全服推送 0： 不开启 推送给粉丝 1：开启
        $root['app_name'] =strim($m_config['app_name']);//app名称  方维直播
        $root['share_title'] =strim($m_config['share_title']);//分享标题  你丑你先睡，我美我直播
        $root['has_save_video'] = intval($m_config['has_save_video']);//保存视频（可用于回播）
        $root['account_name'] =strim($m_config['account_name']);//app账号名称  账号

        /*
        fanwe_require(APP_ROOT_PATH . "system/extend/ip.php");
        $ip = new iplocate ();
        $area = $ip->getaddress ( CLIENT_IP );
        $root['city'] = $area ['area1'];
        */

        $root['ip_info'] = get_ip_info();

        $root['city'] = $root['ip_info']['city'];
        /*
        $province = $ipinfo['province'];
        $city = $ipinfo['city'];
        */


        //地区列表版本号
        $root['region_versions'] =intval($m_config['region_versions']);
        //$root['request'] = print_r($_SERVER['HTTP_USER_AGENT'],1);
       	//$root['ios_check_version'] = $m_config['ios_check_version'];
        //正在审核的版本,只显示：苹果支付
        if ($dev_type == 'ios' && $m_config['ios_check_version'] != '' && $m_config['ios_check_version'] == $sdk_version_name){
        	$root['auto_login'] = 0;//自动创建帐户登陆;苹果审核跟云测试时使用

        	$root['sina_app_api'] = 0;
        	$root['wx_app_api'] = 0;
        	$root['qq_app_api'] = 0;
        	$root['selected_app_api'] =  0;//创建直播时 默认选中分享按钮，0:不选中;1:微信;2:微信朋友圈;3:QQ;4:微博;

        	$root['has_sdk_login'] = 1;//使用sdk登陆,如果使用sdk登陆时,在访问webveiw时,需要传输session_id参数;

        	$root['has_sina_login'] = 0;//支持新浪登陆
        	$root['has_wx_login'] = 0;//支持微信登陆
        	$root['has_qq_login'] = 0;//支持qq登陆
        	$root['has_mobile_login'] = 1;//intval($m_config['has_mobile_login']);//支持手机登陆(注册)

        	//1:可升级;0:不可升级
        	$root['version'] = $this->version2($dev_type,$sdk_version,$sdk_version_name);//版本升级检查
        	
        	//购物竞拍配置开关
        	$root['pai_real_btn']= 0;
        	$root['pai_virtual_btn']= 0;
        	$root['shopping_goods']= 0;
        	$root['open_pai_module']= 0;
			$root['shop_shopping_cart']= 0;
			$root['open_podcast_goods']= 0;

			//是否开启游戏字段
			$root['open_game_module']= 0;
			//是否开启上庄模块
			$root['open_banker_module']= 0;
			//是否开启赠送游戏币模块
			$root['open_send_coins_module']= 0;
			// 游戏分销
			$root['game_distribution']= 0;
			$root['open_send_diamonds_module']= 0;
			
			//是否开启钻石游戏
			/**
			 * 未开启游戏默认为1
			 */
			$root['open_diamond_game_module']= 1;
			/*
			 * 版本审核中
			 */
			$ios_check_version = 1;
        }else{
        	$root['auto_login'] = 0;

        	$root['version'] = $this->version2($dev_type,$sdk_version);//版本升级检查
        	
        	//购物竞拍配置开关
        	$root['pai_real_btn']= intval(PAI_REAL_BTN);
        	$root['pai_virtual_btn']= intval(PAI_VIRTUAL_BTN);
        	$root['shopping_goods']= intval(SHOPPING_GOODS);
        	$root['open_pai_module']= intval(OPEN_PAI_MODULE);
			$root['shop_shopping_cart']= intval(SHOP_SHOPPING_CART);
			$root['open_podcast_goods']= intval(OPEN_PODCAST_GOODS);
			
			//是否开启游戏字段
			$root['open_game_module']= intval(OPEN_GAME_MODULE);
			//是否开启上庄模块
			$root['open_banker_module']= intval(OPEN_BANKER_MODULE) ? 1 : 0;
			//是否开启赠送游戏币模块
			$root['open_send_coins_module']= intval(OPEN_SEND_COINS_MODULE);
			$root['open_send_diamonds_module']= intval(OPEN_SEND_DIAMONDS_MODULE);
			
			//是否开启钻石游戏
			/**
			 * 未开启游戏默认为1
			 */
			$root['open_diamond_game_module']= intval(intval(OPEN_DIAMOND_GAME_MODULE) || !intval(OPEN_GAME_MODULE));
			// 游戏分销
			$root['game_distribution']= intval(GAME_DISTRIBUTION);
        }
        //$root['auto_login'] = 1;

       	//苹果审核版本号
        $root['ios_check_version']=$m_config['ios_check_version'];

		

		//家族开关
		$root['open_family_module']= intval(OPEN_FAMILY_MODULE);

        //公会开关
        $root['open_society_module']= intval(OPEN_SOCIETY_MODULE);

        //每日首次登录赠送积分开关
        $root['open_login_send_score']= intval(OPEN_LOGIN_SEND_SCORE);

		//开启O2O开关
		$root['o2o_open_goods'] = intval(O2O_OPEN_GOODS);

       
        
       
        
        //登录赠送积分值
        $root['login_send_score']= intval($m_config['login_send_score']);

        //是否首次登录
        $root['first_login']= es_session::get("first_login")?es_session::get("first_login"):0;
        es_session::set("first_login",0);
        //每次登录时升级提示
        $root['open_upgrade_prompt']= intval(OPEN_UPGRADE_PROMPT);
        $root['new_level']= es_session::get("new_level")?es_session::get("new_level"):0;
        es_session::set("new_level",0);

        //排行榜开关,审核期间不开
        $root['open_ranking_list'] = 0;
        if(trim($m_config['ios_check_version'] == '')){
            $root['open_ranking_list']= intval(OPEN_RANKING_LIST);
        }
        //启动广告
        if(!$root['open_ranking_list']){
            //未开启排行榜，去除指向排行榜
            $root['start_diagram'] = $GLOBALS['db']->getAll("select * from ".DB_PREFIX."index_image where show_position = 4 and `type`<>2 ",true,true);
        }else{
            $root['start_diagram'] = $GLOBALS['db']->getAll("select * from ".DB_PREFIX."index_image where show_position = 4 ",true,true);
        }
		if(!$root['start_diagram']){
			$root['start_diagram'] = array();
		}
		foreach($root['start_diagram'] as $k=>$v){
			if($v['image'] != ''){
				$root['start_diagram'][$k]['image'] = get_spec_image($v['image']);
			}
		}

		 //H5链接
		$h5_url = array(
		  'url_my_grades' => SITE_DOMAIN.APP_ROOT.'/wap/index.php?ctl=user_center&act=grade',
		  'url_about_we' => SITE_DOMAIN.APP_ROOT.'/wap/index.php?ctl=settings&act=article_cate',
		  'url_help_feedback' => SITE_DOMAIN.APP_ROOT.'/wap/index.php?ctl=settings&act=help',
		  'url_auction_record' => SITE_DOMAIN.APP_ROOT.'/wap/index.php?ctl=pai_user&act=pailogs',
		  'url_user_order' => '',   // 我的订单（暂无）
		  'url_user_pai' => SITE_DOMAIN.APP_ROOT.'/wap/index.php?ctl=pai_user&act=goods',
		  'url_podcast_order' => '',  // 星级订单（暂无）
		  'url_podcast_pai' => SITE_DOMAIN.APP_ROOT.'/wap/index.php?ctl=pai_podcast&act=goods',
		  'url_podcast_goods' => '',  //暂无（原生）
		  'url_auction_agreement' => SITE_DOMAIN.APP_ROOT.'/wap/index.php?ctl=settings&act=article_show&cate_id=18', //竞拍协议
		  'url_pai_income' => SITE_DOMAIN.APP_ROOT.'/wap/index.php?ctl=user_center&act=income', //竞拍收益h5
		  'url_goods_income' => SITE_DOMAIN.APP_ROOT.'/wap/index.php?ctl=user_center&act=income&details=1', //商品收益h5

		);
		if(SHOPPING_GOODS == 1){
			$h5_url['url_podcast_goods'] = SITE_DOMAIN.APP_ROOT.'/wap/index.php?ctl=shop&act=podcasr_goods_management&state=1&page=1'; //商城h5链接（主播）
			$h5_url['url_user_goods'] =''; //进入第三方商城h5链接(观众)
			$h5_url['url_user_order'] = SITE_DOMAIN.APP_ROOT.'/wap/index.php?ctl=shop&act=shop_order&page=1'; //商城h5订单链接（观众）
			$h5_url['url_goods_income'] = SITE_DOMAIN.APP_ROOT.'/wap/index.php?ctl=user_center&act=goods_income_details'; //商品收益h5
			$h5_url['url_shopping_cart'] = SITE_DOMAIN.APP_ROOT.'/wap/index.php?ctl=shop&act=shop_shopping_cart&page=1'; //购物车
		}

		if((defined('PAI_REAL_BTN') && PAI_REAL_BTN == 1) && (defined('PAI_VIRTUAL_BTN') && PAI_VIRTUAL_BTN == 0)){
			$h5_url['url_user_pai'] = SITE_DOMAIN.APP_ROOT.'/wap/index.php?ctl=pai_user&act=goods&is_true=1';
			$h5_url['url_podcast_pai'] = SITE_DOMAIN.APP_ROOT.'/wap/index.php?ctl=pai_podcast&act=goods&is_true=1';
		}

		$root['h5_url']= $h5_url;
				
		
		$root['has_dirty_words'] = intval($m_config['has_dirty_words']);//当为1时,启用脏子过滤;默认0时不过滤, 即：0时不发送组合消息即可; 接收还是要处理组合消息; 主要是为了跟旧版本兼容过度一下,过度完后,这个参数将去掉;


		//将图片直接上传到OSS上,不中转;https://help.aliyun.com/document_detail/31920.html?spm=5176.doc31931.6.206.OEtePt
		$root['open_sts'] = intval($m_config['open_sts']);
		//是否开启 强制输入话题 功能 1是 0否
		$root['must_cate'] = intval($m_config['must_cate']);


        //付费总开关
        $root['live_pay']=0;
        //按时付费
        $root['live_pay_time']=0;
        //按场付费
        $root['live_pay_scene']=0;
        if((defined('OPEN_LIVE_PAY')&&OPEN_LIVE_PAY==1)&&intval($ios_check_version)==0){
			$live_pay_info= $GLOBALS['db']->getAll("SELECT id,class FROM ".DB_PREFIX."plugin WHERE is_effect=1 and type =1");

            if($live_pay_info){
				foreach($live_pay_info as $item){
					$live_pay[$item['id']] = $item['class'];
				}
               $root['live_pay'] =intval(OPEN_LIVE_PAY);
            }
			//按时付费
            if(intval(LIVE_PAY_TIME)==1) {
                if (in_array('live_pay',$live_pay)) {
                    $root['live_pay_time'] = 1;
                    $root['live_pay_count_down'] = intval($m_config['live_count_down']);//进入付费模式倒计时时间
                }   
            }
			//按场付费
            if(intval(LIVE_PAY_SCENE)==1){
				if (in_array('live_pay_scene',$live_pay)) {
                    $root['live_pay_scene'] = 1;
                }
            }
        }


		$pay_interval = intval($m_config['pay_interval'])*60;//（分钟）扣费间隔
		$root['pay_interval'] = $pay_interval>0?$pay_interval:60;
		//最大连麦数量
		$root['mic_max_num'] = intval($m_config['mic_max_num'])>3?3:intval($m_config['mic_max_num']);
		//分销功能
		if((defined('OPEN_DISTRIBUTION')&&OPEN_DISTRIBUTION==1)){
			$root['distribution'] =intval($m_config['distribution'])?1:0;
		}

		//分销类型
		if(defined('distribution_module')){
			$root['distribution_module'] =intval(DISTRIBUTION_MODULE);
		}else{
			$root['distribution_module'] =0;
		}




        $root['full_group_id'] = '';//全员广播大群群组id
        $root['on_line_group_id'] = '';//在线用户大群id
        $group_id = strim($m_config['full_group_id']);
        $online_group_id = strim($m_config['on_line_group_id']);
        if($group_id || $online_group_id){
            require_once(APP_ROOT_PATH . 'system/tim/TimApi.php');
            $api = createTimAPI();
			$api_arr = (array)$api;
			if(intval($api_arr['status'])==0&&$api_arr['error']!=''){
				log_err_file(array(__FILE__,__LINE__,__METHOD__,$api_arr['error']));
			}
            if($group_id){
				$aes_key_info = get_privatekey();
				if(is_array($aes_key_info[0]['aes_key'])){
					$aes_key = $aes_key_info[0]['aes_key'][0];
				}else{
					$aes_key = $aes_key_info[0]['aes_key'];
				}
				$ret = $api->group_get_group_info2(array('0'=>$group_id));
				$root['full_group_id'] = $ret['GroupInfo'][0]['GroupId'];
                if($ret['GroupInfo'][0]['ErrorCode']){
                    $ret = $api->full_group_create($group_id,$aes_key);
                    if($ret['ActionStatus']!='OK'){
						log_err_file(array(__FILE__,__LINE__,__METHOD__,$ret));
                        $ret = $api->full_group_create($group_id,$aes_key);
                    }
                    $root['full_group_id'] = $ret['GroupId'];
                }
            }
            if($online_group_id){
                $ret = $api->group_get_group_info2(array('0'=>$online_group_id));
                $root['on_line_group_id'] = $ret['GroupInfo'][0]['GroupId'];
                if($ret['GroupInfo'][0]['ErrorCode']){
                    $ret = $api->full_group_create($online_group_id);
                    if($ret['ActionStatus']!='OK'){
						log_err_file(array(__FILE__,__LINE__,__METHOD__,$ret));
                        $ret = $api->full_group_create($online_group_id);
                    }
                    $root['on_line_group_id'] = $ret['GroupId'];
                }
            }
        }
		if(OPEN_X){
			$root['weibo_red_price'] =floatval($m_config['weibo_red_price']);//红包单张价格
			$root['weibo_photo_price'] =$m_config['weibo_photo_price'];//写真单张价格限制
			if($m_config['weibo_photo_price']){
				$weibo_photo_price = explode('-',$m_config['weibo_photo_price']);
				$root['weibo_photo_price_1'] =$weibo_photo_price[0];
				$root['weibo_photo_price_2'] =$weibo_photo_price[1];
			}
			$root['weibo_weixin_price'] = $m_config['weibo_weixin_price'];//微信价格限制
			if($m_config['weibo_weixin_price']){
				$weibo_weixin_price = explode('-',$m_config['weibo_weixin_price']);
				$root['weibo_weixin_price_1'] =$weibo_weixin_price[0];
				$root['weibo_weixin_price_2'] =$weibo_weixin_price[1];
			}

			$root['weibo_goods_price'] = $m_config['weibo_goods_price'];//微信价格限制
			if($m_config['weibo_goods_price']){
				$weibo_goods_price = explode('-',$m_config['weibo_goods_price']);
				$root['weibo_goods_price_1'] =$weibo_goods_price[0];
				$root['weibo_goods_price_2'] =$weibo_goods_price[1];
			}


		}
		$root['open_xr'] =  intval(OPEN_XR);
		//按场付费直播最高最低限制
		$root['live_pay_max'] = intval($m_config['live_pay_max']);//付费直播收费最高
		$root['live_pay_min'] = intval($m_config['live_pay_min']);//付费直播收费最低
		//按场付费直播最高最低限制
		$root['live_pay_scene_max'] = intval($m_config['live_pay_scene_max']);//付费直播收费最高
		$root['live_pay_scene_min'] = intval($m_config['live_pay_scene_min']);//付费直播收费最低

        //开启vip会员
        $root['open_vip'] = 0;
        if(defined('OPEN_VIP')){
            $root['open_vip'] = intval(OPEN_VIP);
        }

        $root['init_version'] = intval($m_config['init_version']);//手机端配置版本号
        //是否开启私信
        $root['has_private_chat'] = intval($m_config['has_private_chat']);//开启私信 1开启 0关闭
        
        //视频清晰度:0标清(360*640),1高清(540*960),2超清(720*1280)
        $root['video_resolution_type'] = intval($m_config['video_resolution_type']);
        //备用域名 列表
		$domain_list = array();
		$domain_arr = explode("<br />",nl2br($m_config['domain_list']));
		foreach($domain_arr as $k=>$v){
			$v = ltrim(rtrim(trim($v)));
			if($v!=''){
				$domain_list[]=$v;
			}
		}
		$root['domain_list']=$domain_list;

        //发言等级
        $root['speak_level']=intval($m_config['speak_level']);
        //用户开启发言功能的最低等级
        $root['send_msg_lv'] = intval($m_config['send_msg_lv']);
        //声网AppID
		$root['agora_app_id'] = trim($m_config['agora_app_id']);

		//手机端配置,腾讯云 账号信息
		$root['sdkappid'] = trim($m_config['tim_sdkappid']);
		$root['accountType'] = trim($m_config['tim_account_type']);
		//游客登录
		$root['has_visitors_login'] = intval($m_config['open_visitors_login']);
		ajax_return($root);

	}

	/**
	 * 用户隐私政策
	 */
	function privacy(){
		header("Content-Type:text/html; charset=utf-8");
		$content = load_auto_cache("article_privacy");
		echo $content;
		exit;
	}

	/**
	 * 主播协议
	 */
	function agreement(){
		header("Content-Type:text/html; charset=utf-8");
		$content = load_auto_cache("article_agreement");
		echo $content;
		exit;
	}


	function version2($dev_type,$version,$sdk_version_name)
	{

		$m_config =  load_auto_cache("m_config");//初始化手机端配置

		$site_url = SITE_DOMAIN.APP_ROOT."/";//站点域名;


		$root = array();
		if ($dev_type == 'android'){
			$root['serverVersion'] = $m_config['android_version'];//android版本号
			//print_r($m_config);
			if ($version < $root['serverVersion'] && $m_config['android_filename'] != ''){
				/*为了，计算文件大小，只能使用本地的
				 if (stripos($m_config['android_filename'],'http://')){
				$root['filename'] = $m_config['android_filename'];
				}else{
				$root['filename'] = $site_url.$m_config['android_filename'];//android下载包名
				}
				*/
				//$root['filesize2'] = filesize($m_config['android_filename']);
				$root['filename'] = $m_config['android_filename'];//android下载包名
				$root['android_upgrade'] = $m_config['android_upgrade'];//android版本升级内容
				$root['forced_upgrade'] = intval($m_config['android_forced_upgrade']);//强制升级
				$root['hasfile'] = 1;
				$root['has_upgrade'] = 1;//1:可升级;0:不可升级
			}else{
				$root['hasfile'] = 0;
				$root['has_upgrade'] = 0;//1:可升级;0:不可升级
			}
		}else if ($dev_type == 'ios'){
			$root['serverVersion'] = $m_config['ios_version'];//IOS版本号
			if ($version < $root['serverVersion']&&$m_config['ios_down_url']!=''){
				$root['ios_down_url'] = $m_config['ios_down_url'];//ios下载地址
				$root['ios_upgrade'] = $m_config['ios_upgrade'];//ios版本升级内容
				$root['has_upgrade'] = 1;//1:可升级;0:不可升级
				$root['forced_upgrade'] = intval($m_config['ios_forced_upgrade']);//0:非强制升级;1:强制升级
			}else{
				$root['has_upgrade'] = 0;//1:可升级;0:不可升级
			}

		}else{
			$root['hasfile'] = 0;
			$root['has_upgrade'] = 0;//1:可升级;0:不可升级
		}
		return $root;
		//ajax_return($root);
	}

	public function version($dev_type,$version)
	{

		//客服端手机类型dev_type=android;dev_type=ios
		$dev_type = strim($_REQUEST['sdk_type']);
		$sdk_version = strim($_REQUEST['sdk_version']);//升级版本号yyyymmddnn： 2016021601
		$sdk_version_name = strim($_REQUEST['sdk_version_name']);

		$root = $this->version2($dev_type,$sdk_version,$sdk_version_name);//版本升级检查
		ajax_return($root);
	}


	/**
	 * 将图片直接上传到OSS上,不中转;
	 * 参考网址：https://help.aliyun.com/document_detail/31920.html?spm=5176.doc31931.6.206.OEtePt
	 * status: 1,
		//上传文件时,必要的3个参数
		AccessKeyId: "",
		AccessKeySecret: "",
		SecurityToken: "",

		//过期时间,客户端不关心
		Expiration: "2016-09-28T10:30:02Z",

		//出错时,返回下面3个参数
		RequestId: "",
		Code: "",
		Message: "",

		//回调地址
		callbackUrl: "",
		callbackBody: "",

		//文件存放目录
		dir: ""
	 */
	function aliyun_sts(){
		$root = array();
		$root['status'] = 1;
		//$GLOBALS['user_info']['id'] = 1;
		if(!$GLOBALS['user_info']){
			$root['error'] = "用户未登陆,请先登陆.";
			$root['status'] = 0;
			$root['user_login_status'] = 0;//有这个参数： user_login_status = 0 时，表示服务端未登陆、要求登陆，操作
		}else{
			$root = load_auto_cache("aliyun_sts");

			if ($root['status'] == 200){
				$root['status'] = 1;

				//回调说明https://help.aliyun.com/document_detail/31922.html?spm=5176.doc31921.6.208.xZgpik
				$root['callbackUrl'] = '';
				$root['callbackBody'] = '';
				//上传的目录是由服务端（即PHP）指定的，这样的好处就是安全。 这样就能控制每个客户端只能上传指定到指定的目录，做到安全隔离, 想要修改上传目录地址成abc/(必须以'/'结尾)
				$dir_name = to_date(get_gmtime(),"Ym");
				$root['dir'] = 'public/attachment/'.$dir_name.'/'.intval($GLOBALS['user_info']['id'])."/";
			}else{
				//$sts['status'] = 0;
			}
		}
		ajax_return($root);
	}

	public function test()
	{

		//http://www.tuicool.com/articles/nMRRBf7
		header("Content-Type:text/html; charset=utf-8");
		/*
		$ret = video_status(18545,0);
		var_dump($ret);

		fanwe_require(APP_ROOT_PATH.'mapi/lib/redis/VideoViewerRedisService.php');
		$video_viewer = new VideoViewerRedisService();

		//$ret = $video_viewer->get_viewer_list('@TGS#aPFWQOCEG', 1,200);

		$root = load_auto_cache("video_viewer",array('group_id'=>'@TGS#aPFWQOCEG','page'=>0));

		var_dump($root);
		*/
		/*
		fanwe_require(APP_ROOT_PATH.'mapi/lib/redis/VideoRedisService.php');
		$video_redis = new VideoRedisService();


		$sql = "select id,is_del_vod from ".DB_PREFIX."video_history where online_status = 0 limit 100";
		$list = $GLOBALS['db']->getAll($sql);
		foreach ( $list as $k => $v )
		{
			$ret = get_vodset_by_video_id($v['id']);
			$data = array();

			if ($ret['total_count'] == 0){
				$sql = "update ".DB_PREFIX."video_history set is_del_vod = 1, is_delete = 1, online_status=1 where id = ".$v['id'];
				$data['is_del_vod'] = 1;
			}else{
				$sql = "update ".DB_PREFIX."video_history set is_del_vod = 0,online_status=1 where id = ".$v['id'];
				$data['is_del_vod'] = 0;
			}
			$GLOBALS['db']->query($sql);

			$video_redis->update_db($v['id'], $data);

			//print_r($ret);

			echo $sql."<br>";
		}

		*/
		/*
		$id = intval($_REQUEST['id']);
		$sql = "select id,is_del_vod from ".DB_PREFIX."video_history where is_del_vod = 0 and live_in = 0 limit 50";
		$list = $GLOBALS['db']->getAll($sql);
		foreach ( $list as $k => $v )
		{
			$ret = del_vodset($v['id']);

			print_r($ret);

		}
		*/



		/*exit;


		fanwe_require(APP_ROOT_PATH.'mapi/lib/redis/VideoRedisService.php');
		$video_redis = new VideoRedisService();

		$sql = "select id,is_del_vod from ".DB_PREFIX."video where id = ".intval($_REQUEST['id']);

		echo $sql;
		$list = $GLOBALS['db']->getAll($sql);
		foreach ( $list as $k => $v )
		{
			//1:表示已经清空了,录制视频;0:未做清空操作
			if ($v['is_del_vod'] == 1){
				$ret = get_vodset_by_video_id($v['id']);

				print_r($ret);

				if ($ret['total_count'] > 0){
					//视频存在
					$sql = "update ".DB_PREFIX."video set is_del_vod = 0 where id = ".$v['id'];

					echo $sql;

					$GLOBALS['db']->query($sql);

					$v['is_del_vod'] = 0;
					$data = array();
					$data['is_del_vod'] = 0;
					$video_redis->update_db($v['id'], $data);
				}
			}
		}*/


		/*
		fanwe_require(APP_ROOT_PATH.'mapi/lib/redis/VideoRedisService.php');
		$video_redis = new VideoRedisService();
		$video_id = 14868;

		$video = $video_redis->redis->hMGet($video_redis->video_db.$video_id,array('live_in','province','user_id','title','room_type','begin_time','demand_video_status','virtual_number'));

		print_r($video);

		$live_in = $video['live_in'];
		if(!$video_id){
			return false;
		}

		if ($video['room_type'] == 1) {
			$is_private = true;
		}else{
			$is_private = false;
		}


		$area = $video['province'];
		$data['area'] = $area;
		$data['sex'] = $video_redis->redis->hGet($this->user_db.$video['user_id'],'sex');

		$data['cate_name'] = $video['title'];
		$data['room_type'] =  $video['room_type'];
		$data['demand_video_status'] =  $video['demand_video_status'];
		$data['live_in'] =  $video['live_in'];
		$start_num = intval($video['virtual_number']/2)?intval($video['virtual_number']/2):2;
		$data['virtual_number'] =  rand($start_num,$video['virtual_number']);

		print_r($data);


		$video_redis->deal_watch_num($video_id,$data,'add',$video_redis);

		exit;
		*/
		/*
		$video_redis = new VideoRedisService();
		$video = $video_redis->getRow_db(15542);
		print_r($video);
		//录制地址不能为空,且录制文件没有被删除
		if ($video && $video['video_vid'] != ''){


			$ret = get_vodset($video['video_vid']);

			print_r($ret);

			echo 'play_url:'.$ret['vodset'][0]['fileSet'][0]['playSet'][0]['url'];


		}
		*/

		/*
		fanwe_require(APP_ROOT_PATH .'mapi/lib/core/transport.php');

		$trans = new transport();

		//$url = 'http://ip.taobao.com/service/getIpInfo.php';//?ip=
		$url = 'http://int.dpool.sina.com.cn/iplookup/iplookup.php?format=json&ip='.CLIENT_IP;
		$req = $trans->request($url,array(),'GET');

		//var_dump($req);

		$ip = json_decode($req['body'],true);

		var_dump($ip);


		exit;

		$ret = load_auto_cache("usersig", array("id"=>"aaaaa"));
		print_r($ret);
		exit;

		//echo $usersig;


		$m_config = load_auto_cache("m_config");
		print_r($m_config);

		exit;
		*/
		//测试过滤数组内false
		/*$date = array(
			false,123,'字符串',array(1,'2',false),array(array(11,'字符串','999',false,array(11,'字符串','999',false,array(11,'字符串','999',false,array(false,'false','false',false)))),'字符串',12,),
		);
		//var_dump($date);exit;
		//var_dump(filter_all_false($date));
		print_r(filter_all_false($date));
		exit;*/
		//数据传输加密测试//$r_type 0=>base64;1=>json_encode;2=>array; 4=>aec
		/*$data = array(
			false,123,'字符串',array(1,'2',false),array(array(11,'字符串','999',false,array(11,'字符串','999',false,array(11,'字符串','999',false,array(false,'false','false',false)))),'字符串',12,),
		);*/
		/*$data = 'abc';
		echo"原始数据";
		echo"<hr/>";
		print_r($data);
		echo"<hr/>";
		$data_json = ajax_return_aes($data,4,1);
		echo"服务器输出";
		echo"<hr/>";
		print_r($data_json);
		echo"<hr/>";
		$_REQUEST['requestData'] = $data_json['json_encode_data_2'];
		$_REQUEST['i_type'] =1;
		echo"服务器接收";
		echo"<hr/>";
		print_r($_REQUEST);
		echo "<hr/>";
		aes_request_decode();
		echo"数据解析";
		echo"<hr/>";
		print_r($_REQUEST);
		echo "<hr/>";*/

		/*if($_REQUEST['test']){
			$m_config = load_auto_cache("m_config");
			$group_id = strim($m_config['full_group_id']);
			require_once(APP_ROOT_PATH . 'system/tim/TimApi.php');
			$api = createTimAPI();
			$base_info_filter = array("Introduction");
			$ret = $api->group_get_group_info2(array('0'=>$group_id),$base_info_filter);
			print_r($ret);echo "<hr/>";
		}*/
	}


	/**
	 * 获得举报类型列表
	 */
	public function tipoff_type(){

		$root = array();
		$root['status'] = 1;

		$list = load_auto_cache("tipoff_type_list");

		$root['list'] = $list;
		ajax_return($root);
	}

	/**
	 * 获得礼物列表
	 */
	public function prop(){

		$root = array();
		$root['status'] = 1;
		//$list = load_auto_cache("prop_list");

		$root['list'] = load_auto_cache("prop_list");
        foreach($root['list'] as $k=>$v){
            $root['list'][$k]['ticket']=intval($v['ticket']);
        }

		//$root['expiry_after'] = NOW_TIME;//过期时间
		//$root['expiry_after_fromat'] = to_date($root['expiry_after'],'Y-m-d H:i:s');//过期时间
		ajax_return($root);
	}

	/*
 *插件列表接口
 * */
	public function plugin_init()
    {
        $user_id = intval($GLOBALS['user_info']['id']);
        if ($user_id == 0) {
            api_ajax_return(array(
                'status' => 10007,
                'error'  => "请先登录",
            ));
        }
        $m_config = load_auto_cache("m_config"); //初始化手机端配置

        //审核版本
        $ios_check        = 0;
        $dev_type         = strim($_REQUEST['sdk_type']);
        $sdk_version_name = strim($_REQUEST['sdk_version_name']);
        if ($dev_type == 'ios' && $m_config['ios_check_version'] != '' && $m_config['ios_check_version'] == $sdk_version_name&&$GLOBALS['user_info']['mobile']=='13888888888') {
            $ios_check = 1;
        }

        if ($ios_check) {
           ajax_return(array(
                'status'   => 1,
                'list'     => array(),
                'rs_count' => 0,
            ));
        }

        $plugin = $GLOBALS['db']->getALL("SELECT id,child_id,name,image,type,class as class_name FROM " . DB_PREFIX . "plugin WHERE is_effect=1", true, true);

        $table = DB_PREFIX . 'video';
        $video = $GLOBALS['db']->getRow("SELECT `id` FROM  $table WHERE user_id=$user_id and live_in=1");
        fanwe_require(APP_ROOT_PATH . 'mapi/lib/redis/VideoRedisService.php');
        $video_redis = new VideoRedisService();
        $fields      = array('live_pay_time', 'live_fee', 'live_pay_type', 'is_live_pay', 'game_log_id');
        $video_info  = $video_redis->getRow_db($video['id'], $fields);
        foreach ($plugin as $key => $value) {
            $plugin[$key]['is_active'] = 0;
            switch ($value['class_name']) {
                case 'game':
                    if (defined('OPEN_GAME_MODULE') && OPEN_GAME_MODULE == 1) {
                        if ($value['type'] == 2) {
                            $game_id   = 0;
                            $is_enable = 1;
                            if ($video['id']) {
                                fanwe_require(APP_ROOT_PATH . 'mapi/lib/redis/GamesRedisService.php');
                                $redis     = new GamesRedisService();
                                $last_game = $video_info['game_log_id'];
                                if ($last_game) {
                                    $last_game = $redis->get($last_game, 'game_id,create_time,long_time');
                                    if (NOW_TIME < $last_game['create_time'] + $last_game['long_time']) {
                                        $game_id   = $last_game['game_id'];
                                        $is_enable = 0;
                                    }
                                }
                            }
                            $plugin[$key]['is_active'] = intval($value['id'] == $game_id);
                            $plugin[$key]['is_enable'] = $is_enable;
                        }
                    } else {
                        unset($plugin[$key]);
                        continue 2;
                    }
                    break;

                case 'pai':
                    if (defined('OPEN_PAI_MODULE') && OPEN_PAI_MODULE == 1) {
                        if ($value['type'] == 1) {
                            if ($video['pai_id'] != 0) {
                                $pai_goods = $GLOBALS['db']->getRow("SELECT create_time,pai_time FROM " . DB_PREFIX . "pai_goods WHERE id=" . $video['pai_id'] . " ");
                                if ($pai_goods) {
                                    if (NOW_TIME < strtotime($pai_goods['create_time']) + $pai_goods['pai_time'] * 3600) {
                                        $is_enable = 0;
                                    }
                                }
                            }
                            $plugin[$key]['is_enable'] = $is_enable;
                        }
                    } else {
                        unset($plugin[$key]);
                        continue 2;
                    }
                    break;
                case 'shop':
                    if (!defined('SHOPPING_GOODS') || SHOPPING_GOODS == 0) {
                        unset($plugin[$key]);
                        continue 2;
                    } else {
                        $plugin[$key]['is_enable'] = 1;
                    }
                    break;
				case 'podcast_goods':
					if (!defined('OPEN_PODCAST_GOODS') || OPEN_PODCAST_GOODS == 0) {
						unset($plugin[$key]);
						continue 2;
					} else {
						$plugin[$key]['is_enable'] = 1;
					}
					break;
                case 'live_pay':
                case 'live_pay_scene':
                default:
                    if (defined('OPEN_LIVE_PAY') && OPEN_LIVE_PAY == 1) {
                        $is_nospeaking = $GLOBALS['db']->getOne("SELECT is_nospeaking FROM ".DB_PREFIX."user WHERE id=".$user_id,true,true);
                        if($is_nospeaking){
                            unset($plugin[$key]);
                            continue 2;
                        }
                        $live_pay_time = $video_info['live_pay_time']; //开始收费时间
                        $live_fee      = intval($video_info['live_fee']); //付费直播 收费多少
                        $is_live_pay   = intval($video_info['is_live_pay']);
                        $live_pay_type = intval($video_info['live_pay_type']);
                        $is_active     = 0;
                        if ($live_pay_time != '' && $live_fee > 0) {
                            if (($is_live_pay == 1 && $live_pay_type == 0 && $value['class_name'] == 'live_pay') || ($is_live_pay == 1 && $live_pay_type == 1 && $value['class_name'] == 'live_pay_scene')) {
                                $is_active = 1;
                                $is_enable = 0;
                            } else {
                                $is_enable = 0;
                            }
                        }
                        $plugin[$key]['is_active'] = $is_active;
                        $plugin[$key]['is_enable'] = $is_enable;
                    } else {
                        unset($plugin[$key]);
                        continue 2;
                    }
                    break;
            }
            $plugin[$key]['image']     = get_spec_image($value['image']);
        }

        api_ajax_return(array(
            'status'   => 1,
            'list'     => array_values($plugin),
            'rs_count' => sizeof($plugin),
            'test' => __FILE__.__LINE__,
        ));
    }
 //插件接口状态
 public function plugin_status(){
 		$root = array('status' =>1,'error'=>'');
 		$user_id = intval($GLOBALS['user_info']['id']);
		if($user_id == 0){
			$root['status']=10007;
			$root['error']="请先登录";
			ajax_return($root);
		}else{
			$plugin_id = intval($_REQUEST['plugin_id']);//插件id，fanwe_plugin.id
			$table   = DB_PREFIX . 'video';
			$video   = $GLOBALS['db']->getRow("SELECT `id`,pai_id FROM  $table WHERE user_id=" . $user_id . " and live_in=1");
			fanwe_require(APP_ROOT_PATH.'mapi/lib/redis/VideoRedisService.php');
			$video_redis = new VideoRedisService();
			$fields = array('live_pay_time','live_fee','live_pay_type','is_live_pay','game_log_id');
			$video_info = $video_redis->getRow_db($video['id'],$fields);
			$is_enable = 1;
			$is_active = 0;
			$plugin = $GLOBALS['db']->getRow("SELECT id,child_id,name,image,type,class as class_name FROM ".DB_PREFIX."plugin WHERE is_effect=1 and id=".$plugin_id,true,true);
			$plugin['is_active'] = $is_active;
			$plugin['is_enable'] = $is_enable;
            if(defined('OPEN_PLUGIN')&&OPEN_PLUGIN) {
                $open_plugin= $GLOBALS['db']->getRow("select open_game,open_pay,open_auction from ".DB_PREFIX."user where id = $user_id");
                if($plugin['class_name'] == 'game'){
                    if ($open_plugin['open_game']==1) {
                        $root['error'] = "您无法开启游戏，请联系客服";
                        $root['status'] = 0;
                        ajax_return($root);
                    }
                }
                if($plugin['class_name'] =='pai'){
                    if ($open_plugin['open_auction']==1) {
                        $root['error'] = "您无法开启竞拍，请联系客服";
                        $root['status'] = 0;
                        ajax_return($root);
                    }
                }
                if($plugin['class_name'] =='live_pay'||$plugin['class_name']=='live_pay_scene'){
                    if ($open_plugin['open_pay']==1) {
                        $root['error'] = "您无法开启付费，请联系客服";
                        $root['status'] = 0;
                        ajax_return($root);
                    }
                }

            }
			if(defined('OPEN_LIVE_PAY')&&OPEN_LIVE_PAY==1){
				$live_pay_time = $video_info['live_pay_time'];//开始收费时间
				$live_fee = intval($video_info['live_fee']);//付费直播 收费多少
				$is_live_pay = intval($video_info['is_live_pay']);
				$live_pay_type = intval($video_info['live_pay_type']);
				if($live_pay_time!=''&&$live_fee>0){
					//$is_enable = 0;
					if(($is_live_pay == 1&&$live_pay_type==1&&$plugin['class_name'] == 'live_pay_scene')||($is_live_pay == 1&&$live_pay_type==0&&$plugin['class_name'] == 'live_pay')){
						$is_active = 1;
					}

					if($plugin['class_name'] == 'live_pay'){
						$is_enable = 0;
						if($is_active){
							$error ='按时付费直播正在使用中...';
						}else{
							$error ='按场付费直播正在使用中...';
						}
					}else if($plugin['class_name'] == 'live_pay_scene'){
						$is_enable = 0;
						if($is_active){
							$error ='按场付费直播正在使用中...';
						}else{
							$error ='按时付费直播正在使用中...';
						}
					}
				}

				$plugin['is_active'] = $is_active;
				$plugin['is_enable'] = $is_enable;
			}

			if(defined('OPEN_PAI_MODULE') && OPEN_PAI_MODULE==1){
				if($plugin['class_name'] == 'pai'){
					if($video['pai_id'] != 0) {
						$pai_goods = $GLOBALS['db']->getRow("SELECT create_time,pai_time,status FROM ".DB_PREFIX."pai_goods WHERE id=".$video['pai_id']." ");
						if ($pai_goods) {
							if(NOW_TIME < $pai_goods['create_time'] + $pai_goods['pai_time']*3600 || $pai_goods['status'] < 2){
								$is_enable = 0;
								$error ='竞拍正在使用中...';
							}
						}

					}

					if(defined('OPEN_GAME_MODULE') && OPEN_GAME_MODULE==1){
						fanwe_require(APP_ROOT_PATH . 'mapi/lib/redis/GamesRedisService.php');
						$redis       = new GamesRedisService();
						$last_game   = $video_info['game_log_id'];
						if ($last_game) {
							$last_game = $redis->get($last_game, 'game_id,create_time,long_time');
							$game_id = $last_game['game_id'];
							$is_enable = $game_id ? intval($plugin_id == $game_id) : 1;
							if ($game_id) {
								$error = $plugin_id == $game_id ? '游戏正在使用中...' : '请先关闭当前游戏再切换...';
							}
						}

					}
					$plugin['is_enable'] = $is_enable;
				}
			}

			if(defined('OPEN_GAME_MODULE') && OPEN_GAME_MODULE==1){
				if($plugin['type'] == 2){
					$game_id = 0;
					if($video['id']) {
						fanwe_require(APP_ROOT_PATH . 'mapi/lib/redis/GamesRedisService.php');
						$redis       = new GamesRedisService();
						$last_game   = $video_info['game_log_id'];
						if ($last_game) {
							$last_game = $redis->get($last_game, 'game_id,create_time,long_time');
							$game_id = $last_game['game_id'];
							$is_enable = $game_id ? intval($plugin_id == $game_id) : 1;
							if ($game_id) {
								$error = $plugin_id == $game_id ? '游戏正在使用中...' : '请先关闭当前游戏再切换...';
							}
						}
						if(defined('OPEN_PAI_MODULE') && OPEN_PAI_MODULE==1){
						$pai_goods = $GLOBALS['db']->getRow("SELECT create_time,pai_time FROM ".DB_PREFIX."pai_goods WHERE id=".$video['pai_id']." ");
						if ($pai_goods) {
							$is_enable = 0;
							$error ='竞拍正在使用中...';
						}
						}
					}

					$plugin['is_enable'] = $is_enable;
				}
			}
			$root['class_name'] = $plugin['class_name'];
			$root['is_enable'] = $plugin['is_enable'];
			if($error!='')$root['error'] = $error;

			ajax_return($root);
		}
 }

//接收APP端错误日志
	public function log_err()
	{
		$agent = strtolower($_SERVER['HTTP_USER_AGENT']);
		$dev_type = strim($_REQUEST['sdk_type']);
		$sdk_version_name = strim($_REQUEST['sdk_version_name']);
		$sdk_version = strim($_REQUEST['sdk_version']);//升级版本号yyyymmddnn： 2016021601
		$err = trim($_REQUEST['desc']);
		if($err!=''){
			log_err_file(array($dev_type,$sdk_version_name,$sdk_version,$agent,$err));
		}
	}
}