function get_deal_support(id){
    location.href = ROOT+"?"+VAR_MODULE+"="+MODULE_NAME+"&"+VAR_ACTION+"=deal_support&deal_id="+id;
}
function pay(id)
{
	$.weeboxs.open(ROOT+'?m='+MODULE_NAME+'&a=pay&id='+id, {contentType:'ajax',showButton:false,title:'发放筹款',width:600,height:180});
}


function pay_log(id)
{
	location.href = ROOT + '?m='+MODULE_NAME+'&a=pay_log&id='+id;
}


function add_pay_log(deal_id)
{
	$.weeboxs.open(ROOT+'?m='+MODULE_NAME+'&a=add_pay_log&id='+deal_id, {contentType:'ajax',showButton:false,title:"结算筹款",width:600,height:235});
}

function del_pay_log(id)
{
	if(!id)
	{
		idBox = $(".key:checked");
		if(idBox.length == 0)
		{
			alert("请选择需要删除的项目");
			return;
		}
		idArray = new Array();
		$.each( idBox, function(i, n){
			idArray.push($(n).val());
		});
		id = idArray.join(",");
	}
	if(confirm("确定要删除选中的项目吗？"))
	$.ajax({ 
			url: ROOT+"?"+VAR_MODULE+"="+MODULE_NAME+"&"+VAR_ACTION+"=del_pay_log&id="+id, 
			data: "ajax=1",
			dataType: "json",
			success: function(obj){
				$("#info").html(obj.info);
				if(obj.status==1)
				location.href=location.href;
			}
	});
}


//项目里删除订单
function del_order(id)
{
	if(!id)
	{
		idBox = $(".key:checked");
		if(idBox.length == 0)
		{
			alert(LANG['DELETE_EMPTY_WARNING']);
			return;
		}
		idArray = new Array();
		$.each( idBox, function(i, n){
			idArray.push($(n).val());
		});
		id = idArray.join(",");
	}
	if(confirm(LANG['CONFIRM_DELETE']))
	$.ajax({ 
			url: ROOT+"?"+VAR_MODULE+"="+MODULE_NAME+"&"+VAR_ACTION+"=delete_order&id="+id, 
			data: "ajax=1",
			dataType: "json",
			success: function(obj){
				$("#info").html(obj.info);
				if(obj.status==1)
				location.href=location.href;
			}
	});
}
