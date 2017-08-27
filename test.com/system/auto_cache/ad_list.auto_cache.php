<?php
//底部文章
class ad_list_auto_cache extends auto_cache
{
	private $key = "ad:list:";

	public function load($place_id, $is_real)
	{
		$this->key .= $place_id;
		$key_bf = $this->key . '_bf';

		$ad_list = $GLOBALS['cache']->get($this->key, true);
		if ($ad_list === false || !$is_real) {
			$is_ok = $GLOBALS['cache']->set_lock($this->key);
			if (!$is_ok) {
				return $GLOBALS['cache']->get($key_bf, true);
			}

			$now = to_date(NOW_TIME, 'Y-m-d H:i:s');
			$ad_list = $GLOBALS['db']->getAll("select title,url,image from " . DB_PREFIX . "ad where place_id=" . $place_id . " and begin_time < '" . $now . "' and end_time > '" . $now . "' order by sort asc");
			foreach ($ad_list as &$v) {
				$v['image'] = add_domain_url($v['image']);
			}

			$GLOBALS['cache']->set($this->key, $ad_list, 1800, true);
			$GLOBALS['cache']->set($key_bf, $ad_list, 86400, true);//备份
		}

		return $ad_list;
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