$(document).ready(function(){
	$(".menu").find("a").bind("click",function(){
		$(".menu").find("a").removeClass("current");
		parent.main.location.href = $(this).attr("href");
		$(this).addClass("current");
		return false;
	});
	
	$(".menu").find("a").first().click();
		//侧边导航栏linkto
	$(".navlist>a").bind('click',function(){
    	$(this).parents(".navlist").toggleClass("active");
    	$(this).toggleClass("active");
    });
    $(".linkto").bind('click',function(){
		parent.main.location.href = $(this).attr("data-href");
		$(".linkto").removeClass("active");
    	$(this).addClass("active");
		return false;

    });
});