<?php

class playback_list_auto_cache extends auto_cache{
	private $key = "playback:list:user_id:";
	public function load($param)
	{
		$user_id = intval($param['user_id']);
		
		$this->key .= $user_id;
		
		$playback = $GLOBALS['cache']->get($this->key,true);

		if($playback === false)
		{
			$playback = array();
            fanwe_require(APP_ROOT_PATH.'mapi/lib/redis/UserFollwRedisService.php');
            $userfollw_redis = new UserFollwRedisService($user_id);
            $focus_user = $userfollw_redis->get_follonging_user($user_id,0,100);
            if(sizeof($focus_user)){
                $psql = '';
                foreach($focus_user as $k=>$v){
                    if($psql){
                        $psql.=',';
                    }
                    $psql.=$v['user_id'];
                }

                //精彩回放(只列出当天结束的回放)
                $sql = "select v.id as room_id,v.group_id,v.user_id,v.city,v.title,v.end_time,v.begin_time,v.max_watch_number,v.video_vid,
					u.nick_name,u.head_image,u.thumb_head_image,u.v_type,u.v_icon from ".DB_PREFIX."video_history as v
					left join ".DB_PREFIX."user as u on v.user_id=u.id
					where v.is_live_pay = 0 and v.is_delete = 0 and v.is_del_vod = 0 and v.user_id=u.id and v.end_date =' ".to_date(NOW_TIME,'Y-m-d')."' and v.user_id in (".$psql.") order by v.begin_time desc limit 0,30";

                // $list = $GLOBALS['db']->getAll("select v.id as room_id,v.group_id as group_id,(v.watch_number + v.robot_num + v.virtual_watch_number) as watch_number,v.video_vid,v.room_type,v.vote_number,v.create_time,u.id as user_id,u.nick_name as nick_name,u.head_image as head_image,u.thumb_head_image,u.user_level as user_level,u.sex as sex,u.province as province,u.city as city from ".DB_PREFIX."focus as f  left join ".DB_PREFIX."user as u on f.podcast_id=u.id  left join ".DB_PREFIX."video as v on v.user_id = f.podcast_id where  f.user_id = ".$user_id." and v.is_playback =1 and v.live_in = 0  and ".time()."-v.create_time<86400  and v.begin_time <> 0 order by v.create_time desc");

                /*$sql = "select v.id as room_id,v.group_id,v.user_id,v.city,v.title,v.end_time,v.begin_time,v.max_watch_number,
                        u.nick_name,u.head_image,u.thumb_head_image,u.v_type,u.v_icon from ".DB_PREFIX."video_history as v
                        left join ".DB_PREFIX."user as u on v.user_id=u.id
                        where v.is_delete = 0 and v.is_del_vod = 0 and v.user_id=u.id order by watch_number desc limit 0,30";*/

                $playback = $GLOBALS['db']->getAll($sql,true,true);
                	
                foreach ( $playback as $k => $v )
                {
                	$max_watch_number = intval($v['max_watch_number']);
                	//$playback[$k]['is_cache'] = 1;
                	$playback[$k]['playback_time'] = get_time_len($v['begin_time'],$v['end_time']);
                	$playback[$k]['begin_time_format'] = format_show_date($v['begin_time']);
                	$playback[$k]['head_image'] = get_spec_image($v['head_image']);
                	$playback[$k]['thumb_head_image'] = get_spec_image($v['thumb_head_image']);
                	if ($max_watch_number > 10000){
                		$playback[$k]['watch_number_format'] = round($max_watch_number/10000,2)."万";
                	}else{
                		$playback[$k]['watch_number_format'] = $max_watch_number;
                	}
                	$playback[$k]['max_watch_number'] = $max_watch_number;
                	if ($v['title'] == '')
                		$playback[$k]['title'] = "....";
                	$playback[$k]['nick_name'] = $v['nick_name']?$v['nick_name']:'';
                	$playback[$k]['nick_name'] =  htmlspecialchars_decode($v['nick_name']);
                	$playback[$k]['signature'] = $v['signature']?$v['signature']:'';
                	$playback[$k]['signature'] =  htmlspecialchars_decode($v['signature']);
                }
                
            }
			
			$GLOBALS['cache']->set($this->key, $playback, 60,true);
		}
		
		return $playback;
	}
	
	public function rm($param)
	{
		//$GLOBALS['cache']->rm($this->key);
        $id = intval($param['user_id']);
        $this->key .= $id;
        $GLOBALS['cache']->rm($this->key);
	}
	
	public function clear_all($param)
	{
		//$GLOBALS['cache']->rm($this->key);
        $id = intval($param['user_id']);
        $this->key .= $id;
        $GLOBALS['cache']->rm($this->key);
	}
}
?>