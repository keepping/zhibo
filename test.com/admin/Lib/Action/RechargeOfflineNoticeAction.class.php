<?php
// +----------------------------------------------------------------------
// | Fanwe 方维众筹商业系统
// +----------------------------------------------------------------------
// | Copyright (c) 2011 http://www.fanwe.com All rights reserved.
// +----------------------------------------------------------------------
// | Author: 甘味人生(526130@qq.com)
// +----------------------------------------------------------------------

class RechargeOfflineNoticeAction extends CommonAction{
	
	public function com_search(){
		$map = array ();
	/*
		if (!isset($_REQUEST['end_time']) || $_REQUEST['end_time'] == '') {
			$_REQUEST['end_time'] = to_date(get_gmtime(), 'Y-m-d');
		}
		
		if (!isset($_REQUEST['start_time']) || $_REQUEST['start_time'] == '') {
			$_REQUEST['start_time'] = dec_date($_REQUEST['end_time'], 7);
		}
	
		$map['start_time'] = trim($_REQUEST['start_time']);
		$map['end_time'] = trim($_REQUEST['end_time']);
		$map['is_paid'] = 1;
	
		$this->assign("start_time",$map['start_time']);
		$this->assign("end_time",$map['end_time']);
	*/
		$map['start_time'] = trim($_REQUEST['start_time']);
		$map['end_time'] = trim($_REQUEST['end_time']);
		
		if ($map['start_time'] == ''&&$map['end_time'] != ''){
			$this->error('开始时间 不能为空');
			exit;
		}
	
		if ($map['start_time'] != ''&&$map['end_time'] == ''){
			$this->error('结束时间 不能为空');
			exit;
		}
		
		if ($map['start_time'] != ''&&$map['end_time'] != '') {
			$d = explode('-',$map['start_time']);
			if (checkdate($d[1], $d[2], $d[0]) == false){
				$this->error("开始时间不是有效的时间格式:{$map['start_time']}(yyyy-mm-dd)");
				exit;
			}
			
			$d = explode('-',$map['end_time']);
			if (checkdate($d[1], $d[2], $d[0]) == false){
				$this->error("结束时间不是有效的时间格式:{$map['end_time']}(yyyy-mm-dd)");
				exit;
			}
			
			if (to_timespan($map['start_time']) > to_timespan($map['end_time'])){
				$this->error('开始时间不能大于结束时间:'.$map['start_time'].'至'.$map['end_time']);
				exit;
			}
			
			$q_date_diff = 31;
			$this->assign("q_date_diff",$q_date_diff);
			if ($q_date_diff > 0 && (abs(to_timespan($map['end_time']) - to_timespan($map['start_time'])) / 86400  + 1 > $q_date_diff)){
				$this->error("查询时间间隔不能大于  {$q_date_diff} 天");
				exit;
			}
			
			$map['start_time']=to_timespan($map['start_time'])+app_conf('TIME_ZONE')*3600;
			$map['end_time']=to_timespan($map['end_time'])+86399+app_conf('TIME_ZONE')*3600;
		}else{
			$map = array ();
		}	
		
		return $map;
	}
	
	
	public function index()
	{
		$map = $this->com_search();
		if(trim($_REQUEST['notice_sn'])!='')
		{
			$condition['notice_sn'] = $_REQUEST['notice_sn'];
		}		
		/*$payment_id = M("Payment")->where("class_name = 'Otherpay'")->getField("id");
		$condition['payment_id'] = $payment_id;*/
		$condition['payment_id'] = 0;

		if($map['start_time'] != '' && $map['end_time']!= ''){
			$condition['create_time']= array("between",array($map['start_time'],$map['end_time']));
			
			unset($map['start_time']);
			unset($map['end_time']);
		}
		if($_REQUEST['is_paid']!=''&&intval($_REQUEST['is_paid'])==0)
		{
			$condition['is_paid']=0;
		}elseif (intval($_REQUEST['is_paid'])==1){
			$condition['is_paid']=1;
		}
		
		
		if(intval($_REQUEST['is_paid'])==-1 || !isset($_REQUEST['is_paid']))unset($_REQUEST['is_paid']);
		
		$map = array_merge($map,$condition);
		
		if (method_exists ( $this, '_filter' )) {
			$this->_filter ( $map );
		}
		$model = D ('PaymentNotice');
		if (! empty ( $model )) {
			$this->_list ( $model, $map );
		}
		
		//print_r($condition);exit();
		$this->display ();
		return;
	
	}
	
	
	public function delete() {
		//彻底删除指定记录
		$ajax = intval($_REQUEST['ajax']);
		$id = $_REQUEST ['id'];
		if (isset ( $id )) {
				$condition = array ('id' => array ('in', explode ( ',', $id ) ) );			
				$rel_data = M(PaymentNotice)->where($condition)->findAll();				
				$list = M(PaymentNotice)->where ( $condition )->delete();		
				
				foreach($rel_data as $data)
				{
					$info[] = "[单号:".$data['notice_sn']."]";						
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
	
	

}
?>