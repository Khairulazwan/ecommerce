<?php

/**
 * PayPal Gateway.
 *
 * @class       Woo_PayPal_Gateway_PayPal_Checkout
 * @extends     WC_Payment_Gateway
 * @version     1.0.0
 * @package     Woo_Paypal_Checkout
 */
if (!defined('ABSPATH')) {
    exit;
}


class Woo_PayPal_Gateway_PayPal_Checkout_API_Handler_Rest {

    public function __construct($gateway) {
        $this->gateway = $gateway;
        $this->wpg_sandbox = 'yes' === $this->gateway->get_option('sandbox', 'no');
        if ($this->wpg_sandbox) {
            $this->is_sandbox = 'Yes';
            $this->wpg_client_id = $this->gateway->get_option('rest_client_id_sandbox', '');
            $this->wpg_secret_id = $this->gateway->get_option('rest_secret_id_sandbox', '');
            $this->paypal_oauth_api = 'https://api.sandbox.paypal.com/v1/oauth2/token/';
            $this->paypal_order_api = 'https://api.sandbox.paypal.com/v2/checkout/orders/';
            $this->paypal_refund_api = 'https://api.sandbox.paypal.com/v2/payments/captures/';
            $this->basicAuth = base64_encode($this->wpg_client_id . ":" . $this->wpg_secret_id);
            $this->wpg_access_token = get_transient('wpg_sandbox_access_token', false);
            if ($this->wpg_access_token == false) {
                $this->wpg_get_access_token();
            }
        } else {
            $this->is_sandbox = 'No';
            $this->wpg_client_id = $this->gateway->get_option('rest_client_id_live', '');
            $this->wpg_secret_id = $this->gateway->get_option('rest_secret_id_live', '');
            $this->paypal_oauth_api = 'https://api.paypal.com/v1/oauth2/token/';
            $this->paypal_order_api = 'https://api.paypal.com/v2/checkout/orders/';
            $this->paypal_refund_api = 'https://api.paypal.com/v2/payments/captures/';
            $this->basicAuth = base64_encode($this->wpg_client_id . ":" . $this->wpg_secret_id);
            $this->wpg_access_token = get_transient('wpg_live_access_token', false);
            if ($this->wpg_access_token == false) {
                $this->wpg_get_access_token();
            }
        }
    }

    public function wpg_get_access_token() {
        Woo_PayPal_Gateway_PayPal_Checkout_Rest::log(sprintf(__('PayPal Checkout for WooCommerce Version: %s', 'woo-paypal-checkout'), WPG_VERSION));
        Woo_PayPal_Gateway_PayPal_Checkout_Rest::log(sprintf(__('WooCommerce Version: %s', 'woo-paypal-checkout'), WC_VERSION));
        Woo_PayPal_Gateway_PayPal_Checkout_Rest::log('Test Mode: ' . $this->is_sandbox);
        Woo_PayPal_Gateway_PayPal_Checkout_Rest::log('Endpoint: ' . $this->paypal_oauth_api);
        Woo_PayPal_Gateway_PayPal_Checkout_Rest::log('Action Name : ' . 'Get an access token => https://developer.paypal.com/docs/api/overview/#get-an-access-token');
        $response = wp_remote_post($this->paypal_oauth_api, array(
            'method' => 'POST',
            'timeout' => 45,
            'redirection' => 5,
            'httpversion' => '1.0',
            'blocking' => true,
            'headers' => array('Accept' => 'application/json', 'Authorization' => "Basic " . $this->basicAuth, 'PayPal-Partner-Attribution-Id' => 'MBJTechnolabs_SI_SPB'),
            'body' => array('grant_type' => 'client_credentials'),
            'cookies' => array()
                )
        );
        Woo_PayPal_Gateway_PayPal_Checkout_Rest::log('Get access token Request' . $this->paypal_oauth_api);
        if (is_wp_error($response)) {
            $error_message = $response->get_error_message();
            Woo_PayPal_Gateway_PayPal_Checkout_Rest::log('Error Message : ' . wc_print_r($error_message, true));
        } else {
            $api_response = json_decode(wp_remote_retrieve_body($response), true);
            Woo_PayPal_Gateway_PayPal_Checkout_Rest::log('Response Code: ' . wp_remote_retrieve_response_code($response));
            Woo_PayPal_Gateway_PayPal_Checkout_Rest::log('Response Message: ' . wp_remote_retrieve_response_message($response));
            Woo_PayPal_Gateway_PayPal_Checkout_Rest::log('Response Body: ' . wc_print_r($api_response, true));
            if ($this->wpg_sandbox) {
                set_transient('wpg_sandbox_access_token', $api_response['access_token'], 29000);
            } else {
                set_transient('wpg_live_access_token', $api_response['access_token'], 29000);
            }
            $this->wpg_access_token = $api_response['access_token'];
        }
    }

