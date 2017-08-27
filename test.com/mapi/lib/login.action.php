<?php
// +----------------------------------------------------------------------
// | Fanwe 方维p2p借贷系统
// +----------------------------------------------------------------------
// | Copyright (c) 2011 http://www.fanwe.com All rights reserved.
// +----------------------------------------------------------------------
// | Author: 云淡风轻(88522820@qq.com)
// +----------------------------------------------------------------------

class loginModule  extends baseModule
{
	//手机登录
	public function do_login()
	{
        $m_config =  load_auto_cache("m_config");//初始化手机端配置
        if(trim($_REQUEST['mobile']) == '13888888888'||trim($_REQUEST['mobile']) == '13999999999'){
        	
        	//控制审核账号登录
        	$dev_type = strim($_REQUEST['sdk_type']);
			if ($dev_type == 'ios'){
				if($m_config['ios_check_version'] == ''){
					$root['status'] = 0;
					$root['error'] = '审核账号非审核期间无法登录！';
					ajax_return($root);
				}
			}else{
				$root['status'] = 0;
				$root['error'] = '审核账号只能IOS端登录！';
				ajax_return($root);
			}
        	
			if($GLOBALS['db']->getOne("SELECT count(*) FROM ".DB_PREFIX."user WHERE mobile='".$_REQUEST['mobile']."'")>0){
				$root = array('status' => 0,'error'=>'','first_login'=>0);
				if(!$_REQUEST)
				{
					app_redirect(APP_ROOT."/");
				}
				foreach($_REQUEST as $k=>$v)
				{
					$_REQUEST[$k] = strim($v);
				}
				fanwe_require(APP_ROOT_PATH."system/libs/user.php");

				$result = do_login_user($_REQUEST['mobile'],$_REQUEST['verify_coder']);
				if($result['status'])
				{
					$root['user_id'] = $result['user']['id'];
					$root['status'] = 1;
					$root['is_lack'] = $result['is_lack'];

					$root['is_agree'] = intval($result['user']['is_agree']);//是否同意直播协议 0 表示不同意 1表示同意
					$root['user_id'] = intval($result['user']['id']);
					$root['nick_name'] = $result['user']['nick_name'];

					if($root['is_lack']){

					}else{
						$root['error'] = "登录成功";

					}
					$root['user_info'] = $result['user_info'];

                    $root['first_login'] = $result['first_login'];
                    $root['new_level'] = $result['new_level'];
                    $root['login_send_score'] = intval($m_config['login_send_score']);
				}
				else
				{
					$root['error'] = $result['info'];
				}
				ajax_return($root);
			}elseif($GLOBALS['db']->getOne("SELECT count(*) FROM ".DB_PREFIX."user WHERE mobile='".$_REQUEST['mobile']."'")==0){
				$root = array('status' =>0,'error'=>'','first_login'=>0,'new_level'=>0);
				$image = array(
						'./public/attachment/test/noavatar_0.JPG',
						'./public/attachment/test/noavatar_1.JPG',
						'./public/attachment/test/noavatar_2.JPG',
						'./public/attachment/test/noavatar_3.JPG',
						'./public/attachment/test/noavatar_4.JPG',
						'./public/attachment/test/noavatar_5.JPG',
						'./public/attachment/test/noavatar_6.JPG',
						'./public/attachment/test/noavatar_7.JPG',
						'./public/attachment/test/noavatar_8.JPG',
				);

				$random = mt_rand(0,8);
				//$user_id = get_max_user_id(0);
				$head_image =$image[$random];
				$nick_name='方维科技';
				$mobile = trim($_REQUEST['mobile']);
				$signature='方维科技';

				if($random%2==0)
					$sex = 1;
				else
					$sex = 2;

				$data = array(
						'head_image'=>$head_image,
						'nick_name'=>$nick_name,
						'sex'=>$sex,
						'mobile'=>$mobile,
						'signature'=>$signature,
				);
				if($GLOBALS['distribution_cfg']['OSS_TYPE']&&$GLOBALS['distribution_cfg']['OSS_TYPE']!='NONE')
				{
					syn_to_remote_image_server($data['head_image']);
				}
				fanwe_require(APP_ROOT_PATH."system/libs/user.php");
				$return = save_user($data);
				$user_data = $GLOBALS['db']->getRow("select * from ".DB_PREFIX."user where id =".$return['data']);

				if($user_data['id']!=''){
					$root['status'] = 1;
					$root['error'] = '注册登录成功';
					//添加 10000 钻石
					$GLOBALS['db']->query("update ".DB_PREFIX."user set `diamonds`=10000 where id =".$return['data']);
					if($GLOBALS['db']->affected_rows()){
						fanwe_require(APP_ROOT_PATH.'mapi/lib/redis/BaseRedisService.php');
						fanwe_require(APP_ROOT_PATH.'mapi/lib/redis/UserRedisService.php');
						$user_redis = new UserRedisService();
						$update_data['diamonds'] = 10000;
						$user_redis->update_db($return['data'], $update_data);
					}
                    //修改登录时间之前，获取上一次登录时间，每日首次登录赠送积分
                    $login_root = login_prompt($user_data['id']);
                    $root['first_login'] = $login_root['first_login'];
                    $root['new_level'] = $login_root['new_level'];
                    $root['login_send_score'] = intval($m_config['login_send_score']);
                    $GLOBALS['db']->query("update ".DB_PREFIX."user set login_ip = '".get_client_ip()."',login_time= '".to_date(NOW_TIME)."' where id =".$user_data['id']);
                    $user_redis->update_db($user_data['id'],array("login_time"=>to_date(NOW_TIME)));

					es_session::set("user_info",$user_data);
					$GLOBALS['user_info'] = $user_data;
					es_cookie::set("client_ip",CLIENT_IP,3600*24*30);
					es_cookie::set("nick_name",$user_data['nick_name'],3600*24*30);
					es_cookie::set("user_id",$user_data['id'],3600*24*30);
					es_cookie::set("user_pwd",md5($user_data['user_pwd']."_EASE_COOKIE"),3600*24*30);
					es_cookie::set("PHPSESSID2",es_session::id(),3600*24*30);

					$root['user_id'] = $user_data['id'];
					$root['nick_name'] = $user_data['nick_name'];
					$root['is_agree'] = intval($user_data['is_agree']);//是否同意直播协议 0 表示不同意 1表示同意

					$root['user_info']['user_id'] =$user_data['id'];
					$root['user_info']['nick_name'] =$user_data['nick_name'];
					$root['user_info']['mobile'] =$user_data['mobile'];
					$root['user_info']['signature'] =$user_data['signature'];
					$root['user_info']['head_image'] =get_spec_image($user_data['head_image']);

					if($user_data['synchronize'] == 0){
						//同步IM
						accountimport($user_data);

					}
					//当固定号码注册成功时,更新一次签名,防止报错.
					$GLOBALS['db']->query("update ".DB_PREFIX."user set `signature`=$signature where id =".$return['data']);
					if($GLOBALS['db']->affected_rows()){
						fanwe_require(APP_ROOT_PATH.'mapi/lib/redis/BaseRedisService.php');
						fanwe_require(APP_ROOT_PATH.'mapi/lib/redis/UserRedisService.php');
						$user_redis = new UserRedisService();
						$user_data['signature'] = $signature;
						$user_redis->update_db($return['data'], $user_data);
					}
				}
				ajax_return($root);
			}
		} else{
			$root = array('status' => 0,'error'=>'','first_login'=>0);
			if(!$_REQUEST)
			{
				app_redirect(APP_ROOT."/");
			}
			foreach($_REQUEST as $k=>$v)
			{
				$_REQUEST[$k] = strim($v);
			}
			fanwe_require(APP_ROOT_PATH."system/libs/user.php");
			$result = do_login_user($_REQUEST['mobile'],$_REQUEST['verify_coder']);
            if($result['status'])
            {
                $root['user_id'] = $result['user']['id'];
                $root['status'] = 1;
                $root['is_lack'] = $result['is_lack'];

                $root['is_agree'] = intval($result['user']['is_agree']);//是否同意直播协议 0 表示不同意 1表示同意
                $root['user_id'] = intval($result['user']['id']);
                $root['nick_name'] = $result['user']['nick_name'];

                if($m_config['name_limit']==1){
                    //登录过滤铭感词汇
                    $nick_name=$result['user']['nick_name'];
                    $limit_sql =$GLOBALS['db']->getCol("SELECT name FROM ".DB_PREFIX."limit_name");
                    //判断用户名是否含有铭感词汇,如果包含,替换
                    if($GLOBALS['db']->getCol("SELECT name FROM ".DB_PREFIX."limit_name WHERE '$nick_name' like concat('%',name,'%')")){
                        $root['nick_name']=str_replace($limit_sql,'',$nick_name);
                    }
                    //判断用户名如果被过滤后为空,格式则变更为： 账号+ID
                    if($root['nick_name']==''){
                        $root['nick_name']=('账号'.$root['user_id']);
                    }
                    $result['user_info']['nick_name']=$root['nick_name'];
                    $name=$result['user_info']['nick_name'];
                    $id=$result['user']['id'];
                    //更新数据库
                    $sql = "update ".DB_PREFIX."user set nick_name = '$name' where id=".$id;
                    $GLOBALS['db']->query($sql);
                    //更新redis
                    user_deal_to_reids(array($id));
                }
                $is_effect = $GLOBALS['db']->getOne("select is_effect from ".DB_PREFIX."user where id =".$root['user_id']);
                if($root['is_lack']){
                    $root['error'] = "请更新个人信息";
                }elseif($is_effect!=1){
                    ajax_return(array("status"=>0,"error"=>'账号已被禁用'));
                }elseif($GLOBALS['db']->getOne("SELECT login_ip FROM ".DB_PREFIX."user WHERE is_ban = 1 and ban_type = 1 and login_ip like '%".get_client_ip()."%' and is_effect !=1")){
                    ajax_return(array("status"=>0,"error"=>'当前IP已被封停'));
                }else{
                    $root['error'] = "登录成功";
                }
                $root['user_info'] = $result['user_info'];

                $root['first_login'] = $result['first_login'];
                $root['new_level'] = $result['new_level'];
                $root['login_send_score'] = intval($m_config['login_send_score']);
				//登录日志
				$log_date = array();
				if (is_array($_REQUEST)) $log_date['request'] = json_encode($_REQUEST);
				$log_date['login_type'] = 2 ;
				$log_date['user_id'] = $root['user_id'];
				if(function_exists('log_login')){
					log_login($log_date);
				}

            }
			else
			{
				$root['error'] = $result['info'];
			}
			ajax_return($root);
		}
	}


