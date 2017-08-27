<?php
//系统安装
class IndexAction extends Action{
	private function getRealPath()
	{
		return APP_ROOT_PATH;
	}
	private $install_lock;
	public function __construct()
	{
		import("ORG.Io.Dir");       
		parent::__construct();
		$this->rebuile();
		$this->install_lock = $this->getRealPath()."public/install.lock";
	}
	
	public function rebuile()
	{
		$this->clear_dir_file($this->getRealPath()."public/runtime/admin/Cache/");	
		$this->clear_dir_file($this->getRealPath()."public/runtime/admin/Data/_fields/");		
		$this->clear_dir_file($this->getRealPath()."public/runtime/admin/Temp/");	
		$this->clear_dir_file($this->getRealPath()."public/runtime/admin/Logs/");	
		@unlink($this->getRealPath()."public/runtime/admin/~app.php");
		@unlink($this->getRealPath()."public/runtime/admin/~runtime.php");
		@unlink($this->getRealPath()."public/runtime/admin/lang.js");
		@unlink($this->getRealPath()."public/runtime/app/config_cache.php");	
		
		$this->clear_dir_file($this->getRealPath()."public/runtime/statics/");	
		
		$this->clear_dir_file($this->getRealPath()."public/runtime/app/tpl_caches/");		
		$this->clear_dir_file($this->getRealPath()."public/runtime/app/tpl_compiled/");
		
		$this->clear_dir_file($this->getRealPath()."public/runtime/data/");	
		$this->clear_dir_file($this->getRealPath()."public/runtime/app/data_caches/");				
		$this->clear_dir_file($this->getRealPath()."public/runtime/app/db_caches/");

		@unlink($this->getRealPath()."public/runtime/app/lang.js");	
		
		
	}
	public function clear_dir_file($path)
	{
	   if ( $dir = opendir( $path ) )
	   {
	            while ( $file = readdir( $dir ) )
	            {
	                $check = is_dir( $file );
	                if ( !$check )
	                    unlink( $path . $file );                 
	            }
	            closedir( $dir );
	            return true;
	   }
	}
    
	/**
	 * 文件或目录权限检查函数
	 *
	 * @access          private
	 * @param           string  $file_path   文件路径
	 * @param           bool    $rename_prv  是否在检查修改权限时检查执行rename()函数的权限
	 *
	 * @return          int     返回值的取值范围为{0 <= x <= 15}，每个值表示的含义可由四位二进制数组合推出。
	 *                          返回值在二进制计数法中，四位由高到低分别代表
	 *                          可执行rename()函数权限、可对文件追加内容权限、可写入文件权限、可读取文件权限。
	 */
	private function file_mode_info($file_path)
	{
	    /* 如果不存在，则不可读、不可写、不可改 */
	    if (!file_exists($file_path))
	    {
	        return false;
	    }
	
	    $mark = 0;
	
	    if (strtoupper(substr(PHP_OS, 0, 3)) == 'WIN')
	    {
	        /* 测试文件 */
	        $test_file = $file_path . '/cf_test.txt';
	
	        /* 如果是目录 */
	        if (is_dir($file_path))
	        {
	            /* 检查目录是否可读 */
	            $dir = @opendir($file_path);
	            if ($dir === false)
	            {
	                return $mark; //如果目录打开失败，直接返回目录不可修改、不可写、不可读
	            }
	            if (@readdir($dir) !== false)
	            {
	                $mark ^= 1; //目录可读 001，目录不可读 000
	            }
	            @closedir($dir);
	
	            /* 检查目录是否可写 */
	            $fp = @fopen($test_file, 'wb');
	            if ($fp === false)
	            {
	                return $mark; //如果目录中的文件创建失败，返回不可写。
	            }
	            if (@fwrite($fp, 'directory access testing.') !== false)
	            {
	                $mark ^= 2; //目录可写可读011，目录可写不可读 010
	            }
	            @fclose($fp);
	
	            @unlink($test_file);
	
	            /* 检查目录是否可修改 */
	            $fp = @fopen($test_file, 'ab+');
	            if ($fp === false)
	            {
	                return $mark;
	            }
	            if (@fwrite($fp, "modify test.\r\n") !== false)
	            {
	                $mark ^= 4;
	            }
	            @fclose($fp);
	
	            /* 检查目录下是否有执行rename()函数的权限 */
	            if (@rename($test_file, $test_file) !== false)
	            {
	                $mark ^= 8;
	            }
	            @unlink($test_file);
	        }
	        /* 如果是文件 */
	        elseif (is_file($file_path))
	        {
	            /* 以读方式打开 */
	            $fp = @fopen($file_path, 'rb');
	            if ($fp)
	            {
	                $mark ^= 1; //可读 001
	            }
	            @fclose($fp);
	
	            /* 试着修改文件 */
	            $fp = @fopen($file_path, 'ab+');
	            if ($fp && @fwrite($fp, '') !== false)
	            {
	                $mark ^= 6; //可修改可写可读 111，不可修改可写可读011...
	            }
	            @fclose($fp);
	
	            /* 检查目录下是否有执行rename()函数的权限 */
	            if (@rename($test_file, $test_file) !== false)
	            {
	                $mark ^= 8;
	            }
	        }
	    }
	    else
	    {
	        if (@is_readable($file_path))
	        {
	            $mark ^= 1;
	        }
	
	        if (@is_writable($file_path))
	        {
	            $mark ^= 14;
	        }
	    }
	
	    return $mark;
	}
	
