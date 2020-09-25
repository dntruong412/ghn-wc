; (function($) {
    $('#billing_country_field').hide();

    $('#billing_district, #billing_ward').selectWoo();

    reloadDistricts();
    reloadWards();

    function reloadDistricts() {
        $.ajax({
            url: GHN.ajax_url,
            type: 'POST',
            dataType: 'json',
            data: {
                action: 'ghn_ajax_get_districts'
            },
            success: function(data) {
                $('#billing_district').empty().selectWoo({
                    placeholder: "Select a district",
                    data: data.data.filter(function(district) {
                        return !!district.text;
                    })
                })
                $('#billing_district').trigger('change');
            }
        });
    }

    function reloadWards() {
        $('#billing_district').on('change', function() {
            $.ajax({
                url: GHN.ajax_url,
                type: 'POST',
                dataType: 'json',
                data: {
                    action: 'ghn_ajax_get_wards',
                    district_id: $('#billing_district').val()
                },
                success: function(data) {
                    $('#billing_ward').empty().selectWoo({
                        placeholder: "Select a ward",
                        data: data.data.filter(function(ward) {
                            return !!ward.text;
                        })
                    });
                    $('#billing_ward').trigger('change');
                }
            });
        });

        $('#billing_ward').on('change', function() {
            reloadShippingFee();
        });
    }

    function reloadShippingFee() {
        if ($('#billing_district').val() == null || $('#billing_ward').val() == null) {
            return;
        }

        // reset address
        var district = $('#billing_district option:selected').text().split(' - ');
        var ward = $('#billing_ward option:selected').text();
        var address1Text = $('#billing_address_1_text').val();

        var to_district = parseInt($('#billing_district').val());
        var to_ward_code = $('#billing_ward').val();

        $('#billing_address_1').val(address1Text + ', ' + ward + ', ' + district.shift());
        $('#billing_city').val(district.join(' - '));

        var settingsServices = {
            "url": GHN.api_services,
            "method": "POST",
            "timeout": 0,
            "headers": {
                "Token": GHN.token,
                "Content-Type": "application/json"
            },
            "data": JSON.stringify({
                "shop_id": parseInt(GHN.shop_id),
                "from_district": parseInt(GHN.from_district),
                "to_district": to_district
            }),
        };

        $.ajax(settingsServices).done(function(servicesResponse) {
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
                        "shop_id": parseInt(GHN.shop_id),
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
                $.ajax(settings).done(function(servicesFeesResponse) {
                    validServices.push(Object.assign({}, servicesResponse.data[i], { data_fee: servicesFeesResponse.data }));
                });
            }
            $.ajax({
                url: wc_cart_fragments_params.ajax_url,
                type: 'POST',
                dataType: 'json',
                data: {
                    action: 'ghn_ajax_update_shipping_methods',
                    data: validServices
                }
            }).done(function() {
                $('body').trigger('update_checkout');
            });
        });
    }
})(jQuery);
