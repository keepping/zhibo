<?php

class article_auto_cache extends auto_cache{
	private $key = "article:news";
	public function load($param)
	{
		$page = $param['page'];
		$page_size = $param['page_size'];
		$this->key = $this->key."_".$page;
		
		$listmsg = $GLOBALS['cache']->get($this->key);
		if($listmsg === false||true)
		{
			$page = $page-1>0?($page-1)*$page_size:0;
			$page_size = $page_size>0?$page_size:15;
			$limit = "limit ".$page.",".$page_size;
			$affiche_sql = "select a.id,a.title,a.content,a.create_time from ".DB_PREFIX."article as a left join ".DB_PREFIX."article_cate as ac on ac.id = a.cate_id  where a.is_delete = 0 and a.is_effect = 1 and ac.type_id = 4 order by a.sort desc,a.create_time desc ".$limit;
			$affiche = $GLOBALS['db']->getAll($affiche_sql,true,true);

			$listmsg = array();
			foreach ( $affiche as $k => $v )
			{
				$msg = array();
				$msg['title'] =$v['title'];
				$msg['url'] =url("article#show",array('id'=>$v['id']));
				$msg['create_time'] =$v['create_time'];
				$listmsg[] = $msg;
			}
			$GLOBALS['cache']->set($this->key,$listmsg);
		}
		$affiche_count = "select count(a.id) from ".DB_PREFIX."article as a left join ".DB_PREFIX."article_cate as ac on ac.id = a.cate_id  where a.is_delete = 0 and a.is_effect = 1 and ac.type_id = 4 ";
		$rs_count = $GLOBALS['db']->getOne($affiche_count,true,true);
		$list = array();
		$list['listmsg'] = $listmsg;
		$list['rs_count'] = $rs_count;
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