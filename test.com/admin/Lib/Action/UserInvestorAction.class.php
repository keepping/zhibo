<?php
class UserInvestorAction extends CommonAction{
	public function index(){
        if(intval($_REQUEST['id'])>0)
        {
            $map[DB_PREFIX.'user.id'].= intval($_REQUEST['id']);
        }
        if(trim($_REQUEST['nick_name'])!='')
        {
            $map[DB_PREFIX.'user.nick_name'] = array('like','%'.trim($_REQUEST['nick_name']).'%');
        }
        if(trim($_REQUEST['contact'])!='')
        {
            $map[DB_PREFIX.'user.contact'] = array('like','%'.trim($_REQUEST['contact']).'%');
        }
        if(trim($_REQUEST['mobile'])!='')
        {
            $map[DB_PREFIX.'user.mobile'] = array('like','%'.trim($_REQUEST['mobile']).'%');
        }

        $map[DB_PREFIX.'user.is_authentication'] = 1;
        $map[DB_PREFIX.'user.is_effect'] = 1;
        $map[DB_PREFIX.'user.is_robot'] = 0;
        if (method_exists ( $this, '_filter' )) {
            $this->_filter ( $map );
        }

        //$name=$this->getActionName();
        $model = D ('User');
        if (! empty ( $model )) {
            $this->_list ( $model, $map );
        }
        $this->display ();
	}
    public function batch_content(){
        $id=$_REQUEST['id'];
        $implode_id = implode(',',(explode(',',$id)));

        $user = $GLOBALS['db']->getAll("select id,authentication_name,identify_number,identify_positive_image,identify_nagative_image,identify_hold_image,is_authentication from ".DB_PREFIX."user where id in($implode_id)");

		//是否显示身份证号码
        $m_config = load_auto_cache("m_config");
        $show_identify_number = intval($m_config['is_show_identify_number']);
        $this->assign('show_identify_number',$show_identify_number);

        $this->assign('list',$user);

        $this->assign('implode_id',$implode_id);
//        $this->assign('show_bnt',$show_bnt);
        $this->display();
    }
	public function show_content(){
		$id=intval($_REQUEST['id']);
		$status=intval($_REQUEST['status']);
		
		$user=M("user")->getById($id);
		if($status==1){
			$user['do_info']='审核通过';
		}elseif($status==3){
			$user['do_info']='审核';
			$show_bnt=3;
		}else{
			$user['do_info']='审核不通过';
		}
		$user['is_investor_name']=get_investor($user['user_type']);
		$user['investor_status_name']=get_investor_status($user['is_authentication']);
		
		$user['identify_hold_image']=get_spec_image($user['identify_hold_image']);
		$user['identify_positive_image']=get_spec_image($user['identify_positive_image']);
		$user['identify_nagative_image']=get_spec_image($user['identify_nagative_image']);

		$data['order_type'] = 'shop';
		$data['order_status'] = 4;
		$data['viewer_id'] = $id;

//		if(define(DISTRIBUTION_MODULE) && DISTRIBUTION_MODULE == 1){
//			$order = M('goods_order')->where($data)->count('id');
//			$user['order'] = intval($order);
//		}
        //是否显示身份证号码
        $m_config = load_auto_cache("m_config");
        $show_identify_number = intval($m_config['is_show_identify_number']);
		$this->assign('show_identify_number',$show_identify_number);

 		$this->assign('user',$user);
		$this->assign('status',$status);
		$this->assign('show_bnt',$show_bnt);
		$this->display();
 	}
    public function investor_go_allow()
    {
        $id = $_REQUEST['id'];

        $status = intval($_REQUEST['is_authentication']);
        $v_explain = strim($_REQUEST['v_explain']);
        if ($_REQUEST['investor_send_info']) {
            $investor_send_info = strim($_REQUEST['investor_send_info']);
        }
        fanwe_require(APP_ROOT_PATH . 'mapi/lib/redis/BaseRedisService.php');
        fanwe_require(APP_ROOT_PATH . 'mapi/lib/redis/UserRedisService.php');
        $user_redis = new UserRedisService();
        $user = $GLOBALS['db']->getAll("select * from " . DB_PREFIX . "user where id in($id)");
        foreach ($user as $k => $v) {
            $authentication_type[$k]=$user[$k]['authentication_type'];
            $user_id[$k]=$user[$k]['id'];
        }


            if ($user) {
                for($i=0;$i<count($user);$i++) {
                    //$user_data['id'] = $user['id'];
                    $user_data['is_authentication'] = $status;
                    if ($status == 3) {
                        $m_config = load_auto_cache("m_config");
                        $user_data['v_explain'] = '';
                        $user_data['v_icon'] = '';
                        $user_data['investor_time'] = get_gmtime() + $m_config['attestation_time'];

                    } else {
                        $user_data['v_explain'] = $v_explain;
                        if($user_data['v_explain']==''){
                            $user_data['v_explain'] =$authentication_type[$i];
                        }
                        $user_data['v_icon'] = get_spec_image(M('AuthentList')->where("name='" . trim($authentication_type[$i] . "'"))->getField("icon"));
                    }

                    //认证ID
                    $user_data['authent_list_id'] = get_spec_image(M('AuthentList')->where("name='" . trim($authentication_type[$i] . "'"))->getField("id"));

                    if ($investor_send_info) {
                        $user_data['investor_send_info'] = $investor_send_info;
                    } else {
                        $user_data['investor_send_info'] = '';
                    }

                    /*$list = M("User")->save($user_data);
                   if ($list !== false){
                       save_log($user_data['id']."审核操作成功",1);
                   }else{
                       save_log($user_data['id']."审核操作失败",0);
                   }*/
                    $where = "id=".intval($user_id[$i]);
                    if ($GLOBALS['db']->autoExecute(DB_PREFIX . "user", $user_data, 'UPDATE', $where)) {
                        save_log($user_id[$i] . "审核操作成功", 1);
                    } else {
                        save_log($user_id[$i] . "审核操作失败", 0);
                    }
                    //redis化
                    $user_redis->update_db($user_id[$i], $user_data);
                    //send_investor_status($user_data);
                }
                $this->success("操作成功");
            } else {
                $this->error("没有该会员信息");
            }


    }
 	
 	
}
?>