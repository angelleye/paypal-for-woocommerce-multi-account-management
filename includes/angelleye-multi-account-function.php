<?php

function angelleye_get_product_cat($categories) {
    $all_categories = array();
    if (!empty($categories)) {
        foreach ($categories as $key => $value) {
            $all_categories[] = $value;
            $term_object = get_term($value, 'product_cat');
            if (!empty($term_object->parent)) {
                $all_categories[] = $term_object->parent;
            }
        }
        if (!empty($all_categories)) {
            return $all_categories;
        }
    }
    return $categories;
}

function angelleye_get_closest_amount($array, $value) {
    $size = count($array);
    $index_key = 0;
    if ($size > 0) {
        $diff = abs($array[0]['woocommerce_paypal_express_api_condition_value'] - $value);
        $ret = $array[0]['woocommerce_paypal_express_api_condition_value'];
        $index_key = 0;
        for ($i = 1; $i < $size; $i++) {
            $temp = abs($array[$i]['woocommerce_paypal_express_api_condition_value'] - $value);
            if ($temp < $diff) {
                $diff = $temp;
                $ret = $array[$i]['woocommerce_paypal_express_api_condition_value'];
                $index_key = $i;
            }
        }
        return $array[$index_key];
    } else {
        return array();
    }
}

function angelleye_wc_gateway($gateway) {
    global $woocommerce;
    $gateways = $woocommerce->payment_gateways->payment_gateways();
    return isset($gateways[$gateway]) ? $gateways[$gateway] : array();
}

function angelleye_is_vendor_account_exist($vendor_id) {
    $args = array(
        'post_type' => 'microprocessing',
        'meta_query' => array(
            array(
                'key' => 'vendor_id',
                'value' => $vendor_id
            )
        ),
        'fields' => 'ids'
    );
    $query = new WP_Query($args);
    $duplicates = $query->posts;
    if (!empty($duplicates)) {
        if (!empty($query->posts[0])) {
            return $query->posts[0];
        }
    }
    return false;
}

function angelleye_get_user_multi_accounts($vendor_id) {
    $args = array(
        'post_type' => 'microprocessing',
        'meta_query' => array(
            array(
                'key' => 'woocommerce_paypal_express_api_user',
                'value' => $vendor_id
            )
        ),
        'fields' => 'ids'
    );
    $query = new WP_Query($args);
    $duplicates = $query->posts;
    if (!empty($duplicates)) {
        if (!empty($query->posts[0])) {
            return $query->posts;
        }
    }
    return false;
}

function angelleye_get_user_multi_accounts_by_paypal_email($email) {
    $args = array(
        'post_type' => 'microprocessing',
        'meta_query' => array(
            'relation' => 'OR',
            array(
                'key' => 'woocommerce_paypal_express_sandbox_email',
                'value' => $email
            ),
            array(
                'key' => 'woocommerce_paypal_express_email',
                'value' => $email
            )
        ),
        'fields' => 'ids'
    );
    $query = new WP_Query($args);
    $duplicates = $query->posts;
    if (!empty($duplicates)) {
        if (!empty($query->posts[0])) {
            return $query->posts;
        }
    }
    return false;
}

function angelleye_pfwma_log($message, $level = 'info') {
    if (function_exists('wc_get_logger')) {
        $log = wc_get_logger();
        $log->log($level, $message, array('source' => 'paypal-for-woocommerce-multi-account-management'));
    }
}

function angelleye_display_checkout_custom_field() {
    $woo_custome_fields = array();
    if (!function_exists('WC')) {
        return $woo_custome_fields;
    }
    $woo_checkout_default_fields = array(
        'billing' => array('billing_first_name', 'billing_last_name', 'billing_company', 'billing_address_1', 'billing_address_2', 'billing_city', 'billing_postcode', 'billing_country', 'billing_state', 'billing_email', 'billing_phone'),
        'shipping' => array('shipping_first_name', 'shipping_last_name', 'shipping_company', 'shipping_address_1', 'shipping_address_2', 'shipping_city', 'shipping_postcode', 'shipping_country', 'shipping_state'),
        'account' => array('account_username', 'account_password', 'account_password-2'),
        'order' => array('order_comments')
    );

    $checkout_fields = WC()->checkout->get_checkout_fields();
    $woo_custome_fields = array();

    /**
     * Adds isset() and is_array() to avoid errors when the data is not a clean array.
     * Resolves PFWMA-294
     * support@angelleye.com
     */
    foreach ($checkout_fields as $type => $checkout_field) {
        if (isset($woo_checkout_default_fields[$type]) && is_array($woo_checkout_default_fields[$type])) {
            foreach ($checkout_field as $key => $field) {
                if (!in_array($key, $woo_checkout_default_fields[$type])) {
                    $woo_custome_fields[$key] = $field;
                }
            }
        }
    }

    return $woo_custome_fields;
}

function angelleye_get_checkout_custom_field_keys() {
    $woo_custome_fields = array();
    if (!function_exists('WC')) {
        return $woo_custome_fields;
    }
    $woo_checkout_default_fields = array(
        'billing' => array('billing_first_name', 'billing_last_name', 'billing_company', 'billing_address_1', 'billing_address_2', 'billing_city', 'billing_postcode', 'billing_country', 'billing_state', 'billing_email', 'billing_phone'),
        'shipping' => array('shipping_first_name', 'shipping_last_name', 'shipping_company', 'shipping_address_1', 'shipping_address_2', 'shipping_city', 'shipping_postcode', 'shipping_country', 'shipping_state'),
        'account' => array('account_username', 'account_password', 'account_password-2'),
        'order' => array('order_comments')
    );

    /**
     * Adds isset() and is_array() to avoid errors when the data is not a clean array.
     * Resolves PFWMA-294
     * support@angelleye.com
     */
    
    $checkout_fields = WC()->checkout->get_checkout_fields();

    foreach ($checkout_fields as $type => $checkout_field) {
        if (isset($woo_checkout_default_fields[$type]) && is_array($woo_checkout_default_fields[$type])) {
            foreach ($checkout_field as $key => $field) {
                if (!in_array($key, $woo_checkout_default_fields[$type])) {
                    $woo_custome_fields[$key] = $field;
                }
            }
        }
    }
    return $woo_custome_fields;
}

if (!function_exists('angelleye_is_ppcp_third_party_enable')) {

    function angelleye_is_ppcp_third_party_enable($sandbox) {
        if (!class_exists('WC_Gateway_PPCP_AngellEYE_Settings')) {
            include_once PAYPAL_FOR_WOOCOMMERCE_PLUGIN_DIR . '/ppcp-gateway/class-wc-gateway-ppcp-angelleye-settings.php';
        }
        $settings = WC_Gateway_PPCP_AngellEYE_Settings::instance();
        if ($sandbox) {
            $sandbox_client_id = $settings->get('sandbox_client_id', '');
            $sandbox_secret_id = $settings->get('sandbox_api_secret', '');
            $sandbox_merchant_id = $settings->get('sandbox_merchant_id', '');
            if (!empty($sandbox_client_id) && !empty($sandbox_secret_id)) {
                return false;
            } else if (!empty($sandbox_merchant_id)) {
                return true;
            } else {
                return '';
            }
        } else {
            $live_client_id = $settings->get('api_client_id', '');
            $live_secret_id = $settings->get('api_secret', '');
            $live_merchant_id = $settings->get('merchant_id', '');
            if (!empty($live_client_id) && !empty($live_secret_id)) {
                return false;
            } else if (!empty($live_merchant_id)) {
                return true;
            } else {
                return '';
            }
        }
    }

}
