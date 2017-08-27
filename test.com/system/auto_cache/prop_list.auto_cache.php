<?php

class prop_list_auto_cache extends auto_cache{
	private $key = "prop:list";
	
	public function load($param)
	{
		$list = $GLOBALS['cache']->get($this->key);

		if($list === false)
		{
            $m_config =  load_auto_cache("m_config");
            $sql = "select id,name,score,diamonds,icon,pc_icon,pc_gif,ticket,is_much,sort,is_red_envelope,is_animated,anim_type from ".DB_PREFIX."prop where is_effect = 1 order by sort desc";
            if($m_config['ios_check_version'] != ''){
                $sql = "select id,name,score,diamonds,icon,pc_icon,pc_gif,ticket,is_much,sort,is_red_envelope,is_animated,anim_type from ".DB_PREFIX."prop where is_effect = 1 and is_red_envelope<>1 order by sort desc";
            }
            $list = $GLOBALS['db']->getAll($sql,true,true);

			foreach ( $list as $k => $v )
			{
				$list[$k]['icon'] = get_spec_image($v['icon']);
                $list[$k]['ticket'] = intval($v['ticket']) ;
				$list[$k]['score_fromat'] = '+'.$v['score'].'经验值';
			}
			
			$GLOBALS['cache']->set($this->key,$list);
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