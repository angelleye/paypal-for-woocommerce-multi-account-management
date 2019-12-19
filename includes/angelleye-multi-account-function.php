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
