<?php

class cate_id_auto_cache extends auto_cache{
	public function load($param)
	{
		$id = intval($param['id']);
		$key = "cate:".$id;
		
		$cate = $GLOBALS['cache']->get($key,true);
		$key_bf = $this->key.'_bf';
		
		if ($cate === false) {
			$is_ok =  $GLOBALS['cache']->set_lock($this->key);
			if(!$is_ok){
				$cate = $GLOBALS['cache']->get($key_bf,true);
			}else{
				
				$sql = "select id,user_id,image,url,title,`desc`,num from ".DB_PREFIX."video_cate where id=".$id;
				$cate = $GLOBALS['db']->getAll($sql,true,true);
				 
				if ($cate['url'] != '' && $cate['image'] != ''){
					$banner = array();
					$banner['type'] = 0;
					$banner['url'] = $cate['url'];
					$banner['image'] = get_spec_image($cate['image']);
					
					$cate['banner'][] = $banner;
				
				
					if ($cate['user_id'] > 0){
						$sql = "select head_image from ".DB_PREFIX."user where id =".$cate['user_id'];
						$head_image = $GLOBALS['db']->getAll($sql,true,true);
					}
					$cate['head_image'] = get_spec_image($head_image);
				}
			
				$GLOBALS['cache']->set($key,$cate,60,true);
				$GLOBALS['cache']->set($key_bf, $cate, 86400, true);//备份
				//echo $this->key;
			}
		}	
		return $cate;
	}
	
	public function rm($param)
	{
		$id = intval($param['id']);
		$key = "cate:".$id;
		$GLOBALS['cache']->rm($key);
	}
	
	public function clear_all($param)
	{
		$id = intval($param['id']);
		$key = "cate:".$id;
		$GLOBALS['cache']->rm($key);
	}
}
?>