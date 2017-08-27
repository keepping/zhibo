<?php
// +----------------------------------------------------------------------
// | Fanwe 方维众筹商业系统
// +----------------------------------------------------------------------
// | Copyright (c) 2011 http://www.fanwe.com All rights reserved.
// +----------------------------------------------------------------------
// | Author: 甘味人生(526130@qq.com)
// +----------------------------------------------------------------------

class UserConfirmRefundAction extends CommonAction{
	
	public function index()
	{
		//列表过滤器，生成查询Map对象
		$map = $this->_search ();

		$condition['is_pay'] = 1;

		//追加默认参数
		if($this->get("default_map"))
			$map = array_merge($map,$this->get("default_map"));
		$map = array_merge($map,$condition);
		if (method_exists ( $this, '_filter' )) {
			$this->_filter ( $map );
		}
		$model = D ('UserRefund');//print_r($map);exit();
		if (! empty ( $model )) {
			$this->_list ( $model, $map );
		}
		$this->display ();
		return;
	}
	/**
	 * 提现确认记录
     */
	public function confirm_list(){
		//列表过滤器，生成查询Map对象
		$map = $this->_search ('UserRefund');
		$map['_string'] = 'is_pay = 1 or is_pay = 3 ';
		//追加默认参数
		if($this->get("default_map"))
			$map = array_merge($map,$this->get("default_map"));
		if (method_exists ( $this, '_filter' )) {
			$this->_filter ( $map );
		}
		//$name=$this->getActionName();
		$model = M ('UserRefund');
		if (! empty ( $model )) {
			$this->_list ( $model, $map );
		}
		$this->display ();
		return;
	}
    /**
     * 不允许提现
     */
    public function refund_allow(){
        $id=intval($_REQUEST['id']);
        $status=intval($_REQUEST['status']);
        $refund_data=M("UserRefund")->getById($id);
        $info=array();
        if($status){
            $info['do']='允许';
        }else{
            $info['do']='不允许';
        }
        $sql = "update ".DB_PREFIX."user set refund_ticket=refund_ticket-".$refund_data['ticket']." where  id=".$refund_data['user_id'];
        $GLOBALS['db']->query($sql);
        $this->assign("info",$info);
        $this->assign("refund_data",$refund_data);
        $this->assign("status",$status);
        $this->display ();
    }
	/**
	 * 确认提现	
	 */
	public function refund_confirm()
	{
		$id = intval($_REQUEST['id']);
		$refund_data = M("UserRefund")->getById($id);
		if (intval($refund_data['withdrawals_type']) == 1){
			$alipay_info = M("User")->where("id = ".$refund_data['user_id'])->field('alipay_name,alipay_account')->select();
			$this->assign("alipay_info",$alipay_info);
		}
		$this->assign("refund_data",$refund_data);
		$this->display();
	}
	/** 
	 * 确认提现执行
	 */
	public function confirm()
	{
		$id = intval($_REQUEST['id']);
		$refund_data = M("UserRefund")->getById($id);
		$user_id = $refund_data['user_id'];
		if($refund_data['is_pay']==3)
		{
			$this->error("已经提现过");
		}
		
		//已审核才可以提现	
		if($refund_data&&$refund_data['is_pay']==1)
		{
			
			$m_config =  load_auto_cache("m_config");//初始化手机端配置
			/*fanwe_require(APP_ROOT_PATH.'mapi/lib/redis/BaseRedisService.php');
			fanwe_require(APP_ROOT_PATH.'mapi/lib/redis/UserRedisService.php');
	        $user_redis = new UserRedisService();
	        $refund_user = $user_redis->getRow_db($refund_data['user_id'],array('ticket','diamonds','refund_ticket'));*/
	        
	        //$sql = "select ticket,diamonds,refund_ticket from  ".DB_PREFIX."user where id=".$refund_data['user_id'];
	        //$refund_user = $GLOBALS['db']->getRow($sql,true,true);

	        /*if(intval($refund_user['ticket'])-intval($refund_user['refund_ticket'])<intval($refund_data['ticket'])){
	        	$refund_data['pay_log'] = $m_config['ticket_name']."不足，不能提现";
				$refund_data['is_pay'] = 4;	
				M("UserRefund")->save($refund_data);
				$this->error($m_config['ticket_name']."不足，不能提现");
	        }else{*/

				//微信提现
				//if (wx_withdraw_cash($id)||1){
				//管理员的SESSION
				$adm_session = es_session::get(md5(conf("AUTH_KEY")));
				$adm_id = intval($adm_session['adm_id']);
				if($refund_data['withdrawals_type'] == 0){
					define('OPEN_TEST_WX',0);//开启微信调试 只用于微信提现调试使用
					if (wx_withdraw_cash($id)){
						//写入用户日志
						$data = array();
						$data['ticket'] = intval($refund_data['ticket']);
						$data['log_admin_id'] = intval($adm_id);
						$param['type'] = 1;//类型 0表示充值 1表示提现 2赠送道具 3 兑换印票
						$ticket_name = $m_config['ticket_name']!=''?$m_config['ticket_name']:'印票';
						$log_msg = '提现'.$data['ticket'].$m_config['ticket_name'].'成功';
						account_log_com($data,$refund_data['user_id'],$log_msg,$param);
                        $info = $refund_data['user_id'].$log_msg;
                        save_log($info,1);
						$this->success("提现成功!");
					}else{
						//付款失败解冻用户提现印票
						$sql = "update ".DB_PREFIX."user set refund_ticket=refund_ticket-".$refund_data['ticket']." where  id=".$refund_data['user_id'];
						$GLOBALS['db']->query($sql);
						if($GLOBALS['db']->affected_rows()){
							user_deal_to_reids(array($user_id));
							 //写入用户日志
							$data = array();
							$data['ticket'] = intval($refund_data['ticket']);
							$data['log_admin_id'] = intval($adm_id);
							$param['type'] = 1;//类型 0表示充值 1表示提现 2赠送道具 3 兑换印票
							$ticket_name = $m_config['ticket_name']!=''?$m_config['ticket_name']:'印票';
							$log_msg ='提现失败，解冻提现'.$data['ticket'].$m_config['ticket_name'];
                            save_log($refund_data['user_id'].$log_msg,0);
							account_log_com($data,$user_id,$log_msg,$param);
						}
						//写入日志
						$this->success("提现失败，请重试");
					}
				}elseif ($refund_data['withdrawals_type'] == 1){
					//支付宝提现
                    //支付成功
                    if (intval($_REQUEST['status']) == 1){
                        $sql ="update ".DB_PREFIX."user_refund set pay_log='已付款',is_pay = 3,pay_time = ".get_gmtime().",confirm_cash_ip='".get_client_ip()."' where id = ".$id;
                        $GLOBALS['db']->query($sql);
                        if($GLOBALS['db']->affected_rows()){
                            $data = array();
                            $data['ticket'] = intval($refund_data['ticket']);
                            $data['log_admin_id'] = intval($adm_id);
                            $param['type'] = 1;//类型 0表示充值 1表示提现 2赠送道具 3 兑换印票
                            $ticket_name = $m_config['ticket_name']!=''?$m_config['ticket_name']:'印票';
                            $log_msg = '提现'.$data['ticket'].$m_config['ticket_name'].'成功';
                            account_log_com($data,$refund_data['user_id'],$log_msg,$param);
                            $info = $refund_data['user_id'].$log_msg;
                            save_log($info,1);
                            $this->success("提现成功!");
                        }else{
                            $this->error("确认提现失败!");
                        }
                    }else{
                        //付款失败解冻用户提现印票
                        $sql = "update ".DB_PREFIX."user set refund_ticket=refund_ticket-".$refund_data['ticket']." where id=".$refund_data['user_id'];
                        $GLOBALS['db']->query($sql);
                        if($GLOBALS['db']->affected_rows()){
                            user_deal_to_reids(array($user_id));
                            $refund_data['is_pay'] = 4;
                            $refund_data['confirm_cash_ip'] = get_client_ip();
                            M("UserRefund")->save($refund_data);
                            //写入用户日志
                            $data = array();
                            $data['ticket'] = intval($refund_data['ticket']);
                            $data['log_admin_id'] = intval($adm_id);
                            $param['type'] = 1;//类型 0表示充值 1表示提现 2赠送道具 3 兑换印票
                            $ticket_name = $m_config['ticket_name']!=''?$m_config['ticket_name']:'印票';
                            $log_msg ='提现失败，解冻提现'.$data['ticket'].$m_config['ticket_name'];
                            save_log($refund_data['user_id'].$log_msg,0);
                            account_log_com($data,$user_id,$log_msg,$param);
                        }
                        //写入日志
                        $this->success("提现失败，请重试");
                    }
				}elseif ($refund_data['withdrawals_type'] == 2){//公会提现
                        $GLOBALS['db']->query("update ".DB_PREFIX."user_refund set pay_log='已付款',is_pay = 3,pay_time = ".get_gmtime().",confirm_cash_ip='".get_client_ip()."' where id = ".$id);
                        $data = array();
                        $data['ticket'] = intval($refund_data['ticket']);
                        $data['log_admin_id'] = intval($adm_id);
                        $param['type'] = 1;//类型 0表示充值 1表示提现 2赠送道具 3 兑换印票
                        $ticket_name = $m_config['ticket_name']!=''?$m_config['ticket_name']:'印票';
                        $log_msg = '提现'.$data['ticket'].$m_config['ticket_name'].'成功';
                        account_log_com($data,$refund_data['user_id'],$log_msg,$param);
                        $info = $refund_data['user_id'].$log_msg;
                        save_log($info,1);
                        $this->success("提现成功!");
                }
			//}			
		}else{
			$this->error("没有提现数据");
		}
	}
	/**
	 * 彻底删除
	 */
	public function delete() {
		//彻底删除指定记录
		$ajax = intval($_REQUEST['ajax']);
		$id = $_REQUEST ['id'];
		if (isset ( $id )) {
				$condition = array ('id' => array ('in', explode ( ',', $id ) ) );			
				$rel_data = M("UserRefund")->where($condition)->findAll();
				$list = M("UserRefund")->where ( $condition )->delete();
				
				foreach($rel_data as $data)
				{
					$info[] = "[id:".$data['id'].",money:".$data['money']."]";						
				}
				if($info) $info = implode(",",$info);
				
				if ($list!==false) {
					save_log($info."成功删除",1);
					$this->success ("成功删除",$ajax);
				} else {
					save_log($info."删除出错",0);					
					$this->error ("删除出错",$ajax);
				}
			} else {
				$this->error (l("INVALID_OPERATION"),$ajax);
		}
	}
	/**
	 * 导出电子表
	 */
	public function export_csv($page = 1)
	{
		$pagesize = 10;
		set_time_limit(0);
		$limit = (($page - 1)*intval($pagesize)).",".(intval($pagesize));
		
		$where = " 1=1 ";
		//定义条件
		if(trim($_REQUEST['user_id'])!='')
		{
			$where.= " and ur.user_id = ".intval($_REQUEST['user_id']);
		}
		if(trim($_REQUEST['is_pay'])!='')
		{
			$where.= " and ur.is_pay = ".intval($_REQUEST['is_pay']);
		}
		$sql ="select u.user_name as user_name,u.id as user_id,u.email as email,u.ex_real_name as ex_real_name,u.ex_account_bank as ex_account_bank,u.ex_account_info as ex_account_info,u.ex_contact as ex_contact,u.mobile as mobile, ur.money as money,ur.user_bank_id from ".DB_PREFIX."user as u LEFT JOIN ".DB_PREFIX."user_refund as ur on ur.user_id = u.id where ".$where." limit ".$limit;
		$list=$GLOBALS['db']->getAll($sql);
		//var_dump($_REQUEST);exit;
		if($list)
		{
			register_shutdown_function(array(&$this, 'export_csv'), $page+1);
			
			$refund_value = array( 'user_name'=>'""', 'email'=>'""', 'bank_info'=>'""','mobile'=>'""','money'=>'""');
	    	if($page == 1)
	    	{
		    	$content = iconv("utf-8","gbk","会员名,邮箱,银行账户,手机,提现金额");	    		    	
		    	$content = $content . "\n";
	    	}
	    	
			foreach($list as $k=>$v)
			{
				
				$refund_value['user_name'] = '"' . iconv('utf-8','gbk',$list[$k]['user_name']) . '"';
				$refundr_value['email'] = '"' . iconv('utf-8','gbk',$list[$k]['email']) . '"';
//				$refund_value['ex_real_name'] = '"' . iconv('utf-8','gbk',$list[$k]['ex_real_name']) . '"';
//				$refund_value['ex_account_bank'] = '"' . iconv('utf-8','gbk',$list[$k]['ex_account_bank']) . '"';
//				$refund_value['ex_account_info'] = '"' . iconv('utf-8','gbk',$list[$k]['ex_account_info']) . '"';
//				$refund_value['ex_contact'] = '"' . iconv('utf-8','gbk',$list[$k]['ex_contact']) . '"';
				$refund_value['bank_info'] =  '"' . iconv('utf-8','gbk',get_carray_info($list[$k]['user_bank_id'],$list[$k]['user_id'])) . '"';
				$refund_value['mobile'] = '"' . iconv('utf-8','gbk',$list[$k]['mobile']) . '"';
				$refund_value['money'] = '"' . iconv('utf-8','gbk',$list[$k]['money']) . '"';
				$content .= implode(",", $refund_value) . "\n";
			}	
			
			//
			header("Content-Disposition: attachment; filename=refund_list.csv");
	    	echo $content ;
		}
		else
		{
			if($page==1)
			$this->error(L("NO_RESULT"));
		}	
		
	}
}
?>