    public function wpg_create_order_request($woo_order_id = null) {
        if ($woo_order_id == null) {
            $cart = $this->wpg_get_details_from_cart();
        } else {
            $cart = $this->wpg_get_details_from_order($woo_order_id);
        }
        $reference_id = wc_generate_order_key();
        WC()->session->set('wpg_reference_id', $reference_id);
        $body_request = array(
            'intent' => 'CAPTURE',
            'purchase_units' =>
            array(
                0 =>
                array(
                    'reference_id' => $reference_id,
                    'amount' =>
                    array(
                        'currency_code' => get_woocommerce_currency(),
                        'value' => $cart['order_total'],
                        'breakdown' => array()
                    )
                ),
            ),
        );
        if (isset($cart['total_item_amount']) && $cart['total_item_amount'] > 0) {
            $body_request['purchase_units'][0]['amount']['breakdown']['item_total'] = array(
                'currency_code' => get_woocommerce_currency(),
                'value' => $cart['total_item_amount'],
            );
        }
        if (isset($cart['shipping']) && $cart['shipping'] > 0) {
            $body_request['purchase_units'][0]['amount']['breakdown']['shipping'] = array(
                'currency_code' => get_woocommerce_currency(),
                'value' => $cart['shipping'],
            );
        }
        if (isset($cart['order_tax']) && $cart['order_tax'] > 0) {
            $body_request['purchase_units'][0]['amount']['breakdown']['tax_total'] = array(
                'currency_code' => get_woocommerce_currency(),
                'value' => $cart['order_tax'],
            );
        }
        if (isset($cart['discount']) && $cart['discount'] > 0) {
            $body_request['purchase_units'][0]['amount']['breakdown']['discount'] = array(
                'currency_code' => get_woocommerce_currency(),
                'value' => $cart['discount'],
            );
        }
        if (isset($cart['items']) && !empty($cart['items'])) {
            foreach ($cart['items'] as $key => $order_items) {
                $body_request['purchase_units'][0]['items'][$key] = array(
                    'name' => $order_items['name'],
                    'quantity' => $order_items['quantity'],
                    'unit_amount' =>
                    array(
                        'currency_code' => get_woocommerce_currency(),
                        'value' => $order_items['amount'],
                    ),
                );
            }
        }
        if ($woo_order_id != null) {
            $order = wc_get_order($woo_order_id);
            $old_wc = version_compare(WC_VERSION, '3.0', '<');
            if (( $old_wc && ( $order->shipping_address_1 || $order->shipping_address_2 ) ) || (!$old_wc && $order->has_shipping_address() )) {
                $shipping_first_name = $old_wc ? $order->shipping_first_name : $order->get_shipping_first_name();
                $shipping_last_name = $old_wc ? $order->shipping_last_name : $order->get_shipping_last_name();
                $shipping_address_1 = $old_wc ? $order->shipping_address_1 : $order->get_shipping_address_1();
                $shipping_address_2 = $old_wc ? $order->shipping_address_2 : $order->get_shipping_address_2();
                $shipping_city = $old_wc ? $order->shipping_city : $order->get_shipping_city();
                $shipping_state = $old_wc ? $order->shipping_state : $order->get_shipping_state();
                $shipping_postcode = $old_wc ? $order->shipping_postcode : $order->get_shipping_postcode();
                $shipping_country = $old_wc ? $order->shipping_country : $order->get_shipping_country();
            } else {
                $shipping_first_name = $old_wc ? $order->billing_first_name : $order->get_billing_first_name();
                $shipping_last_name = $old_wc ? $order->billing_last_name : $order->get_billing_last_name();
                $shipping_address_1 = $old_wc ? $order->billing_address_1 : $order->get_billing_address_1();
                $shipping_address_2 = $old_wc ? $order->billing_address_2 : $order->get_billing_address_2();
                $shipping_city = $old_wc ? $order->billing_city : $order->get_billing_city();
                $shipping_state = $old_wc ? $order->billing_state : $order->get_billing_state();
                $shipping_postcode = $old_wc ? $order->billing_postcode : $order->get_billing_postcode();
                $shipping_country = $old_wc ? $order->billing_country : $order->get_billing_country();
            }
            $body_request['purchase_units'][0]['shipping']['address'] = array(
                'address_line_1' => $shipping_address_1,
                'address_line_2' => $shipping_address_2,
                'admin_area_2' => $shipping_city,
                'admin_area_1' => $shipping_state,
                'postal_code' => $shipping_postcode,
                'country_code' => $shipping_country,
            );
        }
        $body_request = wpg_remove_empty_key($body_request);
        Woo_PayPal_Gateway_PayPal_Checkout_Rest::log(sprintf(__('PayPal Checkout for WooCommerce Version: %s', 'woo-paypal-checkout'), WPG_VERSION));
        Woo_PayPal_Gateway_PayPal_Checkout_Rest::log(sprintf(__('WooCommerce Version: %s', 'woo-paypal-checkout'), WC_VERSION));
        Woo_PayPal_Gateway_PayPal_Checkout_Rest::log('Test Mode: ' . $this->is_sandbox);
        Woo_PayPal_Gateway_PayPal_Checkout_Rest::log('Endpoint: ' . $this->paypal_order_api);
        Woo_PayPal_Gateway_PayPal_Checkout_Rest::log('Action Name : ' . 'Create order => https://developer.paypal.com/docs/api/orders/v2/#orders_create');
        Woo_PayPal_Gateway_PayPal_Checkout_Rest::log('Request : ' . wc_print_r($body_request, true));
        $body_request = json_encode($body_request);
        $response = wp_remote_post($this->paypal_order_api, array(
            'method' => 'POST',
            'timeout' => 45,
            'redirection' => 5,
            'httpversion' => '1.0',
            'blocking' => true,
            'headers' => array('Content-Type' => 'application/json', 'Authorization' => "Bearer " . $this->wpg_access_token, "prefer" => "return=representation", 'PayPal-Partner-Attribution-Id' => 'MBJTechnolabs_SI_SPB'),
            'body' => $body_request,
            'cookies' => array()
                )
        );
        if (is_wp_error($response)) {
            $error_message = $response->get_error_message();
            Woo_PayPal_Gateway_PayPal_Checkout_Rest::log('Error Message : ' . wc_print_r($error_message, true));
        } else {
            $return_response = array();
            $api_response = json_decode(wp_remote_retrieve_body($response), true);
            Woo_PayPal_Gateway_PayPal_Checkout_Rest::log('Response Code: ' . wp_remote_retrieve_response_code($response));
            Woo_PayPal_Gateway_PayPal_Checkout_Rest::log('Response Message: ' . wp_remote_retrieve_response_message($response));
            Woo_PayPal_Gateway_PayPal_Checkout_Rest::log('Response Body: ' . wc_print_r($api_response, true));
            $return_response['orderID'] = $api_response['id'];
            if (!empty(isset($woo_order_id) && !empty($woo_order_id))) {
                update_post_meta($woo_order_id, 'paypal_order_id', $api_response['id']);
            }
            wp_send_json($return_response, 200);
            exit();
        }
    }

