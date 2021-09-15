<?php

/**
 * The public-facing functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the public-facing stylesheet and JavaScript.
 *
 * @package    Woo_Paypal_Gateway
 * @subpackage Woo_Paypal_Gateway/public
 * @author     easypayment <wpeasypayment@gmail.com>
 */
class Woo_Paypal_Gateway_Public {

    /**
     * The ID of this plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $plugin_name    The ID of this plugin.
     */
    private $plugin_name;

    /**
     * The version of this plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $version    The current version of this plugin.
     */
    private $version;
    
    
    public $gateway;
    public $sandbox;
    public $client_id;
    public $layout;
    public $color;
    public $shape;
    public $label;
    public $commit;
    public $show_on_product_page;
    public $show_on_cart;
    public $page;
    public $request;
    public $suffix;
    public $show_on_checkout_page;

    /**
     * Initialize the class and set its properties.
     *
     * @since    1.0.0
     * @param      string    $plugin_name       The name of the plugin.
     * @param      string    $version    The version of this plugin.
     */
    public function __construct($plugin_name, $version) {

        $this->plugin_name = $plugin_name;
        $this->version = $version;
        $this->suffix = defined('SCRIPT_DEBUG') && SCRIPT_DEBUG ? '' : '.min';
        $this->show_on_checkout_page = 'yes';
    }

    /**
     * Register the stylesheets for the public-facing side of the site.
     *
     * @since    1.0.0
     */
    public function enqueue_styles() {
        wp_enqueue_style($this->plugin_name, plugin_dir_url(__FILE__) . 'css/woo-paypal-gateway-public.css', array(), $this->version, 'all');
        wp_enqueue_style($this->plugin_name, plugin_dir_url(__FILE__) . 'css/woo-paypal-checkout-public' . $this->suffix . '.css', array(), $this->version, 'all');
        if(is_product()) {
            wp_enqueue_style($this->plugin_name . '-product', plugin_dir_url(__FILE__) . 'css/woo-paypal-checkout-product-page.css', array(), $this->version, 'all');
        } elseif (is_cart()) {
            wp_enqueue_style($this->plugin_name . '-cart', plugin_dir_url(__FILE__) . 'css/woo-paypal-checkout-cart-page.css', array(), $this->version, 'all');
        } elseif (is_checkout()) {
            wp_enqueue_style($this->plugin_name . '-checkout', plugin_dir_url(__FILE__) . 'css/woo-paypal-checkout-page.css', array(), $this->version, 'all');
        }
    }