	//手机注册
	public function do_update()
	{
		$root =array("status"=>0,"error"=>'');
		if(!$GLOBALS['user_info']){
			$root['error'] = "用户未登陆,请先登陆.";
			$root['status'] = 0;
			$root['user_login_status'] = 0;//有这个参数： user_login_status = 0 时，表示服务端未登陆、要求登陆，操作
		}else{
			$user_data = array();
			$user_req = $_REQUEST;
			foreach($user_req as $k=>$v)
			{
				$user_req[$k] = strim($v);
			}
			$user_id = $GLOBALS['user_info']['id'];
			$user_data['id'] = $user_id;
			$type = intval($user_req['type']);//开启oss 上传OSS图片链接
			$oss_path = $user_req['oss_path'];//开启oss 上传OSS图片链接
			$normal_head_path = $user_req['normal_head_path'];//修改头像上传的图片链接
			$nick_name = $user_req['nick_name'];
			$head_image = $user_req['head_image'];//注册时候上传的头像链接

			fanwe_require(APP_ROOT_PATH."system/libs/user.php");

			if($GLOBALS['db']->getOne("SELECT login_ip FROM ".DB_PREFIX."user WHERE is_ban = 1 and ban_type = 1 and login_ip like '%".get_client_ip()."%' and is_effect !=1")){
				ajax_return(array("status"=>0,"error"=>'当前IP已被封停'));
			}
			$m_config =  load_auto_cache("m_config");//初始化手机端配置
			$open_sts = intval($m_config['open_sts']);
			$system_head_image = $m_config['app_logo'];

			//oss //注册流程 (ios:$normal_head_path==''，android:intval($user_req['sex'])!=0)&&$type==0
			//2.2 版本以后优化此部分 去除 $normal_head_path 和 $user_req['sex']的判断，目前兼容旧版本暂时保留
			if($normal_head_path==''&&$type==0&&intval($user_req['sex'])!=0){
				if($nick_name==''){
					ajax_return(array("status"=>0,"error"=>'请输入昵称'));
				}else{
					$user_data['nick_name'] = $nick_name;
				}
                if($GLOBALS['db']->getOne("SELECT nick_name FROM ".DB_PREFIX."user WHERE nick_name<>".$user_id." and nick_name ='$nick_name'"))
                {
                    ajax_return(array("status"=>0,"error"=>'昵称被占用，请重新输入'));
                }
				//过滤敏感词
				if($m_config['name_limit']==1){
					$limit_sql =$GLOBALS['db']->getCol("SELECT name FROM ".DB_PREFIX."limit_name");
					//昵称如果等于铭感词,则提示,如果包含 则用*代替
					$in=in_array($nick_name,$limit_sql);
					if($in){
						ajax_return(array("status"=>0,"error"=>'昵称包含敏感词汇'));
					}elseif($GLOBALS['db']->getCol("SELECT name FROM ".DB_PREFIX."limit_name WHERE '$nick_name' like concat('%',name,'%')")){
						$user_data['nick_name']=str_replace($limit_sql,'*',$nick_name);
					}
				}
				$user_data['sex'] = $user_req['sex']==1?1:2;
				//
				//判断头像 IOS 上传参数 $head_image；Android：$oss_path
				if($head_image==''&&$oss_path==''){
					if($system_head_image==''){
						$head_image = './public/attachment/test/noavatar_10.JPG';
						syn_to_remote_image_server($head_image,false);
					}else{
						$head_image = $system_head_image;
					}
				}else{
					if($oss_path!=''){
						$head_image=$oss_path;
					}
				}
			}else{//修改头像
				//兼容 IOS和Android 参数名称不同
				if($normal_head_path==''){
					$head_image=$oss_path;
				}else if($normal_head_path!=''){
					$head_image=$normal_head_path;
				}
				$user_data['sex'] = $GLOBALS['user_info']['sex'];
			}
			$user_data['head_image']=$head_image;
			$res = update_mobile_user($user_data,'UPDATE');

			if($res['status'] == 1)
			{
				//更新session
				$user_info = $GLOBALS['db']->getRow("select * from ".DB_PREFIX."user where id =".$user_id);
				es_session::set("user_info", $user_info);

				$user	=array();
				$user['user_id'] =$user_info['id'];
				$user['nick_name'] =$user_info['nick_name'];
				$user['mobile'] =$user_info['mobile'];
				$user['head_image'] =get_spec_image($user_info['head_image']);

				$root['error']='修改成功';
				$root['status']=1;
				$root['user_id']=$res['data'];
				$root['user_info']=$user;
				ajax_return($root);
			}
			else
			{
				$root['error']=$res['error'];
				ajax_return($root);
			}
		}
	}
	//退出功能
	public function loginout(){
		$ajax = intval($_REQUEST['ajax']);
		fanwe_require(APP_ROOT_PATH."system/libs/user.php");
		$result = loginout_user();

		es_session::delete("user_info");
		$root['status'] = 1;
		$root['error'] = "登出成功";

		ajax_return($root);
	}
	//检查验证码是否正确
	function check_verify_code()
	{
		$settings_mobile_code=strim($_REQUEST['code']);
		$mobile=strim($_REQUEST['mobile']);


		if($GLOBALS['db']->getOne("SELECT count(*) FROM ".DB_PREFIX."mobile_verify_code WHERE mobile=".$mobile." AND verify_code='".$settings_mobile_code."'")==0){
			$data['status'] = 0;
			$data['info'] = "手机验证码错误";
			ajax_return($data);
		}else{
			$data['status'] = 1;
			$data['info'] = "验证码正确";
			ajax_return($data);
		}


	}
	//是否开启图片验证
	function is_user_verify(){
		$root =array('status'=>0,'error'=>'','verify_url'=>'');
		/*
		if(app_conf('USER_VERIFY_STATUS')){
			$root['status']=1;
			$root['verify_url']=get_domain().APP_ROOT.'/verify.php?name=login_verify';
		}*/
		ajax_return($root);
	}
	//发送手机验证码
	function send_mobile_verify(){
		$mobile = addslashes(htmlspecialchars(trim($_REQUEST['mobile'])));

		if(app_conf("SMS_ON")==0)
		{
			$root['status'] = 0;
			$root['error'] = "短信未开启";
			ajax_return($root);
		}
		if($mobile == '')
		{
			$root['status'] = 0;
			$root['error'] = "请输入你的手机号";
			ajax_return($root);
		}


		if(!check_mobile($mobile))
		{
			$root['status'] = 0;
			$root['error'] = "请填写正确的手机号码";
			ajax_return($root);
		}
		
		//添加：手机发送 防护
		$root = check_sms_send($mobile);
		if ($root['status'] == 0){
			$root['time'] = 0;
			ajax_return($root);
		}
		
		//图片验证码
		/*if(app_conf("USER_VERIFY_STATUS")==1){
			$verify=es_session::get("login_verify");
			$verify_1=es_session::get("login_verify_1");
			$image_code = strim($_REQUEST['image_code']);
			if($image_code){
				$verify = $GLOBALS['db']->getOne("select verify_code from ".DB_PREFIX."image_verify_code where mobile = '".$mobile."'");
				if(md5($image_code)!=$verify){
					ajax_return(array("status"=>0,"error"=>"图片验证码错误!".md5($image_code)."--".$verify));
				}
			}else{
				ajax_return(array("status"=>0,"error"=>"图片验证码不能为空!"));
			}
 		}*/
		//微信绑定判断手机是否被使用
		//获取登录方式
		if(intval($_REQUEST['wx_binding'])){
			if($GLOBALS['user_info']['login_type']==''){
				$login_type_sql = "select login_type from ".DB_PREFIX."user where id = '".$GLOBALS['user_info']['id']."'";
				$login_type = $GLOBALS['db']->getOne($login_type_sql);
			}else{
				$login_type = $GLOBALS['user_info']['login_type'];
			}

			if($GLOBALS['db']->getOne("select count(*) from ".DB_PREFIX."user where mobile = '".$mobile."' and login_type = ".$login_type) > 0){
				$root['status'] = 0;
				$root['error'] = "该手机号已经被使用过";
				ajax_return($root);
			}
		}
		
        if($GLOBALS['db']->getOne("select id from ".DB_PREFIX."user where mobile = '".$mobile."' and is_effect =0")){
            $root['status'] = 0;
            $root['error'] = "账号已被禁用";
            ajax_return($root);
        }

		$result = array("status"=>1,"info"=>'');


		/*if(!check_ipop_limit(get_client_ip(),"mobile_verify",60,0))
		{
			$root['status'] = 0;
			$root['error'] = "发送速度太快了";
			ajax_return($root);
		}*/

		if($GLOBALS['db']->getOne("select count(*) from ".DB_PREFIX."mobile_verify_code where mobile = '".$mobile."' and client_ip='".get_client_ip()."' and create_time>=".(get_gmtime()-60)." ORDER BY id DESC") > 0)
		{
			$root['status'] = 0;
			$root['error'] = "发送速度太快了";
			ajax_return($root);
		}
		$n_time=get_gmtime()-300;
		//删除超过5分钟的验证码
		$GLOBALS['db']->query("DELETE FROM ".DB_PREFIX."mobile_verify_code WHERE create_time <=".$n_time);
		//开始生成手机验证
		if($mobile == '13888888888'||$mobile=='13999999999') {
			$root['status'] = 1;
			$root['time'] = 60;
			$root['error'] = "发送成功";
		}else{
			$code = rand(1000,9999);
			$GLOBALS['db']->autoExecute(DB_PREFIX."mobile_verify_code",array("verify_code"=>$code,"mobile"=>$mobile,"create_time"=>get_gmtime(),"client_ip"=>get_client_ip()),"INSERT");

			send_verify_sms($mobile,$code);
			$status = $GLOBALS['db']->getRow("select * from ".DB_PREFIX."deal_msg_list where dest = '".$mobile."' and code='".$code."'");

			if($status['is_success']){
				$root['status'] = 1;
				$root['time'] = 60;
				$root['error'] = $status['title'].$status['result'];
			}else{
				$root['status'] = 0;
				$root['time'] = 0;
				$root['error'] = "短信验证码发送失败";
			}
		}


		ajax_return($root);
	}
	//微信登录回调
	public function wx_login(){

		$root = array('status'=>1,'error'=>'');
		define('DEBUG_WX',0);
		fanwe_require(APP_ROOT_PATH."system/utils/weixin.php");
		$m_config =  load_auto_cache("m_config");//初始化手机端配置
		$ajax= 1;
		//获取微信配置信息
		if(DEBUG_WX){
			log_result('--微信登录回调调试开始--');
			log_result('-获取微信配置信息-');
			log_result('-wx_appid-');
			log_result($m_config['wx_appid']);
			log_result('-wx_secrit-');
			log_result($m_config['wx_secrit']);
		}

		if($m_config['wx_appid']==''||$m_config['wx_secrit']==''){
			$root['status'] = 0;
			$root['error'] = "wx_appid或wx_secrit不存在";
			ajax_return($root);
		}else{
			$wx_appid = strim($m_config['wx_appid']);
			$wx_secrit = strim($m_config['wx_secrit']);
		}

		$jump_url = SITE_DOMAIN.url_wap("login#wx_login");
		if(DEBUG_WX){
			log_result('-获取微信跳转jump_url-');
			log_result($jump_url);
		}
		$weixin=new weixin($wx_appid,$wx_secrit,$jump_url);

		if(($_REQUEST['openid']!=""&&$_REQUEST['access_token']!="")||$_REQUEST['code']!=""){
			if($_REQUEST['openid']!=""&&$_REQUEST['access_token']!=""){
				$wx_info=$weixin->sns_get_userinfo($_REQUEST['openid'],$_REQUEST['access_token']);
			}else if($_REQUEST['code']!=""){
				$wx_info=$weixin->scope_get_userinfo($_REQUEST['code']);
			}else{
				if(DEBUG_WX){
					log_result('-服务端获取微信参数失败-');
				}
				$root['status'] = 0;
				$root['error'] = "服务端获取微信参数失(openid or code).";
			}

			if($wx_info&&intval($root['status'])!=0){
				if(intval($wx_info['errcode'])!=0){
					$root['status'] = 0;
					$root['error'] =$wx_info['errcode'].$wx_info['errmsg'];
					ajax_return($root);
				}

				if(DEBUG_WX){
					log_result('-微信scope_get_userinfo->wx_info-');
					log_result($wx_info);
				}
				fanwe_require(APP_ROOT_PATH."system/libs/user.php");
				$root = wxxMakeUser($wx_info);
				if(DEBUG_WX){
					log_result('-微信登录信息wxxMakeUser-');
					log_result($root);
				}
				$root['login_send_score'] = intval($m_config['login_send_score']);
				//登录日志
				$log_date = array();
				if (is_array($_REQUEST)) $log_date['request'] = json_encode($_REQUEST,true);
				//'0：微信；1：QQ；2：手机；3：微博',
				$log_date['login_type'] = 0 ;
				$log_date['user_id'] = $root['user_id'];
				if(function_exists('log_login')){
					log_login($log_date);
				}
			}
		}else{
			if(DEBUG_WX){
				log_result('-服务端获取微信参数失败-');
			}
			$root['status'] = 0;
			$root['error'] = "无法获取APP端微信参数(openid or code)!";
		}
		ajax_return($root);
	}
	//QQ登录
	function qq_login(){
		$m_config =  load_auto_cache("m_config");//初始化手机端配置
		//获取QQ openid
		$openid = trim($_REQUEST['openid']);
		$access_token = trim($_REQUEST['access_token']);
		if($openid==''){
			$root['status'] = 0;
			$root['error'] = "openid不存在";
			ajax_return($root);
		}
		if($access_token==''){
			$root['status'] = 0;
			$root['error'] = "access_token不存在";
			ajax_return($root);
		}

		// 应用基本信息
		$dev_type = strim($_REQUEST['sdk_type']);
		if(isios()||$dev_type=='ios'){
			$appid =$m_config['ios_qq_app_id'];
		}else{
			$appid =$m_config['android_qq_app_id'];
		}

		if($appid==''){
			$root['status'] = 0;
			$root['error'] = "qq_app_id不存在0".$appid;
			ajax_return($root);
		}

		fanwe_require(APP_ROOT_PATH."system/QQloginApi/qqConnectAPI.php");
		$qc = new QC($access_token,$openid,$appid);
		$ret = $qc->get_user_info();
		$ret['openid'] = $openid;
		fanwe_require(APP_ROOT_PATH."system/libs/user.php");
		if($ret['ret']==0){
			$root = qqMakeUser($ret);
            $root['login_send_score']= intval($m_config['login_send_score']);
			//登录日志
			$log_date = array();
			if (is_array($_REQUEST)) $log_date['request'] = json_encode($_REQUEST,true);
			//'0：微信；1：QQ；2：手机；3：微博',
			$log_date['login_type'] = 1 ;
			$log_date['user_id'] = $root['user_id'];
			if(function_exists('log_login')){
				log_login($log_date);
			}

		}else{
			log_err_file(array(__FILE__,__LINE__,__METHOD__,$ret));
			$root['status'] = 0;
			$root['error'] = "ret".$ret['ret'].",msg:".$ret['msg'];
		}
		ajax_return($root);
	}
	//新浪微博登录
	function sina_login(){
		$m_config =  load_auto_cache("m_config");//初始化手机端配置
		$access_token = trim($_REQUEST['access_token']);
		$sina_id = trim($_REQUEST['sina_id']);
		if($access_token==''){
			$root['status'] = 0;
			$root['error'] = "access_token不存在";
			ajax_return($root);
		}
		if($sina_id==''){
			$root['status'] = 0;
			$root['error'] = "sina_id不存在";
			ajax_return($root);
		}
		fanwe_require(APP_ROOT_PATH."system/WBloginApi/saetv2.ex.class.php");

		if($m_config['sina_app_key']==''||$m_config['sina_app_secret']==''){
			$root['status'] = 0;
			$root['error'] = "sina_app_key或sina_app_secret不存在";
			ajax_return($root);
		}

		$c = new SaeTClientV2( $m_config['sina_app_key'] , $m_config['sina_app_secret'] , $access_token);
		$ms  = $c->home_timeline(); // done
		$uid_get = $c->get_uid();
		$uid = $uid_get['uid'];
		$user_message = $c->show_user_by_id($uid);//根据ID获取用户等基本信息
		$user_message['sina_id'] =$sina_id;
		fanwe_require(APP_ROOT_PATH."system/libs/user.php");
		if(intval($user_message['error_code'])){
			log_err_file(array(__FILE__,__LINE__,__METHOD__,$user_message['error']));
			$root['status'] = 0;
			$root['error'] = "error_code".$user_message['error_code'].",error:".$user_message['error'];
		}else{
			$root = sinaMakeUser($user_message);
			$root['login_send_score']= intval($m_config['login_send_score']);
			//登录日志
			$log_date = array();
			if (is_array($_REQUEST)) $log_date['request'] = json_encode($_REQUEST,true);
			//'0：微信；1：QQ；2：手机；3：微博',
			$log_date['login_type'] = 3 ;
			$log_date['user_id'] = $root['user_id'];
			if(function_exists('log_login')){
				log_login($log_date);
			}
		}
		ajax_return($root);
	}
	//国家电话地区表
	function mobile_code(){
		$root =array();
		$mobile_code = load_auto_cache("mobile_code");
		$root=$mobile_code;
		$root['status'] = 1;
		$root['error'] = "";
		ajax_return($root);
	}
	
