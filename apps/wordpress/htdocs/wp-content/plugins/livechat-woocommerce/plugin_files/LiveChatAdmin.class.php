<?php
/**
 * Class LiveChatAdmin
 *
 * @package WooLiveChat
 */

namespace WooLiveChat;

use Exception;
use WooLiveChat\Services\ApiClient;
use WooLiveChat\Services\CertProvider;
use WooLiveChat\Services\ConnectTokenProvider;
use WooLiveChat\Services\Options\ReviewNoticeOptions;
use WooLiveChat\Services\Store;
use WooLiveChat\Services\User;
use WooLiveChat\Services\SetupProvider;
use WooLiveChat\Services\MenuProvider;
use WooLiveChat\Services\SettingsProvider;
use WooLiveChat\Services\NotificationsRenderer;

/**
 * Class LiveChatAdmin
 *
 * @package WooLiveChat
 */
final class LiveChatAdmin extends LiveChat {
	/**
	 * Starts the plugin
	 */
	public function __construct() {
		parent::__construct();

		add_action(
			'activated_plugin',
			array(
				$this,
				'plugin_activated_action_handler',
			)
		);

		add_filter( 'auto_update_plugin', array( $this, 'auto_update' ), 10, 2 );
	}

	/**
	 * Enables auto-update for LC plugin.
	 *
	 * @param bool   $update Default WP API response.
	 * @param object $item Plugin's slug.
	 * @return bool
	 */
	public function auto_update( $update, $item ) {
		return PLUGIN_SLUG === $item->slug ? true : $update;
	}

	/**
	 * Returns instance of LiveChat class (singleton pattern).
	 *
	 * @return LiveChat
	 * @throws Exception Can be thrown by constructor.
	 */
	public static function get_instance() {
		if ( ! isset( static::$instance ) ) {
			static::$instance = new static();
		}

		return static::$instance;
	}

	/**
	 * Inits basic services.
	 */
	public function init_services() {
		if ( ! is_super_admin() ) {
			return;
		}

		if ( class_exists( 'LiveChat\LiveChatAdmin' ) ) {
			NotificationsRenderer::get_instance_for_plugins_conflict()->init();
			return;
		}

		SetupProvider::get_instance()->init();
		MenuProvider::get_instance()->init();
		SettingsProvider::get_instance()->init();
		NotificationsRenderer::get_instance()->init();
	}

	/**
	 * Handles plugin activated action - redirects to plugin setting page.
	 *
	 * @param string $plugin Plugin slug.
	 */
	public function plugin_activated_action_handler( $plugin ) {
		if ( 'livechat-woocommerce/woocommerce-livechat.php' !== $plugin ) {
			return;
		}

		wp_safe_redirect( admin_url( 'admin.php?page=livechat_settings' ) );
		exit;
	}

	/**
	 * Removes all LiveChat data stored in WP database.
	 * It's called as uninstall hook.
	 */
	public static function woo_uninstall_hook_handler() {
		$store = Store::get_instance();

		if ( ! empty( $store->get_store_token() ) ) {
			try {
				$connect_token = ConnectTokenProvider::create( CertProvider::create() )->get( $store->get_store_token(), 'store' );
				ApiClient::create( $connect_token )->uninstall();
				// phpcs:disable Generic.CodeAnalysis.EmptyStatement.DetectedCatch
			} catch ( Exception $exception ) {
				// Exception during uninstall request is ignored to not break process of plugin uninstallation.
			}
			// phpcs:enable

			$store->remove_store_data();
		}

		User::get_instance()->remove_authorized_users();
		CertProvider::create()->remove_stored_cert();
		ReviewNoticeOptions::get_instance()->clear();
	}
}