    /**
     * Register the JavaScript for the public-facing side of the site.
     *
     * @since    1.0.0
     */
    public function enqueue_scripts() {
        if (is_order_received_page()) {
            return false;
        }
        if (is_product() == false && 0 >= WC()->cart->total) {
            if ($this->show_on_cart == 'no' && $this->show_on_minicart == 'no') {
                return false;
            }
        }
        if ($this->wpg_enable_paypal_checkout() == false) {
            return;
        }
        if (empty($this->gateway)) {
            return;
        }
        $this->sandbox = 'yes' === $this->gateway->get_option('sandbox', 'no');
        if ($this->sandbox) {
            $this->client_id = $this->gateway->get_option('rest_client_id_sandbox', '');
        } else {
            $this->client_id = $this->gateway->get_option('rest_client_id_live', '');
        }
        $this->show_on_product_page = $this->gateway->get_option('show_on_product_page', 'yes');
        $this->show_on_cart = $this->gateway->get_option('show_on_cart', 'yes');
        $this->show_on_mini_cart = $this->gateway->get_option('show_on_mini_cart', 'yes');
        $this->layout = '';
        $this->color = '';
        $this->shape = '';
        $this->label = '';
        $this->commit = '';
        $this->page = '';
        $this->layout_mini = '';
        $this->color_mini = '';
        $this->shape_mini = '';
        $this->label_mini = '';
        if (is_product() && $this->show_on_product_page == 'yes') {
            $this->layout = $this->gateway->get_option('product_button_layout', 'horizontal');
            $this->color = $this->gateway->get_option('product_button_color', 'gold');
            $this->shape = $this->gateway->get_option('product_button_shape', 'rect');
            $this->label = $this->gateway->get_option('product_button_label', 'paypal');
            $this->commit = "false";
            $this->page = "product";
        } elseif (is_cart() && $this->show_on_cart == 'yes') {
            $this->layout = $this->gateway->get_option('cart_button_layout', 'vertical');
            $this->color = $this->gateway->get_option('cart_button_color', 'gold');
            $this->shape = $this->gateway->get_option('cart_button_shape', 'rect');
            $this->label = $this->gateway->get_option('cart_button_label', 'paypal');
            $this->commit = "false";
            $this->page = "cart";
        } elseif (is_checkout() && $this->show_on_checkout_page == 'yes') {
            $this->layout = $this->gateway->get_option('cart_button_layout', 'vertical');
            $this->color = $this->gateway->get_option('cart_button_color', 'gold');
            $this->shape = $this->gateway->get_option('cart_button_shape', 'rect');
            $this->label = $this->gateway->get_option('cart_button_label', 'paypal');
            $this->commit = "true";
            $this->page = "checkout";
            wp_enqueue_script('wpg_checkout_page', WPG_ASSET_URL . 'public/js/woo-paypal-checkout-page.js', array('jquery'), $this->version, false);
        }
        if ($this->show_on_mini_cart == 'yes') {
            $this->layout_mini = $this->gateway->get_option('mini_cart_button_layout', 'vertical');
            $this->color_mini = $this->gateway->get_option('mini_cart_button_color', 'gold');
            $this->shape_mini = $this->gateway->get_option('mini_cart_button_shape', 'rect');
            $this->label_mini = $this->gateway->get_option('mini_cart_button_label', 'paypal');
        }
        $suffix = defined('SCRIPT_DEBUG') && SCRIPT_DEBUG ? '' : '.min';
        $paypal_sdk = add_query_arg(array('client-id' => $this->client_id, 'commit' => $this->commit, 'currency' => get_woocommerce_currency()), 'https://www.paypal.com/sdk/js');
        wp_register_script('wpg_checkout_sdk', $paypal_sdk, array('jquery'), null, false);
        wp_register_script('wpg_checkout_sdk-frontend', WPG_ASSET_URL . 'public/js/woo-paypal-checkout-public' . $suffix . '.js', array('jquery'), $this->version, false);
        wp_localize_script('wpg_checkout_sdk-frontend', 'wpg_param', array(
            'page' => $this->page,
            'layout' => $this->layout,
            'color' => $this->color,
            'shape' => $this->shape,
            'label' => $this->label,
            'commit' => $this->commit,
            'layout_mini' => $this->layout_mini,
            'color_mini' => $this->color_mini,
            'shape_mini' => $this->shape_mini,
            'label_mini' => $this->label_mini,
            'add_to_cart_ajaxurl' => WC_AJAX::get_endpoint('wpg_ajax_generate_cart_for_paypal_checkout'),
            'get_checkout_details' => add_query_arg(array('pp_action' => 'get_checkout_details', 'utm_nooverride' => '1'), WC()->api_request_url('Woo_PayPal_Gateway_PayPal_Checkout_Rest')),
            'set_checkout' => add_query_arg(array('pp_action' => 'set_checkout', 'utm_nooverride' => '1'), WC()->api_request_url('Woo_PayPal_Gateway_PayPal_Checkout_Rest')),
            'display_order_page' => add_query_arg(array('pp_action' => 'display_order_page', 'utm_nooverride' => '1'), WC()->api_request_url('Woo_PayPal_Gateway_PayPal_Checkout_Rest')),
            'cancel_url' => add_query_arg(array('pp_action' => 'cancel_url', 'utm_nooverride' => '1'), WC()->api_request_url('Woo_PayPal_Gateway_PayPal_Checkout_Rest')),
                )
        );
    }
    
