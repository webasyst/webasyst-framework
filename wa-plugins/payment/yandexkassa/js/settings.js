(function () {
    "use strict";
    var yandexkassa = {
        guide: null,
        form: null,
        receipt: null,

        init: function () {
            this.receipt = $(':input[name$="\[receipt\]"]');
            this.form = this.receipt.parents('form:first');

            this.bind();

            this.guide = this.form.find(":input[readonly=readonly]:first").parents("div.field-group");
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
            if (element.attr("checked")) {
                this.show(fields, fast);
            } else {
                this.hide(fields, fast);
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
                self.changeReceipt(event, $(this));
            }).trigger('change');
        }
    };
    yandexkassa.init();
})();
