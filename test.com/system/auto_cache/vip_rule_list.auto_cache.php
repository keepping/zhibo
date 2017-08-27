<?php

class vip_rule_list_auto_cache extends auto_cache{
	private $key = "vip:rule:list";
	public function load($param)
	{
		$list = $GLOBALS['cache']->get($this->key);

		if($list === false)
		{
			//unserialize(
			$sql = "select id,name,money,day_num,iap_money from ".DB_PREFIX."vip_rule where is_effect = 1 order by sort";
			$list = $GLOBALS['db']->getAll($sql,true,true);
            foreach($list as $k=>$v){
                $list[$k]['day_num'] = $v['day_num'].'天';
            }

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