    public function wpg_display_paypal_button_on_product_page() {
        global $product;
        if (!is_product()) {
            return;
        }
        if ($this->wpg_enable_paypal_checkout() == false) {
            return;
        }
        if (empty($this->gateway)) {
            return false;
        }
        $this->show_on_product_page = $this->gateway->get_option('show_on_product_page', 'yes');
        if ($this->show_on_product_page == 'no') {
            return;
        }
        if (empty($product) || !$product->is_visible()) {
            return;
        }
        if (!$product->is_purchasable() && !$product->is_in_stock()) {
            return;
        }
        if (!$product->is_type('simple') && !$product->is_type('variable')) {
            return;
        }
        if ($product->get_price() == '0') {
            return;
        }
        wp_enqueue_script('wpg_checkout_sdk');
        wp_enqueue_script('wpg_checkout_sdk-frontend');
        ?>
        <div class="wpg_paypal_checkout_div">
            <div id="wpg_paypal_button_product"></div>
        </div>
        <?php
    }

    public function wpg_display_paypal_button_on_cart_page() {
        if (WC()->cart->is_empty()) {
            return;
        }
        if ($this->wpg_enable_paypal_checkout() == false) {
            return;
        }
        if (empty($this->gateway)) {
            return false;
        }
        $this->show_on_cart = $this->gateway->get_option('show_on_cart', 'yes');
        if ($this->show_on_cart == 'no') {
            return;
        }
        wp_enqueue_script('wpg_checkout_sdk');
        wp_enqueue_script('wpg_checkout_sdk-frontend');
        ?>
        <div class="wpg_paypal_checkout_div">
            <div id="wpg_paypal_button_cart"></div>
            <div class="wpg-checkout-buttons__separator">
                <?php _e('OR', 'woo-paypal-checkout'); ?>
            </div>
        </div>
        <?php
    }

    public function wpg_is_credit_supported() {
        $base = wc_get_base_location();
        return 'US' === $base['country'] && 'USD' === get_woocommerce_currency();
    }

    public function wpg_add_header_meta() {
        if ($this->wpg_enable_paypal_checkout() == false) {
            return;
        }
        echo '<meta http-equiv="X-UA-Compatible" content="IE=edge" />';
        echo '<meta name="viewport" content="width=device-width, initial-scale=1">';
    }

    public function wpg_enable_paypal_checkout() {
        $gateways = WC()->payment_gateways->get_available_payment_gateways();
        if (!isset($gateways['wpg_paypal_checkout'])) {
            return false;
        }
        if (isset($gateways['wpg_paypal_checkout']) && is_object($gateways['wpg_paypal_checkout'])) {
            $this->gateway = $gateways['wpg_paypal_checkout'];
        }
        if (isset($this->gateway) && !empty($this->gateway)) {
            $enabled = $this->gateway->get_option('enabled');
            if ($enabled !== 'yes') {
                return false;
            }
        } else {
            return false;
        }
        if (empty($this->gateway)) {
            return false;
        }
        return true;
    }

