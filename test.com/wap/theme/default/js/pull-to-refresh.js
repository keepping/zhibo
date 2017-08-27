$(document).on("pageInit","#page-pai_podcast-goods,#page-pai_user-goods", function(e, pageId, $page) {
	// 下拉刷新
	if(pageId == 'page-pai_user-goods'){
		var pull_to_refresh_url = TMPL+"index.php?ctl=pai_user&act=goods&is_true="+is_true;
	    var $content = $($page).find(".content").on('refresh', function(e) {
	    	refresh(pull_to_refresh_url,pageId,"#refresh-layer",$content,function(){
	    		// 倒计时
			    $(".left_time").each(function(){
			    	var leftTime = Math.abs(parseInt($(this).attr("data-leftTime")));
			    	left_time(leftTime,$(this));
			    });

			    // 初始化分页数据
				p = 2;
				has_next = $("input[name='has_next']").val();
	    	});
	    });
	}
	if(pageId == 'page-pai_podcast-goods'){
		var pull_to_refresh_url = TMPL+"index.php?ctl=pai_podcast&act=goods&is_true="+is_true;
	    var $content = $($page).find(".content").on('refresh', function(e) {
	    	refresh(pull_to_refresh_url,pageId,"#refresh-layer",$content,function(){
	    		// 倒计时
			    $(".left_time").each(function(){
			    	var leftTime = Math.abs(parseInt($(this).attr("data-leftTime")));
			    	left_time(leftTime,$(this));
			    });

			    // 初始化分页数据
				p = 2;
				has_next = $("input[name='has_next']").val();
	    	});
	    });
	}
});



