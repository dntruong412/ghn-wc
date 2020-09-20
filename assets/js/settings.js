jQuery(document).ready(function($) {
	$('#register-submit').on('click', function() {
		var container = $(this);
		var new_name = $('[name=new_name]').val();
		var new_address = $('[name=new_address]').val();
		var new_tel = $('[name=new_tel]').val();
		var new_district = parseInt($('[name=new_district]').val()) || 0;
		var new_ward = parseInt($('[name=new_ward]').val()) || 0;
		var toast = ghn_loading_msg('Đang tạo cửa hàng'); // loading
		
		return $.ajax({
			url: ajaxurl,
			type: 'POST',
			dataType: 'json',
			data: {
				action: 'ghn_ajax_shop_create',
				new_name: new_name,
				new_address: new_address,
				new_tel: new_tel,
				new_district: new_district,
				new_ward: new_ward,
			},
			success: function(ret) {
				if (ret.success) {
					var redirect_url = ret.redirect_url;
					
					ghn_loading_msg_close(toast); // end loading
					
					return window.location.href = redirect_url;
				} else {
					ghn_loading_msg_close(toast); // end loading
					
					return ghn_notice_msg(ret.message);
				}
			}
		});
	});
	
	$('[type=radio][name=ghn_shopid]').on('click', function() {
		var container = $(this);
		var pare = container.closest('li');
		
		pare.addClass('ghn-list-store-item-active').siblings().removeClass('ghn-list-store-item-active');
		
		// change data
		$('[name=ghn_shopname]').val(pare.data('name'));
		$('[name=ghn_shoptel]').val(pare.data('tel'));
		$('[name=ghn_shopaddress]').val(pare.data('address'));
		$('[name=ghn_shopdistrict]').val(pare.data('district_id'));
		$('[name=ghn_shopward]').val(pare.data('ward_code'));
		
		// change makeup
		$('#view_ghn_shopname').val(pare.data('name'));
		$('#view_ghn_shoptel').val(pare.data('tel'));
		$('#view_ghn_shopaddress').val(pare.data('address'));
		$('#view_ghn_shopdistrict').val(pare.data('district_name'));
		$('#view_ghn_shopward').val(pare.data('ward_code'));
		
		$('#makeup-form').show();
		return $('#choose-form').hide();
	});
});