    public function wpg_ajax_generate_cart_for_paypal_checkout() {
        global $HTTP_RAW_POST_DATA;
        if (!isset($HTTP_RAW_POST_DATA)) {
            $HTTP_RAW_POST_DATA = file_get_contents('php://input');
        }
        $_POST = json_decode($HTTP_RAW_POST_DATA, true);
        global $wpdb, $post, $product;
        $product_id = '';
        WC()->shipping->reset_shipping();
        $product_id = absint(wp_unslash($_POST['product_id']));
        $url = esc_url_raw(add_query_arg('pp_action', 'set_express_checkout', add_query_arg('wc-api', 'Woo_PayPal_Gateway_PayPal_Checkout_Rest', home_url('/'))));
        try {
            $product = wc_get_product($product_id);
            if (is_object($product)) {
                if (!defined('WOOCOMMERCE_CART')) {
                    define('WOOCOMMERCE_CART', true);
                }
                $qty = !isset($_POST['qty']) ? 1 : absint($_POST['qty']);
                if ($product->is_type('variable')) {
                    $attributes = array_map('wc_clean', json_decode(stripslashes($_POST['attributes']), true));
                    if (!empty($_POST['variation_id'])) {
                        $variation_id = absint(wp_unslash($_POST['variation_id']));
                    } else {
                        if (version_compare(WC_VERSION, '3.0', '<')) {
                            $variation_id = $product->get_matching_variation($attributes);
                        } else {
                            $data_store = WC_Data_Store::load('product');
                            $variation_id = $data_store->find_matching_product_variation($product, $attributes);
                        }
                    }
                    $bool = $this->wpg_is_product_already_in_cart($product->get_id(), $qty, $variation_id, $attributes);
                    if ($bool == false) {
                        WC()->cart->add_to_cart($product->get_id(), $qty, $variation_id, $attributes);
                    }
                } elseif ($product->is_type('simple')) {
                    $bool = $this->wpg_is_product_already_in_cart($product->get_id(), $qty);
                    if ($bool == false) {
                        WC()->cart->add_to_cart($product->get_id(), $qty);
                    }
                }
                WC()->cart->calculate_totals();
                $this->wpg_init_sdk();
                $this->request->wpg_create_order_request();
            }
        } catch (Exception $ex) {
            
        }
    }

    public function wpg_is_product_already_in_cart($product_id = 0, $quantity = 1, $variation_id = 0, $variation = array(), $cart_item_data = array()) {
        $product_id = absint($product_id);
        $variation_id = absint($variation_id);
        if ('product_variation' === get_post_type($product_id)) {
            $variation_id = $product_id;
            $product_id = wp_get_post_parent_id($variation_id);
        }
        $product_data = wc_get_product($variation_id ? $variation_id : $product_id);
        $quantity = apply_filters('woocommerce_add_to_cart_quantity', $quantity, $product_id);
        if ($quantity <= 0 || !$product_data || 'trash' === $product_data->get_status()) {
            return false;
        }
        $cart_item_data = (array) apply_filters('woocommerce_add_cart_item_data', $cart_item_data, $product_id, $variation_id, $quantity);
        $cart_id = WC()->cart->generate_cart_id($product_id, $variation_id, $variation, $cart_item_data);
        $cart_item_key = WC()->cart->find_product_in_cart($cart_id);
        if ($product_data->is_sold_individually()) {
            $quantity = apply_filters('woocommerce_add_to_cart_sold_individually_quantity', 1, $quantity, $product_id, $variation_id, $cart_item_data);
            $found_in_cart = apply_filters('woocommerce_add_to_cart_sold_individually_found_in_cart', $cart_item_key && WC()->cart->cart_contents[$cart_item_key]['quantity'] > 0, $product_id, $variation_id, $cart_item_data, $cart_id);
            if ($found_in_cart) {
                return true;
            } else {
                return false;
            }
        }
        return false;
    }

    public function wpg_init_sdk() {
        try {
            include_once WPG_PLUGIN_DIR . '/includes/gateways/express-checkout-rest/class-woo-paypal-gateway-paypal-checkout-api-handler.php';
            if ($this->wpg_enable_paypal_checkout() == false) {
                return;
            }
            $this->request = new Woo_PayPal_Gateway_PayPal_Checkout_API_Handler_Rest($this->gateway);
        } catch (Exception $ex) {
            
        }
    }

    public function wpg_add_body_classes($classes) {
        if (is_checkout()) {
            if ($this->wpg_enable_paypal_checkout() == false) {
                return;
            }
            if (empty($this->gateway)) {
                return $classes;
            }
            $classes[] = 'wpg_paypal_checkout';
            if (wpg_has_active_session()) {
                $classes[] = 'wpg_paypal_checkout_hidden';
            }
        }
        return $classes;
    }

