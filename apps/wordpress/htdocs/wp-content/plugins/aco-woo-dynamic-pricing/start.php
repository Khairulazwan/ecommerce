<?php
/*
 * Plugin Name: Acowebs Woocommerce Dynamic Pricing
 * Version: 3.6.8
 * Description: Woocommerce Dynamic Pricing helps to apply discounts for woocommerce products. Its sophisticated user interfaces will help to add discounts very easily.
 * Author: Acowebs
 * Author URI: http://acowebs.com
 * Requires at least: 4.4.0
 * Tested up to: 5.6.0
 * Text Domain: aco-woo-dynamic-pricing
 * WC requires at least: 3.3.0
 * WC tested up to: 4.8.0
 */


define('AWDP_POST_TYPE', 'awdp_pt_rules');
define('AWDP_PRODUCT_LIST', 'awdp_pt_products');
define('AWDP_WC_PRODUCTS', 'product');

define('AWDP_TOKEN', 'awdp');
define('AWDP_VERSION', '3.6.8');
define('AWDP_FILE', __FILE__);
define('AWDP_PLUGIN_NAME', 'Acowebs Woocommerce Dynamic Pricing');
define('AWDP_PRODUCTS_TRANSIENT_KEY', 'awdp_product_list');
define('AWDP_STORE_URL', 'https://api.acowebs.com');

define('AWDP_Wordpress_Version', get_bloginfo('version'));


if (!function_exists('awdp_init')) {

    function awdp_init()
    {
        $plugin_rel_path = basename(dirname(__FILE__)) . '/languages'; /* Relative to WP_PLUGIN_DIR */
        load_plugin_textdomain('woo-pricing-rules', false, $plugin_rel_path);
    }

}


if (!function_exists('awdp_autoloader')) {

    function awdp_autoloader($class_name)
    {
        if (0 === strpos($class_name, 'AWDP')) {
            $classes_dir = realpath(plugin_dir_path(__FILE__)) . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR;
            $class_file = 'class-' . str_replace('_', '-', strtolower($class_name)) . '.php';
            require_once $classes_dir . $class_file;
        }
    }

}

if (!function_exists('AWDP')) {

    function AWDP()
    {
        $instance = AWDP_Backend::instance(__FILE__, AWDP_VERSION);
        return $instance;
    }

}
add_action('plugins_loaded', 'awdp_init');
spl_autoload_register('awdp_autoloader');
if (is_admin()) {
    AWDP();
}
new AWDP_Api();

$discount = new AWDP_Discount();

new AWDP_Front_End($discount, __FILE__, AWDP_VERSION);
