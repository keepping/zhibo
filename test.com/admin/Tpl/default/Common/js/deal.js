function pay(id)
{
	$.weeboxs.open(ROOT+'?m='+MODULE_NAME+'&a=pay&id='+id, {contentType:'ajax',showButton:false,title:'发放筹款',width:600,height:180});
}

//印票贡献榜
function contribution_list(id)
{
    location.href = ROOT+"?"+VAR_MODULE+"="+MODULE_NAME+"&"+VAR_ACTION+"=contribution_list&id="+id;
}

function del_contribution_list(id)
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
			url: ROOT+"?"+VAR_MODULE+"="+MODULE_NAME+"&"+VAR_ACTION+"=del_contribution_list&id="+id,
			data: "ajax=1",
			dataType: "json",
			success: function(obj){
				$("#info").html(obj.info);
				if(obj.status==1)
				location.href=location.href;
			}
	});
}

function del_forbid_list(id){
    if(!id)
    {
        idBox = $(".key:checked");
        if(idBox.length == 0)
        {
            alert("请选择需要删除的记录");
            return;
        }
        idArray = new Array();
        $.each( idBox, function(i, n){
            idArray.push($(n).val());
        });
        id = idArray.join(",");
    }
    if(confirm("确定要删除选中的记录吗？"))
        $.ajax({
            url: ROOT+"?"+VAR_MODULE+"="+MODULE_NAME+"&"+VAR_ACTION+"=del_forbid_list&id="+id,
            data: "ajax=1",
            dataType: "json",
            success: function(obj){
                $("#info").html(obj.info);
                if(obj.status==1)
                    location.href=location.href;
            }
        });
}

