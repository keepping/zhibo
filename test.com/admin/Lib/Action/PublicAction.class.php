<?php
// +----------------------------------------------------------------------
// | Fanwe 方维众筹商业系统
// +----------------------------------------------------------------------
// | Copyright (c) 2011 http://www.fanwe.com All rights reserved.
// +----------------------------------------------------------------------
// | Author: 甘味人生(526130@qq.com)
// +----------------------------------------------------------------------

//开放的公共类，不需RABC验证
class PublicAction extends BaseAction{
	public function login()
	{		
		//验证是否已登录
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
		
 		
		if($adm_id != 0)
		{
			//已登录
			$this->redirect(u("Index/index"));			
		}
		else
		{
			$m_config =  load_auto_cache("m_config");
			$account_mobile = (trim($m_config['account_mobile']));

			$this->assign('account_mobile',hideMobile($account_mobile));
			$open_check_account = intval(OPEN_CHECK_ACCOUNT);
			$check_ip_info = $this->check_account_ip(1);
			if(trim($m_config['account_mobile'])==''||$check_ip_info['status']==1){
				$open_check_account = 0;
			}
			$this->assign('open_check_account', $open_check_account);
			$this->display();
		}
	}
	public function verify()
	{	
        Image::buildImageVerify(4,1);
    }
    
    //登录函数
    public function do_login()
    {		
    	$adm_name = trim($_REQUEST['adm_name']);
    	$adm_password = trim($_REQUEST['adm_password']);
    	$ajax = intval($_REQUEST['ajax']);  //是否ajax提交
    	

    	if($adm_name == '')
    	{
    		$this->error(L('ADM_NAME_EMPTY',$ajax));
    	}
    	if($adm_password == '')
    	{
    		$this->error(L('ADM_PASSWORD_EMPTY',$ajax));
    	}
      	if(es_session::get("verify") != md5($_REQUEST['adm_verify'])) {
			$this->error(L('ADM_VERIFY_ERROR'),$ajax);
		}
		//检查手机验证码
		$m_config =  load_auto_cache("m_config");
		$open_check_account = intval(OPEN_CHECK_ACCOUNT);
		$check_ip_info = $this->check_account_ip(1);
		if(trim($m_config['account_mobile'])==''||$check_ip_info['status']==1){
			$open_check_account = 0;
		}
       if($open_check_account)
	   {
		   $verify_code = trim($_REQUEST['mobile_verify']);
		   $account_mobile =  trim($m_config['account_mobile']);
		   if($GLOBALS['db']->getOne("SELECT count(*) FROM ".DB_PREFIX."mobile_verify_code WHERE mobile=".$account_mobile." AND verify_code='".$verify_code."'")==0){
			   $this->error('手机验证码出错',$ajax);
		   }
	   }

		$condition['adm_name'] = $adm_name;
		$condition['is_effect'] = 1;
		$condition['is_delete'] = 0;
		$adm_data = M("Admin")->where($condition)->find();
		if($adm_data) //有用户名的用户
		{
			if($adm_data['adm_password']!=md5($adm_password))
			{
				save_log($adm_name.L("ADM_PASSWORD_ERROR"),0); //记录密码登录错误的LOG
				$this->error(L("ADM_PASSWORD_ERROR"),$ajax);
			}
			else
			{
				//登录成功
				$adm_session['adm_name'] = $adm_data['adm_name'];
				$adm_session['adm_id'] = $adm_data['id'];
				$adm_session['role_id'] = $adm_data['role_id'];
				$adm_session['admin_logined_time'] = NOW_TIME;
				if(trim($_REQUEST['adm_dog_key'])){
					$adm_session['adm_dog_key'] = trim($_REQUEST['adm_dog_key']);
				}
				
				
				es_session::set(md5(conf("AUTH_KEY")),$adm_session);
				//es_session::set("user_logined_time",NOW_TIME);
				//重新保存记录
				$adm_data['login_ip'] = get_client_ip();
				$adm_data['login_time'] = get_gmtime();
				M("Admin")->save($adm_data);
				save_log($adm_data['adm_name'].L("LOGIN_SUCCESS"),1);
				$this->success(L("LOGIN_SUCCESS"),$ajax);
			}
		}
		else
		{
			save_log($adm_name.L("ADM_NAME_ERROR"),0); //记录用户名登录错误的LOG
			$this->error(L("ADM_NAME_ERROR"),$ajax);
		}
    }
	
