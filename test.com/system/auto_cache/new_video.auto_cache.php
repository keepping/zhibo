<?php

class new_video_auto_cache extends auto_cache{
	private $key = "new:video:";
	public function load($param)
	{
		$this->key .= md5(serialize($param));
		$key_bf = $this->key.'_bf';
		
		$list = $GLOBALS['cache']->get($this->key,true);

		if ($list === false) {
			$is_ok =  $GLOBALS['cache']->set_lock($this->key);
			if(!$is_ok){
				$list = $GLOBALS['cache']->get($key_bf,true);
			}else{
                $m_config =  load_auto_cache("m_config");//初始化手机端配置
				$sql = "SELECT v.id AS room_id, v.sort_num, v.group_id, v.user_id, v.city, v.title, v.cate_id, v.live_in, v.video_type, v.create_type, v.room_type,
						(v.robot_num + v.virtual_watch_number + v.watch_number) as watch_number, u.head_image,u.thumb_head_image, v.xpoint,v.ypoint,
						u.v_type, u.v_icon, u.nick_name,u.user_level,v.live_image,v.is_live_pay,v.live_pay_type,v.live_fee,u.create_time as user_create_time FROM ".DB_PREFIX."video v
					LEFT JOIN ".DB_PREFIX."user u ON u.id = v.user_id where v.live_in in (1,3) and v.room_type = 3 and u.mobile != '13888888888' and u.mobile != '13999999999'";

				if (!empty($param['create_type'])) {
					$sql .= ' and v.create_type = ' . $param['create_type'];
				}

                if((defined('OPEN_ROOM_HIDE')&&OPEN_ROOM_HIDE==1)&&intval($m_config['open_room_hide'])==1){
                    $sql.= " and v.province <> '火星' and v.province <>''";
                }

                $sql.="  order by v.create_time desc ";

				$list = $GLOBALS['db']->getAll($sql,true,true);
				
				foreach($list as $k=>$v){
					
					//判断用户是否为今日创建的新用户，是：1，否：0
					if (date('Y-m-d') == date('Y-m-d',$list[$k]['user_create_time']+3600*8)){
						$list[$k]['today_create'] = 1;
					}else{
						$list[$k]['today_create'] = 0;
					}
					
					if($v['live_image']==''){
						$list[$k]['live_image'] = get_spec_image($v['head_image']);
						$list[$k]['head_image'] = get_spec_image($v['head_image']);
					}else{
						$list[$k]['live_image'] = get_spec_image($v['live_image']);
						$list[$k]['head_image'] = get_spec_image($v['head_image'],150,150);
					}

					if ($v['thumb_head_image'] == ''){
						$list[$k]['thumb_head_image'] = get_spec_image($v['head_image'],150,150);
					}else{
						//$list[$k]['thumb_head_image'] = get_abs_img_root($v['thumb_head_image']);
						$list[$k]['thumb_head_image'] = get_spec_image($v['thumb_head_image'],150,150);
					}

					if($v['live_in']==1){
						if($v['is_live_pay']==0){
							$list[$k]['live_state'] = '直播';
						}else if($v['is_live_pay']==1){
							$list[$k]['live_state'] = '付费直播';
						}
					}else{
						if($v['is_live_pay']==1){
							$list[$k]['live_state'] = '付费回播';
						}else if($v['is_live_pay']==0&&intval($v['is_gather'])!=1){
							$list[$k]['live_state'] = '回播';
						}else if(intval($v['is_gather'])==1){
							$list[$k]['live_state'] = '直播';
						}
					}
				}
				
				$GLOBALS['cache']->set($this->key, $list, 10,true);
				
				$GLOBALS['cache']->set($key_bf, $list, 86400, true);//备份
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