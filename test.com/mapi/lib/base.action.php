<?php
// +----------------------------------------------------------------------
// | Fanwe 方维p2p借贷系统
// +----------------------------------------------------------------------
// | Copyright (c) 2011 http://www.fanwe.com All rights reserved.
// +----------------------------------------------------------------------
// | Author: 云淡风轻(88522820@qq.com)
// +----------------------------------------------------------------------

class baseModule
{
	public function __construct()
	{
//		register_shutdown_function(function(){
//			if(isset($GLOBALS['redisdb'])){
//				$redis = $GLOBALS['redisdb'];
//				$redis->close();
//			}
//		});
	}

	public function __destruct()
	{
//		if(isset($GLOBALS['redisdb'])){
//			$redis = $GLOBALS['redisdb'];
//			$redis->close();
//		}
		register_shutdown_function(function(){
			if(isset($GLOBALS['redisdb'])){
				$redis = $GLOBALS['redisdb'];
				$redis->close();
			}
		});
	}
}