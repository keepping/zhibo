<?php
// +----------------------------------------------------------------------
// | Fanwe 方维众筹商业系统
// +----------------------------------------------------------------------
// | Copyright (c) 2011 http://www.fanwe.com All rights reserved.
// +----------------------------------------------------------------------
// | Author: 甘味人生(526130@qq.com)
// +----------------------------------------------------------------------

//相同操作

class UserCommon extends CommonAction{
    public function __construct()
    {
        parent::__construct();
        require_once APP_ROOT_PATH."/system/libs/user.php";
    }

	public function index($date)
	{
     
        if(trim($date['nick_name'])!='')
        {
            $parameter.= "nick_name like " . urlencode ( '%'.trim($date['nick_name']).'%' ) . "&";
			$sql_w .= "nick_name like '%".trim($date['nick_name'])."%' and ";
        }
        if(trim($date['mobile'])!='')
        {
            $parameter.= "mobile like " . urlencode ( '%'.trim($date['mobile']).'%' ) . "&";
            $sql_w .= "mobile like '%".trim($date['mobile'])."%' and ";
        }
		$create_time_2=empty($_REQUEST['create_time_2'])?to_date(get_gmtime(),'Y-m-d'):strim($date['create_time_2']);
		$create_time_2=to_timespan($create_time_2)+24*3600;
		if(trim($date['create_time_1'])!='' )
		{
			$parameter.="create_time between '". to_timespan($date['create_time_1']) . "' and '". $create_time_2 ."'&";
			$sql_w .=" (create_time between '". to_timespan($date['create_time_1']). "' and '". $create_time_2 ."' ) and ";
		}
        if(intval($date['id'])>0)
        {
            $parameter.= "id=" . intval($date['id']). "&";
			$sql_w .= "id=".intval($date['id'])." and ";
        }

        if($date['is_effect']!=NULL){
            $parameter.= "is_effect=" . intval($date['is_effect']). "&";
			$sql_w .= "is_effect=".intval($date['is_effect'])." and ";
        }else{
        	$parameter.= "is_effect=1&";
			$sql_w .= "is_effect=1 and ";
        }

        if($_REQUEST['is_admin']!='')
        {
            $parameter.= "is_admin=" . intval($date['is_admin']). "&";
            $sql_w .= "is_admin=".intval($date['is_admin'])." and ";
        }

        if (!isset($_REQUEST['is_authentication'])) {
            $_REQUEST['is_authentication'] = -1;
        }

        if($_REQUEST['is_authentication']!=-1) {
            if (isset($date['is_authentication'])) {

                $parameter .= "is_authentication in (" . $date['is_authentication'] . ")&";
                $sql_w .= "is_authentication in (" .  $date['is_authentication'] . ") and ";
            }

        }
        
        if(isset($date['is_robot'])){
            $parameter.= "is_robot=" . intval($date['is_robot']). "&";
			$sql_w .= "is_robot=".intval($date['is_robot'])." and ";
        }else{
            $parameter.= "is_robot=0&";
			$sql_w .= "is_robot=0 and ";
        }

        $m_config =  load_auto_cache("m_config");//初始化手机端配置
        if(defined('OPEN_VIP')&&OPEN_VIP==1){
            if($_REQUEST['is_vip']!='')
            {
                $parameter.= "is_vip=" . intval($date['is_vip']). "&";
                $sql_w .= "is_vip=".intval($date['is_vip'])." and ";
            }
            $this->assign ( 'open_vip', 1 );
        }

		$model = D ();

        $m_config = load_auto_cache("m_config");
        $ote = floatval($m_config['onlinetime_to_experience']);
		$sql_str = "SELECT *," .
		" ticket-refund_ticket as useable_ticket ,floor(score + online_time*".$ote.") as u_score" .
		" FROM ".DB_PREFIX."user WHERE 1=1 ";
        $count_sql = "SELECT count(*) as tpcount" .
            " FROM ".DB_PREFIX."user WHERE (1=1 ";

        if(intval($date['id']) == 0){
            $sql_str .= " and ".$sql_w." 1=1 ";
            $count_sql .= " and ".$sql_w." 1=1) ";
        }else{
            $sql_str .= " and ".$sql_w." 1=1 union SELECT *," .
                " ticket-refund_ticket as useable_ticket ,floor(score + online_time*".$ote.") as u_score" .
                " FROM ".DB_PREFIX."user WHERE luck_num=".$date['id'];
            $count_sql .= " and ".$sql_w." 1=1 ) or (luck_num=".$date['id'].")";
        }

        $distribution_log = 0;
        if(defined('OPEN_DISTRIBUTION') && OPEN_DISTRIBUTION==1){
            $distribution_log = 1;
        }
        $coins = 0;
        if(defined('OPEN_GAME_MODULE') && OPEN_GAME_MODULE==1){
            $coins = 1;
        }
        $goods = 0;
        if((defined('SHOPPING_GOODS') && SHOPPING_GOODS==1) || (defined('PAI_REAL_BTN') && PAI_REAL_BTN==1)){
            $goods = 1;
        }
		$voList = $this->_Sql_list($model, $sql_str, "&".$parameter,'id',0,$count_sql);
		foreach($voList as $k=>$v){
			$voList[$k]['head_image'] = get_spec_image($v['head_image']);
            $voList[$k]['distribution_log'] = $distribution_log;
            $voList[$k]['coins'] = $coins;
            $voList[$k]['goods'] = $goods;
		}
        log_result($sql_str);
		$this->assign ( 'list', $voList );
		$this->display ();
	}

    public function add(){

        $region_lv2 = $GLOBALS['db']->getAll("select * from ".DB_PREFIX."region_conf where region_level = 2 order by py asc");  //二级地址
        $this->assign("region_lv2",$region_lv2);

        //会员等级信息
        $user_level = $GLOBALS['db']->getAll("select * from ".DB_PREFIX."user_level order by level ASC");
        $this->assign("user_level",$user_level);

        //认证类型
        $authent_list = M("AuthentList")->findAll();
        $this->assign("authent_list",$authent_list);
        //分类
        $classifi_list = M("VideoClassified")->findAll();
        $this->assign("classifi_list",$classifi_list);
        $this->display ();
    }

