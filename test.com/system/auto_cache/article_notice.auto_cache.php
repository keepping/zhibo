<?php

class article_notice_auto_cache extends auto_cache{
	private $key = "article:notice";
	public function load($param)
	{
		
		$listmsg = $GLOBALS['cache']->get($this->key);
		if($listmsg === false)
		{
			$affiche_sql = "select a.id,a.title,a.content from ".DB_PREFIX."article as a left join ".DB_PREFIX."article_cate as ac on ac.id = a.cate_id  where a.is_delete = 0 and a.is_effect = 1 and ac.type_id = 2 ";
			$affiche = $GLOBALS['db']->getAll($affiche_sql,true,true);
			
			$listmsg = array();
			foreach ( $affiche as $k => $v )
			{
				$msg = array();
				$msg['type'] = 9;
				$msg['fonts_color'] = '';
				$msg['desc'] = $v['content'];
				
				$listmsg[] = $msg;
			}
			$GLOBALS['cache']->set($this->key,$listmsg);
		}
		
		return $listmsg;
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