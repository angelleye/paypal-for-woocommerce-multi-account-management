
jQuery('#woocommerce_paypal_express_testmode_microprocessing').change(function () {
    angelleye_multi_account_paypal_express_hide_show_field();
}).change();
jQuery('#woocommerce_paypal_testmode_microprocessing').change(function () {
    angelleye_multi_account_paypal_hide_show_field();
}).change();
jQuery('#woocommerce_paypal_pro_payflow_testmode_microprocessing').change(function () {
    angelleye_multi_account_paypal_payfow_hide_show_field();
}).change();

function angelleye_multi_account_paypal_express_hide_show_field() {
    var sandbox_ec = jQuery('#woocommerce_paypal_express_sandbox_email_microprocessing, #woocommerce_paypal_express_sandbox_api_username_microprocessing, #woocommerce_paypal_express_sandbox_api_password_microprocessing, #woocommerce_paypal_express_sandbox_api_signature_microprocessing, #woocommerce_paypal_express_sandbox_merchant_id_microprocessing').closest('tr');
    var production_ec = jQuery('#woocommerce_paypal_express_email_microprocessing, #woocommerce_paypal_express_api_username_microprocessing, #woocommerce_paypal_express_api_password_microprocessing, #woocommerce_paypal_express_api_signature_microprocessing, #woocommerce_paypal_express_merchant_id_microprocessing').closest('tr');
    if (jQuery('#woocommerce_paypal_express_testmode_microprocessing').is(':checked')) {
        sandbox_ec.show();
        production_ec.hide();
    } else {
        sandbox_ec.hide();
        production_ec.show();
    }
}
function angelleye_multi_account_paypal_hide_show_field() {
    var sandbox_pal = jQuery('#woocommerce_paypal_sandbox_email_microprocessing, #woocommerce_paypal_sandbox_api_username_microprocessing, #woocommerce_paypal_sandbox_api_password_microprocessing, #woocommerce_paypal_sandbox_api_signature_microprocessing').closest('tr');
    var production_pal = jQuery('#woocommerce_paypal_email_microprocessing, #woocommerce_paypal_api_username_microprocessing, #woocommerce_paypal_api_password_microprocessing, #woocommerce_paypal_api_signature_microprocessing').closest('tr');
    if (jQuery('#woocommerce_paypal_testmode_microprocessing').is(':checked')) {
        sandbox_pal.show();
        production_pal.hide();
    } else {
        sandbox_pal.hide();
        production_pal.show();
    }
}
function angelleye_multi_account_paypal_payfow_hide_show_field() {
    var sandbox_pf = jQuery('#woocommerce_paypal_pro_payflow_sandbox_paypal_partner_microprocessing, #woocommerce_paypal_pro_payflow_sandbox_api_paypal_vendor_microprocessing, #woocommerce_paypal_pro_payflow_sandbox_api_paypal_user_microprocessing, #woocommerce_paypal_pro_payflow_sandbox_api_password_microprocessing').closest('tr');
    var production_pf = jQuery('#woocommerce_paypal_pro_payflow_paypal_partner_microprocessing, #woocommerce_paypal_pro_payflow_api_paypal_vendor_microprocessing, #woocommerce_paypal_pro_payflow_api_paypal_user_microprocessing, #woocommerce_paypal_pro_payflow_api_password_microprocessing').closest('tr');
    if (jQuery('#woocommerce_paypal_pro_payflow_testmode_microprocessing').is(':checked')) {
        sandbox_pf.show();
        production_pf.hide();
    } else {
        sandbox_pf.hide();
        production_pf.show();
    }
}

function angelleye_multi_account_choose_payment_hide_show_field() {
    if( jQuery('.angelleye_multi_account_choose_payment_gateway').val() === 'paypal_pro_payflow' ) {
        jQuery('.angelleye_multi_account_paypal_pro_payflow_field').show();
        jQuery('.angelleye_multi_account_paypal_express_field').hide();
        jQuery('.angelleye_multi_account_paypal_field').hide();
    } else if( jQuery('.angelleye_multi_account_choose_payment_gateway').val() === 'paypal_express' ) {
        jQuery('.angelleye_multi_account_paypal_express_field').show();
        jQuery('.angelleye_multi_account_paypal_pro_payflow_field').hide();
        jQuery('.angelleye_multi_account_paypal_field').hide();
    } else {
        jQuery('.angelleye_multi_account_paypal_express_field').hide();
        jQuery('.angelleye_multi_account_paypal_pro_payflow_field').hide();
        jQuery('.angelleye_multi_account_paypal_field').show();
    }
}

