<?php

class banner_edu_list_auto_cache extends auto_cache{
	private $key = "banner_edu:list:";
	
	public function load($params = array(), $is_real)
	{
		$type = $params['type'] ? intval($params['type']) : 0;
		$show_position = $params['show_position'] ? intval($params['show_position']) : 0;
		$this->key .= "{$type}_{$show_position}";
		$key_bf = $this->key.'_bf';
		$list = $GLOBALS['cache']->get($this->key,true);
		
		if ($list === false || !$is_real) {
			$is_ok =  $GLOBALS['cache']->set_lock($this->key);
			if(!$is_ok){
				$list = $GLOBALS['cache']->get($key_bf,true);
			}else{
				
				$where=" 1=1 ";
				if(isset($params['type']))
				{
					$where .=" and type=".$params['type']."";
				}
				if(isset($params['show_position']))
				{
					$where .=" and show_position=".$show_position."";
				}
				$sql = "select id,title,image,url,type,show_id,show_position from ".DB_PREFIX."index_image where ".$where." order by sort asc";
				
				$list = $GLOBALS['db']->getAll($sql,true,true);
				foreach($list as $k=>$v){
					$list[$k]['image'] = get_spec_image($v['image']);

                    if ($v['type'] == 8 && $v['show_id'] > 0) {
                        $video = $GLOBALS['db']->getRow("select id as room_id,user_id,group_id,live_image,video_type,create_type,room_type,live_in from " . DB_PREFIX . "video where id=" . intval($v['show_id']));
                        if(!$video){
                            $video = $GLOBALS['db']->getRow("select id as room_id,user_id,group_id,live_image,video_type,create_type,room_type,live_in from " . DB_PREFIX . "video_history where id=" . intval($v['show_id']));
                        }

                        if($video)
                        {
                            $video['live_image'] = get_spec_image($video['live_image']);
                        }else{
                            $video=null;
                        }

                        $list[$k]['video'] = $video;
                    }
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