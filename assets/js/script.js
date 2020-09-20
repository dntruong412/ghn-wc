jQuery(document).ready(function($) {
	select_init();
	
	function select_init() {
		$('.ghn-select2').select2();
	}
	
	$('.select-ajax-district').on('change', function() {
		var container = $(this);
		var val = parseInt(container.val()) || 0; 
		var targetward = container.data('targetward');		
		var toast = ghn_loading_msg('Đang lấy dữ liệu Phường/Xã'); // loading
		
		return $.ajax({
			url: ajaxurl,
			type: 'POST',
			dataType: 'json',
			data: {
				action: 'ghn_ajax_get_wards',
				district_id: val,
			},
			success: function(ret) {
				if (ret.success) {
					var data = ret.data;
					
					$('[name='+targetward+']').select2('destroy');
					$('[name='+targetward+']').html('');					
					$('[name='+targetward+']').select2({
						data: data
					}).val('').trigger('change');
					
					return ghn_loading_msg_close(toast); // end loading
				} else {
					ghn_loading_msg_close(toast); // end loading
					
					return ghn_notice_msg('Không thể lấy dữ liệu Phường/Xã');
				}
			}
		});
	});
});

function ghn_loading_msg(msg) {
	var toast;
	
	if (typeof Toastify === 'function') { 
		toast = Toastify({
			text: msg,
			duration: 30000000,
			backgroundColor: '#F79719',
			className: 'info',
			gravity: 'bottom',
			className: 'ghn-loading-pop',
			close: false,
		});
		
		toast.showToast();
	}
	return toast;
}

function ghn_loading_msg_close(toast) {
	if (typeof Toastify === 'function' && toast) {
		return toast.hideToast();
	}
}

function ghn_notice_msg(msg) {
	var toast;
	
	if (typeof Toastify === 'function') { 
		toast = Toastify({
			text: msg,
			backgroundColor: '#F79719',
			className: 'info',
			gravity: 'bottom',
			className: 'ghn-notice-pop',
			close: false,
			closeOnClick: true,
			autoClose: 15000,
		});
		
		return toast.showToast();
	}
}