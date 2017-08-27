<?php

class selectpc_live_video_auto_cache extends auto_cache
{
    private $key = "selectpc:live:video:";

    public function load($param)
    {
        if (empty($param['index_recommend'])) {
            return array();
        }

        $this->key .= md5(serialize($param));
        $key_bf = $this->key . '_bf';

        $page = $param['page'] > 0 ? $param['page'] : 1;
        $page_size = $param['page_size'] > 0 ? $param['page_size'] : 10;
        $limit = (($page - 1) * $page_size) . "," . $page_size;

        $list = $GLOBALS['cache']->get($this->key, true);
        if ($list === false) {
            $is_ok = $GLOBALS['cache']->set_lock($this->key);
            if (!$is_ok) {
                $list = $GLOBALS['cache']->get($key_bf, true);
            } else {
                $sql = "SELECT v.id AS room_id, v.channelid, v.begin_time, v.create_time, v.play_url, v.play_flv, v.play_hls, v.sort_num, v.group_id, v.user_id, v.city, v.title, v.cate_id, v.live_in, v.video_type, v.room_type,
						(v.robot_num + v.virtual_watch_number + v.watch_number) as watch_number, v.live_image, v.head_image,v.thumb_head_image, v.xpoint,v.ypoint,
						u.v_type, u.v_icon, u.nick_name,u.user_level FROM " . DB_PREFIX . "video v 
					LEFT JOIN " . DB_PREFIX . "user u ON u.id = v.user_id where v.live_in = 1 ";

                $m_config = load_auto_cache("m_config");//初始化手机端配置
                $has_is_authentication = intval($m_config['has_is_authentication']) ? 1 : 0;
                if ($has_is_authentication && $m_config['ios_check_version'] == '') {
                    $sql .= "and u.is_authentication = 2 ";
                }

                $recommend_user = explode(',', $param['index_recommend']);
                foreach ($recommend_user as &$item) {
                    $item = intval($item);
                }
                unset($item);

                $sql .= ' and v.user_id in (' . implode(',', $recommend_user) . ')';
                $sql .= ' and v.room_type = 3'; //1:私密直播;3:直播

                $sql .= " order by v.sort_num desc,v.sort desc";
                $sql .= " limit " . $limit;
                $list = $GLOBALS['db']->getAll($sql, true, true);

                foreach ($list as $k => &$v) {
                    $v['thumb_head_image'] = get_spec_image(empty($v['thumb_head_image']) ? $v['head_image'] : $v['thumb_head_image'],
                        40, 40);
                    $v['live_image'] = get_spec_image(empty($v['live_image']) ? $v['head_image'] : $v['live_image'],
                        320, 180, 1);
                    $v['head_image'] = get_spec_image($v['head_image'], 40, 40);
                    $v['video_url'] = get_video_url($v['room_id'], $v['live_in']);
                }
                unset($v);

                $GLOBALS['cache']->set($this->key, $list, 10, true);
                $GLOBALS['cache']->set($key_bf, $list, 86400, true);//备份
            }
        }
        if ($list == false) {
            $list = array();
        }

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
