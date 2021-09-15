! function(e) {
    "use strict";
    e(window).load(function() {
        e("#woocommerce_wpg_paypal_express_sandbox").change(function() {
            var s = jQuery("#woocommerce_wpg_paypal_express_sandbox_api_username, #woocommerce_wpg_paypal_express_sandbox_api_password, #woocommerce_wpg_paypal_express_sandbox_api_signature").closest("tr"),
                o = jQuery("#woocommerce_wpg_paypal_express_api_username, #woocommerce_wpg_paypal_express_api_password, #woocommerce_wpg_paypal_express_api_signature").closest("tr");
            e(this).is(":checked") ? (s.show(), o.hide()) : (s.hide(), o.show())
        }).change(), e("#woocommerce_wpg_paypal_express_paymentaction").change(function() {
            "Sale" === this.value ? e("#woocommerce_wpg_paypal_express_instant_payments").closest("tr").show() : e("#woocommerce_wpg_paypal_express_instant_payments").closest("tr").hide()
        }).change(), e("#woocommerce_wpg_paypal_express_show_on_cart").change(function() {
            e(this).is(":checked") ? jQuery("#woocommerce_wpg_paypal_express_button_position").closest("tr").show() : jQuery("#woocommerce_wpg_paypal_express_button_position").closest("tr").hide()
        }).change(), e("#woocommerce_wpg_paypal_express_show_on_checkout_page").change(function() {
            e(this).is(":checked") ? jQuery("#woocommerce_wpg_paypal_express_checkout_skip_text").closest("tr").show() : jQuery("#woocommerce_wpg_paypal_express_checkout_skip_text").closest("tr").hide()
        }).change(), e(".wpg_display_optinal").click(function(s) {
            s.preventDefault(), e(this).parent("h3").next("p").next("table").toggleClass("hide"), e(this).parent("h3").next("p").toggleClass("hide"), e(this).text("Hide settings" === e(this).text() ? "Show settings" : "Hide settings")
        }).click(), e(".wpg_button_style_optinal").click(function(s) {
            s.preventDefault(), e(this).parent("h3").next("p").next("table").toggleClass("hide"), e(this).parent("h3").next("p").toggleClass("hide"), e(this).text("Hide settings" === e(this).text() ? "Show settings" : "Hide settings")
        }).click(), e(".wpg_advanced_optinal").click(function(s) {
            s.preventDefault(), e(this).parent("h3").next("table").toggleClass("hide"), e(this).text("Hide settings" === e(this).text() ? "Show settings" : "Hide settings")
        }).click(), e("#woocommerce_wpg_paypal_pro_testmode").change(function() {
            var s = jQuery("#woocommerce_wpg_paypal_pro_sandbox_api_username, #woocommerce_wpg_paypal_pro_sandbox_api_password, #woocommerce_wpg_paypal_pro_sandbox_api_signature").closest("tr"),
                o = jQuery("#woocommerce_wpg_paypal_pro_api_username, #woocommerce_wpg_paypal_pro_api_password, #woocommerce_wpg_paypal_pro_api_signature").closest("tr");
            e(this).is(":checked") ? (s.show(), o.hide()) : (s.hide(), o.show())
        }).change(), e("#woocommerce_wpg_braintree_sandbox").change(function() {
            var s = jQuery("#woocommerce_wpg_braintree_sandbox_public_key, #woocommerce_wpg_braintree_sandbox_private_key, #woocommerce_wpg_braintree_sandbox_merchant_id").closest("tr"),
                o = jQuery("#woocommerce_wpg_braintree_live_public_key, #woocommerce_wpg_braintree_live_private_key, #woocommerce_wpg_braintree_live_merchant_id").closest("tr");
            e(this).is(":checked") ? (s.show(), o.hide()) : (s.hide(), o.show())
        }).change(),  e("#woocommerce_wpg_paypal_advanced_testmode").change(function() {
            var s = jQuery("#woocommerce_wpg_paypal_advanced_sandbox_paypal_partner, #woocommerce_wpg_paypal_advanced_sandbox_paypal_vendor, #woocommerce_wpg_paypal_advanced_sandbox_paypal_user, #woocommerce_wpg_paypal_advanced_sandbox_paypal_password").closest("tr"),
                o = jQuery("#woocommerce_wpg_paypal_advanced_paypal_partner, #woocommerce_wpg_paypal_advanced_paypal_vendor, #woocommerce_wpg_paypal_advanced_paypal_user, #woocommerce_wpg_paypal_advanced_paypal_password").closest("tr");
            e(this).is(":checked") ? (s.show(), o.hide()) : (s.hide(), o.show())
        }).change(), e("#woocommerce_wpg_paypal_pro_payflow_testmode").change(function() {
            var s = jQuery("#woocommerce_wpg_paypal_pro_payflow_sandbox_paypal_partner, #woocommerce_wpg_paypal_pro_payflow_sandbox_paypal_vendor, #woocommerce_wpg_paypal_pro_payflow_sandbox_paypal_user, #woocommerce_wpg_paypal_pro_payflow_sandbox_paypal_password").closest("tr"),
                o = jQuery("#woocommerce_wpg_paypal_pro_payflow_paypal_partner, #woocommerce_wpg_paypal_pro_payflow_paypal_vendor, #woocommerce_wpg_paypal_pro_payflow_paypal_user, #woocommerce_wpg_paypal_pro_payflow_paypal_password").closest("tr");
            e(this).is(":checked") ? (s.show(), o.hide()) : (s.hide(), o.show())
        }).change(), e("#woocommerce_wpg_paypal_rest_sandbox").change(function() {
            var s = jQuery("#woocommerce_wpg_paypal_rest_rest_client_id_sandbox, #woocommerce_wpg_paypal_rest_rest_secret_id_sandbox").closest("tr"),
                o = jQuery("#woocommerce_wpg_paypal_rest_rest_client_id_live, #woocommerce_wpg_paypal_rest_rest_secret_id_live").closest("tr");
            e(this).is(":checked") ? (s.show(), o.hide()) : (s.hide(), o.show())
        }).change()
    })
    
}(jQuery);