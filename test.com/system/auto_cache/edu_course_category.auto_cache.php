<?php

class edu_course_category_auto_cache extends auto_cache
{
    private $key = "edu_course_category";

    //参数有：act，is_recommend ，limit
    public function load($param, $is_real = false)
    {
        if ($param['act'] != '') {
            $this->key .= ":" . $param['act'] . ":";
        } else {
            $this->key .= ":list:";
        }
        $this->key .= md5(serialize($param));
        $list = $GLOBALS['cache']->get($this->key);

        $key_bf = $this->key . '_bf';

        if ($list === false || $is_real) {
            $is_ok = $GLOBALS['cache']->set_lock($this->key);
            if (!$is_ok) {
                $list = $GLOBALS['cache']->get($key_bf, true);
            } else {
                $where = " cc.is_effect = 1 ";

                if ($param['is_recommend'] == 1) {
                    $where .= " and is_recommend=1";
                }

                if ($param['limit'] > 0) {
                    $param['limit'] = intval($param['limit']);
                    $limit = " limit " . $param['limit'] . "";
                } else {
                    $limit = '';
                }

                $order = " order by cc.is_recommend desc,cc.sort desc ";

                $sql = "select cc.id as cate_id,cc.title,cc.image,cc.icon,cc.icon_selected from " . DB_PREFIX . "edu_course_category as cc
						where " . $where . $order . $limit . "";

                $list = $GLOBALS['db']->getAll($sql, true, true);
                $list_out = array();
                foreach ($list as $k => $v) {
                    $list[$k]['image'] = get_spec_image($v['image'], 710, 300);
                    $list[$k]['icon'] = get_spec_image($v['icon']);
                    $list[$k]['icon_selected'] = get_spec_image($v['icon_selected']);

                    $list_out[$v['cate_id']] = $list[$k];
                }

                $list_out = array_values($list_out);

                $GLOBALS['cache']->set($this->key, $list_out, 10, true);
                $GLOBALS['cache']->set($key_bf, $list_out, 86400, true);//备份
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