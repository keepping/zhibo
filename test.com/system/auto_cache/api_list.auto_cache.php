<?php

class api_list_auto_cache extends auto_cache{
	private $key = "api:list";
	public function load($param)
	{
		$list = $GLOBALS['cache']->get($this->key);

		if($list === false)
		{

			$sql = "select a.ctl_act,g.api_url as api,a.has_cookie from ".DB_PREFIX."api_list a left JOIN ".DB_PREFIX."slb_group g on g.id = a.slb_group_id where g.is_effect = 1 and g.id > 0";
			$list = $GLOBALS['db']->getAll($sql,true,true);
			$GLOBALS['cache']->set($this->key,$list);
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