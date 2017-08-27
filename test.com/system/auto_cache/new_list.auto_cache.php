<?php

/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2016/10/13
 * Time: 15:29
 */
class new_list_auto_cache extends auto_cache
{
    private $key = "new:list";

    public function load($param)
    {
        if ($param['change'] == 1) {
            $sql = "SELECT
                    v.id AS room_id,
                    v.sort_num,
                    v.group_id,
                    v.user_id,
                    v.city,
                    v.title,
                    v.cate_id,
                    v.live_in,
                    v.video_type,
                    v.room_type,
                    v.room_title,
                    (v.robot_num + v.virtual_watch_number + v.watch_number) AS watch_number,
                    v.head_image,
                    v.thumb_head_image,
                    v.xpoint,
                    v.ypoint,
                    u.v_type,
                    u.v_icon,
                    u.nick_name,
                    u.user_level
                FROM
                    " . DB_PREFIX . "video v
                        LEFT JOIN
                    " . DB_PREFIX . "user u ON u.id = v.user_id
                WHERE
                    v.live_in IN (1 , 3) AND v.room_type = 3 and UNIX_TIMESTAMP(NOW())-from_unixtime(u.create_time) < 30*24*60
                ORDER BY RAND() , u.create_time DESC
                LIMIT 6";

            $list = $GLOBALS['db']->getAll($sql, true, true);
            foreach ($list as $k => $v) {
                $list[$k]['live_image'] = get_live_image($v);
                $list[$k]['head_image'] = get_spec_image($v['head_image'],40,40);
                $list[$k]['video_url'] = get_video_url($v['room_id'], $v['live_in']);
                if ($v['thumb_head_image'] == ''){
                    $list[$k]['thumb_head_image'] = get_spec_image($v['head_image'],40,40);
                }else{
                    $list[$k]['thumb_head_image'] = get_spec_image($v['thumb_head_image'],40,40);
                }
            }
        } else {
            $key_bf = $this->key . '_bf';
            $list = $GLOBALS['cache']->get($this->key, true);
            $page_size = 6;
            $page = $param['page'];
            if ($page <= 0) {
                $page = 1;
            }
            if ($list === false) {
                $is_ok = $GLOBALS['cache']->set_lock($this->key);
                if (!$is_ok) {
                    $list = $GLOBALS['cache']->get($key_bf, true);
                } else {
                    $limit = (($page - 1) * $page_size) . "," . $page_size;

                    $sql = "SELECT
                            v.id AS room_id,
                            v.sort_num,
                            v.group_id,
                            v.user_id,
                            v.city,
                            v.title,
                            v.cate_id,
                            v.live_in,
                            v.video_type,
                            v.room_type,
                            (v.robot_num + v.virtual_watch_number + v.watch_number) AS watch_number,
                            v.live_image,
                            v.head_image,
                            v.thumb_head_image,
                            v.xpoint,
                            v.ypoint,
                            u.v_type,
                            u.v_icon,
                            u.nick_name,
                            u.user_level
                        FROM
                            " . DB_PREFIX . "video v
                                LEFT JOIN
                            " . DB_PREFIX . "user u ON u.id = v.user_id
                        WHERE
                            v.live_in IN (1 , 3) AND v.room_type = 3 and UNIX_TIMESTAMP(NOW())-from_unixtime(u.create_time) < 30*24*60
                        ORDER BY u.create_time DESC
                        LIMIT " . $limit;

                    $list = $GLOBALS['db']->getAll($sql, true, true);
                    foreach ($list as $k => $v) {
                        $list[$k]['live_image'] = get_live_image($v);
                        $list[$k]['head_image'] = get_spec_image($v['head_image'],40,40);
                        $list[$k]['video_url'] = get_video_url($v['room_id'], $v['live_in']);
                        if ($v['thumb_head_image'] == ''){
                            $list[$k]['thumb_head_image'] = get_spec_image($v['head_image'],40,40);
                        }else{
                            $list[$k]['thumb_head_image'] = get_spec_image($v['thumb_head_image'],40,40);
                        }
                    }
                    $GLOBALS['cache']->set($this->key, $list, 10, true);
                    $GLOBALS['cache']->set($key_bf, $list, 86400, true);//备份
                }
            }
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