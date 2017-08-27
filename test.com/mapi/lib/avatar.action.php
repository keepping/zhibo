<?php
// +----------------------------------------------------------------------
// | Fanwe 方维p2p借贷系统
// +----------------------------------------------------------------------
// | Copyright (c) 2011 http://www.fanwe.com All rights reserved.
// +----------------------------------------------------------------------
// | Author: 云淡风轻(88522820@qq.com)
// +----------------------------------------------------------------------

class avatarModule
{
	public function upload()
	{

		if($GLOBALS['user_info']['id']==0)
		{
			$data['status'] = 0;  //未登录
			$data['error'] = "请先登录";
			ajax_return($data);
		}
		// 开始上传
		// 创建avatar临时目录
		if (!is_dir(APP_ROOT_PATH."public/attachment/temp/")) { 
	             @mkdir(APP_ROOT_PATH."public/attachment/temp/");
	             @chmod(APP_ROOT_PATH."public/attachment/temp/", 0777);
	    }  
	    
		$img_result = save_image_upload ( $_FILES, "file", "attachment/temp", $whs = array (
				'small' => array (
						48,
						48,
						1,
						0 
				),
				'big' => array (
						600,
						600,
						0,
						0 
				) 
		) );
		// 开始移动图片到相应位置
		$id = $GLOBALS['user_info']['id'];
		
		$dir_name = to_date(get_gmtime(),"Ym");
	    if (!is_dir(APP_ROOT_PATH."public/attachment/".$dir_name)) { 
	             @mkdir(APP_ROOT_PATH."public/attachment/".$dir_name);
	             @chmod(APP_ROOT_PATH."public/attachment/".$dir_name, 0777);
	        }
	        
	    $dir_name = $dir_name."/".to_date(get_gmtime(),"d");
	    if (!is_dir(APP_ROOT_PATH."public/attachment/".$dir_name)) { 
	             @mkdir ( APP_ROOT_PATH . "public/attachment/".$dir_name);
	             @chmod ( APP_ROOT_PATH . "public/attachment/".$dir_name, 0777);
	        }
	     
	    $dir_name = $dir_name."/".to_date(get_gmtime(),"H");
	    if (!is_dir(APP_ROOT_PATH."public/attachment/".$dir_name)) { 
	             @mkdir(APP_ROOT_PATH."public/attachment/".$dir_name);
	             @chmod(APP_ROOT_PATH."public/attachment/".$dir_name, 0777);
	        }
         
       	$save_rec_Path = "/public/attachment/".$dir_name."/origin/";  //上传时先存放原图          	      
        $savePath = APP_ROOT_PATH."public/attachment/".$dir_name."/origin/"; //绝对路径
		if (!is_dir(APP_ROOT_PATH."public/attachment/".$dir_name."/origin/")) { 
	             @mkdir(APP_ROOT_PATH."public/attachment/".$dir_name."/origin/");
	             @chmod(APP_ROOT_PATH."public/attachment/".$dir_name."/origin/", 0777);
	    }
		//文件名
		$save_name= get_gmtime().$id.".jpg";
		$save_thumb_name= "thumb_".get_gmtime().$id.".jpg";
		//相对路径
		$image_file_domain = ".".$save_rec_Path.$save_name;
		$image_file_thumb_domain = ".".$save_rec_Path.$save_thumb_name;
		//服务器路径
		$image_big_file =$savePath.$save_name;
		$image_small_file =$savePath.$save_thumb_name;
		
		//保存文件
		@file_put_contents ( $image_big_file, file_get_contents ( $img_result ['file'] ['thumb'] ['big'] ['path'] ) );
		@file_put_contents ( $image_small_file, file_get_contents ( $img_result ['file'] ['thumb'] ['small'] ['path'] ) );
		
		if($GLOBALS['distribution_cfg']['OSS_TYPE']&&$GLOBALS['distribution_cfg']['OSS_TYPE']!='NONE')
		{
			syn_to_remote_image_server($image_file_domain,false);
			syn_to_remote_image_server($image_file_thumb_domain,false);
 		}
		
		@unlink ( $img_result ['file'] ['thumb'] ['big'] ['path'] );
		@unlink ( $img_result ['file'] ['thumb'] ['small'] ['path'] );
		@unlink ( $img_result ['file'] ['path'] );
				
		$status = $GLOBALS['db']->query("update ".DB_PREFIX."user set head_image = '".$image_file_domain."', thumb_head_image = '".$image_file_thumb_domain."' where id =". $GLOBALS['user_info']['id']);
		if($status){
			//更新session
			$user_info = $GLOBALS['db']->getRow("select * from ".DB_PREFIX."user where id =".$id);
			es_session::set("user_info", $user_info);

			$root['user_info']['user_id'] =$user_info['id'];
			$root['user_info']['nick_name'] =$user_info['nick_name'];
			$root['user_info']['mobile'] =$user_info['mobile'];
			$root['user_info']['head_image'] =get_spec_image($user_info['head_image']);
			//redis 更新
			fanwe_require(APP_ROOT_PATH.'/mapi/lib/redis/BaseRedisService.php');
			fanwe_require(APP_ROOT_PATH.'mapi/lib/redis/UserRedisService.php');
			$user_redis = new UserRedisService();
			$data['head_image'] =$image_file_domain;
			$data['thumb_head_image'] =$image_file_thumb_domain;
			$list =  $user_redis->update_db($GLOBALS['user_info']['id'],$data);
			
			$root['status'] = 1;
			$root['error'] = '上传成功';
		}else{
			$root['status'] = 0;
			$root['error'] = '上传失败';
		}
		ajax_return($root);
	}


