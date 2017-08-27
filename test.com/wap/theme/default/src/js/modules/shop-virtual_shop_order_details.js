$(document).on("pageInit","#page-shop-virtual_shop_order_details", function(e, pageId, $page) {
	//付款
	$(document).on('click', '.J-pay', function(){
        window.location.href = TMPL + "index.php?ctl=pay&act=h5_pay&order_sn="+data.order_sn+"&order_id="+data.order_id;
		// $.ajax({
  //           url: APP_ROOT+"/mapi/index.php?ctl=pay&act=h5_pay&itype=shop",
  //           data: data,
  //           type: 'POST',
  //           dataType: 'json',
  //           success:function(data){
  //               if(data.status == 1){
  //                   $.toast(data.error,1000);
  //                   setTimeout(function(){
  //                   	window.location.reload(); 
  //                   },1000);
  //               }
  //               else{
  //                   $.toast(data.error,1000);
  //               }
  //           },
  //           error:function(){
  //               $.hideIndicator();
  //               $.toast('请求失败，请检查网络',1000);
  //           }
  //       });
	});
	$(document).on('click', '.J-remind', function(){
		$.toast('已经提醒商家',1000);
	});
	$(document).on('click', '#J-return_virtual_pai', function(){
      handleAjax.handle(TMPL+"index.php?ctl=pai_podcast&act=buyer_confirm_date",data).done(function(resp){
            $.toast('确认收货成功',1000);
            setTimeout(function(){
               window.location.reload(); 
            },1000);
        }).fail(function(err){
            $.toast(err,1000);
        });

	});
	$(document).on('click', '.J-buyer_to_complaint', function(){
      handleAjax.handle(TMPL+"index.php?ctl=pai_podcast&act=buyer_to_complaint",data).done(function(resp){
            $.toast('已提交申请',1000);
            setTimeout(function(){
               window.location.reload(); 
            },1000);
        }).fail(function(err){
            $.toast(err,1000);
        });

	});
});