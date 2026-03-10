define([
    'Magento_Checkout/js/view/payment/default',
    'mage/url'
], function (Component, urlBuilder) {
    'use strict';

    return Component.extend({
        defaults: {
            template: 'Xpayr_Gateway/payment/xpayr'
        },

        redirectAfterPlaceOrder: false,

        getCode: function () {
            return 'xpayr_gateway';
        },

        afterPlaceOrder: function () {
            window.location.replace(urlBuilder.build('xpayr/payment/redirect'));
        }
    });
});
