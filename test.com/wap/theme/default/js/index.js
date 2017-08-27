$(document).on("pageInit","#page-index", function(e, pageId, $page) {
	

	var app = angular.module('myApp' , ['me-lazyimg']);

	var item1 = document.getElementById('item1mobile');
	var item2 = document.getElementById('item2mobile');
	var item3 = document.getElementById('item3mobile');

	// 判断是否登录微信
/*	$.ajax({ 
		url: app_url+'/wap/index.php?ctl=index&act=app_login',
		type: "GET",
		dataType: "json",
		success: function(data){
			if(data.is_login == 0){
				$.modal({
					title: '',
			      	text: '<div class="login"><div class="logo"></div><div class="operation"><h1>点击微信登录<			      	text: '<div class="login"><div class="logo"></div><div class="operation"><h1>点击微信登录</h1><div class="mode"><div class="weixin J_weixin_login_btn" sdk-data="'+data.s
			      	buttons: []
				});
				$(".J_weixin_login_btn").bind("click",function(){
					try{
				  		App.login_sdk('wxlogin');
						return false;
				  	}
				  	catch(e){
				  		$.toast("SDK调用失败");
						return false;
				  	}
			  	});
			}
		}
	});*/

	// 搜索
	app.controller('key_word_search', function($scope, $http) {
		$scope.key_word = '';
		$scope.null_tip_show = 'hide';
		$scope.change = function(){
			$.showIndicator();
			var key_word = $("input[name='key_word']").val();
			$http.get(app_url+'/wap/index.php?ctl=index&act=search&key_word='+key_word).success(function(data){
				$.hideIndicator();
				console.log(data.users);
				if(key_word){
					if(data.users){
		  				$scope.search_user_list = data.users;
					}
					else{
						$scope.search_user_list='';
						$scope.null_tip_show = '';
					}
				}
				else{
					$scope.null_tip_show = 'hide';
				}
			});
		}
		$scope.J_focus = function(id,is_focus,target){
			var ajax_url;
			is_focus ? ajax_url = app_url+'/wap/index.php?ctl=app&act=follow' : ajax_url = app_url+'/wap/index.php?ctl=app&act=follow';
			$.ajax({ 
				url: ajax_url,
				dataType: "json",
				data:"to_user_id="+id,
				type: "POST",
				success: function(ajaxobj){
					if(is_focus){
						$.alert("取消关注成功！");
					}
					else{
						$.alert("关注成功！");
					}
				},
				error:function(ajaxobj)
				{
					// if(ajaxobj.responseText!='')
					// alert(ajaxobj.responseText);
				}
			});
		}
	});

	
	$(document).on('click','.open-agreement', function () {
	  	$.popup('.popup-agreement');
	});
	$(document).on('click','.close-popup-agreement', function () {
	  	$.closeModal('.popup-agreement');
	});

	$("#J_agree").on('click',function(){
		$.closeModal('.popup-agreement');
		$.popup('.popup-live');
	});
	
	$("#J_agree_1").on('click',function(){
		var title = $("input[name='live_name']").val();
		$.ajax({ 
			url: app_url+'/wap/index.php?ctl=video&act=add_video',
			data:"title="+title,
			type: "GET",
			dataType: "json",
			success: function(data){
				if(data.status == 1){
					//var data_arr = [{room_id:Number(data.room_id)},{group_id:data.group_id}];
					var data_arr = '{"room_id":' +Number(data.room_id) +', "group_id":"' + data.group_id +'"}'; 
						try{
					  		App.create_live(data_arr);
					  	}
					  	catch(e){
					  		$.toast("SDK调用失败");
					  	}			
				}else{
					console.log(111);
				}
			}
		});
		
	});

	$(document).on('click','.close-popup-live', function () {
	  	$.closeModal('.popup-live');
	});

	app.controller('myCtrl_ctrl_pop_live', function($scope) {
	    $scope.live_name = "";
	    $scope.toggle = function () {
	  		$scope.focus = !$scope.focus;
	 	}
	});

	app.controller('myCtrl_header_search', function($scope) {
	    $scope.blur = "";
	});


	// bannner 数据
/*	app.controller('myCtrl_banner', function($scope, $http) {
		$http.get(app_url+'/wap/index.php?ctl=index&act=banner').success(function(data){
			var data_banner = data.banner;
  			$scope.banner = data_banner;
  			$scope.$on('ngRepeatFinished', function (ngRepeatFinishedEvent) {
		      	$.reinitSwiper();
			});
		});
	});*/

	// 热门 数据
	app.controller('myCtrl_video_hot', function($scope, $http) {
		$.showIndicator();
		$http.get(app_url+'/wap/index.php?ctl=index&act=index').success(function(data){
			console.log(data);
			$(".J_login").on('click',function(){

			is_login(data.user_info.is_login,data.user_info.sdk_data);
			});
			$.hideIndicator();
			var data_banner = data.banner;
  			$scope.banner = data_banner;

  			var data_video_hot = data.video_hot;
  			$scope.video_hot = data_video_hot;

  			$scope.$on('ngRepeatFinished', function (ngRepeatFinishedEvent) {
		      	$.reinitSwiper();
			});
		});
		$scope.join_live = function(room_id){
			
			//var data_video_hot_arr = [{room_id:Number(room_id)},$scope.video_hot];
			var data_video_hot_arr = '{"room_id":' +Number(room_id) +', "list":' + JSON.stringify($scope.video_hot) +' }'; 
			try{
			  	App.join_live(data_video_hot_arr);
		  	}
			catch(e){
			  	$.toast("SDK调用失败");
		  	}
		}

	 	$scope.do_refresher = function() {
            $http.get(app_url+'/wap/index.php?ctl=index&act=index').success(function(data){
            	var data_banner = data.banner;
  				$scope.banner = data_banner;

	  			var data_video_hot = data.video_hot;
  				$scope.video_hot = data_video_hot;
			});
        }
	});

	// 最新 数据
	app.controller('myCtrl_video_new', function($scope, $http) {
		$scope.do_load = function() {
			$.showIndicator();
            $http.get(app_url+'/wap/index.php?ctl=index&act=new_video').success(function(data){
            	$.hideIndicator();
            	item3.querySelector('.angluar_loading').remove();
	  			var data_video_new = data.video_new;
  				$scope.video_new = data_video_new;
			});
        }
        $scope.join_live = function(room_id){
			//var data_arr =[{room_id:Number(room_id)},$scope.video_new];
			var data_arr = '{"room_id":' +Number(room_id) +', "list":' + JSON.stringify($scope.video_new) +' }'; 
			try{
				App.join_live(data_arr);
		  	}
			catch(e){
			  	$.toast("SDK调用失败");
		  	}
		}
        $scope.do_refresher = function() {
            $http.get(app_url+'/wap/index.php?ctl=index&act=new_video').success(function(data){
	  			var data_video_new = data.video_new;
  				$scope.video_new = data_video_new;
			});
        }
	});
	
	// 关注 数据
	app.controller('myCtrl_video_playback', function($scope, $http) {
		$scope.do_load = function() {
			$.showIndicator();
            $http.get(app_url+'/wap/index.php?ctl=index&act=focus_video').success(function(data){
            	$.hideIndicator();
            	$(".module_item_live_focus").show();
            	item1.querySelector('.angluar_loading').remove();

            	// 关注的直播
	  			var data_video_focus = data.video_focus;
	  			var data_video_focus_length = data_video_focus.length;
  				$scope.video_focus = data_video_focus;
  				$scope.video_focus_length = data_video_focus_length;

  				// 精彩回放
	  			var data_view_playback = data.playback;
	  			var data_view_playback_length = data_view_playback.length;
  				$scope.view_playback = data_view_playback;
  				$scope.view_playback_length = data_view_playback_length;
			});
        }
        $scope.do_refresher = function() {
            $http.get(app_url+'/wap/index.php?ctl=index&act=focus_video').success(function(data){
	  			// 关注的直播
	  			var data_video_focus = data.video_focus;
	  			var data_video_focus_length = data_video_focus.length;
  				$scope.video_focus = data_video_focus;
  				$scope.video_focus_length = data_video_focus_length;

  				// 精彩回放
	  			var data_view_playback = data.playback;
	  			var data_view_playback_length = data_view_playback.length;
  				$scope.view_playback = data_view_playback;
  				$scope.view_playback_length = data_view_playback_length;
			});
        }
	});

	app.directive('onFinishRenderFilters', function ($timeout) {
	    return {
	        restrict: 'A',
	        link: function(scope, element, attr) {
	            if (scope.$last === true) {
	                $timeout(function() {
	                    scope.$emit('ngRepeatFinished');
	                });
	            }
	        }
	    };
	});

	mui.init();
	(function($) {
		//阻尼系数
		var deceleration = 0.0005;
		$('.mui-scroll-wrapper').scroll({
			bounce: false,
			indicators: true, //是否显示滚动条
			deceleration:deceleration
		});
		$.ready(function() {
			//循环初始化所有下拉刷新，上拉加载。
			$.each(document.querySelectorAll('.mui-slider-group .mui-refresh'), function(index, pullRefreshEl) {
				$(pullRefreshEl).pullToRefresh({
					down: {
						callback: function() {
							var self = this;
							setTimeout(function() {
								if(index == 0){var scope = angular.element(ngSection0).scope();}
								if(index == 1){var scope = angular.element(ngSection1).scope();}
								if(index == 2){var scope = angular.element(ngSection2).scope();}
								scope.$apply(function(){
									scope.do_refresher();
								});
								self.endPullDownToRefresh();
							}, 500);
						}
					}
				});
				// 添加'refresh'监听器
				// $(pullRefreshEl).on('refresh', '.pull-to-refresh-content',function(e) {
				//     // 模拟2s的加载过程
				//     setTimeout(function() {
				//        alert(11);
				//         // 加载完毕需要重置
				//         $.pullToRefreshDone('.pull-to-refresh-content');
				//     }, 2000);
				// });
				// $(document).on('refresh', '.pull-to-refresh-content',function(e) {
				//  	setTimeout(function() {
				//        alert(1);
				//         // 加载完毕需要重置
				//         $.pullToRefreshDone('.pull-to-refresh-content')
		  //       	}, 2000);
				// });
			});
		});

		// 监听切换页面
		document.getElementById('slider').addEventListener('slide', function(e) {
			// 如果是（最新）页面
			if (e.detail.slideNumber === 2) {
				// 判断是否加载过，否则进行加载
				if (item3.querySelector('.angluar_loading')) {
					var scope = angular.element(ngSection2).scope();
					scope.$apply(function(){
						scope.do_load();
					});
				}
			// 如果是（关注）页面
			} else if (e.detail.slideNumber === 0) {
				if (item1.querySelector('.angluar_loading')) {
					var scope = angular.element(ngSection0).scope();
					scope.$apply(function(){
						scope.do_load();
					});
				}
			}
		});
	})(mui);
});