<?php 
  function xCopy($source,   $destination,   $child){    
  //用法：    
  //   xCopy("feiy","feiy2",1):拷贝feiy下的文件到   feiy2,包括子目录    
  //   xCopy("feiy","feiy2",0):拷贝feiy下的文件到   feiy2,不包括子目录    
  //参数说明：    
  //   $source:源目录名    
  //   $destination:目的目录名    
  //   $child:复制时，是不是包含的子目录    
  if(!is_dir($source)){    
  echo("Error:the   $source   is   not   a   direction!");    
  return   0;    
  }    
  if(!is_dir($destination)){    
  	mkdir($destination,0777);    
  }    
   
   
  $handle=dir($source);    
  while($entry=$handle->read())   {    
  if(($entry!=".")&&($entry!="..")){    
  if(is_dir($source."/".$entry)){    
  if($child)    
  xCopy($source."/".$entry,$destination."/".$entry,$child);    
  }    
  else{    
   
  copy($source."/".$entry,$destination."/".$entry);    
  }    
   
  }    
  }    
   
  return   1;    
  }
  
		//全站通用的清除所有缓存的方法
	function clear_cache()
	{
		Dir::delDir(getcwd()."/admin/Runtime/Cache/");
		Dir::delDir(getcwd()."/admin/Runtime/Data/");  
		Dir::delDir(getcwd()."/admin/Runtime/Temp/");  
		@unlink(getcwd()."/admin/Runtime/~app.php");
		@unlink(getcwd()."/admin/Runtime/~runtime.php");

		Dir::delDir(getcwd()."/app/Runtime/Cache/");
		Dir::delDir(getcwd()."/app/Runtime/Data/");  
		Dir::delDir(getcwd()."/app/Runtime/Temp/"); 
		Dir::delDir(getcwd()."/app/Runtime/caches/"); 
		Dir::delDir(getcwd()."/app/Runtime/compiled/");  
		Dir::delDir(getcwd()."/app/Runtime/".HTML_DIR.'/'); 
		@unlink(getcwd()."/app/Runtime/~app.php");
		@unlink(getcwd()."/app/Runtime/~runtime.php");
		@unlink(getcwd()."/app/Runtime/js_lang.js");
		
		
		Dir::delDir(getcwd()."/install/Runtime/Cache/");
		Dir::delDir(getcwd()."/install/Runtime/Data/");  
		Dir::delDir(getcwd()."/install/Runtime/Temp/");  
		@unlink(getcwd()."/install/Runtime/~app.php");
		@unlink(getcwd()."/install/Runtime/~runtime.php");	

		Dir::delDir(getcwd()."/mobile/Runtime/Cache/");
		Dir::delDir(getcwd()."/mobile/Runtime/Data/");  
		Dir::delDir(getcwd()."/mobile/Runtime/Temp/");  
		@unlink(getcwd()."/mobile/Runtime/~app.php");
		@unlink(getcwd()."/mobile/Runtime/~runtime.php");
		
		Dir::delDir(getcwd()."/update/Runtime/Cache/");
		Dir::delDir(getcwd()."/update/Runtime/Data/");  
		Dir::delDir(getcwd()."/update/Runtime/Temp/");  
		@unlink(getcwd()."/update/Runtime/~app.php");
		@unlink(getcwd()."/update/Runtime/~runtime.php");		    

	}

?>