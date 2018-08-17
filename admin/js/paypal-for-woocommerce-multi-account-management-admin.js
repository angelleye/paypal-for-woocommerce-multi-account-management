
jQuery('#woocommerce_paypal_express_testmode_microprocessing').change(function () {
    angelleye_multi_account_paypal_express_hide_show_field();
}).change();

jQuery('#woocommerce_paypal_pro_payflow_testmode_microprocessing').change(function () {
    angelleye_multi_account_paypal_payfow_hide_show_field();
}).change();
function angelleye_multi_account_paypal_express_hide_show_field() {
    var sandbox_ec = jQuery('#woocommerce_paypal_express_sandbox_api_username_microprocessing, #woocommerce_paypal_express_sandbox_api_password_microprocessing, #woocommerce_paypal_express_sandbox_api_signature_microprocessing').closest('tr');
    var production_ec = jQuery('#woocommerce_paypal_express_api_username_microprocessing, #woocommerce_paypal_express_api_password_microprocessing, #woocommerce_paypal_express_api_signature_microprocessing').closest('tr');
    if (jQuery('#woocommerce_paypal_express_testmode_microprocessing').is(':checked')) {
        console.log(16);
        sandbox_ec.show();
        production_ec.hide();
    } else {
        console.log(20);
        sandbox_ec.hide();
        production_ec.show();
    }
}
function angelleye_multi_account_paypal_payfow_hide_show_field() {
    var sandbox_pf = jQuery('#woocommerce_paypal_pro_payflow_sandbox_paypal_partner_microprocessing, #woocommerce_paypal_pro_payflow_sandbox_api_paypal_vendor_microprocessing, #woocommerce_paypal_pro_payflow_sandbox_api_paypal_user_microprocessing, #woocommerce_paypal_pro_payflow_sandbox_api_password_microprocessing').closest('tr');
    var production_pf = jQuery('#woocommerce_paypal_pro_payflow_paypal_partner_microprocessing, #woocommerce_paypal_pro_payflow_api_paypal_vendor_microprocessing, #woocommerce_paypal_pro_payflow_api_paypal_user_microprocessing, #woocommerce_paypal_pro_payflow_api_password_microprocessing').closest('tr');
    if (jQuery('#woocommerce_paypal_pro_payflow_testmode_microprocessing').is(':checked')) {
        console.log(30);
        sandbox_pf.show();
        production_pf.hide();
    } else {
        console.log(34);
        sandbox_pf.hide();
        production_pf.show();
    }
}

function angelleye_multi_account_choose_payment_hide_show_field() {
    if( jQuery('.angelleye_multi_account_choose_payment_gateway').val() === 'paypal_pro_payflow' ) {
        console.log(42);
        jQuery('.angelleye_multi_account_paypal_pro_payflow_field').show();
        jQuery('.angelleye_multi_account_paypal_express_field').hide();
    } else {
        console.log(46);
        jQuery('.angelleye_multi_account_paypal_express_field').show();
        jQuery('.angelleye_multi_account_paypal_pro_payflow_field').hide();
    }
}

jQuery('.angelleye_multi_account_choose_payment_gateway').change(function () {
    angelleye_multi_account_choose_payment_hide_show_field();
    if( jQuery('.angelleye_multi_account_choose_payment_gateway').val() === 'paypal_pro_payflow' ) {
        angelleye_multi_account_paypal_payfow_hide_show_field();
    } else {
        angelleye_multi_account_paypal_express_hide_show_field();
    }
}).change();


jQuery('#product_categories').change(function () {
    jQuery('#product_tags').empty();
    jQuery('#product_list').empty();
    jQuery(".angelleye_multi_account_left").block({message: null, overlayCSS: {background: "#fff", opacity: .6}});
    jQuery.ajax({
        type: 'POST',
        url: ajaxurl,
        data: {
            categories_list: jQuery('select#product_categories').val(),
            action: 'angelleye_get_product_tag_by_product_cat'
        },
        dataType: 'json',
        success: function (response) {
            jQuery(".angelleye_multi_account_left").unblock();
            if (response.success) {
                if (response.data.all_tags) {
                    jQuery.each(response.data.all_tags, function (key, value) {
                        jQuery('#product_tags').append(jQuery("<option></option>").attr("value", key).text(value));
                    });
                }
                if (response.data.all_products) {
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

jQuery('#product_tags').change(function () {
    jQuery('#product_list').empty();
    jQuery(".angelleye_multi_account_left").block({message: null, overlayCSS: {background: "#fff", opacity: .6}});
    jQuery.ajax({
        type: 'POST',
        url: ajaxurl,
        data: {
            tag_list: jQuery('select#product_tags').val(),
            categories_list: jQuery('select#product_categories').val(),
            action: 'angelleye_get_product_by_product_tags'
        },
        dataType: 'json',
        success: function (response) {
            jQuery(".angelleye_multi_account_left").unblock();
            if (response.success) {
                if (response.data.all_products) {
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