(function ($) {
    'use strict';
    $(function () {
        $('#woocommerce_wpg_paypal_checkout_show_on_product_page').change(function () {
            if ($(this).is(':checked')) {
                $("#woocommerce_wpg_paypal_checkout_product_button_layout").closest('tr').show();
                $("#woocommerce_wpg_paypal_checkout_product_button_color").closest('tr').show();
                $("#woocommerce_wpg_paypal_checkout_product_button_shape").closest('tr').show();
                $("#woocommerce_wpg_paypal_checkout_product_button_label").closest('tr').show();
            } else {
                $("#woocommerce_wpg_paypal_checkout_product_button_layout").closest('tr').hide();
                $("#woocommerce_wpg_paypal_checkout_product_button_color").closest('tr').hide();
                $("#woocommerce_wpg_paypal_checkout_product_button_shape").closest('tr').hide();
                $("#woocommerce_wpg_paypal_checkout_product_button_label").closest('tr').hide();
            }
        }).change();
        $('#woocommerce_wpg_paypal_checkout_show_on_cart').change(function () {
            if ($(this).is(':checked')) {
                $("#woocommerce_wpg_paypal_checkout_cart_button_layout").closest('tr').show();
                $("#woocommerce_wpg_paypal_checkout_cart_button_color").closest('tr').show();
                $("#woocommerce_wpg_paypal_checkout_cart_button_shape").closest('tr').show();
                $("#woocommerce_wpg_paypal_checkout_cart_button_label").closest('tr').show();
            } else {
                $("#woocommerce_wpg_paypal_checkout_cart_button_layout").closest('tr').hide();
                $("#woocommerce_wpg_paypal_checkout_cart_button_color").closest('tr').hide();
                $("#woocommerce_wpg_paypal_checkout_cart_button_shape").closest('tr').hide();
                $("#woocommerce_wpg_paypal_checkout_cart_button_label").closest('tr').hide();
            }
        }).change();
        $('#woocommerce_wpg_paypal_checkout_show_on_mini_cart').change(function () {
            if ($(this).is(':checked')) {
                $("#woocommerce_wpg_paypal_checkout_mini_cart_button_layout").closest('tr').show();
                $("#woocommerce_wpg_paypal_checkout_mini_cart_button_color").closest('tr').show();
                $("#woocommerce_wpg_paypal_checkout_mini_cart_button_shape").closest('tr').show();
                $("#woocommerce_wpg_paypal_checkout_mini_cart_button_label").closest('tr').show();
            } else {
                $("#woocommerce_wpg_paypal_checkout_mini_cart_button_layout").closest('tr').hide();
                $("#woocommerce_wpg_paypal_checkout_mini_cart_button_color").closest('tr').hide();
                $("#woocommerce_wpg_paypal_checkout_mini_cart_button_shape").closest('tr').hide();
                $("#woocommerce_wpg_paypal_checkout_mini_cart_button_label").closest('tr').hide();
            }
        }).change();
        
        $('#woocommerce_wpg_paypal_checkout_sandbox').change(function () {
            if ($(this).is(':checked')) {
                $("#woocommerce_wpg_paypal_checkout_rest_client_id_sandbox").closest('tr').show();
                $("#woocommerce_wpg_paypal_checkout_rest_secret_id_sandbox").closest('tr').show();
                $("#woocommerce_wpg_paypal_checkout_rest_client_id_live").closest('tr').hide();
                $("#woocommerce_wpg_paypal_checkout_rest_secret_id_live").closest('tr').hide();
            } else {
                $("#woocommerce_wpg_paypal_checkout_rest_client_id_sandbox").closest('tr').hide();
                $("#woocommerce_wpg_paypal_checkout_rest_secret_id_sandbox").closest('tr').hide();
                $("#woocommerce_wpg_paypal_checkout_rest_client_id_live").closest('tr').show();
                $("#woocommerce_wpg_paypal_checkout_rest_secret_id_live").closest('tr').show();
            }
        }).change();
    });
})(jQuery);
