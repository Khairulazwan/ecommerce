<?php
/**
 * Class ScriptsProvider
 *
 * @package WooLiveChat\Services
 */

namespace WooLiveChat\Services;

/**
 * Class ScriptsProvider
 *
 * @package WooLiveChat\Services
 */
class SetupProvider {
	/**
	 * Instance of SetupProvider.
	 *
	 * @var SetupProvider|null
	 */
	private static $instance = null;

	/**
	 * Plugin URL.
	 *
	 * @var string
	 */
	private $plugin_url;

	/**
	 * Current plugin version.
	 *
	 * @var string
	 */
	private $plugin_version;

	/**
	 * ScriptsProvider constructor.
	 *
	 * @param string $plugin_url     Plugin URL.
	 * @param string $plugin_version Current plugin version.
	 */
	public function __construct( $plugin_url, $plugin_version ) {
		$this->plugin_url     = $plugin_url;
		$this->plugin_version = $plugin_version;
	}

	/**
	 * Loads all necessary scripts and CSS.
	 */
	public function init() {
		add_action( 'admin_init', array( $this, 'load_translations' ) );
		add_action( 'admin_init', array( $this, 'load_menu_icon_styles' ) );
		add_action( 'admin_init', array( $this, 'load_general_scripts_and_styles' ) );
		add_action( 'admin_init', array( $this, 'inject_nonce_object' ) );
	}

	/**
	 * Make translation ready.
	 */
	public function load_translations() {
		load_plugin_textdomain(
			'livechat-woocommerce',
			false,
			'livechat-woocommerce/languages'
		);
	}

	/**
	 * Loads JS scripts and CSS.
	 */
	public function load_general_scripts_and_styles() {
		$this->load_design_system_styles();
		wp_enqueue_script( 'livechat', $this->plugin_url . 'js/livechat.js', 'jquery', $this->plugin_version, true );
		wp_enqueue_style( 'livechat', $this->plugin_url . 'css/livechat-general.css', false, $this->plugin_version );
		wp_enqueue_script( 'bridge', $this->plugin_url . 'js/connect.js', 'jquery', $this->plugin_version, false );

		$config = array(
			'agentAppUrl' => LC_AA_URL,
		);

		wp_localize_script( 'livechat', 'config', $config );
	}

	/**
	 * Fix CSS for icon in menu.
	 */
	public function load_menu_icon_styles() {
		wp_enqueue_style( 'livechat-menu', $this->plugin_url . 'css/livechat-menu.css', false, $this->plugin_version );
	}

	/**
	 * Adds nonce value to AJAX object in JS script.
	 */
	public function inject_nonce_object() {
		$nonce = array(
			'value' => wp_create_nonce( 'wp_ajax_lc_connect' ),
		);

		wp_localize_script( 'bridge', 'ajax_nonce', $nonce );
	}

	/**
	 * Loads CSS.
	 */
	private function load_design_system_styles() {
		// phpcs:disable WordPress.WP.EnqueuedResourceParameters.MissingVersion
		// Files below don't need to be versioned.
		wp_register_style( 'livechat-source-sans-pro-font', 'https://fonts.googleapis.com/css?family=Source+Sans+Pro:400,600' );
		wp_register_style( 'livechat-material-icons', 'https://fonts.googleapis.com/icon?family=Material+Icons' );
		wp_register_style( 'livechat-design-system', 'https://cdn.livechat-static.com/design-system/styles.css' );
		wp_enqueue_style( 'livechat-source-sans-pro-font', false, $this->plugin_version );
		wp_enqueue_style( 'livechat-material-icons', false, $this->plugin_version );
		wp_enqueue_style( 'livechat-design-system', false, $this->plugin_version );
		// phpcs:enable
	}

	/**
	 * Returns instance of SetupProvider (singleton pattern).
	 *
	 * @return SetupProvider|null
	 */
	public static function get_instance() {
		if ( ! isset( static::$instance ) ) {
			$module           = ModuleConfiguration::get_instance();
			static::$instance = new static( $module->get_plugin_url(), $module->get_plugin_version() );
		}

		return static::$instance;
	}
}
