function pull_refresh($page,ajax_url,cls,vm_paging,callback){
    var loading = false;
    if (loading) return;
    loading =true;
    

    handleAjax.handle(ajax_url,'',"html").done(function(result){
        refreshing = false;
        var tplElement = $('<div id="tmpHTML"></div>').html(result),
        htmlObject = tplElement.find(cls),
        list_ele = $($page).find(cls),
        html = $(htmlObject).html();
        value = html.replace(/\s+/g,"");

        var result = $(result).find(".content").html(), total_page = htmlObject.find("input[name='total_page']").val();
        loading =false;
        vm_paging.page = 2;
        vm_paging.total_page = total_page;
        setTimeout(function() {

            list_ele.addClass('animated fadeInUp').html(value.length > 0 ? html : '<div style="text-align:center;color:#999;font-size:0.75rem;">暂无数据</div>');

            setTimeout(function(){
                list_ele.removeClass('fadeInUp');
            }, 1000);

            // 加载完毕需要重置
            $.pullToRefreshDone('.pull-to-refresh-content');
            $(".pull-to-refcontainerresh-layer").css({"visibility":"visible"});

            // 初始化分页数据
            page = 2;

            // 初始化懒加载图片
            // $('.lazyload').picLazyLoad();

            if(typeof(callback) == 'function'){
                callback.call(this);
            }
        }, 300);

    }).fail(function(err){
        $.toast(err);
    });
}