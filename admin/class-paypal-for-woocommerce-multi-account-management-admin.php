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

    public function angelleye_post_exists($id) {
        return is_string(get_post_status($id));
    }

    public function angelleye_display_multi_account_list() {
        if (empty($_GET['ID'])) {
            return false;
        }

        if ($this->angelleye_post_exists($_GET['ID']) == false) {
            return false;
        }

        $microprocessing = get_post_meta($_GET['ID']);
        echo '<br/><div><form method="post" id="mainform" action="" enctype="multipart/form-data"><table class="form-table">
            <tbody class="angelleye_micro_account_body">';
        foreach ($microprocessing as $microprocessing_key => $microprocessing_value) {
            switch ($microprocessing_key) {
                case 'woocommerce_paypal_express_testmode':
                    echo sprintf('<tr valign="top"><th scope="row" class="titledesc"><label for="woocommerce_paypal_express_testmode">PayPal Sandbox</label></th><td class="forminp"><fieldset><label for="woocommerce_paypal_express_testmode_microprocessing"><input class="woocommerce_paypal_express_testmode width460" name="woocommerce_paypal_express_testmode" %1$s id="woocommerce_paypal_express_testmode_microprocessing" type="checkbox"> %2$s</label><br></fieldset></td></tr>', ($microprocessing_value[0] == 'on') ? 'checked' : '', __('Enable PayPal Sandbox', 'paypal-for-woocommerce-multi-account-management'));
                    break;
                case 'woocommerce_paypal_express_account_name':
                    echo sprintf('<tr valign="top"><th scope="row" class="titledesc"><label for="woocommerce_paypal_express_account_name_microprocessing">%1$s</label></th><td class="forminp"><fieldset><input class="input-text regular-input width460" name="woocommerce_paypal_express_account_name" value="%2$s" id="woocommerce_paypal_express_account_name_microprocessing" style="" placeholder="" type="text"></fieldset></td></tr>', __('Account Name/Label', 'paypal-for-woocommerce-multi-account-management'), !empty($microprocessing_value[0]) ? $microprocessing_value[0] : '');
                    break;
                case 'woocommerce_paypal_express_sandbox_api_username':
                    echo sprintf('<tr valign="top"><th scope="row" class="titledesc"><label for="woocommerce_paypal_express_sandbox_api_username_microprocessing">%1$s</label></th><td class="forminp"><fieldset><input class="input-text regular-input width460" name="woocommerce_paypal_express_sandbox_api_username" value="%2$s" id="woocommerce_paypal_express_sandbox_api_username_microprocessing" style="" placeholder="" type="text"></fieldset></td></tr>', __('Sandbox API User Name', 'paypal-for-woocommerce-multi-account-management'), !empty($microprocessing_value[0]) ? $microprocessing_value[0] : '');
                    break;
                case 'woocommerce_paypal_express_sandbox_api_password':
                    echo sprintf('<tr valign="top"><th scope="row" class="titledesc"><label for="woocommerce_paypal_express_sandbox_api_password_microprocessing">%1$s</label></th><td class="forminp"><fieldset><input class="input-text regular-input width460" name="woocommerce_paypal_express_sandbox_api_password" value="%2$s" id="woocommerce_paypal_express_sandbox_api_password_microprocessing" style="" placeholder="" type="password"></fieldset></td></tr>', __('Sandbox API Password', 'paypal-for-woocommerce-multi-account-management'), !empty($microprocessing_value[0]) ? $microprocessing_value[0] : '');
                    break;
                case 'woocommerce_paypal_express_sandbox_api_signature':
                    echo sprintf('<tr valign="top"><th scope="row" class="titledesc"><label for="woocommerce_paypal_express_sandbox_api_signature_microprocessing">%1$s</label></th><td class="forminp"><fieldset><input class="input-text regular-input width460" name="woocommerce_paypal_express_sandbox_api_signature" value="%2$s" id="woocommerce_paypal_express_sandbox_api_signature_microprocessing" style="" placeholder="" type="password"></fieldset></td></tr>', __('Sandbox API Signature', 'paypal-for-woocommerce-multi-account-management'), !empty($microprocessing_value[0]) ? $microprocessing_value[0] : '');
                    break;
                case 'woocommerce_paypal_express_api_username':
                    echo sprintf('<tr style="display: table-row;" valign="top"><th scope="row" class="titledesc"><label for="woocommerce_paypal_express_api_username_microprocessing">%1$s</label><td class="forminp"><fieldset><input class="input-text regular-input width460" name="woocommerce_paypal_express_api_username" value="%2$s" id="woocommerce_paypal_express_api_username_microprocessing" style="" placeholder="" type="text"></fieldset></td></tr>', __('API User Name', 'paypal-for-woocommerce-multi-account-management'), !empty($microprocessing_value[0]) ? $microprocessing_value[0] : '');
                    break;
                case 'woocommerce_paypal_express_api_password':
                    echo sprintf('<tr style="display: table-row;" valign="top"><th scope="row" class="titledesc"><label for="woocommerce_paypal_express_api_password_microprocessing">%1$s</label></th><td class="forminp"><fieldset><input class="input-text regular-input width460" name="woocommerce_paypal_express_api_password" value="%2$s" id="woocommerce_paypal_express_api_password_microprocessing" style="" placeholder="" type="password"></fieldset></td></tr>', __('API Password', 'paypal-for-woocommerce-multi-account-management'), !empty($microprocessing_value[0]) ? $microprocessing_value[0] : '');
                    break;
                case 'woocommerce_paypal_express_api_signature':
                    echo sprintf('<tr><th scope="row" class="titledesc"><label for="woocommerce_paypal_express_api_signature_microprocessing">%1$s</label></th><td class="forminp"><fieldset><input class="input-text regular-input width460" name="woocommerce_paypal_express_api_signature" value="%2$s" id="woocommerce_paypal_express_api_signature_microprocessing" style="" placeholder="" type="password"></fieldset></td></tr>', __('API Signature', 'paypal-for-woocommerce-multi-account-management'), !empty($microprocessing_value[0]) ? $microprocessing_value[0] : '');
                    break;
            }
        }

        echo sprintf('<tr style="display: table-row;" valign="top">
                                <th scope="row" class="titledesc">
                                    <input name="is_edit" class="button-primary woocommerce-save-button" type="hidden" value="%1$s" />
                                    <input name="microprocessing_save" class="button-primary woocommerce-save-button" type="submit" value="%2$s" />
                                    %3$s
                                </th>
                            </tr>', $_GET['ID'], __('Save changes', 'paypal-for-woocommerce-multi-account-management'), wp_nonce_field('microprocessing_save'));
        echo '</tbody></table></form></div>';
    }

    public function angelleye_multi_account_ui() {

        $this->angelleye_save_multi_account_data();
        if (empty($_GET['action'])) {
            if (!empty($_GET['success'])) {
                echo sprintf('<div class=" notice notice-success is-dismissible"><p>%1$s</p></div>', __('Your settings have been saved.', 'paypal-for-woocommerce-multi-account-management'));
            }
            if (!empty($_GET['deleted'])) {
                echo sprintf('<div class=" notice notice-success is-dismissible"><p>%1$s</p></div>', __('Account permanently deleted.', 'paypal-for-woocommerce-multi-account-management'));
            }
            ?>
            <br/>
            <div>
                <form method="post" id="mainform" action="" enctype="multipart/form-data">
                    <table class="form-table" id="micro_account_fields" >
                        <tbody class="angelleye_micro_account_body">
                            <tr valign="top">
                                <th scope="row" class="titledesc">
                                    <label for="woocommerce_paypal_express_testmode">PayPal Sandbox</label>
                                </th>
                                <td class="forminp">
                                    <fieldset>
                                        <label for="woocommerce_paypal_express_testmode_microprocessing">
                                            <input class="woocommerce_paypal_express_testmode width460" name="woocommerce_paypal_express_testmode" id="woocommerce_paypal_express_testmode_microprocessing" type="checkbox"><?php echo __('Enable PayPal Sandbox', 'paypal-for-woocommerce-multi-account-management'); ?> </label><br>
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
                                        <input class="input-text regular-input width460" name="woocommerce_paypal_express_account_name" id="woocommerce_paypal_express_account_name_microprocessing" style="" placeholder="" type="text">
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
                                        <input class="input-text regular-input width460" name="woocommerce_paypal_express_sandbox_api_username" id="woocommerce_paypal_express_sandbox_api_username_microprocessing" style="" placeholder="" type="text">
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
                                        <input class="input-text regular-input width460" name="woocommerce_paypal_express_sandbox_api_password" id="woocommerce_paypal_express_sandbox_api_password_microprocessing" style="" placeholder="" type="password">
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
                                        <input class="input-text regular-input width460" name="woocommerce_paypal_express_sandbox_api_signature" id="woocommerce_paypal_express_sandbox_api_signature_microprocessing" style="" placeholder="" type="password">
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
                                        <input class="input-text regular-input width460" name="woocommerce_paypal_express_api_username" id="woocommerce_paypal_express_api_username_microprocessing" style="" placeholder="" type="text">
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
                                        <input class="input-text regular-input width460" name="woocommerce_paypal_express_api_password" id="woocommerce_paypal_express_api_password_microprocessing" style="" placeholder="" type="password">
                                    </fieldset>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row" class="titledesc">
                                    <label for="woocommerce_paypal_express_api_signature_microprocessing"><?php echo __('API Signature', 'paypal-for-woocommerce-multi-account-management'); ?></label>
                                </th>
                                <td class="forminp">
                                    <fieldset>
                                        <legend class="screen-reader-text"><span><?php echo __('API Signature', 'paypal-for-woocommerce-multi-account-management'); ?></span></legend>
                                        <input class="input-text regular-input width460" name="woocommerce_paypal_express_api_signature" id="woocommerce_paypal_express_api_signature_microprocessing" style="" placeholder="" type="password">
                                    </fieldset>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row" class="titledesc">
                                    <label for="woocommerce_paypal_express_api_trigger_conditions"><?php echo __('Trigger Conditions', 'paypal-for-woocommerce-multi-account-management'); ?></label>
                                </th>
                                <td class="forminp">
                                    <fieldset>
                                        <select class="smart_forwarding_field" name="woocommerce_paypal_express_api_condition_field"><option value="transaction_amount"><?php echo __('Transaction Amount', 'paypal-for-woocommerce-multi-account-management'); ?></option></select>
                                        <select class="smart_forwarding_field" name="woocommerce_paypal_express_api_condition_sign"><option value="equalto"><?php echo __('Equal to', 'paypal-for-woocommerce-multi-account-management'); ?></option><option value="lessthan"><?php echo __('Less than', 'paypal-for-woocommerce-multi-account-management'); ?></option><option value="greaterthan"><?php echo __('Greater than', 'paypal-for-woocommerce-multi-account-management'); ?></option></select>
                                        <input class="input-text regular-input" name="woocommerce_paypal_express_api_condition_value" id="woocommerce_paypal_express_api_condition_value" type="number" min="1" max="100" step="1" pattern="([01]?[0-9]{1}|2[0-3]{1})">
                                    </fieldset>
                                </td>
                            </tr>
                            <tr style="display: table-row;" valign="top">
                                <th scope="row" class="titledesc">
                                    <input name="microprocessing_save" class="button-primary woocommerce-save-button" type="submit" value="<?php esc_attr_e('Save changes', 'paypal-for-woocommerce-multi-account-management'); ?>" />
                                    <?php wp_nonce_field('microprocessing_save'); ?>
                                </th>
                            </tr>
                        </tbody>
                    </table>
                </form>
            </div>
            <?php
        } elseif (!empty($_GET['action']) && $_GET['action'] == 'edit') {

            $this->angelleye_display_multi_account_list();
        }
        ?>
        <br/><br/>
        <div>
            <?php
            $this->angelleye_multi_account_list();
            ?>
        </div>
        <?php
        $woocommerce_paypal_express_settings = get_option('woocommerce_paypal_express_settings');
        if (!empty($woocommerce_paypal_express_settings['microprocessing'])) {
            $this->angelleye_display_multi_account_list($woocommerce_paypal_express_settings['microprocessing']);
        }
    }

    public function angelleye_multi_account_list() {
        if (class_exists('Paypal_For_Woocommerce_Multi_Account_Management_List_Data')) {
            $table = new Paypal_For_Woocommerce_Multi_Account_Management_List_Data();
            $table->prepare_items();
            echo '<form id="account-filter" method="post">';
            echo sprintf('<input type="hidden" name="page" value="%1$s" />', $_REQUEST['page']);
            $table->display();
            echo '</form>';
        }
    }

    public function angelleye_save_multi_account_data() {
        if (!empty($_POST['microprocessing_save'])) {
            if (empty($_REQUEST['_wpnonce']) || !wp_verify_nonce($_REQUEST['_wpnonce'], 'microprocessing_save')) {
                die(__('Action failed. Please refresh the page and retry.', 'paypal-for-woocommerce-multi-account-management'));
            }
            if (!empty($_POST['woocommerce_paypal_express_testmode']) && $_POST['woocommerce_paypal_express_testmode'] == 'on') {
                if (empty($_POST['woocommerce_paypal_express_sandbox_api_username']) && empty($_POST['woocommerce_paypal_express_api_password']) && empty($_POST['woocommerce_paypal_express_sandbox_api_signature'])) {
                    ?>
                    <div class="notice notice-success is-dismissible">
                        <p><?php _e('Sandbox API User Name or Sandbox API Password or Sandbox API Signature empty!', 'paypal-for-woocommerce-multi-account-management'); ?></p>
                    </div>
                    <?php
                } else {
                    $PayPalConfig = array(
                        'Sandbox' => true,
                        'APIUsername' => trim($_POST['woocommerce_paypal_express_sandbox_api_username']),
                        'APIPassword' => trim($_POST['woocommerce_paypal_express_sandbox_api_password']),
                        'APISignature' => trim($_POST['woocommerce_paypal_express_sandbox_api_signature'])
                    );
                }
            } else {
                if (empty($_POST['woocommerce_paypal_express_api_username']) && empty($_POST['woocommerce_paypal_express_api_password']) && empty($_POST['woocommerce_paypal_express_api_signature'])) {
                    ?>
                    <div class="notice notice-success is-dismissible">
                        <p><?php _e('API User Name or API Password or API Signature empty!', 'paypal-for-woocommerce-multi-account-management'); ?></p>
                    </div>
                    <?php
                } else {
                    $PayPalConfig = array(
                        'Sandbox' => false,
                        'APIUsername' => trim($_POST['woocommerce_paypal_express_api_username']),
                        'APIPassword' => trim($_POST['woocommerce_paypal_express_api_password']),
                        'APISignature' => trim($_POST['woocommerce_paypal_express_api_signature'])
                    );
                }
            }
            if (!class_exists('Angelleye_PayPal')) {
                if (defined('PAYPAL_FOR_WOOCOMMERCE_PLUGIN_DIR')) {
                    require_once( PAYPAL_FOR_WOOCOMMERCE_PLUGIN_DIR . '/classes/lib/angelleye/paypal-php-library/includes/paypal.class.php' );
                } else {
                    ?><div class="notice notice-success is-dismissible">
                        <p><?php _e('PayPal library is not loaded!', 'paypal-for-woocommerce-multi-account-management'); ?></p>
                    </div>
                    <?php
                    die();
                }
            }
            $PayPal = new Angelleye_PayPal($PayPalConfig);
            $PayPalResult = $PayPal->GetPalDetails();
            if (isset($PayPalResult['ACK']) && $PayPalResult['ACK'] == 'Success') {
                if (isset($PayPalResult['PAL']) && !empty($PayPalResult['PAL'])) {
                    $merchant_account_id = $PayPalResult['PAL'];
                }
            } else {
                if (!empty($PayPalResult['L_LONGMESSAGE0'])) {
                    ?><div class="notice notice-error is-dismissible">
                        <p><?php _e($PayPalResult['L_LONGMESSAGE0'], 'paypal-for-woocommerce-multi-account-management'); ?></p>
                    </div>
                    <?php
                    return false;
                } else {
                    if (!empty($PayPalResult['L_SHORTMESSAGE0'])) {
                        ?><div class="notice notice-error is-dismissible">
                            <p><?php _e($PayPalResult['L_SHORTMESSAGE0'], 'paypal-for-woocommerce-multi-account-management'); ?></p>
                        </div>
                        <?php
                        return false;
                    } else {
                        ?><div class="notice notice-error is-dismissible">
                            <p><?php _e('PayPal api credentials are incorrect.', 'paypal-for-woocommerce-multi-account-management'); ?></p>
                        </div>
                        <?php
                        return false;
                    }
                }
            }


            $microprocessing_key_array = array('woocommerce_paypal_express_testmode', 'woocommerce_paypal_express_account_name', 'woocommerce_paypal_express_sandbox_api_username', 'woocommerce_paypal_express_sandbox_api_password', 'woocommerce_paypal_express_sandbox_api_signature', 'woocommerce_paypal_express_api_username', 'woocommerce_paypal_express_api_password', 'woocommerce_paypal_express_api_signature');
            if (empty($_POST['is_edit'])) {
                $my_post = array(
                    'post_title' => wp_strip_all_tags($_POST['woocommerce_paypal_express_account_name']),
                    'post_content' => '',
                    'post_status' => 'publish',
                    'post_author' => get_current_user_id(),
                    'post_type' => 'microprocessing'
                );
                $post_id = wp_insert_post($my_post);
            } else {
                $my_post = array(
                    'ID' => $_POST['is_edit'],
                    'post_title' => wp_strip_all_tags($_POST['woocommerce_paypal_express_account_name']),
                    'post_content' => '',
                );
                wp_update_post($my_post);
                $post_id = $_POST['is_edit'];
            }

            foreach ($microprocessing_key_array as $index => $microprocessing_key) {
                if (!empty($_POST[$microprocessing_key])) {
                    update_post_meta($post_id, $microprocessing_key, trim($_POST[$microprocessing_key]));
                } else {
                    update_post_meta($post_id, $microprocessing_key, '');
                }
            }
            ?>
            <?php
            if (!empty($_POST['is_edit'])) {
                $redirect_url = remove_query_arg(array('action', 'ID'));
                wp_redirect(add_query_arg('success', true, $redirect_url));
                exit();
            } else {
                echo sprintf('<div class=" notice notice-success is-dismissible"><p>%1$s</p></div>', __('Your settings have been saved.', 'paypal-for-woocommerce-multi-account-management'));
            }
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
