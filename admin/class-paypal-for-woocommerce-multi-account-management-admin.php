<?php

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    Paypal_For_Woocommerce_Multi_Account_Management
 * @subpackage Paypal_For_Woocommerce_Multi_Account_Management/admin
 * @author     Angell EYE <service@angelleye.com>
 */
class Paypal_For_Woocommerce_Multi_Account_Management_Admin {

    /**
     * The ID of this plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $plugin_name    The ID of this plugin.
     */
    private $plugin_name;

    /**
     * The version of this plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $version    The current version of this plugin.
     */
    private $version;

    /**
     * Initialize the class and set its properties.
     *
     * @since    1.0.0
     * @param      string    $plugin_name       The name of this plugin.
     * @param      string    $version    The version of this plugin.
     */
    public function __construct($plugin_name, $version) {

        $this->plugin_name = $plugin_name;
        $this->version = $version;
    }

    /**
     * Register the stylesheets for the admin area.
     *
     * @since    1.0.0
     */
    public function enqueue_styles() {
        wp_enqueue_style($this->plugin_name, plugin_dir_url(__FILE__) . 'css/paypal-for-woocommerce-multi-account-management-admin.css', array(), $this->version, 'all');
    }

    /**
     * Register the JavaScript for the admin area.
     *
     * @since    1.0.0
     */
    public function enqueue_scripts() {
        wp_enqueue_script($this->plugin_name, plugin_dir_url(__FILE__) . 'js/paypal-for-woocommerce-multi-account-management-admin.js', array('jquery'), $this->version, true);
    }