    public function wpg_endpoint_page_titles($title) {
        if (!is_admin() && is_main_query() && in_the_loop() && is_page() && is_checkout() && wpg_has_active_session()) {
            $title = __('Confirm your PayPal order', 'woo-paypal-checkout');
            remove_filter('the_title', array($this, 'endpoint_page_titles'));
        }
        return $title;
    }

    public function wpg_maybe_disable_other_gateways($gateways) {
        if (wpg_has_active_session()) {
            foreach ($gateways as $id => $gateway) {
                if ('wpg_paypal_checkout' !== $id) {
                    unset($gateways[$id]);
                }
            }
            if (is_cart() || ( is_checkout() && !is_checkout_pay_page() )) {
                if (isset($gateways['wpg_paypal_checkout']) && ( 0 >= WC()->cart->total )) {
                    unset($gateways['wpg_paypal_checkout']);
                }
            }
        }
        if (is_cart() || ( is_checkout() && !is_checkout_pay_page() )) {
            if (isset($gateways['wpg_paypal_checkout']) && ( 0 >= WC()->cart->total )) {
                unset($gateways['wpg_paypal_checkout']);
            }
        }
        return $gateways;
    }

    public function wpg_checkout_details_to_post() {
        if ($this->wpg_enable_paypal_checkout() == false) {
            return;
        }
        if (!wpg_has_active_session()) {
            return;
        }
        $this->wpg_init_sdk();
        try {
            $wpg_order_details = WC()->session->get('wpg_order_details');
            if (empty($wpg_order_details)) {
                $wpg_order_id = WC()->session->get('wpg_order_id');
                $this->request->wpg_get_checkout_details($wpg_order_id);
                $wpg_order_details = WC()->session->get('wpg_order_details');
            }
        } catch (Exception $e) {
            wc_add_notice($e->getMessage(), 'error');
            return;
        }
        $shipping_details = $this->wpg_get_mapped_shipping_address($wpg_order_details);
        $billing_details = $this->wpg_get_mapped_billing_address($wpg_order_details);
        $this->update_customer_addresses_from_paypal($shipping_details, $billing_details);
        if (empty($billing_details['address_1'])) {
            $_POST['ship_to_different_address'] = 1;
            $copyable_keys = array('address_1', 'address_2', 'city', 'state', 'postcode', 'country');
            foreach ($copyable_keys as $copyable_key) {
                if (array_key_exists($copyable_key, $shipping_details)) {
                    $billing_details[$copyable_key] = $shipping_details[$copyable_key];
                }
            }
        } else {
            $_POST['ship_to_different_address'] = 1;
        }
        foreach ($shipping_details as $key => $value) {
            $_POST['shipping_' . $key] = wc_clean(stripslashes($value));
        }
        foreach ($billing_details as $key => $value) {
            $_POST['billing_' . $key] = wc_clean(stripslashes($value));
        }
    }

