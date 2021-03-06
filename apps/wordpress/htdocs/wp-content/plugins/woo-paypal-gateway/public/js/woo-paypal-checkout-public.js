;
(function ($, window, document) {
    'use strict';
    var wpg_render = function () {
        paypal.Buttons({
            style: {
                layout: wpg_param.layout,
                color: wpg_param.color,
                shape: wpg_param.shape,
                label: wpg_param.label
            },
            createOrder: function (data, actions) {
                if (wpg_param.page === 'product') {
                    $('.cart').block({message: null, overlayCSS: {background: '#fff', opacity: 0.6}});
                    var get_attributes = function () {
                        var select = $('.variations_form').find('.variations select'),
                                data = {},
                                count = 0,
                                chosen = 0;
                        select.each(function () {
                            var attribute_name = $(this).data('attribute_name') || $(this).attr('name');
                            var value = $(this).val() || '';
                            if (value.length > 0) {
                                chosen++;
                            }
                            count++;
                            data[ attribute_name ] = value;
                        });
                        return {
                            'count': count,
                            'chosenCount': chosen,
                            'data': data
                        };
                    };
                    var data_param = {
                        'qty': $('.quantity .qty').val(),
                        'attributes': $('.variations_form').length ? JSON.stringify(get_attributes().data) : [],
                        'wc-paypal_express-new-payment-method': $("#wc-paypal_express-new-payment-method").is(':checked'),
                        'product_id': $('[name=add-to-cart]').val(),
                        'variation_id': $("input[name=variation_id]").val()
                    };
                    return fetch(wpg_param.add_to_cart_ajaxurl, {
                        method: 'post',
                        body: JSON.stringify(data_param),
                        headers: {'Content-Type': 'application/json'}
                    }).then(function (res) {
                        return res.json();
                    }).then(function (data) {
                        return data.orderID;
                    });
                } else if (wpg_param.page === 'cart') {
                    $('.woocommerce').block({message: null, overlayCSS: {background: '#fff', opacity: 0.6}});
                    return fetch(wpg_param.set_checkout)
                            .then(function (res) {
                                return res.json();
                            }).then(function (data) {
                        return data.orderID;
                    });
                } else if (wpg_param.page === 'checkout') {
                    
                    var data = $('#wpg_paypal_button_' + wpg_param.page).closest('form')
                            .add($('<input type="hidden" name="from_checkout" /> ')
                                    .attr('value', 'yes')
                                    )
                            .serialize();
                    return fetch(wpg_param.set_checkout, {
                        method: 'POST',
                        body: data,
                        headers: {'Content-Type': 'application/x-www-form-urlencoded'}
                    }).then(function (res) {
                        return res.json();
                    }).then(function (data) {
                        return data.orderID;
                    });
                }
            },
            onApprove: function (data, actions) {
                if (wpg_param.page === 'checkout') {
                    actions.order.capture().then(function (details) {
                        actions.redirect(wpg_param.display_order_page + '&orderID=' + data.orderID + '&payment_id=' + details.id);
                    });
                } else {
                    actions.redirect(wpg_param.get_checkout_details + '&orderID=' + data.orderID);
                }
            },
            onError: function (err) {
                window.location.reload();
            },
            onCancel: function (data) {
                window.location.replace(wpg_param.cancel_url);
            }
        }).render('#wpg_paypal_button_' + wpg_param.page);
    };
    if (wpg_param.page) {
        if (wpg_param.page !== "checkout") {
            wpg_render();
        }
        $(document.body).on('updated_cart_totals updated_checkout', wpg_render.bind(this, false));
    }
    if (wpg_param.page === "checkout") {
        $('form.checkout').on('click', 'input[name="payment_method"]', function () {
            var iswpg = $(this).is('#payment_method_wpg_paypal_checkout');
            $('#place_order').toggle(!iswpg);
            $('#wpg_paypal_button_checkout').toggle(iswpg);
        });
    }
    $('.variations_form').on('hide_variation', function () {
        $('#wpg_paypal_button_product').hide();
    });
    $('.variations_form').on('show_variation', function () {
        $('#wpg_paypal_button_product').show();
    });
})(jQuery, window, document);