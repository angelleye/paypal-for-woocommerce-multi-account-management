=== PayPal for WooCommerce Multi-Account Management ===
Contributors: (angelleye)
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=SG9SQU2GBXJNA
Tags: paypal, woocommerce, express checkout, micro payments, micro processing, micropayments, microprocessing
Requires at least: 5.0
Tested up to: 6.5.5
Stable tag: 4.0.2
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

= 4.0.2 - 06.28.2024 =
* Fix -  Resolves Shipping Address issue with DE country. ([PFWMA-311](https://github.com/angelleye/paypal-for-woocommerce-multi-account-management/pull/121))

= 4.0.1 - 06.13.2024 =
* Feature - Adds vendor compatibility with PPCP. ([PFWMA-309](https://github.com/angelleye/paypal-for-woocommerce-multi-account-management/pull/120))

= 4.0.0 - 06.11.2024 =
* Feature - Adds PPCP compatibility. ([PFWMA-270](https://github.com/angelleye/paypal-for-woocommerce-multi-account-management/pull/119))

= 3.2.5 - 12.18.2023 =
* Feature - Adds HPOS compatibility. ([PFWMA-305](https://github.com/angelleye/paypal-for-woocommerce-multi-account-management/pull/118))

= 3.2.4 - 07.17.2023 =
* Fix -  Resolves Shipping Zone rule(s) are not triggering properly. ([PFWMA-299](https://github.com/angelleye/paypal-for-woocommerce-multi-account-management/pull/117))

= 3.2.3 - 06.06.2023 =
* Fix -  Resolves Tax calculation issue. ([PFWMA-298](https://github.com/angelleye/paypal-for-woocommerce-multi-account-management/pull/116))

= 3.2.2 - 06.05.2023 =
* Fix - Compatibility - YITH Booking and Appointment for WooCommerce Premium. ([PFWMA-297](https://github.com/angelleye/paypal-for-woocommerce-multi-account-management/pull/115))

= 3.2.1 - 02.23.2023 =
* Fix - Checks for clean array data before looping through it. ([PFWMA-294](https://github.com/angelleye/paypal-for-woocommerce-multi-account-management/pull/114))

= 3.2.0 - 02.20.2023 =
* Feature - Show the split payment summary on the order details page. ([PFWMA-293](https://github.com/angelleye/paypal-for-woocommerce-multi-account-management/pull/113))
* Feature - New tab to show the total payments received by the PayPal accounts. ([PFWMA-293](https://github.com/angelleye/paypal-for-woocommerce-multi-account-management/pull/113))

= 3.1.9 - 12.21.2022 =
* Fix - Resolves Js validation for Load Balancer. ([PFWMA-289](https://github.com/angelleye/paypal-for-woocommerce-multi-account-management/pull/112))

= 3.1.8 - 12.07.2022 =
* Feature - Adds Rules based on Variable Product Variations. ([PFWMA-172](https://github.com/angelleye/paypal-for-woocommerce-multi-account-management/pull/110))
* Tweak - Rule Manager Improvements. ([PFWMA-281](https://github.com/angelleye/paypal-for-woocommerce-multi-account-management/pull/111))

= 3.1.7 - 07.19.2022 =
 * Fix - Resolves Classic EC - Taxes are not passing correctly to PayPal when secondary rule is triggered. ([PFWMA-283](https://github.com/angelleye/paypal-for-woocommerce-multi-account-management/pull/109))

= 3.1.6 - 01.03.2021 =
* Feature - Adds Rules Based on State. ([PFWMA-266](https://github.com/angelleye/paypal-for-woocommerce-multi-account-management/pull/104))

= 3.1.5 - 18.10.2021 =
* Feature - Adds Automatically Disable Account from Load Balancer if it is Suspended. ([PFWMA-222](https://github.com/angelleye/paypal-for-woocommerce-multi-account-management/pull/98))

= 3.1.4 - 09.30.2021 =
* Feature - Adds Dokan 3.0.0 Refund Compatibility. ([PFWMA-247](https://github.com/angelleye/paypal-for-woocommerce-multi-account-management/pull/103))

= 3.1.3 - 09.13.2021 =
* Fix - Resolves discount calculation issue. ([PFWMA-254](https://github.com/angelleye/paypal-for-woocommerce-multi-account-management/pull/102))

= 3.1.2 - 07.19.2021 =
* Tweak - Adjustment to Send line item details to PayPal. ([PFWMA-242](https://github.com/angelleye/paypal-for-woocommerce-multi-account-management/pull/100))

= 3.1.1 - 07.12.2021 =
* Fix - Resolves delete link issue on rule listing page. ([PFWMA-240](https://github.com/angelleye/paypal-for-woocommerce-multi-account-management/pull/99))

= 3.1.0 - 05.25.2021 =
* Feature - Adds Smart Commissions. ([PFWMA-229](https://github.com/angelleye/paypal-for-woocommerce-multi-account-management/pull/95))

= 3.0.19 - 05.17.2021 =
* Feature - Adds Rules Based on Shipping Zone. ([PFWMA-120](https://github.com/angelleye/paypal-for-woocommerce-multi-account-management/pull/94))
* Fix - Resolves PayPal Splits Shipping Tax Amount within multivendor orders. ([PFWMA-228](https://github.com/angelleye/paypal-for-woocommerce-multi-account-management/pull/93))

= 3.0.18 - 04.26.2021 =
* Tweak - Updates menu link. ([PFWMA-221](https://github.com/angelleye/paypal-for-woocommerce-multi-account-management/pull/86))
* Fix - Resolves $0.00 Orders Failing in Parallel Payment. ([PFWMA-224](https://github.com/angelleye/paypal-for-woocommerce-multi-account-management/pull/90))
* Fix - Resolves typos in Accounts list. ([PFWMA-226](https://github.com/angelleye/paypal-for-woocommerce-multi-account-management/pull/89))

= 3.0.17 - 04.13.2021 =
* Tweak - Remove order total Max limit. ([PFWMA-220](https://github.com/angelleye/paypal-for-woocommerce-multi-account-management/pull/85))

= 3.0.16 - 03.30.2021 =
* Feature - Adds Woocommerce Shipping Per Product compatibility. ([PFWMA-210](https://github.com/angelleye/paypal-for-woocommerce-multi-account-management/pull/82))

= 3.0.15 - 03.22.2021 =
* Fix - Resolves PayPal Splits Shipping Tax Amount within multivendor orders. ([PFWMA-201](https://github.com/angelleye/paypal-for-woocommerce-multi-account-management/pull/81))

= 3.0.14 - 02.14.2021 =
* Fix - Resolves PHP notices. ([PFWMA-208](https://github.com/angelleye/paypal-for-woocommerce-multi-account-management/pull/80))

= 3.0.13 - 01.11.2021 =
* Fix - Resolves Multi-Account split does not work unless smart buttons are enabled. ([PFWMA-200](https://github.com/angelleye/paypal-for-woocommerce-multi-account-management/pull/79))
* Feature - Adds Create rules that will always be triggered. ([PFWMA-185](https://github.com/angelleye/paypal-for-woocommerce-multi-account-management/pull/78))

= 3.0.12 - 11.19.2020 =
* Fix - Resolves Shipping Cost issue. ([PFWMA-194](https://github.com/angelleye/paypal-for-woocommerce-multi-account-management/pull/77))

= 3.0.11 - 10.19.2020 =
* Fix - Resolves Checkout Custom Field issue. ([PFWMA-192](https://github.com/angelleye/paypal-for-woocommerce-multi-account-management/pull/76))

= 3.0.10 - 10.15.2020 =
* Tweak - Button Label Adjustment. ([PFWMA-190](https://github.com/angelleye/paypal-for-woocommerce-multi-account-management/pull/74))

= 3.0.9 - 10.13.2020 =
* Feature - Adds Extra Fees compatibility. ([PFWMA-186](https://github.com/angelleye/paypal-for-woocommerce-multi-account-management/pull/72))
* Fix - Resolves PHP notices. ([PFWMA-188](https://github.com/angelleye/paypal-for-woocommerce-multi-account-management/pull/73))

= 3.0.8 - 10.05.2020 =
* Feature - Added Custom Field Based Rules. ([PFWMA-7](https://github.com/angelleye/paypal-for-woocommerce-multi-account-management/pull/71))
* Fix - Updates discount related logic. ([PFWMA-176](https://github.com/angelleye/paypal-for-woocommerce-multi-account-management/pull/70))

= 3.0.7 - 09.09.2020 =
* Verification - WooCommerce 4.5.0 compatibility.

= 3.0.6 - 09.04.2020 =
* Verification - WooCommerce 4.4.1 and WordPress 5.5.1 compatibility.

= 3.0.5 - 08.11.2020 =
* Feature - Create rules based on buyer postal code. ([PFWMA-168](https://github.com/angelleye/paypal-for-woocommerce-multi-account-management/pull/69))
* Feature - Create rules for existing vendors on plugin install for WC Vendors & Dokan Vendor. ([PFWMA-167](https://github.com/angelleye/paypal-for-woocommerce-multi-account-management/pull/68))

= 3.0.4 - 08.01.2020 =
* Tweak - Add/Edit Rules UI improvement for a better UX. ([PFWMA-165](https://github.com/angelleye/paypal-for-woocommerce-multi-account-management/pull/67))

= 3.0.3 - 07.23.2020 =
* Fix - Order refund issue resolved. ([PFWMA-164](https://github.com/angelleye/paypal-for-woocommerce-multi-account-management/pull/63))
* Fix - Shipping cost split issue resolved between receivers. ([PFWMA-158](https://github.com/angelleye/paypal-for-woocommerce-multi-account-management/pull/65))
* Tweak - Handle Large User and Product data listing on add/edit rule page. ([PFWMA-156](https://github.com/angelleye/paypal-for-woocommerce-multi-account-management/pull/61))
* Tweak - Warn admins to add conditions for the new rules. ([PFWMA-159](https://github.com/angelleye/paypal-for-woocommerce-multi-account-management/pull/64))

= 3.0.2 - 07.13.2020 =
* Tweak - Refund commission amount as well on order refund. ([PFWMA-162](https://github.com/angelleye/paypal-for-woocommerce-multi-account-management/pull/62))

= 3.0.1 - 06.29.2020 =
* Tweak - Shipping & Tax amount in Owner Commission Calculation. ([PFWMA-146](https://github.com/angelleye/paypal-for-woocommerce-multi-account-management/pull/60))
* Tweak - Delete the author rules when admin removes user from WordPress([PFWMA-142](https://github.com/angelleye/paypal-for-woocommerce-multi-account-management/pull/59))
* Fix - Dokan payout compatibility issue resolved. ([PFWMA-149](https://github.com/angelleye/paypal-for-woocommerce-multi-account-management/pull/58))

= 3.0.0 - 06.07.2020 =
* Feature - WC Vendors & Dokan Compatibility Added. ([PFWMA-125](https://github.com/angelleye/paypal-for-woocommerce-multi-account-management/pull/55))
* Feature - Added Settings to Enable/Disable Auto Rule Creation for WC Vendors & Dokan. ([PFWMA-131](https://github.com/angelleye/paypal-for-woocommerce-multi-account-management/pull/52))
* Tweak - Handle the Partially Successful Orders and Notify Admin & User. ([PFWMA-124](https://github.com/angelleye/paypal-for-woocommerce-multi-account-management/pull/54))
* Tweak - Separated the Account Rule Listing, Add/Edit Rule and Settings Page. ([PFWMA-130](https://github.com/angelleye/paypal-for-woocommerce-multi-account-management/pull/53))
* Tweak - User feedback form added on plugin deactivation. ([PFWMA-133](https://github.com/angelleye/paypal-for-woocommerce-multi-account-management/pull/51))
* Tweak - Added the Plugin Listing Sidebar. ([PFWMA-116](https://github.com/angelleye/paypal-for-woocommerce-multi-account-management/pull/50))
* Fix - Send a single payment to every secondary account in a single order. ([PFWMA-122](https://github.com/angelleye/paypal-for-woocommerce-multi-account-management/pull/56))

= 2.2.0 - 05.04.2020 =
* Feature - Create new rules based on WooCommerce Shipping Classes. ([PFWMA-114](https://github.com/angelleye/paypal-for-woocommerce-multi-account-management/pull/48))
* Fix - VAT Calculation issue resolved. ([PFWMA-112](https://github.com/angelleye/paypal-for-woocommerce-multi-account-management/pull/47))
* Tweak - Commission Calculation Adjustments. ([PFWMA-112](https://github.com/angelleye/paypal-for-woocommerce-multi-account-management/pull/49))

= 2.1.4 - 04.07.2020 =
* Verification - WooCommerce 4.0.1 and WordPress 5.4 compatibility.

= 2.1.3 - 03.29.2020 =
* Fix - Resolves the issue to match with all rules instead of picking random 10 rules. ([PFWMA-105](https://github.com/angelleye/paypal-for-woocommerce-multi-account-management/pull/45))

= 2.1.2 - 03.27.2020 =
* Tweak - Adjustment admin notice for dependency plugin. ([PFWMA-102](https://github.com/angelleye/paypal-for-woocommerce-multi-account-management/pull/43))

= 2.1.1 - 01.31.2020 =
* Tweak - Adjustment to Currency Symbol with product amount in admin side. ([PFWMA-48](https://github.com/angelleye/paypal-for-woocommerce-multi-account-management/pull/40))
* Tweak - GetPalDetails improvements. ([PFWMA-81](https://github.com/angelleye/paypal-for-woocommerce-multi-account-management/pull/42))
* Fix - Resolves some PHP notices. ([PFWMA-98](https://github.com/angelleye/paypal-for-woocommerce-multi-account-management/pull/41))

= 2.1.0 - 01.09.2020 =
* Feature - Adds Site Owner Commission. ([PFWMA-84](https://github.com/angelleye/paypal-for-woocommerce-multi-account-management/pull/38))
* Tweak - Adjustment to use Send line item details to PayPal. ([PFWMA-96](https://github.com/angelleye/paypal-for-woocommerce-multi-account-management/pull/39))

= 2.0.3 - 12.30.2019 =
* Feature - Adds Split Payments - Product Specific Coupons. ([PFWMA-91](https://github.com/angelleye/paypal-for-woocommerce-multi-account-management/pull/37))
* Tweak - Adjustment to Buyer country field value on edit mode. ([PFWMA-92](https://github.com/angelleye/paypal-for-woocommerce-multi-account-management/pull/33))
* Tweak - Adjustment to Product categories. ([PFWMA-93](https://github.com/angelleye/paypal-for-woocommerce-multi-account-management/pull/35))
* Tweak - Adjustment to Product tags list on edit mode. ([PFWMA-95](https://github.com/angelleye/paypal-for-woocommerce-multi-account-management/pull/36))
* Tweak - Adjustment to Updater plugin notice dismissible. ([PFWMA-88](https://github.com/angelleye/paypal-for-woocommerce-multi-account-management/pull/34))

= 2.0.2 - 12.09.2019 =
* Tweak - Adjustment to Updater plugin notice dismissible. ([PFWMA-88](paypal-for-woocommerce-multi-account-management/pull/30))
* Fix - Resolves an issue with woocommerce variable product. ([PFWMA-90](https://github.com/angelleye/paypal-for-woocommerce-multi-account-management/pull/31))

= 2.0.1 - 11.20.2019 =
* Verification - WooCommerce 3.8 and WordPress 5.3 compatibility.

= 2.0.0 - 11.05.2019 =
* Feature - Adds new hooks for WooCommerce Event Manager Pro compatibility. ([PFWMA-69](https://github.com/angelleye/paypal-woocommerce/pull/23))
* Feature - Adds Express Checkout Parallel Payments. ([PFWMA-13](https://github.com/angelleye/paypal-for-woocommerce-multi-account-management/pull/17)) ([PFWMA-66](https://github.com/angelleye/paypal-for-woocommerce-multi-account-management/pull/20)) ([PFWMA-77](https://github.com/angelleye/paypal-for-woocommerce-multi-account-management/pull/27)) ([PFWMA-78](https://github.com/angelleye/paypal-for-woocommerce-multi-account-management/pull/28))
* Tweak - Adjustments to multi-account UI. ([PFWMA-63](https://github.com/angelleye/paypal-for-woocommerce-multi-account-management/pull/19))
* Tweak - Updates plugin Configure URL. ([PFWMA-67](https://github.com/angelleye/paypal-for-woocommerce-multi-account-management/pull/21))
* Tweak - Adjustments to Settings. ([PFWMA-75](https://github.com/angelleye/paypal-for-woocommerce-multi-account-management/pull/25))
* Tweak - Adds tool tip for Priority. ([PFWMA-82](https://github.com/angelleye/paypal-for-woocommerce-multi-account-management/pull/29))
* Fix - Resolves an issue IP address function and use default woocommerce function. ([PFWMA-73](https://github.com/angelleye/paypal-for-woocommerce-multi-account-management/pull/24))
* Fix - Resolves Settings - PHP Notices. ([PFWMA-76](https://github.com/angelleye/paypal-for-woocommerce-multi-account-management/pull/26))

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