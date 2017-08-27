<?php

class edu_deal_support_users_auto_cache extends auto_cache{
	private $key = "edu:deal:support:users:";
	
	public function load($params = array(), $is_real)
	{

        $this->key .= md5(serialize($param));
        $list = $GLOBALS['cache']->get($this->key);

        $key_bf = $this->key . '_bf';
		
		if ($list === false || !$is_real) {
			$is_ok =  $GLOBALS['cache']->set_lock($this->key);
			if(!$is_ok){
				$list = $GLOBALS['cache']->get($key_bf,true);
			}else{
                $deal_id=intval($params['deal_id']);

				$sql = "select u.id,u.nick_name from ".DB_PREFIX."user as u where  order by id asc";
				

			
				$GLOBALS['cache']->set($this->key, $list, 3600, true);
				
				$GLOBALS['cache']->set($key_bf, $list, 86400, true);//备份
				//echo $this->key;
			}
 		}
		
		return $list;
	}
	
	public function rm($param)
	{
		$GLOBALS['cache']->rm($this->key);
	}
	
	public function clear_all()
	{
		$GLOBALS['cache']->rm($this->key);
	}
}
?>