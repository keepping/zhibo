<?php

class video_file_auto_cache extends auto_cache
{
	private $key = "video:file:";

	public function load($video)
	{
		$this->key .= $video['id'];
		$key_bf = $this->key . '_bf';

		$play_info = $GLOBALS['cache']->get($this->key, true);

		if ($play_info === false) {

			$is_ok = $GLOBALS['cache']->set_lock($this->key);
			if (!$is_ok) {
				return $GLOBALS['cache']->get($key_bf, true);
			}
			fanwe_require(APP_ROOT_PATH . 'mapi/lib/core/video_factory.php');
			$video_factory = new VideoFactory();
			if ($video['video_type'] > 0 && $video['channelid'] && strpos($video['channelid'],'_')) {
				$channel_info = $video_factory->GetVodRecordFiles($video['channelid'], $video['create_time']);
				if ($channel_info['totalCount'] > 0) {
                    if (empty($channel_info['urls'])) {
                        $file_id = $channel_info['filesInfo'][0]['fileId'];
                        $file_info = $video_factory->DescribeVodPlayUrls($file_id);
                        $urls = $file_info['urls'];
                    } else {
                        $file_id = "";
                        $urls = $channel_info['urls'];
                    }
					ksort($urls);
					$play_info = array(
						'file_id' => $file_id,
						'urls' => $urls,
						'play_url' => array_shift($urls),
					);
				}
			} else {
				$fileName = $video['id'] . '_' . to_date($video['begin_time'], 'Y-m-d-H');
                if($video['video_type'] == 1 && !empty($video['channelid'])){
                    $fileName = 'live'.$video['id'] . '_' . to_date($video['begin_time'], 'Y-m-d-H');
                }
				$file_info = $video_factory->DescribeVodPlayInfo($fileName);

				if ($file_info['totalCount'] > 0) {
					if (count($file_info['fileSet'][0]['playSet']) == 1 && $file_info['fileSet'][0]['playSet'][0]['definition'] == 0) {
						$file_info['fileSet'][0]['playSet'][0]['definition'] = 20;
					}

					$urls = array();
					foreach ($file_info['fileSet'][0]['playSet'] as $play) {
						$urls[$play['definition']] = $play['url'];
					}
					ksort($urls);
					$play_info = array(
						'file_id' => $file_info['fileSet'][0]['fileId'],
						'urls' => $urls,
						'play_url' => array_shift($urls),
					);
				}
			}
			//兼容再次查找视频
			if($play_info['play_url']==''){
				$root = c_get_vodset_by_video_id($video['id']);
				if(isset($root['vodset'])) {
					$play_list = array();
					$vodset = $root['vodset'];
					$urls = array();
					foreach ($vodset as $k => $v) {
						$playSet = $v['fileSet'];
						for ($i = sizeof($playSet) - 1; $i >= 0; $i--) {
							$play_list[] = $playSet[$i]['fileId'];
							$urls[$playSet[$i]['playSet'][$i]['definition']] = $playSet[$i]['playSet'][$i]['url'];
						}
					}

					ksort($urls);
					$play_info = array(
						'file_id' => $play_list[0],
						'urls' => $urls,
						'play_url' => array_shift($urls),
					);
				}
			}

			if (!empty($play_info['urls'])) {
				$GLOBALS['cache']->set($this->key, $play_info, 3600 * 12, true);
				$GLOBALS['cache']->set($key_bf, $play_info, 86400, true);//备份
			}
		}

		return $play_info;
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