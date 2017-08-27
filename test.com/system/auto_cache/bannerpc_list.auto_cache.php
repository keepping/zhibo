<?php

class bannerpc_list_auto_cache extends auto_cache{
	private $key = "bannerpc:list:";
	
	public function load($params = array())
	{
		$type = $params['type'] ? intval($params['type']) : 0;
		$show_position = $params['show_position'] ? intval($params['show_position']) : 0;
		$this->key .= "{$type}_{$show_position}";
		$key_bf = $this->key.'_bf';
		$list = $GLOBALS['cache']->get($this->key,true);

		if ($list === false) {
			$is_ok =  $GLOBALS['cache']->set_lock($this->key);
			if(!$is_ok){
				$list = $GLOBALS['cache']->get($key_bf,true);
			}else{
				$sql = "select title,image,url from ".DB_PREFIX."index_image where `type`={$type} and show_position={$show_position} order by sort asc";
				
				$list = $GLOBALS['db']->getAll($sql,true,true);
				foreach($list as $k=>$v){
					$list[$k]['type'] = $v['type'];
					$list[$k]['url'] = $v['url'];
					$list[$k]['title'] = $v['title'];
					$list[$k]['image'] = get_spec_image($v['image']);
				}
			
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