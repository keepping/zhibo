<?php
/*ajax返回*/
function api_ajax_return($data,$is_debug=false)
{
	//$user_info = app_login();
	//$data['user_info'] = $user_info;
	if(!$is_debug){
		header("Content-Type:text/html; charset=utf-8");
		header('Cache-Control: no-cache, no-store, max-age=0, must-revalidate');
		//echo(json_encode($data));
		$encrypt = $GLOBALS['encrypt'];
		if($encrypt['i_type']){
			ajax_return_aes($data,4);
		}else{
			echo(json_encode($data));
		}
		exit;
	}else{


		var_export($data);
		echo "<br />";
		exit;


	}

}

function filter_ma_request_mapi(&$str){
	$search = array("../","\n","\r","\t","\r\n","'","<",">","\"","%","\\",".","/");
	return str_replace($search,"",$str);
}

function strim_mapi($str)
{
	return quotes_mapi(htmlspecialchars(trim($str)));
}
function quotes_mapi($content)
{
	//if $content is an array
	if (is_array($content))
	{
		foreach ($content as $key=>$value)
		{
			//$content[$key] = mysql_real_escape_string($value);
			$content[$key] = addslashes($value);
		}
	} else
	{
		//if $content is not an array
		//$content=mysql_real_escape_string($content);
		$content=addslashes($content);
	}
	return $content;
}
?>