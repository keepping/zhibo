<?php
// +----------------------------------------------------------------------
// | Fanwe 方维众筹商业系统
// +----------------------------------------------------------------------
// | Copyright (c) 2011 http://www.fanwe.com All rights reserved.
// +----------------------------------------------------------------------
// | Author: 甘味人生(526130@qq.com)
// +----------------------------------------------------------------------

class IndexAction extends AuthAction{
	//首页
    public function index(){
    	//管理员的SESSION
		$adm_session = es_session::get(md5(conf("AUTH_KEY")));
		$adm_name = $adm_session['adm_name'];
		$adm_id = intval($adm_session['adm_id']);
    	
    	if(intval(app_conf('EXPIRED_TIME'))>0&&$adm_id!=0){
			
			$admin_logined_time = intval($adm_session['admin_logined_time']);
			$max_time = intval(conf('EXPIRED_TIME'))*60;
			if(NOW_TIME-$admin_logined_time>=$max_time)
		{
 				es_session::delete((md5(conf("AUTH_KEY"))));
				$this->display();
			}
		}
		
    	if($adm_id == 0)
		{
			//已登录
			$this->redirect(u("Public/login"));			
		}else{
			$this->display();
		}
		
    }
    

    //框架头
	public function top()
	{
		$adm_session = es_session::get(md5(conf("AUTH_KEY")));
		$role_id = intval($adm_session['role_id']);
 		$navs= get_admin_nav($role_id,$adm_session['adm_name']);
		$this->assign("navs",$navs);

		$this->assign("admin",$adm_session);
		$this->display();
	}
	//框架左侧
	public function left()
	{

		$adm_session = es_session::get(md5(conf("AUTH_KEY")));
		$adm_id = intval($adm_session['adm_id']);
		$role_id = intval($adm_session['role_id']);
		$navs= get_admin_nav($role_id,$adm_session['adm_name']);
		$nav_key = strim($_REQUEST['key']);
		
 		$nav_group = $navs[$nav_key]['groups'];

 		$this->assign("menus",$nav_group);
		$this->display();
	}
	//默认框架主区域

