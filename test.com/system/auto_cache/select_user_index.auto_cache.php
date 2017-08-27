<?php

class select_user_index_auto_cache extends auto_cache{
	private $key = "select:user_index:";
	public function load($param)
	{
//		$type = intval($param['type']);
//		$this->key .= $type;
		$this->key .= md5(serialize($param));
		$page=$param['page']>0?$param['page']:1;
		$page_size=$param['page_size']>0?$param['page_size']:20;
		$limit = (($page-1) * $page_size) . "," . $page_size;
		$key_bf = $this->key.'_bf';
		
		$list = $GLOBALS['cache']->get($this->key,true);

		if ($list === false) {
			$is_ok =  $GLOBALS['cache']->set_lock($this->key);
			if(!$is_ok){
				$list = $GLOBALS['cache']->get($key_bf,true);
			}else{
				$m_config =  load_auto_cache("m_config");//初始化手机端配置
				$has_is_authentication = intval($m_config['has_is_authentication'])?1:0;
				if($has_is_authentication){
					$sql = "SELECT u.id as user_id,u.weibo_sort_num as sort_num,u.xpoint,u.ypoint,u.show_image , u.head_image,u.thumb_head_image,u.v_type, u.v_icon, u.nick_name,u.user_level,u.city,u.create_time as user_create_time FROM
 					".DB_PREFIX."user u  where  u.is_authentication = 2 and u.mobile != '13888888888' and u.mobile != '13999999999'  ";
				}else{
					$sql = "SELECT u.id as user_id,u.weibo_sort_num as sort_num, u.xpoint,u.ypoint,u.show_image , u.head_image,u.thumb_head_image,
						u.v_type, u.v_icon, u.nick_name,u.user_level,u.city,u.create_time as user_create_time FROM
					  ".DB_PREFIX."user u  where   u.mobile != '13888888888' and u.mobile != '13999999999'  ";
				}

				$sql .= "  order by u.weibo_sort_num desc";
				$sql .= " limit " .$limit;
				$list = $GLOBALS['db']->getAll($sql,true,true);
				
				foreach($list as $k=>$v){
					//判断用户是否为今日创建的新用户，是：1，否：0
					if (date('Y-m-d') == date('Y-m-d',$list[$k]['user_create_time']+3600*8)){
						$list[$k]['today_create'] = 1;
					}else{
						$list[$k]['today_create'] = 0;
					}

					$list[$k]['head_image'] = get_spec_image($v['head_image'],150,150,1);

					if(empty($v['city'])){
						$list[$k]['city'] = '喵星';
					}
					if($v['show_image']){
						$show_image_num = count(unserialize($v['show_image']));
					}else{
						$show_image_num = 0;
					}
					$list[$k]['show_image_num'] = $show_image_num;
				}
				
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