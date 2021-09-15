<?php
/**
 * Class LiveChat
 *
 * @package WooLiveChat
 */

namespace WooLiveChat;

use WooLiveChat\Services\LicenseProvider;
use WooLiveChat\Services\ModuleConfiguration;
use WooLiveChat\Services\Templates\TrackingCodeTemplate;
use WooLiveChat\Services\WidgetProvider;

/**
 * Class LiveChat
 */
class LiveChat {
	/**
	 * Singleton pattern
	 *
	 * @var LiveChat $instance
	 */
	protected static $instance;

	/**
	 * Instance of ModuleConfiguration class
	 *
	 * @var ModuleConfiguration|null
	 */
	protected $module = null;

	/**
	 * LiveChat account login
	 *
	 * @var string|null $login
	 */
	protected $login = null;

	/**
	 * Starts the plugin
	 */
	public function __construct() {
		add_action(
			'plugins_loaded',
			function () {
				$widget = WidgetProvider::get_instance();
				add_action( 'wp_ajax_lc-refresh-cart', array( $widget, 'ajax_get_customer_tracking' ) );
				add_action( 'wp_ajax_nopriv_lc-refresh-cart', array( $widget, 'ajax_get_customer_tracking' ) );
				$this->init_services();
			}
		);
	}

	/**
	 * Inits basic services.
	 */
	public function init_services() {
		if ( class_exists( 'LiveChat\LiveChat' ) ) {
			return;
		}

		if ( LicenseProvider::create()->has_deprecated_license_number() ) {
			add_action( 'wp_head', array( TrackingCodeTemplate::create(), 'render' ) );
		} else {
			add_action( 'wp_enqueue_scripts', array( WidgetProvider::get_instance(), 'set_widget' ) );
		}
	}

	/**
	 * Singleton pattern
	 *
	 * @return LiveChat
	 */
	public static function get_instance() {
		if ( ! isset( static::$instance ) ) {
			static::$instance = new static();
		}

		return static::$instance;
	}
}