	public function main()
	{
		$adm_session = es_session::get(md5(conf("AUTH_KEY")));
		$this->assign("adm_session",$adm_session);
		$adm_id = intval($adm_session['adm_id']);
		$login_time = $GLOBALS['db']->getOne("SELECT login_time FROM ".DB_PREFIX."admin where id = $adm_id ");
		$h=to_date($login_time,"H");
		$login_time = to_date($login_time);
		$this->assign("login_time",$login_time);
		if($h<12){
			$greet ="上午好";
		}elseif($h<18){
			$greet ="下午好";
		}else{
			$greet ="晚上好";
		}
		$this->assign("greet",$greet);

		$navs = require_once APP_ROOT_PATH."system/admnav_cfg.php";		
    
		$this->assign("navs",$navs);
		
		//待审核
		$register_count = M("User")->where("is_effect=0 and user_type=0 ")->count();
		$company_register_count = M("User")->where("is_effect=0  and user_type=1")->count();
		$this->assign("register_count",$register_count);
		$this->assign("company_register_count",$company_register_count);

		//认证
		$user_authentication=M("User")->where("is_authentication = 1 and is_effect=1 and is_robot = 0 ")->count();
		//$business_authentication=M("User")->where("is_authentication = 1 and is_effect=1 and user_type=1 ")->count();
		//$all_authentication=M("User")->where(" (user_type=0 or user_type=1) and is_authentication =1 and is_effect=1 and is_robot = 0")->count();
        //认证未通过
        $authentication_not_allow=M("User")->where("is_authentication = 3 and is_effect=1 and  is_robot = 0 ")->count();
		$this->assign("user_authentica",$user_authentication);
		//$this->assign("business_authentica",$business_authentication);
		//$this->assign("all_authentica",$all_authentication);
        $this->assign("authentication_not_allow",$authentication_not_allow);
		
		//充值
		$pay_count = floatval($GLOBALS['db']->getOne("SELECT count(*) FROM ".DB_PREFIX."payment_notice where  payment_id <>0"));
		$this->assign("pay_count",$pay_count);		
		
		//普通用户
		$user_level=M("User")->where("is_authentication<>2 and is_effect=1 and is_robot = 0")->count();
		$this->assign("user_level",$user_level);
		//认证用户
		$authentication=M("User")->where("is_authentication=2 and is_effect=1 and is_robot = 0")->count();
		$this->assign("authentication",$authentication);
        //机器人
        $robot=M("User")->where("is_effect=1 and is_robot = 1")->count();
        $this->assign("robot",$robot);
		//会员总数
		$user_count=M("User")->where("is_effect=1")->count();
		$this->assign("user_count",$user_count);
		

		$is_live=M("Video")->where("live_in=1 or live_in = 3")->count(); //直播中
		$this->assign("is_live",$is_live);
		$is_playback=M("VideoHistory")->where("is_delete=0 and is_del_vod = 0 and room_type<>1")->count(); //保存的视频
		$this->assign("is_playback",intval($is_playback));
		//直播中视频观看人数
		$watch_number = intval($GLOBALS['db']->getOne("SELECT sum(watch_number) FROM ".DB_PREFIX."video where live_in=1"));
        $this->assign("watch_number",$watch_number);
        //统计在线人数
        $m_config =  load_auto_cache("m_config");
        require_once(APP_ROOT_PATH . 'system/tim/TimApi.php');
        $api = createTimAPI();
        $show_online_user = 0;
        if($m_config['tim_identifier']&&!is_array($api)){
            $ret = $api->group_get_group_member_info($m_config['on_line_group_id'],0,0);
            $online_user = isset($ret['MemberNum'])?intval($ret['MemberNum']):0;//减去管理员本身
            $show_online_user = 1;
            $this->assign("online_user",$online_user);
        }
        $this->assign("show_online_user",$show_online_user);
		$this->display();
	}	
	//底部
	public function footer()
	{
		$this->display();
	}
	
	//修改管理员密码
	public function change_password()
	{
		$adm_session = es_session::get(md5(conf("AUTH_KEY")));
		$this->assign("adm_data",$adm_session);
		$this->display();
	}
	public function do_change_password()
	{
		$adm_id = intval($_REQUEST['adm_id']);
		if(!check_empty($_REQUEST['adm_password']))
		{
			$this->error(L("ADM_PASSWORD_EMPTY_TIP"));
		}
		if(!check_empty($_REQUEST['adm_new_password']))
		{
			$this->error(L("ADM_NEW_PASSWORD_EMPTY_TIP"));
		}
		if($_REQUEST['adm_confirm_password']!=$_REQUEST['adm_new_password'])
		{
			$this->error(L("ADM_NEW_PASSWORD_NOT_MATCH_TIP"));
		}		
		if(M("Admin")->where("id=".$adm_id)->getField("adm_password")!=md5($_REQUEST['adm_password']))
		{
			$this->error(L("ADM_PASSWORD_ERROR"));
		}
		M("Admin")->where("id=".$adm_id)->setField("adm_password",md5($_REQUEST['adm_new_password']));
		save_log(M("Admin")->where("id=".$adm_id)->getField("adm_name").L("CHANGE_SUCCESS"),1);
		$this->success(L("CHANGE_SUCCESS"));
		
		
	}
	
	public function reset_sending()
	{
		$field = trim($_REQUEST['field']);
		if($field=='DEAL_MSG_LOCK'||$field=='PROMOTE_MSG_LOCK'||$field=='APNS_MSG_LOCK')
		{
			M("Conf")->where("name='".$field."'")->setField("value",'0');
			$this->success(L("RESET_SUCCESS"),1);
		}
		else
		{
			$this->error(L("INVALID_OPERATION"),1);
		}
	}

