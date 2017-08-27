<?php

class es_session
{
	static function id()
	{
		return session_id();
	}
	static function set_sessid($sess_id)
	{
		session_id($sess_id);
	}


	// 获取某个session值
	static function get($name) {
		$value   = $_SESSION[app_conf("AUTH_KEY").$name];
		return $value;
	}

	static function start()
	{
		 return true;
	}


	// 设置某个session值
	static function set($name,$value) {
		$_SESSION[app_conf("AUTH_KEY").$name]  =   $value;
	}

	// 删除某个session值
	static function delete($name) {
		unset($_SESSION[app_conf("AUTH_KEY").$name]);
	}
	static function is_set($name) {
 		$tag = isset($_SESSION[app_conf("AUTH_KEY").$name]);
		return $tag;
	}
	//关闭session的读写
	static function close()
	{
		@session_write_close();
	}
	/**
	+----------------------------------------------------------
	 * 设置Session gc_maxlifetime值
	 * 返回之前设置
	+----------------------------------------------------------
	 * @param string $gc_maxlifetime
	+----------------------------------------------------------
	 * @static
	 * @access public
	+----------------------------------------------------------
	 * @return string
	+----------------------------------------------------------
	 */
	static function setGcMaxLifetime($gcMaxLifetime = null)
	{
		$return = ini_get('session.gc_maxlifetime');
		if (isset($gcMaxLifetime) && is_int($gcMaxLifetime) && $gcMaxLifetime >= 1) {
			ini_set('session.gc_maxlifetime', $gcMaxLifetime);
		}
		return $return;
	}

}
//end session
?>