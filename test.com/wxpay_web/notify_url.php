<?php
define("FILE_PATH", "/callback/payment"); //文件目录
require_once dir(__FILE__) . '../system/system_init.php';

require_once APP_ROOT_PATH . "system/payment/Wxjspay_payment.php";
$query = file_get_contents('php://input');
$query = simplexml_load_string($query, 'SimpleXMLElement', LIBXML_NOCDATA);
$query = json_decode(json_encode($query), 1);

$o = new Wxjspay_payment();
$o->notify($query);
