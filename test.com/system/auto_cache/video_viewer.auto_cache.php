<?php

class video_viewer_auto_cache extends auto_cache{
	private $key = "video:viewer:";
	public function load($param)
	{
 		$group_id = $param['group_id'];
		$page = $param['page'];

		$this->key .= $group_id . '_' . $page;

		$key_bf = $this->key.'_bf';
		
		$list = $GLOBALS['cache']->get($this->key,true);

		if ($list === false) {
			$is_ok =  $GLOBALS['cache']->set_lock($this->key);
			if(!$is_ok){
				$list = $GLOBALS['cache']->get($key_bf,true);
			}else{
				fanwe_require(APP_ROOT_PATH.'mapi/lib/redis/VideoViewerRedisService.php');
				$video_viewer_redis = new VideoViewerRedisService();
				if($group_id){
					$list = $video_viewer_redis->get_viewer_list($group_id,$page,100);
					foreach($list['list'] as $k=>$v){
						$list['list'][$k]['head_image'] = get_spec_image($v['head_image'],150,150);
					}
				}else{
					$list = array(
						'list'=>array(),
						'has_next'=>0,
						'page'=>1,
						'status'=>1
					);
				}
	
				$GLOBALS['cache']->set($this->key,$list,10,true);
				
				$GLOBALS['cache']->set($key_bf, $list, 86400, true);//备份
			}
		}
			
		if ($list == false) $list = array();
		
		return $list;
	}
	
	public function rm()
	{

		$GLOBALS['cache']->clear_by_name($this->key);
	}
	
	public function clear_all()
	{
		
		$GLOBALS['cache']->rm($this->key);
	}
}
?>