   /**
     * 执行SQL脚本文件
     *
     * @param array $filelist
     * @return string
     */
    private function restore($file,$db_config)
    {
    	
			set_time_limit(0);
			$db = Db::getInstance(array('dbms'=>'mysql','hostname'=>$db_config['DB_HOST'],'username'=>$db_config['DB_USER'],'password'=>$db_config['DB_PWD'],'hostport'=>$db_config['DB_PORT'],'database'=>$db_config['DB_NAME']));
    		$sql = file_get_contents($file);
    		$sql = $this->remove_comment($sql);
    		$sql = trim($sql);
 
    		$sql = str_replace("\r", '', $sql);
       		$segmentSql = explode(";\n", $sql);
       		foreach($segmentSql as $k=>$itemSql)
       		{
       			
       			$itemSql = str_replace("%DB_PREFIX%",$db_config['DB_PREFIX'],$itemSql);
       			$db->query($itemSql);
       		}  
       		
       		
    		return "";
    }
    
    

    /**
     * 过滤SQL查询串中的注释。该方法只过滤SQL文件中独占一行或一块的那些注释。
     *
     * @access  public
     * @param   string      $sql        SQL查询串
     * @return  string      返回已过滤掉注释的SQL查询串。
     */
    private function remove_comment($sql)
    {
        /* 删除SQL行注释，行注释不匹配换行符 */
        $sql = preg_replace('/^\s*(?:--|#).*/m', '', $sql);

        /* 删除SQL块注释，匹配换行符，且为非贪婪匹配 */
        //$sql = preg_replace('/^\s*\/\*(?:.|\n)*\*\//m', '', $sql);
        $sql = preg_replace('/^\s*\/\*.*?\*\//ms', '', $sql);

        return $sql;
    }
    