    public function angelleye_multi_account_ui() {
        ?>
        <br/>
        <div style="display: inline; float: left;width: 442px">
            <form method="post" id="mainform" action="" enctype="multipart/form-data">
                <table class="form-table" id="micro_account_fields" style="width: 415px;">
                    <tbody class="angelleye_micro_account_body">
                        <tr valign="top">
                            <th scope="row" class="titledesc">
                                <label for="woocommerce_paypal_express_testmode">PayPal Sandbox</label>
                            </th>
                            <td class="forminp">
                                <fieldset>
                                    <label for="woocommerce_paypal_express_testmode_microprocessing">
                                        <input class="woocommerce_paypal_express_testmode" name="microprocessing[woocommerce_paypal_express_testmode][]" id="woocommerce_paypal_express_testmode_microprocessing" type="checkbox"><?php echo __('Enable PayPal Sandbox', 'paypal-for-woocommerce-multi-account-management'); ?> </label><br>
                                </fieldset>
                            </td>
                        </tr>
                        <tr valign="top">
                            <th scope="row" class="titledesc">
                                <label for="woocommerce_paypal_express_account_name_microprocessing"><?php echo __('Account Name/Label', 'paypal-for-woocommerce-multi-account-management'); ?></label>
                            </th>
                            <td class="forminp">
                                <fieldset>
                                    <legend class="screen-reader-text"><span><?php echo __('Account Name/Label', 'paypal-for-woocommerce-multi-account-management'); ?></span></legend>
                                    <input class="input-text regular-input " name="microprocessing[woocommerce_paypal_express_account_name_microprocessing][]" id="woocommerce_paypal_express_account_name_microprocessing" style="" placeholder="" type="text">
                                </fieldset>
                            </td>
                        </tr>
                        <tr valign="top">
                            <th scope="row" class="titledesc">
                                <label for="woocommerce_paypal_express_sandbox_api_username_microprocessing"><?php echo __('Sandbox API User Name', 'paypal-for-woocommerce-multi-account-management'); ?></label>
                            </th>
                            <td class="forminp">
                                <fieldset>
                                    <legend class="screen-reader-text"><span><?php echo __('Sandbox API User Name', 'paypal-for-woocommerce-multi-account-management'); ?></span></legend>
                                    <input class="input-text regular-input " name="microprocessing[woocommerce_paypal_express_sandbox_api_username][]" id="woocommerce_paypal_express_sandbox_api_username_microprocessing" style="" placeholder="" type="text">
                                </fieldset>
                            </td>
                        </tr>
                        <tr valign="top">
                            <th scope="row" class="titledesc">
                                <label for="woocommerce_paypal_express_sandbox_api_password_microprocessing"><?php echo __('Sandbox API Password', 'paypal-for-woocommerce-multi-account-management'); ?></label>
                            </th>
                            <td class="forminp">
                                <fieldset>
                                    <legend class="screen-reader-text"><span><?php echo __('Sandbox API Password', 'paypal-for-woocommerce-multi-account-management'); ?></span></legend>
                                    <input class="input-text regular-input " name="microprocessing[woocommerce_paypal_express_sandbox_api_password][]" id="woocommerce_paypal_express_sandbox_api_password_microprocessing" style="" placeholder="" type="password">
                                </fieldset>
                            </td>
                        </tr>
                        <tr valign="top">
                            <th scope="row" class="titledesc">
                                <label for="woocommerce_paypal_express_sandbox_api_signature_microprocessing"><?php echo __('Sandbox API Signature', 'paypal-for-woocommerce-multi-account-management'); ?></label>
                            </th>
                            <td class="forminp">
                                <fieldset>
                                    <legend class="screen-reader-text"><span><?php echo __('Sandbox API Signature', 'paypal-for-woocommerce-multi-account-management'); ?></span></legend>
                                    <input class="input-text regular-input " name="microprocessing[woocommerce_paypal_express_sandbox_api_signature][]" id="woocommerce_paypal_express_sandbox_api_signature_microprocessing" style="" placeholder="" type="password">
                                </fieldset>
                            </td>
                        </tr>
                        <tr style="display: table-row;" valign="top">
                            <th scope="row" class="titledesc">
                                <label for="woocommerce_paypal_express_api_username_microprocessing"><?php echo __('API User Name', 'paypal-for-woocommerce-multi-account-management'); ?></label>
                            </th>
                            <td class="forminp">
                                <fieldset>
                                    <legend class="screen-reader-text"><span><?php echo __('API User Name', 'paypal-for-woocommerce-multi-account-management'); ?></span></legend>
                                    <input class="input-text regular-input " name="microprocessing[woocommerce_paypal_express_api_username][]" id="woocommerce_paypal_express_api_username_microprocessing" style="" placeholder="" type="text">
                                </fieldset>
                            </td>
                        </tr>
                        <tr style="display: table-row;" valign="top">
                            <th scope="row" class="titledesc">
                                <label for="woocommerce_paypal_express_api_password_microprocessing"><?php echo __('API Password', 'paypal-for-woocommerce-multi-account-management'); ?></label>
                            </th>
                            <td class="forminp">
                                <fieldset>
                                    <legend class="screen-reader-text"><span><?php echo __('API Password', 'paypal-for-woocommerce-multi-account-management'); ?></span></legend>
                                    <input class="input-text regular-input " name="microprocessing[woocommerce_paypal_express_api_password][]" id="woocommerce_paypal_express_api_password_microprocessing" style="" placeholder="" type="password">
                                </fieldset>
                            </td>
                        </tr>
                    <th scope="row" class="titledesc">
                        <label for="woocommerce_paypal_express_api_signature_microprocessing"><?php echo __('API Signature', 'paypal-for-woocommerce-multi-account-management'); ?></label>
                    </th>
                    <td class="forminp">
                        <fieldset>
                            <legend class="screen-reader-text"><span><?php echo __('API Signature', 'paypal-for-woocommerce-multi-account-management'); ?></span></legend>
                            <input class="input-text regular-input " name="microprocessing[woocommerce_paypal_express_api_signature][]" id="woocommerce_paypal_express_api_signature_microprocessing" style="" placeholder="" type="password">
                        </fieldset>
                    </td>
                    </tr>
                    <tr style="display: table-row;" valign="top">
                        <th scope="row" class="titledesc">
                            <input name="save" class="button-primary woocommerce-save-button" type="submit" value="<?php esc_attr_e('Save changes', 'paypal-for-woocommerce-multi-account-management'); ?>" />
                            <?php wp_nonce_field('microprocessing'); ?>
                        </th>
                    </tr>
                    </tbody>
                </table>
            </form>
        </div>
        <div style="display: inline; float: left;width: 660px">
            <table class="wp-list-table widefat fixed striped companies">
                <thead>
                    <tr>
                        <td id="cb" class="manage-column column-cb check-column"><label class="screen-reader-text" for="cb-select-all-1">Select All</label><input id="cb-select-all-1" type="checkbox"></td><th scope="col" id="title" class="manage-column column-title column-primary sortable desc"><a href="http://localhost/paypal_for_woo/wp-admin/admin.php?page=paypal-wp-button-manager-option&amp;tab=company&amp;orderby=title&amp;order=asc"><span>Company Name</span><span class="sorting-indicator"></span></a></th><th scope="col" id="paypal_person_name" class="manage-column column-paypal_person_name">Contact Name</th><th scope="col" id="paypal_person_email" class="manage-column column-paypal_person_email">PayPal Account Email</th><th scope="col" id="paypal_mode" class="manage-column column-paypal_mode">PayPal Mode</th>
                    </tr>
                </thead>
                <tbody id="the-list" data-wp-lists="list:company">
                    <tr class="no-items">
                        <td class="colspanchange" colspan="5">No items found.</td>
                    </tr>	
                </tbody>
                <tfoot>
                    <tr>
                        <td class="manage-column column-cb check-column"><label class="screen-reader-text" for="cb-select-all-2">Select All</label><input id="cb-select-all-2" type="checkbox"></td>
                        <th scope="col" class="manage-column column-title column-primary sortable desc"><a href="http://localhost/paypal_for_woo/wp-admin/admin.php?page=paypal-wp-button-manager-option&amp;tab=company&amp;orderby=title&amp;order=asc"><span>Company Name</span><span class="sorting-indicator"></span></a></th><th scope="col" class="manage-column column-paypal_person_name">Contact Name</th><th scope="col" class="manage-column column-paypal_person_email">PayPal Account Email</th>
                        <th scope="col" class="manage-column column-paypal_mode">PayPal Mode</th>	</tr>
                </tfoot>

            </table>
        </div>
        <script type="text/javascript">


        </script>

        <?php
        $woocommerce_paypal_express_settings = get_option('woocommerce_paypal_express_settings');
        if (!empty($woocommerce_paypal_express_settings['microprocessing'])) {
            $this->angelleye_display_multi_account_list($woocommerce_paypal_express_settings['microprocessing']);
        }
    }

