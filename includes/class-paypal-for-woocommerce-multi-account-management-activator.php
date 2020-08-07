<?php

/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 *
 * @since      1.0.0
 * @package    Paypal_For_Woocommerce_Multi_Account_Management
 * @subpackage Paypal_For_Woocommerce_Multi_Account_Management/includes
 * @author     Angell EYE <service@angelleye.com>
 */
class Paypal_For_Woocommerce_Multi_Account_Management_Activator {

    /**
     * @since    1.0.0
     */
    public static function activate() {
        if(!class_exists('Paypal_For_Woocommerce_Multi_Account_Management_Admin')) {
            require_once plugin_dir_path(dirname(__FILE__)) . 'admin/class-paypal-for-woocommerce-multi-account-management-admin.php';
        }
        $admin_class = new Paypal_For_Woocommerce_Multi_Account_Management_Admin(null, null);
        $admin_class->angelleye_pfwma_create_all_vendor_rules($return = false);
        delete_option('angelleye_pfwma_submited_feedback');
        $log_url = $_SERVER['HTTP_HOST'];
        $log_plugin_id = 18;
        $log_activation_status = 1;
        wp_remote_request('http://www.angelleye.com/web-services/wordpress/update-plugin-status.php?url=' . $log_url . '&plugin_id=' . $log_plugin_id . '&activation_status=' . $log_activation_status);
    }

}
