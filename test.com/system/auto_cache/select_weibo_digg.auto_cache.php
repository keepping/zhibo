<?php

class select_weibo_digg_auto_cache extends auto_cache{
	private $key = "select:weibo_digg:";
	public function load($param)
	{

		$this->key .= md5(serialize($param));
		$page=$param['page']>0?$param['page']:1;
		$page_size=$param['page_size']>0?$param['page_size']:20;
		$limit = (($page-1) * $page_size) . "," . $page_size;
		$weibo_id = intval($param['weibo_id']);
		$key_bf = $this->key.'_bf';
		
		$list = $GLOBALS['cache']->get($this->key,true);

		if ($list === false) {
			$is_ok =  $GLOBALS['cache']->set_lock($this->key);
			if(!$is_ok){
				$list = $GLOBALS['cache']->get($key_bf,true);
			}else{
				$weibo_info = $GLOBALS['db']->getRow("select w.id  from ".DB_PREFIX."weibo as w  where w.id = ".$weibo_id);
				if(!$weibo_info){
					$root['error'] = "动态不存在!";
					$root['status'] = 0;
					api_ajax_return($root);
				}
					//点赞列表
				$digg_list = $GLOBALS['db']->getAll("select wc.user_id,u.nick_name,u.head_image,u.is_authentication from ".DB_PREFIX."weibo_comment as wc
		left join ".DB_PREFIX."user as u on wc.user_id = u.id where wc.weibo_id = ".$weibo_id." and type = 2   order by wc.comment_id desc limit $limit");
				if($digg_list){
					foreach($digg_list as $k=>$v){
						if($v){
							$digg_list[$k]['head_image'] = get_spec_image($v['head_image']);
						}
					}
				}
				$list = $digg_list;


				$GLOBALS['cache']->set($this->key, $list, 10, true);
				
				$GLOBALS['cache']->set($key_bf, $list, 86400, true);//备份
				//echo $this->key;
			}
 		}
 		
 		if ($list == false) $list = array();
 		
		return $list;
	}
	
	public function rm()
	{

		//$GLOBALS['cache']->clear_by_name($this->key);
	}
	
	public function clear_all()
	{
		
		//$GLOBALS['cache']->clear_by_name($this->key);
	}
}
?>