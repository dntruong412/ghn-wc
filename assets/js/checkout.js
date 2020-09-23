jQuery(document).ready(function($) {
    $('#billing_country_field').hide();

    $('#billing_district, #billing_ward').selectWoo();

    reloadDistricts();
    reloadWards();
});

function reloadDistricts() {
    jQuery.ajax({
        url: wc_cart_fragments_params.ajax_url,
        type: 'POST',
        dataType: 'json',
        data: {
            action: 'ghn_ajax_get_districts'
        },
        success: function(data) {
            jQuery('#billing_district').empty().selectWoo({
                placeholder: "Select a district",
                data: data.data
            })
            jQuery('#billing_district').trigger('change');
        }
    });
}

function reloadWards() {
    jQuery('#billing_district').on('change', function() {
        jQuery.ajax({
            url: wc_cart_fragments_params.ajax_url,
            type: 'POST',
            dataType: 'json',
            data: {
                action: 'ghn_ajax_get_wards',
                district_id: jQuery('#billing_district').val()
            },
            success: function(data) {
                jQuery('#billing_ward').empty().selectWoo({
                    placeholder: "Select a ward",
                    data: data.data
                });
                jQuery('#billing_ward').trigger('change');
            }
        });
    });

    jQuery('#billing_ward').on('change', function() {
        reloadShippingFee();
    });
}

function reloadShippingFee() {
    console.log(jQuery('#billing_district').val(), jQuery('#billing_ward').val());

    if (jQuery('#billing_district').val() == null || jQuery('#billing_ward').val() == null) {
        return;
    }

    // reset address
    var district = jQuery('#billing_district option:selected').text().split(' - ');
    var ward = jQuery('#billing_ward option:selected').text();
    var address1Text = jQuery('#billing_address_1_text').val();

    var to_district = parseInt(jQuery('#billing_district').val());
    var to_ward_code = jQuery('#billing_ward').val();

    jQuery('#billing_address_1').val(address1Text + ', ' + ward + ', ' + district.shift());
    jQuery('#billing_city').val(district.join(' - '));

    var settingsServices = {
        "url": GHN.api_services,
        "method": "POST",
        "timeout": 0,
        "headers": {
            "Token": GHN.token,
            "Content-Type": "application/json"
        },
        "data": JSON.stringify({
            "shop_id": GHN.shop_id,
            "from_district": parseInt(GHN.from_district),
            "to_district": to_district
        }),
    };

    jQuery.ajax(settingsServices).done(function(servicesResponse) {
        var validServices = [];
        for (var i = 0; i < servicesResponse.data.length; i++) {
            if (servicesResponse.data[i].service_type_id == 0) {
                continue;
            }
            var settings = {
                "url": GHN.api_services_fees,
                "method": "POST",
                "timeout": 0,
                "async": false,
                "headers": {
                    "Token": GHN.token,
                    "Content-Type": "application/json"
                },
                "data": JSON.stringify({
                    "shop_id": GHN.shop_id,
                    "service_id": servicesResponse.data[i].service_id,
                    "service_type_id": servicesResponse.data[i].service_type_id,
                    "to_district_id": to_district,
                    "to_ward_code": to_ward_code,
                    "height": 10,
                    "length": 10,
                    "weight": 1000,
                    "width": 10,
                    "insurance_fee": 0,
                    "coupon": null
                }),
            };
            jQuery.ajax(settings).done(function(servicesFeesResponse) {
                validServices.push(Object.assign({}, servicesResponse.data[i], { data_fee: servicesFeesResponse.data }));
            });
        }
        jQuery.ajax({
            url: wc_cart_fragments_params.ajax_url,
            type: 'POST',
            dataType: 'json',
            data: {
                action: 'ghn_ajax_update_shipping_methods',
                data: validServices
            }
        }).done(function() {
            jQuery('body').trigger('update_checkout');
        });
    });
}
