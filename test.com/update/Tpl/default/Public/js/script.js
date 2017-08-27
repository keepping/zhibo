function do_update()
{
	$("#update").val("在升级完成之前，勿刷新该页");
	$("#update").attr("disabled",true);
	$(".msg").show();
	$("form").submit();
}
