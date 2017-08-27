<?php
// +----------------------------------------------------------------------
// | Fanwe 方维众筹商业系统
// +----------------------------------------------------------------------
// | Copyright (c) 2011 http://www.fanwe.com All rights reserved.
// +----------------------------------------------------------------------
// | Author: 甘味人生(526130@qq.com)
// +----------------------------------------------------------------------

class ConfAction extends CommonAction{
    //检查模块开关是否开启
    public function  check_Module(){
        $m_config =  load_auto_cache("m_config");
        if($m_config['has_dirty_words']==0){
            $this->redirect('APP_ROOT+'.get_manage_url_name().'?m=Conf&a=mobile&');
        }
    }
	public function index()
	{
		$conf_res = M("Conf")->where("is_effect = 1 and is_conf = 1")->order("group_id asc,sort asc")->findAll();

		foreach($conf_res as $k=>$v)
		{
			$v['value'] = htmlspecialchars($v['value']);
			/*if($v['name']=='TEMPLATE')
			{
				
				//输出现有模板文件夹
				$directory = APP_ROOT_PATH."admin/app/Tpl/";
				$dir = @opendir($directory);
			    $tmpls     = array();
			
			    while (false !== ($file = @readdir($dir)))
			    {
			    	if($file!='.'&&$file!='..')
			        $tmpls[] = $file;
			    }
			    @closedir($dir);
				//end
				
				$v['input_type'] = 1;
				$v['value_scope'] = $tmpls;
			}
			elseif($v['name']=='SHOP_LANG')
			{
				//输出现有语言包文件夹
				$directory = APP_ROOT_PATH."app/Lang/";
				$dir = @opendir($directory);
			    $tmpls     = array();
			
			    while (false !== ($file = @readdir($dir)))
			    {
			    	if($file!='.'&&$file!='..')
			        $tmpls[] = $file;
			    }
			    @closedir($dir);
				//end
				
				$v['input_type'] = 1;
				$v['value_scope'] = $tmpls;
			}
			else*/
			$v['value_scope'] = explode(",",$v['value_scope']);
			$conf[$v['group_id']][] = $v;
		}
		$ticke_name = M("MConfig")->where("code='ticket_name'")->getField("val");
		$this->assign("ticke_name",$ticke_name);
		$this->assign("conf",$conf);
		$this->display();
	}
	
	public function update()
	{
		$conf_res = M("Conf")->where("is_effect = 1 and is_conf = 1")->findAll();
		foreach($conf_res as $k=>$v)
		{
			conf($v['name'],$_REQUEST[$v['name']]);
			if($v['name']=='URL_MODEL'&&$v['value']!=$_REQUEST[$v['name']])
			{
				clear_dir_file(get_real_path()."public/runtime/app/data_caches/");	
				clear_dir_file(get_real_path()."public/runtime/app/tpl_caches/");	
				clear_dir_file(get_real_path()."public/runtime/app/tpl_compiled/");	
				
				clear_dir_file(get_real_path()."public/runtime/app/data_caches/");	
				clear_dir_file(get_real_path()."public/runtime/data/page_static_cache/");
				clear_dir_file(get_real_path()."public/runtime/data/dynamic_avatar_cache/");	
			}
		}

			$sys_configs_array = array();
			//开始写入配置文件
			$sys_configs = M("Conf")->findAll();
			$config_str = "<?php\n";
			$config_str .= "return array(\n";
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                 			foreach($sys_configs as $k=>$v)
			{
				$config_str.="'".$v['name']."'=>'".addslashes($v['value'])."',\n";
				$sys_configs_array[$v['name']] = addslashes($v['value']);
			}
			$config_str.=");\n ?>";
			$filename = get_real_path()."public/sys_config.php";
			
		    if (!$handle = fopen($filename, 'w')) {
			     $this->error(l("OPEN_FILE_ERROR").$filename);
			}
			
			    
			if (fwrite($handle, $config_str) === FALSE) {
			     $this->error(l("WRITE_FILE_ERROR").$filename);
			}
			
	    fclose($handle);
			

			
		save_log(l("CONF_UPDATED"),1);		
		//clear_cache();
		write_timezone();
		//var_dump($sys_configs_array);exit;
		create_app_js($sys_configs_array);
		$this->success(L("UPDATE_SUCCESS"));
	}
	
