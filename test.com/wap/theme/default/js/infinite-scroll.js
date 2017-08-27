$(document).on("pageInit","#page-pai_podcast-goods,#page-pai_user-goods", function(e, pageId, $page) {
    // 无限加载
    var page_ajax_url;
	switch (pageId)
	{
		case 'page-pai_podcast-goods':
			page_ajax_url = TMPL+"index.php?ctl=pai_podcast&act=goods&post_type=json&ajax=1&is_true="+is_true;
	  		break;
	  	case 'page-pai_user-goods':
			page_ajax_url = TMPL+"index.php?ctl=pai_user&act=goods&post_type=json&ajax=1&is_true="+is_true;
	  		break;
	}
    pai_infinite_scroll($page,page_ajax_url);
});

function pai_infinite_scroll($page,page_ajax_url,func) {

	var loading=false;
	$($page).on('infinite', function() {
 	 	if (loading || !has_next){
 	 		if(!has_next){
				$(".infinite-scroll-preloader").addClass("data-null").html('<span style="color:#999;font-size:0.75rem;">无更多数据</span>').show();
 	 		}
 	 		$(".content-inner").css({paddingBottom:"0"});
 			return;
 	 	}
      	loading = true;
      	$.ajax({
	      	url:page_ajax_url,
	      	dataType: "html",
	        data:{p:p},
	        async:false,
	        success:function(data){
	        	var data = JSON.parse(data);
	        	has_next = data.is_has;
	        	p++;
	        	// page_ajax_url = data.page_ajax_url;
	        	setTimeout(function() {
	        		loading = false;
	        		$($page).find("#infinite_scroll_box").append(data.html);
	        		$.refreshScroller();

         		}, 300);
         		if(func!=null){
	            	func();
		        }
	        }
      	});
      	return false;
    });
}