    public function angelleye_multi_account_save_settings($settings) {
        $section = array();
        $original = array();
        if (!empty($_POST['microprocessing'])) {
            foreach ($_POST['microprocessing'] as $key_name => $value_array) {
                $count = 0;
                foreach ($value_array as $key => $value) {
                    $section[$count][$key_name] = $value;
                    $count = $count + 1;
                }
            }
            foreach ($section as $section_key => $value_array) {
                if ((empty($value_array['woocommerce_paypal_express_sandbox_api_username']) && empty($value_array['woocommerce_paypal_express_sandbox_api_username']) && empty($value_array['woocommerce_paypal_express_sandbox_api_username'])) && (empty($value_array['woocommerce_paypal_express_sandbox_api_username']) && empty($value_array['woocommerce_paypal_express_sandbox_api_username']) && empty($value_array['woocommerce_paypal_express_sandbox_api_username']))) {
                    unset($original[$section_key]);
                } else {
                    $original[$section_key] = $value_array;
                }
                if (count($value_array) < 4) {
                    unset($original[$section_key]);
                }
            }
            $settings['microprocessing'] = $original;
        }
        return $settings;
    }

    public function angelleye_display_multi_account_list($microprocessing) {
        foreach ($microprocessing as $microprocessing_key => $microprocessing_value) {
            echo '<br/><table class="form-table">
            <tbody class="angelleye_micro_account_body">';
            foreach ($microprocessing_value as $key => $value) {
                switch ($key) {
                    case 'woocommerce_paypal_express_sandbox_api_username':
                        echo sprintf('<tr valign="top"><th scope="row" class="titledesc"><label for="woocommerce_paypal_express_sandbox_api_username_microprocessing">%1$s</label></th><td class="forminp"><fieldset><legend class="screen-reader-text"><span>%2$s</span></legend><input class="input-text regular-input " name="microprocessing[woocommerce_paypal_express_sandbox_api_username][]" id="woocommerce_paypal_express_sandbox_api_username_microprocessing" value="%3$s" style="" placeholder="" type="text"></fieldset></td></tr>', __('Sandbox API User Name', 'paypal-for-woocommerce-multi-account-management'), __('Sandbox API User Name', 'paypal-for-woocommerce-multi-account-management'), $value);
                        break;
                    case 'woocommerce_paypal_express_sandbox_api_password':
                        echo sprintf('<tr valign="top"><th scope="row" class="titledesc"><label for="woocommerce_paypal_express_sandbox_api_password_microprocessing">%1$s</label></th><td class="forminp"><fieldset><legend class="screen-reader-text"><span>%2$s</span></legend><input class="input-text regular-input " name="microprocessing[woocommerce_paypal_express_sandbox_api_password][]" id="woocommerce_paypal_express_sandbox_api_password_microprocessing" value="%1$s" style="" placeholder="" type="password"></fieldset></td></tr>', __('Sandbox API Password', 'paypal-for-woocommerce-multi-account-management'), __('Sandbox API Password', 'paypal-for-woocommerce-multi-account-management'), $value);
                        break;
                    case 'woocommerce_paypal_express_sandbox_api_signature':
                        echo sprintf('<tr valign="top"><th scope="row" class="titledesc"><label for="woocommerce_paypal_express_sandbox_api_signature_microprocessing">%1$s</label></th><td class="forminp"><fieldset><legend class="screen-reader-text"><span>%2$s</span></legend><input class="input-text regular-input " name="microprocessing[woocommerce_paypal_express_sandbox_api_signature][]" id="woocommerce_paypal_express_sandbox_api_signature_microprocessing" value="%1$s" style="" placeholder="" type="password"></fieldset></td></tr>', __('Sandbox API Signature', 'paypal-for-woocommerce-multi-account-management'), __('Sandbox API Signature', 'paypal-for-woocommerce-multi-account-management'), $value);
                        break;
                    case 'woocommerce_paypal_express_api_username':
                        echo sprintf('<tr style="display: table-row;" valign="top"><th scope="row" class="titledesc"><label for="woocommerce_paypal_express_api_username_microprocessing">%1$s</label></th><td class="forminp"><fieldset><legend class="screen-reader-text"><span>%2$s</span></legend><input class="input-text regular-input " name="microprocessing[woocommerce_paypal_express_api_username][]" id="woocommerce_paypal_express_api_username_microprocessing" value="%1$s" style="" placeholder="" type="text"></fieldset></td></tr>', __('Live API User Name', 'paypal-for-woocommerce-multi-account-management'), __('Live API User Name', 'paypal-for-woocommerce-multi-account-management'), $value);
                        break;
                    case 'woocommerce_paypal_express_api_password':
                        echo sprintf('<tr style="display: table-row;" valign="top"><th scope="row" class="titledesc"><label for="woocommerce_paypal_express_api_password_microprocessing">%1$s</label></th><td class="forminp"><fieldset><legend class="screen-reader-text"><span>%2$s</span></legend><input class="input-text regular-input " name="microprocessing[woocommerce_paypal_express_api_password][]" id="woocommerce_paypal_express_api_password_microprocessing" value="%1$s" style="" placeholder="" type="password"></fieldset></td></tr>', __('Live API Password', 'paypal-for-woocommerce-multi-account-management'), __('Live API Password', 'paypal-for-woocommerce-multi-account-management'), $value);
                        break;
                    case 'woocommerce_paypal_express_api_signature':
                        echo sprintf('<tr style="display: table-row;" valign="top"><th scope="row" class="titledesc"><label for="woocommerce_paypal_express_api_signature_microprocessing">%1$s</label></th><td class="forminp"><fieldset><legend class="screen-reader-text"><span>%2$s</span></legend><input class="input-text regular-input " name="microprocessing[woocommerce_paypal_express_api_signature][]" id="woocommerce_paypal_express_api_signature_microprocessing" value="%1$s" style="" placeholder="" type="password"></fieldset></td></tr>', __('Live API Signature', 'paypal-for-woocommerce-multi-account-management'), __('Live API Signature', 'paypal-for-woocommerce-multi-account-management'), $value);
                        break;
                }
            }
            echo '</tbody></table>';
        }
    }

