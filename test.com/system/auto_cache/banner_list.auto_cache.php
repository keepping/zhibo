<?php

class banner_list_auto_cache extends auto_cache{
	private $key = "banner:list";
	
	public function load($param)
	{
		
		$key_bf = $this->key.'_bf';
		$list = $GLOBALS['cache']->get($this->key,true);

		if ($list === false) {
			$is_ok =  $GLOBALS['cache']->set_lock($this->key);
			if(!$is_ok){
				$list = $GLOBALS['cache']->get($key_bf,true);
			}else{
			
				$sql = "select title,image,url,type,show_id from ".DB_PREFIX."index_image where show_position not in(3,4,5,6,7,8,9) order by sort asc";
				
				$list = $GLOBALS['db']->getAll($sql,true,true);
				foreach($list as $k=>$v){
					$list[$k]['type'] = $v['type'];
                    $list[$k]['show_id'] = $v['show_id'];
					$list[$k]['url'] = $v['url'];
					$list[$k]['title'] = $v['title'];
					$list[$k]['image_width'] = 828;
					$list[$k]['image_height'] = 240;//
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