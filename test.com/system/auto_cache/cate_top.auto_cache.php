<?php

class cate_top_auto_cache extends auto_cache{
	private $key = "cate:top";
	
	public function load($param)
	{
		$list = $GLOBALS['cache']->get($this->key);
		
		$key_bf = $this->key.'_bf';
		
		if($list === false)
		{
			$is_ok =  $GLOBALS['cache']->set_lock($this->key);
			if(!$is_ok){
				$list = $GLOBALS['cache']->get($key_bf,true);
			}else{
				$sql = "select vc.id as cate_id,vc.title,vc.num from ".DB_PREFIX."video_cate as vc
						where vc.is_effect = 1 order by vc.sort desc, vc.num desc limit 0,4";

				$list = $GLOBALS['db']->getAll($sql,true,true);
				foreach($list as $k=>$v){
					$list[$k]['title'] ="#".$v['title']."#";
				}

				$cate = array();
				$cate['cate_id'] = 0;
				$cate['title'] = '热门话题    >';
				$cate['num'] = 0;
				$list[] = $cate;
			
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