    public function wpg_get_mapped_billing_address($checkout_details) {
        if (!empty($checkout_details['payer']['name'])) {
            $first_name = $checkout_details['payer']['name']['given_name'];
            $last_name = $checkout_details['payer']['name']['surname'];
        } else {
            $first_name = '';
            $last_name = '';
        }
        if (!empty($checkout_details['purchase_units'][0]['shipping']['address'])) {
            $company = '';
            $address_1 = !empty($checkout_details['purchase_units'][0]['shipping']['address']['address_line_1']) ? $checkout_details['purchase_units'][0]['shipping']['address']['address_line_1'] : '';
            $address_2 = !empty($checkout_details['purchase_units'][0]['shipping']['address']['address_line_2']) ? $checkout_details['purchase_units'][0]['shipping']['address']['address_line_2'] : '';
            $city = !empty($checkout_details['purchase_units'][0]['shipping']['address']['admin_area_2']) ? $checkout_details['purchase_units'][0]['shipping']['address']['admin_area_2'] : '';
            $state_value = !empty($checkout_details['purchase_units'][0]['shipping']['address']['admin_area_1']) ? $checkout_details['purchase_units'][0]['shipping']['address']['admin_area_1'] : '';
            $postcode = !empty($checkout_details['purchase_units'][0]['shipping']['address']['postal_code']) ? $checkout_details['purchase_units'][0]['shipping']['address']['postal_code'] : '';
            $country = !empty($checkout_details['purchase_units'][0]['shipping']['address']['country_code']) ? $checkout_details['purchase_units'][0]['shipping']['address']['country_code'] : '';
            $state = $this->wpg_get_state_code($country, $state_value);
        } else {
            $company = '';
            $address_1 = '';
            $address_2 = '';
            $city = '';
            $state = '';
            $postcode = '';
            $country = '';
        }
        $email = !empty($checkout_details['payer']['email_address']) ? $checkout_details['payer']['email_address'] : '';
        $phone = !empty($checkout_details['payer']['phone']['phone_number']['national_number']) ? $checkout_details['payer']['phone']['phone_number']['national_number'] : '';
        return array(
            'first_name' => $first_name,
            'last_name' => $last_name,
            'company' => $company,
            'address_1' => $address_1,
            'address_2' => $address_2,
            'city' => $city,
            'state' => $state,
            'postcode' => $postcode,
            'country' => $country,
            'phone' => $phone,
            'email' => $email,
            'order_comments' => ''
        );
    }

    public function wpg_get_mapped_shipping_address($checkout_details) {
        if (!empty($checkout_details['purchase_units'][0]['shipping']['name']['full_name'])) {
            $name = explode(' ', $checkout_details['purchase_units'][0]['shipping']['name']['full_name']);
            $first_name = array_shift($name);
            $last_name = implode(' ', $name);
        } else {
            $first_name = '';
            $last_name = '';
        }
        if (!empty($checkout_details['purchase_units'][0]['shipping']['address'])) {
            $company = '';
            $address_1 = !empty($checkout_details['purchase_units'][0]['shipping']['address']['address_line_1']) ? $checkout_details['purchase_units'][0]['shipping']['address']['address_line_1'] : '';
            $address_2 = !empty($checkout_details['purchase_units'][0]['shipping']['address']['address_line_2']) ? $checkout_details['purchase_units'][0]['shipping']['address']['address_line_2'] : '';
            $city = !empty($checkout_details['purchase_units'][0]['shipping']['address']['admin_area_2']) ? $checkout_details['purchase_units'][0]['shipping']['address']['admin_area_2'] : '';
            $state_value = !empty($checkout_details['purchase_units'][0]['shipping']['address']['admin_area_1']) ? $checkout_details['purchase_units'][0]['shipping']['address']['admin_area_1'] : '';
            $postcode = !empty($checkout_details['purchase_units'][0]['shipping']['address']['postal_code']) ? $checkout_details['purchase_units'][0]['shipping']['address']['postal_code'] : '';
            $country = !empty($checkout_details['purchase_units'][0]['shipping']['address']['country_code']) ? $checkout_details['purchase_units'][0]['shipping']['address']['country_code'] : '';
            $state = $this->wpg_get_state_code($country, $state_value);
        } else {
            $company = '';
            $address_1 = '';
            $address_2 = '';
            $city = '';
            $state = '';
            $postcode = '';
            $country = '';
        }
        return array(
            'first_name' => $first_name,
            'last_name' => $last_name,
            'company' => $company,
            'address_1' => $address_1,
            'address_2' => $address_2,
            'city' => $city,
            'state' => $state,
            'postcode' => $postcode,
            'country' => $country,
        );
    }