    public function is_angelleye_multi_account_used($order_id) {
        $multi_account_api_username = WC()->session->get('multi_account_api_username');
        if (!empty($multi_account_api_username)) {
            return true;
        }
        if ($order_id > 0) {
            $_multi_account_api_username = get_post_meta($order_id, '_multi_account_api_username', true);
            if (!empty($_multi_account_api_username)) {
                return true;
            }
        }
        return false;
    }

    public function angelleye_get_multi_account_api_user_name($order_id) {
        $multi_account_api_username = WC()->session->get('multi_account_api_username');
        if (!empty($multi_account_api_username)) {
            return $multi_account_api_username;
        }
        if ($order_id > 0) {
            $_multi_account_api_username = get_post_meta($order_id, '_multi_account_api_username', true);
            if (!empty($_multi_account_api_username)) {
                return $multi_account_api_username;
            }
        }
        return false;
    }

    public function angelleye_get_multi_account_by_order_total($gateways, $gateway_setting, $order_id) {
        $order_total = angelleye_get_total($order_id);
        $microprocessing = $gateways->get_option('microprocessing');
        foreach ($microprocessing as $microprocessing_key => $microprocessing_value) {
            if ($order_total >= $microprocessing_value['woocommerce_paypal_express_rules']) {
                return $microprocessing_value;
            }
        }
    }

