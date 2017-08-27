<?php

class pay_list_alipay_auto_cache extends auto_cache{
	private $key = "pay:list:alipay";
	public function load($param)
	{
		$m_config = load_auto_cache('m_config');
		$alipay_cache_time = intval($m_config['alipay_cache_time']);
		$alipay_cache_time = $alipay_cache_time<60?60:$alipay_cache_time;
		$list = $GLOBALS['cache']->get($this->key);
		if($list === false)
		{
			$list = load_auto_cache("pay_list_all",array('id'=>0));
			if(intval($list[0]['id'])>0){
				load_auto_cache("pay_list_all",array('id'=>intval($list[0]['id'])));
			}
			$GLOBALS['cache']->set($this->key,$list,$alipay_cache_time,true);
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