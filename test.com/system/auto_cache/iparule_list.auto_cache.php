<?php

class iparule_list_auto_cache extends auto_cache{
	private $key = "iparule:list";
	public function load($param)
	{
		$list = $GLOBALS['cache']->get($this->key);

		if($list === false)
		{
			//unserialize(
			$sql = "select id,name,iap_money as money,(iap_diamonds + gift_diamonds) as diamonds,gift_coins,iap_money,gift_diamonds,iap_diamonds from ".DB_PREFIX."recharge_rule where is_effect = 1 and is_delete = 0 and product_id != '' order by sort";
			$list = $GLOBALS['db']->getAll($sql,true,true);
			$GLOBALS['cache']->set($this->key,$list,3600, true);
		}
		foreach($list as $k=>$v){
			$list[$k]['name'] = '钻石'.$v['iap_diamonds']."(赠送".$v['gift_diamonds']."钻石)";
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