    public function update_customer_addresses_from_paypal($shipping_details, $billing_details) {
        $customer = WC()->customer;
        $customer->set_billing_address($billing_details['address_1']);
        $customer->set_billing_address_2($billing_details['address_2']);
        $customer->set_billing_city($billing_details['city']);
        $customer->set_billing_postcode($billing_details['postcode']);
        $customer->set_billing_state($billing_details['state']);
        $customer->set_billing_country($billing_details['country']);
        $customer->set_shipping_address($shipping_details['address_1']);
        $customer->set_shipping_address_2($shipping_details['address_2']);
        $customer->set_shipping_city($shipping_details['city']);
        $customer->set_shipping_postcode($shipping_details['postcode']);
        $customer->set_shipping_state($shipping_details['state']);
        $customer->set_shipping_country($shipping_details['country']);
    }

    public function wpg_maybe_add_shipping_information($packages) {
        if (!wpg_has_active_session()) {
            return $packages;
        }
        $wpg_order_details = WC()->session->get('wpg_order_details');
        if (empty($wpg_order_details)) {
            $wpg_order_id = WC()->session->get('wpg_order_id');
            $this->request->wpg_get_checkout_details($wpg_order_id);
            $wpg_order_details = WC()->session->get('wpg_order_details');
        }
        $destination = $this->wpg_get_mapped_shipping_address($wpg_order_details);
        $packages[0]['destination']['country'] = $destination['country'];
        $packages[0]['destination']['state'] = $destination['state'];
        $packages[0]['destination']['postcode'] = $destination['postcode'];
        $packages[0]['destination']['city'] = $destination['city'];
        $packages[0]['destination']['address'] = $destination['address_1'];
        $packages[0]['destination']['address_2'] = $destination['address_2'];
        return $packages;
    }

    public function wpg_checkout_fields($checkout_fields) {
        if (wpg_has_active_session()) {
            $this->wpg_init_sdk();
            try {
                $wpg_order_details = WC()->session->get('wpg_order_details');
                if (empty($wpg_order_details)) {
                    $wpg_order_id = WC()->session->get('wpg_order_id');
                    $this->request->wpg_get_checkout_details($wpg_order_id);
                    $wpg_order_details = WC()->session->get('wpg_order_details');
                }
            } catch (Exception $e) {
                wc_add_notice($e->getMessage(), 'error');
                return;
            }
            $billing_details = $this->wpg_get_mapped_billing_address($wpg_order_details);
            if (!empty($billing_details)) {
                foreach ($billing_details as $field_key => $value) {
                    if (isset($checkout_fields['billing']) && isset($checkout_fields['billing']['billing_' . $field_key])) {
                        $required = isset($checkout_fields['billing']['billing_' . $field_key]['required']) && $checkout_fields['billing']['billing_' . $field_key]['required'];
                        if (!$required || $required && !empty($value)) {
                            $checkout_fields['billing']['billing_' . $field_key]['class'][] = 'express-provided';
                            $checkout_fields['billing']['billing_' . $field_key]['class'][] = 'hidden';
                        }
                    }
                }
            }
            if (true === WC()->cart->needs_shipping_address()) {
                $shipping_details = $this->wpg_get_mapped_billing_address($wpg_order_details);
                if (!empty($shipping_details)) {
                    foreach ($shipping_details as $field_key => $value) {
                        if (isset($checkout_fields['shipping']) && isset($checkout_fields['shipping']['shipping_' . $field_key])) {
                            $required = isset($checkout_fields['shipping']['shipping_' . $field_key]['required']) && $checkout_fields['shipping']['shipping_' . $field_key]['required'];
                            if (!$required || $required && !empty($value)) {
                                $checkout_fields['shipping']['shipping_' . $field_key]['class'][] = 'express-provided';
                                $checkout_fields['shipping']['shipping_' . $field_key]['class'][] = 'hidden';
                            }
                        }
                    }
                }
            }
        }
        return $checkout_fields;
    }

