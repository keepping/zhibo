<?php

class EduUserAuditAction extends CommonAction{

   public function __construct()
	{	
		parent::__construct();
		require_once APP_ROOT_PATH."/system/libs/user.php";
		//会员银行
		$user_id = intval($_REQUEST['user_id']);
		$username = M("user")->where("id=".$user_id)->getField("user_name");
		$this->assign("username", $username);
		$this->assign("user_id", $user_id);
	}
	public function index()
	{
		$now=get_gmtime();
		if(trim($_REQUEST['nick_name'])!='')
		{
			$map[DB_PREFIX.'user.nick_name'] = array('like','%'.trim($_REQUEST['nick_name']).'%');
		}
		if(trim($_REQUEST['email'])!='')
		{
			$map[DB_PREFIX.'user.email'] = array('like','%'.trim($_REQUEST['email']).'%');
		}
		if(trim($_REQUEST['contact'])!='')
		{
			$map[DB_PREFIX.'user.contact'] = array('like','%'.trim($_REQUEST['contact']).'%');
		}
		 
		$create_time_2=empty($_REQUEST['create_time_2'])?to_date($now,'Y-m-d'):strim($_REQUEST['create_time_2']);
		$create_time_2=to_timespan($create_time_2)+24*3600;
		if(trim($_REQUEST['create_time_1'])!='' )
		{
			$map[DB_PREFIX.'user.create_time'] = array('between',array(to_timespan($_REQUEST['create_time_1']),$create_time_2));
		}
		if(intval($_REQUEST['id'])>0)
		{
			$map[DB_PREFIX.'user.id'] = intval($_REQUEST['id']);
		}		
		
		$map[DB_PREFIX.'user.is_effect'] = 0;
		
//		$map['_string']=" member_type = 1 ";

		if (method_exists ( $this, '_filter' )) {
			$this->_filter ( $map );
		}
		
		//print_r($map);exit;
		$model = D ('User');
		if (! empty ( $model )) {
			$this->_list ( $model, $map );
		}
		$this->display ();
	}
	
	public function edit() {		
		$id = intval($_REQUEST ['id']);
		$condition['id'] = $id;		
		$vo = M('User')->where($condition)->find();
		$this->assign ( 'vo', $vo );
		$region_pid = 0;
		$region_lv2 = $GLOBALS['db']->getAll("select * from ".DB_PREFIX."region_conf where region_level = 2 order by py asc");  //二级地址
		foreach($region_lv2 as $k=>$v)
		{
			if($v['name'] == $vo['province'])
			{
				$region_lv2[$k]['selected'] = 1;
				$region_pid = $region_lv2[$k]['id'];
				break;
			}
		}
		$this->assign("region_lv2",$region_lv2);
		
		
		if($region_pid>0)
		{
			$region_lv3 = $GLOBALS['db']->getAll("select * from ".DB_PREFIX."region_conf where pid = ".$region_pid." order by py asc");  //三级地址
			foreach($region_lv3 as $k=>$v)
			{
				if($v['name'] == $vo['city'])
				{
					$region_lv3[$k]['selected'] = 1;
					break;
				}
			}
			$this->assign("region_lv3",$region_lv3);
		}
		//会员等级信息
		$user_level = $GLOBALS['db']->getAll("select * from ".DB_PREFIX."user_level order by level ASC");
		$this->assign("user_level",$user_level);
        //认证类型
        $authent_list = M("AuthentList")->findAll();
        $this->assign("authent_list",$authent_list);
		
		$this->display ();
	}
		

	public function delete() {
		//彻底删除指定记录
		$ajax = intval($_REQUEST['ajax']);
		$id = $_REQUEST ['id'];
		if (isset ( $id )) {
				$condition = array ('id' => array ('in', explode ( ',', $id ) ) );
				$rel_data = M('User')->where($condition)->findAll();				
				foreach($rel_data as $data)
				{
					$info[] = $data['nick_name'];
				}
				if($info) $info = implode(",",$info);
				$ids = explode ( ',', $id );
				foreach($ids as $uid)
				{
					delete_user($uid);
				}
				save_log($info.l("FOREVER_DELETE_SUCCESS"),1);
				$this->success (l("FOREVER_DELETE_SUCCESS"),$ajax);
				
			} else {
				$this->error (l("INVALID_OPERATION"),$ajax);
		}
	}
	
		
	
