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
                        $user = get_user_by('id', $condition_user);
                        if (isset($user->ID) && !empty($user->ID)) {
                            $user_string = sprintf(
                                    esc_html__('%1$s (#%2$s   %3$s)', 'woocommerce'),
                                    $user->display_name,
                                    absint($user->ID),
                                    $user->user_email
                            );
                            $user_info = '<p class="description">' . sprintf('When Author is %s', $user_string) . '</p>';
                        }
                    }
                }
                $other_condition = '';
                $buyer_countries = get_post_meta($item['ID'], 'buyer_countries', true);
                if ($buyer_countries) {
                    if ($buyer_countries != 'all') {
                        $other_condition = '<p class="description">' . sprintf('When Buyer country is %s', implode(',', $buyer_countries)) . '</p>';
                    }
                }
                $buyer_states = get_post_meta($item['ID'], 'buyer_states', true);
                if ($buyer_states) {
                    if ($buyer_states != 'all') {
                        $other_condition .= '<p class="description">' . sprintf('When Buyer state is %s', implode(',', $buyer_states)) . '</p>';
                    }
                }
                $postcode = get_post_meta($item['ID'], 'postcode', true);
                if (!empty($postcode)) {
                    $other_condition .= '<p class="description">' . sprintf('When Buyer Postal/Zip Code %s', $postcode) . '</p>';
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
                if (!empty($product_ids) && is_array($product_ids)) {
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
                $status_ppcp = get_post_meta($item['ID'], 'woocommerce_angelleye_ppcp_enable', true);
                if ($status == 'on') {
                    return __('Enabled', 'paypal-for-woocommerce-multi-account-management');
                } else if ($status_pf == 'on') {
                    return __('Enabled', 'paypal-for-woocommerce-multi-account-management');
                } else if ($status_pal == 'on') {
                    return __('Enabled', 'paypal-for-woocommerce-multi-account-management');
                } else if ($status_ppcp == 'on') {
                    $ppcp_testmode = get_post_meta($item['ID'], 'woocommerce_angelleye_ppcp_testmode', true);
                    if (!empty($ppcp_testmode) && $ppcp_testmode === 'on') {
                        $sandbox = true;
                    } else {
                        $sandbox = false;
                    }
                    if ($sandbox) {
                        $board_status = get_post_meta($item['ID'], 'woocommerce_angelleye_ppcp_multi_account_on_board_status_sandbox', true);
                    } else {
                        $board_status = get_post_meta($item['ID'], 'woocommerce_angelleye_ppcp_multi_account_on_board_status_live', true);
                    }
                    if (empty($board_status)) {
                        return __('Invitation Sent (Pending)', 'paypal-for-woocommerce-multi-account-management');
                    } else {
                        return __('Enabled', 'paypal-for-woocommerce-multi-account-management');
                    }
                    
                } else {
                    return __('Disabled', 'paypal-for-woocommerce-multi-account-management');
                }

            default:
                return print_r($item, true);
        }
    }

    function column_title($item) {
        $edit_params = array('page' => 'wc-settings', 'tab' => 'multi_account_management', 'section' => 'add_edit_account', 'action' => 'edit', 'ID' => $item['ID']);
        $delete_params = array('page' => 'wc-settings', 'tab' => 'multi_account_management', 'action' => 'delete', 'ID' => $item['ID']);
        $actions = array(
            'edit' => sprintf('<a href="%s">Edit</a>', esc_url(add_query_arg($edit_params, admin_url('admin.php')))),
            'delete' => sprintf('<a href="%s">Delete</a>', esc_url(add_query_arg($delete_params, admin_url('admin.php')))),
        );
        $trash_params = array('page' => 'wc-settings', 'tab' => 'multi_account_management', 'action' => 'trash', 'ID' => $item['ID']);
        $restore_params = array('page' => 'wc-settings', 'tab' => 'multi_account_management', 'action' => 'restore', 'ID' => $item['ID']);

        if ($this->isTrashedView()) {
            $actions = array(
                'edit' => sprintf('<a href="%s">Edit</a>', esc_url(add_query_arg($edit_params, admin_url('admin.php')))),
                'restore' => sprintf('<a href="%s">Restore</a>', esc_url(add_query_arg($restore_params, admin_url('admin.php')))),
                'delete' => sprintf('<a href="%s">Delete</a>', esc_url(add_query_arg($delete_params, admin_url('admin.php'))))
            );
        } else {
            $actions = array(
                'edit' => sprintf('<a href="%s">Edit</a>', esc_url(add_query_arg($edit_params, admin_url('admin.php')))),
                'trash' => sprintf('<a href="%s">Trash</a>', esc_url(add_query_arg($trash_params, admin_url('admin.php')))),
            );
        }
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
            'api_user_name' => __('PayPal Account Email', 'paypal-for-woocommerce-multi-account-management'),
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
        if ($this->isTrashedView()) {
            $actions = array(
                'delete' => __('Delete', 'paypal-for-woocommerce-multi-account-management')
            );
        } else {
            $actions = array(
                'trash' => __('Trash', 'paypal-for-woocommerce-multi-account-management')
            );
        }
        return $actions;
    }

    private function trashAccount($postId) {
        wp_trash_post($postId);
    }

    private function deleteAccount($postId) {
        wp_delete_post($postId, true);
    }

    private function unTrashPost($postId) {
        wp_publish_post($postId);
    }

    function process_bulk_action() {
        $deleteRedirect = function ($action = 'deleted') {
            do_action('update_angelleye_multi_account');
            $redirect_url = remove_query_arg(array('action', 'ID'));
            wp_redirect(add_query_arg($action, true, $redirect_url));
            switch ($action) {
                case 'restored':
                    $message = 'Account restored successfully.';
                    break;
                case 'trashed':
                    $message = 'Account trashed successfully.';
                    break;
                default:
                    $message = 'Account permanently deleted.';
            }
            $this->message = __($message, 'paypal-for-woocommerce-multi-account-management');
            exit();
        };
        if ('trash' === $this->current_action()) {
            if (!empty($_POST['account'])) {
                foreach ($_POST['account'] as $key => $postId) {
                    $this->trashAccount($postId);
                }
            } elseif (!empty($_GET['ID'])) {
                $this->trashAccount($_GET['ID']);
            }
            $deleteRedirect('trashed');
        } else if ('delete' === $this->current_action()) {
            if (!empty($_POST['account'])) {
                $account = $_POST['account'];
                foreach ($account as $key => $postId) {
                    $this->deleteAccount($postId);
                }
            } elseif (!empty($_GET['ID'])) {
                $this->deleteAccount($_GET['ID']);
            }
            $deleteRedirect();
        } else if ('restore' === $this->current_action()) {
            if (!empty($_POST['account'])) {
                $account = $_POST['account'];
                foreach ($account as $key => $postId) {
                    $this->unTrashPost($postId);
                }
            } elseif (!empty($_GET['ID'])) {
                $this->unTrashPost($_GET['ID']);
            }
            $deleteRedirect('restored');
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
        $angelleye_payment_load_balancer = get_option('angelleye_payment_load_balancer', '');
        $order_by = 'DESC';
        if ($angelleye_payment_load_balancer != '') {
            $order_by = 'ASC';
        }
        $args = array(
            'post_type' => 'microprocessing',
            'numberposts' => -1,
            'order' => $order_by,
            'suppress_filters' => false
        );

        if (isset($_REQUEST['filter_entity']) && $_REQUEST['filter_entity'] == 'trashed') {
            $args['post_status'] = 'trash';
        }

        if (isset($_REQUEST['orderby'])) {
            $args['orderby'] = $_REQUEST['orderby'];
        }
        if (isset($_REQUEST['order'])) {
            $args['order'] = $_REQUEST['order'];
        }
        if (class_exists('WC_Gateway_PayPal_Express_AngellEYE')) {
            $paypal_express = angelleye_wc_gateway('paypal_express');
            if (!empty($paypal_express)) {
                $paypal_express_api_mode = angelleye_wc_gateway('paypal_express')->get_option('testmode', '');
            } else {
                $paypal_express_api_mode = 'yes';
            }
        } else {
            $paypal_express_api_mode = 'yes';
        }
        if (class_exists('WC_Gateway_PayPal_Pro_PayFlow_AngellEYE')) {
            $paypal_pro_payflow = angelleye_wc_gateway('paypal_pro_payflow');
            if (!empty($paypal_pro_payflow)) {
                $paypal_pro_payflow_api_mode = angelleye_wc_gateway('paypal_pro_payflow')->get_option('testmode', '');
            } else {
                $paypal_pro_payflow_api_mode = 'yes';
            }
        } else {
            $paypal_pro_payflow_api_mode = 'yes';
        }
        if (class_exists('WC_Gateway_PPCP_AngellEYE')) {
            $angelleye_ppcp = angelleye_wc_gateway('angelleye_ppcp');
            if (!empty($angelleye_ppcp)) {
                $angelleye_ppcp_api_mode = angelleye_wc_gateway('angelleye_ppcp')->get_option('testmode', '');
            } else {
                $angelleye_ppcp_api_mode = 'yes';
            }
        } else {
            $angelleye_ppcp_api_mode = 'yes';
        }
        $paypal_express_seq = 1;
        $payflow_seq = 1;
        $ppcp_seq = 1;
        $seq_text = __('Payment Seq #', 'paypal-for-woocommerce-multi-account-management');
        $angelleye_payment_load_balancer = get_option('angelleye_payment_load_balancer', '');
        if (isset($_REQUEST['s']) && strlen($_REQUEST['s'])) {
            $posts = $wpdb->get_col($wpdb->prepare("SELECT DISTINCT p1.post_id FROM {$wpdb->postmeta} p1, {$wpdb->posts}  WHERE wp_posts.ID = p1.post_id AND wp_posts.post_type = 'microprocessing' AND p1.meta_value LIKE %s", '%' . wc_clean($_REQUEST['s']) . '%'));
        } else {
            $posts = get_posts($args);
        }
        if (!empty($posts)) {
            foreach ($posts as $key => $value) {
                $account_data[$key]['ID'] = isset($value->ID) ? $value->ID : $value;
                $meta_data = get_post_meta(isset($value->ID) ? $value->ID : $value);
                $meta_data['angelleye_multi_account_choose_payment_gateway'][0] = empty($meta_data['angelleye_multi_account_choose_payment_gateway'][0]) ? 'paypal_express' : $meta_data['angelleye_multi_account_choose_payment_gateway'][0];
                if (!empty($meta_data['angelleye_multi_account_choose_payment_gateway'][0]) && $meta_data['angelleye_multi_account_choose_payment_gateway'][0] == 'paypal_express') {
                    $is_enable = false;
                    if (!empty($meta_data['woocommerce_paypal_express_testmode']) && $meta_data['woocommerce_paypal_express_testmode'][0] == 'on') {
                        $account_data[$key]['mode'] = 'Sandbox';
                    } else {
                        $account_data[$key]['mode'] = 'Live';
                    }

                    if (!empty($meta_data['woocommerce_paypal_express_enable'][0]) && $meta_data['woocommerce_paypal_express_enable'][0] == 'on') {
                        $is_enable = true;
                    }
                    $account_data[$key]['title'] = !empty($meta_data['woocommerce_paypal_express_account_name'][0]) ? $meta_data['woocommerce_paypal_express_account_name'][0] : '';
                    if ($account_data[$key]['mode'] == 'Sandbox') {
                        $account_data[$key]['api_user_name'] = !empty($meta_data['woocommerce_paypal_express_sandbox_api_username'][0]) ? $meta_data['woocommerce_paypal_express_sandbox_api_username'][0] : '';
                        if (empty($account_data[$key]['api_user_name'])) {
                            $account_data[$key]['api_user_name'] = !empty($meta_data['woocommerce_paypal_express_sandbox_email'][0]) ? $meta_data['woocommerce_paypal_express_sandbox_email'][0] : '';
                        }
                        if ($is_enable == true && $paypal_express_api_mode == 'yes' && $angelleye_payment_load_balancer != '') {
                            $account_data[$key]['api_user_name'] .= '<br>' . '<mark class="angelleye_tag"><span>' . $seq_text . $paypal_express_seq . '</span></mark>';
                            $paypal_express_seq = $paypal_express_seq + 1;
                        }
                    } else {
                        $account_data[$key]['api_user_name'] = !empty($meta_data['woocommerce_paypal_express_api_username'][0]) ? $meta_data['woocommerce_paypal_express_api_username'][0] : '';
                        if (empty($account_data[$key]['api_user_name'])) {
                            $account_data[$key]['api_user_name'] = !empty($meta_data['woocommerce_paypal_express_email'][0]) ? $meta_data['woocommerce_paypal_express_email'][0] : '';
                        }
                        if ($is_enable == true && $paypal_express_api_mode != 'yes' && $angelleye_payment_load_balancer != '') {
                            $account_data[$key]['api_user_name'] .= '<br>' . '<mark class="angelleye_tag"><span>' . $seq_text . $paypal_express_seq . '</span></mark>';
                            $paypal_express_seq = $paypal_express_seq + 1;
                        }
                    }
                    if (!empty($meta_data['vendor_id'][0]) && $angelleye_payment_load_balancer === '') {
                        $account_data[$key]['api_user_name'] .= '<br>' . '<mark class="angelleye_tag"><span>' . __('Vendor Rule - Auto Generated', 'paypal-for-woocommerce-multi-account-management') . '</span></mark>';
                    }
                } else if (!empty($meta_data['angelleye_multi_account_choose_payment_gateway'][0]) && $meta_data['angelleye_multi_account_choose_payment_gateway'][0] == 'paypal_pro_payflow') {
                    $is_enable = false;
                    if (!empty($meta_data['woocommerce_paypal_pro_payflow_testmode']) && $meta_data['woocommerce_paypal_pro_payflow_testmode'][0] == 'on') {
                        $account_data[$key]['mode'] = 'Sandbox';
                    } else {
                        $account_data[$key]['mode'] = 'Live';
                    }
                    if (!empty($meta_data['woocommerce_paypal_pro_payflow_enable'][0]) && $meta_data['woocommerce_paypal_pro_payflow_enable'][0] == 'on') {
                        $is_enable = true;
                    }
                    $account_data[$key]['title'] = !empty($meta_data['woocommerce_paypal_pro_payflow_account_name'][0]) ? $meta_data['woocommerce_paypal_pro_payflow_account_name'][0] : '';
                    if ($account_data[$key]['mode'] == 'Sandbox') {
                        $account_data[$key]['api_user_name'] = !empty($meta_data['woocommerce_paypal_pro_payflow_sandbox_api_paypal_user'][0]) ? $meta_data['woocommerce_paypal_pro_payflow_sandbox_api_paypal_user'][0] : '';
                        if ($is_enable == true && $paypal_pro_payflow_api_mode == 'yes' && $angelleye_payment_load_balancer != '') {
                            $account_data[$key]['api_user_name'] .= '<br>' . '<mark class="angelleye_tag"><span>' . $seq_text . $payflow_seq . '</span></mark>';
                            $payflow_seq = $payflow_seq + 1;
                        }
                    } else {
                        $account_data[$key]['api_user_name'] = !empty($meta_data['woocommerce_paypal_pro_payflow_api_paypal_user'][0]) ? $meta_data['woocommerce_paypal_pro_payflow_api_paypal_user'][0] : '';
                        if ($is_enable == true && $paypal_pro_payflow_api_mode != 'yes' && $angelleye_payment_load_balancer != '') {
                            $account_data[$key]['api_user_name'] .= '<br>' . '<mark class="angelleye_tag"><span>' . $seq_text . $payflow_seq . '</span></mark>';
                            $payflow_seq = $payflow_seq + 1;
                        }
                    }
                } else if (!empty($meta_data['angelleye_multi_account_choose_payment_gateway'][0]) && $meta_data['angelleye_multi_account_choose_payment_gateway'][0] == 'angelleye_ppcp') {
                    $is_enable = false;
                    if (!empty($meta_data['woocommerce_angelleye_ppcp_testmode']) && $meta_data['woocommerce_angelleye_ppcp_testmode'][0] == 'on') {
                        $account_data[$key]['mode'] = 'Sandbox';
                    } else {
                        $account_data[$key]['mode'] = 'Live';
                    }
                    if (!empty($meta_data['woocommerce_angelleye_ppcp_enable'][0]) && $meta_data['woocommerce_angelleye_ppcp_enable'][0] == 'on') {
                        $is_enable = true;
                    }
                    $account_data[$key]['title'] = !empty($meta_data['woocommerce_angelleye_ppcp_account_name'][0]) ? $meta_data['woocommerce_angelleye_ppcp_account_name'][0] : '';
                    if ($account_data[$key]['mode'] == 'Sandbox') {
                        $account_data[$key]['api_user_name'] = !empty($meta_data['woocommerce_angelleye_ppcp_sandbox_email_address'][0]) ? $meta_data['woocommerce_angelleye_ppcp_sandbox_email_address'][0] : '';
                        if ($is_enable == true && $angelleye_ppcp_api_mode == 'yes' && $angelleye_payment_load_balancer != '') {
                            $account_data[$key]['api_user_name'] .= '<br>' . '<mark class="angelleye_tag"><span>' . $seq_text . $ppcp_seq . '</span></mark>';
                            $ppcp_seq = $ppcp_seq + 1;
                        }
                    } else {
                        $account_data[$key]['api_user_name'] = !empty($meta_data['woocommerce_angelleye_ppcp_email_address'][0]) ? $meta_data['woocommerce_angelleye_ppcp_email_address'][0] : '';
                        if ($is_enable == true && $angelleye_ppcp_api_mode != 'yes' && $angelleye_payment_load_balancer != '') {
                            $account_data[$key]['api_user_name'] .= '<br>' . '<mark class="angelleye_tag"><span>' . $seq_text . $ppcp_seq . '</span></mark>';
                            $ppcp_seq = $ppcp_seq + 1;
                        }
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

    private function count_posts($postType = 'post') {
        global $wpdb;
        $cache_key = _count_posts_cache_key($postType);

        $counts = wp_cache_get($cache_key, 'counts');
        if (false !== $counts) {
            // We may have cached this before every status was registered.
            foreach (get_post_stati() as $status) {
                if (!isset($counts->{$status})) {
                    $counts->{$status} = 0;
                }
            }

            return $counts;
        }

        $query = "SELECT post_status, COUNT( * ) AS num_posts FROM {$wpdb->posts} WHERE post_type = %s GROUP BY post_status";
        $results = (array) $wpdb->get_results($wpdb->prepare($query, $postType), ARRAY_A);
        $counts = array_fill_keys(get_post_stati(), 0);

        foreach ($results as $row) {
            $counts[$row['post_status']] = $row['num_posts'];
        }

        $counts = (object) $counts;
        wp_cache_set($cache_key, $counts, 'counts');

        return $counts;
    }

    protected function get_views() {
        global $wpdb;
        $views = array();
        $current = (!empty($_REQUEST['filter_entity']) ? $_REQUEST['filter_entity'] : 'all');

        //All link
        $published_posts = $trashed_posts = 0;
        $count_posts = $this->count_posts('microprocessing');

        if ($count_posts) {
            $published_posts = $count_posts->publish;
            $trashed_posts = $count_posts->trash;
        }

        $class = ($current == 'all' ? ' class="current"' : '');
        $all_url = remove_query_arg('filter_entity');
        $views['all'] = "<a href='{$all_url }' {$class} >All ($published_posts)</a>";

        //Trash link
        $foo_url = add_query_arg('filter_entity', 'trashed');
        $class = ($current == 'trashed' ? ' class="current"' : '');
        $views['pending'] = "<a href='{$foo_url}' {$class} >Trashed ($trashed_posts)</a>";

        return $views;
    }

    private function isTrashedView() {
        return isset($_REQUEST['filter_entity']) && $_REQUEST['filter_entity'] == 'trashed';
    }

}
