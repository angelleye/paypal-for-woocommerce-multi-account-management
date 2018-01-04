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
                $condition_field = get_post_meta($item['ID'],'woocommerce_paypal_express_api_condition_field',true);
                $condition_sign = get_post_meta($item['ID'],'woocommerce_paypal_express_api_condition_sign',true);
                $condition_value = get_post_meta($item['ID'],'woocommerce_paypal_express_api_condition_value',true);
                $condition_role = get_post_meta($item['ID'],'woocommerce_paypal_express_api_user_role',true);
                $role='';
                if($condition_role){
                    if($condition_role == 'all'){
                        $role = '<p class="description">'.sprintf('For %s user roles', $condition_role).'</p>';
                    }
                    else{
                        $role = '<p class="description">'.sprintf('When role is %s', $condition_role).'</p>';
                    }                        
                }
                if($condition_field == 'transaction_amount'){
                    $field = __('Transaction Amount','paypal-for-woocommerce-multi-account-management');
                }
                else{ $field = ''; }
                if($condition_sign == 'lessthan'){
                    $sign = '<';
                }
                else if($condition_sign == 'greaterthan'){
                    $sign = '>';
                }
                else if($condition_sign == 'equalto'){
                    $sign = '=';
                }
                else{
                    $sign = '';
                }                
                return "{$field} {$sign} ".  get_woocommerce_currency_symbol()."{$condition_value} {$role}";
               case 'status':             
                   $status = get_post_meta($item['ID'],'woocommerce_paypal_express_enable',true);
                   if($status == 'on'){
                       return __('Enabled','paypal-for-woocommerce-multi-account-management');
                   }
                   else{
                       return __('Disabled','paypal-for-woocommerce-multi-account-management');
                   }
                
            default:
                return print_r($item, true);
        }
    }

    function column_title($item) {
        $edit_params = array( 'page' => $_REQUEST['page'], 'action' => 'edit', 'ID' => $item['ID']);
        $delete_params = array( 'page' => $_REQUEST['page'], 'action' => 'delete', 'ID' => $item['ID']);
        $actions = array(
            'edit' => sprintf('<a href="%s">Edit</a>', esc_url( add_query_arg( $edit_params ) )),
            'delete' => sprintf('<a href="%s">Delete</a>', esc_url( add_query_arg( $delete_params ) )),
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
            'title' => __('Account Name','paypal-for-woocommerce-multi-account-management'),
            'api_user_name' => __('API User Name','paypal-for-woocommerce-multi-account-management'),
            'trigger_condition' => __('Trigger Condition','paypal-for-woocommerce-multi-account-management'),
            'mode' => __('Sandbox/Live','paypal-for-woocommerce-multi-account-management'),
            'status' => __('Status','paypal-for-woocommerce-multi-account-management'),
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
            'delete' => __('Delete','paypal-for-woocommerce-multi-account-management')
        );
        return $actions;
    }

    function process_bulk_action() {
        if ('delete' === $this->current_action()) {
            if( !empty($_POST['account']) ) {
                $account = $_POST['account'];
                foreach ($account as $key => $value) {
                    wp_delete_post($value, true);
                }
                $redirect_url = remove_query_arg(array('action', 'ID'));
                wp_redirect(add_query_arg('deleted', true, $redirect_url));
                exit();
            }
            if( !empty($_GET['action']) && 'delete' == $_GET['action'] && !empty($_GET['ID'])) {
                wp_delete_post($_GET['ID'], true);
                $redirect_url = remove_query_arg(array('action', 'ID'));
                wp_redirect(add_query_arg('deleted', true, $redirect_url));
                exit();
            }
            
        }
    }

    function prepare_items() {
        global $wpdb;
        $per_page = 5;
        $account_data = array();
        $columns = $this->get_columns();
        $hidden = array();
        $sortable = $this->get_sortable_columns();
        $this->_column_headers = array($columns, $hidden, $sortable);
        $this->process_bulk_action();
        $args = array(
            'post_type' => 'microprocessing',
            'post_status' => 'any'
        );
        $posts = get_posts($args);
        if (!empty($posts)) {
            foreach ($posts as $key => $value) {
                $account_data[$key]['ID'] = $value->ID;
                $meta_data = get_post_meta($value->ID);
                if (!empty($meta_data['woocommerce_paypal_express_testmode']) && $meta_data['woocommerce_paypal_express_testmode'][0] == 'on') {
                    $account_data[$key]['mode'] = 'Sandbox';
                } else {
                    $account_data[$key]['mode'] = 'Live';
                }
                $account_data[$key]['title'] = !empty($meta_data['woocommerce_paypal_express_account_name'][0]) ? $meta_data['woocommerce_paypal_express_account_name'][0] : '';
                if ($account_data[$key]['mode'] == 'Sandbox') {
                    $account_data[$key]['api_user_name'] = !empty($meta_data['woocommerce_paypal_express_sandbox_api_username'][0]) ? $meta_data['woocommerce_paypal_express_sandbox_api_username'][0] : '';
                } else {
                    $account_data[$key]['api_user_name'] = !empty($meta_data['woocommerce_paypal_express_api_username'][0]) ? $meta_data['woocommerce_paypal_express_sandbox_api_username'][0] : '';
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

        usort($data, 'usort_reorder');
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
