jQuery(document).ready(function($) {
	// Gửi hàng tại điểm giao nhận GHN 
	$('[type=checkbox][name=form_station]').on('click', function() {
		var container = $(this);
		
		$('.station-edit').hide();
		
		if (container.is(':checked')) {
			$('.ghn-list-items-station').show();
			
			return container.prop('checked', false);
		} else {
			$('.ghn-list-items-station-masked').html('');
			$('.ghn-list-items-station').hide();
			$('.ghn-list-items-station-masked').hide();
			
			return container.val(0);
		}
	});
	
	$('.ghn-list-items-station .ghn-list-item').on('click', 'a', function() {
		var container = $(this);
		
		if (container.data('id') > 0) {
			var html = '<li class="ghn-list-item" data-id="'+container.data('id')+'"><strong style="font-size: 1.2em;">'+container.data('name')+'</strong><br/>'+container.data('address')+'</li>';
			
			$('.ghn-list-items-station-masked').html(html);
			$('.ghn-list-items-station').hide();
			$('.ghn-list-items-station-masked').show();
			$('.station-edit').show();
			
			$('[type=checkbox][name=form_station]').val(container.data('id'));
			return $('[type=checkbox][name=form_station]').prop('checked', true);
		}
	});
	
	$('.station-edit').on('click', function() {
		var container = $(this);
		
		$('.ghn-list-items-station').toggle();
	});
	
	// Thêm địa chỉ trả hàng chuyển hoàn 
	$('[type=checkbox][name=form_return]').on('click', function() {
		var container = $(this);
		
		$('.station-edit').hide();
		
		if (container.is(':checked')) {
			$('.form_return-fields').show();
			
			return $('.return-field').attr('required', true);
		} else {
			$('.form_return-fields').hide();
			
			return $('.return-field').attr('required', false);
		}
	});
	
	// Khối lượng quy đổi 
	$('[name=length], [name=width], [name=height]').on('change', function() {
		var container = $(this);
		var d = parseInt($('[name=length]').val()) || 0;
		var r = parseInt($('[name=width]').val()) || 0;
		var c = parseInt($('[name=height]').val()) || 0;
		
		z = (d*r*c) / 5000;
		
		if (z > 0) {
			$('.drc').html(z);
			
			return ghn_calc_fee();
		}
	});
	
	// Gói cước
	$('[name=weight]').on('change', function() {
		var container = $(this);
		var w = parseInt(container.val()) || 0;
		
		if (w > 0) { 
			$('.feeweight').html('(cho khối lượng '+w+'g)');
			
			return ghn_calc_fee();
		}
	});
	
	$('[name=coupon], [name=insurance_value], [name=to_ward_code]').on('change', function() {
		return ghn_calc_fee();
	});
	
	$('body').on('click', '[name=form_service]', function() {
		return ghn_calc_total();
	});
	
	function ghn_calc_fee() {
		var d = parseInt($('[name=length]').val()) || 0;
		var r = parseInt($('[name=width]').val()) || 0;
		var c = parseInt($('[name=height]').val()) || 0;
		var w = parseInt($('[name=weight]').val()) || 0;
		var insurance_value = parseInt($('[name=insurance_value]').val()) || 0;
		var to_district_id = parseInt($('[name=to_district_id]').val()) || 0;
		var to_ward_code = parseInt($('[name=to_ward_code]').val()) || 0;
		var coupon = $('[name=coupon]').val();			
		var service_id = 0;
		var service_type_id = 0;
		var toast = ghn_loading_msg('Đang tính toán gói cước'); // loading
		
		if ($('[name=form_service]').length) {
			var ser = $('[name=form_service]:checked');
			
			if (ser.length) {
				var serli = ser.closest('li');
				
				service_id = parseInt(serli.data('service_id')) || 0;
				service_type_id = parseInt(serli.data('service_type_id')) || 0;
			}
		}
		
		if (d == 0 || r == 0 || c == 0 || w == 0
		 || insurance_value == 0 || to_district_id == 0 || to_ward_code == 0) {
			$('.ghn-list-items-fee').html('');
			
			ghn_loading_msg_close(toast); // end loading
					
			return ghn_notice_msg('Một số giá trị bị rỗng');
		}
		
		return $.ajax({
			url: ajaxurl,
			type: 'POST',
			dataType: 'json',
			data: {
				action: 'ghn_ajax_get_servicefees',
				to_district_id: to_district_id,
				to_ward_code: to_ward_code,
				insurance_value: insurance_value,
				coupon: coupon,
				length: d,
				width: r,
				height: c,
				weight: w,
			},
			success: function(ret) {
				if (ret.success) {
					var data = ret.data;
					var html = '';
					
					for (var i = 0; i < data.length; i++) {
						if (data[i].data_fee.service_fee == 0) continue;
						
						html += '<li class="ghn-list-item" data-service_id="'+data[i].service_id+'" data-service_type_id="'+data[i].service_type_id+'" data-total="'+data[i].data_fee.total+'" data-service_fee="'+data[i].data_fee.service_fee+'">';
						html += '<strong style="font-size: 1.2em;"><label>';
						html += '<input type="radio" name="form_service" value="'+data[i].service_id+'" '+((data[i].service_id == service_id || data[i].service_type_id == service_type_id) ? 'checked' : '')+' /> ';
						html += data[i].short_name;
						html += '</label></strong>';
						html += '<br/>'+data[i].data_fee.service_fee+' VNĐ';
						html += '</li>';
					}
					
					$('.ghn-list-items-fee').html(html);
					
					ghn_loading_msg_close(toast); // end loading
					
					return ghn_calc_total();
				} else {
					ghn_loading_msg_close(toast); // end loading
					
					return ghn_notice_msg('Không thể hiển thị dữ liệu gói cước');
				}
			}
		});
	}
	
	function ghn_calc_total() {
		var d = parseInt($('[name=length]').val()) || 0;
		var r = parseInt($('[name=width]').val()) || 0;
		var c = parseInt($('[name=height]').val()) || 0;
		var w = parseInt($('[name=weight]').val()) || 0;
		var insurance_value = parseInt($('[name=insurance_value]').val()) || 0;
		var to_district_id = parseInt($('[name=to_district_id]').val()) || 0;
		var to_ward_code = parseInt($('[name=to_ward_code]').val()) || 0;
		var coupon = $('[name=coupon]').val();	
		var service_id = 0;
		var service_type_id = 0;
		
		if ($('[name=form_service]').length) {
			var ser = $('[name=form_service]:checked');
			
			if (ser.length) {
				var serli = ser.closest('li');
				
				service_id = parseInt(serli.data('service_id')) || 0;
				service_type_id = parseInt(serli.data('service_type_id')) || 0;
			}
		}
		
		return $.ajax({
			url: ajaxurl,
			type: 'POST',
			dataType: 'json',
			data: {
				action: 'ghn_ajax_get_order_calc',
				to_district_id: to_district_id,
				to_ward_code: to_ward_code,
				insurance_value: insurance_value,
				coupon: coupon,
				length: d,
				width: r,
				height: c,
				weight: w,
				service_id: service_id,
				service_type_id: service_type_id,
			},
			success: function(ret) {
				if (ret.success) {
					var html = ret.html;
					
					return $('.fee-result').html(html);
				} else {
					return ghn_notice_msg(ret.message);
				}
			}
		});
	}
	
	// order
	$('#ghn-create').on('click', function() {
		return ghn_order('create');
	});
	
	$('#ghn-update').on('click', function() {
		return ghn_order('update');
	});
	
	$('#ghn-cancel').on('click', function() {
		var container = $(this);
		var id = container.data('id');
		
		return ghn_order_cancel(id);
	});
	
	$('#ghn-return').on('click', function() {
		var container = $(this);
		var id = container.data('id');
		
		return ghn_order_return(id);
	});
	
	$('#ghn-delivery-again').on('click', function() {
		var container = $(this);
		var id = container.data('id');
		
		return ghn_order_delivery_again(id);
	});
	
	$('#ghn-print').on('click', function() {
		var container = $(this);
		var id = container.data('id');
		
		return ghn_print(id);
	});
	
	function ghn_order(sub_act) {
		var payment_type_id = parseInt($('[name=payment_type_id]').val()) || 0;
		var note = $('[name=note]').val();
		var required_note = $('[name=required_note]').val();
		var return_phone = $('[name=return_phone]').val();
		var return_address = $('[name=return_address]').val();
		var return_district_id = $('[name=return_district_id]').val();
		var return_ward_code = $('[name=return_ward_code]').val();
		var client_order_code = $('[name=client_order_code]').val();
		var client_order_code_custom = $('[name=client_order_code_custom]').val();
		var to_name = $('[name=to_name]').val();
		var to_phone = $('[name=to_phone]').val();
		var to_address = $('[name=to_address]').val();
		var to_district_id = $('[name=to_district_id]').val();
		var to_ward_code = $('[name=to_ward_code]').val();
		var coupon = $('[name=coupon]').val();		
		var weight = parseInt($('[name=weight]').val()) || 0;
		var length = parseInt($('[name=length]').val()) || 0;
		var width = parseInt($('[name=width]').val()) || 0;
		var height = parseInt($('[name=height]').val()) || 0;
		var cod_amount = parseInt($('[name=cod_amount]').val()) || 0;
		var insurance_value = parseInt($('[name=insurance_value]').val()) || 0;
		var pick_station_id = parseInt($('[name=form_station]').val()) || 0;
		var post_id = parseInt($('[name=post_id]').val()) || 0;
		var service_id = 0;
		var service_type_id = 0;
		var loading_msg = (sub_act == 'create') ? 'Đang tạo đơn GHN' : 'Đang cập nhật đơn GHN';
		var toast = ghn_loading_msg(loading_msg); // loading
		
		if ($('[name=form_service]').length) {
			var ser = $('[name=form_service]:checked');
			
			if (ser.length) {
				var serli = ser.closest('li');
				
				service_id = parseInt(serli.data('service_id')) || 0;
				service_type_id = parseInt(serli.data('service_type_id')) || 0;
			} else {
				ghn_loading_msg_close(toast); // end loading
					
				return ghn_notice_msg('Chưa chọn gói cước');
			}
		}		
		
		return $.ajax({
			url: ajaxurl,
			type: 'POST',
			dataType: 'json',
			data: {
				action: 'ghn_ajax_order',
				sub_action: sub_act,
				payment_type_id: payment_type_id,
				note: note,
				required_note: required_note,
				return_phone: return_phone,
				return_address: return_address,
				return_district_id: return_district_id,
				return_ward_code: return_ward_code,
				client_order_code: client_order_code,
				client_order_code_custom: client_order_code_custom,
				coupon: coupon,
				to_name: to_name,
				to_phone: to_phone,
				to_address: to_address,
				to_district_id: to_district_id,
				to_ward_code: to_ward_code,
				weight: weight,
				length: length,
				width: width,
				height: height,
				cod_amount: cod_amount,
				insurance_value: insurance_value,
				pick_station_id: pick_station_id,
				service_id: service_id,
				service_type_id: service_type_id,
				post_id: post_id,
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
	}
	
	function ghn_order_cancel(post_id) {		
		if (!confirm('Bạn có muốn huỷ đơn hàng?')) return;
				
		var toast = ghn_loading_msg('Đang xử lý đơn hàng'); // loading
		
		return $.ajax({
			url: ajaxurl,
			type: 'POST',
			dataType: 'json',
			data: {
				action: 'ghn_ajax_order_cancel',
				post_id: post_id,
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
	}
	
	function ghn_order_return(post_id) {
		if (!confirm('Bạn có muốn huỷ giao và chuyển hoàn đơn hàng?')) return;
				
		var toast = ghn_loading_msg('Đang xử lý đơn hàng'); // loading
		
		return $.ajax({
			url: ajaxurl,
			type: 'POST',
			dataType: 'json',
			data: {
				action: 'ghn_ajax_order_return',
				post_id: post_id,
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
	}
	
	function ghn_order_delivery_again(post_id) {
		if (!confirm('Bạn có muốn kích hoạt giao lại đơn hàng?')) return;
				
		var toast = ghn_loading_msg('Đang xử lý đơn hàng'); // loading
		
		return $.ajax({
			url: ajaxurl,
			type: 'POST',
			dataType: 'json',
			data: {
				action: 'ghn_ajax_order_delivery_again',
				post_id: post_id,
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
	}
	
	function ghn_print(post_id) {
		var toast = ghn_loading_msg('Đang xử lý đơn hàng'); // loading
		
		return $.ajax({
			url: ajaxurl,
			type: 'POST',
			dataType: 'json',
			data: {
				action: 'ghn_ajax_print',
				post_id: post_id,
			},
			success: function(ret) {
				if (ret.success) {
					var redirect_url = ret.redirect_url;
					
					ghn_loading_msg_close(toast); // end loading
					
					return window.open(redirect_url);
				} else {
					ghn_loading_msg_close(toast); // end loading
					
					return ghn_notice_msg(ret.message);
				}
			}
		});
	}
});