<?php

class edu_deal_list_auto_cache extends auto_cache
{
    private $key = "edu:deal:list:";

    //参数有：
    public function load($param)
    {
        $this->key .= md5(serialize($param));
        $list = $GLOBALS['cache']->get($this->key);

        $key_bf = $this->key . '_bf';

        if ($list === false) {
            $is_ok = $GLOBALS['cache']->set_lock($this->key);
            if (!$is_ok) {
                $list = $GLOBALS['cache']->get($key_bf, true);
            } else {
                $where = " d.is_effect = 1 and d.is_delete = 0 and d.begin_time <='".to_date(NOW_TIME)."'";

                if($param['cate_id'] >0){
                    $where .=" and d.cate_id=".intval($param['cate_id'])."";
                }

                if($param['users']){
                    $where .=" and d.user_id in(".implode(',',$param['users']).")";
                }

                if($param['user_id']){
                    $where .=" and d.user_id =".intval($param['user_id'])."";
                }

                if($param['where'] !=''){
                    $where .=" ".$param['where'];
                }

                if ($param['order'] != '') {
                    $order = " order by ".$param['order']."";
                } else {
                    $order = " order by d.sort desc,d.id desc ";
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

                $sql = "select d.id,d.name,d.image,d.user_id,d.tags,d.price,d.limit_num,d.support_count,d.begin_time,d.end_time,d.video_begin_time,d.is_success" .
                    ",d.is_effect,d.deal_status,u.nick_name as teacher,u.head_image" .
                    " from " . DB_PREFIX . "edu_deal as d " .
                    " left join " . DB_PREFIX . "user as u on u.id =d.user_id" .
                    " where " . $where . " " . $order . " " . $limit . "";

                $list = $GLOBALS['db']->getAll($sql, true, true);
                foreach ($list as $k => $v) {
                    $item=FanweServiceCall('edu_deal','get_deal_common',$v);
                    $item['video_begin_time']=to_date(to_timespan($v['video_begin_time']),'Y-m-d');
                    $list[$k]=$item;
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