    public function insert(){
        B('FilterString');
        $ajax = intval($_REQUEST['ajax']);
        $data = M(MODULE_NAME)->create ();

        //开始验证有效性
        $this->assign("jumpUrl",u(MODULE_NAME."/add"));
        if($_REQUEST['v_explain']==''){
        	$_REQUEST['v_explain'] = $_REQUEST['authentication_type'];
        }
        $_REQUEST['v_icon'] = get_spec_image(M('AuthentList')->where("name='".trim($_REQUEST['authentication_type']."'"))->getField("icon"));
       
        $_REQUEST['score'] =  $GLOBALS['db']->getOne("select score from ".DB_PREFIX."user_level where `level`=".$_REQUEST['user_level'],true,true);
        $res = save_user($_REQUEST,'INSERT',$update_status=1);
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
                elseif($error_field['field_name'] == 'mobile')
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
                elseif($error_field['field_name'] == 'email')
                {
                    $this->error(L("USER_EMAIL_EXIST_TIP"));
                }
                elseif($error_field['field_name'] == 'mobile')
                {
                    $this->error(L("USER_MOBILE_EXIST_TIP"));
                }
            }
        }
        $user_id = intval($res['user_id']);

        // 更新数据
        $log_info = $_REQUEST['nick_name'];
        save_log($log_info.L("INSERT_SUCCESS"),1);
        $this->success(L("INSERT_SUCCESS"));
    }

	public function edit($date) {
		
		$id = intval($date['id']);
        $condition['id'] = $id;
        $vo = M('User')->where($condition)->find();
        $vo['ban_time'] = $vo['ban_time']>0?to_date($vo['ban_time']):'';
        $vo['create_time'] = $vo['create_time']>0?to_date($vo['create_time']):'';
        $m_config =  load_auto_cache("m_config");//初始化手机端配置
        if(defined('OPEN_VIP')&&OPEN_VIP==1){
            $this->assign ( 'open_vip', 1 );
            $vip_expire_time = $vo['vip_expire_time'];
            $vo['vip_expire_time'] = '';
            if(intval($vo['is_vip'])==1){
                $vo['vip_expire_time'] = $vip_expire_time>0?to_date($vip_expire_time):'';
                if($vip_expire_time>0 && $vip_expire_time<NOW_TIME){
                    $vo['is_vip'] = 0;
                    $vo['vip_expire_time'] = '';
                    $sql = "update ".DB_PREFIX."user set is_vip = 0 where id = ".$id;
                    $GLOBALS['db']->query($sql);
                    user_deal_to_reids(array($id));
                }
            }
        }
        if(defined('OPEN_DISTRIBUTION') && OPEN_DISTRIBUTION == 1){
            $this->assign('open_distribution',1);
        }
        $user_level = $GLOBALS['db']->getOne("select user_level from ".DB_PREFIX."user where id = ".$id);  //二级地址

        $vo['user_level'] = $user_level>0?$user_level:1;
        //过滤头像是本地连接的问题
        $vo['head_image'] = get_spec_image($vo['head_image'],50,50);
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
		$user_level = $GLOBALS['db']->getAll("select * from ".DB_PREFIX."user_level where `level`>=".$user_level." order by level ASC");
		$this->assign("user_level",$user_level);

        //认证类型
        $authent_list = M("AuthentList")->findAll();
        $this->assign("authent_list",$authent_list);
        //分类
        $classifi_list = M("VideoClassified")->findAll();
        $this->assign("classified_id",$classifi_list);

        if (defined('OPEN_EDU_MODULE') && OPEN_EDU_MODULE == 1) {
            if ($vo['authentication_type'] == '教师') {
                $teacher = M('EduTeacher')->where(array('user_id' => $vo['id']))->find();
                $this->assign('teaching_certificate', get_spec_image($teacher['teaching_certificate']));
                $this->assign('education_certificate', get_spec_image($teacher['education_certificate']));
            } elseif ($vo['authentication_type'] == '机构') {
                $org = M('EduOrg')->where(array('user_id' => $vo['id']))->find();
                $this->assign('business_license', get_spec_image($org['business_license']));
            }
        }
		//是否显示身份证号码
        $show_identify_number = intval($m_config['is_show_identify_number']);
        $this->assign('show_identify_number',$show_identify_number);
		$this->display ();
	}
	//删除
	public function delete($data) {
		//彻底删除指定记录
		$ajax = intval($data['ajax']);
		$id = $data ['id'];
		if (isset ( $id )) {
				$condition = array ('id' => array ('in', explode ( ',', $id ) ) );
				$rel_data = M('User')->where($condition)->findAll();				
				foreach($rel_data as $data)
				{
					$info[] = $data['user_name'];	
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
		//开始验证有效性
		$this->assign("jumpUrl",u(MODULE_NAME."/edit",array("id"=>$data['id'])));
        $old_user_info = M('User')->where("id=".$data['id'])->find();

		//app和admin共用user.php的save_user方法，后台update是没有验证码的，所以save_user设置标示字段$update_status
		$user_info=$GLOBALS['db']->getRow("select * from ".DB_PREFIX."user where id = ".intval($data['id']));
        /*if($user_info['user_level'] != $data['user_level']){
            //require_once APP_ROOT_PATH.'mapi/lib/redis/BaseRedisService.php';
            //require_once(APP_ROOT_PATH.'mapi/lib/redis/UserRedisService.php');
            //$user_redis = new UserRedisService();
            $score = $GLOBALS['db']->getOne("select score from ".DB_PREFIX."user_level where `level` = ".$data['user_level']);
            //$online_time = $user_redis->getOne_db($data['id'],'online_time');
            //$online_time = intval($online_time)>0?intval($online_time):0;
            //$score = $score - floor($online_time/app_conf('ONLINETIME_TO_EXPERIENCE'));
            $user_info['score'] = $score;
        }*/
        if($user_info['login_type'] == 2 && empty($data['mobile'])){
            $this->error('手机注册用户，手机号不能为空');
        }else{
            $user_info['mobile'] = $data['mobile'];
        }
        $user_info['user_level'] = $data['user_level'];
        
        if($data['v_explain']==''){
        	$data['v_explain'] = $data['authentication_type'];
        }
       	$data['v_icon'] = get_spec_image(M('AuthentList')->where("name='".trim($data['authentication_type']."'"))->getField("icon"));
		$data['authent_list_id'] = get_spec_image(M('AuthentList')->where("name='".trim($data['authentication_type']."'"))->getField("id"));

		$user_info = array_merge($user_info,$data);
        if (defined('GAME_DISTRIBUTION') && GAME_DISTRIBUTION == 1) {
            if($user_info['id'] == $user_info['game_distribution_id']){
                $this->error('推荐人不能为自己');
            }
            if ($user_info['game_distribution_id']) {
                $distribution = M('User')->where(['id'=>$user_info['game_distribution_id']])->find();
                if (!$distribution) {
                    $this->error('推荐人ID不存在');
                }
            }
        }
  		$res = save_user($user_info,'UPDATE',$update_status=0);
		
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
			}elseif($error_field['error'] == FORMAT_ERROR)
			{
				if($error_field['field_name'] == 'email')
				{
					$this->error(L("USER_EMAIL_FORMAT_TIP"));
				}
				if($error_field['field_name'] == 'mobile')
				{
					$this->error(L("USER_MOBILE_FORMAT_TIP"));
				}
			}elseif($error_field['error'] == EXIST_ERROR)
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
			}else{
                $this->error($res['error']);
            }
		}
 		
		//开始更新is_effect状态
		M("User")->where("id=".intval($data['id']))->setField("is_effect",intval($data['is_effect']));
		$user_id = intval($data['id']);
        $log_info = '';
		if($data['user_level']!=$old_user_info['user_level']){
            $log_info .=" 等级从".$old_user_info['user_level']."级变为".$data['user_level']."级";
        }
        if($data['is_authentication']!=$old_user_info['is_authentication']){
            $log_info .=" 认证状态从".$this->get_authentication($old_user_info['is_authentication'])."变为".$this->get_authentication($data['is_authentication']);
        }

        if (defined('OPEN_EDU_MODULE') && OPEN_EDU_MODULE == 1) {
            if ($data['authentication_type'] == '教师') {
                if ($old_user_info['authentication_type'] == '机构') {
                    $GLOBALS['db']->autoExecute(DB_PREFIX . "edu_org", array('is_effect' => 0), "UPDATE", "user_id=" . $data['id']);
                }

                $teacher = M('EduTeacher')->create();
                $teacher_info = array_merge( array(
                    'user_id' => $data['id'],
                    'title' => $data['authentication_name'],
                    'teaching_certificate' => $teacher['teaching_certificate'],
                    'education_certificate' => $teacher['education_certificate'],
                ));

                if ($GLOBALS['db']->getOne("select count(*) from " . DB_PREFIX . "edu_teacher where user_id=" . $data['id']) > 0) {
                    $GLOBALS['db']->autoExecute(DB_PREFIX . "edu_teacher", $teacher_info, "UPDATE", "user_id=" . $data['id']);
                } else {
                    $GLOBALS['db']->autoExecute(DB_PREFIX . "edu_teacher", $teacher_info);
                }
            } elseif ($data['authentication_type'] == '机构') {
                if ($old_user_info['authentication_type'] == '教师') {
                    $GLOBALS['db']->autoExecute(DB_PREFIX . "edu_teacher", array('is_effect' => 0), "UPDATE", "user_id=" . $data['id']);
                }
                $org =  M('EduOrg')->create();
                $org_info = array_merge( array(
                    'user_id' => $data['id'],
                    'title' => $data['authentication_name'],
                    'business_license' => $org['business_license'],
                ));
                if ($GLOBALS['db']->getOne("select count(*) from " . DB_PREFIX . "edu_org where user_id=" . $data['id']) > 0) {
                    $GLOBALS['db']->autoExecute(DB_PREFIX . "edu_org", $org_info, "UPDATE", "user_id=" . $data['id']);
                } else {
                    $GLOBALS['db']->autoExecute(DB_PREFIX . "edu_org", $org_info);
                }
            }
        }
		save_log($user_id.L("UPDATE_SUCCESS").$log_info,1);
		$this->success(L("UPDATE_SUCCESS"));
		
	}
    public function get_authentication($is_authentication){
        switch($is_authentication){
            case 1:
                return "待审核";
                break;
            case 2:
                return "已认证";
                break;
            case 3:
                return "认证不通过";
                break;
            default:
                return "未认证";
        }
    }

	//设置状态
	public function set_effect($data)
	{
		$id = intval($data['id']);
		$ajax = intval($data['ajax']);
		$user_info = M("User")->getById($id);
		$c_is_effect = M("User")->where("id=".$id)->getField("is_effect");  //当前状态
		$n_is_effect = $c_is_effect == 0 ? 1 : 0; //需设置的状态
		$result=M("User")->where("id=".$id)->setField("is_effect",$n_is_effect);
		$user_data = array();
		if($result){
			fanwe_require(APP_ROOT_PATH.'mapi/lib/redis/BaseRedisService.php');
			fanwe_require(APP_ROOT_PATH.'mapi/lib/redis/UserRedisService.php');
 			$user_redis = new UserRedisService();
			$user_data['is_effect'] = $n_is_effect;
			$user_redis->update_db($id, $user_data);
		}
		save_log($user_info['nick_name'].l("SET_EFFECT_".$n_is_effect),1);
        clear_auto_cache('charm_podcast');
        clear_auto_cache('newstar_rank');
        clear_auto_cache('rich_list');
        clear_auto_cache('rank_consumption');
        clear_auto_cache('rank_contribution');
		return $n_is_effect;
	}

    //设置永久禁播状态
    public function set_ban($data)
    {
        $id = intval($data['id']);
        $ajax = intval($data['ajax']);
        $user_info = M("User")->getById($id);
        $c_is_effect = M("User")->where("id=".$id)->getField("is_ban");  //当前状态
        $n_is_effect = $c_is_effect == 0 ? 1 : 0; //需设置的状态
        $result=M("User")->where("id=".$id)->setField("is_ban",$n_is_effect);
        $user_data = array();
		if($result){
			fanwe_require(APP_ROOT_PATH.'mapi/lib/redis/BaseRedisService.php');
			fanwe_require(APP_ROOT_PATH.'mapi/lib/redis/UserRedisService.php');
 			$user_redis = new UserRedisService();
			$user_data['is_ban'] = $n_is_effect;
			$user_redis->update_db($id, $user_data);
		}
        save_log($user_info['nick_name'].l("SET_BAN_".$n_is_effect),1);
        return $n_is_effect;
    }

    //设置禁热门状态
    public function set_hot_on($data){
        $id = intval($data['id']);
        $ajax = intval($data['ajax']);
        $user_info = M("User")->getById($id);
        $c_is_effect = M("User")->where("id=".$id)->getField("is_hot_on");  //当前状态
        $n_is_effect = $c_is_effect == 0 ? 1 : 0; //需设置的状态
        $result=M("User")->where("id=".$id)->setField("is_hot_on",$n_is_effect);
        $user_data = array();
        if($result){
            fanwe_require(APP_ROOT_PATH.'mapi/lib/redis/BaseRedisService.php');
            fanwe_require(APP_ROOT_PATH.'mapi/lib/redis/UserRedisService.php');
            $user_redis = new UserRedisService();
            $user_data['is_hot_on'] = $n_is_effect;
            $user_redis->update_db($id, $user_data);
        }
        save_log($user_info['nick_name'].l("SET_HOT_ON_".$n_is_effect),1);
        return $n_is_effect;
    }

    //新增关注
    public function add_focus($data){
        $user_id = intval($data['user_id']);
        $user = M('User')->where("id=".$user_id)->find();
        $this->assign("user",$user);

        fanwe_require(APP_ROOT_PATH.'mapi/lib/redis/BaseRedisService.php');
        fanwe_require(APP_ROOT_PATH.'mapi/lib/redis/UserRedisService.php');
        fanwe_require(APP_ROOT_PATH.'mapi/lib/redis/UserFollwRedisService.php');

        $now=get_gmtime();

        if(trim($data['nick_name'])!='')
        {
            $map[DB_PREFIX.'user.nick_name'] = array('like','%'.trim($data['nick_name']).'%');
        }

        $create_time_2=empty($_REQUEST['create_time_2'])?to_date($now,'Y-m-d'):strim($data['create_time_2']);
        $create_time_2=to_timespan($create_time_2)+24*3600;
        if(trim($data['create_time_1'])!='' )
        {
            $map[DB_PREFIX.'user.create_time'] = array('between',array(to_timespan($data['create_time_1']),$create_time_2));
        }

        if(intval($data['id'])>0)
        {
            $map[DB_PREFIX.'user.id'] = intval($data['id']);
        }else{
            $users = array();
            $user_redis = new UserFollwRedisService($user_id);
            $fans =  $user_redis->get_follonging_user($user_id,1,100);
            foreach($fans as $k=>$v){
                $users[] = $v['user_id'];
            }
            $users[] = $user_id;
            if($users){
                $map[DB_PREFIX.'user.id'] = array('not in',$users);
            }
        }
        $map[DB_PREFIX.'user.is_effect'] = 1;

        if (method_exists ( $this, '_filter' )) {
            $this->_filter ( $map );
        }
        $model = D ('User');
        if (! empty ( $model )) {
            $this->_list ( $model, $map );
        }
        $list = $this->get("list");
        foreach($list as $k=>$v){
            $user_id = intval($v['id']);
            $user_redis = new UserRedisService();
            $user_info = $user_redis->getRow_db($user_id,array('diamonds','use_diamonds','ticket','score','user_level'));
            $list[$k]['diamonds'] = $user_info['diamonds']>0?$user_info['diamonds']:0;
            $list[$k]['use_diamonds'] = $user_info['use_diamonds']>0?$user_info['use_diamonds']:0;
            $list[$k]['ticket'] = $user_info['ticket']>0?$user_info['ticket']:0;
            $list[$k]['score'] = $user_info['score']>0?$user_info['score']:0;
            $list[$k]['user_level'] = $user_info['user_level']>0?$user_info['user_level']:1;
            $userfollw_redis = new UserFollwRedisService($user_id);
            $fans_count = $userfollw_redis->follower_count();
            $focus_count = $userfollw_redis->follow_count();
            $list[$k]['focus_count'] = $focus_count>0?$focus_count:0;
            $list[$k]['fans_count'] = $fans_count>0?$fans_count:0;
        }
        $this->assign ( 'list', $list );
        $this->assign ( 'module_name', MODULE_NAME );
        $this->display("UserCommon:add_focus");
    }

    //新增关注
    public function set_follow($data){
        fanwe_require(APP_ROOT_PATH.'mapi/lib/core/common.php');
        fanwe_require(APP_ROOT_PATH.'mapi/lib/redis/BaseRedisService.php');
        $id = $data ['id'];
        $user_id = $data['user_id'];
        if (isset ( $id )) {
            $info_success = array();
            $focus_user = explode(',',$id);
            foreach($focus_user as $v)
            {
                $deal_id = $user_id;
                if($user_id!=$v){
                    $root = redis_set_follow($user_id,$v);
                    if($root['status']==1){
                        $info_success[] = $v;
                    }else{
                        $info[] = $v;
                    }
                }
            }
            if($info) $info = implode(",",$info);
            $info = "用户ID".$deal_id."新增关注:".$info;
            if($info_success) $success_str = implode(",",$info_success);
            $success_str = "用户ID".$deal_id."新增关注:".$success_str;

            if (sizeof($info_success)) {
                save_log($success_str.l("INSERT_SUCCESS"),1);
                $root['status'] = 1;
                $root['info'] = '新增成功！';
                admin_ajax_return($root);
            } else {
                save_log($info.l("INSERT_FAILED"),0);
                $root['status'] = 0;
                $root['info'] = '新增失败！';
                admin_ajax_return($root);
            }
        } else {
            return false;
        }
    }

	//关注列表
    public function focus_list($data,$page=0){
		$user_id = intval($data['id']);
        $user = M("User")->getById($user_id);
        $this->assign("user",$user);
        if($user)
        {
            $page = intval($_REQUEST['p']);
            if($page<=0)
                $page = 1;
            $map['user_id'] = $user['id'];
            require_once APP_ROOT_PATH."/admin/Lib/Action/RedisCommon.class.php";
            $redisCommon = new Ridescommon();
            $redis = $redisCommon->video_follw_list($map['user_id'],0,$page);

			$model = D ("Focus");
			if (! empty ( $model )) {
				$this->_list ( $model, $map,'','',1,$redis);
			}
        }
        $this->display("UserCommon:focus_list");
    }
    //新增粉丝
    public function add_fans($data){
        $user_id = intval($data['user_id']);
        $user = M('User')->where("id=".$user_id)->find();
        $this->assign("user",$user);

        fanwe_require(APP_ROOT_PATH.'mapi/lib/redis/BaseRedisService.php');
        fanwe_require(APP_ROOT_PATH.'mapi/lib/redis/UserRedisService.php');
        fanwe_require(APP_ROOT_PATH.'mapi/lib/redis/UserFollwRedisService.php');

        $now=get_gmtime();

        if(trim($data['nick_name'])!='')
        {
            $map[DB_PREFIX.'user.nick_name'] = array('like','%'.trim($data['nick_name']).'%');
        }

        $create_time_2=empty($_REQUEST['create_time_2'])?to_date($now,'Y-m-d'):strim($data['create_time_2']);
        $create_time_2=to_timespan($create_time_2)+24*3600;
        if(trim($data['create_time_1'])!='' )
        {
            $map[DB_PREFIX.'user.create_time'] = array('between',array(to_timespan($data['create_time_1']),$create_time_2));
        }

        if(intval($data['id'])>0)
        {
            $map[DB_PREFIX.'user.id'] = intval($data['id']);
        }else{
            $users = array();
            $user_redis = new UserFollwRedisService($user_id);
            $fans =  $user_redis->get_follonging_by_user($user_id,1,100);
            foreach($fans as $k=>$v){
                $users[] = $v['user_id'];
            }
            $users[] = $user_id;
            if($users){
                $map[DB_PREFIX.'user.id'] = array('not in',$users);
            }
        }
        $map[DB_PREFIX.'user.is_effect'] = 1;

        if (method_exists ( $this, '_filter' )) {
            $this->_filter ( $map );
        }
        $model = D ('User');
        if (! empty ( $model )) {
            $this->_list ( $model, $map );
        }
        $list = $this->get("list");
        foreach($list as $k=>$v){
            $user_id = intval($v['id']);
            $user_redis = new UserRedisService();
            $user_info = $user_redis->getRow_db($user_id,array('diamonds','use_diamonds','ticket','score','user_level'));
            $list[$k]['diamonds'] = $user_info['diamonds']>0?$user_info['diamonds']:0;
            $list[$k]['use_diamonds'] = $user_info['use_diamonds']>0?$user_info['use_diamonds']:0;
            $list[$k]['ticket'] = $user_info['ticket']>0?$user_info['ticket']:0;
            $list[$k]['score'] = $user_info['score']>0?$user_info['score']:0;
            $list[$k]['user_level'] = $user_info['user_level']>0?$user_info['user_level']:1;
            $userfollw_redis = new UserFollwRedisService($user_id);
            $fans_count = $userfollw_redis->follower_count();
            $focus_count = $userfollw_redis->follow_count();
            $list[$k]['focus_count'] = $focus_count>0?$focus_count:0;
            $list[$k]['fans_count'] = $fans_count>0?$fans_count:0;
        }
        $this->assign ( 'list', $list );
        $this->assign ( 'module_name', MODULE_NAME );
        $this->display("UserCommon:add_fans");
    }

    //新增粉丝
    public function set_follower($data){
        fanwe_require(APP_ROOT_PATH.'mapi/lib/core/common.php');
        fanwe_require(APP_ROOT_PATH.'mapi/lib/redis/BaseRedisService.php');
        $id = $data ['id'];
        $user_id = $data['user_id'];
        if (isset ( $id )) {
            $info_success = array();
            $focus_user = explode(',',$id);
            foreach($focus_user as $v)
            {
                $deal_id = $user_id;
                if($user_id!=$v){
                    $root = redis_set_follow($v,$user_id);
                    if($root['status']==1){
                        $info_success[] = $v;
                    }else{
                        $info[] = $v;
                    }
                }
            }
            if($info) $info = implode(",",$info);
            $info = "用户ID".$deal_id."新增粉丝:".$info;
            if($info_success) $success_str = implode(",",$info_success);
            $success_str = "用户ID".$deal_id."新增粉丝:".$success_str;

            if (sizeof($info_success)) {
                save_log($success_str.l("INSERT_SUCCESS"),1);
                $root['status'] = 1;
                $root['info'] = '新增成功！';
                admin_ajax_return($root);
            } else {
                save_log($info.l("INSERT_FAILED"),0);
                $root['status'] = 0;
                $root['info'] = '新增失败！';
                admin_ajax_return($root);
            }
        } else {
            return false;
        }
    }

     //粉丝列表
    public function fans_list($data,$page=0){
        $user_id = intval($data['id']);
        $user = M("User")->getById($user_id);
        $this->assign("user",$user);
        if($user)
        {
            $page = intval($_REQUEST['p']);
            if($page<=0)
                $page = 1;
            $map['user_id'] = $user['id'];
            require_once APP_ROOT_PATH."/admin/Lib/Action/RedisCommon.class.php";
            $redisCommon = new Ridescommon();
            $redis = $redisCommon->video_follw_list($map['user_id'],1,$page);
			$model = D ("Focus");
			if (! empty ( $model )) {
				$this->_list ( $model, $map,'','',1,$redis);
			}
        }
        $this->display("UserCommon:fans_list");
    }
    //删除关注
    public function del_focus_list($data){
        fanwe_require(APP_ROOT_PATH.'mapi/lib/core/common.php');
        fanwe_require(APP_ROOT_PATH.'mapi/lib/redis/BaseRedisService.php');
        $id = $data ['id'];
        $user_id = $data['user_id'];
        if (isset ( $id )) {
            $info_success = array();
            $focus_user = explode(',',$id);
            foreach($focus_user as $v)
            {
                $deal_id = $user_id;
                $info[] = $v;
                if($user_id!=$v){
                    $root = redis_set_follow($user_id,$v);
                    if($root['status']==1){
                        $info_success[] = $v;
                    }
                }
            }
            if($info) $info = implode(",",$info);
            $info = "用户ID".$deal_id."删除关注:".$info;
            if($info_success) $success_str = implode(",",$info_success);
            $success_str = "用户ID".$deal_id."删除关注:".$success_str;

            if (sizeof($info_success)) {
                save_log($info_success.l("FOREVER_DELETE_SUCCESS"),1);
                $root['status'] = 1;
                $root['info'] = '删除成功！';
                admin_ajax_return($root);
            } else {
                save_log($info.l("FOREVER_DELETE_FAILED"),0);
                $root['status'] = 0;
                $root['info'] = '删除失败！';
                admin_ajax_return($root);
            }
        } else {
            $root['status'] = 0;
            $root['info'] = '删除失败！';
            admin_ajax_return($root);
        }
    }

    //删除粉丝
    public function del_fans_list($data){
        fanwe_require(APP_ROOT_PATH.'mapi/lib/core/common.php');
        fanwe_require(APP_ROOT_PATH.'mapi/lib/redis/BaseRedisService.php');
        $id = $data ['id'];
        $user_id = $data['user_id'];
        if (isset ( $id )) {
            $info_success = array();
            $focus_user = explode(',',$id);
            foreach($focus_user as $v)
            {
                $deal_id = $user_id;
                $info[] = $v;
                if($user_id!=$v){
                    $root = redis_set_follow($v,$user_id);
                    if($root['status']==1){
                        $info_success[] = $v;
                    }
                }
            }
            if($info) $info = implode(",",$info);
            $info = "用户ID".$deal_id."删除粉丝:".$info;
            if($info_success) $success_str = implode(",",$info_success);
            $success_str = "用户ID".$deal_id."删除粉丝:".$success_str;

            if (sizeof($info_success)) {
                save_log($info_success.l("FOREVER_DELETE_SUCCESS"),1);
                $root['status'] = 1;
                $root['info'] = '删除成功！';
                admin_ajax_return($root);
            } else {
                save_log($info.l("FOREVER_DELETE_FAILED"),0);
                $root['status'] = 0;
                $root['info'] = '删除失败！';
                admin_ajax_return($root);
            }
        } else {
            $root['status'] = 0;
            $root['info'] = '删除失败！';
            admin_ajax_return($root);
        }
    }

    //印票贡献榜
    public function contribution_list($data){
        $user_id = intval($data['id']);
        $user = M("User")->getById($user_id);
        $this->assign("user",$user);

        if($user)
        {

            $page = intval($_REQUEST['p']);
            if($page<=0)
                $page = 1;
            $map['podcast_id'] = $user['id'];
            require_once APP_ROOT_PATH."/admin/Lib/Action/RedisCommon.class.php";
            $redisCommon = new Ridescommon();
            $redis = $redisCommon->video_contribute_list($map['podcast_id'],0,$map['podcast_id'],$page);
            $model = D ("UserContribution");
            if (! empty ( $model )) {
                $this->_list ( $model, $map,'','',1,$redis);
            }
        }

        $this->display();
    }

    /**
     * 删除印票贡献榜
     */
   /* public function del_contribution_list($data)
    {
        $id = $data['id'];
        if (isset ( $id )) {
            $condition = array ('id' => array ('in', explode ( ',', $id ) ) );
            $rel_data = M("UserContribution")->where($condition)->findAll();
            foreach($rel_data as $data)
            {
                $deal_id = $data['podcast_id'];
                $info[] = $data['user_id'];
            }
            if($info) $info = implode(",",$info);
            $info = "用户ID".$deal_id."的印票贡献榜:".$info;
            $list = M("UserContribution")->where ( $condition )->delete();

            if ($list!==false) {
                save_log($info.l("FOREVER_DELETE_SUCCESS"),1);
                return true;
            } else {
                save_log($info.l("FOREVER_DELETE_FAILED"),0);
                return false;
            }
        } else {
             return false;
        }
    }*/

    //消息推送
    public function push($data){
        $user_id = intval($data['id']);
        $user = M("User")->getById($user_id);
        $this->assign("user",$user);

        if($user)
        {
            $map['user_id'] = $user['id'];
            $model = D ("PushAnchor");
            if (! empty ( $model )) {
                $this->_list ( $model, $map );
            }
        }
        $this->display();
    }

    /**
     * 删除推送消息
     */
    public function del_push($data)
    {
        $id = $data['id'];
        if (isset ( $id )) {
            $condition = array ('id' => array ('in', explode ( ',', $id ) ) );
            $rel_data = M("PushAnchor")->where($condition)->findAll();
            foreach($rel_data as $data)
            {
                $deal_id = $data['user_id'];
                $info[] = $data['room_id'];
            }
            if($info) $info = implode(",",$info);
            $info = "用户ID".$deal_id."的直播推送消息:".$info;
            $list = M("PushAnchor")->where ( $condition )->delete();

            if ($list!==false) {
                save_log($info.l("FOREVER_DELETE_SUCCESS"),1);
                return true;
            } else {
                save_log($info.l("FOREVER_DELETE_FAILED"),0);
                return false;
            }
        } else {
            return false;
        }
    }
	
	public function account($data)
	{
		$user_id = intval($data['id']);
		$model = D ();
		$user_info = array();
		if($user_id){
			$sql_str = "select id,nick_name,diamonds,score,ticket from ".DB_PREFIX."user where id = ".$user_id;
			$user_info = $GLOBALS['db']->getRow($sql_str);  //
		}	
		$this->assign("user_info",$user_info);
		$this->display();
	}

	public function modify_account($data)
	{
		$data_arr = array();
		$user_id = intval($data['id']);

		$data_arr['diamonds'] = floatval($data['diamonds']);
		
		$sql_str = "select id,diamonds from ".DB_PREFIX."user where id = ".$user_id;
		$user_info = $GLOBALS['db']->getRow($sql_str);  //
		
		if($data_arr['diamonds']+$user_info['diamonds']>2147483647||$data_arr['diamonds']+$user_info['diamonds']<0){
			return false;
		}
		//$data_arr['ticket'] = intval($data['ticket']);
		
		$msg = trim($data['msg'])==''?l("ADMIN_MODIFY_ACCOUNT"):trim($data['msg']);
		
		modify_account($data_arr,$user_id,$msg);
        if ($data_arr['diamonds'] >= 0)
		    save_log("用户".$user_id."钻石增加".$data_arr['diamonds']."成功",1);
        elseif ($data_arr['diamonds'] < 0){
            save_log("用户".$user_id."钻石减少".(-$data_arr['diamonds'])."成功",1);
        }
		return true;
	}
	
	public function account_detail($data)
	{
		$user_id = intval($data['id']);
		$user_info = M("User")->getById($user_id);
		$this->assign("user_info",$user_info);
		$map['user_id'] = $user_id;
        if($data['type']!=''){
            $map['type'] = intval($data['type']);
        }
		if (method_exists ( $this, '_filter' )) {
			$this->_filter ( $map );
		}
		$types = array('0'=>'充值记录','1'=>'提现记录','3'=>'兑换记录');
        if(defined('OPEN_SHARE_EXPERIENCE')&&OPEN_SHARE_EXPERIENCE==1){
            $types[4] = '分享';
        }
        if(defined('OPEN_FAMILY_MODULE')&&OPEN_FAMILY_MODULE==1){
            $types[4] = '家族收益';
        }
        if((defined('OPEN_FAMILY_MODULE')&&OPEN_FAMILY_MODULE==1) && (defined('OPEN_SHARE_EXPERIENCE')&&OPEN_SHARE_EXPERIENCE==1)){
            $types[4] = '分享或家族收益';
        }
        if(defined('OPEN_LOGIN_SEND_SCORE')&&OPEN_LOGIN_SEND_SCORE==1){
            $types[5] = '每日首次登录送积分';
        }
        if(defined('OPEN_LIVE_PAY')&&OPEN_LIVE_PAY==1){
            $types[6] = '付费直播消费记录';
        }
        if(defined('PAI_REAL_BTN')&&PAI_REAL_BTN==1 || defined('PAI_VIRTUAL_BTN')&&PAI_VIRTUAL_BTN==1){
            $types[8] = '竞拍记录';
        }
		$model = M ("UserLog");
		if (! empty ( $model )) {
			$this->_list ( $model, $map );
		}
        $this->assign ( 'module_name', MODULE_NAME );
        $this->assign("types",$types);
		$this->display ();
		return;
	}

	public function foreverdelete_account_detail()
	{

		//彻底删除指定记录
		$ajax = intval($_REQUEST['ajax']);
		$id = $_REQUEST ['id'];
		if (isset ( $id )) {
				$condition = array ('id' => array ('in', explode ( ',', $id ) ) );
				$rel_data = M("UserLog")->where($condition)->findAll();
				foreach($rel_data as $data)
				{
					$info[] = $data['id'];
				}
				if($info) $info = implode(",",$info);
				$list = M("UserLog")->where ( $condition )->delete();

				if ($list!==false) {
					save_log($info.l("FOREVER_DELETE_SUCCESS"),1);
					$this->success (l("FOREVER_DELETE_SUCCESS"),$ajax);
				} else {
					save_log($info.l("FOREVER_DELETE_FAILED"),0);
					$this->error (l("FOREVER_DELETE_FAILED"),$ajax);
				}
			} else {
				$this->error (l("INVALID_OPERATION"),$ajax);
		}
	}

    
	public function check_user($user_id){
		if(intval($user_id)>0)
		{
			$uinfo = M("User")->getById(intval($user_id));
			if($uinfo)
			{
				$result['status'] = true;
				$result['user_info'] = $uinfo;
                admin_ajax_return($result);
			}
			else
			{
				$result['status'] = false;
                admin_ajax_return($result);
			}
		}
		$result['status'] = false;
		return($result);
	}

    //礼物日志
    public function prop($data)
    {
        $now=get_gmtime();
        $user_id = intval($_REQUEST['id']);
        $user_info = M("User")->getById($user_id);
        $prop_list = M("prop")->where("is_effect <>0")->findAll();

        $where = "l.from_user_id=".$user_id ;
        $model = D ("video_prop");
        //赠送时间

        $current_Year = date('Y');


        $current_YM = date('Ym');
        for ($i=0; $i<5; $i++)
        {
            $years[$i] = $current_Year - $i;
        }

        for ($i=01; $i<13; $i++)
        {
            $month[$i] = str_pad(0+$i,2,0,STR_PAD_LEFT);
        }

        if(strim($data['years'])!=-1&&strim($data['month']!=-1)){
            $time=$data['years'].''.$data['month'];
        }else{
            $time=$current_YM;
        }
        if(strim($data['years'])!=-1&&strim($data['month']==-1)){
            $this->error("请选择月份");
        }
        if(strim($data['years'])==-1&&strim($data['month']!=-1)){
            $this->error("请选择年份");
        }

        //查询ID
        if(strim($data['to_user_id'])!=''){
            $parameter.= "l.to_user_id=".intval($data['to_user_id']). "&";
            $sql_w .= "l.to_user_id=".intval($data['to_user_id'])." and ";
        }
        //查询昵称
        if(trim($data['nick_name'])!='')
        {
            $parameter.= "u.nick_name like " . urlencode ( '%'.trim($data['nick_name']).'%' ) . "&";
            $sql_w .= "u.nick_name like '%".trim($data['nick_name'])."%' and ";

        }
        if (!isset($_REQUEST['prop_id'])) {
            $_REQUEST['prop_id'] = -1;
        }
        //查询礼物
        if($_REQUEST['prop_id']!=-1) {
            if (isset($data['prop_id'])) {
                $parameter .= "l.prop_id=" . intval($data['prop_id']) . "&";
                $sql_w .= "l.prop_id=" . intval($data['prop_id']) . " and ";
            }
        }
//        //查询时间
//        $create_time_2=empty($data['create_time_2'])?to_date($now,'Y-m-d'):strim($data['create_time_2']);
//        $create_time_1=to_timespan($data['create_time_1'])+24*3600;
//        $create_time_2=to_timespan($create_time_2)+24*3600;
//
//
//        if(trim($data['create_time_1'])!='')
//        {
//            $parameter.="l.create_time between '".strtotime($data['create_time_1'])  . "' and '". $create_time_2 ."'&";
//            $sql_w .="l.create_time between '". strtotime($data['create_time_1']). "' and '". $create_time_2 ."' and ";
//
//        }
//        if(trim($data['create_time_2']))
//        {
//
//            $parameter.="l.create_time<=".$create_time_2. "&";
//            $sql_w .="l.create_time<=". $create_time_2." and ";
//
//
//        }
//
//        //比较两个时间段的月份是否一致,如不一致,提示
//        $create_time_1_m =date('m', $create_time_1);
//        $create_time_2_m =date('m', $create_time_2);
//        if(trim($data['create_time_1'])!=''&& trim($data['create_time_2'])!=''){
//            if($create_time_2_m!=$create_time_1_m){
//                $this->error("查询时间只能在当月内");
//            }
//        }

        //默认查询本月的记录,选择查询时间时,如果查询时间 不等于当前时间,则查询他表
        if($data['years']!=''&&$data['month']!=''){
            $sql_str = "SELECT l.id,l.create_ym,l.to_user_id, l.create_time,l.prop_id,l.prop_name,l.from_user_id,l.create_date,l.num,l.total_ticket,u.nick_name,l.from_ip
                         FROM   ".DB_PREFIX."video_prop_".$time." as l
                         LEFT JOIN ".DB_PREFIX."user AS u  ON l.to_user_id = u.id" ." LEFT JOIN ".DB_PREFIX."prop AS v ON l.prop_name = v.name" ."
                         WHERE $where "."  and ".$sql_w." 1=1  ";
           
           $count_sql = "SELECT count(l.id)  as tpcount
                         FROM   ".DB_PREFIX."video_prop_".$time." as l
                         LEFT JOIN ".DB_PREFIX."user AS u  ON l.to_user_id = u.id" ." LEFT JOIN ".DB_PREFIX."prop AS v ON l.prop_name = v.name" ."
                         WHERE $where "."  and ".$sql_w." 1=1  ";
        }else{

            $sql_str = "SELECT l.id,l.create_ym,l.to_user_id, l.create_time,l.prop_id,l.prop_name,l.from_user_id,l.create_date,l.num,l.total_ticket,u.nick_name,l.from_ip
                         FROM   ".DB_PREFIX."video_prop_".date('Ym',NOW_TIME)." as l
                         LEFT JOIN ".DB_PREFIX."user AS u  ON l.to_user_id = u.id" ." LEFT JOIN ".DB_PREFIX."prop AS v ON l.prop_name = v.name" ."
                         WHERE $where "."   and ".$sql_w." 1=1  ";
            
			$count_sql = "SELECT count(l.id)  as tpcount
                         FROM   ".DB_PREFIX."video_prop_".date('Ym',NOW_TIME)." as l
                         LEFT JOIN ".DB_PREFIX."user AS u  ON l.to_user_id = u.id" ." LEFT JOIN ".DB_PREFIX."prop AS v ON l.prop_name = v.name" ."
                         WHERE $where "."   and ".$sql_w." 1=1  ";
        }

        $volist = $this->_Sql_list($model,$sql_str,'&'.$parameter,1,0,$count_sql);
        foreach($volist as $k=>$v){
            if($volist[$k]['prop_id']==12){
                $volist[$k]['total_ticket']='';
            }
            $volist[$k]['create_time']=date('Y-m-d',$volist[$k]['create_time']);
        }

        $this->assign("user_info",$user_info);
        $this->assign("prop",$prop_list);
        $this->assign("years",$years);
        $this->assign("month",$month);
        $this->assign("list", $volist);
        $this->display ();
        return;

    }
    /**
     * 删除礼物日志
     */
    public function del_prop()
    {

        //彻底删除指定记录
        $ajax = intval($_REQUEST['ajax']);
        $id = intval($_REQUEST ['id']);
        $create_ym = intval($_REQUEST ['create_ym']);
        if (isset ( $id )) {
            $condition = array ('id' => array ('in', explode ( ',', $id ) ) );
            //默认删除本月记录,如果用户进行时间搜索,通过ajax获取起始时间的年月用REQUEST获取,
            //再与表名连接进行查询删除。
            $rel_data = M("video_prop_".date('Ym',NOW_TIME))->where($condition)->findAll();
            foreach($rel_data as $data)
            {
                $info[] = $data['id'];
            }
                if($info) $info = implode(",",$info);
                $list = M("video_prop_".date('Ym',NOW_TIME))->where ( $condition )->delete();

            if($create_ym!='NaNNaN' &&date('Ym',NOW_TIME)!=$create_ym) {
                $rel_data = M("video_prop_".$create_ym)->where($condition)->findAll();
                foreach($rel_data as $data)
                {
                    $info[] = $data['id'];
                }
                if($info) $info = implode(",",$info);
                $list = M("video_prop_".$create_ym)->where ( $condition )->delete();
            }
            if ($list!==false) {
                save_log($info.l("FOREVER_DELETE_SUCCESS"),1);
                $this->success (l("FOREVER_DELETE_SUCCESS"),$ajax);
            } else {
                save_log($info.l("FOREVER_DELETE_FAILED"),0);
                $this->error (l("FOREVER_DELETE_FAILED"),$ajax);
            }
        } else {
            $this->error (l("INVALID_OPERATION"),$ajax);
        }
    }
    //分享奖励
    public function distribution_log($data)
    {
        $now=get_gmtime();
        $user_id = intval($_REQUEST['id']);
        $user_info = M("User")->getById($user_id);
        $where = "l.to_user_id=".$user_id ;
        $model = D ("distribution_log");

        //查询ID
        if(strim($data['from_user_id'])!=''){
            $parameter.= "l.from_user_id=".intval($data['from_user_id']). "&";
            $sql_w .= "l.from_user_id=".intval($data['from_user_id'])." and ";
        }
        //查询昵称
        if(trim($data['nick_name'])!='')
        {
            $parameter.= "u.nick_name like " . urlencode ( '%'.trim($data['nick_name']).'%' ) . "&";
            $sql_w .= "u.nick_name like '%".trim($data['nick_name'])."%' and ";

        }
        //查询时间
        $create_time_2=empty($data['create_time_2'])?to_date($now,'Y-m-d'):strim($data['create_time_2']);
        $create_time_1=to_timespan($data['create_time_1'])+24*3600;
        $create_time_2=to_timespan($create_time_2)+24*3600;


        if(trim($data['create_time_1'])!='')
        {
            $parameter.="l.create_time between '".strtotime($data['create_time_1'])  . "' and '". $create_time_2 ."'&";
            $sql_w .="l.create_time between '". strtotime($data['create_time_1']). "' and '". $create_time_2 ."' and ";

        }
        if(trim($data['create_time_2']))
        {

            $parameter.="l.create_time<=".$create_time_2. "&";
            $sql_w .="l.create_time<=". $create_time_2." and ";


        }

        //比较两个时间段的月份是否一致,如不一致,提示
        $create_time_1_m =date('m', $create_time_1);
        $create_time_2_m =date('m', $create_time_2);
        if(trim($data['create_time_1'])!=''&& trim($data['create_time_2'])!=''){
            if($create_time_2_m!=$create_time_1_m){
                $this->error("查询时间只能在当月内");
            }
        }

        $sql_str = "SELECT l.id,l.from_user_id,l.ticket, l.create_time,u.nick_name
                         FROM   ".DB_PREFIX."distribution_log as l
                         LEFT JOIN ".DB_PREFIX."user AS u  ON l.from_user_id = u.id
                         WHERE $where "."  and ".$sql_w." 1=1  ";
                         
                         
        $count_sql = "SELECT count(l.id) as tpcount
                         FROM   ".DB_PREFIX."distribution_log as l
                         LEFT JOIN ".DB_PREFIX."user AS u  ON l.from_user_id = u.id
                         WHERE $where "."  and ".$sql_w." 1=1  ";

        $volist = $this->_Sql_list($model,$sql_str,'&'.$parameter,'id',0,$count_sql);
                
        foreach($volist as $k=>$v){
            $volist[$k]['create_time']=date('Y-m-d',$volist[$k]['create_time']);
        }

        $this->assign("user_info",$user_info);
        $this->assign("list", $volist);
        $this->display ();
        return;

    }
    //查询分销子成员
    public function distribution_user($data)
    {
        $user_id = intval($data['id']);
        $model = D("user");
        $user_info = M("User")->getById($user_id);
        $parameter = '';
        $sql_w = '1=1';

        //赠送时间
        $current_Year = date('Y');
        for ($i=0; $i<5; $i++)
        {
            $years[$i] = $current_Year - $i;
        }

        for ($i=01; $i<13; $i++)
        {
            $month[$i] = str_pad(0+$i,2,0,STR_PAD_LEFT);
        }

        if(strim($data['years'])!=-1&&strim($data['month']!=-1)){
            $time=$data['years'].'-'.$data['month']."-01 00:00:00";
            $s_month=to_timespan($time);
            $e_month=to_timespan(date('Y-m-d', mktime(23, 59, 59, date('m', strtotime($time))+1, 00)));
            $is_seslect = 1;
        }


        if(strim($data['years'])!=-1&&strim($data['month']==-1)){
            $this->error("请选择月份");
        }
        if(strim($data['years'])==-1&&strim($data['month']!=-1)){
            $this->error("请选择年份");
        }
        //查询注册时间
        if ($is_seslect) {
            $parameter .= "u.create_time>" . intval($s_month) . "&";
            $parameter .= "u.create_time<" . intval($e_month) . "&";
            $sql_w .= " and u.create_time>" . intval($s_month);
            $sql_w .= " and u.create_time<" . intval($e_month);
        }

        //查询ID
        if (strim($data['from_user_id']) != '') {
            $parameter .= "u.id=" . intval($data['from_user_id']) . "&";
            $sql_w .= " and u.id=" . intval($data['from_user_id']);
        }
        //查询昵称
        if (trim($data['nick_name']) != '') {
            $parameter .= "u.nick_name like " . urlencode('%' . trim($data['nick_name']) . '%') . "&";
            $sql_w .= " and u.nick_name like '%" . trim($data['nick_name']) . "%'";
        }

        $field       = "u.id,u.nick_name ";
        $left_join_1 = ",(select sum(pn.money) from ".DB_PREFIX."payment_notice as pn where pn.user_id = u.id and pn.is_paid=1 ) as sum_money";
        $left_join_2 = ", u.use_diamonds as sum_diamonds";
        $where       = " u.p_user_id=".$user_id." and ".$sql_w;

        $sql_str = "select ".$field.$left_join_1.$left_join_2." from fanwe_user as u where ".$where."  group by u.id";
        $count_sql = "select count(u.id) as tpcount from ".DB_PREFIX."user as u where $where";

        $volist = $this->_Sql_list($model,$sql_str,'&'.$parameter,'id',0,$count_sql);
        foreach($volist as $k=>$v){
            if($v['sum_money']==''){
                $volist[$k]['sum_money'] = 0;
            }
            if($v['sum_diamonds']==''){
                $volist[$k]['sum_diamonds'] = 0;
            }
        }
        $this->assign("user_info",$user_info);
        $this->assign("list", $volist);
        $this->assign("years",$years);
        $this->assign("month",$month);
        $this->display ();
    }

    public function forbid_msg($data){
        fanwe_require(APP_ROOT_PATH.'mapi/lib/core/common.php');
        $id = intval($data['user_id']);
        $is_nospeaking = M("User")->where("id=".$id)->getField("is_nospeaking");
        require_once(APP_ROOT_PATH.'system/tim/TimApi.php');
        $api = createTimAPI();
        $time = 4294967295;
        $info = "im全局禁言";
        $set_nospeaking = 1;
        if($is_nospeaking){
            $time = 0;
            $set_nospeaking =0;
            $info = "解除im全局禁言";
        }
        $ret = $api->set_no_speaking($id,$time);
        if($ret['ErrorCode']){
            $ret = $api->set_no_speaking($id,$time);
        }
        if($ret['ErrorCode']){
            $result['status'] = 0;
            $result['info'] = $info."失败";
            save_log($id.$info."失败",0);
        }else{
            $result['status'] = 1;
            $result['info'] = $info."成功";
            M("User")->where("id=".$id)->setField("is_nospeaking",$set_nospeaking);
            user_deal_to_reids(array($id));
            save_log($id.$info."成功",1);
        }
        admin_ajax_return($result);
    }
	
}
	