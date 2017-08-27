<?php

class video_live_recommend_auto_cache extends auto_cache
{
	private $key = "video:live:index";

	public function load($hot_list)
	{
		$key_bf = $this->key . '_bf';

		$live_video = $GLOBALS['cache']->get($this->key, true);
		if ($live_video === false) {

			$is_ok = $GLOBALS['cache']->set_lock($this->key);
			if (!$is_ok) {
				return $GLOBALS['cache']->get($key_bf, true);
			}

			$live_video = array();
			if (count($hot_list) > 6) {
				foreach (array_rand($hot_list, 6) as $key) {
					$live_video[] = $hot_list[$key];
				}
			} else {
				$live_video = $hot_list;
			}

			fanwe_require(APP_ROOT_PATH . 'mapi/lib/redis/VideoRedisService.php');
			$video_redis = new VideoRedisService();
			foreach ($live_video as $key => $value) {
				$room = $video_redis->getRow_db($value['room_id'], array('channelid', 'begin_time', 'create_time'));
				$live_video[$key]['channelid'] = $room['channelid'];
				if ($value['live_in'] == 3) {
					$file_info = load_auto_cache('video_file', array(
						'id' => $value['room_id'],
						'video_type' => $value['video_type'],
						'channelid' => $room['channelid'],
						'begin_time' => $room['begin_time'],
						'create_time' => $room['create_time'],
					));
					$live_video[$key]['fileid'] = $file_info['file_id'];
					$live_video[$key]['play_url'] = $file_info['play_url'];
				}
			}

			if (!empty($live_video)) {
				$GLOBALS['cache']->set($this->key, $live_video, 60, true);
				$GLOBALS['cache']->set($key_bf, $live_video, 86400, true);//备份
			}
		}

		return $live_video;
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