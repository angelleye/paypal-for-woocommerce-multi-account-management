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
    return $gateways[$gateway];
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