    public function wpg_formatted_billing_address() {
        if (!wpg_has_active_session()) {
            return;
        }
        try {
            $wpg_order_details = WC()->session->get('wpg_order_details');

            if (empty($wpg_order_details)) {
                $wpg_order_id = WC()->session->get('wpg_order_id');
                $this->request->wpg_get_checkout_details($wpg_order_id);
                $wpg_order_details = WC()->session->get('wpg_order_details');
            }
            $billing_address = $this->wpg_get_mapped_billing_address($wpg_order_details);
        } catch (Exception $e) {
            wc_add_notice($e->getMessage(), 'error');
            return;
        }
        ?>
        <ul>
            <?php
            $first_name = isset($billing_address['first_name']) ? $billing_address['first_name'] : '';
            $last_name = isset($billing_address['last_name']) ? $billing_address['last_name'] : '';
            ?>
            <li><strong><?php _e('Name:', 'woo-paypal-checkout') ?></strong> <?php echo esc_html($first_name . ' ' . $last_name); ?></li>
            <?php if (!empty($billing_address['email'])) : ?>
                <li><strong><?php _e('Email:', 'woo-paypal-checkout') ?></strong> <?php echo esc_html($billing_address['email']); ?></li>
            <?php endif; ?>
            <?php if (!empty($billing_address['phone'])) : ?>
                <li><strong><?php _e('Phone:', 'woo-paypal-checkout') ?></strong> <?php echo esc_html($billing_address['phone']); ?></li>
            <?php endif; ?>
        </ul>
        <?php
    }

    public function wpg_formatted_shipping_address() {
        if (!wpg_has_active_session()) {
            return;
        }
        try {
            $wpg_order_details = WC()->session->get('wpg_order_details');
            if (empty($wpg_order_details)) {
                $wpg_order_id = WC()->session->get('wpg_order_id');
                $this->request->wpg_get_checkout_details($wpg_order_id);
                $wpg_order_details = WC()->session->get('wpg_order_details');
            }
            $shipping_address = $this->wpg_get_mapped_shipping_address($wpg_order_details);
        } catch (Exception $e) {
            wc_add_notice($e->getMessage(), 'error');
            return;
        }
        ?>
        <h3><?php _e('Shipping details', 'woo-paypal-checkout'); ?></h3>
        <?php
        echo WC()->countries->get_formatted_address($shipping_address);
    }

    public function wpg_get_state_code($country, $state) {
        try {
            $valid_states = WC()->countries->get_states($country);
            if (!empty($valid_states) && is_array($valid_states)) {
                $valid_state_values = array_flip(array_map('strtolower', $valid_states));
                if (isset($valid_state_values[strtolower($state)])) {
                    $state_value = $valid_state_values[strtolower($state)];
                    return $state_value;
                }
            } else {
                return $state;
            }
            if (!empty($valid_states) && is_array($valid_states) && sizeof($valid_states) > 0) {
                if (!in_array(strtoupper($state), array_keys($valid_states))) {
                    return false;
                } else {
                    return strtoupper($state);
                }
            }
            return $state;
        } catch (Exception $ex) {
            
        }
    }

    public function wpg_woocommerce_checkout_get_value($value, $checkout_key) {
        $wpg_order_details = WC()->session->get('wpg_order_details');
        $shipping_address = $this->wpg_get_mapped_shipping_address($wpg_order_details);
        $billing_address = $this->wpg_get_mapped_billing_address($wpg_order_details);
        if (!empty($_POST[$checkout_key])) {
            return $_POST[$checkout_key];
        } elseif (0 === strpos($checkout_key, 'billing_')) {
            $new_checkout_key = str_replace('billing_', '', $checkout_key);
            if (!empty($billing_address[$new_checkout_key])) {
                return $billing_address[$new_checkout_key];
            }
        } elseif (0 === strpos($checkout_key, 'shipping_')) {
            $new_checkout_key = str_replace('shipping_', '', $checkout_key);
            if (!empty($shipping_address[$new_checkout_key])) {
                return $shipping_address[$new_checkout_key];
            }
        }
        return $value;
    }

}
