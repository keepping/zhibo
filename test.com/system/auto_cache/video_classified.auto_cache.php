<?php

class video_classified_auto_cache extends auto_cache{
	private $key = "video:classified";
	
	public function load($param)
	{
		$list = $GLOBALS['cache']->get($this->key);

		if($list === false)
		{
            $sql = "select id as classified_id,title from ".DB_PREFIX."video_classified where is_effect = 1 order by sort desc";
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