	public function mobile()
	{
		$config = M("MConfig")->order("sort asc")->findAll();
		
		foreach($config as $k=>$v){
			/*if($v['code']=='wx_appid'){
				$wx_appid=$v['val'];
				continue;
			}
			if($v['code']=='wx_secrit'){
				$wx_secrit=$v['val'];
				continue;
			}*/
			if($v['group_id'] == '鲜肉设置'){
				unset($config[$k]);
				continue;
			}

			if(!defined('OPEN_PC') || OPEN_PC != 1) {
				/*if(in_array($v['code'], array(
					'rank_cache_time',
					'videotime_to_experience',
					'contribution_to_experience',
					'wx_web_appid',
					'wx_web_secrit',
					'sina_web_app_key',
					'sina_web_app_secret',
					'qq_web_app_id',
					'qq_web_app_key',
					'qq_wpa_key',
					'pc_logo',
				))){
					unset($config[$k]);
					continue;
				}*/
                if($v['group_id'] == 'PC端设置'){
                    unset($config[$k]);
                    continue;
                }
			} else if (defined('ONLY_PC') && ONLY_PC == 1) {
				if(!in_array($v['code'], array(
					'rank_cache_time',
					'videotime_to_experience',
					'contribution_to_experience',
					'wx_web_appid',
					'wx_web_secrit',
					'sina_web_app_key',
					'sina_web_app_secret',
					'qq_web_app_id',
					'qq_web_app_key',
					'qq_wpa_key',
					'pc_logo',

					'short_name',
					'share_title',
					'short_video_time',
					'monitor_overtime',
					'robot_num',
					'virtual_number',
					'tim_sdkappid',
					'tim_identifier',
					'tim_account_type',
					'qcloud_secret_id',
					'qcloud_secret_key',

					'diamonds_rate',
					'exchange_rate',
					'kf_phone',
					'ticket_name',
					'profit_ratio',
					'has_is_authentication',

					// 2.1
					'has_dirty_words',
					'must_authentication',

				))){
					unset($config[$k]);
					continue;
				}
			}
			//声网
            if((!defined('SOUND_NETWORK')||SOUND_NETWORK!=1)&&$v['code']=='agora_app_id'){
                unset($config[$k]);
                continue;
            }
            if((!defined('SOUND_NETWORK')||SOUND_NETWORK!=1)&&$v['code']=='agora_app_certificate'){
                unset($config[$k]);
                continue;
            }
            if((!defined('SOUND_NETWORK')||SOUND_NETWORK!=1)&&$v['code']=='agora_anchor_resolution'){
                unset($config[$k]);
                continue;
            }
            if((!defined('SOUND_NETWORK')||SOUND_NETWORK!=1)&&$v['code']=='agora_audience_resolution'){
                unset($config[$k]);
                continue;
            }
			//发言等级
            if((!defined('OPEN_SPEAK_LEVEL')||OPEN_SPEAK_LEVEL!=1)&&$v['code']=='speak_level'){
                unset($config[$k]);
                continue;
            }
            if(!(defined('OPEN_MISSION') && OPEN_MISSION == 1) && in_array(['mission_switch','mission_money','mission_max_times','mission_time','mission_name','mission_desc'], $v['code'])){
                unset($config[$k]);
                continue;
            }
			//置顶权重
            if((!defined('OPEN_STICK')||OPEN_STICK!=1)&&$v['code']=='top_weight'){
                unset($config[$k]);
                continue;
            }
            //审核时间
            if((!defined('EXAMINE_TIME')||EXAMINE_TIME!=1)&&$v['code']=='attestation_time'){
                unset($config[$k]);
                continue;
            }
			//家族
			if(!defined('OPEN_FAMILY_MODULE')||OPEN_FAMILY_MODULE!=1){
				if($v['code']=='profit_ratio'||$v['code']=='family_profit_platform') {
					unset($config[$k]);
					continue;
				}
			}
			//登录送积分
            if((!defined('OPEN_LOGIN_SEND_SCORE')||OPEN_LOGIN_SEND_SCORE!=1)&&$v['code']=='login_send_score'){
                unset($config[$k]);
                continue;
            }
			//升级提升
            if((!defined('OPEN_UPGRADE_PROMPT')||OPEN_UPGRADE_PROMPT!=1)&&$v['code']=='upgrade_level'){
                unset($config[$k]);
                continue;
            }
			//付费
            if((!defined('OPEN_LIVE_PAY')||OPEN_LIVE_PAY!=1)&&$v['group_id']=='付费直播配置'){
				unset($config[$k]);
				continue;
			}
			//分销
            if((!defined('OPEN_DISTRIBUTION')||OPEN_DISTRIBUTION!=1)&&$v['group_id']=='分销模块'){
                unset($config[$k]);
                continue;
            }
			//PC端
            if((!defined('OPEN_PC')||OPEN_PC!=1)&&$v['group_id']=='PC端设置'){
                unset($config[$k]);
                continue;
            }
			//分享收益
            if((!defined('OPEN_SHARE_EXPERIENCE')||OPEN_SHARE_EXPERIENCE!=1)){
                if($v['code']=='open_share_ticket' || $v['code']=='share_ticket'){
                    unset($config[$k]);
                    continue;
                }
            }
            if((!defined('OPEN_PAI_MODULE')||OPEN_PAI_MODULE!=1)){
            	if($v['code']=='ticket_exchange_rate'){
            		unset($config[$k]);
            		continue;
            	}
            }
			//游客
			if((!defined('VISITORS')||VISITORS!=1)){
				if($v['code']=='open_visitors_login'){
					unset($config[$k]);
					continue;
				}
			}

            //支付宝认证
            if((!defined('OPEN_AUTHENT_ALIPAY')||OPEN_AUTHENT_ALIPAY!=1)){
            	if($v['code']=='authent_alipay'||$v['code']=='alipay_partner'||$v['code']=='alipay_key'){
            		unset($config[$k]);
            		continue;
            	}
            }
            //分销
            if((!defined('OPEN_DISTRIBUTION')||OPEN_DISTRIBUTION!=1)){
            	if($v['code']=='distribution'||$v['code']=='distribution_rate'){
            		unset($config[$k]);
            		continue;
            	}
            }
			//VIP
            if((!defined('OPEN_VIP')||OPEN_VIP!=1)&&$v['code']=='open_vip'){
                unset($config[$k]);
                continue;
            }
			//房间隐藏
            if((!defined('OPEN_ROOM_HIDE')||OPEN_ROOM_HIDE!=1)&&$v['code']=='open_room_hide'){
                unset($config[$k]);
                continue;
            }
			//工会
            if((!defined('OPEN_SOCIETY_MODULE')||OPEN_SOCIETY_MODULE!=1)&&$v['code']=='society_public_rate'){
                unset($config[$k]);
                continue;
            }
			//工会收益
            if((!defined('OPEN_SOCIETY_MODULE')||OPEN_SOCIETY_MODULE!=1) && ($v['code']=='society_user_rate')){
                unset($config[$k]);
                continue;
            }
            if(!(defined('OPEN_GAME_MODULE') && OPEN_GAME_MODULE && !(defined('OPEN_DIAMOND_GAME_MODULE') && OPEN_DIAMOND_GAME_MODULE)) && $v['code']=='coin_exchange_rate'){
                unset($config[$k]);
                continue;
            }
            if (!(defined('GAME_GAIN_FOR_ALERT') && GAME_GAIN_FOR_ALERT) && $v['code']=='game_gain_for_alert') {
            	unset($config[$k]);
            	continue;
            }

            if (!(defined('GAME_COMMISSION') && GAME_COMMISSION) && $v['code']=='game_commission') {
            	unset($config[$k]);
            	continue;
            }
            if (!(defined('GAME_DISTRIBUTION') && GAME_DISTRIBUTION) && $v['code']=='game_distribution1') {
            	unset($config[$k]);
            	continue;
            }
            if (!(defined('GAME_DISTRIBUTION') && GAME_DISTRIBUTION) && $v['code']=='game_distribution2') {
            	unset($config[$k]);
            	continue;
            }
            if (!(defined('ENTER_INVITATION_CODE') && ENTER_INVITATION_CODE) && $v['code']=='enter_invitation_code') {
            	unset($config[$k]);
            	continue;
            }
            if (!(defined('ENTER_INVITATION_CODE') && ENTER_INVITATION_CODE) && $v['code']=='enter_invitation_code_tip') {
            	unset($config[$k]);
            	continue;
            }
            if (!(defined('OPEN_INVITE_CODE') && OPEN_INVITE_CODE) && $v['code']=='invite_ratio') {
            	unset($config[$k]);
            	continue;
            }

            if($v['code']=='full_group_id'|| $v['code']=='on_line_group_id'){
                unset($config[$k]);
                continue;
            }
			//付费 按时
            if(intval(LIVE_PAY_TIME)==0){
                if($v['code']=='live_pay_max' || $v['code']=='live_pay_min'|| $v['code']=='live_count_down'|| $v['code']=='live_pay_rule'|| $v['code']=='live_pay_fee'|| $v['code']=='is_only_play_video'|| $v['code']=='live_pay_num'|| $v['code']=='countdown'){
                    unset($config[$k]);
                    continue;
                }
            }
			//付费 按场
            if(intval(LIVE_PAY_SCENE)==0){
                if($v['code']=='live_pay_scene_max' || $v['code']=='live_pay_scene_min'){
                    unset($config[$k]);
                    continue;
                }
            }
			//后台短信验证
			if(intval(OPEN_CHECK_ACCOUNT)==0){
                if($v['code']=='account_ip' || $v['code']=='account_mobile'){
                    unset($config[$k]);
                    continue;
                }
            }
			//多支付宝账号功能
			if(intval(MORE_ALIPAY)==0){
				if($v['code']=='alipay_cache_time'){
					unset($config[$k]);
					continue;
				}
			}
            
			//隐藏客户端不许自义美颜度
 			if($v['code']=='beauty_close'){
        		unset($config[$k]);
        		continue;
        	}

        	/*if($v['code']=='mic_max_num'){
        		unset($config[$k]);
        		continue;
        	}*/

        	//隐藏直播类型: 默认为 云直播 video_type=1
            /*if ($v['code'] == 'video_type' || $v['group_id'] == '方维云') {
        	    if(!defined('OPEN_FWYUN') || OPEN_FWYUN != 1){
                    unset($config[$k]);
                    continue;
                }
            }*/
            
            //隐藏注册礼物配置
            if ($v['code'] == 'register_gift' || $v['code'] == 'register_gift_diamonds'|| $v['code'] == 'register_gift_coins') {
            	if(!defined('OPEN_REGISTER_GIFT') || OPEN_REGISTER_GIFT != 1){
            		unset($config[$k]);
            		continue;
            	}
            }
			//移除苹果价格是否与其他支付一致
        	if($v['code']=='iap_recharge'){
				unset($config[$k]);
				continue;
			}
        	if($v['type']==2){
        		$v['val'] =get_spec_image($v['val']);
        	}
        	
			if($v['type']==4){
				$v['value_scope']=explode(',',$v['value_scope']);
				$v['title_scope']=explode(',',$v['title_scope']);
 			}else{
 				$v['value_scope']='';
 			}
 			$conf[$v['group_id']][] = $v;
 			
 			
 			
		}
		/*if(!empty($wx_appid)&&!empty($wx_secrit)){
			require APP_ROOT_PATH."system/utils/weixin.php";
			$weixin=new weixin($wx_appid,$wx_secrit,get_domain().APP_ROOT."/wap");
			$wx_url=$weixin->scope_get_code();
 		}
 		$this->assign('wx_url',$wx_url);*/
  		
  		$is_limit_time_h = array("01","02","03","04","05","06","07","08","09","10","11","12","13","14","15","16","17","18","19","20","21","22","23","24");
  		$this->assign("is_limit_time_h",$is_limit_time_h);
  		$this->assign("conf",$conf);
  		$this->assign("domain_url",get_domain());
		$this->display();
	}
	
