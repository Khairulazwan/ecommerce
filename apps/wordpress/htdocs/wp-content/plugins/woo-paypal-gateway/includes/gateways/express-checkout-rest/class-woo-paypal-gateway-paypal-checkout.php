<?php
/**
 * PayPal Gateway.
 *
 * @class       Woo_PayPal_Gateway_PayPal_Checkout_Rest
 * @extends     WC_Payment_Gateway
 * @version     1.0.0
 * @package     Woo_Paypal_Checkout
 */
if (!defined('ABSPATH')) {
    exit;
}

if (class_exists('WC_Payment_Gateway')) {

    class Woo_PayPal_Gateway_PayPal_Checkout_Rest extends WC_Payment_Gateway {

        public static $log_enabled = false;
        public static $log = false;

        public function __construct() {
            $this->id = 'wpg_paypal_checkout';
            $this->has_fields = false;
            if (wpg_has_active_session() == true) {
                $this->order_button_text = __('Continue to payment', 'woo-paypal-checkout');
            }
            $this->method_title = __('PayPal Checkout (Smart Payment Buttons)', 'woo-paypal-checkout');
            $this->method_description = __('Add Smart Payment Buttons to your website. https://developer.paypal.com/docs/checkout/', 'woo-paypal-checkout');
            $this->supports = array(
                'products',
                'refunds',
            );
            $this->init_form_fields();
            $this->init_settings();
            $this->title = $this->get_option('title');
            $this->description = $this->get_option('description');
            $this->sandbox = 'yes' === $this->get_option('sandbox', 'no');
            if ($this->sandbox) {
                $this->client_id = $this->get_option('rest_client_id_sandbox', '');
                $this->secret_id = $this->get_option('rest_secret_id_sandbox', '');
            } else {
                $this->client_id = $this->get_option('rest_client_id_live', '');
                $this->secret_id = $this->get_option('rest_secret_id_live', '');
            }
            $this->debug = 'yes' === $this->get_option('debug', 'no');
            $this->email = $this->get_option('email');
            $this->show_on_checkout_page = 'yes';
            $this->show_on_product_page = $this->get_option('show_on_product_page');
            self::$log_enabled = $this->debug;
            add_action('admin_enqueue_scripts', array($this, 'admin_scripts'));
            add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));
            add_action('woocommerce_api_' . strtolower('Woo_PayPal_Gateway_PayPal_Checkout_Rest'), array($this, 'wpg_request_handler'));
            add_action('woocommerce_admin_order_totals_after_total', array($this, 'wpg_display_order_fee'));
            if (!$this->is_valid_for_use() || !$this->is_credentials_set()) {
                $this->enabled = 'no';
            } else {
                
            }
        }

        public static function log($message, $level = 'info') {
            if (self::$log_enabled) {
                if (empty(self::$log)) {
                    self::$log = wc_get_logger();
                }
                self::$log->log($level, $message, array('source' => 'paypal'));
            }
        }

        public function process_admin_options() {
            delete_transient('wpg_sandbox_access_token');
            delete_transient('wpg_live_access_token');
            $saved = parent::process_admin_options();
            if ('yes' !== $this->get_option('debug', 'no')) {
                if (empty(self::$log)) {
                    self::$log = wc_get_logger();
                }
                self::$log->clear('paypal');
            }
            return $saved;
        }

        public function get_icon() {
            
        }

        public function is_valid_for_use() {
            return in_array(
                    get_woocommerce_currency(), apply_filters(
                            'woocommerce_paypal_supported_currencies', array('AUD', 'BRL', 'CAD', 'MXN', 'NZD', 'HKD', 'SGD', 'USD', 'EUR', 'JPY', 'TRY', 'NOK', 'CZK', 'DKK', 'HUF', 'ILS', 'MYR', 'PHP', 'PLN', 'SEK', 'CHF', 'TWD', 'THB', 'GBP', 'RMB', 'RUB', 'INR')
                    ), true
            );
        }

        public function is_credentials_set() {
            if (!empty($this->client_id) && !empty($this->secret_id)) {
                return true;
            } else {
                return false;
            }
        }

        public function admin_options() {
            if ($this->is_valid_for_use()) {
                parent::admin_options();
            } else {
                ?>
                <div class="inline error">
                    <p>
                        <strong><?php esc_html_e('Gateway disabled', 'woo-paypal-checkout'); ?></strong>: <?php esc_html_e('PayPal does not support your store currency.', 'woo-paypal-checkout'); ?>
                    </p>
                </div>
                <?php
            }
        }

        public function init_form_fields() {
            $this->form_fields = include WPG_PLUGIN_DIR . '/includes/gateways/express-checkout-rest/settings-paypal-checkout.php';
        }

        public function payment_fields() {
            parent::payment_fields();
            if (wpg_has_active_session() == true) {
                return false;
            }
            if ($this->show_on_checkout_page == 'no') {
                return;
            }
            wp_enqueue_script('wpg_checkout_sdk');
            wp_enqueue_script('wpg_checkout_sdk-frontend');
            ?>
            <br>
            <div class="wpg_paypal_checkout_div">
                <div id="wpg_paypal_button_checkout"></div>
            </div>
            <?php
        }

        public function get_transaction_url($order) {
            if ($this->sandbox) {
                $this->view_transaction_url = 'https://www.sandbox.paypal.com/cgi-bin/webscr?cmd=_view-a-trans&id=%s';
            } else {
                $this->view_transaction_url = 'https://www.paypal.com/cgi-bin/webscr?cmd=_view-a-trans&id=%s';
            }
            return parent::get_transaction_url($order);
        }

        public function process_payment($woo_order_id) {
            if (isset($_POST['from_checkout']) && 'yes' === $_POST['from_checkout']) {
                include_once WPG_PLUGIN_DIR . '/includes/gateways/express-checkout-rest/class-woo-paypal-gateway-paypal-checkout-api-handler.php';
                $this->request = new Woo_PayPal_Gateway_PayPal_Checkout_API_Handler_Rest($this);
                $this->request->wpg_create_order_request($woo_order_id);
                exit();
            } else {
                $wpg_order_id = WC()->session->get('wpg_order_id');
                if (!empty($wpg_order_id)) {
                    include_once WPG_PLUGIN_DIR . '/includes/gateways/express-checkout-rest/class-woo-paypal-gateway-paypal-checkout-api-handler.php';
                    $this->request = new Woo_PayPal_Gateway_PayPal_Checkout_API_Handler_Rest($this);
                    $order = wc_get_order($woo_order_id);
                    $is_success = $this->request->wpg_order_capture_request($woo_order_id);
                    if ($is_success) {
                        return array(
                            'result' => 'success',
                            'redirect' => $this->get_return_url($order),
                        );
                    } else {
                        return array(
                            'result' => 'fail',
                            'redirect' => ''
                        );
                    }
                }
            }
        }

        public function can_refund_order($order) {
            $has_api_creds = false;
            if (!empty($this->client_id) && !empty($this->secret_id)) {
                $has_api_creds = true;
            }
            return $order && $order->get_transaction_id() && $has_api_creds;
        }

        public function process_refund($order_id, $amount = null, $reason = '') {
            $order = wc_get_order($order_id);
            if (!$this->can_refund_order($order)) {
                return new WP_Error('error', __('Refund failed.', 'woo-paypal-checkout'));
            }
            include_once WPG_PLUGIN_DIR . '/includes/gateways/express-checkout-rest/class-woo-paypal-gateway-paypal-checkout-api-handler.php';
            $this->request = new Woo_PayPal_Gateway_PayPal_Checkout_API_Handler_Rest($this);
            $transaction_id = $order->get_transaction_id();
            $response = $this->request->wpg_refund_order($order_id, $amount, $reason, $transaction_id);
            if (is_wp_error($response)) {
                $api_response = json_decode(wp_remote_retrieve_body($response), true);
                self::log('Response Code: ' . wp_remote_retrieve_response_code($response));
                self::log('Response Message: ' . wp_remote_retrieve_response_message($response));
                self::log('Response Body: ' . wc_print_r($api_response, true));
                $error_message = $response->get_error_message();
                self::log('Error Failed Message : ' . wc_print_r($error_message, true));
                $order->add_order_note('Error Failed Message : ' . wc_print_r($error_message, true));
                return new WP_Error('error', $$error_message);
            }
            $api_response = json_decode(wp_remote_retrieve_body($response), true);
            self::log('Response Code: ' . wp_remote_retrieve_response_code($response));
            self::log('Response Message: ' . wp_remote_retrieve_response_message($response));
            self::log('Response Body: ' . wc_print_r($api_response, true));
            if (isset($api_response['status']) && $api_response['status'] == "COMPLETED") {
                $gross_amount = isset($api_response['seller_payable_breakdown']['gross_amount']['value']) ? $api_response['seller_payable_breakdown']['gross_amount']['value'] : '';
                $refund_transaction_id = isset($api_response['id']) ? $api_response['id'] : '';
                $order->add_order_note(
                        sprintf(__('Refunded %1$s - Refund ID: %2$s', 'woo-paypal-checkout'), $gross_amount, $refund_transaction_id)
                );
            } else {
                return false;
            }
            return true;
        }

        public function admin_scripts() {
            $screen = get_current_screen();
            $screen_id = $screen ? $screen->id : '';
            if ('woocommerce_page_wc-settings' !== $screen_id) {
                return;
            }
            $suffix = defined('SCRIPT_DEBUG') && SCRIPT_DEBUG ? '' : '.min';
            wp_enqueue_script('woocommerce_paypal_checkout_admin', WPG_ASSET_URL . '/admin/js/woo-paypal-checkout-admin' . $suffix . '.js', array(), WC_VERSION, true);
        }

        public function wpg_request_handler() {
            global $HTTP_RAW_POST_DATA;
            if (isset($_GET['pp_action']) && !empty($_GET['pp_action'])) {
                if ($_GET['pp_action'] == 'get_checkout_details' && isset($_GET['orderID']) && !empty($_GET['orderID'])) {
                    include_once WPG_PLUGIN_DIR . '/includes/gateways/express-checkout-rest/class-woo-paypal-gateway-paypal-checkout-api-handler.php';
                    $this->request = new Woo_PayPal_Gateway_PayPal_Checkout_API_Handler_Rest($this);
                    $this->request->wpg_get_checkout_details(wc_clean($_GET['orderID']));
                    wp_safe_redirect(wc_get_checkout_url());
                    exit();
                } else if ($_GET['pp_action'] == 'set_checkout') {
                    if (isset($_POST['from_checkout']) && 'yes' === $_POST['from_checkout']) {
                        WC()->checkout->process_checkout();
                        $this->wpg_set_customer_data($_POST);
                    } else {
                        include_once WPG_PLUGIN_DIR . '/includes/gateways/express-checkout-rest/class-woo-paypal-gateway-paypal-checkout-api-handler.php';
                        $this->request = new Woo_PayPal_Gateway_PayPal_Checkout_API_Handler_Rest($this);
                        $this->request->wpg_create_order_request();
                        exit();
                    }
                } else if ($_GET['pp_action'] == 'display_order_page' && isset($_GET['orderID']) && !empty($_GET['orderID'])) {
                    include_once WPG_PLUGIN_DIR . '/includes/gateways/express-checkout-rest/class-woo-paypal-gateway-paypal-checkout-api-handler.php';
                    $this->request = new Woo_PayPal_Gateway_PayPal_Checkout_API_Handler_Rest($this);
                    $this->request->wpg_get_checkout_details(wc_clean($_GET['orderID']));
                    $wpg_order_details = WC()->session->get('wpg_order_details');
                    if (!empty($wpg_order_details)) {
                        if (class_exists('Woo_Paypal_Gateway_Public')) {
                            $public_class = new Woo_Paypal_Gateway_Public(WPG_PLUGIN_NAME, WPG_VERSION);
                        } else {
                            include_once WPG_PLUGIN_DIR . '/public/class-woo-paypal-gateway-public.php';
                            $public_class = new Woo_Paypal_Gateway_Public(WPG_PLUGIN_NAME, WPG_VERSION);
                        }
                        $shipping_details = $public_class->wpg_get_mapped_shipping_address($wpg_order_details);
                        $billing_details = $public_class->wpg_get_mapped_billing_address($wpg_order_details);
                        $public_class->update_customer_addresses_from_paypal($shipping_details, $billing_details);
                    }
                    $order_id = absint(WC()->session->get('order_awaiting_payment'));
                    $order = wc_get_order($order_id);
                    if ($wpg_order_details['status'] == 'COMPLETED') {
                        $transaction_id = isset($wpg_order_details['purchase_units']['0']['payments']['captures']['0']['id']) ? $wpg_order_details['purchase_units']['0']['payments']['captures']['0']['id'] : '';
                        $seller_protection = isset($wpg_order_details['purchase_units']['0']['payments']['captures']['0']['seller_protection']['status']) ? $wpg_order_details['purchase_units']['0']['payments']['captures']['0']['seller_protection']['status'] : '';
                        $currency_code = isset($wpg_order_details['purchase_units'][0]['payments']['captures'][0]['seller_receivable_breakdown']['paypal_fee']['currency_code']) ? $wpg_order_details['purchase_units'][0]['payments']['captures'][0]['seller_receivable_breakdown']['paypal_fee']['currency_code'] : '';
                        $value = isset($wpg_order_details['purchase_units'][0]['payments']['captures'][0]['seller_receivable_breakdown']['paypal_fee']['value']) ? $wpg_order_details['purchase_units'][0]['payments']['captures'][0]['seller_receivable_breakdown']['paypal_fee']['value'] : '';
                        update_post_meta($order_id, 'paypal_fee', $value);
                        update_post_meta($order_id, 'paypal_fee_currency_code', $currency_code);
                        $payment_status = isset($wpg_order_details['purchase_units']['0']['payments']['captures']['0']['status']) ? $wpg_order_details['purchase_units']['0']['payments']['captures']['0']['status'] : '';
                        if ($payment_status == 'COMPLETED') {
                            $order->payment_complete($transaction_id);
                            $order->add_order_note(sprintf(__('Payment via %s : %s .', 'paypal-for-woocommerce'), $this->title, ucfirst(strtolower($payment_status))));
                        } else {
                            $payment_status_reason = isset($wpg_order_details['purchase_units']['0']['payments']['captures']['0']['status_details']['reason']) ? $wpg_order_details['purchase_units']['0']['payments']['captures']['0']['status_details']['reason'] : '';
                            $order->update_status('on-hold');
                            $order->add_order_note(sprintf(__('Payment via %s Pending. PayPal reason: %s.', 'paypal-for-woocommerce'), $this->title, $payment_status_reason));
                        }
                        $order->add_order_note(sprintf(__('%s Transaction ID: %s', 'woo-paypal-checkout'), $this->title, $transaction_id));
                        $order->add_order_note('Seller Protection Status: ' . $seller_protection);
                    }
                    WC()->cart->empty_cart();
                    wp_safe_redirect($this->get_return_url($order));
                    exit();
                } elseif ($_GET['pp_action'] == 'cancel_url') {

                    wp_safe_redirect(wc_get_cart_url());
                    exit();
                }
            }
        }

        public function wpg_set_customer_data($data) {
            $customer = WC()->customer;
            $billing_first_name = empty($data['billing_first_name']) ? '' : wc_clean($data['billing_first_name']);
            $billing_last_name = empty($data['billing_last_name']) ? '' : wc_clean($data['billing_last_name']);
            $billing_country = empty($data['billing_country']) ? '' : wc_clean($data['billing_country']);
            $billing_address_1 = empty($data['billing_address_1']) ? '' : wc_clean($data['billing_address_1']);
            $billing_address_2 = empty($data['billing_address_2']) ? '' : wc_clean($data['billing_address_2']);
            $billing_city = empty($data['billing_city']) ? '' : wc_clean($data['billing_city']);
            $billing_state = empty($data['billing_state']) ? '' : wc_clean($data['billing_state']);
            $billing_postcode = empty($data['billing_postcode']) ? '' : wc_clean($data['billing_postcode']);
            $billing_phone = empty($data['billing_phone']) ? '' : wc_clean($data['billing_phone']);
            $billing_email = empty($data['billing_email']) ? '' : wc_clean($data['billing_email']);
            if (isset($data['ship_to_different_address'])) {
                $shipping_first_name = empty($data['shipping_first_name']) ? '' : wc_clean($data['shipping_first_name']);
                $shipping_last_name = empty($data['shipping_last_name']) ? '' : wc_clean($data['shipping_last_name']);
                $shipping_country = empty($data['shipping_country']) ? '' : wc_clean($data['shipping_country']);
                $shipping_address_1 = empty($data['shipping_address_1']) ? '' : wc_clean($data['shipping_address_1']);
                $shipping_address_2 = empty($data['shipping_address_2']) ? '' : wc_clean($data['shipping_address_2']);
                $shipping_city = empty($data['shipping_city']) ? '' : wc_clean($data['shipping_city']);
                $shipping_state = empty($data['shipping_state']) ? '' : wc_clean($data['shipping_state']);
                $shipping_postcode = empty($data['shipping_postcode']) ? '' : wc_clean($data['shipping_postcode']);
            } else {
                $shipping_first_name = $billing_first_name;
                $shipping_last_name = $billing_last_name;
                $shipping_country = $billing_country;
                $shipping_address_1 = $billing_address_1;
                $shipping_address_2 = $billing_address_2;
                $shipping_city = $billing_city;
                $shipping_state = $billing_state;
                $shipping_postcode = $billing_postcode;
            }
            $customer->set_shipping_country($shipping_country);
            $customer->set_shipping_address($shipping_address_1);
            $customer->set_shipping_address_2($shipping_address_2);
            $customer->set_shipping_city($shipping_city);
            $customer->set_shipping_state($shipping_state);
            $customer->set_shipping_postcode($shipping_postcode);
            if (version_compare(WC_VERSION, '3.0', '<')) {
                $customer->shipping_first_name = $shipping_first_name;
                $customer->shipping_last_name = $shipping_last_name;
                $customer->billing_first_name = $billing_first_name;
                $customer->billing_last_name = $billing_last_name;
                $customer->set_country($billing_country);
                $customer->set_address($billing_address_1);
                $customer->set_address_2($billing_address_2);
                $customer->set_city($billing_city);
                $customer->set_state($billing_state);
                $customer->set_postcode($billing_postcode);
                $customer->billing_phone = $billing_phone;
                $customer->billing_email = $billing_email;
            } else {
                $customer->set_shipping_first_name($shipping_first_name);
                $customer->set_shipping_last_name($shipping_last_name);
                $customer->set_billing_first_name($billing_first_name);
                $customer->set_billing_last_name($billing_last_name);
                $customer->set_billing_country($billing_country);
                $customer->set_billing_address_1($billing_address_1);
                $customer->set_billing_address_2($billing_address_2);
                $customer->set_billing_city($billing_city);
                $customer->set_billing_state($billing_state);
                $customer->set_billing_postcode($billing_postcode);
                $customer->set_billing_phone($billing_phone);
                $customer->set_billing_email($billing_email);
            }
        }

        public function wpg_display_order_fee($order_id) {
            $order = wc_get_order($order_id);
            $fee = get_post_meta($order_id, 'paypal_fee', true);
            $currency = get_post_meta($order_id, 'paypal_fee_currency_code', true);
            if ($order->get_status() == 'refunded') {
                return true;
            }
            ?>
            <tr>
                <td class="label stripe-fee">
                    <?php echo wc_help_tip(__('This represents the fee PayPal collects for the transaction.', 'woo-paypal-checkout')); // wpcs: xss ok.   ?>
                    <?php esc_html_e('PayPal Fee:', 'woo-paypal-checkout'); ?>
                </td>
                <td width="1%"></td>
                <td class="total">
                    -&nbsp;<?php echo wc_price($fee, array('currency' => $currency)); ?>
                </td>
            </tr>
            <?php
        }

    }

}