	/*
	 * 网站数据统计
	*/
	public function statistics(){
		
		//$user_count=M("User")->where("is_robot=0")->count();
        $user_count=M("User")->count();
		$no_effect=M("User")->where(" is_effect=0 or is_effect=2")->count(); //无效
		$is_effect=M("User")->where(" is_effect=1")->count(); //有效

		//认证
		$user_authentication=M("User")->where("is_authentication = 2 and user_type=0  and is_effect=1 and is_robot = 0")->count();
		$business_authentication=M("User")->where("is_authentication = 2 and user_type=1 and is_effect=1 and is_robot = 0")->count();
		$all_authentication=M("User")->where(" (user_type=0 or user_type=1) and is_authentication =2 and is_effect=1 and is_robot = 0")->count();
		
		//资金进出
		//线上充值
		$online_pay = floatval($GLOBALS['db']->getOne("SELECT sum(money) FROM ".DB_PREFIX."payment_notice where is_paid = 1 and payment_id>0  "));
		$this->assign("online_pay",$online_pay);
		//总计
		$total_usre_money = $online_pay;
		$this->assign("total_usre_money",$total_usre_money);

		
		$this->assign("user_count",$user_count);
		$this->assign("no_effect",$no_effect);
		$this->assign("is_effect",$is_effect);
		$this->assign("user_authentication",$user_authentication);
		$this->assign("business_authentication",$business_authentication);
		$this->assign("all_authentication",$all_authentication);
		$this->display();
	}

	public function main_weibo()
	{
		$adm_session = es_session::get(md5(conf("AUTH_KEY")));
		$this->assign("adm_session",$adm_session);
		$adm_id = intval($adm_session['adm_id']);
		$login_time = $GLOBALS['db']->getOne("SELECT login_time FROM ".DB_PREFIX."admin where id = $adm_id ");
		$h=to_date($login_time,"H");
		$login_time = to_date($login_time);
		$this->assign("login_time",$login_time);
		if($h<12){
			$greet ="上午好";
		}elseif($h<18){
			$greet ="下午好";
		}else{
			$greet ="晚上好";
		}
		$this->assign("greet",$greet);

		$navs = require_once APP_ROOT_PATH."system/admnav_cfg.php";

		$this->assign("navs",$navs);

		//待审核
		$register_count = M("User")->where("is_effect=0 and user_type=0 ")->count();
		$company_register_count = M("User")->where("is_effect=0  and user_type=1")->count();
		$this->assign("register_count",$register_count);
		$this->assign("company_register_count",$company_register_count);

		//认证
		$user_authentication=M("User")->where("is_authentication = 1 and is_effect=1 and user_type=0 and is_robot = 0 ")->count();
		$business_authentication=M("User")->where("is_authentication = 1 and is_effect=1 and user_type=1 ")->count();
		$all_authentication=M("User")->where(" (user_type=0 or user_type=1) and is_authentication =1 and is_effect=1 and is_robot = 0")->count();
		//认证未通过
		$authentication_not_allow=M("User")->where("is_authentication = 3 and is_effect=1 and user_type=0 and is_robot = 0 ")->count();
		$this->assign("user_authentica",$user_authentication);
		$this->assign("business_authentica",$business_authentication);
		$this->assign("all_authentica",$all_authentication);
		$this->assign("authentication_not_allow",$authentication_not_allow);

		//充值
		$pay_count = floatval($GLOBALS['db']->getOne("SELECT count(*) FROM ".DB_PREFIX."payment_notice where  payment_id <>0"));
		$this->assign("pay_count",$pay_count);

		//普通用户
		$user_level=M("User")->where("is_authentication<>2 and is_effect=1 and is_robot = 0")->count();
		$this->assign("user_level",$user_level);
		//认证用户
		$authentication=M("User")->where("is_authentication=2 and is_effect=1 and is_robot = 0")->count();
		$this->assign("authentication",$authentication);

		//会员总数
		$user_count=M("User")->where("is_effect=1")->count();
		$this->assign("user_count",$user_count);


		$this->display();
	}
}
?>