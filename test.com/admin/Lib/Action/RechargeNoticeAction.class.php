<?php
// +----------------------------------------------------------------------
// | Fanwe 方维众筹商业系统
// +----------------------------------------------------------------------
// | Copyright (c) 2011 http://www.fanwe.com All rights reserved.
// +----------------------------------------------------------------------
// | Author: 甘味人生(526130@qq.com)
// +----------------------------------------------------------------------

class RechargeNoticeAction extends CommonAction{
	
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
	
	/*public function index()
	{		
		//列表过滤器，生成查询Map对象
		//$map = $this->_search ();
		$map = $this->com_search();
		
		if(trim($_REQUEST['notice_sn'])!='')
		{
			$condition['notice_sn'] = $_REQUEST['notice_sn'];
		}
		if(intval($_REQUEST['is_paid'])==0)
		{
			$condition['is_paid']=0;
		}elseif (intval($_REQUEST['is_paid'])==1){
			$condition['is_paid']=1;
		}
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
	}*/
	
	public function index()
	{
	
		$map = $this->com_search();
		//按会员名查询
		if(trim($_REQUEST['nick_name'])!='')
        {
			$user=M("User")->where("nick_name like '%".trim($_REQUEST['nick_name'])."%' ")->findAll();
			foreach($user as $k=>$v){
				$user_arr_id[$k] =intval($v['id']);
			}
			$user_str_id = implode(',',$user_arr_id);
			$condition['user_id'] = array('in',$user_str_id);
        }

		if(trim($_REQUEST['notice_sn'])!='')
		{
			$condition['notice_sn'] = array('like','%'.trim($_REQUEST['notice_sn']).'%');
		}
        if(intval($_REQUEST['user_id'])>0)
        {
            $condition['user_id'] = intval($_REQUEST['user_id']);
        }
	
		/*if($map['start_time'] != '' && $map['end_time'] && ( !isset($_REQUEST['is_paid']) || intval($_REQUEST['is_paid'])==-1 || intval($_REQUEST['is_paid'])==1 ) ){
			if(intval($_REQUEST['is_paid'])==1)
				$condition['pay_time']= array("between",array($map['start_time'],$map['end_time']));
			else
				$condition['create_time']= array("between",array($map['start_time'],$map['end_time']));
		}
	
		if(intval($_REQUEST['is_paid'])==0)
		{
			$condition['create_time']= array("between",array($map['start_time'],$map['end_time']));
		}*/
		
		if($map['start_time'] != '' && $map['end_time'] != ''){
			if(intval($_REQUEST['is_paid'])==1)
				$condition['pay_time']= array("between",array($map['start_time'],$map['end_time']));
			else
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

        $m_config =  load_auto_cache("m_config");//初始化手机端配置
        if((defined('OPEN_VIP')&&OPEN_VIP==1)&&intval($m_config['open_vip'])==1){
            if($_REQUEST['type']!='')
            {
                $condition['type'] = intval($_REQUEST['type']);
            }
            $this->assign ( 'open_vip', 1 );
        }
	
	
		$payment_id = M("Payment")->where("class_name = 'Otherpay' ")->getField("id");
		$condition['payment_id'] = array("neq",intval($payment_id));
	
		if(intval($_REQUEST['payment_id'])==0){
			unset($_REQUEST['payment_id']);
		}
		else{
			$condition['payment_id'] = array("eq",intval($_REQUEST['payment_id']));
		}
		if(intval($_REQUEST['is_paid'])==-1 || !isset($_REQUEST['is_paid']))
			unset($_REQUEST['is_paid']);

		$this->assign("payment_list",M("Payment")->findAll());
		$map = array_merge($map,$condition);
		
		if (method_exists ( $this, '_filter' )) {
			$this->_filter ( $map );
		}
		$model = D ('PaymentNotice');
		if (! empty ( $model )) {
			$this->_list ( $model, $map );
		}
		//print_r($map);exit();
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
    //导出电子表
    public function export_csv($page = 1)
    {

        $pagesize = 10;
        set_time_limit(0);
        $limit = (($page - 1)*intval($pagesize)).",".(intval($pagesize));


        //定义条件

        if(trim($_REQUEST['nick_name'])!='')
        {
            $where .= "u.nick_name like '%".trim($_REQUEST['nick_name'])."%' and ";
        }
        if(trim($_REQUEST['notice_sn'])!='')
        {
            $where .= "p.notice_sn like '%".intval($_REQUEST['notice_sn'])."%' and ";
        }
        if(trim($_REQUEST['payment_id'])!=''&& trim($_REQUEST['payment_id']!=0))
        {
            $where .= "p.payment_id =".intval($_REQUEST['payment_id'])." and ";
        }
        if(trim($_REQUEST['is_paid'])!=''&&trim($_REQUEST['is_paid'])!= -1)
        {
            $where .= "p.is_paid =".intval($_REQUEST['is_paid'])." and ";
        }

        $map['start_time']=to_timespan($_REQUEST['start_time'])+app_conf('TIME_ZONE')*3600;

        $map['end_time']=to_timespan($_REQUEST['end_time'])+86399+app_conf('TIME_ZONE')*3600;

        if($_REQUEST['start_time'] != '' && $_REQUEST['end_time'] != ''){
            $where .="p.create_time between '". $map['start_time']. "' and '". $map['end_time'] ."' and ";
        }

        $time ='1970-01-01 16:00:00';

        $sql ="select p.id as id,p.notice_sn as notice_sn ,p.recharge_name as recharge_name ,p.outer_notice_sn as outer_notice_sn ,u.nick_name as nick_name,t.name as name,FROM_UNIXTIME(p.create_time+8*3600,'%Y-%m-%d %H:%i:%S') as create_time,FROM_UNIXTIME(p.pay_time+8*3600,'%Y-%m-%d %H:%i:%S') as pay_time,
              p.money as money,p.is_paid as is_paid,p.payment_id as payment_id from fanwe_payment_notice as p LEFT JOIN fanwe_user as u on u.id = p.user_id LEFT JOIN fanwe_payment as t on t.id =p.payment_id where ".$where." 1=1 order by create_time desc  limit ".$limit;


        $list=$GLOBALS['db']->getAll($sql);
        if($list)
        {
            register_shutdown_function(array(&$this, 'export_csv'), $page+1);

            $refund_value = array( 'id'=>'""', 'notice_sn'=>'""', 'recharge_name'=>'""','outer_notice_sn'=>'""','nick_name'=>'""','name'=>'""','create_time'=>'""','pay_time'=>'""','money'=>'""','is_paid'=>'""');
            if($page == 1)
            {
                $content = iconv("utf-8","gbk","充值编号,付款单号,项目名称,外部单号,主播昵称,支付方式,创建时间,支付时间,金额,是否支付");
                $content = $content . "\n";
            }


            foreach($list as $k=>$v)
            {
                $refund_value['id'] = '"' . iconv('utf-8','gbk',$list[$k]['id']) . '"';
                $refund_value['notice_sn']= $list[$k]['notice_sn']."\t";
                $refund_value['outer_notice_sn']= $list[$k]['outer_notice_sn']."\t";
                $refund_value['recharge_name'] = '"' . iconv('utf-8','gbk',$list[$k]['recharge_name']) . '"';
                $refund_value['nick_name'] = '"' . iconv('utf-8','gbk',$list[$k]['nick_name']) . '"';
                $refund_value['name'] = '"' . iconv('utf-8','gbk',$list[$k]['name']) . '"';
                $refund_value['create_time'] = '"' . iconv('utf-8','gbk',$list[$k]['create_time']) . '"';
                $refund_value['pay_time'] = '"' . iconv('utf-8','gbk',$list[$k]['pay_time']) . '"';
                $refund_value['pay_time'] = str_replace($time,'0',$list[$k]['pay_time']);
                $refund_value['money'] = '"' . iconv('utf-8','gbk',$list[$k]['money']) . '"';
                if($list[$k]['is_paid']==1){
                    $refund_value['is_paid']=iconv('utf-8','gbk','是') . '';
                }else{
                    $refund_value['is_paid']=iconv('utf-8','gbk','否') . '';
                }

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