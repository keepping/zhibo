<?php
//底部文章
class index_image_auto_cache extends auto_cache{
	public function load($param,$is_real)
	{
		$param=array();
		$key = $this->build_key(__CLASS__,$param);
 		$GLOBALS['cache']->set_dir(APP_ROOT_PATH."public/runtime/data/".__CLASS__."/");
		$index_image = $GLOBALS['cache']->get($key);

		if($index_image === false||!$is_real)
		{
			$index_image_array=array();
  			$index_image = $GLOBALS['db']->getAll("select image from ".DB_PREFIX."index_image order by sort asc");
  			foreach($index_image as $k=>$v){
 				$index_image_array[$k] = add_domain_url($v['image']);
			}


  			$index_image=$index_image_array;
  			$GLOBALS['cache']->set_dir(APP_ROOT_PATH."public/runtime/data/".__CLASS__."/");
			$GLOBALS['cache']->set($key,$index_image);
		}
		
		return $index_image;
	}
	public function rm($param)
	{
		$key = $this->build_key(__CLASS__,$param);
		$GLOBALS['cache']->set_dir(APP_ROOT_PATH."public/runtime/data/".__CLASS__."/");
		$GLOBALS['cache']->rm($key);
	}
	public function clear_all()
	{
		$GLOBALS['cache']->set_dir(APP_ROOT_PATH."public/runtime/data/".__CLASS__."/");
		$GLOBALS['cache']->clear();
	}
}
?>