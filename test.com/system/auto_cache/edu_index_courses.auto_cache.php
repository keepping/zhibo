<?php

class edu_index_courses_auto_cache extends auto_cache
{
    private $key = "edu:index:courses:";

    //参数有：act，is_recommend ，limit
    public function load($param, $is_real = false)
    {

        if ($param['list_type'] != '') {
            $this->key .= $param['list_type'] . ":";
        }
        $this->key .= md5(serialize($param));
        $list = $GLOBALS['cache']->get($this->key);

        $key_bf = $this->key . '_bf';

        if ($list === false || $is_real) {
            $is_ok = $GLOBALS['cache']->set_lock($this->key);
            if (!$is_ok) {
                $list = $GLOBALS['cache']->get($key_bf, true);
            } else {
                $where = " cou.is_effect = 1 and cou.is_delete = 0";

                if ($param['is_recommend'] == 1) {
                    $where .= " and cou.is_recommend=1 ";
                }

                if ($param['limit'] > 0) {
                    $param['limit'] = intval($param['limit']);
                    $limit = " limit " . $param['limit'] . " ";
                } else {
                    $limit = '';
                }

                if ($param['order'] != '') {
                    $order = $param['order'];
                } else {
                    $order = " order by cou.is_recommend desc,cou.sort desc,cou.id desc ";
                }

                $sql = "select cou.id,cou.title,cou.image from " . DB_PREFIX . "edu_courses as cou 
						where " . $where . " " . $order . " " . $limit . "";

                $list = $GLOBALS['db']->getAll($sql, true, true);
                foreach ($list as $k => $v) {
                    $list[$k]['image'] = get_spec_image($v['image']);
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