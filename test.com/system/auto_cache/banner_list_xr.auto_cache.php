<?php

class banner_list_xr_auto_cache extends auto_cache{
	private $key = "banner:list:xr";
	
	public function load($param)
	{
		$type = intval($param['type']);
		$this->key .= ':'.$type;
		$key_bf = $this->key.'_bf';
		$list = $GLOBALS['cache']->get($this->key,true);

		if ($list === false) {
			$is_ok =  $GLOBALS['cache']->set_lock($this->key);
			if(!$is_ok){
				$list = $GLOBALS['cache']->get($key_bf,true);
			}else{
			
				$sql = "select title,image,url,type,show_id from ".DB_PREFIX."index_image where show_position = ".$type." order by sort asc";
				
				$list = $GLOBALS['db']->getAll($sql,true,true);
				foreach($list as $k=>$v){
					$list[$k]['type'] = $v['type'];
					if($v['type']==11){
						$row = $GLOBALS['db']->getRow("select type,price from ".DB_PREFIX."weibo where id = ".intval($v['show_id']));
						$list[$k]['type_cate'] = $row['type'];
						$list[$k]['price'] = $row['price'];
					}else{
						$list[$k]['type_cate'] = '';
						$list[$k]['price'] = 0;
					}
                    $list[$k]['show_id'] = $v['show_id'];
					$list[$k]['url'] = $v['url'];
					$list[$k]['title'] = $v['title'];
					$list[$k]['image_width'] = 750;
					$list[$k]['image_height'] = 400;
					$list[$k]['image'] = get_spec_image($v['image'],$list[$k]['image_width'],$list[$k]['image_height'],1);
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