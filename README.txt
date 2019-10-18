=== PayPal for WooCommerce Multi-Account Management ===
Contributors: (angelleye)
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=SG9SQU2GBXJNA
Tags: paypal, woocommerce, express checkout, micro payments, micro processing, micropayments, microprocessing
Requires at least: 3.0.1
Tested up to: 5.2.2
Stable tag: 1.1.4
License: GPLv3
License URI: http://www.gnu.org/licenses/gpl-3.0.html

Adds the ability to configure multiple PayPal accounts for use with WooCommerce based on rules provided.

== Description ==

= Introduction =

Easily configure multiple PayPal accounts for use with your WooCommerce store.

 * Process low dollar orders with a MicroPayments account and high dollar orders with a MacroPayments account.

= PayPal MicroPayments vs. PayPal MacroPayments =
Most PayPal accounts are considered "MacroPayments" accounts, which means you will be charged the standard fees of 2.9% + .30 USD per transaction.

PayPal also provides "MicroPayments" accounts, which are designed for low price orders (typically $12 or less).  These types of accounts charge fees at 5% + .05 USD, which will be cheaper for these low price orders.

If you are selling both high priced and low priced products on your site, you may want to utilize both accounts so that you can always get the lowest fee charged possible.

This plugin allows you to configure multiple accounts and provide rules for when to use each account based on order data.

== Changelog ==

= 1.1.5 - **.**.2019 =
* Feature - Adds Express Checkout - Parallel Payments Compatibility. [PFWMA-13] (https://github.com/angelleye/paypal-for-woocommerce-multi-account-management/pull/17))
* Feature - Adds Express Checkout - Parallel Refund Compatibility. [PFWMA-13] (https://github.com/angelleye/paypal-for-woocommerce-multi-account-management/pull/18))
* Tweak - Adjustments to multi-account UI. [PFWMA-63] (https://github.com/angelleye/paypal-for-woocommerce-multi-account-management/pull/19))
* Fix - Resolves an issue where Parallel Payments not working when skip final review is disable in express checkout. ([PFWMA-66] (https://github.com/angelleye/paypal-for-woocommerce-multi-account-management/pull/20))

= 1.1.4 - 07.19.2019 =
* Fix - Resolves a PHP notice showing up in email receipts with some orders. [PFWMA-38] (https://github.com/angelleye/paypal-for-woocommerce-multi-account-management/pull/5))
* Fix - Resolves a bad link in the plugin action links. [PFWMA-44] (https://github.com/angelleye/paypal-for-woocommerce-multi-account-management/pull/7))
* Fix - Resolves an issue with account edit mode. ([PFWMA-9](https://github.com/angelleye/paypal-for-woocommerce-multi-account-management/pull/6))
* Fix - Resolves PHP Error with Subscription Renewal. ([PFWMA-52](https://github.com/angelleye/paypal-for-woocommerce-multi-account-management/pull/10))
* Fix - Resolves PHP Error with Payflow Authorization. ([PFWMA-54](https://github.com/angelleye/paypal-for-woocommerce-multi-account-management/pull/11))
* Fix - Resolves an issue with Express Checkout tokenization payment. ([PFWMA-55](https://github.com/angelleye/paypal-for-woocommerce-multi-account-management/pull/12))
* Tweak - Adjustments to rule builder default values. [PFWMA-42] (https://github.com/angelleye/paypal-for-woocommerce-multi-account-management/pull/4))
* Tweak - Updates AE Updater install URL. [PFWMA-37](https://github.com/angelleye/paypal-for-woocommerce-multi-account-management/pull/2))
* Tweak - Adjusts link for Activate and Download for PFW. ([PFWMA-40](https://github.com/angelleye/paypal-for-woocommerce-multi-account-management/pull/8))

= 1.1.3.1 - 01.16.2019 =
* Tweak - Updates WooCommerce tested version to show compatibility. [PFWMA-31]

= 1.1.3 - 12.08.2018 =
* Fix - Resolves PHP errors on some sites when activating the plugin. [PFWMA-30]

= 1.1.2 - 10.11.2018 =
* Tweak - Adds rules from PayFlow that were not included in Express Checkout. [PFWMA-25]
* Tweak - Removes the Card Type option from Express Checkout, which is not applicable.  [PFWMA-26]
* Fix - Resolves a problem where live API git credentials were not displaying correctly after setup. [PFWMA-23]
* Fix - Resolves PHP errors with some admin notices about required plugins. [PFWMA-6]

= 1.1.1 - 08.31.2018 =
* Feature - Adds Card Type, Currency Code, and Buyer Country based condition triggers. [PFWMA-4][PFWMA-11]
* Feature - Adds PayFlow Compatibility. [PFWMA-13]
* Tweak - Adjusts layout of condition trigger builder. [PFWMA-5]
* Fix - Resolves an issue where some original condition triggers no longer worked after new condition triggers were added in last update. [PFWMA-21]

= 1.1.0 - 08.16.2018 =
* Feature - Adds User Role based condition triggers. [PFWMA-8]
* Feature - Adds product based condition triggers. [PFWMA-10]
* Fix - Resolves a problem where the rules would not trigger correctly based on specific scenarios. [PFWMA-3][PFWMA-9]
* Fix - Resolves a PHP warning related to countable array data. [PFWMA-7]

= 1.0.2 - 01.02.2018 =
* Tweak - Adds better error details if you try to add an account with incorrect API credentials. ([#9](https://bitbucket.org/angelleye/paypal-for-woocommerce-multi-account-management/issues/9/accounts-are-not-getting-added-correctly))
* Tweak - Adjustments to columns for the account list data that is displayed. ([#10](https://bitbucket.org/angelleye/paypal-for-woocommerce-multi-account-management/issues/10/update-columns-for-list-of-accounts))
* Fix - Resolves an issue where refunds would sometimes not correctly process from secondary accounts through WooCommerce. ([#11](https://bitbucket.org/angelleye/paypal-for-woocommerce-multi-account-management/issues/11/refunds-are-not-working-properly))

= 1.0.1 - 11.16.2017 =
* Fix - Minor bug fixes.

= 1.0.0 - 10.12.2017 =
* Initial stable release.