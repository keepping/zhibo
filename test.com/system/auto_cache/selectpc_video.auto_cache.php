<?php

class selectpc_video_auto_cache extends auto_cache{
	private $key = "selectpc:video:";
	public function load($param)
	{
		$is_recommend = intval($param['is_recommend']);//推荐
		$index_recommend=intval($param['index_recommend']);//推荐主播
		$is_hot = intval($param['is_hot']);//热门
		$is_new = intval($param['is_new']);//最新
		$update = intval($param['update']);
		$is_family_hot = intval($param['is_family_hot']);//家族热门
		$has_private = intval($param['has_private']);//1：包括私密直播
		$page=$param['page']>0?$param['page']:1;
		$page_size=$param['page_size']>0?$param['page_size']:8;
		$limit = (($page-1) * $page_size) . "," . $page_size;
		if ($is_recommend){
			$this->key .= 'is_recommend';
		}
		if ($is_hot){
			$this->key .= 'is_hot';
		}
		if ($is_new){
			$this->key .= 'is_new';
		}
		if ($is_family_hot){
			$this->key .= 'is_family_hot';
		}
		
		if($param['pc']){                   //主页读取
			$this->key .= $this->key.'_pc';
		}
		
		$key_bf = $this->key.'_bf';
		
		$list = $GLOBALS['cache']->get($this->key,true);

		if ($list === false || $update == 1) {
//			$is_ok =  $GLOBALS['cache']->set_lock($this->key);
//			if(!$is_ok){
//				$list = $GLOBALS['cache']->get($key_bf,true);
//			}else{
				$m_config =  load_auto_cache("m_config");//初始化手机端配置
				$has_is_authentication = intval($m_config['has_is_authentication'])?1:0;
				if($has_is_authentication&&$m_config['ios_check_version'] == ''){
					$sql = "SELECT v.id AS room_id, v.channelid, v.begin_time, v.create_time, v.play_url, v.play_flv, v.play_hls, v.sort_num, v.group_id, v.user_id, v.city, v.title, v.cate_id, v.live_in, v.video_type, v.room_type,
						(v.robot_num + v.virtual_watch_number + v.watch_number) as watch_number, v.live_image, v.head_image,v.thumb_head_image, v.xpoint,v.ypoint,
						u.v_type, u.v_icon, u.nick_name,u.user_level FROM ".DB_PREFIX."video v 
					LEFT JOIN ".DB_PREFIX."user u ON u.id = v.user_id where v.live_in in (1,3) and u.is_authentication = 2 ";
				}elseif($is_family_hot){
					$sql = " SELECT v.id AS room_id, v.channelid, v.begin_time, v.create_time, v.play_url, v.play_flv, v.play_hls, v.sort_num, v.group_id, v.user_id, v.city, v.title, v.cate_id, v.live_in, v.video_type, v.room_type,
						(v.robot_num + v.virtual_watch_number + v.watch_number) as watch_number, v.live_image, v.head_image,v.thumb_head_image, v.xpoint,v.ypoint,
						u.v_type, u.v_icon, u.nick_name,u.user_level,f.id  FROM ".DB_PREFIX."family as f , ".DB_PREFIX."video v LEFT JOIN ".DB_PREFIX."user u ON u.id = v.user_id where u.family_id = f.id and u.family_id >0 and f.status=1 and v.live_in in (1,3) ";
				}elseif($param['cate_id']){
					$sql = "SELECT v.id AS room_id, v.channelid, v.begin_time, v.create_time, v.play_url, v.play_flv, v.play_hls, v.sort_num, v.group_id, v.user_id, v.city, v.title, v.cate_id, v.live_in, v.video_type, v.room_type,
						(v.robot_num + v.virtual_watch_number + v.watch_number) as watch_number, v.live_image, v.head_image,v.thumb_head_image, v.xpoint,v.ypoint,
						u.v_type, u.v_icon, u.nick_name,u.user_level FROM ".DB_PREFIX."video v
					LEFT JOIN ".DB_PREFIX."user u ON u.id = v.user_id where v.live_in in (1,3) and v.cate_id =" . $param['cate_id'];
				}else{
					$sql = "SELECT v.id AS room_id, v.channelid, v.begin_time, v.create_time, v.play_url, v.play_flv, v.play_hls, v.sort_num, v.group_id, v.user_id, v.city, v.title, v.cate_id, v.live_in, v.video_type, v.room_type,
						(v.robot_num + v.virtual_watch_number + v.watch_number) as watch_number, v.live_image, v.head_image,v.thumb_head_image, v.xpoint,v.ypoint,
						u.v_type, u.v_icon, u.nick_name,u.user_level FROM ".DB_PREFIX."video v 
					LEFT JOIN ".DB_PREFIX."user u ON u.id = v.user_id where v.live_in in (1,3) ";
				}
				if($index_recommend){
					$sql .= ' and v.user_id ='.$index_recommend;
				}
				if ($has_private == 1){
					$sql .= ' and v.room_type in (1,3)'; //1:私密直播;3:直播
				}else{
					$sql .= ' and v.room_type = 3'; //1:私密直播;3:直播
				}
				if($is_recommend){
					$sql .= ' and v.is_recommend = '.$is_recommend;
					$sql .= " order by v.is_recommend desc,v.sort desc,v.sort_num desc";
				}elseif ($is_hot){
					$sql .= " order by watch_number desc,v.sort_num desc,v.sort desc";
				}elseif ($is_new){
					$sql .= " order by v.create_time desc,v.sort_num desc,v.sort desc ";
				}elseif ($is_family_hot){
					$sql .= " order by v.watch_number desc,v.sort_num desc,v.sort desc ";
				}else{
					$sql .= " order by v.sort_num desc,v.sort desc";
				}
				$sql .=" limit ". $limit;
				$list = $GLOBALS['db']->getAll($sql,true,true);
				
				foreach($list as $k=>$v){
					if ($v['thumb_head_image'] == ''){
						$list[$k]['thumb_head_image'] = get_spec_image($v['head_image'],40,40);
					}else{
						$list[$k]['thumb_head_image'] = get_spec_image($v['thumb_head_image'],40,40);
					}
					if(empty($v['live_image'])) {
						$list[$k]['live_image'] = get_spec_image($v['head_image'],320,180,1);
					}else{
						$list[$k]['live_image']=get_spec_image($v['live_image'],320,180,1);
					}
					$list[$k]['head_image'] = get_spec_image($v['head_image'],40,40);
					$list[$k]['video_url'] = get_video_url($v['room_id'], $v['live_in']);
					if ($v['live_in'] == 3) {
						$file_info = load_auto_cache('video_file', array(
							'id' => $v['room_id'],
							'video_type' => $v['video_type'],
							'channelid' => $v['channelid'],
							'begin_time' => $v['begin_time'],
							'create_time' => $v['create_time'],
						));
						$list[$k]['fileid'] = $file_info['file_id'];
						$list[$k]['play_url'] = $file_info['play_url'];
						$list[$k]['urls'] = $file_info['urls'];
					}
				}
				
				$GLOBALS['cache']->set($this->key, $list, 10, true);
				
				$GLOBALS['cache']->set($key_bf, $list, 86400, true);//备份
			}
// 		}
 		if ($list == false) $list = array();
		return $list;
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