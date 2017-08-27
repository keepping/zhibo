function account(user_id)
{
	$.ajax({
			url: ROOT+"?"+VAR_MODULE+"="+MODULE_NAME+"&"+VAR_ACTION+"=account&id="+user_id,
			data: "ajax=1",
			dataType: "json",
			success: function(msg){
				if(msg.status==0){
					alert(msg.info);
				}
			},
			error: function(){
				$.weeboxs.open(ROOT+'?'+VAR_MODULE+'='+MODULE_NAME+'&'+VAR_ACTION+'=account&id='+user_id, {contentType:'ajax',showButton:false,title:LANG['USER_ACCOUNT'],width:600,height:260});
			}
		});
	
}
function account_detail(user_id)
{
	location.href = ROOT+"?"+VAR_MODULE+"="+MODULE_NAME+"&"+VAR_ACTION+"=account_detail&id="+user_id;
}
function prop(id)
{
    location.href = ROOT+"?"+VAR_MODULE+"="+MODULE_NAME+"&"+VAR_ACTION+"=prop&id="+id;

}
function distribution_log(id)
{
    location.href = ROOT+"?"+VAR_MODULE+"="+MODULE_NAME+"&"+VAR_ACTION+"=distribution_log&id="+id;

}
function distribution_user(id)
{
    location.href = ROOT+"?"+VAR_MODULE+"="+MODULE_NAME+"&"+VAR_ACTION+"=distribution_user&id="+id+"&years=-1&month=-1";

}
//设备信息
function equipment_info(id)
{
    location.href = ROOT+"?"+VAR_MODULE+"="+MODULE_NAME+"&"+VAR_ACTION+"=equipment_info&id="+id;
}

function exchange_log(user_id)
{
    location.href = ROOT+"?"+VAR_MODULE+"="+MODULE_NAME+"&"+VAR_ACTION+"=exchange_log&id="+user_id;
}

function consignee(user_id)
{
	location.href = ROOT + '?m=User&a=consignee&id='+user_id;
}

function weibo(user_id)
{
	location.href = ROOT + '?m=User&a=weibo&id='+user_id;
}
function userBank(user_id)
{
	location.href = ROOT + '?m=UserBank&a=index&user_id='+user_id;
}
function user_bank(user_id)
{
	location.href = ROOT + '?m=User&a=userbank_index&user_id='+user_id;
}

//关注列表
function focus_list(id){
    location.href = ROOT+"?"+VAR_MODULE+"="+MODULE_NAME+"&"+VAR_ACTION+"=focus_list&id="+id;
}

//关注列表
function fans_list(id){
    location.href = ROOT+"?"+VAR_MODULE+"="+MODULE_NAME+"&"+VAR_ACTION+"=fans_list&id="+id;
}


//删除关注
function del_focus_list(id,user_id){
    if(!id)
    {
        idBox = $(".key:checked");
        if(idBox.length == 0)
        {
            alert("请选择需要删除的关注");
            return;
        }
        idArray = new Array();
        $.each( idBox, function(i, n){
            idArray.push($(n).val());
        });
        id = idArray.join(",");
    }
    if(confirm("确定要删除选中的关注吗？"))
        $.ajax({
            url: ROOT+"?"+VAR_MODULE+"="+MODULE_NAME+"&"+VAR_ACTION+"=del_focus_list&id="+id+"&user_id="+user_id,
            data: "ajax=1",
            dataType: "json",
            success: function(obj){
                if(obj.status==1)
                    location.href = location.href;
                else
                    alert(obj.info);
            }
        });
}

//删除粉丝
function del_fans_list(id,user_id){
    if(!id)
    {
        idBox = $(".key:checked");
        if(idBox.length == 0)
        {
            alert("请选择需要删除的粉丝");
            return;
        }
        idArray = new Array();
        $.each( idBox, function(i, n){
            idArray.push($(n).val());
        });
        id = idArray.join(",");
    }
    if(confirm("确定要删除选中的粉丝吗？"))
        $.ajax({
            url: ROOT+"?"+VAR_MODULE+"="+MODULE_NAME+"&"+VAR_ACTION+"=del_fans_list&id="+id+"&user_id="+user_id,
            data: "ajax=1",
            dataType: "json",
            success: function(obj){
                if(obj.status==1)
                    location.href = location.href;
                else
                    alert(obj.info);
            }
        });
}

//印票贡献榜
function contribution_list(id)
{
    location.href = ROOT+"?"+VAR_MODULE+"="+MODULE_NAME+"&"+VAR_ACTION+"=contribution_list&id="+id;
}

//消息推送
function push(id)
{
    location.href = ROOT+"?"+VAR_MODULE+"="+MODULE_NAME+"&"+VAR_ACTION+"=push&id="+id;
}

//删除推送消息
function del_push(id){
    if(!id)
    {
        idBox = $(".key:checked");
        if(idBox.length == 0)
        {
            alert("请选择需要删除的消息");
            return;
        }
        idArray = new Array();
        $.each( idBox, function(i, n){
            idArray.push($(n).val());
        });
        id = idArray.join(",");
    }
    if(confirm("确定要删除选中的消息吗？"))
        $.ajax({
            url: ROOT+"?"+VAR_MODULE+"="+MODULE_NAME+"&"+VAR_ACTION+"=del_push&id="+id,
            data: "ajax=1",
            dataType: "json",
            success: function(obj){
                $("#info").html(obj.info);
                if(obj.status==1)
                    location.href=location.href;
            }
        });
}