    public function wpg_order_capture_request($woo_order_id) {
        $order = wc_get_order($woo_order_id);
        $this->wpg_update_order($order);
        $paypal_order_id = WC()->session->get('wpg_order_id');
        Woo_PayPal_Gateway_PayPal_Checkout_Rest::log(sprintf(__('PayPal Checkout for WooCommerce Version: %s', 'woo-paypal-checkout'), WPG_VERSION));
        Woo_PayPal_Gateway_PayPal_Checkout_Rest::log(sprintf(__('WooCommerce Version: %s', 'woo-paypal-checkout'), WC_VERSION));
        Woo_PayPal_Gateway_PayPal_Checkout_Rest::log('Test Mode: ' . $this->is_sandbox);
        Woo_PayPal_Gateway_PayPal_Checkout_Rest::log('Endpoint: ' . $this->paypal_order_api . $paypal_order_id . '/capture');
        Woo_PayPal_Gateway_PayPal_Checkout_Rest::log('Action Name : ' . 'Capture payment for order  => https://developer.paypal.com/docs/api/orders/v2/#orders_capture');
        Woo_PayPal_Gateway_PayPal_Checkout_Rest::log('Request : ' . wc_print_r($this->paypal_order_api . $paypal_order_id . '/capture', true));
        $response = wp_remote_post($this->paypal_order_api . $paypal_order_id . '/capture', array(
            'timeout' => 45,
            'redirection' => 5,
            'httpversion' => '1.0',
            'blocking' => true,
            'headers' => array('Content-Type' => 'application/json', 'Authorization' => "Bearer " . $this->wpg_access_token, "prefer" => "return=representation", 'PayPal-Partner-Attribution-Id' => 'MBJTechnolabs_SI_SPB'),
            'body' => array(),
            'cookies' => array()
                )
        );
        if (is_wp_error($response)) {
            $error_message = $response->get_error_message();
            Woo_PayPal_Gateway_PayPal_Checkout_Rest::log('Error Message : ' . wc_print_r($error_message, true));
            wc_add_notice($error_message, 'error');
            return false;
        } else {
            $return_response = array();
            $api_response = json_decode(wp_remote_retrieve_body($response), true);
            Woo_PayPal_Gateway_PayPal_Checkout_Rest::log('Response : ' . wc_print_r($api_response, true));
            $return_response['orderID'] = $api_response['id'];
            if (isset($woo_order_id) && !empty($woo_order_id)) {
                update_post_meta($woo_order_id, 'paypal_order_id', $api_response['id']);
            }
            if ($api_response['status'] == 'COMPLETED') {
                $currency_code = isset($api_response['purchase_units'][0]['payments']['captures'][0]['seller_receivable_breakdown']['paypal_fee']['currency_code']) ? $api_response['purchase_units'][0]['payments']['captures'][0]['seller_receivable_breakdown']['paypal_fee']['currency_code'] : '';
                $value = isset($api_response['purchase_units'][0]['payments']['captures'][0]['seller_receivable_breakdown']['paypal_fee']['value']) ? $api_response['purchase_units'][0]['payments']['captures'][0]['seller_receivable_breakdown']['paypal_fee']['value'] : '';
                update_post_meta($woo_order_id, 'paypal_fee', $value);
                update_post_meta($woo_order_id, 'paypal_fee_currency_code', $currency_code);
                $transaction_id = isset($api_response['purchase_units']['0']['payments']['captures']['0']['id']) ? $api_response['purchase_units']['0']['payments']['captures']['0']['id'] : '';
                $seller_protection = isset($api_response['purchase_units']['0']['payments']['captures']['0']['seller_protection']['status']) ? $api_response['purchase_units']['0']['payments']['captures']['0']['seller_protection']['status'] : '';
                $payment_status = isset($api_response['purchase_units']['0']['payments']['captures']['0']['status']) ? $api_response['purchase_units']['0']['payments']['captures']['0']['status'] : '';
                if ($payment_status == 'COMPLETED') {
                    $order->payment_complete($transaction_id);
                    $order->add_order_note(sprintf(__('Payment via %s : %s.', 'paypal-for-woocommerce'), $this->gateway->title, ucfirst(strtolower($payment_status))));
                } else {
                    $payment_status_reason = isset($api_response['purchase_units']['0']['payments']['captures']['0']['status_details']['reason']) ? $api_response['purchase_units']['0']['payments']['captures']['0']['status_details']['reason'] : '';
                    $order->update_status('on-hold');
                    $order->add_order_note(sprintf(__('Payment via %s Pending. PayPal reason: %s.', 'paypal-for-woocommerce'), $this->gateway->title, $payment_status_reason));
                }
                $order->add_order_note(sprintf(__('%s Transaction ID: %s', 'woo-paypal-checkout'), $this->gateway->title, $transaction_id));
                $order->add_order_note('Seller Protection Status: ' . $seller_protection);
            }
            WC()->cart->empty_cart();
            return true;
        }
    }

