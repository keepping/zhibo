<?php
// +----------------------------------------------------------------------
// | Fanwe 方维众筹商业系统
// +----------------------------------------------------------------------
// | Copyright (c) 2011 http://www.fanwe.com All rights reserved.
// +----------------------------------------------------------------------
// | Author: 甘味人生(526130@qq.com)
// +----------------------------------------------------------------------

class UserRefundListAction extends CommonAction{

	/**
	 * 提现审核记录
	 */
	public function index()
	{
		//列表过滤器，生成查询Map对象
		$map = $this->_search ();
		if($_REQUEST['is_pay']!='')
		{
			$condition['is_pay'] = $_REQUEST['is_pay'];
		}
        if($_REQUEST['user_id']>0)
        {
            $condition['user_id'] = $_REQUEST['user_id'];
        }

		//追加默认参数
		if($this->get("default_map"))
			$map = array_merge($map,$this->get("default_map"));
		if(strim($_REQUEST['nick_name'])!=''){//name
			$user=M("User")->where("nick_name like '%".strim($_REQUEST['nick_name'])."%' ")->findAll();
			$user_arr_id = array();
			foreach($user as $k=>$v){
				$user_arr_id[$k] =intval($v['id']);
			}
			//$user_str_id = implode(',',$user_arr_id);
			$condition['user_id'] = array('in',$user_arr_id);
		}
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
		if($refund_data)
		{
			if($refund_data['is_pay']==1)
			{
				$this->error("已经允许提现");
			}

			$reply = strim($_REQUEST['reply']);
			if($status==1){
				$refund_data['is_pay'] = 1;
				$info="允许操作成功";

			}else{
				$refund_data['is_pay'] = 2;
				$info="未允许操作成功";
			}
			$refund_data['reply']=$reply;

			$list = M("UserRefund")->save($refund_data);
            if ($list !== false){
                save_log($refund_data['user_id'].$info,1);
            }else{
                save_log($refund_data['user_id'].$info,0);
            }
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
			$rel_data = M('UserRefund')->where($condition)->findAll();
			$list = M('UserRefund')->where ( $condition )->delete();

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
	//导出电子表
	public function export_csv($page = 1)
	{

		$pagesize = 10;
		set_time_limit(0);
		$limit = (($page - 1)*intval($pagesize)).",".(intval($pagesize));


		//定义条件

		if(trim($_REQUEST['id'])!='')
		{
			$where.= "ur.id = ".intval($_REQUEST['id'])." and ";
		}

		if(trim($_REQUEST['user_id'])!='')
		{
			$where.= " ur.user_id = ".intval($_REQUEST['user_id'])." and ";
		}
		if(trim($_REQUEST['is_pay'])!='')
		{
			$where.= " ur.is_pay = ".intval($_REQUEST['is_pay'])." and ";
		}
        if(trim($_REQUEST['nick_name'])!='')
        {
            $where .= "u.nick_name like '%".trim($_REQUEST['nick_name'])."%' and ";
        }

        $time ='1970-01-01 16:00:00';
        $sql ="select ur.id as user_id,ur.money as money,ur.ticket as ticket,u.nick_name as user_name, FROM_UNIXTIME(ur.create_time+8*3600,'%Y-%m-%d %H:%i:%S') as create_time,ur.memo as memo,ur.is_pay as is_pay, FROM_UNIXTIME(ur.pay_time+8*3600,'%Y-%m-%d %H:%i:%S')  as pay_time,ur.reply as reply,ur.ybdrawflowid as ybdrawflowid,ur.pay_log as pay_log from ".DB_PREFIX."user as u INNER JOIN ".DB_PREFIX."user_refund as ur on ur.user_id = u.id where ".$where." 1=1 limit ".$limit;
        $list=$GLOBALS['db']->getAll($sql);
		if($list)
		{
			register_shutdown_function(array(&$this, 'export_csv'), $page+1);
            $m_config = load_auto_cache('m_config');
            $ticket_name = $m_config['ticket_name']!=''?$m_config['ticket_name']:'印票';
			$refund_value = array( 'user_id'=>'""', 'money'=>'""', 'ticket'=>'""','user_name'=>'""','create_time'=>'""','memo'=>'""','is_pay'=>'""','pay_time'=>'""','reply'=>'""','ybdrawflowid'=>'""','pay_log'=>'""');
			if($page == 1)
			{
				$content = iconv("utf-8","gbk","提现编号,提现金额,$ticket_name,主播昵称,申请时间,申请备注,是否审核,确认支付时间,操作备注,业务单号,支付备注");
				$content = $content . "\n";
			}

			foreach($list as $k=>$v)
			{

				$refund_value['user_id'] = '"' . iconv('utf-8','gbk',$list[$k]['user_id']) . '"';
				$refund_value['money'] = '"' . iconv('utf-8','gbk',$list[$k]['money']) . '"';
				$refund_value['ticket'] = '"' . iconv('utf-8','gbk',$list[$k]['ticket']) . '"';
				$refund_value['user_name'] = '"' . iconv('utf-8','gbk',$list[$k]['user_name']) . '"';
				$refund_value['create_time'] = '"' . iconv('utf-8','gbk',$list[$k]['create_time']) . '"';
				$refund_value['memo'] = '"' . iconv('utf-8','gbk',$list[$k]['memo']) . '"';
				$refund_value['is_pay'] = '"' . iconv('utf-8','gbk',get_status($list[$k]['is_pay'])) . '"';
				$refund_value['pay_time'] = '"' . iconv('utf-8','gbk',$list[$k]['pay_time']) . '"';
                $refund_value['pay_time'] = str_replace($time,'0',$list[$k]['pay_time']);
				$refund_value['reply'] = '"' . iconv('utf-8','gbk',$list[$k]['reply']) . '"';
				$refund_value['ybdrawflowid'] = '"' . iconv('utf-8','gbk',$list[$k]['ybdrawflowid']) . '"';
				$refund_value['pay_log'] = '"' . iconv('utf-8','gbk',$list[$k]['pay_log']) . '"';
//				$refund_value['bank_info'] =  '"' . iconv('utf-8','gbk',get_carray_info($list[$k]['user_bank_id'],$list[$k]['user_id'])) . '"';
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