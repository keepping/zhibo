<?php
/**
 *功能：小小贝接口公用函数
 *详细：提供了签名、验签、提交等共用函数
 *版本：2.0.2
 *修改日期：2016-12-15
 '说明：
 '以下代码只是为了方便商户测试而提供的样例代码，商户可以根据自己的需要，按照技术文档编写,并非一定要使用该代码。
 '该代码仅供学习和研究小小贝接口使用，只是提供一个参考。
 */
 
/**格式化公钥
 * $pubKey PKCS8格式的公钥串
 * return pem格式公钥， 可以保存为.pem文件
 */
function formatPubKey($pubKey) {
    $fKey = "-----BEGIN PUBLIC KEY-----\n";
   /* $len = strlen($pubKey);
    for($i = 0; $i < $len; ) {
        $fKey = $fKey . substr($pubKey, $i, 64) . "\n";
        $i += 64;
    }*/
	$fKey .= str_replace(" ","\n",$pubKey);
    $fKey .= "\n-----END PUBLIC KEY-----";
    return $fKey;
}

/**格式化公钥
 * $priKey PKCS8格式的私钥串
 * return pem格式私钥， 可以保存为.pem文件
 */
function formatPriKey($priKey) {
    $fKey = "-----BEGIN RSA PRIVATE KEY-----\n";
    /*$len = strlen($priKey);
    for($i = 0; $i < $len; ) {
        $fKey = $fKey . substr($priKey, $i, 64) . "\n";
        $i += 64;
    }*/
	$fKey .= str_replace(" ","\n",$priKey);
    $fKey .= "\n-----END RSA PRIVATE KEY-----";
    return $fKey;
}

/**RSA签名
 * $data待签名数据
 * $priKey商户私钥
 * 签名用商户私钥
 * 使用MD5摘要算法
 * 最后的签名，需要用base64编码
 * return Sign签名
 */
function sign($data, $priKey) {
    //调用openssl内置签名方法，生成签名$sign
    openssl_sign($data, $sign, $priKey, OPENSSL_ALGO_MD5);
    //base64编码
    $sign = base64_encode($sign);
	$sign = urlencode($sign);
    return $sign;
}

/**RSA验签
 * $data待签名数据
 * $sign需要验签的签名
 * $pubKey小小贝公钥
 * 验签用小小贝公钥，摘要算法为MD5
 * return 验签是否通过 bool值
 */
function verify($data, $sign, $pubKey)  {
	 //调用openssl内置方法验签，返回bool值
	$result  = (bool)openssl_verify($data, base64_decode($sign), $pubKey, OPENSSL_ALGO_MD5);
    //返回资源是否成功
    return $result;
}

/**
 * RSA验签
 */
function parseRespRsa($content, $pkey) {
	$response	=	json_decode($content);
	$sign		=	$response->sign;
	//取出验证签名正文，空格转为加号
	$sign = str_replace(' ', '+', $sign);
	foreach($response->info as $k=>$v){
		if($k!='money'){	//金额保留两位小数
			$transdata[$k]=trim($v);
		}else{
			$transdata[$k]=sprintf("%.2f", $v);
		}
	}
	//转换为校验签名格式
	$content	= createLinkstringUrlencode($transdata);
	//校验签名
	$pkey = formatPubKey($pkey); 
	return verify($content, $sign, $pkey);
}

/**
 * MD5验签
 */
function parseRespMd5($content, $md5key) {
	$response	=	json_decode($content);
	$sign		=	$response->sign;
	foreach($response->info as $k=>$v){
		if($k!='money'){	//金额保留两位小数
			$transdata[$k]=trim($v);
		}else{
			$transdata[$k]=sprintf("%.2f", $v);
		}
	}
	//转换为校验签名格式
	$content	= createLinkstringUrlencode($transdata);
	$content .= '&key='.$md5key;
	//生成MD5签名
    $check = md5($content);
	return $sign==$check;
}

/**
 * 把数组所有元素，按照“参数=参数值”的模式用“&”字符拼接成字符串
 * @param $para 需要拼接的数组
 * return 拼接完成以后的字符串
 */
function createLinkstringUrlencode($para) {
	//使用ASCII码正序
	ksort($para);
	$arg  = "";
	while (list ($key, $val) = each ($para)) {
		$arg.=$key."=".$val."&";
	}
	//去掉最后一个&字符
	$arg = substr($arg,0,count($arg)-2);
	//如果存在转义字符，那么去掉转义
	if(get_magic_quotes_gpc()){$arg = stripslashes($arg);}
	return $arg;
}

/**
 * curl方式发送post报文
 * $remoteServer 请求地址
 * $postData post报文内容
 * $userAgent用户属性
 * return 返回报文
 */
function request_by_curl($remoteServer, $postData) {
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $remoteServer);
	curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	$data = urldecode(curl_exec($ch));
	curl_close($ch);
	return $data;
}

/**
 * 组装request报文
 * $reqJson 需要组装的json报文
 * $md5key  md5密钥
 * return 返回组装后的报文`
 */
function composeMd5($reqJson, $md5key) {
    //获取待签名字符串
	$content = createLinkstringUrlencode($reqJson);
	$content .= '&key='.$md5key;
	//生成MD5签名
    $sign = md5($content);
	if($reqJson['psw']){
		unset($reqJson['psw']); 
	}
	$content = json_encode($reqJson);
    $reqData = "transdata=".urlencode(trim($content))."&sign=".$sign."&signtype=MD5";
    return $reqData;
}

/**
 * 组装request报文
 * $reqJson 需要组装的json报文
 * $vkey  cp私钥，格式化之前的私钥
 * return 返回组装后的报文`
 */
function composeRsa($reqJson, $vkey) {
    //获取待签名字符串
	$content = createLinkstringUrlencode($reqJson);
    //格式化key，建议将格式化后的key保存，直接调用
    $vkey = formatPriKey($vkey);
    //生成RSA签名
    $sign = sign($content, $vkey);
	$content = json_encode($reqJson);
    $reqData = "transdata=".urlencode(trim($content))."&sign=".urlencode($sign)."&signtype=RSA";
    return $reqData;
}
/**
 * 发送post请求
 * $Url 请求地址
 * $reqData  请求的内容
 * return 返回服务端响应数据
 */
function HttpPost($Url,$reqData){
	$respData = request_by_curl($Url,$reqData);
	return $respData;
}


function get_real_ip(){
	$ip=false;
	if(!empty($_SERVER['HTTP_CLIENT_IP'])){
		$ip=$_SERVER['HTTP_CLIENT_IP'];
	}
	if(!empty($_SERVER['HTTP_X_FORWARDED_FOR'])){
		$ips=explode (', ', $_SERVER['HTTP_X_FORWARDED_FOR']);
		if($ip){ array_unshift($ips, $ip); $ip=FALSE; }
		for ($i=0; $i < count($ips); $i++){
			if(!eregi ('^(10│172.16│192.168).', $ips[$i])){
				$ip=$ips[$i];
				break;
			}
		}
	}
	return ($ip ? $ip : $_SERVER['REMOTE_ADDR']);
}

function clean($str){
	$qian	=	array(" ","　","\t","\n","\r","-","BEGIN","PRIVATE","KEY","END","PUBLIC");
	return  str_replace($qian, '', $str);
}
?>