	//上传图片
	function uploadImage()
	{		
	    // 创建temp临时目录
	    $save_rec_temp = "/public/attachment/temp/";       	      
        $savePath_temp = APP_ROOT_PATH."public/attachment/temp/";
		if (!is_dir(APP_ROOT_PATH."public/attachment/temp/")) { 
	             @mkdir(APP_ROOT_PATH."public/attachment/temp/");
	             @chmod(APP_ROOT_PATH."public/attachment/temp/", 0777);
	    }       
		
        // 开始上传
		
		$img_result = save_image_upload ($_FILES, "file", "attachment/temp", $whs = array ('origin' => array (600,600,0,0) ) );
		// 开始移动图片到相应位置
		$dir_name = to_date(get_gmtime(),"Ym");
	    if (!is_dir(APP_ROOT_PATH."public/attachment/".$dir_name)) { 
	             @mkdir(APP_ROOT_PATH."public/attachment/".$dir_name);
	             @chmod(APP_ROOT_PATH."public/attachment/".$dir_name, 0777);
	        }
	        
	    $dir_name = $dir_name."/".to_date(get_gmtime(),"d");
	    if (!is_dir(APP_ROOT_PATH."public/attachment/".$dir_name)) { 
	             @mkdir(APP_ROOT_PATH."public/attachment/".$dir_name);
	             @chmod(APP_ROOT_PATH."public/attachment/".$dir_name, 0777);
	        }
	     
	    $dir_name = $dir_name."/".to_date(get_gmtime(),"H");
	    if (!is_dir(APP_ROOT_PATH."public/attachment/".$dir_name)) { 
	             @mkdir(APP_ROOT_PATH."public/attachment/".$dir_name);
	             @chmod(APP_ROOT_PATH."public/attachment/".$dir_name, 0777);
	        }
         
       	$save_rec_Path = "/public/attachment/".$dir_name."/origin/";  //上传时先存放原图          	      
        $savePath = APP_ROOT_PATH."public/attachment/".$dir_name."/origin/"; //绝对路径
		if (!is_dir(APP_ROOT_PATH."public/attachment/".$dir_name."/origin/")) { 
	             @mkdir(APP_ROOT_PATH."public/attachment/".$dir_name."/origin/");
	             @chmod(APP_ROOT_PATH."public/attachment/".$dir_name."/origin/", 0777);
	    }
	    
		$id = trim($GLOBALS['user_info']['id']);
		if(!$id){
			$id =rand(1000,9999);
		}
		$save_name= get_gmtime().$id.".jpg";
		
		$image_file_domain = ".".$save_rec_Path.$save_name;

		$image_file =$savePath.$save_name;
		@file_put_contents ( $image_file, file_get_contents ( $img_result ['file']['path'] ) ); //使用原图
		//@file_put_contents ( $image_file, file_get_contents ( $img_result ['file'] ['thumb'] ['origin'] ['path'] ) );// 使用 600*600px的缩略图
		
		if($GLOBALS['distribution_cfg']['OSS_TYPE']&&$GLOBALS['distribution_cfg']['OSS_TYPE']!='NONE')
		{
			//false 代表不删除服务器图片
			syn_to_remote_image_server($image_file_domain,false);
 		}
		
		@unlink ( $img_result ['file'] ['thumb'] ['origin'] ['path'] );
		@unlink ( $img_result ['file'] ['path'] );

		if(file_exists($image_file)){
			$root['status'] = 1;
			$root['error'] = '上传成功';
			$root['path'] = $image_file_domain;
			$root['server_full_path'] = get_spec_image($image_file_domain);
		}else{
			$root['status'] = 0;
			$root['error'] = '上传失败';
			$root['path'] ='';
		}
		ajax_return($root);
	}
}
?>