<?php

define("FILE_PATH","/callback/payment"); //文件目录
require_once '../../system/system_init.php';

require_once APP_ROOT_PATH."system/payment/Wxappsdk_payment.php";
$o = new Wxappsdk_payment();
$xml = file_get_contents('php://input');
$o->notify($xml);

?>