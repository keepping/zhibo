<?php

class m_config_auto_cache extends auto_cache{
	private $key = "m_config:list";
	public function load($param,$is_real=true)
	{
		$m_config = $GLOBALS['cache']->get($this->key);
		if($m_config === false)
		{
			$m_config = array();
			$sql = "select code,val from ".DB_PREFIX."m_config";
			$list = $GLOBALS['db']->getAll($sql);
			foreach($list as $item){
				$m_config[$item['code']] = $item['val'];
			}
			//print_r($list);
			$GLOBALS['cache']->set($this->key,$m_config,20,true);
		}
		
		return $m_config;
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