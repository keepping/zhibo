<?php

class article_agreement_auto_cache extends auto_cache{
	private $key = "article:agreement";
	public function load($param)
	{
		
		$content = $GLOBALS['cache']->get($this->key);

		if($content === false)
		{
			$sql = "select a.id,a.title,a.cate_id,a.content from ".DB_PREFIX."article a left join ".DB_PREFIX."article_cate b on b.id = a.cate_id where a.is_delete = 0 and a.is_effect = 1 and b.title = '主播协议'";
			$article = $GLOBALS['db']->getRow($sql,true,true);
			
			
			
			//$content = '<title>'.$article['title'].'</title>';
			//$content = $content.$article['content'];
			
			$content = file_get_contents(SITE_DOMAIN.APP_ROOT."/wap/index.php?ctl=settings&act=article_show&cate_id=".$article['cate_id']);

			$GLOBALS['cache']->set($this->key,$content);
		}
		
		return $content;
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