	//游客登录
	public function visitors_login()
	{
		$root = array('status' =>0,'error'=>'');
		$m_config =  load_auto_cache("m_config");//初始化手机端配置
		if(intval(VISITORS)&&intval($m_config['open_visitors_login'])){
			if(intval(DEBUG_VISITORS)){
				log_file('游客注册api接收信息','visitors_login');
				log_file($_REQUEST,'visitors_login');
			}
			$um_reg_id = strim($_REQUEST['um_reg_id']);
			//客服端手机类型dev_type=android;dev_type=ios
			$dev_type = strim($_REQUEST['sdk_type']);
			//验证um_reg_id
			$um_reg_id_info= $this->verify_um_reg_id($um_reg_id,$dev_type);
			$is_agree = 1;//ios 无法验证推送 暂时跳过验证
			if($um_reg_id_info['status']){
				$is_agree = 1;
			}

			if($um_reg_id!=''&&$is_agree){
				$root = $this->login_viditors($um_reg_id);
			}else{
				$root['error'] ='请求参数错误！其重新登录！'.$um_reg_id."--".$is_agree;
			}
		}else{
			$root['error'] ='功能未开放，请联系管理员。';
		}
		ajax_return($root);
	}
	public function  login_viditors($um_reg_id){
		fanwe_require(APP_ROOT_PATH."system/libs/user.php");
		$result = array('status'=>0,'info'=>'','is_lack'=>0);
		fanwe_require(APP_ROOT_PATH.'mapi/lib/redis/UserRedisService.php');
		$user_redis = new UserRedisService();
		$sql = "select * from ".DB_PREFIX."user where  apns_code = '".$um_reg_id."' and login_type = 4";
		if(intval(DEBUG_VISITORS)) {
			log_file('游客是否已经注册', 'visitors_login');
			log_file($sql, 'visitors_login');
		}
		$user = $GLOBALS['db']->getRow($sql);

		$user_id = intval($user['id']);
		//如果不存在，注册账号
		if(!$user)
		{
			$result = $this->reg_visitors($um_reg_id);
		}else{
			$result['user'] = $user;
			//判断账号有效
			if($user['is_effect'] != 1){
				$result['info'] = "帐户已被禁用,请联系管理员";
			}
			else
			{
				$result['status'] =1;
				//更新等级
				user_leverl_syn($user);
				$login_time = get_gmtime();
				$user['login_time'] = $login_time;
				//设置cookie
				es_cookie::set("client_ip",CLIENT_IP,3600*24*30);
				es_cookie::set("nick_name",$user['nick_name'],3600*24*30);
				es_cookie::set("user_id",$user['id'],3600*24*30);
				es_cookie::set("user_pwd",md5($user['user_pwd']."_EASE_COOKIE"),3600*24*30);
				es_cookie::set("PHPSESSID2",es_session::id(),3600*24*30);

				//设置session
				es_session::set("user_info",$user);
				$GLOBALS['user_info'] = $user;
				//修改登录时间之前，获取上一次登录时间，每日首次登录赠送积分
				$login_root = login_prompt($user['id']);
				$result['first_login'] = $login_root['first_login'];
				$result['new_level'] = $login_root['new_level'];

				$GLOBALS['db']->query("update ".DB_PREFIX."user set login_ip = '".get_client_ip()."',login_time= '".to_date($login_time)."' where id =".$user['id']);
				//更新redis
				$user_redis->update_db($user['id'],array("login_time"=>to_date($login_time),"login_ip"=>get_client_ip()));

				//登录成功 同步信息
				$user_im = array();
				$user_im['id']=$user['id'];
				$user_im['nick_name']=$user['nick_name'];
				$user_im['head_image']=$user['head_image'];
				if($user_im['nick_name']==''){
					$user_im['nick_name']= '游客'.$user['id'];
				}
				if($user_im['head_image']==''){
					$m_config =  load_auto_cache("m_config");//初始化手机端配置
					$system_head_image = $m_config['app_logo'];
					if($system_head_image==''){
						$system_head_image = './public/attachment/test/noavatar_10.JPG';
					}
					$user_im['head_image'] = $system_head_image;
				}
				accountimport($user_im);

			}

			if($user['nick_name']==''||$user['head_image']==''){
				$result['is_lack'] = 1;
			}
			set_xy_point($user['id']);
			$result['user_info']['user_id'] =$user['id'];
			$result['user_info']['nick_name'] =$user['nick_name']?$user['nick_name']:'';
			$result['user_info']['mobile'] =$user['mobile']?$user['mobile']:'';
			$result['user_info']['head_image'] =get_spec_image($user['head_image']);
		}

		return $result;
	}

