<?php
// +----------------------------------------------------------------------
// | Fanwe 方维众筹商业系统
// +----------------------------------------------------------------------
// | Copyright (c) 2011 http://www.fanwe.com All rights reserved.
// +----------------------------------------------------------------------
// | Author: 甘味人生(526130@qq.com)
// +----------------------------------------------------------------------

class WeiboRefundAction extends CommonAction{

	/**
	 * 提现审核记录
     */
	public function index()
	{
		//列表过滤器，生成查询Map对象
		$map = $this->_search ();
		$map['_string'] = 'is_pay = 0';
		//追加默认参数
		if($this->get("default_map"))
		$map = array_merge($map,$this->get("default_map"));
		$map['type'] = 1;
        if(strim($_REQUEST['nick_name'])!=''){//name
            $user=M("User")->where("nick_name like '%".strim($_REQUEST['nick_name'])."%' ")->findAll();
            $user_arr_id = array();
            foreach($user as $k=>$v){
                $user_arr_id[$k] =intval($v['id']);
            }
            //$user_str_id = implode(',',$user_arr_id);
            $map['user_id'] = array('in',$user_arr_id);
        }
        if(intval($_REQUEST['user_id'])>0)
        {
            $map['user_id'] = intval($_REQUEST['user_id']);
        }
		if (method_exists ( $this, '_filter' )) {
			$this->_filter ( $map );
		}
		$name=$this->getActionName();
		$model = D ($name);
		if (! empty ( $model )) {
			$this->_list ( $model, $map );
		}
		$this->display ();
		return;
	}

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
		$this->assign("info",$info);
		$this->assign("refund_data",$refund_data);
		$this->assign("status",$status);
		$this->display ();
	}
	public function refund_go_allow(){
		$id=intval($_REQUEST['id']);
		$status=intval($_REQUEST['status']);
		$refund_data = M("UserRefund")->getById($id);
		$user_id = $refund_data['user_id'];
		if($refund_data)
		{
			if($refund_data['is_pay']==1)
			{
				$this->error("已经允许提现");
			}

			$reply = strim($_REQUEST['reply']);
			$m_config =  load_auto_cache("m_config");//初始化手机端配置
			$ticket_name = $m_config['ticket_name']!=''?$m_config['ticket_name']:'印票';
			//管理员的SESSION
			$adm_session = es_session::get(md5(conf("AUTH_KEY")));
			$adm_id = intval($adm_session['adm_id']);
			if($status==1){

	        	//修改读取本地mysql 20170118
	        	$refund_user = $GLOBALS['db']->getRow("select weibo_money,weibo_refund_money   from ".DB_PREFIX."user where id=".$user_id);

				if(intval($refund_user['weibo_money'])-intval($refund_user['weibo_refund_money'])<intval($refund_data['weibo_money']))
				{
					$refund_data['pay_log'] = "动态红包不足，不能提现";
					$refund_data['is_pay'] = 4;
					M("UserRefund")->save($refund_data);
					$info="动态红包不足不足，不能提现";
				}else{
					//冻结用户提现印票
					$sql = "update ".DB_PREFIX."user set weibo_refund_money=weibo_refund_money+".$refund_data['weibo_money']." where weibo_money >= weibo_refund_money + ".$refund_data['weibo_money']." and id=".$refund_data['user_id'];
					$GLOBALS['db']->query($sql);
					if($GLOBALS['db']->affected_rows()){
						user_deal_to_reids(array($user_id));
						$refund_data['is_pay'] = 1;
						$refund_data['pay_log']="已审核";
						$info="允许操作成功";
						//写入用户日志
						$data = array();
						$data['weibo_money'] = intval($refund_data['weibo_money']);
						$data['log_admin_id'] = intval($adm_id);
						$param['type'] = 1;//类型 0表示充值 1表示提现 2赠送道具 3 兑换印票
						$log_msg ='冻结提现'.$data['weibo_money'].'动态红包成功';
						account_log_com($data,$refund_data['user_id'],$log_msg,$param);
						save_log($user_id.$log_msg,1);
					}else{
						$refund_data['pay_log']="提现冻结失败";
					}
				}

			}else{
				$refund_data['is_pay'] = 2;
				$refund_data['pay_log']="--";
				$refund_data['partner_trade_no']="--";
				$info="拒绝提现操作成功";
				 //写入用户日志
                $data = array();
                $data['weibo_money'] = intval($refund_data['weibo_money']);
                $data['log_admin_id'] = intval($adm_id);
                $param['type'] = 1;//类型 0表示充值 1表示提现 2赠送道具 3 兑换印票
                $log_msg ='提现动态红包'.$data['weibo_money'].',被拒绝';
                account_log_com($data,$refund_data['user_id'],$log_msg,$param);
                save_log($user_id.$log_msg,0);
			}
			$refund_data['reply']=$reply;
			M("UserRefund")->save($refund_data);
			$GLOBALS['msg']->manage_msg('MSG_MONEY_CARRY_RESULT',$refund_data['user_id'],$refund_data);
			$this->success($info);

		}else{
			$this->error("没有提现数据");
		}

	}

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
					$info[] = "[id:".$data['id'].",ticket:".$data['ticket']."]";
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
	
	public function refund_check() {
        $user_id=intval($_REQUEST['user_id']);
        $sql = "select u.id, u.nick_name, b.`max_ticket` , u.ticket as 'actual_ticket', (u.ticket - b.`max_ticket`) as 'differ',  u.refund_ticket, u.ticket - u.refund_ticket as 'surplus_ticket' from (select to_user_id, sum(total_ticket) as 'max_ticket' from (select to_user_id,sum(total_ticket) as total_ticket from ".DB_PREFIX."video_prop group by to_user_id";

        $table = DB_PREFIX . 'live_pay_log';
        $sql .= $this->refund_check_tablename_exist($table);

        $table = DB_PREFIX . 'live_pay_log_history';
        $sql .= $this->refund_check_tablename_exist($table);

        //家族、分享
        $table = DB_PREFIX . 'user_log';
        $sql .= $this->refund_check_tablename_exist_user_log($table);

        //分销
        $table = DB_PREFIX . 'distribution_log';
        $sql .= $this->refund_check_tablename_exist_distribution_log($table);

        $i = 0;
        do{
            $lastday=date('Ym',strtotime(date('Ym',NOW_TIME)." -".$i." month"));
            $sql1 = $this->refund_check_tablename_exist1($lastday);
            if($sql1!=''){
                $sql .= $sql1;
            }
            $i++;
        }while($sql1!=''&&$i<12);

        $sql .= " ) as a where a.to_user_id group by to_user_id ) b LEFT JOIN ".DB_PREFIX."user u on u.id = b.to_user_id  where u.id=".$user_id." order by (u.ticket - b.`max_ticket`) desc";

        $list = $GLOBALS['db']->getAll($sql);
        $this->assign("list",$list);
        $this->display ();
	}

    public function refund_check_tablename_exist_distribution_log($table){
        $res = $GLOBALS['db']->getRow("SHOW TABLES LIKE'$table'");
        $sql = '';
        if($res){
            $sql = " UNION ALL select to_user_id,sum(ticket) as total_ticket from ".$table." group by to_user_id";
        }
        return $sql;
    }

    public function refund_check_tablename_exist_user_log($table){
        $res = $GLOBALS['db']->getRow("SHOW TABLES LIKE'$table'");
        $sql = '';
        if($res){
            $sql = " UNION ALL select user_id as to_user_id,sum(ticket) as total_ticket from ".$table." where type=4 and (log_info like '%收取家族收益%' or log_info like '%分享%') group by to_user_id";
        }
        return $sql;
    }
	
	public function refund_check_tablename_exist($table) {
    	$res = $GLOBALS['db']->getRow("SHOW TABLES LIKE'$table'");
    	$sql = '';
    	if($res){
    		$sql = " UNION ALL select to_user_id, sum(total_ticket) from ".$table." group by to_user_id";
    	}    	
    	return $sql;
	}
	public function refund_check_tablename_exist1($dayname='') {
        if($dayname==''){
            $dayname = date('Ym',NOW_TIME);
        }
        $table = DB_PREFIX . 'video_prop_' . $dayname;
        if($table!=''){
            $res = $GLOBALS['db']->getRow("SHOW TABLES LIKE'$table'");
        }else{
            $res = 0 ;
        }
        $sql = '';
        if($res){
            $sql = " UNION ALL select to_user_id, sum(total_ticket) from ".$table." where is_red_envelope=0 group by to_user_id";
        }
        return $sql;
	}
}
?>