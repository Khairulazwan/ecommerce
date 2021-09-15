<?php

/**
 * The core plugin class.
 *
 * This is used to define internationalization, admin-specific hooks, and
 * public-facing site hooks.
 *
 * Also maintains the unique identifier of this plugin as well as the current
 * version of the plugin.
 *
 * @since      1.0.0
 * @package    Woo_Paypal_Gateway
 * @subpackage Woo_Paypal_Gateway/includes
 * @author     easypayment <wpeasypayment@gmail.com>
 */
class Woo_Paypal_Gateway {

    /**
     * The loader that's responsible for maintaining and registering all hooks that power
     * the plugin.
     *
     * @since    1.0.0
     * @access   protected
     * @var      Woo_Paypal_Gateway_Loader    $loader    Maintains and registers all hooks for the plugin.
     */
    protected $loader;

    /**
     * The unique identifier of this plugin.
     *
     * @since    1.0.0
     * @access   protected
     * @var      string    $plugin_name    The string used to uniquely identify this plugin.
     */
    protected $plugin_name;

    /**
     * The current version of the plugin.
     *
     * @since    1.0.0
     * @access   protected
     * @var      string    $version    The current version of the plugin.
     */
    protected $version;

    /**
     * Define the core functionality of the plugin.
     *
     * Set the plugin name and the plugin version that can be used throughout the plugin.
     * Load the dependencies, define the locale, and set the hooks for the admin area and
     * the public-facing side of the site.
     *
     * @since    1.0.0
     */
    public function __construct() {
        if (defined('WPG_VERSION')) {
            $this->version = WPG_VERSION;
        } else {
            $this->version = '4.0.3';
        }
        $this->plugin_name = 'woo-paypal-gateway';
        if (!defined('WPG_PLUGIN_NAME')) {
            define('WPG_PLUGIN_NAME', $this->plugin_name);
        }
        $this->load_dependencies();
        $this->set_locale();
        $this->define_admin_hooks();
        $this->define_public_hooks();
        add_action('init', array($this, 'add_endpoint'), 0);
        add_action('parse_request', array($this, 'handle_api_requests'), 0);
        add_action('wpg_paypal_payment_api_ipn', array($this, 'wpg_paypal_payment_api_ipn'));
        add_action('http_api_curl', array($this, 'wpg_http_api_curl_ec_add_curl_parameter'), 10, 3);
        $prefix = is_network_admin() ? 'network_admin_' : '';
        add_filter("{$prefix}plugin_action_links_" . WPG_PLUGIN_BASENAME, array($this, 'wpg_plugin_action_links'), 10, 4);
        add_action('woocommerce_cart_emptied', array($this, 'wpg_clear_session'), 1);
        add_action('woocommerce_cart_item_removed', array($this, 'wpg_clear_session'), 1);
        add_action('woocommerce_update_cart_action_cart_updated', array($this, 'wpg_clear_session'), 1);
    }

    /**
     * Load the required dependencies for this plugin.
     *
     * Include the following files that make up the plugin:
     *
     * - Woo_Paypal_Gateway_Loader. Orchestrates the hooks of the plugin.
     * - Woo_Paypal_Gateway_i18n. Defines internationalization functionality.
     * - Woo_Paypal_Gateway_Admin. Defines all hooks for the admin area.
     * - Woo_Paypal_Gateway_Public. Defines all hooks for the public side of the site.
     *
     * Create an instance of the loader which will be used to register the hooks
     * with WordPress.
     *
     * @since    1.0.0
     * @access   private
     */
    private function load_dependencies() {

        /**
         * The class responsible for orchestrating the actions and filters of the
         * core plugin.
         */
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-woo-paypal-gateway-loader.php';

        /**
         * The class responsible for defining internationalization functionality
         * of the plugin.
         */
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-woo-paypal-gateway-i18n.php';

        /**
         * The class responsible for defining all actions that occur in the admin area.
         */
        require_once plugin_dir_path(dirname(__FILE__)) . 'admin/class-woo-paypal-gateway-admin.php';

        /**
         * The class responsible for defining all actions that occur in the public-facing
         * side of the site.
         */
        require_once plugin_dir_path(dirname(__FILE__)) . 'public/class-woo-paypal-gateway-public.php';

        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-woo-paypal-gateway-functions.php';

        if (!class_exists('Woo_Paypal_Gateway_Calculations')) {
            require_once plugin_dir_path(dirname(__FILE__)) . '/includes/class-woo-paypal-gateway-calculations.php';
        }

        $this->loader = new Woo_Paypal_Gateway_Loader();
    }