	public function update() {
		B('FilterString');
		$data = M('User')->create ();
		
		$log_info = M('User')->where("id=".intval($data['id']))->getField("nick_name");
		//开始验证有效性
		$this->assign("jumpUrl",u('UserAudit'."/edit",array("id"=>$data['id'])));
		/*if(!check_empty($data['user_pwd'])&&$data['user_pwd']!=$_REQUEST['user_confirm_pwd'])
		{
			$this->error(L("USER_PWD_CONFIRM_ERROR"));
		}
		if($data['is_investor'] ==0){
			$_REQUEST['member_type']=0;
		}
		elseif($data['is_investor']==1 || $data['is_investor']==3 || $data['is_investor']==7){
			$_REQUEST['member_type']=1;
		}else{
			$_REQUEST['member_type']=2;
		}*/
		//app和admin共用user.php的save_user方法，后台update是没有验证码的，所以save_user设置标示字段$update_status
		$user_info=$GLOBALS['db']->getRow("select * from ".DB_PREFIX."user where id = ".intval($_REQUEST['id']));
		$user_info = array_merge($user_info,$_REQUEST);
  		$res = save_user($user_info,'UPDATE',$update_status=1);
		if($res['status']==0)
		{
			$error_field = $res['data'];
			if($error_field['error'] == EMPTY_ERROR)
			{
				if($error_field['field_name'] == 'user_name')
				{
					$this->error(L("USER_NAME_EMPTY_TIP"));
				}
				elseif($error_field['field_name'] == 'email')
				{
					$this->error(L("USER_EMAIL_EMPTY_TIP"));
				}
				else
				{
					$this->error(sprintf(L("USER_EMPTY_ERROR"),$error_field['field_show_name']));
				}
			}
			if($error_field['error'] == FORMAT_ERROR)
			{
				if($error_field['field_name'] == 'email')
				{
					$this->error(L("USER_EMAIL_FORMAT_TIP"));
				}
				if($error_field['field_name'] == 'mobile')
				{
					$this->error(L("USER_MOBILE_FORMAT_TIP"));
				}
			}
			
			if($error_field['error'] == EXIST_ERROR)
			{
				if($error_field['field_name'] == 'user_name')
				{
					$this->error(L("USER_NAME_EXIST_TIP"));
				}
				if($error_field['field_name'] == 'email')
				{
					$this->error(L("USER_EMAIL_EXIST_TIP"));
				}
				if($error_field['field_name'] == 'mobile')
				{
					$this->error(L("USER_MOBILE_EXIST_TIP"));
				}
			}
		}
 		
		//开始更新is_effect状态
		M("User")->where("id=".intval($_REQUEST['id']))->setField("is_effect",intval($_REQUEST['is_effect']));
		$user_id = intval($_REQUEST['id']);		
		
		save_log($log_info.L("UPDATE_SUCCESS"),1);
		$this->success(L("UPDATE_SUCCESS"));
		
	}

	public function set_effect()
	{
		$id = intval($_REQUEST['id']);
		$ajax = intval($_REQUEST['ajax']);
		$user_info = M('User')->getById($id);
		$c_is_effect = M('User')->where("id=".$id)->getField("is_effect");  //当前状态
		$n_is_effect = $c_is_effect == 0 ? 1 : 0; //需设置的状态
		$result=M('User')->where("id=".$id)->setField("is_effect",$n_is_effect);
		if($result && $c_is_effect==0 && $user_info['is_send_referrals']==1 && $user_info['pid'] >0)
		{
			send_referrals($user_info);//发入返利给推荐人
		}	
		save_log($user_info['nick_name'].l("SET_EFFECT_".$n_is_effect),1);
		$this->ajaxReturn($n_is_effect,l("SET_EFFECT_".$n_is_effect),1);	
	}

}
?>