    //登出函数
	public function do_loginout()
	{
	//验证是否已登录
		//管理员的SESSION
		$adm_session = es_session::get(md5(conf("AUTH_KEY")));
		$adm_id = intval($adm_session['adm_id']);
		
		if($adm_id == 0)
		{
			//已登录
			$this->redirect(u("Public/login"));			
		}
		else
		{
			es_session::delete(md5(conf("AUTH_KEY")));
			$this->assign("jumpUrl",U("Public/login"));
			$this->assign("waitSecond",3);
			$this->success(L("LOGINOUT_SUCCESS"));
		}
	}
	//检查登录账号
	public function check_account(){
		$result =  array('status'=>0,'error'=>'');
		$adm_name = trim($_REQUEST['adm_name']);
		$adm_password = trim($_REQUEST['adm_password']);

		if($adm_name == '')
		{
			$result['error'] = L('ADM_NAME_EMPTY');
			admin_ajax_return($result);
		}
		if($adm_password == '')
		{
			$result['error'] = L('ADM_PASSWORD_EMPTY');
			admin_ajax_return($result);
		}

		$condition['adm_name'] = $adm_name;
		$condition['is_effect'] = 1;
		$condition['is_delete'] = 0;
		$adm_data = M("Admin")->where($condition)->find();
		if($adm_data) //有用户名的用户
		{
			if($adm_data['adm_password']!=md5($adm_password))
			{
				$result['error'] = L('ADM_PASSWORD_ERROR');
				admin_ajax_return($result);
			}
			else
			{
				$result['status'] =1;
				admin_ajax_return($result);
			}
		}
		else
		{
			$result['error'] = L('ADM_NAME_ERROR');
			admin_ajax_return($result);
		}
	}
	//检查登录IP
	public function check_account_ip($type=0)
	{
		$result =  array('status'=>0,'error'=>'');
		$m_config =  load_auto_cache("m_config");
		$ip = get_client_ip();
		//备用域名 列表
		$account_ip = array();
		$account_ip_arr = explode("<br />",nl2br($m_config['account_ip']));
		foreach($account_ip_arr as $k=>$v){
			$v = ltrim(rtrim(trim($v)));
			if($v!=''){
				$account_ip[]=$v;
			}
		}
		if(in_array($ip,$account_ip)&&count($m_config['account_ip'])>0){
			$result['status'] = 1;
		}
		if(intval($type)==0){
			admin_ajax_return($result);
		}else{
			return $result;
		}
	}
	//发送短信
	public function send_account_verify()
	{
		$result =  array('status'=>0,'error'=>'');
		$m_config =  load_auto_cache("m_config");
		$mobile = addslashes(htmlspecialchars(trim($m_config['account_mobile'])));
		if(app_conf("SMS_ON")==0)
		{
			$result['status'] = 0;
			$result['error'] = "短信未开启";
			admin_ajax_return($result);
		}
		//添加：手机发送 防护
		$result = check_sms_send($mobile);

		if ($result['status'] == 0){
			$result['time'] = 0;
			admin_ajax_return($result);
		}

		if($GLOBALS['db']->getOne("select count(*) from ".DB_PREFIX."mobile_verify_code where mobile = '".$mobile."' and client_ip='".get_client_ip()."' and create_time>=".(get_gmtime()-60)." ORDER BY id DESC") > 0)
		{
			$result['status'] = 0;
			$result['error'] = "发送速度太快了";
			admin_ajax_return($result);
		}
		$n_time=get_gmtime()-300;
		//删除超过5分钟的验证码
		$GLOBALS['db']->query("DELETE FROM ".DB_PREFIX."mobile_verify_code WHERE create_time <=".$n_time);
		//开始生成手机验证

		$code = rand(1000,9999);
		$GLOBALS['db']->autoExecute(DB_PREFIX."mobile_verify_code",array("verify_code"=>$code,"mobile"=>$mobile,"create_time"=>get_gmtime(),"client_ip"=>get_client_ip()),"INSERT");

		send_verify_sms($mobile,$code);
		$status = $GLOBALS['db']->getRow("select * from ".DB_PREFIX."deal_msg_list where dest = '".$mobile."' and code='".$code."'");

		if($status['is_success']){
			$result['status'] = 1;
			$result['time'] = 60;
			$result['error'] = $status['title'].$status['result'];
		}else{
			$result['status'] = 0;
			$result['time'] = 0;
			$result['error'] = "短信验证码发送失败";
		}
		admin_ajax_return($result);
	}
}
?>