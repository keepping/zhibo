function infinite_scroll($page,ajax_url,cls,vm_paging,func) {
    if (loading || vm_paging.page>total_page){
        $(".content-inner").css({paddingBottom:"0"});
        return;
    }
    loading = true;

    handleAjax.handle(ajax_url,{page:vm_paging.page},"html").done(function(result){
        var tplElement = $('<div id="tmpHTML"></div>').html(result),
        htmlObject = tplElement.find(cls),
        html = $(htmlObject).html();
        $(html).find(".total_page").remove();
        vm_paging.page++;
        loading = false;
        $($page).find(cls).append(html);
        $.refreshScroller();
        if(func!=null){
            func();
        }
        // $('.lazyload').picLazyLoad();
    }).fail(function(err){
        $.toast(err);
    });
}