	public function reg_visitors($um_reg_id){
		$image = array(
			'./public/attachment/test/noavatar_0.JPG',
			'./public/attachment/test/noavatar_1.JPG',
			'./public/attachment/test/noavatar_2.JPG',
			'./public/attachment/test/noavatar_3.JPG',
			'./public/attachment/test/noavatar_4.JPG',
			'./public/attachment/test/noavatar_5.JPG',
			'./public/attachment/test/noavatar_6.JPG',
			'./public/attachment/test/noavatar_7.JPG',
			'./public/attachment/test/noavatar_8.JPG',
		);

		$random = mt_rand(0,8);
		$head_image =$image[$random];
		$nick_name='游客';

		if($random%2==0)
			$sex = 1;
		else
			$sex = 2;

		$data = array(
			'head_image'=>$head_image,
			'nick_name'=>$nick_name,
			'sex'=>$sex,
			'apns_code'=>$um_reg_id,
			'login_type'=>4,
		);

		if($GLOBALS['distribution_cfg']['OSS_TYPE']&&$GLOBALS['distribution_cfg']['OSS_TYPE']!='NONE')
		{
			syn_to_remote_image_server($data['head_image'],false);
		}
		fanwe_require(APP_ROOT_PATH."system/libs/user.php");
		if(intval(DEBUG_VISITORS)){
			log_file('游客注册数据','visitors_login');
			log_file($data,'visitors_login');
		}

		$return = save_user($data);
		$user_data = $GLOBALS['db']->getRow("select * from ".DB_PREFIX."user where id =".$return['data']);

		if($user_data['id']!=''){
			//更新昵称
			$user_nick_name = $nick_name = $user_data['nick_name'] = $user_data['nick_name'].":". $user_data['id'];
			$GLOBALS['db']->query("update ".DB_PREFIX."user set login_ip = '".get_client_ip()."', nick_name = '".$user_nick_name."' where id =".$user_data['id']);

			$root['status'] = 1;
			$root['error'] = $nick_name.'注册登录成功';
			$root['user'] = $user_data;
			es_session::set("user_info",$user_data);
			$GLOBALS['user_info'] = $user_data;
			es_cookie::set("client_ip",CLIENT_IP,3600*24*30);
			es_cookie::set("nick_name",$user_data['nick_name'],3600*24*30);
			es_cookie::set("user_id",$user_data['id'],3600*24*30);
			es_cookie::set("user_pwd",md5($user_data['user_pwd']."_EASE_COOKIE"),3600*24*30);
			es_cookie::set("is_agree",$user_data['is_agree'],3600*24*30);
			es_cookie::set("PHPSESSID2",es_session::id(),3600*24*30);

			$root['user_info']['user_id'] = $root['user_id'] = $user_data['id'];
			$root['user_info']['nick_name'] = $root['nick_name'] = $user_data['nick_name'];
			$root['is_agree'] = intval($user_data['is_agree']);//是否同意直播协议 0 表示不同意 1表示同意
			$root['user_info']['head_image'] = $root['head_image'] = get_abs_img_root($user_data['head_image']);//是否同意直播协议 0 表示不同意 1表示同意

			if($user_data['synchronize'] == 0){
				//同步IM
				accountimport($user_data);
			}

		}else{
			$root['error'] =$nick_name.'注册登录成功';
		}
		return $root;
	}

