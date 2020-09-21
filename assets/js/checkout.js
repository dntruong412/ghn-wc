jQuery(document).ready(function($) {
    $('#billing_country_field').hide();

    $('#billing_district, #billing_ward').selectWoo();
    $('#billing_district').on('change', function() {
        reloadWards($(this).val());
    });

    jQuery.ajax({
        url: wc_cart_fragments_params.ajax_url,
        type: 'POST',
        dataType: 'json',
        data: {
            action: 'ghn_ajax_get_districts'
        },
        success: function (data) {
            $('#billing_district').empty();
            $('#billing_district').selectWoo({
                placeholder: "Select a district",
                data: data.data
            })
            $('#billing_district').trigger('change');
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
        success: function (data) {
            jQuery('#billing_ward').empty().trigger('change');
            jQuery('#billing_ward').selectWoo({
                placeholder: "Select a ward",
                data: data.data
            });
        }
    });
}