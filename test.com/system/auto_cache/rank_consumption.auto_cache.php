<?php

class rank_consumption_auto_cache extends auto_cache{
	private $key = "rank:consumption:";
	public function load($param)
	{
		$rank_name = strim($param['rank_name']);
		$table = strim($param['table']);
		$page = intval($param['page']);
		$page_size = intval($param['page_size']);
		$cache_time = strim($param['cache_time']);
		$limit = (($page - 1) * $page_size) . "," . $page_size;

		$this->key .= $rank_name . '_' . $page;
	
		$key_bf = $this->key.'_bf';
		
		$list = $GLOBALS['cache']->get($this->key,true);

		if ($list === false) {
			$is_ok =  $GLOBALS['cache']->set_lock($this->key);
			if(!$is_ok){
				$list = $GLOBALS['cache']->get($key_bf,true);
			}else{
				if($rank_name=='day'){//day
					$sql ="select u.id as user_id,u.nick_name,u.v_type,u.v_icon,u.head_image,u.sex,u.user_level,sum(v.total_ticket) as ticket,u.is_authentication
											from  ".$table." as v LEFT JOIN ".DB_PREFIX."user as u  on u.id = v.to_user_id
											where u.is_effect=1 and v.create_ym=".to_date(NOW_TIME,'Ym')." and v.create_d=".to_date(NOW_TIME,'d')." and v.is_red_envelope = 0 GROUP BY v.to_user_id
											order BY sum(v.total_ticket) desc limit ".$limit;
				}elseif($rank_name=='month'){//month
					$sql = "select u.id as user_id,u.nick_name,u.v_type,u.v_icon,u.head_image,u.sex,u.user_level,sum(v.total_ticket) as ticket,u.is_authentication
											from ".$table." as v LEFT JOIN ".DB_PREFIX."user as u on u.id = v.to_user_id
											where u.is_effect=1 and v.create_ym=".to_date(NOW_TIME,'Ym')." and v.is_red_envelope = 0 GROUP BY v.to_user_id
											order BY sum(v.total_ticket) desc limit ".$limit;
				}else{//all
					$sql = "select u.id as user_id,u.nick_name,u.v_type,u.v_icon,u.head_image,u.sex,u.user_level,u.ticket as ticket,u.is_authentication
											from ".DB_PREFIX."user as u
											where u.is_effect=1 and u.ticket>0
											order BY u.ticket desc limit ".$limit;
				}
				
				$list=$GLOBALS['db']->getAll($sql,true,true);
				
				if($rank_name=='day'){
					$GLOBALS['cache']->set($this->key, $list, $cache_time, true);//缓存时间 1800秒 
					$GLOBALS['cache']->set($key_bf, $list, 86400, true);//备份
				}elseif($rank_name=='month'){
					$GLOBALS['cache']->set($this->key, $list, $cache_time, true);//缓存时间 28800秒 8h
					$GLOBALS['cache']->set($key_bf, $list, 86400, true);//备份
				}else{
					$GLOBALS['cache']->set($this->key, $list, $cache_time, true);//缓存时间 86400秒 24h
					$GLOBALS['cache']->set($key_bf, $list, 86400, true);//备份
				}
			}
 		}
 		
 		if ($list == false) $list = array();
 		
		return $list;
	}
	
	public function rm()
	{

		$GLOBALS['cache']->clear_by_name($this->key);
	}
	
	public function clear_all()
	{
		
		$GLOBALS['cache']->clear_by_name($this->key);
	}
}
?>