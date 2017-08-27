<?php

class select_weibo_index_auto_cache extends auto_cache{
	private $key = "select:weibo_index:";
	public function load($param)
	{
		fanwe_require(APP_ROOT_PATH.'mapi/xr/core/common.php');
		$this->key .= md5(serialize($param));
		$type = $param['type']; //type: photo or video
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
					$sql = "select w.user_id,w.id as weibo_id,u.nick_name,w.sort_num,w.photo_image as video_image ,u.city,u.user_level,w.data as video_url,w.xpoint,w.ypoint,w.content as vide_desc,u.head_image,w.video_count,u.show_image  from ".DB_PREFIX."weibo as w
					left join ".DB_PREFIX."user as u on w.user_id = u.id where u.is_authentication = 2 and w.type = '".$type."'  ";

				}else{
					$sql = "select w.user_id,w.id as weibo_id,u.nick_name,w.sort_num,w.photo_image as video_image,u.city,u.user_level,w.data as video_url,w.xpoint,w.ypoint,w.content as vide_desc,u.head_image,w.video_count,u.show_image  from ".DB_PREFIX."weibo as w
					left join ".DB_PREFIX."user as u on w.user_id = u.id where  w.type = '".$type."' ";
				}

				$sql .= "  order by w.sort_num desc";
				$sql .= " limit " .$limit;
				
				$list = $GLOBALS['db']->getAll($sql,true,true);
				
				foreach($list as $k=>$v){
					//判断用户是否为今日创建的新用户，是：1，否：0
					if (date('Y-m-d') == date('Y-m-d',$list[$k]['user_create_time']+3600*8)){
						$list[$k]['today_create'] = 1;
					}else{
						$list[$k]['today_create'] = 0;
					}
					if($v['video_url']){
						$list[$k]['video_url']  = get_file_oss_url($v['video_url']);
					}
					$list[$k]['head_image'] = get_spec_image($v['head_image'],200,200,1);
					$list[$k]['video_image'] = get_spec_image($v['video_image'],200,200,1);
					if($type=='photo'){
						$list[$k]['head_image'] = get_spec_image($v['video_image'],200,200,1);
						$list[$k]['nick_name'] = $v['vide_desc'];
					}
					if($v['video_url']){
						$show_image_num = count(unserialize($v['video_url']));
					}else{
						$show_image_num = 0;
					}
					//写真照片数量
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