    public function wpg_get_checkout_details($paypal_order_id) {
        if (WC()->cart->is_empty()) {
            return;
        }
        Woo_PayPal_Gateway_PayPal_Checkout_Rest::log(sprintf(__('PayPal Checkout for WooCommerce Version: %s', 'woo-paypal-checkout'), WPG_VERSION));
        Woo_PayPal_Gateway_PayPal_Checkout_Rest::log(sprintf(__('WooCommerce Version: %s', 'woo-paypal-checkout'), WC_VERSION));
        Woo_PayPal_Gateway_PayPal_Checkout_Rest::log('Test Mode: ' . $this->is_sandbox);
        Woo_PayPal_Gateway_PayPal_Checkout_Rest::log('Endpoint: ' . $this->paypal_order_api . $paypal_order_id);
        Woo_PayPal_Gateway_PayPal_Checkout_Rest::log('Action Name : ' . 'Show order details  => https://developer.paypal.com/docs/api/orders/v2/#orders_get');
        $response = wp_remote_get($this->paypal_order_api . $paypal_order_id, array(
            'timeout' => 45,
            'redirection' => 5,
            'httpversion' => '1.0',
            'blocking' => true,
            'headers' => array('Content-Type' => 'application/json', 'Authorization' => "Bearer " . $this->wpg_access_token, "prefer" => "return=representation", 'PayPal-Partner-Attribution-Id' => 'MBJTechnolabs_SI_SPB'),
            'body' => array(),
            'cookies' => array()
                )
        );
        if (is_wp_error($response)) {
            $error_message = $response->get_error_message();
            Woo_PayPal_Gateway_PayPal_Checkout_Rest::log('Error Message : ' . wc_print_r($error_message, true));
        } else {
            $api_response = json_decode(wp_remote_retrieve_body($response), true);
            Woo_PayPal_Gateway_PayPal_Checkout_Rest::log('Response Code: ' . wp_remote_retrieve_response_code($response));
            Woo_PayPal_Gateway_PayPal_Checkout_Rest::log('Response Message: ' . wp_remote_retrieve_response_message($response));
            Woo_PayPal_Gateway_PayPal_Checkout_Rest::log('Response Body: ' . wc_print_r($api_response, true));
            WC()->session->set('wpg_order_id', $paypal_order_id);
            WC()->session->set('wpg_order_details', $api_response);
        }
    }

