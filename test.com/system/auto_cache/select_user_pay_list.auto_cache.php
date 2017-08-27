<?php

class select_user_pay_list_auto_cache extends auto_cache{
	private $key = "select:user_pay_list:";
	public function load($param)
	{

		$user_id = intval($param['user_id']);
		$page=$param['page']>0?$param['page']:1;
		$page_size=$param['page_size']>0?$param['page_size']:20;
		$limit = (($page-1) * $page_size) . "," . $page_size;

		$this->key .= md5(serialize($param));
		$key_bf = $this->key.'_bf';

		$list = $GLOBALS['cache']->get($this->key,true);

		if ($list === false) {
			$is_ok =  $GLOBALS['cache']->set_lock($this->key);
			if(!$is_ok){
				$list = $GLOBALS['cache']->get($key_bf,true);
			}else{
				$order_list_array = array();
				$diggs_array = array();
				$list = array();
				$order_list = $GLOBALS['db']->getAll("select order_id from ".DB_PREFIX."payment_notice where user_id = ".$user_id." and  is_paid =1 and type in 11");
				if(count($order_list)>0){
					foreach($order_list as $k=>$v){
						$order_list_array[] = $v['order_id'];
					}
				}
				$list['order'] = $order_list_array;

				$diggs  = $GLOBALS['db']->getAll("select weibo_id from ".DB_PREFIX."weibo_comment where user_id = ".$user_id." and type = 2");
				if(count($diggs)>0){
					foreach($diggs as $k=>$v){
						$diggs_array[] = $v['weibo_id'];
					}
				}
				$list['digg'] = $diggs_array;

				$GLOBALS['cache']->set($this->key, $list, 10, true);
				
				$GLOBALS['cache']->set($key_bf, $list, 86400, true);//备份
				//echo $this->key;
			}
 		}
 		
 		if ($list == false) $list = array();
 		
		return $list;
	}
	
	public function rm()
	{

		//$GLOBALS['cache']->clear_by_name($this->key);
	}
	
	public function clear_all()
	{
		
		//$GLOBALS['cache']->clear_by_name($this->key);
	}
	public function time_tran($the_time)
	{
		$now_time = to_date(NOW_TIME,"Y-m-d H:i:s");
		$now_time = to_timespan($now_time);
		$show_time = to_timespan($the_time);
		$dur = $now_time - $show_time;
		if ($dur < 0) {
			return to_date($show_time,"Y-m-d");
		} else {
			if ($dur < 60) {
				return $dur . '秒前';
			} else {
				if ($dur < 3600) {
					return floor($dur / 60) . '分钟前';
				} else {
					if ($dur < 86400) {
						return floor($dur / 3600) . '小时前';
					} else {
						if ($dur < 2592000) {//30天内
							return floor($dur / 86400) . '天前';
						} else {
							return to_date($show_time,"Y-m-d");
						}
					}
				}
			}
		}
	}

}
?>