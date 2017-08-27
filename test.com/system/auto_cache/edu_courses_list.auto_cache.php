<?php

class edu_courses_list_auto_cache extends auto_cache
{
    private $key = "edu:courses:list:";

    //参数有：act，is_recommend ，limit
    public function load($param)
    {

        if (!$param['type']) {
            $param['type'] = 0;//0课堂,1约课一对一,2线下预约
        }
        $this->key .= md5(serialize($param));
        $list = $GLOBALS['cache']->get($this->key);

        $key_bf = $this->key . '_bf';

        if ($list === false) {
            $is_ok = $GLOBALS['cache']->set_lock($this->key);
            if (!$is_ok) {
                $list = $GLOBALS['cache']->get($key_bf, true);
            } else {
                $where = " cou.is_effect = 1 and cou.is_delete = 0";

                if ($param["cate_id"] > 0) {
                    $where .= " and category_id=" . intval($param["cate_id"]) . "";
                }

                if ($param['order'] != '') {
                    $order = $param['order'];
                } else {
                    $order = " order by cou.is_recommend desc,cou.sort desc,cou.id desc ";
                }

                if ($param['page'] > 0) {
                    $page = $param['page'];
                    $page_size = $param['page_size'];
                    $limit = " limit " . $page_size * ($page - 1) . "," . $page_size;
                } else {
                    if ($param['limit'] > 0) {
                        $param['limit'] = intval($param['limit']);
                        $limit = " limit " . $param['limit'] . " ";
                    } else {
                        $limit = '';
                    }
                }

                $sql = "select cou.id,cou.title,cou.image,cou.user_id,cou.price,cou.courses_count,cou.sale_count,cou.tags" .
                    ",u.nick_name as teacher,u.head_image,u.is_authentication" .
                    " from " . DB_PREFIX . "edu_courses as cou " .
                    " left join " . DB_PREFIX . "user as u on u.id =cou.user_id" .
                    " where " . $where . " " . $order . " " . $limit . "";

                $list = $GLOBALS['db']->getAll($sql, true, true);
                foreach ($list as $k => $v) {
                    $list[$k]['image'] = get_spec_image($v['image'], 346, 220);
                    $list[$k]['head_image'] = get_spec_image($v['head_image']);
                    $list[$k]['is_authentication'] = $v['is_authentication'] == 2 ? true : false;
                    if ($v['tags'] != '') {
                        $list[$k]['tags'] = explode(',', $v['tags']);
                    } else {
                        $list[$k]['tags'] = array();
                    }
                }

                $GLOBALS['cache']->set($this->key, $list, 10, true);
                $GLOBALS['cache']->set($key_bf, $list, 86400, true);//备份
            }
        }

        if ($list == false) {
            $list = array();
        }

        return $list;
    }

    public function rm($param)
    {
        $GLOBALS['cache']->rm($this->key);
    }

    public function clear_all()
    {
        $GLOBALS['cache']->rm($this->key);
    }
}

?>