<?php
//底部文章
class weixin_conf_auto_cache extends auto_cache{
	public function load($param)
	{
		return false;
	}
	public function rm($param)
	{
		return false;
	}
	public function clear_all()
	{
		return false;
	}
}
?>