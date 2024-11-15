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

    constructor(options) {
        this.o = options;
        this.$wrapper = this.o.$wrapper;
        this.$sidebar = this.$wrapper.closest('.sidebar');
    }

    /** Called by the core SiteEditor to show the drawer after user selects a block. */
    show() {
        this.$sidebar.removeClass('hidden').show();
    }

    /** Hides a drawer when user de-selects a block */
    hide() {
        this.$sidebar.hide();
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
                is_new_block: is_new_block
            });
        }
        console.log('BlockSettingsDrawer.setForm()', form_config, block_data, media_prop);
    }

    /** Called by the core SiteEditor to update data without changing currently visible form.
     * May be called very often (e.g. on each character typed inside content-editable). */
    setData(block_data) {
        // !!! TODO
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
