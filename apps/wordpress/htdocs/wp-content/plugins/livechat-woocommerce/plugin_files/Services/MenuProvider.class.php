<?php
/**
 * Class MenuProvider
 *
 * @package WooLiveChat\Services
 */

namespace WooLiveChat\Services;

use WooLiveChat\Services\Templates\ResourcesTabTemplate;

/**
 * Class MenuProvider
 *
 * @package WooLiveChat\Services
 */
class MenuProvider {
	/**
	 * Instance of MenuProvider.
	 *
	 * @var MenuProvider|null
	 */
	private static $instance = null;

	/**
	 * Instance of User.
	 *
	 * @var User|null
	 */
	private $user;

	/**
	 * Instance of Store.
	 *
	 * @var Store|null
	 */
	private $store;

	/**
	 * Instance of SettingsProvider.
	 *
	 * @var SettingsProvider|null
	 */
	private $settings;

	/**
	 * Instance of ResourcesTabTemplate.
	 *
	 * @var ResourcesTabTemplate|null
	 */
	private $resources_tab;

	/**
	 * Plugin URL;
	 *
	 * @var string
	 */
	private $plugin_url;

	/**
	 * MenuProvider constructor.
	 *
	 * @param User                 $user          Instance of User.
	 * @param Store                $store         Instance of Store.
	 * @param SettingsProvider     $settings      Instance of SettingsProvider.
	 * @param ResourcesTabTemplate $resources_tab Instance of ResourcesTabTemplate.
	 * @param string               $plugin_url    Plugin URL.
	 */
	public function __construct( $user, $store, $settings, $resources_tab, $plugin_url ) {
		$this->user          = $user;
		$this->store         = $store;
		$this->settings      = $settings;
		$this->resources_tab = $resources_tab;
		$this->plugin_url    = $plugin_url;
	}

	/**
	 * Registers admin menu.
	 */
	public function init() {
		add_action( 'admin_menu', array( $this, 'register_admin_menu' ) );
	}

	/**
	 * Registers plugin menu in WP menu bar.
	 */
	public function register_admin_menu() {
		add_menu_page(
			'LiveChat',
			$this->is_installed() ? 'LiveChat' : 'LiveChat <span class="awaiting-mod">!</span>',
			'administrator',
			'livechat',
			array( $this, 'livechat_settings_page' ),
			$this->plugin_url . 'images/livechat-icon.svg'
		);

		add_submenu_page(
			'livechat',
			__( 'Settings', 'woocommerce-livechat' ),
			__( 'Settings', 'woocommerce-livechat' ),
			'administrator',
			'livechat_settings',
			array( $this, 'livechat_settings_page' )
		);

		add_submenu_page(
			'livechat',
			__( 'Resources', 'woocommerce-livechat' ),
			__( 'Resources', 'woocommerce-livechat' ),
			'administrator',
			'livechat_resources',
			array( $this, 'livechat_resources_page' )
		);

		// Remove the submenu that is automatically added.
		if ( function_exists( 'remove_submenu_page' ) ) {
			remove_submenu_page( 'livechat', 'livechat' );
		}

		// Settings link.
		add_filter( 'plugin_action_links', array( $this, 'livechat_settings_link' ), 10, 2 );

		if ( $this->has_user_token() ) {
			add_submenu_page(
				'livechat',
				__( 'Go to LiveChat', 'woocommerce-livechat' ),
				__( 'Go to LiveChat', 'woocommerce-livechat' ),
				'administrator',
				'livechat_link',
				'__return_false'
			);

			add_filter( 'clean_url', array( $this, 'go_to_livechat_link' ), 10, 2 );
		}
	}

	/**
	 * Renders settings page.
	 */
	public function livechat_settings_page() {
		$this->settings->render();
	}

	/**
	 * Renders resources page.
	 */
	public function livechat_resources_page() {
		$this->resources_tab->render();
	}

	/**
	 * Returns link to LiveChat setting page.
	 *
	 * @param array  $links Array with links.
	 * @param string $file File name.
	 *
	 * @return mixed
	 */
	public function livechat_settings_link( $links, $file ) {
		if ( basename( $file ) !== 'woocommerce-livechat.php' ) {
			return $links;
		}

		$settings_link = sprintf( '<a href="admin.php?page=livechat_settings">%s</a>', __( 'Settings' ) );
		array_unshift( $links, $settings_link );
		return $links;
	}

	/**
	 * Opens Agent App in new tab
	 *
	 * @param string $current_url URL of current menu page.
	 *
	 * @return string
	 */
	public function go_to_livechat_link( $current_url ) {
		if ( 'admin.php?page=livechat_link' === $current_url ) {
			$current_url = LC_AA_URL;
		}

		return $current_url;
	}

	/**
	 * Returns true if LiveChat store token is set (not empty string),
	 * false otherwise.
	 *
	 * @return bool
	 */
	private function is_installed() {
		return ! empty( $this->store->get_store_token() );
	}

	/**
	 * Checks if current WP user has LC account.
	 *
	 * @return bool
	 */
	private function has_user_token() {
		return ! empty( $this->user->get_current_user_token() );
	}

	/**
	 * Returns instance of MenuProvider (singleton pattern).
	 *
	 * @return MenuProvider|null
	 */
	public static function get_instance() {
		if ( ! isset( static::$instance ) ) {
			static::$instance = new static(
				User::get_instance(),
				Store::get_instance(),
				SettingsProvider::get_instance(),
				ResourcesTabTemplate::create(),
				ModuleConfiguration::get_instance()->get_plugin_url()
			);
		}

		return static::$instance;
	}
}
