
var canCheck = true, code_timeer = null, canSendSmsVerify = true, checkLoginAction = {
	checkAccountIp: function(callback){
		var self = this, $loginMsg = document.getElementById("login_msg");
		$.ajax({
            url: ROOT+'?m=Public&a=check_account_ip',
            data: null,
            dataType: "json",
            success: function(obj){
                if(obj.status==1){
                   (typeof(callback) == 'function') && callback.call(this, 1);
                }
                else{
                	(typeof(callback) == 'function') && callback.call(this, 0);

                }
            }
        });
	},
 	code_lefttime_fuc: function(verify_name,code_lefttime){
    	// 验证码倒计时
        var self = this;
        clearTimeout(self.code_timeer);
        $(verify_name).addClass("disabled");
        $(verify_name).html("重新发送 "+code_lefttime);
        code_lefttime--;
        if(code_lefttime >0){
            $(verify_name).attr("disabled","disabled");
            canSendSmsVerify = false;
            code_timeer = setTimeout(function(){checkLoginAction.code_lefttime_fuc(verify_name,code_lefttime);},1000);
        }
        else{
            code_lefttime = 60;
            canSendSmsVerify = true;
            $(verify_name).html("发送验证码");
            $(verify_name).removeClass("disabled");
        }
    },
};

$(document).ready(function(){
	if(document.getElementsByName("is_check_account")[0].value){
		checkLoginAction.checkAccountIp(function(data){
			if(data){
				canCheck = false;
				document.querySelectorAll(".tr_smsVerify")[0] && (document.querySelectorAll(".tr_smsVerify")[0].style.display = 'none');
				document.querySelectorAll(".tr_smsVerify")[1] && (document.querySelectorAll(".tr_smsVerify")[1].style.display = 'none');
			}
			else{
				document.querySelectorAll(".tr_smsVerify")[0] && (document.querySelectorAll(".tr_smsVerify")[0].style.display = 'table-row');
				document.querySelectorAll(".tr_smsVerify")[1] && (document.querySelectorAll(".tr_smsVerify")[1].style.display = 'table-row');
			}
		});
	}
	else{
		canCheck = false;
	}
	//绑定提交按钮
	$("input[name='adm_name']").focus();
	$(".submit").bind("click",function(){
		do_login();
	});
	$("input[name='adm_name']").bind("keypress",function(event){
		if(event.keyCode==13)
		{
			$("input[name='adm_password']").focus();
		}
	});
	$("input[name='adm_password']").bind("keypress",function(event){
		if(event.keyCode==13)
		{
			if(CHECK_DOG)
				$("input[name='adm_dog_key']").focus();
			else
				$("input[name='adm_verify']").focus();
		}
	});
	$("input[name='adm_dog_key']").bind("keypress",function(event){
		if(event.keyCode==13)
		{
			$("input[name='adm_verify']").focus();
		}
	});
	$("input[name='adm_verify']").bind("keypress",function(event){
		if(event.keyCode==13)
		{
			do_login();
		}
	})
	//绑定提交结束
	
	$("#verify").bind("click",function(){
		timenow = new Date().getTime();
		$(this).attr("src",$(this).attr("alt")+"&rand="+timenow);
	});
	
	// 短信验证码
	if(document.getElementById('smsVerify')){
		document.getElementById('smsVerify').onclick = function(){
			if(canSendSmsVerify){
				var $obj = this;
				var dataObj = {
					adm_name : document.getElementsByName("adm_name")[0].value,
					adm_password : document.getElementsByName("adm_password")[0].value
				}
				var $loginMsg = document.getElementById("login_msg");
				$.ajax({
		            url:  ROOT+'?m=Public&a=check_account',
		            data: dataObj,
		            dataType: "json",
		            success: function(obj){
		                if(obj.status==1){
		                    $.ajax({
					            url: ROOT+'?m=Public&a=send_account_verify',
					            data: null,
					            dataType: "json",
					            success: function(obj){
					                if(obj.status==1){
					                	// 验证码倒计时
							            code_lefttime = 60;
							            checkLoginAction.code_lefttime_fuc("#smsVerify", code_lefttime);
					                }
					                else{
					                    alert(obj.error);
					                }
					            }
					        });
		                }
		                else{
		                	$loginMsg.innerHTML = obj.error || '验证失败';
		                	setTimeout(function(){
		                		$loginMsg.innerHTML = '';
		                	}, 2000);
		                	return false;
		                }
		            }
		        });
			}
		}
	}
	
});

function do_login(){

	
	CHECK_DOG_HASH = $.trim($(".adm_dog_key").val());
	if (check_dog() == false) return;
	
	$(this).attr("disabled",true);
	
	//验证帐号
	if($.trim($(".adm_name").val())=='')
	{
		$(".adm_name").val("");
		$(".adm_name").focus();
		$("#login_msg").html(ADM_NAME_EMPTY);
		$("#login_msg").oneTime(2000, function() {
		    $(this).html("");
		    $(".submit").attr("disabled",false);
		    
		 });
		return;
	}	
	//验证密码
	if($.trim($(".adm_password").val())=='')
	{
		$(".adm_password").val("");
		$(".adm_password").focus();
		$("#login_msg").html(ADM_PASSWORD_EMPTY);
		$("#login_msg").oneTime(2000, function() {
		    $(this).html("");
		    $(".submit").attr("disabled",false);
		    
		 });
		return;
	}	
	
	//验证密码
	if($.trim($(".adm_verify").val())=='')
	{
		$(".adm_verify").val("");
		$(".adm_verify").focus();
		$("#login_msg").html(ADM_VERIFY_EMPTY);
		$("#login_msg").oneTime(2000, function() {
		    $(this).html("");
		    $(".submit").attr("disabled",false);
		    
		 });
		return;
	}	
	
	//表单参数
	var query = new Object();
	query.adm_name = $(".adm_name").val();
	query.adm_password = $(".adm_password").val();
	query.adm_dog_key = $(".adm_dog_key").val();
	query.adm_verify = $(".adm_verify").val();
	canCheck && (query.mobile_verify = $(".mobile_verify").val());
	query.ajax = 1;
	url = $("form").attr("action");
	
	$(".adm_name").attr("disabled",true);
	$(".adm_password").attr("disabled",true);
	$(".adm_dog_key").attr("disabled",true);
	$(".adm_verify").attr("disabled",true);
	$.ajax({
		url: url, 
		data: query,
		type:"post",
		dataType: "json",
		success: function(obj){
			if(obj.status)
			{
				$("#login_msg").html(obj.info);
				$("#login_msg").oneTime(2000, function() {
				    $(this).html("");
				    location.href = L_jumpUrl;
				 });
				
			}
			else
			{
				$("#login_msg").html(obj.info);
				$("#login_msg").oneTime(1000, function() {
				    $(this).html("");
				    $(".submit").attr("disabled",false);
				    $(".adm_name").attr("disabled",false);
					$(".adm_password").attr("disabled",false);
					$(".adm_dog_key").attr("disabled",false);
					$(".adm_verify").attr("disabled",false);
					$("#verify").click();
				 });
			}
	}});
}