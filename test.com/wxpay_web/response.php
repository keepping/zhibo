<?php
define("FILE_PATH", "/callback/payment"); //文件目录
require_once dir(__FILE__) . '../system/system_init.php';

require_once APP_ROOT_PATH . "system/payment/Wxjspay_payment.php";
$o = new Wxjspay_payment();
$o->response($_REQUEST);
