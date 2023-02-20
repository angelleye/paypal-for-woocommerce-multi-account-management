<?php

class PFWMA_Payments_History_List extends Paypal_For_Woocommerce_Multi_Account_Management_WP_List_Table {

    var $account_data = array();
    private string $listMode;

    function __construct() {
        global $status, $page;
        parent::__construct(array(
            'singular' => 'payment',
            'plural' => 'payments',
            'ajax' => false
        ));
        $this->listMode = '';
    }

    function column_default($item, $column_name) {
        switch ($column_name) {
            case 'account_name':
                return !empty($this->listMode) ? str_replace($this->listMode, '', $item['post_title']) :  $item['post_title'];
            case 'total_amount':
                return wc_price($item['post_content']);

            default:
                return print_r($item, true);
        }
    }

    function get_columns() {
        $columns = array(
            'account_name' => __('Account ID/Email', 'paypal-for-woocommerce-multi-account-management'),
            'total_amount' => __('Total Amount', 'paypal-for-woocommerce-multi-account-management')
        );
        return $columns;
    }

    function get_sortable_columns() {
        $sortable_columns = array(

        );
        return $sortable_columns;
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
            'post_type' => 'multi_ac_totals',
            'numberposts' => -1,
            'order' => 'asc',
            'suppress_filters' => false
        );

        $offset = $this->get_pagenum() * $per_page - $per_page;

        $prefix_mode = " and post_title not like 'sandbox-%'";
        if (isset($_REQUEST['filter_entity']) && $_REQUEST['filter_entity'] == 'sandbox') {
            $this->listMode = 'sandbox-';
            $prefix_mode = " and post_title like 'sandbox-%'";
        }

        $data = $wpdb->get_results("select * from {$wpdb->posts} where post_type = 'multi_ac_totals' $prefix_mode limit $offset, $per_page", ARRAY_A);
        $total_items = $wpdb->get_var("select count(*) from {$wpdb->posts} where post_type = 'multi_ac_totals' $prefix_mode limit $offset, $per_page");
        $this->items = $data;
        $this->set_pagination_args(array(
            'total_items' => $total_items,
            'per_page' => $per_page,
            'total_pages' => ceil($total_items / $per_page)
        ));
    }

    protected function get_views()
    {
        global $wpdb;
        $views = array();
        $current = ( !empty($_REQUEST['filter_entity']) ? $_REQUEST['filter_entity'] : 'all');

        //All link

        $class = ($current == 'all' ? ' class="current"' :'');
        $all_url = remove_query_arg('filter_entity');
        $views['live'] = "<a href='{$all_url }' {$class} >Live</a>";

        //Pending link
        $foo_url = add_query_arg('filter_entity', 'sandbox');
        $class = ($current == 'sandbox' ? ' class="current"' :'');
        $views['sandbox'] = "<a href='{$foo_url}' {$class} >Sandbox</a>";

        return $views;
    }

}