	public function savemobile()
	{
		
		
		foreach($_POST as $k=>$v)
		{
			
           /* if($k=='app_logo'&&$v!=''){
                $index = strpos($v,"/public/");
                $v = ".".substr($v,$index);
                syn_to_remote_image_server($v);
            }*/
            if($k=='rank_day'||$k=='rank_month'||$k=='rank_all'||$k=='rank_day_user'){
            	$result = $this->check_rank_time(array('name'=>$k,'value'=>strim($v)));
            	if($result['status']==0){
            		$this->error($result['error']);
            	}
            }
            if((defined('OPEN_LIVE_PAY')&&OPEN_LIVE_PAY==1)&&($k=='live_pay_num'||$k=='live_pay_rule'||$k=='live_pay_fee'||$k=='live_count_down'||$k=='ticket_to_rate'||$k=='uesddiamonds_to_score')){
            	if(floor($v)!=$v){
            		$this->error("付费直播的参数必须为整数");
            	}
            }
            if((defined('OPEN_LIVE_PAY')&&OPEN_LIVE_PAY==1)&&($k=='live_pay_max'||$k=='live_pay_min'||$k=='live_pay_scene_max'||$k=='live_pay_scene_min')){
            	if(floor($v)!=$v){
            		$this->error("付费直播的收费参数必须为整数");
            	}
            }



            if(defined('OPEN_LIVE_PAY')&&OPEN_LIVE_PAY==1){
                if(intval(LIVE_PAY_TIME)==1){
                    if(intval($_POST['live_pay_min'])<1){
                        $this->error("按时收费最低收费参数不能小于1");
                    }
                    if(intval($_POST['live_pay_max'])<intval($_POST['live_pay_min'])){
                        $this->error("按时收费最低收费参数必须小于按时收费最高收费参数");
                    }
                }
                if(intval(LIVE_PAY_SCENE)==1){
                    if(intval($_POST['live_pay_scene_min'])<1){
                        $this->error("按场收费最低收费参数不能小于1");
                    }
                    if(intval($_POST['live_pay_scene_max'])<intval($_POST['live_pay_scene_min'])){
                        $this->error("按场收费最低收费参数必须小于按场收费最高收费参数");
                    }
                }
            }
            if($_POST['qcloud_secret_id']==''||$_POST['qcloud_secret_key']==''){
                if($_POST['has_dirty_words']==1){
                    $this->error("腾讯云API账号或腾讯云API密钥未填写，无法启用脏字库");
                }
            }






			$res = M("MConfig")->where("code='".$k."'")->setField("val",trim($v));
			if($res)
			{
				$this->addself_init_version();
			}

		}
		$tourist_chat = $_POST['tourist_chat'];
		if($tourist_chat != ''){
			require_once(APP_ROOT_PATH.'system/tim/TimApi.php');
			$api = createTimAPI();
			$id = 0;
			if($tourist_chat){
				$time = 0;
			}else{
				$time = 4294967295;
			}
			$ret = $api->set_no_speaking($id,$time);
			if($ret['ErrorCode']){
				$ret = $api->set_no_speaking($id,$time);
				if($ret['ErrorCode']) {
					$this->error("开启游客发言失败");
				}
			}
		}

		clear_auto_cache("m_config");
        $log_info = "手机端配置";
        save_log($log_info.L("UPDATE_SUCCESS"),1);
		$this->success("保存成功");
	}
	//脏字库
	public function dirty_words(){
        $this->check_Module();
		//调用tim
        require_once(APP_ROOT_PATH.'system/tim/TimApi.php');
        $api = createTimAPI();
        $ret = $api->openim_dirty_words_get();
		$vo = array();
		$vo['title'] = '脏字库';
		$vo['content'] = implode(',',$ret['DirtyWordsList']);
		$this->assign ( 'vo', $vo );
		$this->display ();
	}
	
