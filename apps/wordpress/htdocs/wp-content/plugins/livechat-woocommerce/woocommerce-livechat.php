<?php
/**
 * Plugin Name: LiveChat WooCommerce
 * Plugin URI: http://www.livechatinc.com/integrations/cms/woocommerce
 * Description: Live chat software for live help, online sales and customer support. This plugin allows to quickly install LiveChat on any WooCommerce website.
 * Version: 2.1.2
 * Author: LiveChat
 * Author URI: http://www.livechatinc.com
 * Text Domain: livechat-woocommerce
 * Domain Path: /languages
 *
 * Copyright: © 2015 LiveChat.
 * License: GNU General Public License v3.0
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 */

require_once dirname( __FILE__ ) . '/vendor/autoload.php';
require_once dirname( __FILE__ ) . '/config.php';

/**
 * Uninstall hook handler.
 *
 * @throws \WooLiveChat\Exceptions\ApiClientException Can be thrown by uninstall_hook_header method.
 * @throws \WooLiveChat\Exceptions\InvalidTokenException Can Can be thrown by uninstall_hook_header method.
 */
function woo_uninstall_hook_handler() {
	\WooLiveChat\LiveChatAdmin::woo_uninstall_hook_handler();
}

if ( is_admin() ) {
	require_once dirname( __FILE__ ) . '/plugin_files/LiveChatAdmin.class.php';
	\WooLiveChat\LiveChatAdmin::get_instance();

	register_uninstall_hook( __FILE__, 'woo_uninstall_hook_handler' );
} else {
	require_once dirname( __FILE__ ) . '/plugin_files/LiveChat.class.php';
	\WooLiveChat\LiveChat::get_instance();
}