    public function wpg_get_details_from_cart() {
        $old_wc = version_compare(WC_VERSION, '3.0', '<');
        $decimals = $this->wpg_get_number_of_decimal_digits();
        $rounded_total = $this->wpg_get_rounded_total_in_cart();
        $discounts = WC()->cart->get_cart_discount_total();
        $details = array(
            'total_item_amount' => wpg_number_format(WC()->cart->cart_contents_total, $decimals) + $discounts,
            'order_tax' => wpg_number_format(WC()->cart->tax_total + WC()->cart->shipping_tax_total, $decimals),
            'shipping' => wpg_number_format(WC()->cart->shipping_total, $decimals),
            'items' => $this->wpg_get_paypal_line_items_from_cart(),
            'shipping_address' => $this->wpg_get_address_from_customer(),
            'email' => $old_wc ? WC()->customer->billing_email : WC()->customer->get_billing_email(),
        );
        return $this->wpg_get_details($details, $discounts, $rounded_total, WC()->cart->total);
    }

    public function wpg_get_number_of_decimal_digits() {
        return $this->wpg_is_currency_supports_zero_decimal() ? 0 : 2;
    }

    public function wpg_is_currency_supports_zero_decimal() {
        return in_array(get_woocommerce_currency(), array('HUF', 'JPY', 'TWD'));
    }

    public function wpg_get_rounded_total_in_cart() {
        $decimals = $this->wpg_get_number_of_decimal_digits();
        $rounded_total = 0;
        foreach (WC()->cart->cart_contents as $cart_item_key => $values) {
            $amount = wpg_number_format($values['line_subtotal'] / $values['quantity'], $decimals);
            $rounded_total += wpg_number_format($amount * $values['quantity'], $decimals);
        }
        return $rounded_total;
    }

    public function wpg_get_paypal_line_items_from_cart() {
        $decimals = $this->wpg_get_number_of_decimal_digits();
        $items = array();
        foreach (WC()->cart->cart_contents as $cart_item_key => $values) {
            $amount = wpg_number_format($values['line_subtotal'] / $values['quantity'], $decimals);
            if (version_compare(WC_VERSION, '3.0', '<')) {
                $name = $values['data']->post->post_title;
                $description = $values['data']->post->post_content;
            } else {
                $product = $values['data'];
                $name = $product->get_name();
                $description = $product->get_description();
            }
            $item = array(
                'name' => $name,
                'description' => $description,
                'quantity' => $values['quantity'],
                'amount' => wpg_number_format($amount),
            );
            $items[] = $item;
        }
        return $items;
    }

    public function wpg_get_address_from_customer() {
        $customer = WC()->customer;
        $old_wc = version_compare(WC_VERSION, '3.0', '<');
        if ($customer->get_shipping_address() || $customer->get_shipping_address_2()) {
            $shipping_first_name = $old_wc ? $customer->shipping_first_name : $customer->get_shipping_first_name();
            $shipping_last_name = $old_wc ? $customer->shipping_last_name : $customer->get_shipping_last_name();
            $shipping_address_1 = $customer->get_shipping_address();
            $shipping_address_2 = $customer->get_shipping_address_2();
            $shipping_city = $customer->get_shipping_city();
            $shipping_state = $customer->get_shipping_state();
            $shipping_postcode = $customer->get_shipping_postcode();
            $shipping_country = $customer->get_shipping_country();
        } else {
            $shipping_first_name = $old_wc ? $customer->billing_first_name : $customer->get_billing_first_name();
            $shipping_last_name = $old_wc ? $customer->billing_last_name : $customer->get_billing_last_name();
            $shipping_address_1 = $old_wc ? $customer->get_address() : $customer->get_billing_address_1();
            $shipping_address_2 = $old_wc ? $customer->get_address_2() : $customer->get_billing_address_2();
            $shipping_city = $old_wc ? $customer->get_city() : $customer->get_billing_city();
            $shipping_state = $old_wc ? $customer->get_state() : $customer->get_billing_state();
            $shipping_postcode = $old_wc ? $customer->get_postcode() : $customer->get_billing_postcode();
            $shipping_country = $old_wc ? $customer->get_country() : $customer->get_billing_country();
        }
        return array(
            'first_name' => $shipping_first_name,
            'last_name' => $shipping_last_name,
            'company' => '',
            'address_1' => $shipping_address_1,
            'address_2' => $shipping_address_2,
            'city' => $shipping_city,
            'state' => $shipping_state,
            'postcode' => $shipping_postcode,
            'country' => $shipping_country,
            'phone' => $old_wc ? $customer->billing_phone : $customer->get_billing_phone(),
        );
    }