	//保存脏字库
	public function save_dirty_words(){
        $this->check_Module();
		require_once(APP_ROOT_PATH.'system/tim/TimApi.php');
        $api = createTimAPI();
        
		$content = trim($_REQUEST['content']);
		//开始验证有效性
		if(!check_empty($content))
		{
			$this->error("脏字库内容不能为空");
		}			

		if(count(explode(',',$content))>50)
		{
			$this->error("脏字库词数量不能大于50");
		}
		
		if($this->arrayHasOnlyInts(explode(',',$content)))
		{
			$this->error("数字不能添加到脏字库");
		}

		$ret = $api->openim_dirty_words_get();
		$old_words =array();
		$old_words = $ret['DirtyWordsList'];

		$new_words = array();
		$new_words = explode(',',$content);

		//未修改
		if(count($old_words)==count($new_words)){
			$this->error("未修改脏字库");die;
		}
		//添加OR删除
		if(count($old_words)>count($new_words)){
			$dirty_words_list = array_values(array_diff($old_words,$new_words));
	        $ret = $api->openim_dirty_words_delete($dirty_words_list);
	        $act = '更新';
		}else{
			$dirty_words_list = array_values(array_diff($new_words,$old_words));
			$ret = $api->openim_dirty_words_add($dirty_words_list);
			$act = '追加';
		}
		
		$log_info = '脏字库';

		if($ret['ErrorCode']==0) {
			//成功提示
			save_log($log_info.$act.'成功！',1);
			$this->success($log_info.$act.'成功！');
		} else {
			//错误提示
			save_log($log_info.$act.'失败！',0);
			$this->error($log_info.$act.'失败！');
		}
	}
	//判断数组是否包含数字
	function arrayHasOnlyInts($array)
	{
        $this->check_Module();
	    foreach ($array as $value)
	    {
	   		if($value!="0"){
	   			$value = intval($value);
	   			if (is_int($value)&&$value>0) 
		        {
		             return true;
		        }
	   		}else{
	   			return true;
	   		}
	        
	    }
	    return false;
	}
	//检查排行榜缓存时间
	function check_rank_time($data){
		$result = array('status'=>1,'error'=>'');
		//日榜
		if($data['name']=='rank_day'&&$data['value'] <1800){
			$result['status'] = 0;
			$result['error'] = '总排行日榜缓存时间,不得低1800秒';		
		}
		//总排行月榜
		if($data['name']=='rank_month'&&$data['value'] <28800){
			$result['status'] = 0;
			$result['error'] = '总排行月榜缓存时间,不得低28800秒';		
		}
		//总排行总榜
		if($data['name']=='rank_all'&&$data['value'] <86400){
			$result['status'] = 0;
			$result['error'] = '总排行总榜缓存时间,不得低86400秒';
		}
		//主播日排行榜
		if($data['name']=='rank_day_user'&&$data['value'] <300){
			$result['status'] = 0;
			$result['error'] = '主播日排行榜缓存时间,不得低300秒';		
		}
		return $result;
	}
	function   addself_init_version()
	{
		//更新初始化版本号
		$sql = "update ".DB_PREFIX."m_config set val = val + 1 where code = 'init_version'";
		$GLOBALS['db']->query($sql);
	}
}
?>