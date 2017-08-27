<?php
define("FILE_PATH","/callback/payment"); //文件目录
require_once '../../system/system_init.php';

require_once APP_ROOT_PATH."system/payment/Xiaoxiaopay_payment.php";
$o = new Xiaoxiaopay_payment();
$string  = file_get_contents('php://input');//接收post请求数据
$o->notify($string);
?>