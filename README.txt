=== PayPal for WooCommerce Multi-Account Management ===
Contributors: (angelleye)
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=SG9SQU2GBXJNA
Tags: paypal, woocommerce, express checkout, micro payments, micro processing, micropayments, microprocessing
Requires at least: 3.0.1
Tested up to: 4.9.8
Stable tag: 1.1.0
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

= 1.1.1 - xx.xx.2018 =
* Feature - Adds Card Type and Currency Code condition triggers. [PFWMA-4]
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