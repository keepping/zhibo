<?php

class pay_list_all_auto_cache extends auto_cache{
	private $key = "pay:list:all";
	public function load($param)
	{
		$list = $GLOBALS['cache']->get($this->key);
		if(intval($param['id'])>0||!$list)
		{
			$sql = "select id,name,class_name,logo from ".DB_PREFIX."payment where is_effect = 1 and online_pay in (3,4) and class_name like  '%Aliapp%' and id>".intval($param['id'])." order by sort limit 1";
			$lists = $GLOBALS['db']->getAll($sql,true,true);
			if(!$lists){
				$sql = "select id,name,class_name,logo from ".DB_PREFIX."payment where is_effect = 1 and online_pay in (3,4) and class_name like  '%Aliapp%' and id>0 order by sort limit 1";
				$lists = $GLOBALS['db']->getAll($sql,true,true);
			}
			foreach ( $lists as $k => $v )
			{
				$lists[$k]['logo'] = get_spec_image($v['logo']);
			}
			$GLOBALS['cache']->set($this->key,$lists);
		}
		if(!$list)$list=$lists;
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