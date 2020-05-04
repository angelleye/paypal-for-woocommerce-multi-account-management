<?php

class Paypal_For_Woocommerce_Multi_Account_Management_List_Data extends Paypal_For_Woocommerce_Multi_Account_Management_WP_List_Table {

    var $account_data = array();

    function __construct() {
        global $status, $page;
        parent::__construct(array(
            'singular' => 'account',
            'plural' => 'accounts',
            'ajax' => false
        ));
    }

    function column_default($item, $column_name) {
        switch ($column_name) {
            case 'api_user_name':
            case 'mode':
                return $item[$column_name];
            case 'trigger_condition':
                $condition_field = get_post_meta($item['ID'], 'woocommerce_paypal_express_api_condition_field', true);
                $condition_sign = get_post_meta($item['ID'], 'woocommerce_paypal_express_api_condition_sign', true);
                $condition_value = get_post_meta($item['ID'], 'woocommerce_paypal_express_api_condition_value', true);
                $condition_role = get_post_meta($item['ID'], 'woocommerce_paypal_express_api_user_role', true);
                $condition_user = get_post_meta($item['ID'], 'woocommerce_paypal_express_api_user', true);
                
                $product_ids = get_post_meta($item['ID'], 'woocommerce_paypal_express_api_product_ids', true);
                
                $role = '';
                if ($condition_role) {
                    if ($condition_role != 'all') {
                        $role = '<p class="description">' . sprintf('When role is %s', $condition_role) . '</p>';
                    }
                }
                $user_info = '';
                if ($condition_user) {
                    if ($condition_user != 'all') {
                        $user_info = '<p class="description">' . sprintf('When User ID is %s', $condition_user) . '</p>';
                    }
                }
                $other_condition = '';
                $buyer_countries = get_post_meta($item['ID'], 'buyer_countries', true);
                if ($buyer_countries) {
                    if ($buyer_countries != 'all') {
                        $other_condition = '<p class="description">' . sprintf('When Buyer country is %s', implode(',', $buyer_countries)) . '</p>';
                    }
                }
                $store_countries = get_post_meta($item['ID'], 'store_countries', true);
                if ($store_countries) {
                    if ($store_countries != 'all') {
                        $other_condition .= '<p class="description">' . sprintf('When Store country is %s', $store_countries) . '</p>';
                    }
                }
                $currency_code = get_post_meta($item['ID'], 'currency_code', true);
                if ($currency_code) {
                    if ($currency_code != 'all') {
                        $other_condition .= '<p class="description">' . sprintf('When Currency Code is %s', $currency_code) . '</p>';
                    }
                }

                if ($condition_field == 'transaction_amount') {
                    $field = __('Transaction Amount', 'paypal-for-woocommerce-multi-account-management');
                } else {
                    $field = '';
                }
                if ($condition_sign == 'lessthan') {
                    $sign = '<';
                } else if ($condition_sign == 'greaterthan') {
                    $sign = '>';
                } else if ($condition_sign == 'equalto') {
                    $sign = '=';
                } else {
                    $sign = '';
                }

                add_thickbox();
                $product_text = '';
                if (!empty($product_ids)) {
                    $products = $product_ids;
                    $product_text .= '<a href="#TB_inline?width=600&height=550&inlineId=modal-window-' . esc_attr($item['ID']) . '" class="thickbox" title="Products added in Trigger Condition">Products</a>';
                    $product_text .= '<div id="modal-window-' . esc_attr($item['ID']) . '" style="display:none;">';
                    if (!empty($products)) {
                        foreach ($products as $product_id) {
                            $product = wc_get_product($product_id);
                            if (is_object($product)) {
                                $product_text .= '<p><a href="' . $product->get_permalink() . '" target="_blank">' . wp_kses_post($product->get_formatted_name()) . "</a></p>";
                            }
                        }
                    }
                    $product_text .= "</div>";
                }
                if ($currency_code != 'all') {
                    return "{$field} {$sign} " . wc_price($condition_value, array('currency' => $currency_code)) . " {$role}  {$user_info} {$other_condition} {$product_text}";
                } else {
                    return "{$field} {$sign} " . wc_price($condition_value) . " {$role} {$user_info} {$other_condition} {$product_text}";
                }
                
            case 'status':
                $status = get_post_meta($item['ID'], 'woocommerce_paypal_express_enable', true);
                $status_pf = get_post_meta($item['ID'], 'woocommerce_paypal_pro_payflow_enable', true);
                $status_pal = get_post_meta($item['ID'], 'woocommerce_paypal_enable', true);
                if ($status == 'on') {
                    return __('Enabled', 'paypal-for-woocommerce-multi-account-management');
                } else if ($status_pf == 'on') {
                    return __('Enabled', 'paypal-for-woocommerce-multi-account-management');
                } else if ($status_pal == 'on') {
                    return __('Enabled', 'paypal-for-woocommerce-multi-account-management');
                } else {
                    return __('Disabled', 'paypal-for-woocommerce-multi-account-management');
                }

            default:
                return print_r($item, true);
        }
    }

