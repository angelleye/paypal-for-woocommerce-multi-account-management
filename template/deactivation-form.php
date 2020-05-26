<?php
defined('ABSPATH') || die('Cheatin&#8217; uh?');
$deactivation_url = wp_nonce_url('plugins.php?action=deactivate&amp;plugin=' . rawurlencode(PFWMA_PLUGIN_BASENAME), 'deactivate-plugin_' . PFWMA_PLUGIN_BASENAME);
?>
<div class="deactivation-Modal-pfwma">
    <div class="deactivation-Modal-pfwma-header">
        <div>
            <button class="deactivation-Modal-pfwma-return deactivation-icon-chevron-left"><?php _e('Return', 'paypal-for-woocommerce-multi-account-management'); ?></button>
            <h2><?php _e('PayPal for WooCommerce Multi-Account Management', 'paypal-for-woocommerce-multi-account-management'); ?></h2>
        </div>
        <button class="deactivation-Modal-pfwma-close deactivation-icon-close"><?php _e('Close', 'paypal-for-woocommerce-multi-account-management'); ?></button>
    </div>
    <div class="deactivation-Modal-pfwma-content">
        <div class="deactivation-Modal-pfwma-question deactivation-isOpen">
            <h3><?php _e('May we have a little info about why you are deactivating?', 'paypal-for-woocommerce-multi-account-management'); ?></h3>
            <ul>
                <li>
                    <input type="radio" name="reason" id="reason-temporary" value="Temporary Deactivation">
                    <label for="reason-temporary"><?php _e('<strong>It is a temporary deactivation.</strong> I am just debugging an issue.', 'paypal-for-woocommerce-multi-account-management'); ?></label>
                </li>
                <li>
                    <input type="radio" name="reason" id="reason-broke" value="Broken Layout">
                    <label for="reason-broke"><?php _e('The plugin <strong>broke my layout</strong> or some functionality.', 'paypal-for-woocommerce-multi-account-management'); ?></label>
                </li>
                <li>
                    <input type="radio" name="reason" id="reason-complicated" value="Complicated">
                    <label for="reason-complicated"><?php _e('The plugin is <strong>too complicated to configure.</strong>', 'paypal-for-woocommerce-multi-account-management'); ?></label>
                </li>
                <li>
                    <input type="radio" name="reason" id="reason-other" value="Other">
                    <label for="reason-other"><?php _e('Other', 'paypal-for-woocommerce-multi-account-management'); ?></label>
                    <div class="deactivation-Modal-pfwma-fieldHidden">
                        <textarea name="reason-other-details" id="reason-other-details" placeholder="<?php _e('Let us know why you are deactivating PayPal Invoicing for WordPress so we can improve the plugin', 'paypal-for-woocommerce-multi-account-management'); ?>"></textarea>
                    </div>
                </li>
            </ul>
            <input id="deactivation-reason" type="hidden" value="">
            <input id="deactivation-details" type="hidden" value="">
        </div>
    </div>
    <div class="deactivation-Modal-pfwma-footer">
        <div>
            <a href="<?php echo esc_attr($deactivation_url); ?>" class="button button-primary deactivation-isDisabled" disabled id="mixpanel-send-deactivation-pfwma"><?php _e('Send & Deactivate', 'paypal-for-woocommerce-multi-account-management'); ?></a>
            <button class="deactivation-Modal-pfwma-cancel"><?php _e('Cancel', 'paypal-for-woocommerce-multi-account-management'); ?></button>
        </div>
        <a href="<?php echo esc_attr($deactivation_url); ?>" class="button button-secondary"><?php _e('Skip & Deactivate', 'paypal-for-woocommerce-multi-account-management'); ?></a>
    </div>
</div>
<div class="deactivation-Modal-pfwma-overlay"></div>
