(function () {
    "use strict";
    var yandexkassa = {
        guide: null,
        form: null,
        receipt: null,
        payment_type: null,
        customer_payment_type: null,

        init: function () {
            this.receipt = $(':input[name$="\[receipt\]"]');
            this.form = this.receipt.parents('form:first');
            this.payment_type = this.form.find(':input[name$="\[payment_type\]"]');
            this.guide = this.form.find(":input[readonly=readonly]:first").parents("div.field-group");
            this.customer_payment_type = this.form.find(':input[name*="\[customer_payment_type\]"]:first').parents('div.field:first');


            this.bind();


            var registered = true;

            this.form.find(':input[name$="\[shop_id\]"], :input[name$="\[shop_password\]"]').each(function () {
                /** @this HTMLInputElement */
                if (('' + this.value).length === 0) {
                    registered = false;
                }
            });

            if (registered) {
                this.form.find('.js-yandexkassa-registration-link').hide();
            }
        },

        /**
         *
         * @param event
         * @param HTMLInputElement element
         */
        changeReceipt: function (event, element) {
            var fast = !event.originalEvent;
            var fields = [
                this.form.find(':input[name$="\[tax_system_code\]"]:first').parents('div.field:first'),
                this.form.find(':input[name$="\[taxes\]"]:first').parents('div.field:first'),
                this.form.find(':input[name$="\[payment_subject_type_product\]"]:first').parents('div.field:first'),
                this.form.find(':input[name$="\[payment_subject_type_service\]"]:first').parents('div.field:first'),
                this.form.find(':input[name$="\[payment_subject_type_shipping\]"]:first').parents('div.field:first'),
                this.form.find(':input[name$="\[payment_method_type\]"]:first').parents('div.field:first')
            ];
            if (element.checked) {
                this.show(fields, fast);
            } else {
                this.hide(fields, fast);
            }
        },
        /**
         *
         * @param event
         * @param HTMLSelectElement|HTMLInputElement element
         */
        changePaymentMode: function (event, element) {

            var value = null;
            if (element instanceof HTMLSelectElement) {
                value = element.value;
            } else if (element instanceof HTMLInputElement) {
                if (element.checked) {
                    value = element.value;
                }
            }

            console.log('changePaymentMode', [element, value]);
            if (value !== null) {
                var fast = !event.originalEvent;
                var fields = [this.customer_payment_type];

                if (value === 'customer') {
                    this.show(fields, fast);
                } else {
                    this.hide(fields, fast);
                }
            }
        },
        show: function (elements, fast) {
            for (var i = 0; i < elements.length; i++) {
                if (elements[i]) {
                    if (fast) {
                        elements[i].show();
                    } else {
                        elements[i].slideDown();
                    }
                }
            }

        },
        hide: function (elements, fast) {
            for (var i = 0; i < elements.length; i++) {
                if (elements[i]) {
                    if (fast) {
                        elements[i].hide();
                    } else {
                        elements[i].slideUp();
                    }
                }
            }
        },
        bind: function () {
            var self = this;

            this.receipt.unbind('change').bind('change', function (event) {
                self.changeReceipt(event, this);
            }).trigger('change');

            this.payment_type.unbind('change').bind('change', function (event) {
                self.changePaymentMode(event, this);
            }).trigger('change');
        }
    };
    yandexkassa.init();
})();
