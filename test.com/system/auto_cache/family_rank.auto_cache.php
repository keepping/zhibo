<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2016/10/26
 * Time: 17:45
 */

class family_rank_auto_cache extends auto_cache{
    private $key = "family_rank:";
    public function load($param)
    {
        $key_bf = $this->key.'_bf';

        $list = $GLOBALS['cache']->get($this->key,true);

        if ($list === false) {
            $is_ok =  $GLOBALS['cache']->set_lock($this->key);
            if(!$is_ok){
                $list = $GLOBALS['cache']->get($key_bf,true);
            }else{
                $m_config =  load_auto_cache("m_config");//初始化手机端配置

                $rank_cache_time = intval($m_config['rank_cache_time'])>0?intval($m_config['rank_cache_time']):300;
                //数据处理
                $family_day = $this->family_ceil("day");
                $family_weeks = $this->family_ceil("weeks");
                $family_month = $this->family_ceil("month");
                $family_all = $this->family_ceil();

                $list= array(
                    'day' => $family_day,
                    'weeks' => $family_weeks,
                    'month' => $family_month,
                    'all' => $family_all,
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

    public function family_ceil($type)
    {
        $where = " 1=1 ";
        if ($type == 'day') {
            $where = " create_d = day(CURDATE()) ";
        }
        if ($type == 'weeks') {
            $where = " create_w = week(CURDATE()) ";
        }
        if ($type == 'month') {
            $where = " create_m = month(CURDATE()) ";
        }

        $sql = "SELECT j.id as family_id,j.logo as family_logo,j.name as family_name,j.user_id,j.create_time,j.family_level,(SELECT icon FROM " . DB_PREFIX . "family_level fl where fl.level=j.family_level ) as v_icon,j.user_count FROM " . DB_PREFIX . "family as j where $where and j.status=1 order by j.user_count desc,j.contribution desc,j.video_time desc,j.create_time desc limit 0,10";

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