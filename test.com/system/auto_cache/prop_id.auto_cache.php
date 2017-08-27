<?php

class prop_id_auto_cache extends auto_cache{
	public function load($param)
	{
		$id = intval($param['id']);
		$key = "prop:".$id;
		$prop = $GLOBALS['cache']->get($key);
		if($prop === false)
		{
			$sql = "select id,name,score,diamonds,icon,pc_icon,pc_gif,ticket,is_much,sort,is_red_envelope,is_animated,anim_type,robot_diamonds from ".DB_PREFIX."prop where id=".$id;
			$prop = $GLOBALS['db']->getRow($sql,true,true);//以后需要缓存
			
			$prop['icon'] = get_spec_image($prop['icon']);
			
			if ($prop['is_animated'] == 1){
				//要缓存getAllCached
				$sql = "select id,url,play_count,delay_time,duration,show_user,type from ".DB_PREFIX."prop_animated where prop_id = ".$id." order by sort desc";
				$anim_list = $GLOBALS['db']->getAll($sql,true,true);
				foreach ( $anim_list as $k => $v )
				{
					$anim_list[$k]['url'] = get_spec_image($v['url']);
				}
				
				$prop['anim_cfg'] = $anim_list;
				//$ext['sql'] = $sql;
			}else{
				$prop['anim_cfg'] = array();
			}
			
			
			$GLOBALS['cache']->set($key,$prop);
		}else{
			//echo 'cache';
		}
		return $prop;
	}
	
	public function rm($param)
	{
		$id = intval($param['id']);
		$key = "prop:".$id;
		$GLOBALS['cache']->rm($key);
	}
	
	public function clear_all($param)
	{
		$id = intval($param['id']);
		$key = "prop:".$id;
		$GLOBALS['cache']->rm($key);
	}
}
?>