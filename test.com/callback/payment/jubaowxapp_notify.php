<?php

define("FILE_PATH","/callback/payment"); //文件目录
require_once '../../system/system_init.php';

require_once APP_ROOT_PATH."system/payment/JubaoWxsdk_payment.php";
$o = new JubaoWxsdk_payment();
$o->notify($_POST);

?>