jQuery('#angelleye_payment_load_balancer').change(function () {
    if (jQuery(this).is(':checked')) {
        jQuery('.angelleye_multi_account_paypal_express_field').hide();
    } else {
       jQuery('.angelleye_multi_account_paypal_express_field').show();
    }
}).change();

jQuery('.angelleye_multi_account_choose_payment_gateway').change(function () {
    angelleye_multi_account_choose_payment_hide_show_field();
    if( jQuery('.angelleye_multi_account_choose_payment_gateway').val() === 'paypal_pro_payflow' ) {
        angelleye_multi_account_paypal_payfow_hide_show_field();
    } else if( jQuery('.angelleye_multi_account_choose_payment_gateway').val() === 'paypal_express' ) {
        angelleye_multi_account_paypal_express_hide_show_field();
    } else {
        angelleye_multi_account_paypal_hide_show_field();
    }
}).change();


jQuery('#pfwst_author, #pfwst_shipping_class').change(function () {
    jQuery('#product_list').empty();
    jQuery('#product_tags').change();
});
jQuery('#product_categories').change(function () {
    jQuery('#product_tags').empty();
    jQuery('#product_list').empty();
    jQuery(".angelleye_multi_account_left").block({message: null, overlayCSS: {background: "#fff", opacity: .6}});
    jQuery.ajax({
        type: 'POST',
        url: ajaxurl,
        data: {
            categories_list: jQuery('select#product_categories').val(),
            author : jQuery('select#pfwst_author').val(),
            shipping_class : jQuery('select#pfwst_shipping_class').val(),
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
            shipping_class : jQuery('select#pfwst_shipping_class').val(),
            author : jQuery('select#pfwst_author').val(),
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

jQuery(function () {
    jQuery('[id^=angelleye_notification]').each(function (i) {
        jQuery('[id="' + this.id + '"]').slice(1).remove();
    });
    var el_notice = jQuery(".angelleye-notice");
    el_notice.fadeIn(750);
    jQuery(".angelleye-notice-dismiss").click(function(e){
        e.preventDefault();
        jQuery( this ).parent().parent(".angelleye-notice").fadeOut(600, function () {
            jQuery( this ).parent().parent(".angelleye-notice").remove();
        });
        notify_wordpress(jQuery( this ).data("msg"));
    });
    function notify_wordpress(message) {
        var param = {
            action: 'angelleye_paypal_for_woocommerce_multi_account_adismiss_notice',
            data: message
        };
        jQuery.post(ajaxurl, param);
    }
});
jQuery(document).off('click', '#angelleye-updater-notice .notice-dismiss').on('click', '#angelleye-updater-notice .notice-dismiss',function(event) {
    var r = confirm("If you do not install the Updater plugin you will not receive automated updates for Angell EYE products going forward!");
    if (r == true) {
        var data = {
            action : 'angelleye_updater_dismissible_admin_notice'
        };
        jQuery.post(ajaxurl, data, function (response) {
            var $el = jQuery( '#angelleye-updater-notice' );
            event.preventDefault();
            $el.fadeTo( 100, 0, function() {
                    $el.slideUp( 100, function() {
                            $el.remove();
                    });
            });
        });
    } 
});
jQuery('.disable_all_vendor_rules').on('click', function (event) {
        var r = confirm(pfwma_param.disable_all_vendor_rules_alert_message);
        if (r == true) {
            jQuery(".disable_all_vendor_rules").block({message:null,overlayCSS:{background:"#fff",opacity:.6}});
            var data = {
                'action': 'pfwma_disable_all_vendor_rules'
            };
            jQuery.post(ajaxurl, data, function (response) {
                if ('failed' !== response)
                {
                    var redirectUrl = response;
                    top.location.replace(redirectUrl);
                    return true;
                } else
                {
                    alert('Error updating records.');
                    return false;
                }
            });
        } else {
            event.preventDefault();
            return r;
        }
});
jQuery('.enable_all_vendor_rules').on('click', function (event) {
        var r = confirm(pfwma_param.enable_all_vendor_rules_alert_message);
        if (r == true) {
            jQuery(".enable_all_vendor_rules").block({message:null,overlayCSS:{background:"#fff",opacity:.6}});
            var data = {
                'action': 'pfwma_enable_all_vendor_rules'
            };
            jQuery.post(ajaxurl, data, function (response) {
                if ('failed' !== response)
                {
                    var redirectUrl = response;
                    top.location.replace(redirectUrl);
                    return true;
                } else
                {
                    alert('Error updating records.');
                    return false;
                }
            });
        } else {
            event.preventDefault();
            return r;
        }
});
