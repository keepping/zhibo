<?php

class video_contribute_auto_cache extends auto_cache{
	private $key = "video:contribute:";
	public function load($param)
	{
		$user_id = intval($param['user_id']);
		$table = strim($param['table']);
		$page = intval($param['page']);
		$page_size = intval($param['page_size']);
		$cache_time = strim($param['cache_time']);
		$limit = (($page - 1) * $page_size) . "," . $page_size;

		$this->key .= $user_id . '_' . $page;

		$key_bf = $this->key.'_bf';

		$list = $GLOBALS['cache']->get($this->key,true);

        if ($list === false) {
            $is_ok =  $GLOBALS['cache']->set_lock($this->key);
            if(!$is_ok){
                $list = $GLOBALS['cache']->get($key_bf,true);
            }elseif(defined('OPEN_LIVE_PAY')&&OPEN_LIVE_PAY==1){
                $sql ="select u.id as user_id ,u.nick_name,u.v_icon,u.head_image,u.sex,u.user_level,sum(v.total_ticket) as num
                from ".$table." as v LEFT JOIN  ".DB_PREFIX."user as u on u.id = v.from_user_id
											where v.prop_id<>12 and v.create_ym=".to_date(NOW_TIME,'Ym')." and v.create_d=".to_date(NOW_TIME,'d')."
                and v.to_user_id=".$user_id." GROUP BY v.from_user_id
											order BY sum(v.total_diamonds) desc limit ".$limit;
                $sql_list=$GLOBALS['db']->getAll($sql,true,true);

                $pay_history="select u.id as user_id ,u.nick_name,u.v_icon,u.head_image,u.sex,u.user_level,sum(v.total_ticket) as num
                                            from ".DB_PREFIX."live_pay_log_history as v LEFT JOIN ".DB_PREFIX."user  as u  on u.id = v.from_user_id
                                            where v.create_ym=".to_date(NOW_TIME,'Ym')." and v.create_d=".to_date(NOW_TIME,'d')."
                and v.to_user_id=".$user_id." GROUP BY v.from_user_id
                                           order BY sum(v.total_diamonds) desc limit ".$limit;
                $history_list=$GLOBALS['db']->getAll($pay_history,true,true);
                $list_arr=array_merge($sql_list,$history_list);


                foreach($list_arr as $k=>$v){
                    if(!isset($for_list[$v['user_id']])){
                        $for_list[$v['user_id']]=$v;
                    }else{
                        $for_list[$v['user_id']]['num']+=$v['num'];

                    }
                }
                $list=array_values($for_list);
            }else{
                $sql ="select u.id as user_id ,u.nick_name,u.v_icon,u.head_image,u.sex,u.user_level,sum(v.total_ticket) as num
                from ".$table." as v LEFT JOIN  ".DB_PREFIX."user as u on u.id = v.from_user_id
											where v.prop_id<>12 and v.create_ym=".to_date(NOW_TIME,'Ym')." and v.create_d=".to_date(NOW_TIME,'d')."
                and v.to_user_id=".$user_id." GROUP BY v.from_user_id
											order BY sum(v.total_diamonds) desc limit ".$limit;
                $sql_list=$GLOBALS['db']->getAll($sql,true,true);

                $list=$sql_list;
            }

            $GLOBALS['cache']->set($this->key, $list, $cache_time, true);//缓存时间 1800秒
            $GLOBALS['cache']->set($key_bf, $list, 86400, true);//备份


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

		$GLOBALS['cache']->clear_by_name($this->key);
	}
}
?>