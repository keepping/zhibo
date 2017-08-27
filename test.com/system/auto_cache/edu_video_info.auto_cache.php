<?php

class edu_video_info_auto_cache extends auto_cache{
	private $key = "edu:video:info:";
	
	//参数有：
	public function load($param)
	{
		
		$this->key .= md5(serialize($param));
		$list = $GLOBALS['cache']->get($this->key);
		
		$key_bf = $this->key.'_bf';
		
		if($list === false)
		{
			$is_ok =  $GLOBALS['cache']->set_lock($this->key);
			if(!$is_ok){
				$list = $GLOBALS['cache']->get($key_bf,true);
			}else{
                $sql = "SELECT v.id AS room_id,ev.tags,ev.deal_id,ev.edu_cate_id,ev.video_code,ev.is_verify FROM ".DB_PREFIX."video as v LEFT JOIN ".DB_PREFIX."edu_video_info as ev ON ev.video_id = v.id where v.live_in in (1,3) and v.id=".intval($param['video_id'])."";

				$list = $GLOBALS['db']->getRow($sql,true,true);

                $GLOBALS['cache']->set($this->key,$list,10,true);
                $GLOBALS['cache']->set($key_bf, $list, 86400, true);//备份

			}
 		}
 		
 		if ($list == false) $list = array();
		
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