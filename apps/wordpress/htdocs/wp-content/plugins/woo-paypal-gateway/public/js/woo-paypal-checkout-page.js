jQuery(function ($) {
    if ($(".wpg_paypal_checkout_hidden").length > 0) {
        if ($('#place_order').length) {
            scrollElement = $('#place_order');
            $('html, body').animate({
                scrollTop: (scrollElement.offset().top - 120)
            }, 1000);
        }
    }
});