<?php

class mobile_code_auto_cache extends auto_cache{
	private $key = "mobile_code:list";
	public function load($param)
	{
		$list = $GLOBALS['cache']->get($this->key);

		if($list === false)
		{
			//unserialize(
			$sql = "select id,country,area_code from ".DB_PREFIX."national_telephone_code where is_effect = 1 order by sort";
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