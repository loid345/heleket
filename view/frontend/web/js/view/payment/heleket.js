define(
    [
        'uiComponent',
        'Magento_Checkout/js/model/payment/renderer-list'
    ],
    function (
        Component,
        rendererList
    ) {
        'use strict';
        rendererList.push(
            {
                type: 'heleket',
                component: 'MageBrains_Heleket/js/view/payment/method-renderer/heleket-method'
            }
        );
        return Component.extend({});
    }
);