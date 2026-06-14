define([
    'Magento_Ui/js/form/element/ui-select',
    'jquery'
], function (Select, $) {
    'use strict';

    return Select.extend({

        defaults: {
            hiddenInputId: ''
        },

        initObservable: function () {
            this._super();
            var self = this;
            this.value.subscribe(function(newValue) {
                $('#' + self.hiddenInputId).val(newValue.toString());
            });
            return this;
        }
    });
});
