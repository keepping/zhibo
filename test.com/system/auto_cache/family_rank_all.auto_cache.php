<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2016/10/26
 * Time: 17:45
 */

class family_rank_all_auto_cache extends auto_cache{
    private $key = "family_rank:";
    public function load($params = array())
    {
        $sort = $params['sort'] ? $params['sort'] : 'user_count';
        $limit = $params['limit'] ? $params['limit'] : 10;
        $this->key .= "{$sort}_{$limit}";
        $key_bf = $this->key.'_bf';
        $type=$params['type'];
        $list = $GLOBALS['cache']->get($this->key,true);

        if ($list === false) {
            $is_ok =  $GLOBALS['cache']->set_lock($this->key);
            if(!$is_ok){
                $list = $GLOBALS['cache']->get($key_bf,true);
            }else{
                $list=array();
                $list = $this->family_ceil($sort, $limit);
                if($type=='all'){
                    //数据处理
                    $list['day']= $this->family_ceil($sort, $limit,"day");
                    $list['weeks'] = $this->family_ceil($sort, $limit,"weeks");
                    $list['month']= $this->family_ceil($sort, $limit,"month");
                    $list['all'] = $this->family_ceil($sort, $limit);
                }

                $m_config =  load_auto_cache("m_config");//初始化手机端配置

                //缓存更新时间
                $rank_cache_time = intval($m_config['rank_cache_time'])>0?intval($m_config['rank_cache_time']):300;
                $GLOBALS['cache']->set($this->key, $list, $rank_cache_time, true);
                $GLOBALS['cache']->set($key_bf, $list, 86400, true);//备份
            }
        }

        if ($list == false) $list = array();

        return $list;
    }
   //家族排行数据来源
    public function family_ceil($sort = 'user_count', $limit = 10,$type)
    {
        $where = " f.user_id = u.id AND f.status = 1 ";
        if ($type == 'day') {
            $where .= " and create_d = day(CURDATE()) ";
        }
        if ($type == 'weeks') {
            $where .= "  and create_w = week(CURDATE()) ";
        }
        if ($type == 'month') {
            $where .= "  and create_m = month(CURDATE()) ";
        }
        $sql = "
SELECT
    f.id AS family_id,
    f.logo AS family_logo,
    f.name AS family_name,
    f.user_id,
    u.nick_name,
    f.create_time,
    f.user_count
FROM
    " . DB_PREFIX . "family AS f,
    " . DB_PREFIX . "user AS u
WHERE
    {$where}
ORDER BY {$sort} DESC, f.contribution desc, f.video_time desc, f.create_time desc
LIMIT {$limit}";

        $family = $GLOBALS['db']->getAll($sql);
        foreach ($family as $k => $v) {
            $family[$k]['family_url'] = url("family#info", array("family_id" => $v['family_id']));
            $family[$k]['v_icon'] = get_domain().'/public/images/rank/rank_'.$v["family_level"].'.png';
        }

        return $family;
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