    public function wpg_get_details($details, $discounts, $rounded_total, $total) {
        $decimals = $this->wpg_get_number_of_decimal_digits();
        $discounts = wpg_number_format($discounts, $decimals);
        $details['order_total'] = wpg_number_format(
                $details['total_item_amount'] + $details['order_tax'] + $details['shipping'], $decimals
        );
        $diff = 0;
        if ($details['total_item_amount'] != $rounded_total) {
            unset($details['items']);
        }
        if ($details['total_item_amount'] == $discounts) {
            unset($details['items']);
        } else if ($discounts > 0 && $discounts < $details['total_item_amount'] && !empty($details['items'])) {
            $details['discount'] = $discounts;
        }
        $details['discount'] = $discounts;
        $details['ship_discount_amount'] = 0;
        $wc_order_total = wpg_number_format($total, $decimals);
        $discounted_total = $details['order_total'];
        if ($wc_order_total != $discounted_total) {
            if ($discounted_total < $wc_order_total) {
                $details['order_tax'] += $wc_order_total - $discounted_total;
                $details['order_tax'] = wpg_number_format($details['order_tax'], $decimals);
            } else {
                $details['ship_discount_amount'] += $wc_order_total - $discounted_total;
                $details['ship_discount_amount'] = wpg_number_format($details['ship_discount_amount'], $decimals);
            }
            $details['order_total'] = $wc_order_total;
        }
        if (!is_numeric($details['shipping'])) {
            $details['shipping'] = 0;
        }
        $lisum = 0;
        if (!empty($details['items'])) {
            foreach ($details['items'] as $li => $values) {
                $lisum += $values['quantity'] * $values['amount'];
            }
        }
        if (abs($lisum) > 0.000001 && 0.0 !== (float) $diff) {
            $details['items'][] = $this->wpg_get_extra_offset_line_item($details['total_item_amount'] - $lisum);
        }
        return $details;
    }

    public function wpg_get_details_from_order($order_id) {
        $order = wc_get_order($order_id);
        $decimals = $this->wpg_is_currency_supports_zero_decimal() ? 0 : 2;
        $rounded_total = $this->wpg_get_rounded_total_in_order($order);
        $discounts = $order->get_total_discount();
        $details = array(
            'total_item_amount' => wpg_number_format($order->get_subtotal(), $decimals),
            'order_tax' => wpg_number_format($order->get_total_tax(), $decimals),
            'shipping' => wpg_number_format(( version_compare(WC_VERSION, '3.0', '<') ? $order->get_total_shipping() : $order->get_shipping_total()), $decimals),
            'items' => $this->wpg_get_paypal_line_items_from_order($order),
        );
        $details = $this->wpg_get_details($details, $order->get_total_discount(), $rounded_total, $order->get_total());
        $old_wc = version_compare(WC_VERSION, '3.0', '<');
        if (( $old_wc && ( $order->shipping_address_1 || $order->shipping_address_2 ) ) || (!$old_wc && $order->has_shipping_address() )) {
            $shipping_first_name = $old_wc ? $order->shipping_first_name : $order->get_shipping_first_name();
            $shipping_last_name = $old_wc ? $order->shipping_last_name : $order->get_shipping_last_name();
            $shipping_address_1 = $old_wc ? $order->shipping_address_1 : $order->get_shipping_address_1();
            $shipping_address_2 = $old_wc ? $order->shipping_address_2 : $order->get_shipping_address_2();
            $shipping_city = $old_wc ? $order->shipping_city : $order->get_shipping_city();
            $shipping_state = $old_wc ? $order->shipping_state : $order->get_shipping_state();
            $shipping_postcode = $old_wc ? $order->shipping_postcode : $order->get_shipping_postcode();
            $shipping_country = $old_wc ? $order->shipping_country : $order->get_shipping_country();
        } else {
            $shipping_first_name = $old_wc ? $order->billing_first_name : $order->get_billing_first_name();
            $shipping_last_name = $old_wc ? $order->billing_last_name : $order->get_billing_last_name();
            $shipping_address_1 = $old_wc ? $order->billing_address_1 : $order->get_billing_address_1();
            $shipping_address_2 = $old_wc ? $order->billing_address_2 : $order->get_billing_address_2();
            $shipping_city = $old_wc ? $order->billing_city : $order->get_billing_city();
            $shipping_state = $old_wc ? $order->billing_state : $order->get_billing_state();
            $shipping_postcode = $old_wc ? $order->billing_postcode : $order->get_billing_postcode();
            $shipping_country = $old_wc ? $order->billing_country : $order->get_billing_country();
        }
        if (empty($shipping_country)) {
            $shipping_country = WC()->countries->get_base_country();
        }
        $details['shipping_address'] = array(
            'first_name' => $shipping_first_name,
            'last_name' => $shipping_last_name,
            'company' => '',
            'address_1' => $shipping_address_1,
            'address_2' => $shipping_address_2,
            'city' => $shipping_city,
            'state' => $shipping_state,
            'postcode' => $shipping_postcode,
            'country' => $shipping_country,
            'phone' => $old_wc ? $order->billing_phone : $order->get_billing_phone(),
        );
        $details['email'] = $old_wc ? $order->billing_email : $order->get_billing_email();
        return $details;
    }

