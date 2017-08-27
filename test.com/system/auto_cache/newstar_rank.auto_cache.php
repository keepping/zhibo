<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2016/12/6
 * Time: 15:37
 */
class newstar_rank_auto_cache extends auto_cache{
    private $key = "rank:new_star";
    public function load($param)
    {
        $key_bf = $this->key.'_bf';

        $list = $GLOBALS['cache']->get($this->key,true);

        if ($list === false||true) {
            $is_ok =  $GLOBALS['cache']->set_lock($this->key);
            if(!$is_ok){
                $list = $GLOBALS['cache']->get($key_bf,true);
            }else{
                $m_config =  load_auto_cache("m_config");//初始化手机端配置
                //缓存更新时间
                $rank_cache_time = intval($m_config['rank_cache_time'])>0?intval($m_config['rank_cache_time']):300;
                //数据处理
                $live_list = $this->get_live();
                $date = $this->newstar_ceil();
                //判断是否在直播
                $date['day'] = $this->is_live($date['day'],$live_list);
                $date['weeks'] = $this->is_live($date['weeks'],$live_list);
                $date['month'] = $this->is_live($date['month'],$live_list);
                $date['all']=$this->is_live($date['all'],$live_list);
                $list=array(
                    'day' => $date['day'],
                    'weeks' => $date['weeks'],
                    'month' => $date['month'],
                    'all'=>$date['all']
                );

                //数据处理结束

                $GLOBALS['cache']->set($this->key, $list, $rank_cache_time, true);

                $GLOBALS['cache']->set($key_bf, $list, 86400, true);//备份
                //echo $this->key;
            }
        }

        if ($list == false) $list = array();

        return $list;
    }

    //给榜单中正在直播中的主播添加直播链接
    public function is_live($data, $live_list)
    {
        foreach ($data as $k => $v) {
            foreach ($live_list as $kk => $vv) {
                if ($vv['user_id'] == $v['user_id']) {
                    $data[$k]['live_in'] = $vv['live_in'];
                    $data[$k]['room_id'] = $vv['room_id'];
                    if($vv['live_in']==1){
                        $data[$k]['video_url'] = get_video_url($vv['room_id'], $vv['live_in']);
                        break 1;
                    }else{
                        $data[$k]['video_url'] = get_video_url($vv['room_id'], $vv['live_in']);
                    }

                }
            }
            if(empty($data[$k]['video_url']))
            {
                $data[$k]['video_url'] = url('live#show', array('podcast_id' => $v['user_id']));
            }
            $data[$k]['user_level_ico'] = get_spec_image("./public/images/rank/rank_" . $v['user_level'] . ".png");
        }
        return $data;
    }
    //当前直播
    public function get_live()
    {
        $sql = "SELECT v.id AS room_id, v.sort_num, v.group_id, v.user_id, v.city, v.title, v.cate_id, v.live_in, v.video_type, v.room_type,
						(v.robot_num + v.virtual_watch_number + v.watch_number) as watch_number, v.head_image,v.thumb_head_image, v.xpoint,v.ypoint,
						u.v_type, u.v_icon, u.nick_name,u.user_level FROM " . DB_PREFIX . "video v
					LEFT JOIN " . DB_PREFIX . "user u ON u.id = v.user_id where v.live_in in (1,3) and v.room_type = 3 order by v.create_time desc,v.sort_num desc,v.sort desc";
        $live_list = $GLOBALS['db']->getAll($sql, true, true);

        return $live_list;
    }
    // 魅力排行榜数据
    public function newstar_ceil()
    {

        $table = createPropTable();
        $limit = " 0,10 ";//取前十
        $where=" u.is_effect=1 and";
        $where .= " v.create_d = day(curdate()) and is_red_envelope = 0";//日榜条件
        $sql = "select u.id as user_id ,u.nick_name,u.v_type,u.v_icon,u.head_image,u.sex,u.user_level,sum(v.total_ticket) as use_ticket ,u.is_authentication
											from " . DB_PREFIX . "user as u INNER JOIN " . $table. " as v on u.id = v.to_user_id
											where TO_DAYS(NOW())-TO_DAYS(FROM_UNIXTIME(u.create_time)) <=30 and " . $where . " GROUP BY to_user_id
											order BY sum(v.total_ticket) desc limit " . $limit;

        $root['day'] = $GLOBALS['db']->getAll($sql);

        $where = " v.create_ym=".to_date(NOW_TIME,'Ym')." and is_red_envelope = 0";//月榜条件
        $sql = "select u.id as user_id ,u.nick_name,u.v_type,u.v_icon,u.head_image,u.sex,u.user_level,sum(v.total_ticket) as use_ticket ,u.is_authentication
											from " . DB_PREFIX . "user as u INNER JOIN " . $table." as v on u.id = v.to_user_id
											where TO_DAYS(NOW())-TO_DAYS(FROM_UNIXTIME(u.create_time)) <=30 and  " . $where . " GROUP BY to_user_id
											order BY sum(v.total_ticket) desc limit " . $limit;

        $root['month'] = $GLOBALS['db']->getAll($sql);

        $where = " v.create_w = week(curdate()) and is_red_envelope = 0";//周榜条件

        $sql = "select u.id as user_id ,u.nick_name,u.v_type,u.v_icon,u.head_image,u.sex,u.user_level,sum(v.total_ticket) as use_ticket ,u.is_authentication
											from " . DB_PREFIX . "user as u INNER JOIN " . $table. " as v on u.id = v.to_user_id
											where TO_DAYS(NOW())-TO_DAYS(FROM_UNIXTIME(u.create_time)) <=30 and " . $where . " GROUP BY to_user_id
											order BY sum(v.total_ticket) desc limit " . $limit;

        $root['weeks'] = $GLOBALS['db']->getAll($sql);
        //总榜
        $sql = "select u.id as user_id ,u.nick_name,u.v_type,u.v_icon,u.head_image,u.sex,u.user_level,sum(v.total_ticket) as use_ticket ,u.is_authentication
											from " . DB_PREFIX . "user as u INNER JOIN " . $table. " as v on u.id = v.to_user_id
											where u.is_effect=1 and TO_DAYS(NOW())-TO_DAYS(FROM_UNIXTIME(u.create_time)) <=30 and is_red_envelope = 0 GROUP BY to_user_id
											order BY sum(v.total_ticket) desc limit " . $limit;
        $root['all'] = $GLOBALS['db']->getAll($sql);
        return $root;
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