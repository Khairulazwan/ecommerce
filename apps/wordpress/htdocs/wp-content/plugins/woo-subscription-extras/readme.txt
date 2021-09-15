=== WooCommerce Subscriptions Extras ===
Contributors: moiseh
Tags: woocommerce, subscriptions, bulk, price, pricing, update
Requires at least: 4.8
Tested up to: 5.5.1
Stable tag: 1.0.23
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

== Description ==

This plugin offers some extra facilities and missing features for the WooCommerce Subscriptions official extension.

**Attention: This plugin requires the [WooCommerce Subscriptions](https://woocommerce.com/products/woocommerce-subscriptions/) extension to work correctly.**

Feel free to report bugs and provide new ideas.

[youtube https://youtu.be/v-USSaNHMxE ]

Free version features:

* Update all subscriptions (and taxes) when change the product price
* Recalculate all subscriptions when change related shipping methods
* Change the Start date of specific subscription products
* Change the Next Payment date of specific subscription products

== Installation ==

1. Upload `woo-subscription-extras.zip` to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. It's done. Now you can go to the Settings to configure what you want

== Screenshots ==

1. Question asked when product price that have active subscriptions change
2. Extra field added in product to change the start date of new subscription

== Changelog ==

= 1.0.23 =
* Added "Next Payment" date for subscription products
* Fixed session issue happening in specific WordPress installs

= 1.0.22 =
* Force requiring wc-cart-functions woocommerce file to prevent fatal errors in specific cases
* Improved internationalization

= 1.0.21 =
* Removing unwanted admin notices

= 1.0.20 =
* Tested with latest WordPress and WooCommerce versions

= 1.0.19 =
* Removing deprecated PHP short_open_tag blocks

= 1.0.18 =
* Reversed changelog ordering to make it more standard
* Changed plugin notices to respect the guidelines

= 1.0.17 =
* Emulating the correct customer when calculate shipping and taxes
* Optionally ignore customer taxes when calculate prices

= 1.0.16 =
* Updated WC tested up to tag
* Added compatibility with WooCommerce Admin
* Added support to calculate prices with coupons

= 1.0.15 =
* Added support to update simple products with related subscriptions

= 1.0.14 =
* Added new feature to delay the start date of subscription
* Updated WC tested up to tag

= 1.0.13 =
* Added initial support for variable subscriptions (beta)
* Added Synchronize prices action in product list

= 1.0.12 =
* Fixed issue with shipping cost decimal

= 1.0.11 =
* Fixing bugs to recalculate prices when subscription quantity is greater than one
* Trying to fix reported PHP warnings and other small issues

= 1.0.10 =
* Rolled back to version 1.0.8 as there issues in shipping calculation reported by user

== Frequently Asked Questions ==

= It's secure to let this plugin update my subscription prices? =

It's never guaranteed that something wrong and unexpected can happen with prices when update/synchronize the subscriptions for many different use cases and configurations. So always make sure you have a backup of the database before using this plugin.

= Why my subscription prices became $0 after update? =

This can happen in some situations where subscription looses the database integrity/relation with the original product or variation. So always make a backup before doing updates with this plugin.

= This plugin will calculate all the taxes, fees and shipping correctly? =

For common cases, yes, but there are some situations that it's technically difficult to handle, for example, shipping and/or taxes calculations that require very contextual information that it's difficult to reproduce when doing recalculations.

== Upgrade Notice ==
