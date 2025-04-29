"use strict";
/**
 * Part of SiteEditor machinery.
 * Contains last known state for all blocks currently on page being opened in editor.
 * Used for undo/redo operations.
 *
 * Also stores config for BlockSettingsDrawer for all blocks.
 * Config is used to draw proper form in the right drawer
 * depending on currently selected block.
 */
class BlockStorage
{
    data;
    form_config;

    constructor(data, form_config) {
        this.data = data;
        this.form_config = form_config || {};
    }

    getFormConfig(block_id) {
        return this.form_config[block_id] || {};
    }

    setFormConfig(block_id, config) {
        this.form_config[block_id] = config || {};
    }

    updateData(block_id, data) {
        var old_data = this.getData(block_id);
        if (this.data[block_id]) {
            this.data[block_id].data = {...(this.data[block_id].data || {}), ...data};
        }
        return old_data;
    }

    setData(block_id, data) {
        var old_data = this.getData(block_id);
        if (!this.data[block_id]) {
            this.data[block_id] = {
                id: block_id,
                files: {},
                data: null
            };
        }
        this.data[block_id].data = {...data};
        return old_data;
    }

    getData(block_id) {
        return this.data[block_id]?.data || null;
    }

    getParents(block_id) {
        return this.data[block_id]?.parents || [];
    }

    setFile(block_id, key, file) {
        if (!this.data[block_id]) {
            this.data[block_id] = {
                id: block_id,
                files: {},
                data: null
            };
        }
        if (!this.data[block_id].files) {
            this.data[block_id].files = {};
        }
        const old_file = this.getFile(block_id, key);
        this.data[block_id].files[key] = file;
        return old_file;
    }

    getFile(block_id, key) {
        return (this.data[block_id]?.files || {})[key] || null;
    }
}
