<?php

/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2016/10/26
 * Time: 17:45
 */
class rich_list_auto_cache extends auto_cache
{
    private $key = "rich_list:";

    public function load($param)
    {
        $this->key .= md5(serialize($param));
        $key_bf = $this->key . '_bf';

        $list = $GLOBALS['cache']->get($this->key, true);

        if ($list === false) {
            $is_ok = $GLOBALS['cache']->set_lock($this->key);
            if (!$is_ok) {
                $list = $GLOBALS['cache']->get($key_bf, true);
            } else {
                $m_config = load_auto_cache("m_config");//初始化手机端配置
                //缓存更新时间
                $rank_cache_time = intval($m_config['rank_cache_time']) > 0 ? intval($m_config['rank_cache_time']) : 300;
                //数据处理
                $live_list = $this->get_live();
                $data = $this->rich_ceil(isset($param['page_size']) ? $param['page_size'] : 10);

                $list = array(
                    'day' => $this->is_live($data['day'], $live_list),
                    'pre_day' => $this->is_live($data['pre_day'], $live_list),
                    'weeks' => $this->is_live($data['weeks'], $live_list),
                    'pre_weeks' => $this->is_live($data['pre_weeks'], $live_list),
                    'month' => $this->is_live($data['month'], $live_list),
                    'all' => $this->is_live($data['all'], $live_list),
                );

                //数据处理结束
                $GLOBALS['cache']->set($this->key, $list, $rank_cache_time, true);
                $GLOBALS['cache']->set($key_bf, $list, 86400, true);//备份
            }
        }

        if ($list == false) {
            $list = array();
        }

        return $list;
    }

    //给榜单中正在直播的用户添加直播链接
    public function is_live($data, $live_list)
    {
        foreach ($data as $k => $v) {
            foreach ($live_list as $kk => $vv) {
                if ($vv['user_id'] == $v['user_id']) {
                    $data[$k]['live_in'] = $vv['live_in'];
                    $data[$k]['room_id'] = $vv['room_id'];
                    $data[$k]['watch_number'] = $vv['watch_number'];
                    $data[$k]['title'] = $vv['title'];
                    $data[$k]['video_url'] = get_video_url($vv['room_id'], $vv['live_in']);

                    $data[$k]['group_id'] = $vv['group_id'];
                    $data[$k]['live_image'] = get_spec_image(empty($vv['live_image']) ? $vv['head_image'] : $vv['live_image']);
                    $data[$k]['create_type'] = $vv['create_type'];
                    $data[$k]['video_type'] = $vv['video_type'];
                }
            }
            if (empty($data[$k]['video_url'])) {
                $data[$k]['video_url'] = url('live#show', array('podcast_id' => $v['user_id']));
            }
            $data[$k]['user_level_ico'] = get_spec_image("./public/images/rank/rank_" . $v['user_level'] . ".png");
        }
        return $data;
    }

    //获取当前直播中的用户列表
    public function get_live()
    {
        $sql = "SELECT v.id AS room_id, v.sort_num, v.group_id, v.user_id, v.city, v.title, v.cate_id, v.live_in, v.video_type, v.room_type, v.create_type,
						(v.robot_num + v.virtual_watch_number + v.watch_number) as watch_number, v.live_image, v.head_image,v.thumb_head_image, v.xpoint,v.ypoint,
						u.v_type, u.v_icon, u.nick_name,u.user_level FROM " . DB_PREFIX . "video v
					LEFT JOIN " . DB_PREFIX . "user u ON u.id = v.user_id where v.live_in in (1,3) and v.room_type = 3 order by v.create_time,v.sort_num desc,v.sort desc";
        $live_list = $GLOBALS['db']->getAll($sql, true, true);

        return $live_list;
    }

    //财富榜数据
    public function rich_ceil($page_size = 10)
    {
        $pre_time = NOW_TIME - 60 * 10;
        $limit = "0,{$page_size}";

        $root['month'] = $this->month($limit);

        $root['day'] = $this->day($limit);
        $root['pre_day'] = $this->day($limit, $pre_time);

        $root['weeks'] = $this->weeks($limit);
        $root['pre_weeks'] = $this->weeks($limit, $pre_time);

        $sql = "select u.id as user_id ,u.nick_name,u.v_type,u.v_icon,u.head_image,u.sex,u.user_level,u.use_diamonds as ticket ,u.is_authentication
											from " . DB_PREFIX . "user as u where u.is_effect=1 and u.use_diamonds>0
											order BY u.use_diamonds desc limit " . $limit;

        $root['all'] = $GLOBALS['db']->getAll($sql);
        return $root;
    }


    private function day($limit, $pre_time = NOW_TIME)
    {
        return $this->get_data("create_d = day(curdate()) and is_red_envelope = 0", $limit, $pre_time);
    }

    private function weeks($limit, $pre_time = NOW_TIME)
    {
        return $this->get_data('create_w = WEEK(curdate())', $limit, $pre_time);
    }

    private function month($limit)
    {
        return $this->get_data("create_ym = " . to_date(NOW_TIME, 'Ym'), $limit, NOW_TIME);
    }

    private function get_data($where, $limit, $pre_time)
    {
        $table = createPropTable();
        $last_month_table = createPropTable(to_timespan("first day of last month"));
        $sql = "SELECT
	u.id AS user_id ,
	u.nick_name ,
	u.v_type ,
	u.v_icon ,
	u.head_image ,
	u.sex ,
	u.user_level ,
	u.is_authentication ,
	sum(v.total_diamonds) AS use_ticket
FROM
	" . DB_PREFIX . "user AS u
INNER JOIN(
	SELECT
		from_user_id ,
		total_diamonds
	FROM
		{$table}
	WHERE
		{$where}
	AND create_time < {$pre_time}
		UNION ALL
		SELECT
			from_user_id ,
			total_diamonds
		FROM
		{$last_month_table}
		WHERE
		    {$where}
	    AND create_time < {$pre_time}
) AS v ON u.id = v.from_user_id
WHERE
	u.is_effect = 1
GROUP BY
	v.from_user_id
ORDER BY
	sum(v.total_diamonds) DESC
LIMIT {$limit}";

        return $GLOBALS['db']->getAll($sql);
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