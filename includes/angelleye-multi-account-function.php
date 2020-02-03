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
