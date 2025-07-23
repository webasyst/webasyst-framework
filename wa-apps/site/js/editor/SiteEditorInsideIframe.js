"use strict";

class SiteEditorInsideIframe {

    // this.api object is a SiteEditor instance
    // it lives outside of iframe and does its job in parent window
    api;

    $wrapper;

    // this.delayed_save keeps track of timeouts used by saveBlockData()
    delayed_save;

    constructor(o) {
        this.$wrapper = o.$wrapper;
        this.api = window.top.registerEditorIframe(this);
        this.delayed_save = {};
        this.dropdown_open = true;
        this.watchClicks();
    }

    watchClicks(){
        const that = this;
        let last_alt = false;
        let last_shift = false;
        that.$wrapper.on('close_dropdown', function(event, data) {
                if (that.dropdown_open) {
                    that.$wrapper.find('.dropdown.is-opened').each(function(i, el){
                        var is_target = (el === data.target || $.contains(el, data.target));
                        if (!is_target) {
                            $(el).waDropdown('dropdown').toggleMenu(false);
                        }
                    })
                }
        })
        that.$wrapper.on('keydown', function(event) {
            if (event.key === 'Enter') {
                if ($(event.target).hasClass('site-block-list')) return
                document.execCommand('insertLineBreak')
                event.preventDefault()
                //event.stopPropagation()
            }
            if (event.keyCode == 27) {
                var esc = $.Event("keydown", { keyCode: event.keyCode });
                parent.$('body').trigger(esc);
            }
            /*if ((event.ctrlKey || event.metaKey) && (event.keyCode == 90 || event.keyCode == 89)) {
                    var ctrlZY = $.Event("keydown", { keyCode: event.keyCode, ctrlKey: event.ctrlKey, metaKey: event.metaKey});
                    parent.$('body').trigger(ctrlZY);
            }*/
            if (event.altKey && !last_alt) {
                    that.$wrapper.addClass('alt-down');
                    last_alt = true;
            }
            if (event.shiftKey && !last_shift) {
                    that.$wrapper.addClass('shift-down');
                    last_shift = true;
            }
        });
        that.$wrapper.on('keyup', function(event) {
            if (last_alt) {
                last_alt = false;
                that.$wrapper.removeClass('alt-down');
            }
            if (last_shift) {
                last_shift = false;
                that.$wrapper.removeClass('shift-down');
            }
        })

        $(window).on('blur', function(event) {
            if (last_alt) {
                last_alt = false;
                that.$wrapper.removeClass('alt-down');
            }
            if (last_shift) {
                last_shift = false;
                that.$wrapper.removeClass('shift-down');
            }
        });

        that.$wrapper.on('paste', function(e) {
            e.preventDefault();
            var text = e.originalEvent.clipboardData.getData("text/plain");
            document.execCommand("insertHTML", false, text);
          });

    }
    /**
     * Called from inside iframe to open dialog outside of iframe.
     * Dialog allows to select new block type to add at specified place.
     * Returns a promise that will be resolved with HTML of parent block
     * (re-rendered with new block added).
     */
    openAddBlockDialog(place_data) {
        const that = this;
        return $.Deferred((deferred) => {
            that.api.openAddBlockDialog(place_data, (data) => {
                deferred.resolve(data);
            }, () => {
                deferred.reject();
            });
        }).promise();
    }

    /**
     * Called from inside iframe to schedule block save operation.
     */
    saveBlockData(block_id, data, delay) {
        var that = this;
        if (typeof delay === 'undefined') {
            delay = 400;
        }
        that.api.saveBlockData(block_id, data, {
            notify_block_settings_form: true,
            mode: 'update',
            delay: delay,
        });
    }

    /**
     * Called from outside iframe (block settings form) to update DOM of the block
     * according to new settings. Also called from JS code of various blocks
     * from inside iframe to update data in block storage without touching the server.
     * This does not touch server.
     */
    updateBlockData(block_id, data, parent_id) {
        this.api.block_storage.setData(block_id, data, parent_id);
        const $blocks = this.$wrapper.find('[data-block-id="'+block_id+'"]');
        $blocks.trigger('block_data_updated', [block_id, data]);
    }

    /**
     * Called from outside iframe (block settings form) to update DOM of the block
     * according to new settings. This does not touch server.
     */
    updateBlockFile(block_id, key, file) {
        this.api.block_storage.setFile(block_id, key, file);
        const $blocks = this.$wrapper.find('[data-block-id="'+block_id+'"]');
        $blocks.trigger('block_file_updated', [block_id, key, file]);
    }

    /**
     * Called from inside iframe to set/update configs for block settings form.
     * This is used when a new block is added to the page
     */
    updateBlockSettingsFormConfig(block_id, config) {
        this.api.block_storage.setFormConfig(block_id, config);
    }

    /**
     * Called from inside iframe (by Vertical Sequence code) when user selects a block.
     * Right drawer outside of iframe displays settings of currently selected block.
     */
    setSelectedBlock(block_id, $wrapper, $currentTarget, is_new_block = false) {
        this.$wrapper.trigger('close_dropdown', {target: $currentTarget});
        if (this.api.selected_block_id != block_id) {
            try {
                this.api.setSelectedBlock(block_id, is_new_block);
            } catch (e) {
                console.log('unable to set selected block', e);
            }
            $('#wa-app').find('.selected-block').removeClass('selected-block');
            if ($wrapper.is('.seq-child')) {
                $wrapper.addClass('selected-block');
            }
            return true;
        }
        return false;
    }

    // @see copy SiteEditorInsideIframe.js
    static sanitizeHTML(str) {
        if (!str) {
            return str;
        }

        // clean up JS
        const pattern = /<script[^>]*>.*?<\/script>/igs;
        const html = str.replace(pattern, '');

        // keep http(s)
        const sanitizeIframeSrc = (str) => {
            return str.replace(/<iframe[^>]*src\s*=\s*"(.*?)"[^>]*>/g, (start, src) => {
                if (src.match(/^https?:/)) {
                    return start + src;
                }
                return start.replace(src, '');
            });
        }

        return sanitizeIframeSrc(html);
    }
}