	public function verify_um_reg_id($um_reg_id,$dev_type){
		$result = array('status'=>0,'error'=>'',);
		fanwe_require(APP_ROOT_PATH.'system/schedule/android_list_schedule.php');
		fanwe_require(APP_ROOT_PATH.'system/schedule/android_unicast_schedule.php');
		fanwe_require(APP_ROOT_PATH.'system/schedule/ios_list_schedule.php');
		fanwe_require(APP_ROOT_PATH.'system/schedule/ios_unicast_schedule.php');
		if(strtolower($dev_type)=='ios'){
			$apns_ios_code_list[0] = $um_reg_id;
		}else{
			$apns_app_code_list[0] = $um_reg_id;
		}
		$content = '游客账号注册！';
		//安卓推送信息
		if(count($apns_app_code_list)>0){
			$AndroidList = new android_unicast_schedule();
			$android_dest = implode(",",$apns_app_code_list);
			$data = array(
				'dest' =>$android_dest,
				'content' =>$content,
				'type'=>0,
			);
			if(intval(DEBUG_VISITORS)){
				log_file('友盟推送提交android','visitors_login');
				log_file($data,'visitors_login');
			}
			$return = $AndroidList->exec($data);
		}
		//ios 推送信息
		if(count($apns_ios_code_list)>0){
			$IosList = new ios_list_schedule();
			$ios_dest = implode(",",$apns_ios_code_list);
			$ios_data = array(
				'dest' =>$ios_dest,
				'content' =>$content,
				'type'=>0,
			);
			if(intval(DEBUG_VISITORS)){
				log_file('友盟推送提交ios','visitors_login');
				log_file($data,'visitors_login');
			}
			$return = $IosList->exec($ios_data);

		}
		if(intval(DEBUG_VISITORS)){
			log_file('友盟推送结果','visitors_login');
			log_file($data,'visitors_login');
		}
		if($return['res']['ret']=='SUCCESS'){
			$result['status'] = 1;
		}else{
			log_err_file(array(__FILE__,__LINE__,__METHOD__,$return));
		}
		return $result;
	}
}


?>