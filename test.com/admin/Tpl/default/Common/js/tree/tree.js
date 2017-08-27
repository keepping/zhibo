$(function() {
	$('.tree li:has(ul)').addClass('parent_li').find(' > span').attr('title', 'Collapse this branch');
	$('.tree li.parent_li > span').on('click', function(e) {
		var children = $(this).parent('li.parent_li').find(' > ul > li');
		if(children.is(":visible")) {
			children.hide('fast');
			$(this).attr('title', 'Expand this branch').parent(".parent_li").find(' > i').addClass('iconTree-plus-sign').removeClass('iconTree-minus-sign');
			$(this).attr('title', 'Expand this branch').find(' > i').addClass('iconTree-folder-open').removeClass('iconTree-user');
		} else {
			children.show('fast');
			$(this).attr('title', 'Collapse this branch').parent(".parent_li").find(' > i').addClass('iconTree-minus-sign').removeClass('iconTree-plus-sign');
			$(this).attr('title', 'Collapse this branch').find(' > i').addClass('iconTree-user').removeClass('iconTree-folder-open');
		}
		e.stopPropagation();
	});
});