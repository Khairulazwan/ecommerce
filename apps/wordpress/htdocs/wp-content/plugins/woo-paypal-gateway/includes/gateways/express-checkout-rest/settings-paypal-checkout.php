<?php

defined('ABSPATH') || exit;

$require_ssl = '';
if (wc_checkout_is_https() == false) {
    $require_ssl = __('This image requires an SSL host.  Please upload your image to <a target="_blank" href="http://www.sslpic.com">www.sslpic.com</a> and enter the image URL here.', 'woo-paypal-checkout');
}
$this->button_label_array = array(
    'paypal' => __('PayPal', 'woo-paypal-checkout'),
    'checkout' => __('Checkout', 'woo-paypal-checkout'),
    'pay' => __('Pay', 'woo-paypal-checkout')
);
return $this->form_fields = array(
    'enabled' => array(
        'title' => __('Enable/Disable', 'woo-paypal-checkout'),
        'label' => __('Enable PayPal Express', 'woo-paypal-checkout'),
        'type' => 'checkbox',
        'description' => '',
        'default' => 'no'
    ),
    'title' => array(
        'title' => __('Title', 'woo-paypal-checkout'),
        'type' => 'text',
        'description' => __('This controls the title which the user sees during checkout.', 'woo-paypal-checkout'),
        'default' => __('PayPal Express', 'woo-paypal-checkout'),
        'desc_tip' => true,
    ),
    'description' => array(
        'title' => __('Description', 'woo-paypal-checkout'),
        'type' => 'textarea',
        'description' => __('This controls the description which the user sees during checkout.', 'woo-paypal-checkout'),
        'default' => __("Pay via PayPal; you can pay with your credit card if you don't have a PayPal account", 'woo-paypal-checkout'),
        'desc_tip' => true,
    ),
    'account_settings' => array(
        'title' => __('Account Settings', 'woo-paypal-checkout'),
        'type' => 'title',
        'description' => '',
    ),
    'sandbox' => array(
        'title' => __('Sandbox Mode', 'woo-paypal-checkout'),
        'type' => 'checkbox',
        'label' => __('Enable PayPal Sandbox Mode', 'woo-paypal-checkout'),
        'default' => 'yes',
        'description' => sprintf(__('Place the payment gateway in development mode. Sign up for a developer account <a href="%s" target="_blank">here</a>', 'woo-paypal-checkout'), 'https://developer.paypal.com/'),
    ),
    'rest_client_id_sandbox' => array(
        'title' => __('Sandbox Client ID', 'woo-paypal-checkout'),
        'type' => 'password',
        'description' => 'Enter your Sandbox PayPal Rest API Client ID',
        'default' => '',
        'desc_tip' => true,
    ),
    'rest_secret_id_sandbox' => array(
        'title' => __('Sandbox Secret ID', 'woo-paypal-checkout'),
        'type' => 'password',
        'description' => __('Enter your Sandbox PayPal Rest API Secret ID.', 'woo-paypal-checkout'),
        'default' => '',
        'desc_tip' => true
    ),
    'rest_client_id_live' => array(
        'title' => __('Live Client ID', 'woo-paypal-checkout'),
        'type' => 'password',
        'description' => 'Enter your PayPal Rest API Client ID',
        'default' => '',
        'desc_tip' => true
    ),
    'rest_secret_id_live' => array(
        'title' => __('Live Secret ID', 'woo-paypal-checkout'),
        'type' => 'password',
        'description' => __('Enter your PayPal Rest API Secret ID.', 'woo-paypal-checkout'),
        'default' => '',
        'desc_tip' => true,
    ),
   /* 'paypal_hosted_display_settings' => array(
        'title' => __('PayPal hosted Checkout Settings (Optional)', 'woo-paypal-checkout'),
        'type' => 'title',
        'description' => __('Customize the appearance of PayPal Checkout on the PayPal side.', 'woo-paypal-checkout'),
    ),
    'page_style' => array(
        'title' => __('Page Style', 'woo-paypal-checkout'),
        'type' => 'text',
        'description' => __('Optionally enter the name of the page style you wish to use. These are defined within your PayPal account.', 'woo-paypal-checkout'),
        'default' => '',
        'desc_tip' => true,
        'placeholder' => __('Optional', 'woo-paypal-checkout'),
    ),
    'brand_name' => array(
        'title' => __('Brand Name', 'woo-paypal-checkout'),
        'type' => 'text',
        'description' => __('This controls what users see as the brand / company name on PayPal review pages.', 'woo-paypal-checkout'),
        'default' => __(get_bloginfo('name'), 'woo-paypal-checkout'),
        'desc_tip' => true,
    ),
    'checkout_logo' => array(
        'title' => __('PayPal Checkout Logo Image(190x60)', 'woo-paypal-checkout'),
        'type' => 'text',
        'description' => __('This controls what users see as the logo on PayPal review pages. ', 'woo-paypal-checkout') . $require_ssl,
        'default' => '',
        'desc_tip' => true,
        'placeholder' => __('Optional', 'woo-paypal-checkout'),
    ),
    'checkout_logo_hdrimg' => array(
        'title' => __('Header Image (750x90)', 'woo-paypal-checkout'),
        'type' => 'text',
        'description' => __('This controls what users see as the header banner on PayPal review pages. ', 'woo-paypal-checkout') . $require_ssl,
        'default' => '',
        'desc_tip' => true,
        'placeholder' => __('Optional', 'woo-paypal-checkout'),
    ),
    'landing_page' => array(
        'title' => __('Landing Page', 'woo-paypal-checkout'),
        'type' => 'select',
        'class' => 'wc-enhanced-select',
        'description' => __('Type of PayPal page to display.', 'woo-paypal-checkout'),
        'default' => 'Login',
        'desc_tip' => true,
        'options' => array(
            'Billing' => _x('Billing (Non-PayPal account)', 'Type of PayPal page', 'woo-paypal-checkout'),
            'Login' => _x('Login (PayPal account login)', 'Type of PayPal page', 'woo-paypal-checkout'),
        ),
    ),*/
    'display_settings' => array(
        'title' => __('PayPal Smart Button Display settings', 'woo-paypal-checkout'),
        'type' => 'title',
        'description' => ''
    ),
    'show_on_product_page' => array(
        'title' => __('Product Page', 'woo-paypal-checkout'),
        'type' => 'checkbox',
        'label' => __('Show the Express Checkout button on product detail pages.', 'woo-paypal-checkout'),
        'default' => 'yes',
        'description' => sprintf(__('Allows customers to checkout using PayPal directly from a product page.')),
        'desc_tip' => true,
    ),
    'product_button_layout' => array(
        'title' => __('Button Layout', 'woo-paypal-checkout'),
        'type' => 'select',
        'class' => 'wc-enhanced-select product_smart_button',
        'description' => __('Select Vertical for stacked buttons, and Horizontal for side-by-side buttons.', 'woo-paypal-checkout'),
        'default' => 'vertical',
        'desc_tip' => true,
        'options' => array(
            'horizontal' => __('Horizontal', 'woo-paypal-checkout'),
            'vertical' => __('Vertical', 'woo-paypal-checkout')
        ),
    ),
    'product_button_color' => array(
        'title' => __('Button Color', 'woo-paypal-checkout'),
        'type' => 'select',
        'class' => 'wc-enhanced-select product_smart_button',
        'description' => __('Set the color you would like to use for the PayPal button.', 'woo-paypal-checkout'),
        'default' => 'gold',
        'desc_tip' => true,
        'options' => array(
            'gold' => __('Gold', 'woo-paypal-checkout'),
            'blue' => __('Blue', 'woo-paypal-checkout'),
            'silver' => __('Silver', 'woo-paypal-checkout')
        ),
    ),
    'product_button_shape' => array(
        'title' => __('Button Shape', 'woo-paypal-checkout'),
        'type' => 'select',
        'class' => 'wc-enhanced-select product_smart_button',
        'description' => __('Set the shape you would like to use for the buttons.', 'woo-paypal-checkout'),
        'default' => 'rect',
        'desc_tip' => true,
        'options' => array(
            'rect' => __('Rect', 'woo-paypal-checkout'),
            'pill' => __('Pill', 'woo-paypal-checkout')
        ),
    ),
    'product_button_label' => array(
        'title' => __('Button Label', 'woo-paypal-checkout'),
        'type' => 'select',
        'class' => 'wc-enhanced-select product_smart_button',
        'description' => __('Set the label type you would like to use for the PayPal button.', 'woo-paypal-checkout'),
        'default' => 'paypal',
        'desc_tip' => true,
        'options' => $this->button_label_array,
    ),
    'show_on_cart' => array(
        'title' => __('Cart Page', 'woo-paypal-checkout'),
        'label' => __('Show Express Checkout button on shopping cart page.', 'woo-paypal-checkout'),
        'type' => 'checkbox',
        'default' => 'yes',
        'desc_tip' => true,
    ),
    'cart_button_layout' => array(
        'title' => __('Button Layout', 'woo-paypal-checkout'),
        'type' => 'select',
        'class' => 'wc-enhanced-select cart_smart_button',
        'description' => __('Select Vertical for stacked buttons, and Horizontal for side-by-side buttons.', 'woo-paypal-checkout'),
        'default' => 'vertical',
        'desc_tip' => true,
        'options' => array(
            'vertical' => __('Vertical', 'woo-paypal-checkout'),
            'horizontal' => __('Horizontal', 'woo-paypal-checkout')
        ),
    ),
    'cart_button_color' => array(
        'title' => __('Button Color', 'woo-paypal-checkout'),
        'type' => 'select',
        'class' => 'wc-enhanced-select cart_smart_button',
        'description' => __('Set the color you would like to use for the PayPal button.', 'woo-paypal-checkout'),
        'default' => 'gold',
        'desc_tip' => true,
        'options' => array(
            'gold' => __('Gold', 'woo-paypal-checkout'),
            'blue' => __('Blue', 'woo-paypal-checkout'),
            'silver' => __('Silver', 'woo-paypal-checkout')
        ),
    ),
    'cart_button_shape' => array(
        'title' => __('Button Shape', 'woo-paypal-checkout'),
        'type' => 'select',
        'class' => 'wc-enhanced-select cart_smart_button',
        'description' => __('Set the shape you would like to use for the buttons.', 'woo-paypal-checkout'),
        'default' => 'rect',
        'desc_tip' => true,
        'options' => array(
            'rect' => __('Rect', 'woo-paypal-checkout'),
            'pill' => __('Pill', 'woo-paypal-checkout')
        ),
    ),
    'cart_button_label' => array(
        'title' => __('Button Label', 'woo-paypal-checkout'),
        'type' => 'select',
        'class' => 'wc-enhanced-select cart_smart_button',
        'description' => __('Set the label type you would like to use for the PayPal button.', 'woo-paypal-checkout'),
        'default' => 'paypal',
        'desc_tip' => true,
        'options' => $this->button_label_array,
    ),
    'show_on_mini_cart' => array(
        'title' => __('Sidebar Widget Cart (Mini-cart)', 'woo-paypal-checkout'),
        'label' => __('Show Express Checkout button on Sidebar Widget Cart (Mini-cart).', 'woo-paypal-checkout'),
        'type' => 'checkbox',
        'default' => 'yes',
        'desc_tip' => true,
    ),
    'mini_cart_button_layout' => array(
        'title' => __('Button Layout', 'woo-paypal-checkout'),
        'type' => 'select',
        'class' => 'wc-enhanced-select mini_cart_smart_button',
        'description' => __('Select Vertical for stacked buttons, and Horizontal for side-by-side buttons.', 'woo-paypal-checkout'),
        'default' => 'vertical',
        'desc_tip' => true,
        'options' => array(
            'vertical' => __('Vertical', 'woo-paypal-checkout'),
            'horizontal' => __('Horizontal', 'woo-paypal-checkout')
        ),
    ),
    'mini_cart_button_color' => array(
        'title' => __('Button Color', 'woo-paypal-checkout'),
        'type' => 'select',
        'class' => 'wc-enhanced-select mini_cart_smart_button',
        'description' => __('Set the color you would like to use for the PayPal button.', 'woo-paypal-checkout'),
        'default' => 'gold',
        'desc_tip' => true,
        'options' => array(
            'gold' => __('Gold', 'woo-paypal-checkout'),
            'blue' => __('Blue', 'woo-paypal-checkout'),
            'silver' => __('Silver', 'woo-paypal-checkout')
        ),
    ),
    'mini_cart_button_shape' => array(
        'title' => __('Button Shape', 'woo-paypal-checkout'),
        'type' => 'select',
        'class' => 'wc-enhanced-select mini_cart_smart_button',
        'description' => __('Set the shape you would like to use for the buttons.', 'woo-paypal-checkout'),
        'default' => 'rect',
        'desc_tip' => true,
        'options' => array(
            'rect' => __('Rect', 'woo-paypal-checkout'),
            'pill' => __('Pill', 'woo-paypal-checkout')
        ),
    ),
    'mini_cart_button_label' => array(
        'title' => __('Button Label', 'woo-paypal-checkout'),
        'type' => 'select',
        'class' => 'wc-enhanced-select mini_cart_smart_button',
        'description' => __('Set the label type you would like to use for the PayPal button.', 'woo-paypal-checkout'),
        'default' => 'paypal',
        'desc_tip' => true,
        'options' => $this->button_label_array,
    ),
    'show_on_checkout_page' => array(
        'title' => __('Customize paypal smart button on checkout', 'woo-paypal-checkout'),
        'type' => 'title',
        'description' => 'Allows customers to checkout using PayPal directly from a checkout page',
        'desc_tip' => true
    ),
    'checkout_button_layout' => array(
        'title' => __('Button Layout', 'woo-paypal-checkout'),
        'type' => 'select',
        'class' => 'wc-enhanced-select checkout_smart_button',
        'description' => __('Select Vertical for stacked buttons, and Horizontal for side-by-side buttons.', 'woo-paypal-checkout'),
        'default' => 'vertical',
        'desc_tip' => true,
        'options' => array(
            'vertical' => __('Vertical', 'woo-paypal-checkout'),
            'horizontal' => __('Horizontal', 'woo-paypal-checkout')
        ),
    ),
    'checkout_button_color' => array(
        'title' => __('Button Color', 'woo-paypal-checkout'),
        'type' => 'select',
        'class' => 'wc-enhanced-select checkout_smart_button',
        'description' => __('Set the color you would like to use for the PayPal button.', 'woo-paypal-checkout'),
        'default' => 'gold',
        'desc_tip' => true,
        'options' => array(
            'gold' => __('Gold', 'woo-paypal-checkout'),
            'blue' => __('Blue', 'woo-paypal-checkout'),
            'silver' => __('Silver', 'woo-paypal-checkout')
        ),
    ),
    'checkout_button_shape' => array(
        'title' => __('Button Shape', 'woo-paypal-checkout'),
        'type' => 'select',
        'class' => 'wc-enhanced-select checkout_smart_button',
        'description' => __('Set the shape you would like to use for the buttons.', 'woo-paypal-checkout'),
        'default' => 'rect',
        'desc_tip' => true,
        'options' => array(
            'rect' => __('Rect', 'woo-paypal-checkout'),
            'pill' => __('Pill', 'woo-paypal-checkout')
        ),
    ),
    'checkout_button_label' => array(
        'title' => __('Button Label', 'woo-paypal-checkout'),
        'type' => 'select',
        'class' => 'wc-enhanced-select checkout_smart_button',
        'description' => __('Set the label type you would like to use for the PayPal button.', 'woo-paypal-checkout'),
        'default' => 'paypal',
        'desc_tip' => true,
        'options' => $this->button_label_array,
    ),
    'advanced' => array(
        'title' => __('Advanced Settings (Optional)', 'woo-paypal-checkout'),
        'type' => 'title',
        'description' => '',
    ),
    'invoice_id_prefix' => array(
        'title' => __('Invoice ID Prefix', 'woo-paypal-checkout'),
        'type' => 'text',
        'description' => __('Add a prefix to the invoice ID sent to PayPal. This can resolve duplicate invoice problems when working with multiple websites on the same PayPal account.', 'woo-paypal-checkout'),
        'desc_tip' => true,
        'default' => 'WC-EC'
    ),
    /*'paymentaction' => array(
        'title' => __('Payment Action', 'woo-paypal-checkout'),
        'type' => 'select',
        'class' => 'wc-enhanced-select',
        'description' => __('Choose whether you wish to capture funds immediately or authorize payment only.', 'woo-paypal-checkout'),
        'default' => 'sale',
        'desc_tip' => true,
        'options' => array(
            'Sale' => __('Sale', 'woo-paypal-checkout'),
            'Authorization' => __('Authorization', 'woo-paypal-checkout'),
            'Order' => __('Order', 'woo-paypal-checkout')
        ),
    ),*/
    'debug' => array(
        'title' => __('Debug log', 'woo-paypal-checkout'),
        'type' => 'checkbox',
        'label' => 'Enable logging',
        'default' => 'yes',
        'description' => sprintf(__('Log PayPal events, such as IPN requests, inside %s Note: this may log personal information. We recommend using this for debugging purposes only and deleting the logs when finished.', 'woo-paypal-checkout'), '<code>' . version_compare(WC_VERSION, '3.0', '<') ? wc_get_log_file_path('paypal_express') : WC_Log_Handler_File::get_log_file_path('paypal_express') . '</code>'),
    )
);
