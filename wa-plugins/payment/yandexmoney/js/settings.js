(function () {
    "use strict";
    var yandexmarket = {
        fields: null,
        guide: null,
        form: null,
        payment_mode: null,
        receipt: null,

        init: function () {
            this.fields = this.form.find(".js-yandexmoney-integration-type");
            this.guide = this.form.find(":input[readonly=readonly]:first").parents("div.field-group");
            var registered = true;

            this.form.find(':input[name$="\[ShopID\]"], :input[name$="\[scid\]"], :input[name$="\[shopPassword\]"]').each(function () {
                /** @this HTMLInputElement */
                if (('' + this.value).length === 0) {
                    registered = false;
                }
            });

            if (registered) {
                this.form.find('.js-yandexmoney-registration-link').hide();
            }
        },
        changeIntegrationType: function (event, element) {
            if (element.attr("checked")) {
                this.init(element);
                var fast = !event.originalEvent;
                var selected = element.val();
                var queue = {
                    'show': [],
                    'hide': []
                };
                switch (selected) {
                    case 'mpos':
                        this.payment_mode.filter('[value="MP"]').attr('checked', 'checked');

                        queue.show.push(this.guide);
                        this.fields.filter('.js-yandexmoney-kassa:not([name*="\[paymentType\]"])').each(function () {
                            queue.show.push($(this).parents("div.field:first"));
                        });

                        this.payment_mode.filter(':not([value="MP"])').each(function () {
                            queue.hide.push($(this).parents("div.value:first"));
                        });

                        this.fields.filter('.js-yandexmoney-personal, .js-yandexmoney-kassa[name*="\[paymentType\]"]').each(function () {
                            queue.hide.push($(this).parents("div.field:first"));
                        });


                        break;
                    case 'kassa':
                        queue.show.push(this.guide);
                        this.payment_mode.filter(':not([value="MP"])').each(function () {
                            queue.show.push($(this).parents("div.value:first"));
                        });

                        this.fields.filter(".js-yandexmoney-kassa").each(function () {
                            queue.show.push($(this).parents("div.field:first"));
                        });

                        //hide
                        this.fields.filter(".js-yandexmoney-personal").each(function () {
                            queue.hide.push($(this).parents("div.field:first"));
                        });

                        break;
                    case 'personal':
                        //show
                        this.fields.filter(".js-yandexmoney-personal").each(function () {
                            queue.show.push($(this).parents("div.field:first"));
                        });
                        //hide
                        queue.hide.push(this.guide);
                        this.fields.filter(".js-yandexmoney-kassa").each(function () {
                            queue.hide.push($(this).parents("div.field:first"));
                        });
                        break;
                }

                this.show(queue.show, fast);
                this.hide(queue.hide, fast);
                this.payment_mode.trigger('change');
            }
        },
        changePaymentMode: function (event, element) {
            if (element.attr("checked")) {
                var fast = !event.originalEvent;
                var field = this.form.find(':input[name*="\[paymentType\]"]:first').parents('div.field:first');
                if (element.val() === 'customer') {
                    this.show([field], fast);
                } else {
                    this.hide([field], fast);
                }
            }
        },
        changeReceipt: function (event, element) {
            var fast = !event.originalEvent;
            var fields = [
                this.form.find(':input[name$="\[taxSystem\]"]:first').parents('div.field:first'),
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
            var input_type = $(':input[name$="\[integration_type\]"]');
            this.form = input_type.parents('form:first');
            this.payment_mode = this.form.find(':input[name$="\[payment_mode\]"]');
            this.receipt = this.form.find(':input[name$="\[receipt\]"]');

            var self = this;
            input_type.unbind('change').bind('change', function (event) {
                self.changeIntegrationType(event, $(this));
            }).trigger('change');

            this.payment_mode.unbind('change').bind('change', function (event) {
                self.changePaymentMode(event, $(this));
            }).trigger('change');

            this.receipt.unbind('change').bind('change', function (event) {
                self.changeReceipt(event, $(this));
            }).trigger('change');
        }
    };
    yandexmarket.bind();
})();
