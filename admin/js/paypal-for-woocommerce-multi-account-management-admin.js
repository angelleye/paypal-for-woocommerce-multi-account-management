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
    jQuery(".angelleye_multi_account_left").block({message: null, overlayCSS: {background: "#fff", opacity: .6}});
    jQuery.ajax({
        type: 'POST',
        url: ajaxurl,
        data: {
            categories_list: jQuery('select#product_categories').val(),
            action: 'angelleye_get_product_tas_by_product_cat'
        },
        dataType: 'json',
        success: function (response) {
            jQuery(".angelleye_multi_account_left").unblock();
            if (response.success) {
                if (response.data.all_tags) {
                    jQuery('#product_tags').empty();
                    jQuery('#product_list').empty();
                    jQuery.each(response.data.all_tags, function (key, value) {
                        jQuery('#product_tags').append(jQuery("<option></option>").attr("value", key).text(value));
                    });
                }
            }
        }
    }).fail(function (response) {
        jQuery(".angelleye_multi_account_left").unblock();
        window.console.log(response);
    });



});

jQuery('#product_tags').change(function () {
    jQuery(".angelleye_multi_account_left").block({message: null, overlayCSS: {background: "#fff", opacity: .6}});
    jQuery.ajax({
        type: 'POST',
        url: ajaxurl,
        data: {
            tag_list: jQuery('select#product_tags').val(),
            action: 'angelleye_get_product_by_product_tags'
        },
        dataType: 'json',
        success: function (response) {
            jQuery(".angelleye_multi_account_left").unblock();
            if (response.success) {
                if (response.data.all_products) {
                    jQuery('#product_list').empty();
                    jQuery.each(response.data.all_products, function (key, value) {
                        jQuery('#product_list').append(jQuery("<option></option>").attr("value", key).text(value));
                    });
                }
            }
        }
    }).fail(function (response) {
        jQuery(".angelleye_multi_account_left").unblock();
        window.console.log(response);
    });
});