    public function wpg_get_rounded_total_in_order($order) {
        $decimals = $this->wpg_get_number_of_decimal_digits();
        $order = wc_get_order($order);
        $rounded_total = 0;
        foreach ($order->get_items() as $cart_item_key => $values) {
            $amount = wpg_number_format($values['line_subtotal'] / $values['qty'], $decimals);
            $rounded_total += wpg_number_format($amount * $values['qty'], $decimals);
        }
        return $rounded_total;
    }

    public function wpg_get_paypal_line_items_from_order($order) {
        $decimals = $this->wpg_get_number_of_decimal_digits();
        $order = wc_get_order($order);
        $items = array();
        foreach ($order->get_items() as $cart_item_key => $values) {
            $amount = wpg_number_format($values['line_subtotal'] / $values['qty'], $decimals);
            $item = array(
                'name' => $values['name'],
                'quantity' => $values['qty'],
                'amount' => wpg_number_format($amount),
            );

            $items[] = $item;
        }
        return $items;
    }

    public function wpg_refund_order($order_id, $amount, $reason, $transaction_id) {
        Woo_PayPal_Gateway_PayPal_Checkout_Rest::log(sprintf(__('PayPal Checkout for WooCommerce Version: %s', 'woo-paypal-checkout'), WPG_VERSION));
        Woo_PayPal_Gateway_PayPal_Checkout_Rest::log(sprintf(__('WooCommerce Version: %s', 'woo-paypal-checkout'), WC_VERSION));
        Woo_PayPal_Gateway_PayPal_Checkout_Rest::log('Test Mode: ' . $this->is_sandbox);
        Woo_PayPal_Gateway_PayPal_Checkout_Rest::log('Endpoint: ' . $this->paypal_refund_api . $transaction_id . '/refund');
        Woo_PayPal_Gateway_PayPal_Checkout_Rest::log('Action Name : ' . 'Refund captured payment => https://developer.paypal.com/docs/api/payments/v2/#captures_refund');
        $order = wc_get_order($order_id);
        $decimals = $this->wpg_get_number_of_decimal_digits();
        $body_request = array(
            'amount' =>
            array(
                'value' => wpg_number_format($amount, $decimals),
                'currency_code' => $order->get_currency()
            )
        );
        $body_request = wpg_remove_empty_key($body_request);
        $body_request = json_encode($body_request);
        $response = wp_remote_post($this->paypal_refund_api . $transaction_id . '/refund', array(
            'timeout' => 45,
            'redirection' => 5,
            'httpversion' => '1.0',
            'blocking' => true,
            'headers' => array('Content-Type' => 'application/json', 'Authorization' => "Bearer " . $this->wpg_access_token, "prefer" => "return=representation", 'PayPal-Partner-Attribution-Id' => 'MBJTechnolabs_SI_SPB'),
            'body' => $body_request,
            'cookies' => array()
                )
        );
        return $response;
    }

