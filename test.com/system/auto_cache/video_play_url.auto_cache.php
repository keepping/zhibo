<?php

class video_play_url_auto_cache extends auto_cache{
	private $key = "video:play:url:";
	public function load($video)
	{
		$this->key .= $video['id'];
		$key_bf = $this->key . '_bf';

		$play_url = $GLOBALS['cache']->get($this->key, true);
		if ($play_url === false) {

			$is_ok = $GLOBALS['cache']->set_lock($this->key);
			if (!$is_ok) {
				return $GLOBALS['cache']->get($key_bf, true);
			}

			fanwe_require(APP_ROOT_PATH . 'mapi/lib/core/video_factory.php');
			$video_factory = new VideoFactory();
			$channel_info = $video_factory->GetVodUrls($video['channelid'], $video['begin_time']);
			if(! $channel_info['status']){
				return;
			}

			$play_url = $channel_info['urls']['20'];
			if($play_url){
				$GLOBALS['cache']->set($this->key, $play_url, 3600 * 12, true);
				$GLOBALS['cache']->set($key_bf, $play_url, 86400, true);//备份
			}
		}

		return $play_url;
	}
	
	public function rm()
	{

		$GLOBALS['cache']->clear_by_name($this->key);
	}
	
	public function clear_all()
	{
		
		$GLOBALS['cache']->clear_by_name($this->key);
	}
}
?>