    function column_title($item) {
        $edit_params = array('page' => $_REQUEST['page'], 'action' => 'edit', 'ID' => $item['ID']);
        $delete_params = array('page' => $_REQUEST['page'], 'action' => 'delete', 'ID' => $item['ID']);
        $actions = array(
            'edit' => sprintf('<a href="%s">Edit</a>', esc_url(add_query_arg($edit_params))),
            'delete' => sprintf('<a href="%s">Delete</a>', esc_url(add_query_arg($delete_params))),
        );
        return sprintf('%1$s <span style="color:silver">(id:%2$s)</span>%3$s', $item['title'], $item['ID'], $this->row_actions($actions)
        );
    }

    function column_cb($item) {
        return sprintf(
                '<input type="checkbox" name="%1$s[]" value="%2$s" />', $this->_args['singular'], $item['ID']
        );
    }

    function get_columns() {
        $columns = array(
            'cb' => '<input type="checkbox" />',
            'title' => __('Account Name', 'paypal-for-woocommerce-multi-account-management'),
            'api_user_name' => __('API User Name', 'paypal-for-woocommerce-multi-account-management'),
            'trigger_condition' => __('Trigger Condition', 'paypal-for-woocommerce-multi-account-management'),
            'mode' => __('Sandbox/Live', 'paypal-for-woocommerce-multi-account-management'),
            'status' => __('Status', 'paypal-for-woocommerce-multi-account-management'),
        );
        return $columns;
    }

    function get_sortable_columns() {
        $sortable_columns = array(
            'title' => array('title', false),
            'api_user_name' => array('api_user_name', false),
            'mode' => array('mode', false)
        );
        return $sortable_columns;
    }

    function get_bulk_actions() {
        $actions = array(
            'delete' => __('Delete', 'paypal-for-woocommerce-multi-account-management')
        );
        return $actions;
    }

    function process_bulk_action() {
        if ('delete' === $this->current_action()) {
            if (!empty($_POST['account'])) {
                $account = $_POST['account'];
                foreach ($account as $key => $value) {
                    wp_delete_post($value, true);
                }
                $redirect_url = remove_query_arg(array('action', 'ID'));
                wp_redirect(add_query_arg('deleted', true, $redirect_url));
                exit();
            }
            if (!empty($_GET['action']) && 'delete' == $_GET['action'] && !empty($_GET['ID'])) {
                wp_delete_post($_GET['ID'], true);
                $redirect_url = remove_query_arg(array('action', 'ID'));
                wp_redirect(add_query_arg('deleted', true, $redirect_url));
                exit();
            }
        }
    }

