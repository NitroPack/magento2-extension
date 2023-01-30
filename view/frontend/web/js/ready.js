require([
    'jquery',
    'mage/url',
    'Magento_Customer/js/customer-data',
    'domReady!'
], function ($, urlBuilder, customerData) {
    'use strict';
    $.ajax({
        url: urlBuilder.build('NitroPack/Remote'),
        type: 'POST',
        dataType: 'json',
        data: {
            route: $('#nitropack_route').val(),
            currentUrl: $('#nitropack_currentUrl').val(),
            storeCode: $('#nitropack_storeCode').val(),
            siteId: $('#nitropack_siteId').val(),
            siteSecret: $('#nitropack_siteSecret').val()
        },
        success: function (data) {
            this.nitropack_remote_cache = customerData.set('nitropack_remote_cache', true);
            customerData.reload(['nitropack_remote_cache']);
            this._super();
        }.bind(this)
    });
});
