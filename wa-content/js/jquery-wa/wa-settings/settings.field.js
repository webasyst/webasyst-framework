class WASettingsField {
    constructor(options) {
        let that = this;

        // DOM
        that.$wrapper = options.$wrapper;

        // VARS
        // DYNAMIC VARS
        // INIT
        that.initClass();
    }

    initClass() {
        let that = this;
        //
        let $sidebar = $('#js-sidebar-wrapper');
        $sidebar
            .find('ul li')
            .removeClass('selected')
            .end()
            .find('[data-id="field"]')
            .addClass('selected');

        that.initSortable();
        that.bindEvents();
    }

    initSortable() {
        let that = this,
            href = "?module=settingsFieldSortSave",
            item_index,
            xhr = false,
            $block = that.$wrapper.find('.wa-other-fields');
        console.log($block)
        $block.sortable({
            handle: '.sort',
            items: '.field',
            axis: 'y',
            tolerance: 'pointer',
            delay: 200,
            start: function (event, ui) {
                item_index = ui.item.index();
            },
            stop: function (event, ui) {
                if (item_index != ui.item.index()) {
                    let fields = getSortArray($block);
                    saveSort(href, {fields: fields});
                }
            }
        });

        function getSortArray($block) {
            return $block.find(".field").map(function () {
                return $.trim($(this).data("id")) || '';
            }).toArray();
        }

        function saveSort(href, data) {
            if (xhr) {
                xhr.abort();
                xhr = null;
            }
            return $.post(href, data, function () {
                xhr = null;
            });
        }
    }

    bindEvents() {
        let that = this,
            href = "?module=settingsFieldEditDialog",
            xhr = null;

        that.$wrapper.on('click', '.js-edit-field-link', function () {
            let $el = $(this);

            if (xhr) {
                xhr.abort();
                xhr = null;
            }

            xhr = $.post(href, {id: $el.data('id') || null}, function (html) {
                $.waDialog({
                    html: html
                });
            });
        });
    }
}
