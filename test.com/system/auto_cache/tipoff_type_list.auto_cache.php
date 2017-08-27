<?php

class tipoff_type_list_auto_cache extends auto_cache{
	private $key = "tipoff_type:list";
	public function load($param)
	{
		$list = $GLOBALS['cache']->get($this->key);

		if($list === false)
		{
			$sql = "select id,name from ".DB_PREFIX."tipoff_type where is_effect = 1 order by id desc";
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