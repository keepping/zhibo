<?php

class sex_area_auto_cache extends auto_cache{
	private $key = "sex:area:";
	
	public function load($param)
	{
		$sex = intval($param['sex']);//性别 0:全部, 1-男，2-女
		
		$this->key .= $sex;
		
		$key_bf = $this->key.'_bf';
		
		$list = $GLOBALS['cache']->get($this->key);

		if($list === false)
		{
			$is_ok =  $GLOBALS['cache']->set_lock($this->key);
			if(!$is_ok){
				$list = $GLOBALS['cache']->get($key_bf,true);
			}else{
                $m_config =  load_auto_cache("m_config");//初始化手机端配置
				if ($sex == 0){
					$sql = "select province as city,count(*) as number from ".DB_PREFIX."video where live_in in (1,3) ";
				}else{
					$sql = "select province as city,count(*) as number from ".DB_PREFIX."video where live_in in (1,3) and sex = ".$sex;
				}

                if((defined('OPEN_ROOM_HIDE')&&OPEN_ROOM_HIDE==1)&&intval($m_config['open_room_hide'])==1){
                    $sql.= " and province <> '火星' and province <>''";
                }

                $sql.=" group by province";

				$total_num = 0;
				$list = $GLOBALS['db']->getAll($sql,true,true);
				foreach($list as $k=>$v){
					$total_num = $total_num + $v['number'];
				}
				
				$hot = array();
				if($total_num>0){
					$hot[] = array(
							'city'=>'热门',
							'number'=>$total_num
					);
					$list = array_merge($hot,$list);
				}
				
				$GLOBALS['cache']->set($this->key,$list,10,true);
				
				$GLOBALS['cache']->set($key_bf, $list, 86400, true);//备份
			}
		}
		
		if ($list == false) $list = array();
		
		return $list;
	}
	
	public function rm($param)
	{
		$GLOBALS['cache']->clear_by_name($this->key);
	}
	
	public function clear_all()
	{
		$GLOBALS['cache']->clear_by_name($this->key);
	}
}
?>