    public function wpg_update_order($order) {
        try {
            $patch_request = array();
            $update_amount_request = array();
            $reference_id = WC()->session->get('wpg_reference_id');
            $order_id = version_compare(WC_VERSION, '3.0', '<') ? $order->id : $order->get_id();
            $cart = $this->wpg_get_details_from_order($order_id);
            $old_wc = version_compare(WC_VERSION, '3.0', '<');
            if (( $old_wc && ( $order->shipping_address_1 || $order->shipping_address_2 ) ) || (!$old_wc && $order->has_shipping_address() )) {
                $shipping_first_name = $old_wc ? $order->shipping_first_name : $order->get_shipping_first_name();
                $shipping_last_name = $old_wc ? $order->shipping_last_name : $order->get_shipping_last_name();
                $shipping_address_1 = $old_wc ? $order->shipping_address_1 : $order->get_shipping_address_1();
                $shipping_address_2 = $old_wc ? $order->shipping_address_2 : $order->get_shipping_address_2();
                $shipping_city = $old_wc ? $order->shipping_city : $order->get_shipping_city();
                $shipping_state = $old_wc ? $order->shipping_state : $order->get_shipping_state();
                $shipping_postcode = $old_wc ? $order->shipping_postcode : $order->get_shipping_postcode();
                $shipping_country = $old_wc ? $order->shipping_country : $order->get_shipping_country();
            } else {
                $shipping_first_name = $old_wc ? $order->billing_first_name : $order->get_billing_first_name();
                $shipping_last_name = $old_wc ? $order->billing_last_name : $order->get_billing_last_name();
                $shipping_address_1 = $old_wc ? $order->billing_address_1 : $order->get_billing_address_1();
                $shipping_address_2 = $old_wc ? $order->billing_address_2 : $order->get_billing_address_2();
                $shipping_city = $old_wc ? $order->billing_city : $order->get_billing_city();
                $shipping_state = $old_wc ? $order->billing_state : $order->get_billing_state();
                $shipping_postcode = $old_wc ? $order->billing_postcode : $order->get_billing_postcode();
                $shipping_country = $old_wc ? $order->billing_country : $order->get_billing_country();
            }
            $shipping_address_request = array(
                'address_line_1' => $shipping_address_1,
                'address_line_2' => $shipping_address_2,
                'admin_area_2' => $shipping_city,
                'admin_area_1' => $shipping_state,
                'postal_code' => $shipping_postcode,
                'country_code' => $shipping_country,
            );
            $patch_request[] = array(
                'op' => 'replace',
                'path' => '/intent',
                'value' => 'CAPTURE',
            );
            if (isset($cart['total_item_amount']) && $cart['total_item_amount'] > 0) {
                $update_amount_request['item_total'] = array(
                    'currency_code' => get_woocommerce_currency(),
                    'value' => $cart['total_item_amount'],
                );
            }
            if (isset($cart['discount']) && $cart['discount'] > 0) {
                $update_amount_request['discount'] = array(
                    'currency_code' => get_woocommerce_currency(),
                    'value' => $cart['discount'],
                );
            }
            if (isset($cart['shipping']) && $cart['shipping'] > 0) {
                $update_amount_request['shipping'] = array(
                    'currency_code' => get_woocommerce_currency(),
                    'value' => $cart['shipping'],
                );
            }
            if (isset($cart['order_tax']) && $cart['order_tax'] > 0) {
                $update_amount_request['tax_total'] = array(
                    'currency_code' => get_woocommerce_currency(),
                    'value' => $cart['order_tax'],
                );
            }
            $patch_request[] = array(
                'op' => 'replace',
                'path' => "/purchase_units/@reference_id=='$reference_id'/amount",
                'value' =>
                array(
                    'currency_code' => $old_wc ? $order->get_order_currency() : $order->get_currency(),
                    'value' => $cart['order_total'],
                    'breakdown' => $update_amount_request
                ),
            );
            $patch_request[] = array(
                'op' => 'replace',
                'path' => "/purchase_units/@reference_id=='$reference_id'/shipping/address",
                'value' => $shipping_address_request
            );
            $patch_request_json = json_encode($patch_request);
            $paypal_order_id = WC()->session->get('wpg_order_id');

            Woo_PayPal_Gateway_PayPal_Checkout_Rest::log('Test Mode: ' . $this->is_sandbox);
            Woo_PayPal_Gateway_PayPal_Checkout_Rest::log('Endpoint: ' . $this->paypal_order_api . $paypal_order_id);
            Woo_PayPal_Gateway_PayPal_Checkout_Rest::log('Request: ' . print_r($patch_request_json, true));
            Woo_PayPal_Gateway_PayPal_Checkout_Rest::log('Action Name : ' . 'Update order => https://developer.paypal.com/docs/api/orders/v2/#orders_patch');
            $response = wp_remote_request($this->paypal_order_api . $paypal_order_id, array(
                'timeout' => 45,
                'method' => 'PATCH',
                'redirection' => 5,
                'httpversion' => '1.0',
                'blocking' => true,
                'headers' => array('Content-Type' => 'application/json', 'Authorization' => "Bearer " . $this->wpg_access_token, "prefer" => "return=representation", 'PayPal-Partner-Attribution-Id' => 'MBJTechnolabs_SI_SPB'),
                'body' => $patch_request_json,
                'cookies' => array()
                    )
            );
            if (is_wp_error($response)) {
                $error_message = $response->get_error_message();
                Woo_PayPal_Gateway_PayPal_Checkout_Rest::log('Error Message : ' . wc_print_r($response, true));
                wc_add_notice($error_message, 'error');
                return false;
            } else {
                $api_response = json_decode(wp_remote_retrieve_body($response), true);
                Woo_PayPal_Gateway_PayPal_Checkout_Rest::log('Response Code: ' . wp_remote_retrieve_response_code($response));
                Woo_PayPal_Gateway_PayPal_Checkout_Rest::log('Response Message: ' . wp_remote_retrieve_response_message($response));
                Woo_PayPal_Gateway_PayPal_Checkout_Rest::log('Response Body: ' . wc_print_r($api_response, true));
            }
        } catch (Exception $ex) {
            
        }
    }

    public function wpg_get_extra_offset_line_item($amount) {
        $decimals = $this->wpg_get_number_of_decimal_digits();
        return array(
            'name' => 'Line Item Amount Offset',
            'description' => 'Adjust cart calculation discrepancy',
            'quantity' => 1,
            'amount' => wpg_number_format($amount, $decimals),
        );
    }

}
