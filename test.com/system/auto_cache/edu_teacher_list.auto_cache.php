<?php

class edu_teacher_list_auto_cache extends auto_cache{
	private $key = "edu:teacher:list:";
	
	//参数有：act，is_recommend ，limit
	public function load($param)
	{
		
		$this->key .= md5(serialize($param));
		$list = $GLOBALS['cache']->get($this->key);
		
		$key_bf = $this->key.'_bf';
		
		if($list === false)
		{
			$is_ok =  $GLOBALS['cache']->set_lock($this->key);
			if(!$is_ok){
				$list = $GLOBALS['cache']->get($key_bf,true);
			}else{
				$where=" t.is_recommend = 1";
			
				if($param['order']!='')
					$order=$param['order'];
				else
					$order=" order by t.sort desc,t.id desc ";
					
				if($param['page']>0)
				{
					$page=$param['page'];
					$page_size=$param['page_size'];
					$limit=" limit ".$page_size*($page-1).",".$page_size;
				}
				else if($param['limit'] >0){
					$param['limit']=intval($param['limit']);
					$limit=" limit ".$param['limit']." ";
				}else{
					$limit='';
				}
							
				$sql = "select t.id,t.title,t.user_id,t.desc_image as image,t.sale_count as booking_count,t.tags,t.description" .
						",u.nick_name as teacher,u.head_image,u.user_level,u.is_authentication" .
						" from ".DB_PREFIX."edu_teacher as t " .
						" left join ".DB_PREFIX."user as u on u.id =t.user_id" .
						" where ".$where." ".$order." ".$limit."";
				
				$list = $GLOBALS['db']->getAll($sql,true,true);
				foreach($list as $k=>$v){
					$list[$k]['image'] =get_spec_image($v['image'], 290, 184);
                    $list[$k]['head_image'] =get_spec_image($v['head_image']);
                    $list[$k]['is_authentication'] = $v['is_authentication'] == 2 ? true : false;
					if($v['tags'] !='')
					{
						$list[$k]['tags']=explode(',',$v['tags']);
					} else {
					    $list[$k]['tags'] = array();
                    }
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
		$GLOBALS['cache']->rm($this->key);
	}
	
	public function clear_all()
	{
		$GLOBALS['cache']->rm($this->key);
	}
}
?>