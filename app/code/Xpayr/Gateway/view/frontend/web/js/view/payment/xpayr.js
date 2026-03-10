define([
    'uiComponent',
    'Magento_Checkout/js/model/payment/renderer-list'
], function (Component, rendererList) {
    'use strict';

    rendererList.push({
        type: 'xpayr_gateway',
        component: 'Xpayr_Gateway/js/view/payment/method-renderer/xpayr-method'
    });

    return Component.extend({});
});