    public function angelleye_paypal_for_woocommerce_multi_account_api_paypal_express($gateways, $request = null, $order_id = null) {
        if ($request == null) {
            $gateway_setting = $gateways;
        } else {
            $gateway_setting = $request;
        }

        if ($order_id == null) {
            if (is_null(WC()->cart)) {
                return;
            }
            if (WC()->cart->is_empty()) {
                return false;
            }
        }
        if ($this->is_angelleye_multi_account_used($order_id)) {
            $_multi_account_api_username = $this->angelleye_get_multi_account_api_user_name($order_id);
            $microprocessing_value = $this->angelleye_get_multi_account_details_by_api_user_name($gateways, $_multi_account_api_username);
        } else {
            $microprocessing_value = $this->angelleye_get_multi_account_by_order_total($gateways, $gateway_setting, $order_id);
        }
        if ($gateways->testmode == true) {
            if (!empty($microprocessing_value['woocommerce_paypal_express_sandbox_api_username']) && !empty($microprocessing_value['woocommerce_paypal_express_sandbox_api_password']) && !empty($microprocessing_value['woocommerce_paypal_express_sandbox_api_signature'])) {
                $gateway_setting->api_username = $microprocessing_value['woocommerce_paypal_express_sandbox_api_username'];
                $gateway_setting->api_password = $microprocessing_value['woocommerce_paypal_express_sandbox_api_password'];
                $gateway_setting->api_signature = $microprocessing_value['woocommerce_paypal_express_sandbox_api_signature'];
                WC()->session->set('multi_account_api_username', $gateway_setting->api_username);
                return;
            }
        } else {
            if (!empty($microprocessing_value['woocommerce_paypal_express_api_username']) && !empty($microprocessing_value['woocommerce_paypal_express_api_password']) && !empty($microprocessing_value['woocommerce_paypal_express_api_signature'])) {
                $gateway_setting->api_username = $microprocessing_value['woocommerce_paypal_express_api_username'];
                $gateway_setting->api_password = $microprocessing_value['woocommerce_paypal_express_api_password'];
                $gateway_setting->api_signature = $microprocessing_value['woocommerce_paypal_express_api_signature'];
                WC()->session->set('multi_account_api_username', $gateway_setting->api_username);
                return;
            }
        }
    }

    public function angelleye_get_multi_account_details_by_api_user_name($gateways, $_multi_account_api_username) {
        $microprocessing = $gateways->get_option('microprocessing');
        foreach ($microprocessing as $microprocessing_key => $microprocessing_value) {
            if ((!empty($microprocessing_value['woocommerce_paypal_express_sandbox_api_username']) && $_multi_account_api_username == $microprocessing_value['woocommerce_paypal_express_sandbox_api_username']) || (!empty($microprocessing_value['woocommerce_paypal_express_api_username']) && $_multi_account_api_username == $microprocessing_value['woocommerce_paypal_express_api_username'] )) {
                return $microprocessing_value;
            }
        }
    }

    public function angelleye_get_total($order_id) {
        if ($order_id > 0) {
            $order = new WC_Order($order_id);
            $cart_contents_total = $order->get_total();
        } else {
            if (wc_prices_include_tax()) {
                $cart_contents_total = WC()->cart->cart_contents_total;
            } else {
                $cart_contents_total = WC()->cart->cart_contents_total + WC()->cart->tax_total;
            }
        }
        return $cart_contents_total;
    }

    public function angelleye_woocommerce_checkout_update_order_meta($order_id, $data) {
        $multi_account_api_username = WC()->session->get('multi_account_api_username');
        if (!empty($multi_account_api_username)) {
            update_post_meta($order_id, '_multi_account_api_username', $multi_account_api_username);
        }
    }

    public function angelleye_paypal_for_woocommerce_general_settings_tab() {
        $gateway = isset($_GET['gateway']) ? $_GET['gateway'] : 'paypal_payment_gateway_products';
        ?> 
        <a href="?page=paypal-for-woocommerce&tab=general_settings&gateway=paypal_for_wooCommerce_for_multi_account_management" class="nav-tab <?php echo $gateway == 'paypal_for_wooCommerce_for_multi_account_management' ? 'nav-tab-active' : ''; ?>"><?php echo __('PayPal for WooCommerce for Multi-Account Management', 'paypal-for-woocommerce'); ?></a> <?php
    }

    public function angelleye_paypal_for_woocommerce_general_settings_tab_content() {
        $gateway = isset($_GET['gateway']) ? $_GET['gateway'] : 'paypal_payment_gateway_products';
        if ($gateway == 'paypal_for_wooCommerce_for_multi_account_management') {
            $this->angelleye_multi_account_ui();
        }
    }

}
