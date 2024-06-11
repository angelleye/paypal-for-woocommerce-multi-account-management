jQuery(function ($) {
    console.log(pfwma_param);
});

jQuery('#woocommerce_paypal_express_testmode_microprocessing').change(function () {
    angelleye_multi_account_paypal_express_hide_show_field();
}).change();
jQuery('#woocommerce_paypal_testmode_microprocessing').change(function () {
    angelleye_multi_account_paypal_hide_show_field();
}).change();
jQuery('#woocommerce_angelleye_ppcp_testmode_microprocessing').change(function () {
    angelleye_multi_account_angelleye_ppcp_hide_show_field();
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
function angelleye_multi_account_angelleye_ppcp_hide_show_field() {
    var sandbox_pal = jQuery('#woocommerce_angelleye_ppcp_sandbox_email_address, #woocommerce_angelleye_ppcp_sandbox_client_id, #woocommerce_angelleye_ppcp_sandbox_secret').closest('tr');
    var production_pal = jQuery('#woocommerce_angelleye_ppcp_email_address, #woocommerce_angelleye_ppcp_client_id, #woocommerce_angelleye_ppcp_secret').closest('tr');
    if (jQuery('#woocommerce_angelleye_ppcp_testmode_microprocessing').is(':checked')) {
        sandbox_pal.show();
        production_pal.hide();
    } else {
        sandbox_pal.hide();
        production_pal.show();
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
    if (jQuery('.angelleye_multi_account_choose_payment_gateway').val() === 'paypal_pro_payflow') {
        jQuery('.angelleye_multi_account_paypal_pro_payflow_field').show();
        jQuery('.angelleye_multi_account_paypal_express_field').hide();
        jQuery('.angelleye_multi_account_paypal_field').hide();
        jQuery('.angelleye_multi_account_angelleye_ppcp_field').hide();
    } else if (jQuery('.angelleye_multi_account_choose_payment_gateway').val() === 'paypal_express') {
        jQuery('.angelleye_multi_account_paypal_express_field').show();
        jQuery('.angelleye_multi_account_paypal_pro_payflow_field').hide();
        jQuery('.angelleye_multi_account_paypal_field').hide();
        jQuery('.angelleye_multi_account_angelleye_ppcp_field').hide();
    } else if (jQuery('.angelleye_multi_account_choose_payment_gateway').val() === 'paypal') {
        jQuery('.angelleye_multi_account_paypal_express_field').hide();
        jQuery('.angelleye_multi_account_paypal_pro_payflow_field').hide();
        jQuery('.angelleye_multi_account_paypal_field').show();
        jQuery('.angelleye_multi_account_angelleye_ppcp_field').hide();
    } else if (jQuery('.angelleye_multi_account_choose_payment_gateway').val() === 'angelleye_ppcp') {
        jQuery('.angelleye_multi_account_paypal_express_field').hide();
        jQuery('.angelleye_multi_account_paypal_pro_payflow_field').hide();
        jQuery('.angelleye_multi_account_paypal_field').hide();
        jQuery('.angelleye_multi_account_angelleye_ppcp_field').show();
        jQuery('#woocommerce_angelleye_ppcp_always_trigger').change();
    }
}

jQuery('#angelleye_payment_load_balancer').change(function () {
    if (jQuery(this).is(':checked')) {
        jQuery('.global_ec_site_owner_commission_label_tr, .global_ec_site_owner_commission_tr, .global_ec_include_tax_shipping_in_commission_tr, .angelleye_smart_commission_tr, .angelleye_smart_commission_tt').hide();
    } else {
        jQuery('.angelleye_smart_commission_tr, .global_ec_include_tax_shipping_in_commission_tr').show();
        if (jQuery('#angelleye_smart_commission').is(':checked')) {
            jQuery('.global_ec_site_owner_commission_label_tr, .global_ec_site_owner_commission_tr').hide();
            jQuery('.angelleye_smart_commission_tt').show();
        } else {
            jQuery('.global_ec_site_owner_commission_label_tr, .global_ec_site_owner_commission_tr').show();
            jQuery('.angelleye_smart_commission_tt').hide();
        }
    }
}).change();
jQuery('#angelleye_smart_commission').change(function () {
    if (jQuery('#angelleye_payment_load_balancer').is(':checked')) {
        jQuery('.global_ec_site_owner_commission_label_tr, .global_ec_site_owner_commission_tr, .global_ec_include_tax_shipping_in_commission_tr, .angelleye_smart_commission_tr, .angelleye_smart_commission_tt').hide();
    } else {
        if (jQuery(this).is(':checked')) {
            jQuery('.global_ec_site_owner_commission_label_tr, .global_ec_site_owner_commission_tr').hide();
            jQuery('.angelleye_smart_commission_tt').show();
        } else {
            jQuery('.global_ec_site_owner_commission_label_tr, .global_ec_site_owner_commission_tr').show();
            jQuery('.angelleye_smart_commission_tt').hide();
        }
    }
}).change();
jQuery(document).on('click', 'td a.angelleye_smart_commission_delete', function () {
    if (!confirm("Do you want to delete?")) {
        return false;
    } else {
        jQuery(this).closest("tr").remove();
    }
});
jQuery('.angelleye_multi_account_choose_payment_gateway').change(function () {
    angelleye_multi_account_choose_payment_hide_show_field();
    if (jQuery('.angelleye_multi_account_choose_payment_gateway').val() === 'paypal_pro_payflow') {
        angelleye_multi_account_paypal_payfow_hide_show_field();
    } else if (jQuery('.angelleye_multi_account_choose_payment_gateway').val() === 'paypal_express') {
        angelleye_multi_account_paypal_express_hide_show_field();
    } else if (jQuery('.angelleye_multi_account_choose_payment_gateway').val() === 'paypal') {
        angelleye_multi_account_paypal_hide_show_field();
    } else if(jQuery('.angelleye_multi_account_choose_payment_gateway').val() === 'angelleye_ppcp') {
        angelleye_multi_account_angelleye_ppcp_hide_show_field();
    }
}).change();


jQuery('#woocommerce_paypal_express_api_user, #pfwst_shipping_class').change(function () {
    jQuery('#product_categories').val('').trigger('change');
    jQuery('#product_list').val('').trigger('change');
    jQuery('#product_tags').val('').trigger('change');
    jQuery('#product_list').val('').trigger('change');
});


jQuery('#woocommerce_paypal_express_always_trigger').change(function () {
    if (jQuery(this).is(':checked')) {
        jQuery('.trigger_conditions_fields').hide();
        jQuery('.site_owner_commission_field').hide();
        jQuery("#always_trigger_commission_microprocessing").prop('required', true);
        jQuery("#always_trigger_commission_item_label_microprocessing").prop('required', true);
        jQuery('.paypal_express_always_trigger_commission_field').show();
    } else {
        jQuery('.trigger_conditions_fields').show();
        if (jQuery('.angelleye_multi_account_choose_payment_gateway').val() === 'paypal_express') {
            jQuery('.site_owner_commission_field').show();
        }
        jQuery('.paypal_express_always_trigger_commission_field').hide();
        jQuery("#always_trigger_commission_microprocessing").prop('required', false);
        jQuery("#always_trigger_commission_item_label_microprocessing").prop('required', false);
    }
}).change();


jQuery('#woocommerce_angelleye_ppcp_always_trigger').change(function () {
    if (jQuery(this).is(':checked')) {
        jQuery('.trigger_conditions_fields').hide();
        jQuery('.ppcp_site_owner_commission_field').hide();
        jQuery("#ppcp_always_trigger_commission").prop('required',true);
        jQuery("#ppcp_always_trigger_commission_item_label").prop('required',true);
        jQuery('.angelleye_ppcp_always_trigger_commission_field').show();
    } else {
        jQuery('.trigger_conditions_fields').show();
        jQuery('.ppcp_site_owner_commission_field').show();
        jQuery('.angelleye_ppcp_always_trigger_commission_field').hide();
        jQuery("#ppcp_always_trigger_commission").prop('required',false);
        jQuery("#ppcp_always_trigger_commission_item_label").prop('required',false);
    }
}).change();



jQuery('#product_categories').on('select2:unselect', function (e) {
    jQuery('#product_tags').val('').trigger('change');
    jQuery('#product_list').val('').trigger('change');
});
jQuery('#buyer_countries').on('change', function (e) {
    jQuery('#buyer_states').empty();
    jQuery('#buyer_states').val('').trigger('change');
    var data = {
        'action': 'angelleye_pfwma_get_buyer_states',
        'country_list': jQuery('#buyer_countries').val(),
    };
    jQuery.post(ajaxurl, data, function (response) {
        if ('failed' !== response) {
            jQuery.each(response, function (key, value) {
                jQuery('#buyer_states').append('<option value=' + key + '>' + value + '</option>');
            });
            jQuery('#buyer_states').trigger('change');
        }
    });
});

jQuery('#product_tags').on('select2:unselect', function (e) {
    jQuery('#product_list').val('').trigger('change');
});


jQuery(function () {
    jQuery('[id^=angelleye_notification]').each(function (i) {
        jQuery('[id="' + this.id + '"]').slice(1).remove();
    });
    var el_notice = jQuery(".angelleye-notice");
    el_notice.fadeIn(750);
    jQuery(".angelleye-notice-dismiss").click(function (e) {
        e.preventDefault();
        jQuery(this).parent().parent(".angelleye-notice").fadeOut(600, function () {
            jQuery(this).parent().parent(".angelleye-notice").remove();
        });
        notify_wordpress(jQuery(this).data("msg"));
    });
    function notify_wordpress(message) {
        var param = {
            action: 'angelleye_paypal_for_woocommerce_multi_account_adismiss_notice',
            data: message
        };
        jQuery.post(ajaxurl, param);
    }
});
jQuery(document).off('click', '#angelleye-updater-notice .notice-dismiss').on('click', '#angelleye-updater-notice .notice-dismiss', function (event) {
    var r = confirm("If you do not install the Updater plugin you will not receive automated updates for Angell EYE products going forward!");
    if (r == true) {
        var data = {
            action: 'angelleye_updater_dismissible_admin_notice'
        };
        jQuery.post(ajaxurl, data, function (response) {
            var $el = jQuery('#angelleye-updater-notice');
            event.preventDefault();
            $el.fadeTo(100, 0, function () {
                $el.slideUp(100, function () {
                    $el.remove();
                });
            });
        });
    }
});
jQuery('.disable_all_vendor_rules').on('click', function (event) {
    var r = confirm(pfwma_param.disable_all_vendor_rules_alert_message);
    if (r == true) {
        jQuery(".disable_all_vendor_rules").block({message: null, overlayCSS: {background: "#fff", opacity: .6}});
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
jQuery("#angelleye_multi_account_global_setting").submit(function (event) {
    window.onbeforeunload = null;
    jQuery('angelleye_multi_account_global_setting').submit();
});
jQuery("#angelleye_multi_account").submit(function (event) {
    window.onbeforeunload = null;
    if (jQuery("#is_force_submit").val() === 'yes') {
        return true;
    }
    if (pfwma_param.is_angelleye_payment_load_balancer_enable === 'no') {
        var total_not_empty_fields = 0;
        var paypal_express_field_names = ["woocommerce_priority", "woocommerce_paypal_express_api_user_role", "woocommerce_paypal_express_api_user", "buyer_countries", "buyer_states", "store_countries", "pfwst_shipping_class", "product_categories", "product_tags", "product_list", "woocommerce_paypal_express_api_condition_value", "currency_code", "card_type", "postcode"];
        jQuery.each(paypal_express_field_names, function (i, name) {
            if (jQuery('#' + name).val() !== '' && jQuery('#' + name).val() !== 'all' && jQuery('#' + name).val() !== '0' && jQuery('#' + name).val() !== null && jQuery('#' + name).val() !== undefined) {
                total_not_empty_fields = total_not_empty_fields + 1;
            }
        });
        jQuery.each(pfwma_param.custom_fields, function (i, name) {
            if (name === 'checkbox' || name === 'radio') {
                if (jQuery("[name=" + i + "]").is(":checked")) {
                    total_not_empty_fields = total_not_empty_fields + 1;
                }
            } else {
                if (jQuery("[name=" + i + "]").val() !== '') {
                    total_not_empty_fields = total_not_empty_fields + 1;
                }
            }
        });
        if (total_not_empty_fields === 0) {
            event.preventDefault();
            var r = confirm(pfwma_param.rule_with_no_condition_set_message);
            if (r === true) {
                var force_submit_flag = jQuery("<input>").attr("type", "hidden").attr("name", "microprocessing_save").attr("id", "is_force_submit").val("yes");
                jQuery("#angelleye_multi_account").append(force_submit_flag);
                jQuery("#angelleye_multi_account").submit();
            }
        }
    }

});

jQuery('.enable_all_vendor_rules').on('click', function (event) {
    var r = confirm(pfwma_param.enable_all_vendor_rules_alert_message);
    if (r == true) {
        jQuery(".enable_all_vendor_rules").block({message: null, overlayCSS: {background: "#fff", opacity: .6}});
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
jQuery('.create_all_vendor_rules').on('click', function (event) {
    var r = confirm(pfwma_param.create_all_vendor_rules_alert_message);
    if (r == true) {
        jQuery(".create_all_vendor_rules").block({message: null, overlayCSS: {background: "#fff", opacity: .6}});
        var data = {
            'action': 'pfwma_create_all_vendor_rules'
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
jQuery('.angelleye_add_new_smart_commission_role').on('click', function (event) {
    event.preventDefault();
    var $tableBody = jQuery('#angelleye_smart_commission_table').find("tbody"),
            $trLast = $tableBody.find("tr:last"),
            $trNew = $trLast.clone();
    $trNew.find('input').val('');
    $trNew.find("option").prop("selected", false).trigger("change");
    $trLast.after($trNew);
});