    function prepare_items() {
        global $wpdb;
        $current_user_id = get_current_user_id();
        $angelleye_multi_account_item_per_page_default = 10;
        $angelleye_multi_account_item_per_page_value = get_user_meta($current_user_id, 'angelleye_multi_account_item_per_page', true);
        if ($angelleye_multi_account_item_per_page_value == false) {
            $angelleye_multi_account_item_per_page_value = $angelleye_multi_account_item_per_page_default;
        }
        $per_page = $angelleye_multi_account_item_per_page_value;
        $account_data = array();
        $columns = $this->get_columns();
        $hidden = array();
        $sortable = $this->get_sortable_columns();
        $this->_column_headers = array($columns, $hidden, $sortable);
        $this->process_bulk_action();
        $args = array(
            'post_type' => 'microprocessing',
            'numberposts' => -1,
            'order' => 'DESC',
        );
        if (isset($_REQUEST['orderby'])) {
            $args['orderby'] = $_REQUEST['orderby'];
        }
        if (isset($_REQUEST['order'])) {
            $args['order'] = $_REQUEST['order'];
        }
        $posts = get_posts($args);
        if (!empty($posts)) {
            foreach ($posts as $key => $value) {
                $account_data[$key]['ID'] = $value->ID;
                $meta_data = get_post_meta($value->ID);
                $meta_data['angelleye_multi_account_choose_payment_gateway'][0] = empty($meta_data['angelleye_multi_account_choose_payment_gateway'][0]) ? 'paypal_express' : $meta_data['angelleye_multi_account_choose_payment_gateway'][0];
                if (!empty($meta_data['angelleye_multi_account_choose_payment_gateway'][0]) && $meta_data['angelleye_multi_account_choose_payment_gateway'][0] == 'paypal_express') {
                    if (!empty($meta_data['woocommerce_paypal_express_testmode']) && $meta_data['woocommerce_paypal_express_testmode'][0] == 'on') {
                        $account_data[$key]['mode'] = 'Sandbox';
                    } else {
                        $account_data[$key]['mode'] = 'Live';
                    }
                    $account_data[$key]['title'] = !empty($meta_data['woocommerce_paypal_express_account_name'][0]) ? $meta_data['woocommerce_paypal_express_account_name'][0] : '';
                    if ($account_data[$key]['mode'] == 'Sandbox') {
                        $account_data[$key]['api_user_name'] = !empty($meta_data['woocommerce_paypal_express_sandbox_api_username'][0]) ? $meta_data['woocommerce_paypal_express_sandbox_api_username'][0] : '';
                        if (empty($account_data[$key]['api_user_name'])) {
                            $account_data[$key]['api_user_name'] = !empty($meta_data['woocommerce_paypal_express_sandbox_email'][0]) ? $meta_data['woocommerce_paypal_express_sandbox_email'][0] : '';
                        }
                    } else {
                        $account_data[$key]['api_user_name'] = !empty($meta_data['woocommerce_paypal_express_api_username'][0]) ? $meta_data['woocommerce_paypal_express_api_username'][0] : '';
                        if (empty($account_data[$key]['api_user_name'])) {
                            $account_data[$key]['api_user_name'] = !empty($meta_data['woocommerce_paypal_express_email'][0]) ? $meta_data['woocommerce_paypal_express_email'][0] : '';
                        }
                    }
                } else if (!empty($meta_data['angelleye_multi_account_choose_payment_gateway'][0]) && $meta_data['angelleye_multi_account_choose_payment_gateway'][0] == 'paypal_pro_payflow') {
                    if (!empty($meta_data['woocommerce_paypal_pro_payflow_testmode']) && $meta_data['woocommerce_paypal_pro_payflow_testmode'][0] == 'on') {
                        $account_data[$key]['mode'] = 'Sandbox';
                    } else {
                        $account_data[$key]['mode'] = 'Live';
                    }

                    $account_data[$key]['title'] = !empty($meta_data['woocommerce_paypal_pro_payflow_account_name'][0]) ? $meta_data['woocommerce_paypal_pro_payflow_account_name'][0] : '';
                    if ($account_data[$key]['mode'] == 'Sandbox') {
                        $account_data[$key]['api_user_name'] = !empty($meta_data['woocommerce_paypal_pro_payflow_sandbox_api_paypal_user'][0]) ? $meta_data['woocommerce_paypal_pro_payflow_sandbox_api_paypal_user'][0] : '';
                    } else {
                        $account_data[$key]['api_user_name'] = !empty($meta_data['woocommerce_paypal_pro_payflow_api_paypal_user'][0]) ? $meta_data['woocommerce_paypal_pro_payflow_api_paypal_user'][0] : '';
                    }
                } else {
                    if (!empty($meta_data['woocommerce_paypal_testmode']) && $meta_data['woocommerce_paypal_testmode'][0] == 'on') {
                        $account_data[$key]['mode'] = 'Sandbox';
                    } else {
                        $account_data[$key]['mode'] = 'Live';
                    }
                    $account_data[$key]['title'] = !empty($meta_data['woocommerce_paypal_account_name'][0]) ? $meta_data['woocommerce_paypal_account_name'][0] : '';
                    if ($account_data[$key]['mode'] == 'Sandbox') {
                        $account_data[$key]['api_user_name'] = !empty($meta_data['woocommerce_paypal_sandbox_api_username'][0]) ? $meta_data['woocommerce_paypal_sandbox_api_username'][0] : '';
                        if (empty($account_data[$key]['api_user_name'])) {
                            $account_data[$key]['api_user_name'] = !empty($meta_data['woocommerce_paypal_sandbox_email'][0]) ? $meta_data['woocommerce_paypal_sandbox_email'][0] : '';
                        }
                    } else {
                        $account_data[$key]['api_user_name'] = !empty($meta_data['woocommerce_paypal_api_username'][0]) ? $meta_data['woocommerce_paypal_api_username'][0] : '';
                        if (empty($account_data[$key]['api_user_name'])) {
                            $account_data[$key]['api_user_name'] = !empty($meta_data['woocommerce_paypal_email'][0]) ? $meta_data['woocommerce_paypal_email'][0] : '';
                        }
                    }
                }
            }
            $data = $account_data;
        } else {
            $data = $account_data;
        }

        function usort_reorder($a, $b) {
            $orderby = (!empty($_REQUEST['orderby'])) ? $_REQUEST['orderby'] : 'title';
            $order = (!empty($_REQUEST['order'])) ? $_REQUEST['order'] : 'asc';
            $result = strcmp($a[$orderby], $b[$orderby]);
            return ($order === 'asc') ? $result : -$result;
        }

        //usort($data, 'usort_reorder');
        $current_page = $this->get_pagenum();
        $total_items = count($data);
        $data = array_slice($data, (($current_page - 1) * $per_page), $per_page);
        $this->items = $data;
        $this->set_pagination_args(array(
            'total_items' => $total_items,
            'per_page' => $per_page,
            'total_pages' => ceil($total_items / $per_page)
        ));
    }

}
