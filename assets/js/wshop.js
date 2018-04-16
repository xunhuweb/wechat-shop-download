(function($,api){
	window.wshop_jsapi={
		shopping_add_to_cart:function(context){
			var dom_id = '#btn-add-to-cart-'+context;
			var pre_text = $(dom_id).html();
			
			var settings ={ajax:{}}; 
			window.wshop_jsapi.add_to_cart(context,settings,function(context){
				if($(dom_id).attr('data-loading')){
					return false;
				}
				
				$(dom_id).attr('data-loading','1').html(api.msg_processing);
				return true;
			},function(){
				$(dom_id).removeAttr('data-loading').html(pre_text);
			},function(m,settings){
				alert(api.msg_add_to_cart_successfully);
				location.reload();
			});
			return false;
		},
		shopping:function(context){
			var dom_id = '#btn-pay-button-'+context;
			var pre_text = $(dom_id).html();
			
			var on_start=function(context){
				if($(dom_id).attr('data-loading')){
					return false;
				}
				
				$(dom_id).attr('data-loading','1').html(api.msg_processing);
				return true;
			};
			var on_end = function(){
				$(dom_id).removeAttr('data-loading').html(pre_text);
			};
			var settings ={ajax:{}}; 
			window.wshop_jsapi.create_order(context,settings,on_start,on_end,function(m){
				if(typeof m.data.redirect_url!='undefined'){
					location.href=m.data.redirect_url;
					return;
				}
				window.wshop_jsapi.confirm_order(context,{
        			ajax:{
        				url:m.data.url
        			}
        		},on_start,on_end,function(m){
        			location.href=m.data;
        		});
			});
			return false;
		},
		shopping_list:function(context){
			var dom_id = '#btn-pay-button-'+context;
			var pre_txt = $(dom_id).html();
			var settings ={ajax:{}}; 
			var loading_start = function(context){
				if($(dom_id).attr('data-loading')){
					return false;
				}
				$(dom_id).attr('data-loading',1).html(wshop_jsapi_params.msg_processing);
				return true;
			};
			var loading_end =function(){
				$(dom_id).removeAttr('data-loading').html(pre_txt);
			};
			window.wshop_jsapi.create_order(context,settings,loading_start,loading_end,function(m){
				if(!api.payment_methods||api.payment_methods.length==0){
            		alert(api.msg_no_payment_method);
            		return;
            	}
				
            	if(api.payment_methods.length==1){
            		window.wshop_jsapi.confirm_order(context,{
            			ajax:{
            				url:m.data.url
            			},
            			payment_method : api.payment_methods[0].id
            		},loading_start,loading_end,function(m){
            			location.href=m.data;
            		});
            		return;
            	}
            	
    			$('.wshop-pay-button').hide();
    			$('#wshop-modal-payment-gateways .xh-button-box .loading').hide();
    			$('#wshop-modal-payment-gateways')
	    			.css({'display':'block'})
	    			.attr('data-context',context)
	    			.attr('data-url',m.data.url);
    	
    			window.wshop_jsapi.inner.__modal_btnui_resize();
			});
			return false;
		},
		shopping_one_step:function(context){
			var dom_id = '#btn-pay-button-'+context;
			var pre_txt = $(dom_id).html();
			var settings ={ajax:{}}; 
			
			var loading_start = function(context){
				if($(dom_id).attr('data-loading')){
					return false;
				}
				$(dom_id).attr('data-loading',1).html(wshop_jsapi_params.msg_processing);
				return true;
			};
			var loading_end =function(){
				$(dom_id).removeAttr('data-loading').html(pre_txt);
			};
			window.wshop_jsapi.create_order(context,settings,loading_start,loading_end,function(m){
				if(!api.payment_methods||api.payment_methods.length==0){
            		alert(api.msg_no_payment_method);
            		return;
            	}
				
            	if(api.payment_methods.length==1){
            		window.wshop_jsapi.confirm_order(context,{
            			ajax:{
            				url:m.data.url
            			},
            			payment_method : api.payment_methods[0].id
            		},loading_start,loading_end,function(m){
            			location.href=m.data;
            		});
            		return;
            	}
            	$('.wshop-pay-button').hide();
            	$('#wshop-modal-payment-gateways-1-qrcode').attr('src',m.data.qrcode_url);
            	$('#wshop-modal-payment-gateways-1-amount').html(m.data.price_html);
            	$('#shop-modal-payment-gateways-payment-method').css('color','#333').html($('#shop-modal-payment-gateways-payment-method-pre').html());
            	$('#wshop-modal-payment-gateways-1').css({'display':'block'});
    		
    			window.wshop_jsapi.inner.__modal_qrcode_resize();
    			
    			window.wshop_jsapi.__stop_loop=false;
    			
				window.wshop_jsapi.loop_query(m.data.url_query,function(result,url,on_success){
					if(result.data.payment_method!=null){
	            		$('#shop-modal-payment-gateways-payment-method').html('您正在使用 '+result.data.payment_method+'进行支付').css('color','green');
	            	}
	            	
	            	if(result.data.paid){
	            		window.wshop_jsapi.__stop_loop=true;
	            		$('#shop-modal-payment-gateways-payment-method').html('您已使用'+result.data.payment_method+'支付成功，跳转中...').css('color','green');
	            		location.href=result.data.received_url;
	            		return;
	            	}
	            	
	            	window.wshop_jsapi.loop_query(url,on_success);
				});
			});
			return false;
		},
		shopping_cart:function(context){
			var dom_id = '#btn-pay-button-'+context;
			var pre_txt = $(dom_id).html();
			var settings ={ajax:{}}; 
			window.wshop_jsapi.create_order(context,settings,function(context){
				if($(dom_id).attr('data-loading')){
					return false;
				}
				$(dom_id).attr('data-loading',1).html(api.msg_processing);
				return true;
			},function(){
				$(dom_id).removeAttr('data-loading').html(pre_txt);
			},function(m){
				location.href=m.data;
			});
			return false;
		},
		add_to_cart:function(context,settings,loading_start,loading_end,on_success){
			$(document).trigger('wshop_form_'+context+'_add_to_cart',settings);

			window.wshop_jsapi.inner.__sync(context,settings,loading_start,loading_end,settings.ajax.onsuccess?settings.ajax.onsuccess:on_success);
			return false;
		},
		confirm_order:function(context,settings,loading_start,loading_end,on_success){
			$(document).trigger('wshop_form_'+context+'_confirm_order',settings);
			window.wshop_jsapi.inner.__sync(context,settings,loading_start,loading_end,on_success);
			return false;
		},
		create_order:function(context,settings,loading_start,loading_end,on_success){
			$(document).trigger('wshop_form_'+context+'_submit',settings);
			window.wshop_jsapi.inner.__sync(context,settings,loading_start,loading_end,on_success);
			return false;
		},
		stop_loop:function(){
			window.wshop_jsapi.__stop_loop=true;
		},
		loop_query:function(url,on_success){
			if(window.wshop_jsapi.__stop_loop){
				return;
			}
			
			setTimeout(function(){
				$.ajax({
		            url: url,
		            type: 'post',
		            timeout: 60 * 1000,
		            async: true,
		            cache: false,
		            dataType: 'json',
		            success: function(m) {
		            	if(m.errcode!=0){
		            		window.wshop_jsapi.loop_query(url,on_success);
		            		return;
						}

		            	on_success(m,url,on_success);
		            },error:function(e){
			            console.warn(e.responseText);
			            window.wshop_jsapi.loop_query(url,on_success);
			         }
		         });
			},1500);
			
		}
	};
	
	window.wshop_jsapi.inner={
		__modal_btnui_resize:function(){
			var $ul =$('#wshop-modal-payment-gateways .xh-button-box');
			var width = window.innerWidth,height = window.innerHeight;
			if (typeof width != 'number') { 
			    if (document.compatMode == 'CSS1Compat') {
			        width = document.documentElement.clientWidth;
			        height = document.docuementElement.clientHeight;
			    } else {
			        width = document.body.clientWidth;
			        height = document.body.clientHeight; 
			    }
			}
			if(width>450){
				$ul.css({
					top:((height - $ul.height()) / 2) + "px",
					left:((width - $ul.width()) / 2) + "px"
				});
			}else{
				$ul.css({top:'',left:''});
			}
		},
		__modal_qrcode_resize:function(){
			var $ul =$('#wshop-modal-payment-gateways-1 div.mod-ct');
			var width = window.innerWidth,height = window.innerHeight;
			if (typeof width != 'number') { 
			    if (document.compatMode == 'CSS1Compat') {
			        width = document.documentElement.clientWidth;
			        height = document.docuementElement.clientHeight;
			    } else {
			        width = document.body.clientWidth;
			        height = document.body.clientHeight; 
			    }
			}
			if(width>450){
				$ul.css({
					top:((height - 415) / 2) + "px",
					left:((width - 450) / 2) + "px"
				});
			}else{
				$ul.css({top:'',left:''});
			}
		},
		__sync:function(context,settings,loading_start,loading_end,on_success){
			if(loading_start&&loading_start(context,settings)===false){return;}
			
			window.wshop_jsapi.stop_loop();
			var ajax_url =  settings.ajax?settings.ajax.url:null;
			if(!ajax_url){
				alert('请检查页面中引用了多个jQuery.js文件！');
			}
			if(settings.ajax){
				delete settings.ajax;
			}
			$.ajax({
	            url: ajax_url,
	            type: 'POST',
	            timeout: 60 * 1000,
	            async: true,
	            cache: false,
	            data: settings,
	            dataType: 'json',
	            success: function(m) {
	            	if(loading_end){loading_end(context,settings);}
	            	if(m.errcode!=0){
		            	if(m.errcode==501){
		            		if(typeof settings.data=='undefined'){
		            			settings.data={};
		            		}
		            		
		            		if(typeof settings.data.location=='undefined'){
		            			settings.data.location='/';
		            		}
		            		location.href=api.wp_login_url.replace('#location#',settings.data.location);
		            		return;
			            }
		            	
		            	alert(m.errmsg);
		            	return;
					}
	            	
	            	if(on_success){on_success(m,settings);}
	            },error:function(e){
		            console.warn(e.responseText);
		            if(loading_end){loading_end(context,settings);}
	            	alert(wshop_jsapi_params.msg_err_500);
		         }
	         });
		}
	};
	
	$(function(){
		$(window).resize(function(){
			window.wshop_jsapi.inner.__modal_btnui_resize();
			window.wshop_jsapi.inner.__modal_qrcode_resize();
		});

		$('#wshop-modal-payment-gateways .cover,#wshop-modal-payment-gateways .xh-button-box .close').click(function(){
			$('#wshop-modal-payment-gateways').hide();
			$('#wshop-modal-payment-gateways-1').hide();
			
			window.wshop_jsapi.stop_loop();
			return false;
		});
		
		$('#wshop-modal-payment-gateways-1 .cover,#wshop-modal-payment-gateways-1 .mod-ct .xh-close').click(function(){
			$('#wshop-modal-payment-gateways').hide();
			$('#wshop-modal-payment-gateways-1').hide();
			
			window.wshop_jsapi.stop_loop();
			return false;
		});

		$('#wshop-modal-payment-gateways .xh-button-box .xh-item').click(function(){
			var payment_method = $(this).attr('data-id');
			var context = $('#wshop-modal-payment-gateways').attr('data-context');
			var url = $('#wshop-modal-payment-gateways').attr('data-url');
		
			window.wshop_jsapi.confirm_order(context,{
    			ajax:{
    				url:url
    			},
    			payment_method:payment_method
    		},function(context){
    			if($('#wshop-modal-payment-gateways').attr('data-loading')){
    				return false;
    			}
    			$('#wshop-modal-payment-gateways').attr('data-loading',1);
            	$('#wshop-modal-payment-gateways .xh-button-box .loading').show();
            	return true;
    		},function(context){
    			$('#wshop-modal-payment-gateways').removeAttr('data-loading');
    			$('#wshop-modal-payment-gateways .xh-button-box .loading').hide();
    		},function(m){
    			location.href=m.data;
    		});
			return false;
		});
	});
})(jQuery,wshop_jsapi_params);