    /**
     * Define the locale for this plugin for internationalization.
     *
     * Uses the Woo_Paypal_Gateway_i18n class in order to set the domain and to register the hook
     * with WordPress.
     *
     * @since    1.0.0
     * @access   private
     */
    private function set_locale() {

        $plugin_i18n = new Woo_Paypal_Gateway_i18n();

        $this->loader->add_action('plugins_loaded', $plugin_i18n, 'load_plugin_textdomain');
    }

    /**
     * Register all of the hooks related to the admin area functionality
     * of the plugin.
     *
     * @since    1.0.0
     * @access   private
     */
    private function define_admin_hooks() {

        $plugin_admin = new Woo_Paypal_Gateway_Admin($this->get_plugin_name(), $this->get_version());

        $this->loader->add_action('admin_enqueue_scripts', $plugin_admin, 'enqueue_styles', 0);
        $this->loader->add_action('admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts');
        $this->loader->add_action('plugins_loaded', $plugin_admin, 'init_wpg_paypal_payment');
        $this->loader->add_filter('woocommerce_payment_gateways', $plugin_admin, 'wpg_pal_payment_for_woo_add_payment_method_class', 9999, 1);
    }

    /**
     * Register all of the hooks related to the public-facing functionality
     * of the plugin.
     *
     * @since    1.0.0
     * @access   private
     */
    private function define_public_hooks() {

        $plugin_public = new Woo_Paypal_Gateway_Public($this->get_plugin_name(), $this->get_version());

        $this->loader->add_action('wp_enqueue_scripts', $plugin_public, 'enqueue_styles');
        $this->loader->add_action('wp_enqueue_scripts', $plugin_public, 'enqueue_scripts');


        $this->loader->add_action('wp_enqueue_scripts', $plugin_public, 'enqueue_styles', 0);
        $this->loader->add_action('wp_enqueue_scripts', $plugin_public, 'enqueue_scripts');
        $this->loader->add_action('woocommerce_after_add_to_cart_form', $plugin_public, 'wpg_display_paypal_button_on_product_page', 1);
        $this->loader->add_action('woocommerce_proceed_to_checkout', $plugin_public, 'wpg_display_paypal_button_on_cart_page', 19);
        $this->loader->add_action('wp_head', $plugin_public, 'wpg_add_header_meta', 0);
        $this->loader->add_action('wc_ajax_wpg_ajax_generate_cart_for_paypal_checkout', $plugin_public, 'wpg_ajax_generate_cart_for_paypal_checkout');
        $this->loader->add_filter('body_class', $plugin_public, 'wpg_add_body_classes');
        $this->loader->add_filter('the_title', $plugin_public, 'wpg_endpoint_page_titles');
        $this->loader->add_action('woocommerce_available_payment_gateways', $plugin_public, 'wpg_maybe_disable_other_gateways');
        $this->loader->add_action('woocommerce_checkout_billing', $plugin_public, 'wpg_checkout_details_to_post', 0);
        $this->loader->add_action('woocommerce_cart_shipping_packages', $plugin_public, 'wpg_maybe_add_shipping_information', 9);
        $this->loader->add_action('woocommerce_checkout_fields', $plugin_public, 'wpg_checkout_fields', 9);
        $this->loader->add_action('woocommerce_before_checkout_billing_form', $plugin_public, 'wpg_formatted_billing_address', 9);
        $this->loader->add_action('woocommerce_before_checkout_shipping_form', $plugin_public, 'wpg_formatted_shipping_address', 9);
        $this->loader->add_filter('woocommerce_checkout_get_value', $plugin_public, 'wpg_woocommerce_checkout_get_value', 99, 2);
    }

