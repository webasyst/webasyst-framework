"use strict";
/**
 * This object is used as an interface to communicate with the VueJS code that controls
 * the right drawer in block editor. Drawer contains a form that depends on currently
 * selected block in the editor. The form changes on the fly.
 *
 */
class BlockSettingsDrawer
{
    current_block_id;
    o;
    drawer;
    $wrapper;
    form_constructor;
    position_number = 0;
    position_number_hor = 0;
    position_number_width = 0;

    constructor(options) {
        this.o = options;
        this.$wrapper = this.o.$wrapper;
        this.$sidebar = this.$wrapper.closest('.sidebar');
        this.html_templates = this.o.html_templates;
    }

    /** Called by the core SiteEditor to show the drawer after user selects a block. */
    show() {
        this.$sidebar.removeClass('hidden').show();
    }

    /** Called by the core SiteEditor to show the drawer after user selects a block. */
    /*showSidebarButton() {
        const that = this;
        const sidebarButton = this.$sidebar.siblings('.sidebar-button');
        if (!sidebarButton.hasClass('hidden') || !this.$sidebar.is(":hidden")) return
        sidebarButton.removeClass('hidden');
        sidebarButton.on('click', function(){
            that.show();
            sidebarButton.off('click');
            sidebarButton.addClass('hidden');
        });
    }*/

    /** Hides a drawer when user de-selects a block */
    hide() {
        this.$sidebar.hide();
        //const sidebarButton = this.$sidebar.siblings('.sidebar-button');
        //sidebarButton.addClass('hidden');
    }

    /** Change view of drawer  */
    updatePosition() {
        this.position_number
        switch (this.position_number) {
            case 0:
                this.$sidebar.addClass('secondSmallPosition');
                break;
            /* не удаляю, чтобы можно было сделать больше 2х режимов отображения сайдбара
            case 1:
                this.$sidebar.addClass('secondSmallPosition').removeClass('firstSmallPosition');
                break;
            case 2:
                this.$sidebar.addClass('thirdSmallPosition').removeClass('secondSmallPosition');
                break;*/
            default:
                this.$sidebar.removeClass('secondSmallPosition')
                this.position_number = -1;
                break;
        }
        this.position_number++
    }

        /** Change position of drawer  */
        updatePositionHorizontal() {
            this.position_number_hor
            switch (this.position_number_hor) {
                case 0:
                    this.$sidebar.addClass('leftPosition');
                    break;
                default:
                    this.$sidebar.removeClass('leftPosition')
                    this.position_number_hor = -1;
                    break;
            }
            this.position_number_hor++
        }
        /** Change width of drawer  */
        updateWidth() {
            this.position_number_width
            switch (this.position_number_width) {
                case 0:
                    this.$sidebar.addClass('halfWidth');
                    break;
                case 1:
                    this.$sidebar.removeClass('halfWidth').addClass('fullWidth');
                    break;
                default:
                    this.$sidebar.removeClass('fullWidth')
                    this.position_number_width = -1;
                    break;
            }
            this.position_number_width++
            // See transition in #editor-page .sidebar.block-settings-sidebar:hover
            setTimeout(()  => {
                wa_editor.resize();
            }, 210);
        }

    /** Called by the core SiteEditor to (re-)initialize a form in the drawer
     * when user selects a different block. */
    setForm(block_id, form_config, block_data, media_prop, is_new_block = false, force_update = false) {
        this.current_block_id = block_id;
        // !!! TODO remove debugging code
        //this.$wrapper.find('.js-block-id').html(block_id);
        if (this.form_constructor) {
            this.form_constructor.resetState(+block_id, form_config, block_data, media_prop, is_new_block, force_update);
        }
        else {
            this.form_constructor = new FormConstructor({
                $wrapper: this.$wrapper,
                block_id: block_id,
                block_data: block_data,
                form_config: form_config,
                media_prop: media_prop,
                is_new_block: is_new_block,
                html_templates: this.html_templates
            });
        }
        console.log('BlockSettingsDrawer.setForm()', form_config, block_data, media_prop);
        this.updatePaddingRight(media_prop)
    }

    updatePaddingRight(media_prop) {
        $iframe = $('#js-main-editor-body');
        //console.log(media_prop, this.$sidebar.hasClass('overflow-mode'))
        if (!media_prop && this.$sidebar.hasClass('overflow-mode')) {
            let padding_right = $iframe[0].contentWindow.innerWidth - $iframe[0].contentWindow.document.body.clientWidth + 10 + 'px';
            this.$sidebar.css('right', padding_right);
        } else this.$sidebar.css('right', '');
    }

    /** Called by the core SiteEditor to update data without changing currently visible form.
     * May be called very often (e.g. on each character typed inside content-editable). */
    setData(block_data) {
        // nothing to do
        //console.log('BlockSettingsDrawer.setData()', block_data);
    }

    /** Called by VueJS code to upload a file into a block.
     * Returns a promise that will contain information about uploaded file such as URL.
     * Also propagates changes into WYSIWYG iframe once file is uploaded. */
    uploadFile(file, key) {
        if (!this.current_block_id) {
            console.log('Warning: attempt to save block data without any block selected');
            return;
        }
        return $.wa.editor.uploadFile(this.current_block_id, key, file);
    }

    /** Called by VueJS code when user changes something in the form.
     * Propagates changes into WYSIWYG and saves to the server. */
    saveBlockData(block_data, update = true) {
        if (!this.current_block_id) {
            console.log('Warning: attempt to save block data without any block selected');
            return;
        }
        $.wa.editor.saveBlockData(this.current_block_id, block_data, {
            notify_editor_inside_iframe: update,
            mode: 'update',
            delay: 400,
        });
    }
}
