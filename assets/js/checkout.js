jQuery(document).ready(function($) {
    $('#billing_country_field').hide();

    $('#billing_district, #billing_ward').selectWoo();

    jQuery.ajax({
        url: wc_cart_fragments_params.ajax_url,
        type: 'POST',
        dataType: 'json',
        data: {
            action: 'ghn_ajax_get_districts'
        },
        success: function(data) {
            $('#billing_district').empty();
            $('#billing_district').selectWoo({
                placeholder: "Select a district",
                data: data.data
            })
            $('#billing_district').trigger('change');
            reloadWards($('#billing_district').val());
            $('#billing_district').on('change', function() {
                reloadWards($('#billing_district').val());
            });
        }
    });
});

function reloadWards(distictId) {
    jQuery.ajax({
        url: wc_cart_fragments_params.ajax_url,
        type: 'POST',
        dataType: 'json',
        data: {
            action: 'ghn_ajax_get_wards',
            district_id: distictId
        },
        success: function(data) {
            jQuery('#billing_ward').empty().trigger('change');
            jQuery('#billing_ward').selectWoo({
                placeholder: "Select a ward",
                data: data.data
            });
            reloadShippingFee();
        }
    });
}

function reloadShippingFee() {
    jQuery.ajax({
        url: wc_cart_fragments_params.ajax_url,
        type: 'POST',
        dataType: 'json',
        data: {
            action: 'ghn_ajax_get_servicefees',
            to_district_id: parseInt(jQuery('#billing_district').val()),
            to_ward_code: jQuery('#billing_ward').val(),
            insurance_value: 0,
            coupon: null,
            length: 10,
            width: 10,
            height: 10,
            weight: 1000,
        },
        success: function(response) {
            jQuery.ajax({
                url: wc_cart_fragments_params.ajax_url,
                type: 'POST',
                dataType: 'json',
                data: {
                    action: 'ghn_ajax_update_shipping_methods',
                    data: response.data
                }
            }).done(function() {
                jQuery('body').trigger('update_checkout');
            });
        }
    });

}
