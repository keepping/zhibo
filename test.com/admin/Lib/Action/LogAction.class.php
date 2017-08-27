<?php
// +----------------------------------------------------------------------
// | Fanwe 方维众筹商业系统
// +----------------------------------------------------------------------
// | Copyright (c) 2011 http://www.fanwe.com All rights reserved.
// +----------------------------------------------------------------------
// | Author: 甘味人生(526130@qq.com)
// +----------------------------------------------------------------------

class LogAction extends CommonAction{
	public function index()
	{
		if(trim($_REQUEST['log_info'])!='')
		{
			$map['log_info'] = array('like','%'.trim($_REQUEST['log_info']).'%');			
		}
		
		$log_begin_time  = trim($_REQUEST['log_begin_time'])==''?0:to_timespan($_REQUEST['log_begin_time']);
		$log_end_time  = trim($_REQUEST['log_end_time'])==''?0:to_timespan($_REQUEST['log_end_time']);
		if($log_end_time==0)
		{
			$map['log_time'] = array('gt',$log_begin_time);	
		}
		else
		$map['log_time'] = array('between',array($log_begin_time,$log_end_time));	
		
		
		$this->assign("default_map",$map);
		parent::index();
	}
	public function foreverdelete() {
		/*//彻底删除指定记录
		$ajax = intval($_REQUEST['ajax']);
		$id = $_REQUEST ['id'];
		if (isset ( $id )) {
				$condition = array ('id' => array ('in', explode ( ',', $id ) ) );			
				
				$list = M(MODULE_NAME)->where ( $condition )->delete();
				if ($list!==false) {
					save_log(l("FOREVER_DELETE_SUCCESS"),1);
					$this->success (l("FOREVER_DELETE_SUCCESS"),$ajax);
				} else {
                    save_log(l("FOREVER_DELETE_FAILED"),0);
					$this->error (l("FOREVER_DELETE_FAILED"),$ajax);
				}
			} else {
				$this->error (l("INVALID_OPERATION"),$ajax);
		}*/
	}
	public function claer_log() {
		//彻底删除指定记录
		$ajax = intval($_REQUEST['ajax']);

		$table = DB_PREFIX . 'log_history';
   		$res = $GLOBALS['db']->getRow("SHOW TABLES LIKE'$table'");
		if (!$res) {
			$sql= "CREATE TABLE `$table` (
					  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'ID',
					  `log_info` text NOT NULL COMMENT '日志信息',
					  `log_time` int(11) NOT NULL COMMENT '日志时间',
					  `log_admin` int(11) NOT NULL COMMENT '日志管理',
					  `log_ip` varchar(255) NOT NULL COMMENT '日志IP',
					  `log_status` tinyint(1) NOT NULL COMMENT '日志状态',
					  `module` varchar(255) NOT NULL COMMENT '模块',
					  `action` varchar(255) NOT NULL COMMENT '方法',
					  PRIMARY KEY (`id`)
				) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COMMENT='系统日志历史表';";
			$res = $GLOBALS['db']->query($sql);
		}
		$fields = " log_info,log_time,log_admin,log_ip,log_status,module,action ";
		$sql = "insert into ".DB_PREFIX."log_history(".$fields.") select ".$fields." from ".DB_PREFIX."log ";

		$res = $GLOBALS['db']->query($sql);

		if ($res!==false) {
			$sql= "delete from  ".DB_PREFIX."log ";
			$GLOBALS['db']->query($sql);
			save_log('清除日志成功',1);
			$this->success ('清除日志成功',$ajax);
		} else {
			save_log('清除日志失败',0);
			$this->error ('清除日志失败',$ajax);
		}
	}

}
?>