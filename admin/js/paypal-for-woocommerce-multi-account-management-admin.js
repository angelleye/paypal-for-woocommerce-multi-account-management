jQuery('#woocommerce_paypal_express_testmode_microprocessing').change(function () {
    var sandbox = jQuery('#woocommerce_paypal_express_sandbox_api_username_microprocessing, #woocommerce_paypal_express_sandbox_api_password_microprocessing, #woocommerce_paypal_express_sandbox_api_signature_microprocessing').closest('tr');
    var production = jQuery('#woocommerce_paypal_express_api_username_microprocessing, #woocommerce_paypal_express_api_password_microprocessing, #woocommerce_paypal_express_api_signature_microprocessing').closest('tr');
    if (jQuery(this).is(':checked')) {
        sandbox.show();
        production.hide();
    } else {
        sandbox.hide();
        production.show();
    }
}).change();

jQuery('#product_categories').change(function () {
    var data = {
        'action': 'angelleye_get_product_tas_by_product_cat',
        'categories_list': jQuery('select#product_categories').val()

    };
    jQuery.post(ajaxurl, data, function (response) {
        if ('failed' !== response) {
            console.log();
            jQuery('#product_tags').empty();
            jQuery.each(response.data.all_tags, function (key, value) {
                jQuery('#product_tags').append(jQuery("<option></option>").attr("value", key).text(value));
            });
        } else {
            return false;
        }
    });
}).change();

jQuery('#product_tags').change(function () {
    jQuery.ajax({
        type: 'POST',
        url: ajaxurl,
        data: {
            categories_list: jQuery('select#product_categories').val(),
            action: 'angelleye_get_product_by_product_tags'
        },
        dataType: 'json',
        success: function (response) {
            if (response.success) {
                if (response.data.all_tags) {
                    jQuery('#product_tags').empty();
                    jQuery.each(response.data.all_tags, function (key, value) {
                        jQuery('#product_tags').append(jQuery("<option></option>").attr("value", key).text(value));
                    });
                }
            }
        }
    }).fail(function (response) {
        window.console.log(response);
    });
});