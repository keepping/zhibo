<?php if (!defined('THINK_PATH')) exit();?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title><?php echo app_conf("SITE_NAME");?> - <?php echo l("SYSTEM_LOGIN");?> </title>
<script type="text/javascript">
	//定义JS语言
	var ADM_NAME_EMPTY = '<?php echo l("ADM_NAME_EMPTY");?>';
	var ADM_PASSWORD_EMPTY = '<?php echo l("ADM_PASSWORD_EMPTY");?>';
	var ADM_VERIFY_EMPTY = '<?php echo l("ADM_VERIFY_EMPTY");?>';
	var L_jumpUrl = '<?php echo U("Index/index");?>';
	var ROOT = '__APP__';
	function resetwindow()
	{
		if(top.location != self.location)
		{
			top.location.href = self.location.href;
			return 
		}
	}
	resetwindow();
	
</script>
<link rel="stylesheet" type="text/css" href="__TMPL__Common/style/login.css" />
<script type="text/javascript" src="__TMPL__Common/js/des.js"></script>
<script type="text/javascript" src="__TMPL__Common/js/jquery.js"></script>
<script type="text/javascript" src="__TMPL__Common/js/jquery.timer.js"></script>
<script type="text/javascript" src="__TMPL__Common/js/login.js"></script>
<script type="text/javascript" src="__TMPL__Common/js/check_dog.js"></script>
<script type="text/javascript" src="__TMPL__Common/js/IA300ClientJavascript.js"></script>
<script type="text/javascript">
	var __LOGIN_KEY = "<?php echo LOGIN_DES_KEY();?>";
</script>
</head>
<body onLoad="javascript:DogPageLoad();">
<form action="<?php echo u("Public/do_login");?>">
<table border="0" cellpadding="0" cellspacing="0" class="login_bar">
  <tr>
  	<td width="330" align="right"><img src="/public/images/admin/login/logo.png" border="0" /></td>
	<td width="63"><img src="__TMPL__Common/images/login/line.png" border="0" /></td>
	<td>
		<table border="0" cellpadding="0" cellspacing="0" class="login_f">
			<tr>
				<td>&nbsp;</td>
				<td id="login_msg">&nbsp;</td>
			</tr>
			<tr>
				<td><b>账&nbsp;&nbsp;户：</b></td>
				<td><input type="text" name="adm_name" class="adm_name"></td>
			</tr>
			<tr>
				<td><b>密&nbsp;&nbsp;码：</b></td>
				<td><input type="password"  name="adm_password" class="adm_password"></td>
			</tr>
			<tr id="CHECK_DOG_BOX" style="display:none;">
				<td><b>USBKEY：</b></td>
				<td>
					<input type="password"  class="adm_dog_key " name="adm_dog_key">
				</td>
			</tr>
			<?php if($open_check_account == 1): ?><tr class="tr_smsVerify">
				<td><b>绑定手机号：</b></td>
				<td>
					<input type="text"  class="login_input " name="mobile" value="<?php echo ($account_mobile); ?>" readonly>
				</td>
			</tr>
			<tr class="tr_smsVerify">
				<td><b>短信验证码：</b></td>
				<td>
					<input type="text"  class="login_input sms_verify mobile_verify" name="mobile_verify">
					<span type="button" class="button_smsVerify" id="smsVerify">获取验证码</span>
				</td>
			</tr><?php endif; ?>
			<tr>
				<td><b>验证码：</b></td>
				<td>
					<input type="text"  class="login_input adm_verify" name="adm_verify">
					<img src="__ROOT__/verify.php?name=verify" alt="__ROOT__/verify.php?name=verify"  id="verify" align="absmiddle" />
				</td>
			</tr>
			<tr>
				<td>&nbsp;</td>
				<td>
					<input type="hidden" name="is_check_account" value="<?php echo ($open_check_account); ?>" >
					<img src="__TMPL__Common/images/login/login_btn.png" border="0" class="login_button submit" id="login_btn" alt="登录">
				</td>
			</tr>
		</table>
	</td>
  </tr>
</table>
</form>
<script type="text/javascript">
	if(CHECK_DOG){
		$("#CHECK_DOG_BOX").show();
	}
</script>
</body>
</html>