    /**
     * Run the loader to execute all of the hooks with WordPress.
     *
     * @since    1.0.0
     */
    public function run() {
        $this->loader->run();
    }

    /**
     * The name of the plugin used to uniquely identify it within the context of
     * WordPress and to define internationalization functionality.
     *
     * @since     1.0.0
     * @return    string    The name of the plugin.
     */
    public function get_plugin_name() {
        return $this->plugin_name;
    }

    /**
     * The reference to the class that orchestrates the hooks with the plugin.
     *
     * @since     1.0.0
     * @return    Woo_Paypal_Gateway_Loader    Orchestrates the hooks of the plugin.
     */
    public function get_loader() {
        return $this->loader;
    }

    /**
     * Retrieve the version number of the plugin.
     *
     * @since     1.0.0
     * @return    string    The version number of the plugin.
     */
    public function get_version() {
        return $this->version;
    }

    public function handle_api_requests() {
        global $wp;
        if (isset($_GET['wpg_ipn_action']) && $_GET['wpg_ipn_action'] == 'ipn') {
            $wp->query_vars['Woo_Paypal_Gateway'] = $_GET['wpg_ipn_action'];
        }
        if (!empty($wp->query_vars['Woo_Paypal_Gateway'])) {
            ob_start();
            $api = strtolower(esc_attr($wp->query_vars['Woo_Paypal_Gateway']));
            do_action('wpg_paypal_payment_api_' . $api);
            ob_end_clean();
            die('1');
        }
    }

    public function add_endpoint() {
        add_rewrite_endpoint('Woo_Paypal_Gateway', EP_ALL);
    }

    public function wpg_paypal_payment_api_ipn() {
        require_once( WPG_PLUGIN_DIR . '/includes/paypal-ipn/class-woo-paypal-gateway-ipn-handler.php' );
        $Woo_Paypal_Gateway_IPN_Handler_Object = new Woo_Paypal_Gateway_IPN_Handler();
        $Woo_Paypal_Gateway_IPN_Handler_Object->check_response();
    }

    public function wpg_http_api_curl_ec_add_curl_parameter($handle, $r, $url) {
        try {
            if ((strstr($url, 'https://') && strstr($url, '.paypal.com'))) {
                curl_setopt($handle, CURLOPT_VERBOSE, 1);
                curl_setopt($handle, CURLOPT_SSLVERSION, 6);
            }
        } catch (Exception $ex) {

        }
    }

    public function wpg_plugin_action_links($actions, $plugin_file, $plugin_data, $context) {
        $custom_actions = array(
            'configure' => sprintf('<a href="%s">%s</a>', admin_url('admin.php?page=wc-settings&tab=checkout&section=wpg_paypal_express'), __('Settings', 'woo-paypal-gateway')),
            'docs' => sprintf('<a href="%s" target="_blank">%s</a>', 'https://wordpress.org/plugins/woo-paypal-gateway/', __('Docs', 'woo-paypal-gateway')),
            'support' => sprintf('<a href="%s" target="_blank">%s</a>', 'https://wordpress.org/support/plugin/woo-paypal-gateway/', __('Support', 'woo-paypal-gateway')),
            'review' => sprintf('<a href="%s" target="_blank">%s</a>', 'https://wordpress.org/support/plugin/woo-paypal-gateway/reviews/', __('Write a Review', 'woo-paypal-gateway')),
        );
        return array_merge($custom_actions, $actions);
    }

    public function wpg_clear_session() {
        wpg_clear_session_data();
    }

}
