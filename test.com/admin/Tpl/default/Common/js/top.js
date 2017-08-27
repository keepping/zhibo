$(document).ready(function(){
	//绑定头部清除缓存
//	$("#clearcache").bind("click",function(){
//		$("#info").html(LANG['AJAX_RUNNING']);
//		$("#info").show();
//		$.ajax({ 
//			url: $(this).attr("href"), 
//			data: "ajax=1",
//			dataType: "json",
//			success: function(obj){
//				$("#info").html(obj.info);				
//				$("#info").oneTime(2000, function() {				    
//			    $(this).fadeOut(2,function(){$("#info").html("");});
//			   
//			 });
//			}
//		});
//		return false;
//	});	
	//下拉菜单显示隐藏
	$(".navrightlist").bind('click',function(){
    	$(this).toggleClass("active");
    });
	
	//绑定菜单按钮
	$("#navs").find("a").bind("click",function(){
		$("#navs").find("a").removeClass("active");
		parent.menu.location.href = $(this).attr("href");
		$(this).addClass("active");
		return false;		
	});
	$("#navs").find("a").bind("focus",function(){$(this).blur();});
	$("#navs").find("a").first().click();
	//左侧菜单显示隐藏drag-frame
	$(".c-left-nav").bind('click',function(){
    	toggleMenu();
    });
	
});	



function toggleMenu()
{
  frmBody = parent.document.getElementById('frame-body');
  imgArrow = document.getElementById('img');
  if (frmBody.cols == "0, 7, *")
  {
    frmBody.cols="221, 7, *";
    imgArrow.src = "__TMPL__Common/images/bar_close.gif";
  }
  else
  {
    frmBody.cols="0, 7, *";
    imgArrow.src = "__TMPL__Common/images/bar_open.gif";
  }
}