    public function index(){
    	$this->display();
    }
    public function do_update()
    {
    	header("Content-type: text/html; charset=utf-8");
    	echo "<script>function jump(){ parent.location.href = ".__ROOT__."/"."; }</script>";
    	echo "<style type='text/css'> body{ font-size:12px; line-height:18px; font-family:'arial'; } div{ margin:5px 0px;} .error{ border:#f30 solid 1px; color:#f30;}</style>";
    	@set_time_limit(0);
   	
    	$return_rs = array(
    		'msg'=>'更新成功',
    		'status'=>true,
    	);  //用于返回的数据
    	
    	$db_config = require_once $this->getRealPath()."public/db_config.php";
 

		$connect = @mysql_connect($db_config['DB_HOST'].":".$db_config['DB_PORT'],$db_config['DB_USER'],$db_config['DB_PWD']);
    	if(mysql_error()=="")
    	{
    		$rs = mysql_select_db($db_config['DB_NAME'],$connect);
    		if($rs)
    		{
    			$return_rs['status'] = true;
    		}
    		else 
    		{
    			$return_rs['msg'] = "不存在的数据库";
    			$return_rs['status'] = false;
    		}
    	}
    	else 
    	{
    			$return_rs['msg'] = "连接数据库失败";
    			$return_rs['status'] = false;
    	}
    	
    	if($return_rs['status'])
    	{
	    	    	
    		set_time_limit(0);
			$db = Db::getInstance(array('dbms'=>'mysql','hostname'=>$db_config['DB_HOST'],'username'=>$db_config['DB_USER'],'password'=>$db_config['DB_PWD'],'hostport'=>$db_config['DB_PORT'],'database'=>$db_config['DB_NAME']));
    		$sql = file_get_contents($this->getRealPath()."update/update.sql");
		
    		$sql = $this->remove_comment($sql);
    		$sql = trim($sql);
 
    		$sql = str_replace("\r", '', $sql);
       		$segmentSql = explode(";\n", $sql);

       		if(!is_numeric($segmentSql[0])&&$segmentSql[0]!='license')
       		{
       			$this->assign("waitSecond",'-1');	       		
	    		die("脚本没有版本号，无法更新");	
       		}
       		else
       		{
       			$version = $segmentSql[0];
       			$db_version = $db->query("select value from ".$db_config['DB_PREFIX']."conf where name='DB_VERSION'");
       			$db_version = $db_version[0]['value'];

       			if($db_version==$version)
       			{
       				die("数据库已经是最新版本");	
       			}
       			
       			if(floatval($db_version)>floatval($version)&&$segmentSql[0]!='license')
       			{
       				die("不能更新旧版本的数据脚本");	
       			}
       		}
       		$errmsg = '';
       		$output_msg = '';
       		foreach($segmentSql as $k=>$itemSql)
       		{      			
       			$itemSql = str_replace("%DB_PREFIX%",$db_config['DB_PREFIX'],$itemSql);     
       			if($itemSql!=''&&!is_numeric($itemSql)&&$itemSql!='license')  	
       			{	       				
       				$db->query($itemSql);        				
	       			$current_err = $db->getError();
	       			if($current_err!=$errmsg)
	       			{
	       				$errmsg = $current_err;
	       				echo "<div class='error'>".$itemSql."错误信息：".$current_err."</div>";				
	       			}
	       			else
	       			{
	       				echo "<div>".$itemSql."</div>";
	       			}
       			}      			
       		}  

       		
       		
       		//开始写入配置文件
			$sys_configs = $db->query("select * from ".$db_config['DB_PREFIX']."conf");
			$config_str = "<?php\n";
			$config_str .= "return array(\n";
			foreach($sys_configs as $k=>$v)
			{
				$config_str.="'".$v['name']."'=>'".addslashes($v['value'])."',\n";
			}
			$config_str.=");\n ?>";			
			file_put_contents($this->getRealPath()."public/sys_config.php",$config_str);				
			$this->rebuile();      		
      		
			
       		//更新成功后执行
			if($output_msg!='')
			{
				import("ORG.Io.Dir");
				$this->rebuile();			
				echo "<br />".$output_msg." <a href='javascript:jump();'>返回首页</a>";
     			
			}
			else
			{
				import("ORG.Io.Dir");
	       		$this->rebuile();	       		
	    		echo "<br />".$return_rs['msg']." <a href='javascript:jump();'>返回首页</a>";
			}		
			
    	}
    	else 
    	{    		
    		echo "<br />".$return_rs['msg']." <a href='javascript:jump();'>返回首页</a>";     	
    	} 
    }
    
}
?>