
( function($) {

$.form_storage = $.extend($.form_storage || {}, {
    app_url: false,
    backend_url: false,
    is_debug: false,
    is_form_loaded: false,
})
})(jQuery);

var FormConstructor = ( function($) {

        function FormConstructor(options) {
            var that = this;

            // CONST
            //that.block_id = options["block_id"];
            that.$wrapper = options["$wrapper"];
            that.$sidebar = that.$wrapper.parent();
            that.templates = $.form_storage.templates;
            that.storage_data = $.form_storage.data;
            that.media_prop =  options["media_prop"];
            that.html_templates = options["html_templates"];
            $.form_storage.html_templates = options["html_templates"];
            that.states = {
                block_id: options["block_id"],
                form_config: options["form_config"],
                block_data: options["block_data"],
                media_prop: that.media_prop,
            };

            that.is_new_block = options["is_new_block"];

            that.selected_element = that.states.form_config.elements?.main || null,

            // DOM
           // that.$wrapper = options["$wrapper"];
            $iframe = $('#js-main-editor-body');
            //that.$iframe_window = $iframe[0].contentWindow? $iframe[0].contentWindow : $iframe[0].contentDocument.defaultView;
            that.$iframe_wrapper = $iframe.contents();
            that.$target_wrapper = that.$iframe_wrapper.find('.seq-child > [data-block-id=' + options["block_id"] + ']');

            that.components = new FormComponents(that.$iframe_wrapper);
            that.vue_components = that.components.base_components;
            that.vue_manual_components = that.components.manual_components;
            that.vue_custom_components = that.components.custom_components;
            // CONST

            //append :root styles from blockpage.wrapper.html
            appendIframeWrapperStyles(that.$iframe_wrapper);
            // INIT
            that.vue_model = that.initVue();
        }

        FormConstructor.prototype.init = function(vue_model) {
            var that = this;
        };

        FormConstructor.prototype.initialState = function(state) {
            var that = this;
            let {block_id, form_config, block_data, media_prop, is_new_block} = state;
            let comp_data = [];
            for (let key in form_config.sections) {
                comp_data.push(Object.assign(form_config.sections[key], {'block_type': form_config.type}));
            }
            that.media_prop = media_prop;
            that.$target_wrapper = that.$iframe_wrapper.find('.seq-child [data-block-id=' + block_id + ']');
            that.is_new_block = is_new_block;
            //let is_element = form_config.tags && form_config.tags === 'element';
            that.states = {
                block_id: block_id,
                form_config: form_config,
                settings_array: comp_data,
                block_data: block_data,
                media_prop: media_prop,
                elements: form_config.elements || null,
                semi_headers: form_config.semi_headers || null,
                header: form_config.type_name,
                selected_element: form_config.elements?.main || null, //that.selected_element,
            }
            that.states.parents = $.wa.editor.block_storage.getParents(block_id).slice(0).reverse();
            //that.states.parents = {};

            return that.states
        };

        FormConstructor.prototype.resetState = function(block_id, form_config, block_data, media_prop, is_new_block, force_update) {
            var that = this;
            that.vue_model.reset(that.initialState({block_id, form_config, block_data, media_prop, is_new_block}), force_update);
        };

        FormConstructor.prototype.initVue = function() {
            var that = this;

            const i18n = VueI18n.createI18n({
                locale: $.site.lang,
                legacy: false,
                //silentTranslationWarn: false,
                //globalInjection: true,
                fallbackWarn: false,
                missingWarn: false,
                warnHtmlMessage: false,
                fallbackLocale: 'en',
                messages: $.form_storage.translate
            });

            if (typeof $.vue_app === "object" && typeof $.vue_app.unmount === "function") {
                $.vue_app.unmount();
            }

            $.vue_app = Vue.createApp({

                data() {
                    const { ref, reactive } = Vue;
                    that.states = that.initialState(that.states)
                    let states = ref(that.states);
                    this.force_key = 1;
                    return states.value;
                  },
                components: {
                    'FormHeader': that.vue_components['form-header'],
                    "FontHeaderGroup": {
                        props: {
                            group_config: { type: Object },
                            block_data: { type: Object, default: {} },
                            block_id: { type: Number},
                        },
                        data() {
                            const form_type = that.storage_data[this.group_config.type].type;
                            const arr_options = that.storage_data[this.group_config.type].values;
                            const active_option = this.block_data?.block_props?.[form_type];
                            const semi_header = this.group_config.name;
                            let editable = that.$target_wrapper.find('.style-wrapper');
                            editable = editable.length ? editable : that.$target_wrapper;
                            return { arr_options, semi_header, active_option, form_type, with_text: true}
                          },
                        template: `
                        <div class="s-editor-option-wrapper">
                            <div class="s-editor-option-body custom-mt-4 custom-pb-4">
                                <font-header-toggle :options="arr_options" :activeOption="active_option" :form_type="form_type" :with_text="with_text" :block_data="block_data" :block_id="block_id"></font-header-toggle>
                            </div>
                        </div>
                        `,
                        delimiters: ['{ { ', ' } }'],
                        components: {
                            'FontHeaderToggle': that.vue_components['component-toggle'],
                          },
                    },
                    "FontGroup": {
                        props: {
                            group_config: { type: Object },
                            block_data: { type: Object, default: {} },
                            block_id: { type: Number},
                        },
                        data() {
                            const self = this;
                            const form_type = that.storage_data[this.group_config.type].type;
                            let arr_options = that.storage_data[this.group_config.type];
                            const selected_element = that.states.selected_element;
                            const active_icon = that.storage_data[this.group_config.type].icon;
                            const data_type_array = ['inline_props', 'block_props']
                            let data_type = null;
                            let active_option = null;
                            /*for (let key in this.options.values) {
                                arr_options.push(this.options.values[key]);
                            }*/
                            //let active_option = this.block_data?.block_props?.[form_type];
                            $.each(data_type_array, function(i, d) {
                                let temp_options = self.block_data?.[d]?.[form_type] || self.block_data?.[d]?.[selected_element]?.[form_type] || null;
                                if (temp_options) {
                                    active_option = temp_options;
                                    if (active_option) {data_type = d}
                                }
                            })
                            //console.log("FontGroup", active_option, self.block_data)
                            //if (selected_element) active_option = this.block_data?.block_props?.[selected_element]?.[form_type];
                            const header_name = this.group_config.name;

                            return { active_icon, data_type, selected_element, arr_options, header_name, active_option, form_type }
                          },
                        template: that.templates["component_font_group"],
                        delimiters: ['{ { ', ' } }'],
                        components: {
                            //'FontDropdown': that.vue_components['component-dropdown'],
                            'FontSizeDropdown': that.vue_custom_components['component-width-dropdown'],
                          },

                    },
                    /*"ButtonStyleGroupOld": {
                        props: {
                            group_config: { type: Object },
                            block_data: { type: Object, default: {} },
                            block_id: { type: Number},
                        },
                        data() {
                            const form_type = that.storage_data[this.group_config.type].type;
                            let arr_options = that.storage_data[this.group_config.type].values;

                            let active_option_class = this.block_data?.block_props?.[form_type];
                            const form_type_custom = 'custom';
                            var filter_search = arr_options.filter( function(option) {
                                return active_option_class == option.value_class;
                            });
                            active_option = (filter_search.length ? filter_search[0].value : 'main-set');

                            const header_name = this.group_config.name;

                            return { active_option_class, arr_options, header_name, active_option, form_type, form_type_custom }
                          },
                        template: that.templates["component_button_style_group"],
                        delimiters: ['{ { ', ' } }'],
                        components: {
                            'ButtonStyleDropdown': that.vue_components['component-dropdown'],
                          },
                        methods: {
                            change: function(option) { //put classes to remove in temp_active_option
                                let self = this;

                                if (self.active_option !== option.value) {
                                    var filter_search = self.arr_options.filter( function(opt) {
                                        return option.value == opt.value;
                                    });
                                    let new_option_class = (filter_search.length ? filter_search[0].value_class : self.active_option_class);

                                    $.wa.editor._block_settings_drawer_promise.then(function(bs) {
                                        self.block_data.block_props[self.form_type] = new_option_class;
                                        bs.saveBlockData(self.block_data);
                                    });
                                    self.active_option = option.value; //update base
                                    self.active_option_class = new_option_class
                                    console.log('saveBlockData', self.block_data)
                                }
                            }
                        },
                    },*/
                    "ButtonStyleGroup": {
                        props: {
                            group_config: { type: Object },
                            block_data: { type: Object, default: {} },
                            block_id: { type: Number},
                        },
                        data() {
                            const form_type = that.storage_data[this.group_config.type].type;
                            let arr_options = that.storage_data[this.group_config.type];

                            let active_option = this.block_data?.block_props?.[form_type];

                            const header_name = this.group_config.name;

                            return { arr_options, header_name, active_option, form_type }
                          },
                        template: that.templates["component_button_style_group"],
                        delimiters: ['{ { ', ' } }'],
                        components: {
                            'ButtonStyleDropdown': that.vue_custom_components['component-button-color-dropdown'],
                        },
                        methods: {
                            change: function(option) { //put classes to remove in temp_active_option
                                let self = this;

                                if (self.active_option !== option.value) {
                                    var filter_search = self.arr_options.filter( function(opt) {
                                        return option.value == opt.value;
                                    });
                                    let new_option_class = (filter_search.length ? filter_search[0].value_class : self.active_option_class);

                                    $.wa.editor._block_settings_drawer_promise.then(function(bs) {
                                        self.block_data.block_props[self.form_type] = new_option_class;
                                        bs.saveBlockData(self.block_data);
                                    });
                                    self.active_option = option.value; //update base
                                    //self.active_option_class = new_option_class
                                    console.log('saveBlockData', self.block_data)
                                }
                            },
                            changePalette: function(option) { //put classes to remove in temp_active_option
                                let self = this;
                                if (self.active_option !== option) {
                                    $.wa.editor._block_settings_drawer_promise.then(function(bs) {
                                        self.block_data.block_props[self.form_type] = option;
                                        bs.saveBlockData(self.block_data);
                                    });
                                    self.active_option = option; //update base
                                    //self.active_option_class = new_option_class
                                    console.log('saveBlockData', self.block_data)
                                }
                            }
                        },
                    },
                    "ButtonSizeGroup": {
                        props: {
                            group_config: { type: Object },
                            block_data: { type: Object, default: {} },
                            block_id: { type: Number},
                        },
                        data() {
                            const form_type = that.storage_data[this.group_config.type].type;
                            let arr_options = that.storage_data[this.group_config.type].values;
                            let active_option_class = this.block_data?.block_props?.[form_type];
                            var filter_search = arr_options.filter( function(option) {
                                return active_option_class == option.value;
                            });

                            active_option = (filter_search.length ? filter_search[0].value : arr_options[0].value);

                            const header_name = this.group_config.name;

                            return { arr_options, header_name, active_option, form_type, with_text: true}
                          },
                        template: that.templates["component_button_size_group"],
                        delimiters: ['{ { ', ' } }'],
                        components: {
                            //'ButtonStyleDropdown': that.vue_components['component-dropdown'],
                            'ButtonSizeToggle': that.vue_components['component-toggle'],
                          },
                    },
                    "ButtonToggleGroup": {
                        props: {
                            group_config: { type: Object },
                            block_data: { type: Object, default: {} },
                            block_id: { type: Number},
                        },
                        data() {
                            const form_type = 'nobutton';
                            //let active_class = that.storage_data[this.group_config.type].values;
                            let active_class = this.block_data?.block_props?.[form_type] ? true : false;

                            const header_name = this.group_config.name;

                            return { active_class, header_name, with_text: true, form_type}
                          },
                        template: that.templates["component_button_toggle_group"],
                        delimiters: ['{ { ', ' } }'],
                        components: {
                            //'ButtonStyleDropdown': that.vue_components['component-dropdown'],
                            'SwitchToggle': that.vue_components['component-switch'],
                          },
                        methods: {
                            changeSwitch(option) {
                                let self = this;
                                //let $editable = that.$target_wrapper;
                                if (option) {
                                    self.block_data.block_props[self.form_type] = self.form_type;
                                }
                                else if (self.block_data.block_props) {
                                    delete self.block_data.block_props[self.form_type];
                                }
                                self.active_class = option;
                                $.wa.editor._block_settings_drawer_promise.then(function(bs) {
                                    bs.saveBlockData(self.block_data);
                                });
                                console.log('saveBlockData', self.block_data)
                            },
                        }

                    },
                    /*"OldColumnsGroup": {
                        props: {
                            group_config: { type: Object },
                            block_data: { type: Object, default: {} },
                            block_id: { type: Number},
                        },
                        data() {
                            //const form_type = that.storage_data[this.group_config.type].type;
                            const form_type = 'customColumn';

                            let columns_data = [];
                            for (let key in this.block_data.columns) {
                                columns_data.push(this.block_data?.columns[key]);
                            }
                            let arr_options = that.storage_data[this.group_config.type].values;
                            const icons = that.storage_data[this.group_config.type].icons;
                            const active_option = this.block_data?.block_props?.[form_type];
                            const header_name = false;
                            const media_prop = that.media_prop;
                            const icon = that.media_prop ? icons[media_prop] : 'fa-desktop';
                            //let temp_columns_data = columns_data;
                            return { columns_data, header_name, arr_options, active_option, form_type, media_prop, icon}
                          },
                        template: that.templates["component_columns_group"],
                        delimiters: ['{ { ', ' } }'],
                        components: {
                            'ColumnWidthDropdown': that.vue_components['component-dropdown'],
                            'CustomButton': that.vue_components['custom-button'],
                          },
                        methods: {
                            change: function(option, column_num, activeOption) { //put classes to remove in temp_active_option
                                let self = this;
                                let temp_active_options = self.block_data.columns['column-' + column_num].split(' ');
                                const classPosition = temp_active_options.indexOf(activeOption.value);
                                if ( classPosition >= 0 ) temp_active_options.splice(classPosition, 1);
                                temp_active_options.push(option.value);
                                $.wa.editor._block_settings_drawer_promise.then(function(bs) {
                                    self.block_data.columns['column-' + column_num] = temp_active_options.join(' ');
                                    bs.saveBlockData(self.block_data);
                                });
                                console.log('saveBlockData', self.block_data);
                            },
                            changeComponent: function(key) {
                                let self = this;
                                let $editable = that.$target_wrapper.find('.style-wrapper');
                                $editable = $editable.length ? $editable : that.$target_wrapper;
                                $editable = $editable.find('[data-count=' + (key + 1) + '] .site-block-column');
                                let column_block_id = $editable.data('block-id');
                                updateSelectedBlock(column_block_id)

                            }
                        }
                    },*/
                    "ColumnsGroup": {
                        props: {
                            group_config: { type: Object },
                            block_data: { type: Object, default: {} },
                            block_id: { type: Number},
                        },
                        data() {
                            //const form_type = that.storage_data[this.group_config.type].type;
                            const form_type = 'customColumn';
                            self.columns_nodes = that.$target_wrapper.find('.js-seq-wrapper').eq(0).find('> .seq-child');
                            let columns_data = [];
                            let indestructible_cols = this.block_data?.indestructible || false;
                            $.each(self.columns_nodes, function(i, node) {
                                const column_id = $(node).attr('data-block-id');
                                const column_data = Object.assign({}, $.wa.editor.block_storage.getData(column_id));
                                columns_data.push(column_data.column);
                                if (column_data?.indestructible) indestructible_cols = true;
                            });
                            let arr_options = that.storage_data['ColumnsGroup'].values;
                            const icons = that.storage_data['ColumnsGroup'].icons;
                            const active_option = this.block_data?.block_props?.[form_type];

                            const header_name = this.group_config.name;
                            const media_prop = that.media_prop;
                            const icon = that.media_prop ? icons[media_prop] : 'fa-desktop';
                            return { indestructible_cols, columns_data, arr_options, header_name, active_option, form_type, media_prop, icon}
                        },
                        template: that.templates["component_columns_group"],
                        delimiters: ['{ { ', ' } }'],
                        components: {
                            'ColumnWidthDropdown': that.vue_components['component-dropdown'],
                            'CustomButton': that.vue_components['custom-button'],
                          },
                        methods: {
                            change: function(option, column_num, activeOption) { //put classes to remove in temp_active_option
                                let self = this;
                                let temp_active_options = self.columns_data[column_num - 1].split(' ');
                                const classPosition = temp_active_options.indexOf(activeOption.value);
                                if ( classPosition >= 0 ) temp_active_options.splice(classPosition, 1);
                                temp_active_options.push(option.value);

                                let column_id = that.$target_wrapper.find('.js-seq-wrapper').eq(0).find('> .seq-child').eq(column_num - 1).data('block-id');
                                const column_data = Object.assign({}, $.wa.editor.block_storage.getData(column_id));
                                column_data.column = temp_active_options.join(' ');
                                self.columns_data[column_num - 1] = temp_active_options.join(' ');
                                $.wa.editor.saveBlockData(column_id, column_data, {
                                    notify_editor_inside_iframe: true,
                                    mode: 'update',
                                    delay: 400,
                                });
                                console.log('saveBlockData', column_data);
                            },
                            changeComponent: function(key) {
                                let self = this;
                                let $editable = that.$target_wrapper.find('.style-wrapper');
                                $editable = $editable.length ? $editable : that.$target_wrapper;
                                $editable = $editable.find('.site-block-column').eq(key);
                                let column_block_id = $editable.data('block-id');
                                updateSelectedBlock(column_block_id)
                            },
                            addColumn: function(event) {
                                let self = this;
                                const $block_wrapper = that.$target_wrapper.find('.js-seq-wrapper').eq(0);
                                let column_id = $block_wrapper.find('> .seq-child')?.eq(self.columns_data.length - 1)?.data('block-id');
                                $block_wrapper.css('opacity', 0.5)
                                let column_data = {}
                                if (column_id) column_data = { duplicate_block_id: column_id };
                                else column_data = { parent_block_id: $block_wrapper.data('block-id'), type_name: 'site.Column_' };
                                $.post('?module=editor&action=addBlock', column_data).then(function(new_parent_block_html) {
                                    $block_wrapper.replaceWith(new_parent_block_html);
                                });
                            },
                            removeColumn: function() {
                                let self = this;
                                let column_id = that.$target_wrapper.find('.js-seq-wrapper').eq(0).find('> .seq-child').eq(self.columns_data.length - 1).data('block-id');
                                $.wa.editor.removeBlock(column_id, that.$target_wrapper.find('.js-seq-wrapper').eq(0));
                                self.columns_data.pop();
                                console.log('removeColumn', column_id);

                            }
                        }
                    },
                    "ColumnWidthGroup": {
                        props: {
                            group_config: { type: Object },
                            block_data: { type: Object, default: {} },
                            block_id: { type: Number},
                        },
                        data() {
                            //const form_type = that.storage_data[this.group_config.type].type;
                            const form_type_custom = 'custom';
                            const form_type = that.storage_data[this.group_config.type].type;
                            const element = that.states.elements['wrapper'];
                            const selected_element = that.states.selected_element;
                            let arr_options = that.storage_data[this.group_config.type].values;
                            const icons = that.storage_data[this.group_config.type].icons;
                            const active_option = this.block_data?.block_props?.[element]?.[form_type];
                            const header_name = this.group_config.name;
                            //const media_prop = that.media_prop;
                            //const icon = that.media_prop ? icons[media_prop] : 'fa-desktop';
                            return {  arr_options, header_name, active_option, form_type, form_type_custom, element, selected_element}
                        },
                        template: that.templates["component_columns_maxwidth_group"],
                        delimiters: ['{ { ', ' } }'],
                        components: {
                            'MaxWidthDropdown': that.vue_components['component-dropdown'],
                          },
                        methods: {
                            change: function(option) {
                                let self = this;
                                //console.log('saveBlockData', option)
                            },

                        },
                        mounted: function() {
                            const self = this;
                            $(self.$el).find("#tooltip-column-max-width").waTooltip();
                        }
                    },
                    "CardsGroup": {
                        props: {
                            group_config: { type: Object },
                            block_data: { type: Object, default: {} },
                            block_id: { type: Number},
                        },
                        data() {
                            //const form_type = that.storage_data[this.group_config.type].type;
                            const form_type = 'customColumn';
                            self.columns_nodes = that.$target_wrapper.find('.js-seq-wrapper').eq(0).find('> .seq-child');
                            let columns_data = [];
                            let cards_dropdown_array = [];
                            let indestructible_cols = this.block_data?.indestructible || false;
                            cards_dropdown_array.push({
                                name: 'Choose card',
                                value: 'default'
                            });
                            $.each(self.columns_nodes, function(i, node) {
                                const column_id = $(node).attr('data-block-id');
                                const column_data = Object.assign({}, $.wa.editor.block_storage.getData(column_id));
                                cards_dropdown_array.push({
                                    name: (i+1) + ' card',
                                    value: column_id
                                });
                                columns_data.push(column_data.column);
                                if (column_data?.indestructible) indestructible_cols = true;
                            });
                            //console.log(columns_data);

                            let arr_options = that.storage_data['ColumnsGroup'].values;
                            const icons = that.storage_data['ColumnsGroup'].icons;
                            const active_option = this.block_data?.block_props?.[form_type];

                            const header_name = this.group_config.name;
                            const media_prop = that.media_prop;
                            const icon = that.media_prop ? icons[media_prop] : 'fa-desktop';
                            return { cards_dropdown_array, indestructible_cols, columns_data, arr_options, header_name, active_option, form_type, media_prop, icon}
                        },
                        template: that.templates["component_cards_group"],
                        delimiters: ['{ { ', ' } }'],
                        components: {
                            'CardsListDropdown': that.vue_components['component-dropdown'],
                            'CustomButton': that.vue_components['custom-button'],
                          },
                        methods: {
                            change: function(option, column_num, activeOption) { //put classes to remove in temp_active_option
                                let self = this;

                            },
                            changeComponent: function(option) {
                                let self = this;
                                let key = option;
                                let column_block_id = option.value;
                                /*let $editable = that.$target_wrapper.find('.style-wrapper');
                                $editable = $editable.length ? $editable : that.$target_wrapper;
                                $editable = $editable.find('.site-block-column').eq(key);
                                let column_block_id = $editable.data('block-id');*/
                                updateSelectedBlock(column_block_id)
                            },
                            addColumn: function(event) {
                                let self = this;
                                const $block_wrapper = that.$target_wrapper.find('.js-seq-wrapper').eq(0);
                                let column_id = $block_wrapper.find('> .seq-child')?.eq(self.columns_data.length - 1)?.data('block-id');
                                $block_wrapper.css('opacity', 0.5)
                                let column_data = {}
                                if (column_id) column_data = { duplicate_block_id: column_id };
                                else column_data = { parent_block_id: $block_wrapper.data('block-id'), type_name: 'site.Column_' };
                                $.post('?module=editor&action=addBlock', column_data).then(function(new_parent_block_html) {
                                    $block_wrapper.replaceWith(new_parent_block_html);
                                });
                            },
                            removeColumn: function() {
                                let self = this;
                                let column_id = that.$target_wrapper.find('.js-seq-wrapper').eq(0).find('> .seq-child').eq(self.columns_data.length - 1).data('block-id');
                                $.wa.editor.removeBlock(column_id, that.$target_wrapper.find('.js-seq-wrapper').eq(0));
                                self.columns_data.pop();
                                console.log('removeColumn', column_id);

                            }
                        }
                    },
                    "ColumnsAlignGroup": {
                        props: {
                            group_config: { type: Object },
                            block_data: { type: Object, default: {} },
                            block_id: { type: Number},
                        },
                        data() {
                            const form_type = that.storage_data[this.group_config.type].type;
                            let arr_options = that.storage_data[this.group_config.type].values;
                            //const element = that.states.elements['wrapper'];
                            const active_option = this.block_data?.wrapper_props?.[form_type];
                            const header_name = this.group_config.name;
                            return { arr_options, header_name, active_option, form_type }
                        },
                        template: that.templates["component_columns_align_group"],
                        delimiters: ['{ { ', ' } }'],
                        components: {
                            'ColumnsAlignDropdown': that.vue_components['component-dropdown'],
                          },
                        methods: {
                            change: function(option) { //set class props for hseq wrapper
                                let self = this;
                                $.wa.editor._block_settings_drawer_promise.then(function(bs) {
                                    if (!self.block_data.wrapper_props) self.block_data.wrapper_props = {};
                                    self.block_data.wrapper_props[self.form_type] = option.value;
                                    bs.saveBlockData(self.block_data);
                                    console.log('saveBlockData', self.block_data)
                                });
                                self.active_option = option.value;
                            },
                        }
                    },
                    "ColumnsAlignVerticalGroup": {
                        props: {
                            group_config: { type: Object },
                            block_data: { type: Object, default: {} },
                            block_id: { type: Number},
                        },
                        data() {
                            const form_type = that.storage_data[this.group_config.type].type;
                            let arr_options = that.storage_data[this.group_config.type].values;
                            //const element = that.states.elements['wrapper'];
                            const active_option = this.block_data?.wrapper_props?.[form_type];
                            const header_name = this.group_config.name;
                            const form_type_custom = 'custom';
                            return { form_type_custom, arr_options, header_name, active_option, form_type}
                        },
                        template: that.templates["component_columns_align_vertical_group"],
                        delimiters: ['{ { ', ' } }'],
                        components: {
                            'ColumnsAlignDropdown': that.vue_components['component-dropdown'],
                          },
                          methods: {
                            change: function(option) { //set class props for hseq wrapper
                                let self = this;
                                $.wa.editor._block_settings_drawer_promise.then(function(bs) {
                                    if (!self.block_data.wrapper_props) self.block_data.wrapper_props = {};
                                    self.block_data.wrapper_props[self.form_type] = option.value;
                                    bs.saveBlockData(self.block_data);
                                    console.log('saveBlockData', self.block_data)
                                });
                                self.active_option = option.value;
                            },
                        }
                    },
                    "ColumnAlignVerticalGroup": {
                        props: {
                            group_config: { type: Object },
                            block_data: { type: Object, default: {} },
                            block_id: { type: Number},
                        },
                        data() {
                            const form_type = that.storage_data[this.group_config.type].type;
                            let arr_options = that.storage_data[this.group_config.type].values;
                            const element = that.states.elements['main'];
                            const active_option = this.block_data?.block_props?.[element]?.[form_type];
                            const header_name = this.group_config.name;
                            return { element, arr_options, header_name, active_option, form_type}
                        },
                        template: that.templates["component_column_align_vertical_group"],
                        delimiters: ['{ { ', ' } }'],
                        components: {
                            'ColumnsAlignDropdown': that.vue_components['component-dropdown'],
                        },
                    },
                    "RowsAlignGroup": {
                        props: {
                            group_config: { type: Object },
                            block_data: { type: Object, default: {} },
                            block_id: { type: Number},
                        },
                        data() {
                            const form_type = that.storage_data[this.group_config.type].type;
                            let arr_options = that.storage_data[this.group_config.type].values;
                            //const element = that.states.elements['wrapper'];
                            const active_option = this.block_data?.wrapper_props?.[form_type];
                            const header_name = this.group_config.name;
                            const form_type_custom = 'custom';
                            return { form_type_custom, arr_options, header_name, active_option, form_type}
                          },
                        template: that.templates["component_rows_align_group"],
                        delimiters: ['{ { ', ' } }'],
                        components: {
                            'RowsAlignDropdown': that.vue_components['component-dropdown'],
                          },
                        methods: {
                            change: function(option) { //set class props for hseq wrapper
                                let self = this;
                                $.wa.editor._block_settings_drawer_promise.then(function(bs) {
                                    if (!self.block_data.wrapper_props) self.block_data.wrapper_props = {};
                                    self.block_data.wrapper_props[self.form_type] = option.value;
                                    bs.saveBlockData(self.block_data);
                                    console.log('saveBlockData', self.block_data)
                                });
                                self.active_option = option.value;
                            },
                        }
                    },
                    "RowsWrapGroup": {
                        props: {
                            group_config: { type: Object },
                            block_data: { type: Object, default: {} },
                            block_id: { type: Number},
                        },
                        data() {
                            const form_type = that.storage_data[this.group_config.type].type;
                            const options = that.storage_data[this.group_config.type].values[0];
                            const icons = that.storage_data[this.group_config.type].icons;
                            //const element = that.states.elements['wrapper'];
                            const icon = that.media_prop ? icons[that.media_prop] : 'fa-desktop';
                            const disabledValue = false;
                            const header_name = this.group_config.name;

                            let active_options = this.block_data?.wrapper_props?.[form_type]?.split(' ') || [];
                            const currentValue = that.media_prop? options[`value_${that.media_prop}`] : options.value;
                            let activeValue = !active_options.includes(currentValue);
                            return { active_options, header_name, activeValue, currentValue, form_type, icon, disabledValue}
                          },
                          template: `
                          <div class="s-editor-option-wrapper">
                            <div class="s-editor-option-body custom-mt-8">
                                <div class="switch-wrap-group custom-mb-24 flexbox middle space-12">
                                    <switch-toggle styleData="flex-shrink: 0" activeName="switch-wrap" :activeValue="activeValue" :disabled="disabledValue" @changeSwitch="changeSwitch" :textValue="$t('custom.Move elements to the next line')" switchClass="smaller"></switch-toggle>
                                    <span class="s-icon icon text-light-gray" v-if="icon"><i class="fas" :class="icon"></i></span>
                                </div>
                            </div>
                          </div>
                          `,
                        delimiters: ['{ { ', ' } }'],
                        components: {
                            'SwitchToggle': that.vue_components['component-switch'],
                          },
                        methods: {
                            changeSwitch(option) {
                                let self = this;
                                let temp_active_options = self.active_options.slice();
                                if (option) {
                                    temp_active_options = temp_active_options.filter((el) => el !== self.currentValue);
                                } else {
                                    if (!temp_active_options.includes(self.currentValue)) temp_active_options.push(self.currentValue);
                                }
                                //console.log(option, self.currentValue, temp_active_options)
                                if (!self.block_data.wrapper_props) self.block_data.wrapper_props = {};
                                self.block_data.wrapper_props[self.form_type] = temp_active_options.join(' ');
                                if (!temp_active_options.length) delete self.block_data.wrapper_props[self.form_type];

                                $.wa.editor._block_settings_drawer_promise.then(function(bs) {
                                    bs.saveBlockData(self.block_data);
                                    console.log('saveBlockData', self.block_data)
                                });
                                self.active_options = temp_active_options;
                                self.activeValue = option;
                            },
                        }
                    },
                    "RowsAttrsVisibilityGroup": {
                        props: {
                            group_config: { type: Object },
                            block_data: { type: Object, default: {} },
                            block_id: { type: Number},
                        },
                        data() {
                            const form_type = that.storage_data[this.group_config.type].type;
                            const options = that.storage_data[this.group_config.type].values;
                            const header_name = this.group_config.name;
                            const hidden_attrs = this.block_data.hidden_attrs;
                            const active_options = options.map(option => {
                                option.value = !hidden_attrs[option.key];
                                return option;
                            });
                            const is_visible = this.block_data.additional.product.sku_type === '0' && Object.values(this.block_data.additional.product.skus || {}).length > 1;

                            return { active_options, header_name, form_type, is_visible }
                          },
                          template: `
                          <div v-if="is_visible" class="s-editor-option-wrapper">
                            <p class="s-semi-header text-gray small custom-mb-8">{{ header_name }}</p>
                            <div class="s-editor-option-body">
                                <div class="switch-wrap-group">
                                    <div v-for="option in active_options" class="custom-mb-4">
                                        <switch-toggle :activeName="form_type+option.key" :activeValue="option.value" @changeSwitch="changeSwitch($event, option.key)" :textValue="$t('custom.'+option.name)" switchClass="smaller"></switch-toggle>
                                    </div>
                                </div>
                            </div>
                          </div>
                          `,
                        components: {
                            'SwitchToggle': that.vue_components['component-switch'],
                        },
                        methods: {
                            changeSwitch(value, key) {
                                const self = this;
                                if (value) {
                                    delete self.block_data.hidden_attrs[key];
                                } else {
                                    self.block_data.hidden_attrs[key] = 1;
                                }

                                $.wa.editor._block_settings_drawer_promise.then(function(bs) {
                                    bs.saveBlockData(self.block_data);
                                    console.log('saveBlockData', self.block_data)
                                });
                            },
                        }
                    },
                    "MenuDecorationGroup": {
                        props: {
                            group_config: { type: Object },
                            block_data: { type: Object, default: {} },
                            block_id: { type: Number},
                        },
                        data() {
                            const header_name = this.group_config.name;
                            const form_type = that.storage_data[this.group_config.type].type;
                            let arr_options = that.storage_data[this.group_config.type].values;
                            const element = that.states.elements['main'];
                            let activeValue = false;
                            if (element) {
                                activeValue = this.block_data?.block_props?.[element]?.[form_type] || false;
                            };
                            let active_option = false;
                            if (activeValue) {
                                var filter_search = arr_options.filter( function(option) {
                                    return activeValue === option.value;
                                });
                                active_option = (filter_search.length ? filter_search[0] : false);
                            }
                            return { header_name, activeValue, arr_options, active_option, element, form_type }
                          },
                        template: `
                        <div class="s-editor-option-wrapper">
                            <div class="s-semi-header text-gray small">{ { header_name } }</div>
                            <div class="s-editor-option-body custom-mt-8">
                                <div class="switch-style-group" style="font-size: 95%;">
                                    <switch-sticky activeName="activeStk" :activeValue="active_option.key === 'activeStk' || active_option.key === 'activeBoth'" @changeSwitch="changeSwitch(arr_options[0].value, $event)" :textValue="$t('custom.' + arr_options[0].name)" switchClass="smaller"></switch-sticky>
                                </div>
                                <div class="switch-style-group" style="font-size: 95%;">
                                    <switch-abs activeName="activeAbs" :activeValue="active_option.key === 'activeAbs' || active_option.key === 'activeBoth'" @changeSwitch="changeSwitch(arr_options[1].value, $event)" :textValue="$t('custom.' + arr_options[1].name)" switchClass="smaller"></switch-abs>
                                </div>
                            </div>
                        </div>
                        `,
                        delimiters: ['{ { ', ' } }'],
                        components: {
                            'SwitchSticky': that.vue_components['component-switch'],
                            'SwitchAbs': that.vue_components['component-switch'],
                          },
                        methods: {
                            changeSwitch(option, event) {
                                let self = this;
                                let result_value = '';

                                if (event) {
                                    if (!self.active_option) {
                                        result_value = option;
                                    } else {
                                        result_value = self.arr_options[2].value;
                                    }
                                } else {
                                    if (self.active_option.value === option) {
                                        self.activeValue = false;
                                        self.active_option = false;
                                        $.wa.editor._block_settings_drawer_promise.then(function(bs) {
                                            delete self.block_data.block_props[self.element][self.form_type];
                                            bs.saveBlockData(self.block_data);
                                        });
                                        return;
                                    } else {
                                        result_value = (self.arr_options[0].value === option) ? self.arr_options[1].value : self.arr_options[0].value;
                                    }
                                }

                                let filter_search = self.arr_options.filter( function(opt) {
                                    return result_value === opt.value;
                                })[0];
                                self.activeValue = result_value;
                                self.active_option = filter_search;
                                $.wa.editor._block_settings_drawer_promise.then(function(bs) {
                                    if (!self.block_data.block_props) self.block_data.block_props = {};
                                    if (!self.block_data.block_props[self.element]) self.block_data.block_props[self.element] = {};
                                    self.block_data.block_props[self.element][self.form_type] = result_value;
                                    bs.saveBlockData(self.block_data);
                                    console.log('saveBlockData', self.block_data);
                                });

                            },
                        },
                        mounted: function() { }
                    },
                    "MenuToggleGroup": {
                        props: {
                            group_config: { type: Object },
                            block_data: { type: Object, default: {} },
                            block_id: { type: Number},
                        },
                        data() {
                            const header_name = this.group_config.name;
                            const activeValue = this.block_data.app_template?.['active'] || false;
                            const disabledValue = this.block_data.app_template?.['disabled'] || false;
                            return { header_name, activeValue, disabledValue }
                        },
                        template: `
                        <div class="switch-app-group custom-mb-32 custom-mt-24 flexbox middle space-12">
                            <switch-toggle activeName="switch-app" :activeValue="activeValue" :disabled="disabledValue" @changeSwitch="changeSwitch" :textValue="$t('custom.Menu from sections')" switchClass="small"></switch-toggle>
                            <span id="tooltip-switch-app" :data-wa-tooltip-content="$t('custom.tooltip-switch-app')">
                                <i class="fas fa-question-circle"></i>
                            </span>
                        </div>
                        `,
                        delimiters: ['{ { ', ' } }'],
                        components: {
                            'SwitchToggle': that.vue_components['component-switch'],
                          },
                        methods: {
                            changeSwitch(option) {
                                let self = this;
                                let $editable = that.$target_wrapper;
                                let $app_template_section = $editable.siblings('.app-template');
                                let $main_settings_wrapper =  $(self.$el).closest('.site-editor-block-settings');
                                self.block_data.html = $editable.html();
                                if (option) {
                                    $editable.hide();
                                    const app_template = $app_template_section.data('template').replace(/\"/g, '"');
                                    $app_template_section.html(app_template).show();
                                    $main_settings_wrapper.children().each(function(i, el){
                                        if (i > 2) $(el).hide();
                                    })
                                } else {
                                    $editable.show();
                                    $app_template_section.html('').hide();
                                    $main_settings_wrapper.children().each(function(i, el){
                                        if (i > 2) $(el).show();
                                    })
                                }

                                $.wa.editor._block_settings_drawer_promise.then(function(bs) {
                                    if (!self.block_data.app_template) self.block_data.app_template = {};
                                    self.block_data.app_template['active'] = option;
                                    bs.saveBlockData(self.block_data, false);
                                });

                            },
                        },
                        mounted: function() { //Костыль / Сломается если будет долгая загрузка
                            const self = this;
                            let $main_settings_wrapper =  $(self.$el).closest('.site-editor-block-settings');
                            if (self.activeValue) {
                                $(() => setTimeout(function(){
                                    $main_settings_wrapper.children().each(function(i, el){
                                        if (i > 2) $(el).hide();
                                    })
                                 }, 50));
                            }
                            $(self.$el).find("#tooltip-switch-app").waTooltip();
                        }
                    },
                    "MaxWidthToggleGroup": {
                        props: {
                            group_config: { type: Object },
                            block_data: { type: Object, default: {} },
                            block_id: { type: Number},
                        },
                        data() {
                            const header_name = this.group_config.name;
                            const element = that.states.elements['wrapper'];
                            const selected_element = that.states.selected_element;
                            const form_type = that.storage_data[this.group_config.type].type;
                            let arr_option = that.storage_data[this.group_config.type].values[0].value;
                            let activeValue = false;
                            if (element) {
                                activeValue = this.block_data?.block_props?.[element]?.[form_type] || false;
                            };
                            const disabledValue = false;
                            return { header_name, activeValue, disabledValue, arr_option, form_type, element, selected_element }
                        },
                        template: `
                        <div class="switch-max-width-group custom-mb-32 custom-mt-24 flexbox middle space-12" v-if="element === selected_element">
                            <switch-toggle activeName="switch-max-width" :activeValue="activeValue" :disabled="disabledValue" @changeSwitch="changeSwitch" :textValue="$t('custom.Width is limited')" switchClass="smaller"></switch-toggle>
                            <span id="tooltip-max-width-toggle" data-wa-tooltip-template="#tooltip-max-width-toggle1">
                                <i class="fas fa-question-circle text-light-gray"></i>
                            </span>
                            <div class="wa-tooltip-template" id="tooltip-max-width-toggle1" >
                                <div style="width: 240px"> { { $t('custom.tooltip-max-width-toggle') } }</div>
                            </div>
                        </div>
                        `,
                        delimiters: ['{ { ', ' } }'],
                        components: {
                            'SwitchToggle': that.vue_components['component-switch'],
                          },
                        methods: {
                            changeSwitch(option) {
                                let self = this;

                                if (option) {
                                    if (!self.block_data.block_props) self.block_data.block_props = {};
                                    if (!self.block_data.block_props[self.element]) self.block_data.block_props[self.element] = {};
                                    self.block_data.block_props[self.element][self.form_type] = self.arr_option;
                                }
                                else {
                                    if (self.block_data.block_props[self.element]) delete self.block_data.block_props[self.element][self.form_type];
                                }

                                $.wa.editor._block_settings_drawer_promise.then(function(bs) {
                                    bs.saveBlockData(self.block_data);
                                    console.log('saveBlockData', self.block_data);
                                });

                            },
                        },
                        mounted: function() {
                            const self = this;
                            $(self.$el).find("#tooltip-max-width-toggle").waTooltip();
                        }
                    },
                    "FullWidthToggleGroup": {
                        props: {
                            group_config: { type: Object },
                            block_data: { type: Object, default: {} },
                            block_id: { type: Number},
                        },
                        data() {
                            const header_name = this.group_config.name;
                            //const element = that.states.elements['wrapper'];
                            //const selected_element = that.states.selected_element;
                            const form_type = that.storage_data[this.group_config.type].type;
                            let arr_option = that.storage_data[this.group_config.type].values[0];
                            let activeValue = this.block_data?.block_props?.[form_type] || false;

                            const disabledValue = false;
                            return { header_name, activeValue, disabledValue, arr_option, form_type }
                        },
                        template: `
                        <div class="switch-max-width-group flexbox middle space-12">
                            <switch-toggle activeName="switch-max-width" :activeValue="activeValue" :disabled="disabledValue" @changeSwitch="changeSwitch" :textValue="$t('custom.' + arr_option.name)" switchClass="smaller no-shrink custon-mt-4" class="small"></switch-toggle>
                           <!--<span id="tooltip-max-width-toggle" data-wa-tooltip-template="#tooltip-max-width-toggle1">
                                <i class="fas fa-question-circle text-light-gray"></i>
                            </span>
                            <div class="wa-tooltip-template" id="tooltip-max-width-toggle1" >
                                <div style="width: 240px"> { { $t('custom.tooltip-max-width-toggle') } }</div>
                            </div>-->
                        </div>
                        `,
                        delimiters: ['{ { ', ' } }'],
                        components: {
                            'SwitchToggle': that.vue_components['component-switch'],
                          },
                        methods: {
                            changeSwitch(option) {
                                let self = this;

                                if (option) {
                                    if (!self.block_data.block_props) self.block_data.block_props = {};
                                    self.block_data.block_props[self.form_type] = self.arr_option.value;
                                }
                                else {
                                    if (self.block_data.block_props) delete self.block_data.block_props[self.form_type];
                                }

                                $.wa.editor._block_settings_drawer_promise.then(function(bs) {
                                    bs.saveBlockData(self.block_data);
                                    console.log('saveBlockData', self.block_data);
                                });

                            },
                        },
                        mounted: function() {
                            const self = this;
                            //$(self.$el).find("#tooltip-max-width-toggle").waTooltip();
                        }
                    },

                    "FontStyleGroup": {
                        props: {
                            group_config: { type: Object },
                            block_data: { type: Object, default: {} },
                            block_id: { type: Number},
                        },
                        data() {
                            const form_type = that.storage_data[this.group_config.type].type;
                            const tagsArr = ['B', 'I', 'U', 'STRIKE', 'A', 'FONT']
                            let arr_options = that.storage_data[this.group_config.type].values;

                            const { reactive, ref } = Vue;
                            let selection = reactive(that.$iframe_wrapper[0].getSelection());
                            let for_key = 0;

                            const active_options = reactive(this.block_data?.block_props?.[form_type] ? this.block_data.block_props[form_type].split(' ') : []);
                            const button_class = 'nobutton';
                            let showLinkModal = false;
                            let showAIPanel = false;
                            const parentsBlocks = Object.assign(that.states.parents || {}, { [that.states.form_config.type_name]: this.block_id });
                            //console.log(parents, this.block_data, that.states.form_config.type_name)
                            let active_link_options = {};
                            let prompt_options = that.storage_data['ai_prompt_data']?.[this.group_config.block_type];
                            let aiFacility = prompt_options.facility;
                            return { aiFacility, prompt_options, showAIPanel, active_link_options, parentsBlocks, for_key, selection, arr_options, button_class, form_type, active_options, tagsArr, showLinkModal}
                        },
                        template: `
                            <div class="font-style-group custom-mb-16">
                                <button class="button smaller nobutton gray" title="Webasyst AI" @click="showAIPanel = !showAIPanel">
                                    <span class="icon webasyst-ai"></span>
                                </button>
                                <template v-for="(item, key) in arr_options" :key="block_id-key">
                                    <custom-button :buttonClass="button_class" :iconClass="item.icon" @click="change(item.value)" :selfOption="item.value" :activeOptions="active_options" :title="$t(form_type+'.'+item.name)"></custom-button>
                                </template>
                                <ai-generator v-if="showAIPanel" :group_config="group_config" :form_type="form_type" :facility="aiFacility" @generate="handleAiAnswer" @undo="undoElement" @closeForm="showAIPanel = !showAIPanel" :block_id="block_id" container_class="text-panel"></ai-generator>
                            </div>
                            <form-link v-if="showLinkModal" :active_link_options="active_link_options" :parents="parentsBlocks"  @closeDrawer="toggleModal" @goToParent="goToParent" @updateLink="change" :selection="selection" :key="for_key"></form-link>
                            `,
                        //delimiters: ['{ { ', ' } }'],
                        components: {
                            'CustomButton': that.vue_components['custom-button'],
                            'AiGenerator': that.vue_components['component-ai-generator'],
                            "FormLink": {
                                props: {
                                    selection : { type: Object },
                                    parents : { type: Object, default: {} },
                                    active_link_options: { type: Object, default: {} }
                                },
                                emits: ['closeDrawer', 'updateLink', 'goToParent'],
                                data() {
                                    const link_header = 'Link';
                                    const arr_options = that.storage_data.link_dropdown_data;
                                    const arr_block_options = that.storage_data.link_block_data;
                                    const arr_pages_options = that.storage_data.link_page_data;
                                    const form_type = 'custom';
                                    let selection_attr = {
                                        active_option: 'internal-link',
                                        active_block_option: null,
                                        active_pages_option: null,
                                        inputEmail: '',
                                        inputSubject: '',
                                        inputPhone: '',
                                        inputCheckbox: false,
                                        inputCheckboxNoFollow: false
                                    };

                                    const active_data = arr_options.filter( function(option) {
                                        return (option.value === 'internal-link');
                                    })[0];
                                    //
                                    return { selection_attr, active_data, link_header, arr_options, arr_block_options, arr_pages_options, form_type}
                                  },
                                computed: {
                                    isEmptyData: function () {
                                       return jQuery.isEmptyObject(this.active_link_options)
                                    }
                                },
                                template: that.templates["component_form_link_group"],
                                components: {
                                    'FormHeader': that.vue_components['form-header'],
                                    'LinkActionDropdown': that.vue_components['component-dropdown'],
                                    'CustomButton': that.vue_components['custom-button'],
                                },
                                created: function() {
                                    let self = this;
                                    let selection_temp = self.active_link_options ? self.active_link_options : self.selection.anchorNode.parentElement.attributes;
                                    if (selection_temp['data-value']) {
                                        let value = selection_temp.getNamedItem("data-value").value;
                                        self.selection_attr.active_option = value;
                                        self.active_data = self.arr_options.filter( function(option) {
                                            return (option.value === value);
                                        })[0];

                                        if (value === 'external-link') {
                                            self.selection_attr.inputEmail = selection_temp.getNamedItem("href").nodeValue;
                                        }
                                        if (value === 'email-link' ) {
                                            const temp = selection_temp.getNamedItem("href").nodeValue.split('mailto:')[1];
                                            self.selection_attr.inputEmail = temp.split('?subject=')[0];
                                            self.selection_attr.inputSubject = temp.split('?subject=')[1];
                                        }
                                        if (value === 'phone-link' ) {
                                            self.selection_attr.inputPhone = selection_temp.getNamedItem("href").nodeValue.split('tel:')[1];
                                        }
                                        if (value === 'internal-link') {
                                            self.selection_attr.active_pages_option = selection_temp.getNamedItem("data-block")?.value || null;
                                            if (self.selection_attr.active_pages_option === null) {
                                                self.selection_attr.inputEmail = selection_temp.getNamedItem("href").nodeValue;
                                            }
                                        }
                                        if (value === 'block-link' ) {
                                            self.selection_attr.active_block_option = selection_temp.getNamedItem("data-block")?.value || null;
                                            if (self.selection_attr.active_block_option === null) {
                                                self.selection_attr.inputEmail = selection_temp.getNamedItem("href").nodeValue;
                                            }
                                        }
                                        if (selection_temp.getNamedItem("target") ) {
                                            self.selection_attr.inputCheckbox = true;
                                        }
                                        if (selection_temp.getNamedItem("rel") ) {
                                            self.selection_attr.inputCheckboxNoFollow = true;
                                        }
                                    }
                                },
                                mounted: function() {
                                    let self = this;
                                    self.mountTooltips();
                                },
                                methods: {
                                    change: function(option) {
                                        let self = this;
                                        self.active_data = self.arr_options.filter( function(opt) {
                                            return (opt.value === option.value);
                                        })[0];
                                        setTimeout(()=> self.mountTooltips(), 0);
                                    },
                                    mountTooltips: function() {
                                        let self = this;
                                        $(self.$el).find("#tooltip-internal-link")?.waTooltip();
                                        $(self.$el).find("#tooltip-block-link")?.waTooltip();
                                    },
                                    changePagesDropdown: function(option) {
                                        let self = this;
                                        self.selection_attr.active_pages_option = option.value;
                                    },
                                    changeBlockDropdown: function(option) {
                                        let self = this;
                                        self.selection_attr.active_block_option = option.value;
                                    },
                                    changeAnchor() {
                                        let self = this;
                                        let temp_input = self.selection_attr.inputEmail;
                                        if (temp_input !== '#' && temp_input !== '') {
                                            let hash_pos = temp_input.indexOf('#');
                                            if (hash_pos >= 0) {
                                                self.selection_attr.inputEmail = '#' + temp_input.split('#')[1];
                                            } else {
                                                self.selection_attr.inputEmail = '#' + temp_input;
                                            }
                                        }
                                    },
                                    saveLink: function() {
                                        let self = this;
                                        let new_url = self.selection_attr.inputEmail;
                                        let active_data = self.active_data;
                                        let active_block_attr = '';
                                        //const selection = that.$iframe_wrapper[0].getSelection();

                                        if (active_data.value === 'email-link' ) {
                                            new_url = 'mailto:' + new_url;
                                            if (self.selection_attr.inputSubject) {
                                                new_url = new_url + '?subject=' + self.selection_attr.inputSubject
                                            }
                                        }
                                        if (active_data.value === 'phone-link' ) {
                                            new_url = 'tel:' + self.selection_attr.inputPhone;
                                        }
                                        if (active_data.value === 'internal-link' ) {
                                            active_block_attr = self.selection_attr.active_pages_option;
                                            if (active_block_attr) {
                                                new_url = self.arr_pages_options.filter( function(option) {
                                                    return (option.value === self.selection_attr.active_pages_option);
                                                })[0]['url'];
                                            }
                                        }
                                        if (active_data.value === 'block-link' ) {
                                            active_block_attr = self.selection_attr.active_block_option;
                                            if (active_block_attr) {
                                                new_url = self.arr_block_options.filter( function(option) {
                                                    return (option.value === self.selection_attr.active_block_option);
                                                })[0]['url'];
                                            } else {
                                                new_url = new_url
                                            }
                                        }
                                        if (new_url) {

                                            //попробовал сделать через insertHTML, работает криво
                                            /*let linkNode = document.createElement('a')
                                            linkNode.href = new_url;
                                            linkNode.innerText = self.selection.toString();

                                            if (self.selection_attr.inputCheckbox) {
                                                linkNode.target = '_blank'
                                            }
                                            linkNode.setAttribute("data-value", active_data.value)
                                            if (active_block_attr) {
                                                linkNode.target.setAttribute("data-block", active_block_attr)
                                            }
                                            console.log(self.selection.toString(), linkNode.outerHTML);
                                            self.$emit('updateLink', 'insertHTML', linkNode.outerHTML);
                                            self.$emit('closeDrawer');*/

                                            self.$emit('updateLink', 'CreateLink', new_url);
                                            self.$emit('closeDrawer');

                                            if (self.selection_attr.inputCheckbox) {
                                                self.selection.anchorNode.parentElement.target = '_blank';
                                            }
                                            if (self.selection_attr.inputCheckboxNoFollow) {
                                                self.selection.anchorNode.parentElement.rel = 'nofollow';
                                            }
                                            self.selection.anchorNode.parentElement.setAttribute("data-value", active_data.value)
                                            if (active_block_attr) {
                                                self.selection.anchorNode.parentElement.setAttribute("data-block", active_block_attr)
                                            }
                                            //self.cleanForm();

                                        }
                                    },
                                    deleteLink: function() {
                                        let self = this;
                                        //self.cleanForm();

                                        self.$emit('updateLink', 'unlink');
                                        self.$emit('closeDrawer');


                                    },
                                    cleanForm: function() {
                                        let self = this;

                                        /*self.selection_attr = {
                                            active_option: 'external-link',
                                            active_block_option: null,
                                            active_pages_option: null,
                                            inputEmail: '',
                                            inputSubject: '',
                                            inputPhone: '',
                                            inputCheckbox: false,
                                            inputCheckboxNoFollow: false
                                        };*/
                                        //self.clean_key += 1;
                                        //console.log('cleankey', self.clean_key)
                                        //self.selection_attr.inputEmail = '';

                                    },
                                    goToParent: function(parent_id) {
                                        let self = this;
                                        self.$emit('goToParent', parent_id)
                                    }
                                }
                            },

                        },
                        methods: {
                            change: function(option, url = null) { //put classes to remove in temp_active_option
                                let self = this;
                                let $editable = that.$target_wrapper.find('.style-wrapper');
                                $editable = $editable.length ? $editable : that.$target_wrapper;

                                if (option === 'variables') {
                                    $('.site-editor-wa-header-wrapper').find('.js-show-variables').click();
                                    return
                                }

                                if (option === 'link') {
                                    self.toggleModal();
                                    return
                                }
                                //проверяем есть ли выделенный фрагмент или просто фокус на элементе
                                let selected_text_length = (that.$iframe_wrapper[0].getSelection().anchorOffset - that.$iframe_wrapper[0].getSelection().extentOffset);
                                //если просто фокус, берем весь контент элемента на котором фокус, если родитель сам блок, то ничего не делаем
                                if (selected_text_length === 0) {
                                    const targetEl = self.getSelectionBoundaryElement()
                                    let tagN = targetEl.tagName;
                                    //console.log(targetEl, tagN)
                                    if (self.tagsArr.indexOf(tagN) < 0) return;
                                    let rng = that.$iframe_wrapper[0].createRange()
                                    let sel = that.$iframe_wrapper[0].getSelection()
                                    rng.selectNodeContents(targetEl)
                                    sel.removeAllRanges()
                                    sel.addRange(rng)
                                }
                                //магия execCommand
                                that.$iframe_wrapper[0].execCommand(option, false, url); // сохраняет форму сама по себе, т.к. идет событие input в элементе

                                //self.block_data.html = $editable.html();
                                $.wa.editor._block_settings_drawer_promise.then(function(bs) {
                                    self.block_data.html = $editable.html();
                                    bs.saveBlockData(self.block_data);
                                });

                                //Update buttons
                                let temp_active_options = self.active_options;
                                if (option === 'CreateLink' || option === 'insertHTML' || option === 'unlink') {
                                    option = 'link';
                                }
                                const classPosition = temp_active_options.indexOf(option);
                                if ( classPosition >= 0 ) {
                                    temp_active_options.splice(classPosition, 1);
                                } else {
                                    temp_active_options.push(option);
                                }
                                self.active_options = temp_active_options;
                            },
                            handleAiAnswer: function(data) {
                                let self = this;
                                let $editable = that.$target_wrapper.find('.style-wrapper');
                                $editable = $editable.length ? $editable : that.$target_wrapper;
                                self.oldElementHtml = $editable.html();

                                let response_type = self.prompt_options?.response_type;
                                //console.log(response_type, data?.someData?.[response_type])
                                if (data?.someData?.[response_type]) {
                                    let rng = that.$iframe_wrapper[0].createRange()
                                    let sel = that.$iframe_wrapper[0].getSelection()
                                    rng.selectNodeContents($editable[0])
                                    sel.removeAllRanges()
                                    sel.addRange(rng)

                                    if (response_type !== 'list') {
                                        console.log('text', $(data.someData[response_type]).text().replace(/\s+/g, " "))
                                        //$editable.html($(data.someData[response_type]).html())
                                        that.$iframe_wrapper[0].execCommand('insertText', false, $(data.someData[response_type]).text().replace(/\s+/g, " "));
                                    }
                                    else {
                                        let list_html = '';
                                        data.someData[response_type].forEach(function(elem) {
                                            list_html = list_html + '<li>' + elem + '</li>';
                                        })
                                        $editable.html(list_html)
                                        $.wa.editor._block_settings_drawer_promise.then(function(bs) {
                                            self.block_data.html = $editable.html();
                                            bs.saveBlockData(self.block_data, false);
                                        });
                                    }
                                }
                                else return;

                            },
                            undoElement: function() {
                                let self = this;
                                let $editable = that.$target_wrapper.find('.style-wrapper');
                                $editable = $editable.length ? $editable : that.$target_wrapper;
                                if (self.oldElementHtml) $editable.html(self.oldElementHtml);
                                else return;
                                $.wa.editor._block_settings_drawer_promise.then(function(bs) {
                                    self.block_data.html = $editable.html();
                                    bs.saveBlockData(self.block_data, false);
                                });
                            },
                            toggleModal() {
                                let self = this;
                                self.showLinkModal = !self.showLinkModal;
                            },
                            goToParent: function(parent_id) {
                                let self = this;
                                if (self.block_id === parent_id) {
                                    self.toggleModal();
                                    return
                                }
                                updateSelectedBlock(parent_id)
                            }
                        },
                        created: function() {
                            let self = this;
                            let $editable = that.$target_wrapper.find('.style-wrapper');
                            $editable = $editable.length ? $editable : that.$target_wrapper;

                            //Take element from getSelection()
                            self.getSelectionBoundaryElement = function(isStart) {
                                let range, sel, container;
                                    sel = that.$iframe_wrapper[0].getSelection();
                                    if (sel.rangeCount > 0) {
                                        range = sel.getRangeAt(0);
                                    }

                                    if (range) {
                                       container = range[isStart ? "startContainer" : "endContainer"];
                                       // Check if the container is a text node and return its parent if so
                                       return container.nodeType === 3 ? container.parentNode : container;
                                    }
                            }

                            $editable.on('mouseup', (e) => updateTagData($(e.target)))

                            updateTagData($(self.getSelectionBoundaryElement(true)));

                            // Function highlight the buttons on mouseup and get Link Attributes
                            function updateTagData(targetEl) {

                                //let targetEl = $(e.target);
                                let tagN = targetEl.prop("tagName");
                                let temp_active_options = [];
                                self.active_link_options = [];
                                if (self.showLinkModal) {
                                    self.selection = that.$iframe_wrapper[0].getSelection();
                                    self.for_key += 1;
                                }
                                while (self.tagsArr.indexOf(tagN) >= 0 ) {
                                    switch (tagN) {
                                        case 'B':
                                            temp_active_options.push('bold');
                                            break;
                                        case 'I':
                                            temp_active_options.push('italic');
                                            break;
                                        case 'U':
                                            temp_active_options.push('underline');
                                            break;
                                        case 'STRIKE':
                                            temp_active_options.push('strikethrough');
                                            break;
                                        case 'A':
                                            temp_active_options.push('link');
                                            self.active_link_options = targetEl[0].attributes;
                                            break;
                                        case 'FONT':
                                            break;
                                        default:
                                            temp_active_options = [];
                                            break;
                                    }
                                targetEl = targetEl.parent();
                                tagN = targetEl.prop("tagName");
                               }
                               self.active_options = temp_active_options;
                            }
                        },
                    },
                    "ButtonLinkGroup": { //сделать значение по умолчанию
                        props: {
                            group_config: { type: Object },
                            block_data: { type: Object, default: {} },
                            block_id: { type: Number},
                        },
                        //emits: ['closeDrawer', 'updateLink'],
                        data() {
                            const arr_options = that.storage_data.link_dropdown_data;
                            const arr_block_options = that.storage_data.link_block_data;
                            const arr_pages_options = that.storage_data.link_page_data;
                            const form_type = 'custom';
                            let showSwitch = false;
                            let showChildren = true;
                            const group_header = this.group_config.name;
                            if (this.group_config.is_hidden) {
                                showSwitch = true;
                                showChildren = false;
                            }
                            let selection_attr = {
                                active_option: 'internal-link',
                                active_block_option: null,
                                active_pages_option: null,
                                inputEmail: '',
                                inputSubject: '',
                                inputPhone: '',
                                inputCheckbox: false,
                                inputCheckboxNoFollow: false,
                            };
                            //self.wa_url_front = $.site.wa_url;
                            const show_buttons = false;

                            const active_data = arr_options.filter( function(option) {
                                return (option.value === 'internal-link');
                            })[0];
                            //
                            return { showSwitch, showChildren, show_buttons, selection_attr, active_data, group_header, arr_options, arr_block_options, arr_pages_options, form_type}
                          },

                        template: that.templates["component_button_link_group"],
                        components: {
                            'LinkActionDropdown': that.vue_components['component-dropdown'],
                            'CustomButton': that.vue_components['custom-button'],
                            'SwitchToggle': that.vue_components['component-switch'],
                        },
                        created: function() {
                            let self = this;
                            self.$editable = that.$target_wrapper.find('.style-wrapper');
                            self.$editable = self.$editable.length ? self.$editable : that.$target_wrapper;
                            //let selection = that.$iframe_wrapper[0].getSelection();
                            //let selection_temp = self.selection.anchorNode.parentElement.attributes;
                            //let selection_arr = self.selection_attr;
                            self.isEmptyData = true;
                            if (self.block_data.link_props) {
                                self.isEmptyData = false;
                                let value = self.block_data.link_props['data-value'];
                                self.showChildren = true;
                                self.selection_attr.active_option = value;
                                self.active_data = self.arr_options.filter( function(option) {
                                    return (option.value === value);
                                })[0];
                                if (value === 'external-link') {
                                    self.selection_attr.inputEmail = self.$editable.attr("href");
                                }
                                if (value === 'email-link' ) {
                                    const temp = self.block_data.link_props["href"].split('mailto:')[1];
                                    self.selection_attr.inputEmail = temp.split('?subject=')[0];
                                    self.selection_attr.inputSubject = temp.split('?subject=')[1];
                                }
                                if (value === 'phone-link' ) {
                                    self.selection_attr.inputPhone = self.block_data.link_props["href"].split('tel:')[1];
                                }
                                if (value === 'internal-link') {
                                    self.selection_attr.active_pages_option = self.block_data.link_props["data-block"] || null;
                                    if (self.selection_attr.active_pages_option === null) {
                                        self.selection_attr.inputEmail = self.block_data.link_props["href"];
                                    }
                                }
                                if (value === 'block-link' ) {
                                    self.selection_attr.active_block_option = self.block_data.link_props["data-block"] || null;
                                    if (self.selection_attr.active_block_option === null) {
                                        self.selection_attr.inputEmail = self.block_data.link_props["href"];
                                    }
                                }
                                if (self.block_data.link_props["target"] ) {
                                    self.selection_attr.inputCheckbox = true;
                                }
                                if (self.block_data.link_props["rel"] ) {
                                    self.selection_attr.inputCheckboxNoFollow = true;
                                }
                            }
                        },
                        mounted: function() {
                            let self = this;
                            self.mountTooltips()
                        },
                        methods: {
                            change: function(option) {
                                let self = this;
                                self.active_data = self.arr_options.filter( function(opt) {
                                    return (opt.value === option.value);
                                })[0];
                                self.show_buttons = true;
                                setTimeout(()=> self.mountTooltips(), 0);
                            },
                            changeSwitch(option) {
                                let self = this;
                                //let $editable = that.$target_wrapper;
                                self.showChildren = option
                                if (!option) {
                                    //self.switchValue = option;
                                  self.deleteLink();
                                }
                            },
                            changeAnchor() {
                                let self = this;
                                self.show_buttons = true;
                                let temp_input = self.selection_attr.inputEmail;
                                if (temp_input !== '#' && temp_input !== '') {
                                    let hash_pos = temp_input.indexOf('#');
                                    if (hash_pos >= 0) {
                                        self.selection_attr.inputEmail = '#' + temp_input.split('#')[1];
                                    } else {
                                        self.selection_attr.inputEmail = '#' + temp_input;
                                    }
                                }
                            },
                            changePagesDropdown: function(option) {
                                let self = this;
                                self.selection_attr.active_pages_option = option.value;
                                self.show_buttons = true;
                            },
                            changeBlockDropdown: function(option) {
                                let self = this;
                                self.selection_attr.active_block_option = option.value;
                                self.show_buttons = true;
                            },
                            mountTooltips: function() {
                                let self = this;
                                $(self.$el).find("#tooltip-internal-link")?.waTooltip();
                                $(self.$el).find("#tooltip-block-link")?.waTooltip();
                            },
                            saveLink: function() {
                                let self = this;
                                let new_url = self.selection_attr.inputEmail;
                                let active_data = self.active_data;
                                let active_block_attr = '';

                                if (active_data.value === 'email-link' ) {
                                    new_url = 'mailto:' + new_url;
                                    if (self.selection_attr.inputSubject) {
                                        new_url = new_url + '?subject=' + self.selection_attr.inputSubject
                                    }
                                }
                                if (active_data.value === 'phone-link' ) {
                                    new_url = 'tel:' + self.selection_attr.inputPhone;
                                }
                                if (active_data.value === 'internal-link' ) {
                                    active_block_attr = self.selection_attr.active_pages_option;
                                    if (active_block_attr) {
                                        new_url = self.arr_pages_options.filter( function(option) {
                                            return (option.value === self.selection_attr.active_pages_option);
                                        })[0]['url'];
                                    }
                                }
                                if (active_data.value === 'block-link' ) {
                                    active_block_attr = self.selection_attr.active_block_option;
                                    if (active_block_attr) {
                                        new_url = self.arr_block_options.filter( function(option) {
                                            return (option.value === self.selection_attr.active_block_option);
                                        })[0]['url'];
                                    } else {
                                        new_url = new_url;
                                    }
                                }
                                if (new_url) {
                                    //self.$emit('updateLink', 'CreateLink', new_url);
                                    //self.$emit('closeDrawer');
                                    if (!self.block_data.link_props) self.block_data.link_props = {};
                                    self.$editable.attr('href', new_url);
                                    self.block_data.link_props['href'] = new_url;
                                    //self.$editable.attr("data-value", active_data.value);
                                    self.block_data.link_props['data-value'] = active_data.value;

                                    if (self.selection_attr.inputCheckbox) {
                                        self.$editable.attr('target', '_blank');
                                        self.block_data.link_props['target'] = '_blank';
                                    }
                                    else {
                                        self.$editable.removeAttr("target")
                                        delete self.block_data.link_props['target']
                                    }
                                    if (self.selection_attr.inputCheckboxNoFollow) {
                                        self.$editable.attr('rel', 'nofollow');
                                        self.block_data.link_props['rel'] = 'nofollow';
                                    }
                                    else {
                                        self.$editable.removeAttr("rel")
                                        delete self.block_data.link_props['rel']
                                    }
                                    if (active_block_attr) {
                                        //self.$editable.attr("data-block", active_block_attr);
                                        self.block_data.link_props['data-block'] = active_block_attr;
                                    }
                                    else {
                                        //self.$editable.removeAttr("data-block");
                                        delete self.block_data.link_props['data-block'];
                                    }
                                    //self.cleanForm();
                                    self.show_buttons = false;
                                    $.wa.editor._block_settings_drawer_promise.then(function(bs) {
                                        //self.block_data.html = self.$editable.html();
                                        bs.saveBlockData(self.block_data, false);

                                    });
                                    console.log('saveBlockData', self.block_data)
                                    self.isEmptyData = false;
                                }

                            },
                            deleteLink: function() {
                                let self = this;
                                self.$editable.attr('href', '');
                                if (self.block_data.link_props) delete self.block_data.link_props;
                                $.wa.editor._block_settings_drawer_promise.then(function(bs) {
                                    bs.saveBlockData(self.block_data, false);
                                });
                                self.show_buttons = false;
                                //self.$emit('updateLink', 'unlink');
                                //self.$emit('closeDrawer');
                                self.showChildren = false;
                                self.cleanForm()
                            },
                            cleanForm: function() {
                                let self = this;
                                self.show_buttons = false;
                                self.selection_attr = {
                                    active_option: 'internal-link',
                                    active_block_option: null,
                                    active_pages_option: null,
                                    inputEmail: '',
                                    inputSubject: '',
                                    inputPhone: '',
                                    inputCheckbox: false,
                                    inputCheckboxNoFollow: false,
                                };
                                self.isEmptyData = true;
                            }
                        }
                    },
                    "CommonLinkGroup": {
                        props: {
                            group_config: { type: Object },
                            block_data: { type: Object, default: {} },
                            block_id: { type: Number},
                        },
                        //emits: ['closeDrawer', 'updateLink'],
                        delimiters: ['{ { ', ' } }'],
                        data() {
                            const arr_options = that.storage_data.link_dropdown_data;

                            const arr_block_options = that.storage_data.link_block_data;
                            const arr_pages_options = that.storage_data.link_page_data;
                            const form_type = 'custom';
                            const header_name = this.group_config.name;
                            //let showSwitch = false;
                            let showChildren = false;
                            const group_header = this.group_config.name;
                            const selected_element = that.states.selected_element;

                            let selection_attr = {
                                active_option: 'internal-link',
                                active_block_option: null,
                                active_pages_option: null,
                                inputEmail: '',
                                inputSubject: '',
                                inputPhone: '',
                                inputCheckbox: false,
                                inputCheckboxNoFollow: false,
                            };

                            const show_buttons = false;
                            const active_data = arr_options.filter( function(option) {
                                return (option.value === 'internal-link');
                            })[0];
                            //
                            return { header_name, selected_element, showChildren, show_buttons, selection_attr, active_data, group_header, arr_options, arr_block_options, arr_pages_options, form_type}
                          },

                        template: that.templates["component_common_link_group"],
                        components: {
                            'LinkActionDropdown': that.vue_components['component-dropdown'],
                            'CustomButton': that.vue_components['custom-button'],
                            'SwitchToggle': that.vue_components['component-switch'],
                        },
                        created: function() {
                            let self = this;
                            //self.$editable = that.$target_wrapper.find('.style-wrapper');
                            //self.$editable = self.$editable.length ? self.$editable : that.$target_wrapper;
                            let link_props = Object.assign({}, this.block_data?.link_props || {});
                            if (self.selected_element) {
                                link_props = Object.assign({}, this.block_data?.link_props?.[self.selected_element] || {});
                            };
                            self.isEmptyData = true;
                            if (!jQuery.isEmptyObject(link_props)) {
                                self.isEmptyData = false;
                                let value = link_props['data-value'];
                                self.showChildren = true;
                                self.selection_attr.active_option = value;
                                self.active_data = self.arr_options.filter( function(option) {
                                    return (option.value === value);
                                })[0];
                                if (value === 'external-link') {
                                    self.selection_attr.inputEmail = link_props["href"];
                                }
                                if (value === 'email-link' ) {
                                    const temp = link_props["href"].split('mailto:')[1];
                                    self.selection_attr.inputEmail = temp.split('?subject=')[0];
                                    self.selection_attr.inputSubject = temp.split('?subject=')[1];
                                }
                                if (value === 'phone-link' ) {
                                    self.selection_attr.inputPhone = link_props["href"].split('tel:')[1];
                                }
                                if (value === 'internal-link') {
                                    self.selection_attr.active_pages_option = link_props["data-block"] || null;
                                    if (self.selection_attr.active_pages_option === null) {
                                        self.selection_attr.inputEmail = link_props["href"];
                                    }
                                }
                                if (value === 'block-link' ) {
                                    self.selection_attr.active_block_option = link_props["data-block"] || null;
                                    if (self.selection_attr.active_block_option === null) {
                                        self.selection_attr.inputEmail = link_props["href"];
                                    }
                                }
                                if (link_props["target"] ) {
                                    self.selection_attr.inputCheckbox = true;
                                }
                                if (link_props["rel"] ) {
                                    self.selection_attr.inputCheckboxNoFollow = true;
                                }
                            }
                        },
                        mounted: function() {
                            let self = this;
                            self.mountTooltips()
                        },
                        methods: {
                            change: function(option) {
                                let self = this;
                                self.active_data = self.arr_options.filter( function(opt) {
                                    return (opt.value === option.value);
                                })[0];
                                self.show_buttons = true;
                                setTimeout(()=> self.mountTooltips(), 0);
                            },
                            toggleChildren() {
                                var self = this;
                                $(self.$el).find(".s-editor-options-body").slideToggle(0);
                                if (self.showChildren) {
                                    self.deleteLink();
                                }
                                self.showChildren = !self.showChildren;
                            },
                            changeAnchor() {
                                let self = this;
                                self.show_buttons = true;
                                let temp_input = self.selection_attr.inputEmail;
                                if (temp_input !== '#' && temp_input !== '') {
                                    let hash_pos = temp_input.indexOf('#');
                                    if (hash_pos >= 0) {
                                        self.selection_attr.inputEmail = '#' + temp_input.split('#')[1];
                                    } else {
                                        self.selection_attr.inputEmail = '#' + temp_input;
                                    }
                                }
                            },
                            changePagesDropdown: function(option) {
                                let self = this;
                                self.selection_attr.active_pages_option = option.value;
                                self.show_buttons = true;
                            },
                            changeBlockDropdown: function(option) {
                                let self = this;
                                self.selection_attr.active_block_option = option.value;
                                self.show_buttons = true;
                            },
                            mountTooltips: function() {
                                let self = this;
                                $(self.$el).find("#tooltip-internal-link")?.waTooltip();
                                $(self.$el).find("#tooltip-block-link")?.waTooltip();
                            },
                            saveLink: function() {
                                let self = this;
                                let new_url = self.selection_attr.inputEmail;
                                let active_data = self.active_data;
                                let active_block_attr = '';

                                if (active_data.value === 'email-link' ) {
                                    new_url = 'mailto:' + new_url;
                                    if (self.selection_attr.inputSubject) {
                                        new_url = new_url + '?subject=' + self.selection_attr.inputSubject
                                    }
                                }
                                if (active_data.value === 'phone-link' ) {
                                    new_url = 'tel:' + self.selection_attr.inputPhone;
                                }
                                if (active_data.value === 'internal-link' ) {
                                    active_block_attr = self.selection_attr.active_pages_option;
                                    if (active_block_attr) {
                                        new_url = self.arr_pages_options.filter( function(option) {
                                            return (option.value === self.selection_attr.active_pages_option);
                                        })[0]['url'];
                                    }
                                }
                                if (active_data.value === 'block-link' ) {
                                    active_block_attr = self.selection_attr.active_block_option;
                                    if (active_block_attr) {
                                        new_url = self.arr_block_options.filter( function(option) {
                                            return (option.value === self.selection_attr.active_block_option);
                                        })[0]['url'];
                                    } else {
                                        new_url = new_url;
                                    }
                                }
                                if (new_url) {
                                    let temp_link_props = {};

                                    temp_link_props['href'] = new_url;
                                    temp_link_props['data-value'] = active_data.value;

                                    if (self.selection_attr.inputCheckbox) {
                                        temp_link_props['target'] = '_blank';
                                    }

                                    if (self.selection_attr.inputCheckboxNoFollow) {
                                        temp_link_props['rel'] = 'nofollow';
                                    }

                                    if (active_block_attr) {
                                        temp_link_props['data-block'] = active_block_attr;
                                    }

                                    self.show_buttons = false;

                                    if (!self.selected_element) {
                                        self.block_data.link_props = temp_link_props;
                                    } else {
                                        if (!self.block_data.link_props) self.block_data.link_props = {};
                                        self.block_data.link_props[self.selected_element] = temp_link_props;
                                    }

                                    $.wa.editor._block_settings_drawer_promise.then(function(bs) {
                                        bs.saveBlockData(self.block_data, false);

                                    });
                                    console.log('saveBlockData', self.block_data)
                                    self.isEmptyData = false;
                                }

                            },
                            deleteLink: function() {
                                let self = this;
                                //self.$editable.attr('href', '');
                                if (!self.selected_element) {
                                    if (self.block_data?.link_props) delete self.block_data.link_props;
                                } else {
                                    if (self.block_data?.link_props?.[self.selected_element]) delete self.block_data.link_props[self.selected_element];
                                }

                                $.wa.editor._block_settings_drawer_promise.then(function(bs) {
                                    bs.saveBlockData(self.block_data, false);
                                });
                                self.show_buttons = false;
                                //self.showChildren = false;
                                self.cleanForm()
                            },
                            cleanForm: function() {
                                let self = this;
                                self.show_buttons = false;
                                self.selection_attr = {
                                    active_option: 'internal-link',
                                    active_block_option: null,
                                    active_pages_option: null,
                                    inputEmail: '',
                                    inputSubject: '',
                                    inputPhone: '',
                                    inputCheckbox: false,
                                    inputCheckboxNoFollow: false,
                                };
                                self.isEmptyData = true;
                            }
                        }
                    },
                    "ProductLinkGroup": {
                        props: {
                            group_config: { type: Object },
                            block_data: { type: Object, default: {} },
                            block_id: { type: Number},
                        },
                        //emits: ['closeDrawer', 'updateLink'],
                        data() {
                            let self = this;
                            const active_options = Object.assign({}, this.block_data?.link_props || {});
                            self.storefront_object = active_options?.storefronts || {};
                            let storefront_options =  [
                                {name: 'storefront 1', value: 'internal-link'},
                                {name: 'storefront 2', value: 'external-link'},
                            ];
                            if (self.storefront_object) {
                                storefront_options = [];
                                $.each(Object.values(self.storefront_object), function(i, option) {
                                    storefront_options.push({
                                        name: option.url_decoded || option.url,
                                        value: option.url_decoded || option.url
                                    });
                                });
                            }
                            self.base_prod_url = this.block_data?.additional?.product?.url || '';
                            let active_href = active_options?.href || '';
                            let active_storefront = active_options?.storefront || '';
                            const form_type = 'custom';
                            let inputCheckboxNewPage = active_options?.target || false;
                            let inputCheckboxNoFollow = active_options?.rel || false;

                            const group_header = this.group_config.name;
                            const link_name = this.block_data.type_name;

                            return { link_name, active_storefront, group_header, storefront_options, form_type, active_href, inputCheckboxNewPage, inputCheckboxNoFollow}
                          },

                        template: that.templates["component_product_link_group"],
                        components: {
                            'LinkActionDropdown': that.vue_components['component-dropdown'],
                            'CustomButton': that.vue_components['custom-button'],
                        },
                        created: function() {
                            let self = this;
                            self.$editable = that.$target_wrapper.find('.style-wrapper');
                            self.$editable = self.$editable.length ? self.$editable : that.$target_wrapper;
                        },
                        mounted: function() {
                            let self = this;
                            self.mountTooltips()
                        },
                        methods: {
                            changeStorefront: function(option) {
                                let self = this;

                                self.active_storefront = option.value;
                                self.active_href = 'http://' + option.value + self.base_prod_url;
                                self.saveLink();
                            },
                            mountTooltips: function() {
                                let self = this;
                                //$(self.$el).find("#tooltip-internal-link")?.waTooltip();
                            },
                            saveLink: function() {
                                let self = this;
                                self.block_data.link_props = {'href': self.active_href, 'storefront': self.active_storefront, 'rel': self.inputCheckboxNoFollow, 'target': self.inputCheckboxNewPage, 'storefronts': self.storefront_object};
                                //console.log('saveLink', self.block_data.link_props, self.active_href)
                                $.wa.editor._block_settings_drawer_promise.then(function(bs) {
                                    bs.saveBlockData(self.block_data, false);
                                });
                                console.log('saveBlockData', self.block_data)
                            }
                        }
                    },
                    "TextColorGroup": {
                        props: {
                            group_config: { type: Object },
                            block_data: { type: Object, default: {} },
                            block_id: { type: Number},
                        },
                        data() {
                            const { ref } = Vue;
                            const form_type = that.storage_data[this.group_config.type].type;
                            const arr_options = that.storage_data[this.group_config.type];
                            let active_option = this.block_data?.inline_props?.[form_type];
                            const semi_header = this.group_config.name;
                            const active_icon = that.storage_data[this.group_config.type].icon;
                            const tagsArr = ['B', 'I', 'U', 'STRIKE', 'A', 'FONT', 'SPAN']
                            return { arr_options, semi_header, active_option, form_type, active_icon, tagsArr}
                          },
                        template: that.templates["component_text_color_group"],
                        delimiters: ['{ { ', ' } }'],
                        components: {
                            'TextColorDropdown': that.vue_custom_components['component-text-color-dropdown']
                        },
                        methods: {
                            change: function(option) { //put classes to remove in temp_active_option
                                let self = this;
                                $editable = that.$target_wrapper.find('.style-wrapper');
                                $editable = $editable.length ? $editable : that.$target_wrapper;
                                let selected_text_length = (that.$iframe_wrapper[0].getSelection().anchorOffset - that.$iframe_wrapper[0].getSelection().extentOffset);

                               if (selected_text_length === 0) {
                                    let rng = that.$iframe_wrapper[0].createRange()
                                    let sel = that.$iframe_wrapper[0].getSelection()
                                    rng.selectNodeContents($editable[0])
                                    sel.removeAllRanges()
                                    sel.addRange(rng)
                                    $(self.getSelectionBoundaryElement(true)).find('font').each(function(){$(this).removeAttr("class")});
                                    that.$iframe_wrapper[0].execCommand('foreColor', false, '#' + option);
                                }
                                else {
                                    $(self.getSelectionBoundaryElement(true)).find('font').each(function(){$(this).removeAttr("class")});
                                    that.$iframe_wrapper[0].execCommand('foreColor', false, '#' + option);
                                    $(self.getSelectionBoundaryElement(false)).removeAttr("class");
                                }
                                self.block_data.html = $editable.html();
                            },
                            changePalette: function(option) {
                                let self = this;
                                $editable = that.$target_wrapper.find('.style-wrapper');
                                $editable = $editable.length ? $editable : that.$target_wrapper;
                                let selected_text_length = (that.$iframe_wrapper[0].getSelection().anchorOffset - that.$iframe_wrapper[0].getSelection().extentOffset);

                               if (selected_text_length === 0) {
                                    let rng = that.$iframe_wrapper[0].createRange()
                                    let sel = that.$iframe_wrapper[0].getSelection()
                                    rng.selectNodeContents($editable[0])
                                    sel.removeAllRanges()
                                    sel.addRange(rng)
                                    //that.$iframe_wrapper[0].execCommand("SelectAll");
                               }
                                    $(self.getSelectionBoundaryElement(true)).find('font').each(function(){$(this).removeAttr("class")});
                                    that.$iframe_wrapper[0].execCommand('foreColor', false, '#010203');
                                    that.$iframe_wrapper[0].getSelection().focusNode.parentNode.color = '';
                                    that.$iframe_wrapper[0].getSelection().focusNode.parentNode.classList = [option.value];

                                self.block_data.html = $editable.html();
                                $.wa.editor._block_settings_drawer_promise.then(function(bs) {
                                    bs.saveBlockData(self.block_data);
                                });
                                console.log('saveBlockData', self.block_data)

                                //self.active_option = { type: option.type, value: option.value, name: option.name};

                            }
                        },
                        mounted: function() {
                            let self = this;
                            let $editable = that.$target_wrapper.find('.style-wrapper');
                            $editable = $editable.length ? $editable : that.$target_wrapper;
                            $editable.on('mouseup', (e) => checkTag($(e.target))) // To highlight the buttons on mouseup

                            function checkTag(targetEl){
                                //let targetEl = $(el.target);
                                let tagN = targetEl.prop("tagName");

                                while (self.tagsArr.indexOf(tagN) >= 0 ) {
                                    if (tagN === 'FONT') {
                                        let target_color = targetEl[0].attributes.color?.value || false;
                                        let target_class = targetEl[0].attributes.class?.value || false;
                                        if (target_color) {
                                            self.active_option = { type: 'self_color', value: target_color, name: 'Self color'};
                                        }
                                        if (target_class) {
                                            self.active_option = { type: 'palette', value: target_class, name: 'Palette'};
                                        }
                                        //break;
                                        return;
                                    }
                                    if (tagN === 'SPAN') {
                                        let target_class = targetEl[0].attributes.class?.value || false;
                                        if (target_class) {
                                            self.active_option = { type: 'palette', value: target_class, name: 'Palette'};
                                        }
                                        return;
                                    }
                                    targetEl = targetEl.parent();
                                    tagN = targetEl.prop("tagName");
                               }
                               self.active_option =  self.block_data?.inline_props?.[self.form_type];
                               //console.log('checktag', self.active_option)
                            }

                            self.getSelectionBoundaryElement = function(isStart) {
                                let range, sel, container;
                                    sel = that.$iframe_wrapper[0].getSelection();
                                    if (sel.rangeCount > 0) {
                                        range = sel.getRangeAt(0);
                                    }

                                    if (range) {
                                       container = range["startContainer"];
                                       // Check if the container is a text node and return its parent if so
                                       return isStart ?  container : container.parentNode;
                                    }
                            }

                            checkTag($(self.getSelectionBoundaryElement(false)));

                        },
                    },
                    "BackgroundColorGroup": {
                        props: {
                            group_config: { type: Object },
                            block_data: { type: Object, default: {} },
                            block_id: { type: Number},
                        },
                        data() {
                            const self = this;
                            const form_type = that.storage_data[this.group_config.type].type;
                            const arr_options = that.storage_data[this.group_config.type];
                            self.default_prop = that.storage_data[this.group_config.type].default_prop;
                            const active_icon = that.storage_data[this.group_config.type].icon;
                            const header_name = this.group_config.name;
                            const data_type_array = ['inline_props', 'block_props']
                            let showChildren = false;
                            let active_options = null;
                            let layers = [];

                            self.last_uuid = 1;
                            self.element = that.states.selected_element;

                            $.each(data_type_array, function(i, d) {
                                let temp_options = self.block_data?.[d]?.[form_type] || self.block_data?.[d]?.[self.element]?.[form_type] || null;
                                if (temp_options) {
                                    active_options = temp_options;
                                    if (active_options.layers) {
                                        layers = Object.values(active_options.layers)
                                        self.last_uuid = active_options.uuid || self.last_uuid;
                                    }
                                }
                            })
                            self.$editable = that.$target_wrapper.find('.style-wrapper');
                            if (self.element) self.$editable = that.$target_wrapper.find('.' + self.element);
                            self.$editable = self.$editable.length ? self.$editable : that.$target_wrapper;

                            return { layers, header_name, arr_options, active_options, active_icon, form_type, showChildren}
                          },
                        template: that.templates["component_background_color_group"],
                        delimiters: ['{ { ', ' } }'],
                        components: {
                            'BackgroundColorDropdown': that.vue_custom_components['component-color-dropdown'],
                        },
                        methods: {
                            updateSort: function (event) { //update layer array after hand sorting
                                var self = this;
                                if (event.oldIndex === event.newIndex) return;
                                const oldLayer = self.layers[event.oldIndex]
                                const newLayer = self.layers[event.newIndex]
                                self.layers[event.newIndex] = oldLayer;
                                self.layers[event.oldIndex] = newLayer;
                                self.changeCss();
                                //console.log(oldLayer, newLayer, oldUid, newUid)
                            },
                            toggleChildren() {
                                var self = this;
                                self.addLayer();
                            },
                            removeLayer(index) {
                                const self = this;
                                self.layers.splice(index, 1);
                                self.changeCss();
                                //self.change();
                            },
                            addLayer(index) {
                                const self = this;
                                if (self.layers?.[0]) { //copy first layer if we have layers
                                    const new_layer = Object.assign({}, self.layers[0]);
                                    new_layer.uuid = ++self.last_uuid; //add uniq layer index
                                    self.layers.push(new_layer);
                                }
                                else { //create default layer
                                    const new_layer = Object.assign({}, self.default_prop);
                                    new_layer.uuid = self.last_uuid; //add uniq layer index
                                    self.layers.push(new_layer)
                                }
                                self.changeCss();
                                //self.change();
                            },
                            changeCss(layer, index) {
                                let self = this;
                                self.backgroundCss = '';
                                console.log('changeCss',layer, index)
                                if (!self.layers.length) { //clear block data if we dont have layers
                                    self.removeColor();
                                    return;
                                }
                                if (layer) { //update layer
                                    layer.uuid = self.layers[index].uuid;
                                    self.layers[index] = layer;
                                };

                                if (self.layers[0].type === 'palette') { //set palette settings
                                    $.each(self.layers, function(i, l) { //update disable styles
                                        if (i > 0 ) l.disabled = 1
                                        else if (l.disabled) delete l.disabled
                                    });
                                    self.changePalette(self.layers[0]);
                                    return;
                                }
                                 else { //set manually settings
                                    const manual_layers = self.layers.filter(function(option) { return option.type !== 'palette' && option.type !== 'video'})
                                    $.each(manual_layers, function(i, l) {
                                        //if (l.type !== 'video') { //
                                            self.backgroundCss = self.backgroundCss + l.value + (manual_layers.length-1 > i ? ', ' : '');
                                        //} else {
                                            //self.changeVideo(l);
                                        //}
                                    });
                                    $.each(self.layers, function(i, l) { //update disable styles
                                        if (l.type === 'palette') l.disabled = 1
                                        else if (l.disabled) delete l.disabled
                                    });
                                    self.change();
                                    return;
                                }
                            },
                            change() {
                                let self = this;
                                if (self.active_options?.type === 'palette') {
                                    if (!self.element) {
                                        if (self.block_data.block_props) delete self.block_data.block_props[self.form_type];
                                    } else {
                                        if (self.block_data.block_props && self.block_data.block_props[self.element]) delete self.block_data.block_props[self.element][self.form_type];
                                    }
                                }
                                const temp_active_option = { type: 'self_color', value: self.backgroundCss, name: 'Self color', layers: self.layers};

                                self.active_options = temp_active_option;
                                self.active_options.uuid = self.last_uuid; //safe max uuid
                                //self.$editable.css('background-blend-mode', 'color'); //to do

                                $.wa.editor._block_settings_drawer_promise.then(function(bs) {
                                    if (!self.element) {
                                        if (!self.block_data.inline_props) self.block_data.inline_props = {};
                                        self.block_data.inline_props[self.form_type] = temp_active_option;
                                    } else {
                                        if (!self.block_data.inline_props) self.block_data.inline_props = {};
                                        if (!self.block_data.inline_props[self.element]) self.block_data.inline_props[self.element] = {};
                                        self.block_data.inline_props[self.element][self.form_type] = temp_active_option;
                                    }
                                    bs.saveBlockData(self.block_data);
                                });
                                console.log('saveBlockData', self.block_data)

                            },
                            changeVideo(layer){
                                if (!self.element) {
                                    if (!self.block_data.inline_props) self.block_data.inline_props = {};
                                    self.block_data.inline_props['background-video'] = layer.value;
                                } else {
                                    if (!self.block_data.inline_props) self.block_data.inline_props = {};
                                    if (!self.block_data.inline_props[self.element]) self.block_data.inline_props[self.element] = {};
                                    self.block_data.inline_props[self.element]['background-video'] = layer.value;
                                }
                            },
                            removeColor() {
                                let self = this;
                                if (self.active_options?.type === 'palette') {
                                    $.wa.editor._block_settings_drawer_promise.then(function(bs) {
                                        if (!self.element) {
                                            if (self.block_data.block_props) delete self.block_data.block_props[self.form_type];
                                        } else {
                                            if (self.block_data.block_props && self.block_data.block_props[self.element]) delete self.block_data.block_props[self.element][self.form_type];
                                        }
                                        bs.saveBlockData(self.block_data);
                                    });
                                } else {
                                    $.wa.editor._block_settings_drawer_promise.then(function(bs) {
                                        if (!self.element) {
                                            if (self.block_data.inline_props) delete self.block_data.inline_props[self.form_type];
                                        } else {
                                            if (self.block_data.inline_props && self.block_data.inline_props[self.element]) delete self.block_data.inline_props[self.element][self.form_type];
                                        }
                                        bs.saveBlockData(self.block_data);
                                    });
                                }
                                console.log('saveBlockData', self.block_data)

                                self.active_options = null;
                            },
                            changePalette(option) {
                                let self = this;
                                if (self.active_options?.type === 'self_color') {
                                    if (!self.element) {
                                        if (self.block_data.inline_props) delete self.block_data.inline_props[self.form_type];
                                    } else {
                                        if (self.block_data.inline_props && self.block_data.inline_props[self.element]) delete self.block_data.inline_props[self.element][self.form_type];
                                    }
                                }
                                if (self.active_options?.type === 'palette') {}
                                const temp_active_option = Object.assign({}, option);
                                temp_active_option.layers = self.layers;
                                //console.log('changePalette', temp_active_option)
                                self.active_options = temp_active_option;
                                self.active_options.uuid = self.last_uuid; //safe max uuid
                               // self.active_toggle_option = temp_active_option.type;
                                $.wa.editor._block_settings_drawer_promise.then(function(bs) {
                                    if (!self.element) {
                                        if (!self.block_data.block_props) self.block_data.block_props = {};
                                        self.block_data.block_props[self.form_type] = temp_active_option;
                                    } else {
                                        if (!self.block_data.block_props) self.block_data.block_props = {};
                                        if (!self.block_data.block_props[self.element]) self.block_data.block_props[self.element] = {};
                                        self.block_data.block_props[self.element][self.form_type] = temp_active_option;
                                    }
                                    bs.saveBlockData(self.block_data);
                                });
                                console.log('saveBlockData', self.block_data)
                            },

                            /*changeImage(option) {
                                const self = this;
                                const $dropdown = $(self.$el);
                                const $dropdown_toggle = $dropdown.find('.dropdown-toggle');

                                if (self.active_option.type === 'palette') {
                                    self.$editable.removeClass(self.active_option.value);
                                    if (!self.element) {
                                        if (self.block_data.block_props) delete self.block_data.block_props[self.form_type];
                                    } else {
                                        if (self.block_data.block_props && self.block_data.block_props[self.element]) delete self.block_data.block_props[self.element][self.form_type];
                                    }
                                }
                                const temp_active_option = option;
                                self.$editable.css(self.form_type, temp_active_option.value);
                                $dropdown_toggle.find('.s-icon svg').css('color', temp_active_option.css);
                                $dropdown_toggle.find('.s-name').html(temp_active_option.file_name);
                                self.active_option = temp_active_option;
                                $.wa.editor._block_settings_drawer_promise.then(function(bs) {
                                    if (!self.element) {
                                        if (!self.block_data.inline_props) self.block_data.inline_props = {};
                                        self.block_data.inline_props[self.form_type] = temp_active_option;
                                    } else {
                                        if (!self.block_data.inline_props) self.block_data.inline_props = {};
                                        if (!self.block_data.inline_props[self.element]) self.block_data.inline_props[self.element] = {};
                                        self.block_data.inline_props[self.element][self.form_type] = temp_active_option;
                                    }
                                    bs.saveBlockData(self.block_data);
                                });
                                console.log('saveBlockData', self.block_data)

                            },*/
                        },
                        mounted: function() {
                            let self = this;
                            self.$wrapper = $(self.$el);
                            var context = self.$wrapper.find('.s-editor-option-body');
                            context.sortable({
                                distance: 5,
                                helper: 'clone',
                                items: '.js-sort-toggle',
                                opacity: 0.75,
                                handle: '.sort',
                                tolerance: 'pointer',
                                containment: context,
                                onUpdate: self.updateSort
                            });
                        }
                    },
                    "ShadowsGroup": {
                        props: {
                            group_config: { type: Object },
                            block_data: { type: Object, default: {} },
                            block_id: { type: Number},
                        },
                        data() {
                            const self = this;
                            const shadow_type = this.group_config.shadow_type || '';
                            const group_config_type = shadow_type + this.group_config.type;
                            const form_type = that.storage_data[group_config_type].type;
                            const arr_options = that.storage_data[group_config_type];
                            const active_icon = that.storage_data[group_config_type].icon;
                            const header_name = this.group_config.name;
                            const data_type_array = ['inline_props', 'block_props'];
                            let showChildren = false;
                            let active_options = null;
                            let layers = [];

                            self.last_uuid = 1;
                            self.element = that.states.selected_element;
                            $.each(data_type_array, function(i, d) {
                                let temp_options = self.block_data?.[d]?.[form_type] || self.block_data?.[d]?.[self.element]?.[form_type] || null;
                                if (temp_options) {
                                    active_options = $.extend(true, {}, temp_options);
                                    if (active_options.layers) {
                                        layers = Object.values(active_options.layers)
                                        self.last_uuid = active_options.uuid || self.last_uuid;
                                    }
                                }
                            });

                            //const target_wrapper = iframe_wrapper.find('.seq-child > [data-block-id=' + self.block_id + ']');
                            self.$editable = that.$target_wrapper.find('.style-wrapper');
                            if (self.element) self.$editable = that.$target_wrapper.find('.' + self.element);
                            self.$editable = self.$editable.length ? self.$editable : that.$target_wrapper;

                            //console.log('shadow_group', active_options, layers)
                            return { layers, header_name, arr_options, active_options, form_type, active_icon, form_type, showChildren}
                          },
                        template: that.templates["component_shadows_group"],
                        delimiters: ['{ { ', ' } }'],
                        components: {
                            'ShadowsDropdown': that.vue_custom_components['component-shadow-dropdown'],
                        },
                        methods: {
                            updateSort: function (event) { //update layer array after hand sorting
                                var self = this;
                                if (event.oldIndex === event.newIndex) return;
                                const oldLayer = self.layers[event.oldIndex]
                                const newLayer = self.layers[event.newIndex]
                                self.layers[event.newIndex] = oldLayer;
                                self.layers[event.oldIndex] = newLayer;
                                self.changeCss();
                            },
                            toggleChildren() {
                                var self = this;
                                self.addLayer();
                            },
                            removeLayer(index) {
                                const self = this;
                                self.layers.splice(index, 1);
                                self.changeCss();
                                //self.change();
                            },
                            addLayer(index) {
                                const self = this;

                                if (self.layers?.[0]) { //copy first layer if we have layers
                                    const new_layer = Object.assign({}, self.layers[0]);
                                    new_layer.uuid = ++self.last_uuid; //add uniq layer index
                                    self.layers.push(new_layer);
                                }
                                else { //create default layer
                                    const new_layer = Object.assign({}, self.arr_options.palette[0]);
                                    new_layer.uuid = self.last_uuid; //add uniq layer index
                                    self.layers.push(new_layer)
                                }
                                self.changeCss();
                                //self.change();
                            },
                            changeCss(layer, index) {
                                let self = this;
                                self.shadowCss = '';
                                if (!self.layers.length) { //clear block data if we dont have layers
                                    self.removeColor();
                                    return;
                                }

                                if (layer) { //update layer
                                    layer.uuid = self.layers[index].uuid;
                                    self.layers[index] = layer;
                                };

                                if (self.layers[0].type === 'palette') { //set palette settings
                                    $.each(self.layers, function(i, l) { //update disable styles
                                        if (i > 0 ) l.disabled = 1
                                        else if (l.disabled) delete l.disabled
                                    });
                                    self.changePalette(self.layers[0]);
                                    return;
                                } else { //set manually settings
                                    const manual_layers = self.layers.filter(function(option) { return option.type === 'self_color'})
                                    $.each(manual_layers, function(i, l) {
                                        self.shadowCss = self.shadowCss + l.value + (manual_layers.length-1 > i ? ', ' : '');
                                    });
                                    $.each(self.layers, function(i, l) { //update disable styles
                                        if (l.type === 'palette') l.disabled = 1
                                        else if (l.disabled) delete l.disabled
                                    });
                                    self.change();
                                    return;
                                }
                            },
                            change() {
                                let self = this;
                                if (self.active_options?.type === 'palette') {
                                    if (!self.element) {
                                        if (self.block_data.block_props) delete self.block_data.block_props[self.form_type];
                                    } else {
                                        if (self.block_data.block_props && self.block_data.block_props[self.element]) delete self.block_data.block_props[self.element][self.form_type];
                                    }
                                }
                                let temp_active_option = { type: 'self_color', value: self.shadowCss, name: 'Manually', layers: self.layers};
                                self.active_options = temp_active_option;
                                self.active_options.uuid = self.last_uuid; //safe max uuid
                                //self.active_toggle_option = temp_active_option.type;
                                $.wa.editor._block_settings_drawer_promise.then(function(bs) {
                                    if (!self.element) {
                                        if (!self.block_data.inline_props) self.block_data.inline_props = {};
                                        self.block_data.inline_props[self.form_type] = temp_active_option;
                                    } else {
                                        if (!self.block_data.inline_props) self.block_data.inline_props = {};
                                        if (!self.block_data.inline_props[self.element]) self.block_data.inline_props[self.element] = {};
                                        self.block_data.inline_props[self.element][self.form_type] = temp_active_option;
                                    }
                                    bs.saveBlockData(self.block_data);
                                });
                                console.log('saveBlockData', self.block_data)
                            },
                            removeColor() {
                                let self = this;
                                if (self.active_options?.type === 'palette') {
                                    $.wa.editor._block_settings_drawer_promise.then(function(bs) {
                                        if (!self.element) {
                                            if (self.block_data.block_props) delete self.block_data.block_props[self.form_type];
                                        } else {
                                            if (self.block_data.block_props && self.block_data.block_props[self.element]) delete self.block_data.block_props[self.element][self.form_type];
                                        }
                                        bs.saveBlockData(self.block_data);
                                    });
                                } else {
                                    $.wa.editor._block_settings_drawer_promise.then(function(bs) {
                                        if (!self.element) {
                                            if (self.block_data.inline_props) delete self.block_data.inline_props[self.form_type];
                                        } else {
                                            if (self.block_data.inline_props && self.block_data.inline_props[self.element]) delete self.block_data.inline_props[self.element][self.form_type];
                                        }
                                        bs.saveBlockData(self.block_data);
                                    });
                                }
                                console.log('saveBlockData', self.block_data)

                                self.active_options = null;
                            },
                            changePalette(option) {
                                let self = this;
                                if (self.active_options?.type === 'self_color') {
                                    if (!self.element) {
                                        if (self.block_data.inline_props) delete self.block_data.inline_props[self.form_type];
                                    } else {
                                        if (self.block_data.inline_props && self.block_data.inline_props[self.element]) delete self.block_data.inline_props[self.element][self.form_type];
                                    }
                                }
                                if (self.active_options?.type === 'palette') {
                                }
                                const temp_active_option = Object.assign({}, option);
                                temp_active_option.layers = self.layers;
                                self.active_options = temp_active_option;
                                self.active_options.uuid = self.last_uuid; //safe max uuid
                               // self.active_toggle_option = temp_active_option.type;
                                $.wa.editor._block_settings_drawer_promise.then(function(bs) {
                                    if (!self.element) {
                                        if (!self.block_data.block_props) self.block_data.block_props = {};
                                        self.block_data.block_props[self.form_type] = temp_active_option;
                                    } else {
                                        if (!self.block_data.block_props) self.block_data.block_props = {};
                                        if (!self.block_data.block_props[self.element]) self.block_data.block_props[self.element] = {};
                                        self.block_data.block_props[self.element][self.form_type] = temp_active_option;
                                    }
                                    bs.saveBlockData(self.block_data);
                                });
                                console.log('saveBlockData', self.block_data)
                            },
                        },
                        mounted: function() {
                            let self = this;
                            self.$wrapper = $(self.$el);
                            var context = self.$wrapper.find('.s-editor-option-body');
                            context.sortable({
                                distance: 5,
                                helper: 'clone',
                                items: '.js-sort-toggle',
                                opacity: 0.75,
                                handle: '.sort',
                                tolerance: 'pointer',
                                containment: context,
                                onUpdate: self.updateSort
                            });
                        }
                    },
                    "LineHeightGroup": {
                        props: {
                            group_config: { type: Object },
                            block_data: { type: Object, default: {} },
                            block_id: { type: Number},
                        },
                        data() {
                            const form_type = that.storage_data[this.group_config.type].type;
                            const arr_options = that.storage_data[this.group_config.type].values;
                            const active_option = (this.block_data?.block_props||{})[form_type];
                            const semi_header = this.group_config.name;
                            const active_icon = that.storage_data[this.group_config.type].icon;
                            return { arr_options, semi_header, active_option, form_type, active_icon }
                          },
                        template: that.templates["component_line_height_group"],
                        delimiters: ['{ { ', ' } }'],
                        components: {
                            'LineHeightDropdown': that.vue_components['component-dropdown'],
                          },
                        methods: {
                        },
                    },
                    "AlignGroup": {
                        props: {
                            group_config: { type: Object },
                            block_data: { type: Object, default: {} },
                            block_id: { type: Number},
                        },
                        data() {
                            const form_type = that.storage_data[this.group_config.type].type;
                            const arr_options = that.storage_data[this.group_config.type].values;
                            const active_option = (this.block_data?.block_props||{})[form_type];
                            //const header_name = this.group_config.name;
                            const semi_header = this.group_config.name;
                            //const { ref, toRaw } = Vue;
                            return { arr_options, semi_header, active_option, form_type }
                          },
                        template: that.templates["component_align_group"],
                        delimiters: ['{ { ', ' } }'],
                        components: {
                            'AlignToggle': that.vue_components['component-toggle'],
                          },
                    },
                    "PaddingGroup": {
                        props: {
                            group_config: { type: Object },
                            block_data: { type: Object, default: {} },
                            block_id: { type: Number},
                            //element: { type: String }
                        },
                        data() {
                            const arr_options = that.storage_data[this.group_config.type]; //.slice(0,2)
                            const selected_element = that.states.selected_element;
                            //const form_type = ["padding-top", "padding-bottom"];
                            let active_options = (this.block_data?.block_props||{});
                            if (selected_element) {
                                active_options = this.block_data?.block_props?.[selected_element]
                            };
                            const semi_headers = that.states.semi_headers ? that.states.semi_headers : 'Inner';
                            const header_name = this.group_config.name;
                            const body_class = 'right';
                            const icons = that.storage_data.media_icons_data;
                            const media_prop = that.media_prop;
                            const media_icon = that.media_prop ? icons[that.media_prop] : 'fa-desktop';

                            return { media_prop, media_icon, selected_element, arr_options, header_name, semi_headers, body_class, active_options}
                          },
                        template: that.templates["component_padding_group"],
                        delimiters: ['{ { ', ' } }'],
                        components: {
                            'PaddingDropdown': that.vue_components['component-dropdown-removable'],
                          },
                        methods: {
                            toggleChildren() {
                                var self = this;
                                self.showChildren = !self.showChildren;
                                $(self.$el).find(".s-editor-options-body").slideToggle(350);
                            },
                        },
                    },
                    "MarginGroup": {
                        props: {
                            group_config: { type: Object },
                            block_data: { type: Object, default: {} },
                            block_id: { type: Number},
                        },
                        data() {
                            const arr_options = that.storage_data[this.group_config.type];
                            const selected_element = that.states.selected_element;
                            let active_options = (this.block_data?.block_props||{});
                            let showChildren = false;
                            if (selected_element) {
                                active_options = this.block_data?.block_props?.[selected_element];
                                if (active_options){
                                    $.each(arr_options, function(i, option) {
                                        if (active_options[option.type]) showChildren = true
                                    })
                                };
                            } else {
                                showChildren = true;
                            };
                            const icons = that.storage_data.media_icons_data;
                            const media_prop = that.media_prop;
                            const media_icon = that.media_prop ? icons[that.media_prop] : 'fa-desktop';
                            const semi_headers = that.states.semi_headers ? that.states.semi_headers : 'Inner';
                            const header_name = this.group_config.name;
                            return { media_prop, media_icon, selected_element, arr_options, header_name, showChildren, semi_headers, body_class: 'right', active_options}
                          },
                        template: that.templates["component_margin_group"],
                        delimiters: ['{ { ', ' } }'],
                        components: {
                            'MarginDropdown': that.vue_components['component-dropdown-removable'],
                          },
                        methods: {
                            toggleChildren() {
                                var self = this;
                                //TO_DO желательно провести оптимизацию чтобы отправлялся только один запрос на сервер, а не на каждый дропдаун
                                $.each(self.$refs.child, function(i, option) {
                                    option.toggleData(self.showChildren);
                                });
                                self.showChildren = !self.showChildren;
                                $(self.$el).find(".s-editor-options-body").slideToggle(0);
                            },
                        },
                    },
                    "HeightGroup": {
                        props: {
                            group_config: { type: Object },
                            block_data: { type: Object, default: {} },
                            block_id: { type: Number},
                        },
                        data() {
                            const form_type = that.storage_data[this.group_config.type].type;
                            const arr_options = that.storage_data[this.group_config.type].values;
                            const selected_element = that.states.selected_element;
                            self.active_option_object = this.block_data?.inline_props?.[form_type] || this.block_data?.inline_props?.[selected_element]?.[form_type] || null;
                            let active_option = self.active_option_object?.type || null;
                            let showChildren = active_option ? true : false;
                            const header_name = this.group_config.name;

                            let inputUnit = 'px';
                            let unit_options = that.storage_data.border_unit_data;
                            let inputSize = 300;
                            if (active_option === 'custom') {
                                inputUnit = self.active_option_object?.unit;
                                inputSize = self.active_option_object?.value.split(inputUnit)[0];
                            }
                            let active_option_dropdown = self.active_option_object?.value;
                            return { active_option_dropdown, form_type, inputUnit, inputSize, unit_options, selected_element, arr_options, header_name, showChildren, body_class: 'right', active_option}
                          },
                        template: that.templates["component_height_group"],
                        delimiters: ['{ { ', ' } }'],
                        components: {
                            'HeightDropdown': that.vue_components['component-dropdown'],
                            'WidthUnitDropdown': that.vue_components['component-dropdown'],
                          },
                        methods: {
                            change: function(option) {
                                const self = this;

                                let temp_active_option = self.arr_options.filter((obj) => obj.name === option.name)[0];
                                if (temp_active_option.type === 'custom') {
                                    temp_active_option.value = self.inputSize+self.inputUnit;
                                    temp_active_option.unit = self.inputUnit;
                                }
                                self.active_option = temp_active_option.type;
                                updateBlockData(self.form_type, 'inline_props', temp_active_option)
                            },
                            changeInput() {
                                let self = this;
                                self.change({name: 'Custom'});
                            },
                            changeUnit(option) {
                                let self = this;
                                self.inputUnit = option.value
                                self.change({name: 'Custom'});
                            },
                            setDefault() {
                                const self = this;
                                if (self.active_option) {
                                    self.active_option = null;
                                    self.active_option_dropdown = self.arr_options[0].value;
                                    self.active_option_object = null;
                                    self.inputUnit = self.arr_options[0].unit;
                                    self.inputSize = self.arr_options[0].value.split(self.inputUnit)[0];
                                    deleteBlockData(self.form_type, 'inline_props')

                                } else self.change({name: 'Content'});

                            },
                            toggleChildren() {
                                var self = this;
                                self.setDefault();
                                self.showChildren = !self.showChildren;
                                //$(self.$el).find(".s-editor-option-body").slideToggle(0);
                            },
                        },
                    },
                    "BorderRadiusGroup": {
                        props: {
                            group_config: { type: Object },
                            block_data: { type: Object, default: {} },
                            block_id: { type: Number},
                        },
                        data() {
                            const form_type = that.storage_data[this.group_config.type].type;
                            const arr_options = that.storage_data[this.group_config.type].values;
                            const selected_element = that.states.selected_element;
                            let showChildren = false;
                            let active_options = (this.block_data?.block_props||{});
                            if (selected_element) active_options = this.block_data?.block_props?.[selected_element];
                            if (active_options?.[form_type]) showChildren = true;
                            const header_name = this.group_config.name;
                            const semi_headers = that.states.semi_headers;
                            const corners_group_config = {type: 'BorderRadiusCornersGroup'};
                            return { corners_group_config, selected_element, arr_options, header_name, semi_headers, active_options, form_type, showChildren }
                        },
                        template: that.templates["component_border_radius_group"],
                        delimiters: ['{ { ', ' } }'],
                        components: {
                            'BorderRadiusDropdown': that.vue_components['component-dropdown'],
                            "BorderRadiusCornersGroup": {
                                props: {
                                    group_config: { type: Object },
                                    block_data: { type: Object, default: {} },
                                    block_id: { type: Number},
                                },
                                data() {
                                    const form_type = that.storage_data[this.group_config.type].type;
                                    const arr_options = that.storage_data[this.group_config.type];
                                    const toggle_options = that.storage_data.border_style_toggle_data;
                                    const selected_element = that.states.selected_element;
                                    this.active_options = arr_options.values[0];
                                    this.active_options = this.block_data?.block_props?.[form_type] || this.active_options;
                                    if (selected_element) this.active_options = this.block_data?.block_props?.[selected_element]?.[form_type] || this.active_options;
                                    let active_toggle_option = this.active_options.type;

                                    let active_options_array = this.active_options.value.split(' ');
                                    return { toggle_options, active_toggle_option, selected_element, arr_options, active_options_array, form_type}
                                },
                                template: that.templates["component_border_radius_corners_group"],
                                delimiters: ['{ { ', ' } }'],
                                components: {
                                    'ColorToggle': that.vue_components['component-toggle'],
                                },
                                methods: {
                                    change(option) {
                                        let self = this;
                                        let temp_active_option = self.active_options;
                                        let temp_array = self.active_options_array;
                                        if (option === 'all') {
                                            temp_active_option = self.arr_options.values[0]
                                            temp_array = [temp_active_option.value];
                                        } else {
                                            /*if (temp_array[0] === self.arr_options.values[0].value) {
                                                temp_array = [];
                                                $.each(self.arr_options.separate, function(i, opt) {
                                                    temp_array.push(opt.value)
                                                })
                                            }*/
                                            if (temp_array.includes(option.value)) {
                                                temp_array = temp_array.filter((e) => e !== option.value);
                                            } else {
                                                temp_array.push(option.value);
                                            }
                                            temp_active_option = {value: temp_array.join(' '), type: "separate"}
                                        }
                                        self.active_options_array = temp_array;
                                        self.active_options = temp_active_option;
                                        $.wa.editor._block_settings_drawer_promise.then(function(bs) {
                                            if (!self.selected_element) {
                                                self.block_data.block_props[self.form_type] = temp_active_option;
                                            } else {
                                            if (!self.block_data.block_props) self.block_data.block_props = {};
                                            if (!self.block_data.block_props[self.selected_element]) self.block_data.block_props[self.selected_element] = {};
                                            self.block_data.block_props[self.selected_element][self.form_type] = temp_active_option;
                                            }
                                            bs.saveBlockData(self.block_data);
                                        });
                                        console.log('saveBlockData', self.block_data)
                                    },
                                    changeToggle(option) {
                                        let self = this;
                                        self.active_toggle_option = option.value;
                                        self.active_options.type = option.value;
                                        if (option.value === 'all') {
                                            self.change('all');
                                        }
                                    },
                                    toggleData: function(option, def) {
                                        let self = this;
                                        if (option) self.changeToggle({value: 'all'}); //remove data from block
                                        //else self.change(def ? def: self.options[0]); //set default to block
                                    }
                                },
                            },
                        },
                        methods: {
                            toggleChildren() {
                                var self = this;
                                self.$refs.child.toggleData(self.showChildren, self.arr_options[3]);
                                self.$refs.childCorner.toggleData(self.showChildren, self.arr_options[3]);
                                self.showChildren = !self.showChildren;
                                $(self.$el).find(".s-editor-options-body").slideToggle(0);
                            },
                        },
                    },
                    "BorderGroup": {
                        props: {
                            group_config: { type: Object },
                            block_data: { type: Object, default: {} },
                            block_id: { type: Number},
                        },
                        data() {
                            const self = this;
                            let header_name = '';
                            //let showGroup = 'edited';
                            let showChildren = true;
                            self.selected_element = that.states.selected_element;
                            const config_array = [{type: 'BorderColorGroup'}, {type: 'BorderWidthGroup'}]
                            const data_type_array = ['inline_props', 'block_props']

                            if (self.selected_element || self.group_config.is_block) {
                                config_array.push({type: 'BorderStyleGroup'});
                                header_name = self.group_config.name;
                                showChildren = false;
                                //showGroup = 'empty';
                            }
                            $.each(config_array, function(i, config) {  // find and set active option from 'block_data'
                                let type = that.storage_data[config.type].type;
                                let activeOption = null;
                                let data_type = null;

                                $.each(data_type_array, function(i, d) {
                                    let temp_options = self.block_data?.[d]?.[type] || self.block_data?.[d]?.[self.selected_element]?.[type] || null;
                                    if (temp_options) {
                                        activeOption = temp_options;
                                        if (activeOption) {data_type = d}
                                    }
                                })

                                if (activeOption) {
                                    //showGroup = 'edited';
                                    showChildren = true;
                                    config_array[i]['active_option'] = activeOption;
                                    config_array[i]['active_type'] = data_type;
                                }

                            })
                            //let buttonData = that.storage_data.border_button_data[showGroup];
                            return { config_array, header_name, showChildren }
                          },
                        template: that.templates["component_border_group"],
                        delimiters: ['{ { ', ' } }'],
                        components: {
                            "BorderColorGroup": {
                                props: {
                                    group_config: { type: Object },
                                    block_data: { type: Object, default: {} },
                                    block_id: { type: Number},
                                },
                                data() {
                                    const self = this;
                                    const form_type = that.storage_data[this.group_config.type].type;
                                    const arr_options = that.storage_data[this.group_config.type];
                                    const active_icon = that.storage_data[this.group_config.type].icon;
                                    const selected_element = that.states.selected_element;

                                    self.$editable = that.$target_wrapper.find('.style-wrapper');
                                    if (selected_element) self.$editable = that.$target_wrapper.find('.' + selected_element);
                                    self.$editable = self.$editable.length ? self.$editable : that.$target_wrapper;

                                    return { selected_element, arr_options, form_type, active_icon, form_type, showChildren: false}
                                  },
                                template: `<background-color-dropdown @changeCss="changeCss" :options="arr_options" :activeOption="group_config.active_option" :element="selected_element" :activeIcon="active_icon" :form_type="form_type" :block_data="block_data" :block_id="block_id"></background-color-dropdown>`,
                                delimiters: ['{ { ', ' } }'],
                                components: {
                                    'BackgroundColorDropdown': that.vue_custom_components['component-color-dropdown'],
                                },
                                methods: {
                                    changeCss(layer, index) {
                                        let self = this;
                                        self.backgroundCss = '';
                                        if (layer.type === 'palette') { //set palette settings
                                            self.changePalette(layer);
                                            return;
                                        } else { //set manually settings
                                            self.change(layer);
                                            return;
                                        }
                                    },
                                    change(layer) {
                                        let self = this;
                                        if (self.group_config?.active_option?.type === 'palette') {
                                            if (!self.selected_element) {
                                                if (self.block_data.block_props) delete self.block_data.block_props[self.form_type];
                                            } else {
                                                if (self.block_data.block_props && self.block_data.block_props[self.selected_element]) delete self.block_data.block_props[self.selected_element][self.form_type];
                                            }
                                        }
                                        const temp_active_option = Object.assign({}, layer);
                                        temp_active_option.value = layer.css;
                                        self.group_config.active_option = temp_active_option;
                                        $.wa.editor._block_settings_drawer_promise.then(function(bs) {
                                            if (!self.selected_element) {
                                                if (!self.block_data.inline_props) self.block_data.inline_props = {};
                                                self.block_data.inline_props[self.form_type] = temp_active_option;
                                            } else {
                                                if (!self.block_data.inline_props) self.block_data.inline_props = {};
                                                if (!self.block_data.inline_props[self.selected_element]) self.block_data.inline_props[self.selected_element] = {};
                                                self.block_data.inline_props[self.selected_element][self.form_type] = temp_active_option;
                                            }
                                            bs.saveBlockData(self.block_data);
                                        });
                                        console.log('saveBlockData', self.block_data)
                                    },
                                    changePalette(option) {
                                        let self = this;
                                        if (self.group_config?.active_option?.type === 'self_color') {
                                            if (!self.selected_element) {
                                                if (self.block_data.inline_props) delete self.block_data.inline_props[self.form_type];
                                            } else {
                                                if (self.block_data.inline_props && self.block_data.inline_props[self.selected_element]) delete self.block_data.inline_props[self.selected_element][self.form_type];
                                            }
                                        }
                                        if (self.group_config?.active_option?.type === 'palette') {
                                        }
                                        const temp_active_option = Object.assign({}, option);
                                        self.group_config.active_option = temp_active_option;
                                        $.wa.editor._block_settings_drawer_promise.then(function(bs) {
                                            if (!self.selected_element) {
                                                if (!self.block_data.block_props) self.block_data.block_props = {};
                                                self.block_data.block_props[self.form_type] = temp_active_option;
                                            } else {
                                                if (!self.block_data.block_props) self.block_data.block_props = {};
                                                if (!self.block_data.block_props[self.selected_element]) self.block_data.block_props[self.element] = {};
                                                self.block_data.block_props[self.selected_element][self.form_type] = temp_active_option;
                                            }
                                            bs.saveBlockData(self.block_data);
                                        });
                                        console.log('saveBlockData', self.block_data)
                                    },
                                },
                            },
                            "BorderWidthGroup": {
                                props: {
                                    group_config: { type: Object },
                                    block_data: { type: Object, default: {} },
                                    block_id: { type: Number},
                                },
                                data() {
                                    const form_type = that.storage_data[this.group_config.type].type;
                                    const arr_options = that.storage_data[this.group_config.type];
                                    const active_icon = that.storage_data[this.group_config.type].icon;
                                    const selected_element = that.states.selected_element;
                                    //let active_options = (this.block_data.inline_props||{});
                                    //if (selected_element) active_options = active_options?.[selected_element] || null;

                                    return { selected_element, arr_options, form_type, active_icon, form_type, showChildren: false}

                                },
                                template: `<border-width-dropdown :options="arr_options" :element="selected_element" :activeOption="group_config.active_option" :activeIcon="active_icon" :form_type="form_type" :block_data="block_data" :block_id="block_id"></border-width-dropdown>`,
                                delimiters: ['{ { ', ' } }'],
                                components: {
                                    'BorderWidthDropdown': that.vue_custom_components['component-width-dropdown'],
                                },
                                methods: {
                                },
                            },
                            "BorderStyleGroup": {
                                props: {
                                    group_config: { type: Object },
                                    block_data: { type: Object, default: {} },
                                    block_id: { type: Number},
                                },
                                data() {
                                    const form_type = that.storage_data[this.group_config.type].type;
                                    const arr_options = that.storage_data[this.group_config.type];
                                    const toggle_options = that.storage_data.border_style_toggle_data;
                                    const selected_element = that.states.selected_element;
                                    this.active_options = arr_options.values[0];
                                    let active_toggle_option = this.active_options.type;

                                    if (this.group_config.active_option) {
                                        this.active_options = this.group_config.active_option;
                                        active_toggle_option = this.active_options.type;
                                    }

                                    let active_options_array = this.active_options.value.split(' ');
                                    return { toggle_options, active_toggle_option, selected_element, arr_options, active_options_array, form_type}
                                },
                                template: that.templates["component_border_style_group"],
                                delimiters: ['{ { ', ' } }'],
                                components: {
                                    'ColorToggle': that.vue_components['component-toggle'],
                                },
                                methods: {
                                    change(option) {
                                        let self = this;
                                        let temp_active_option = self.active_options;
                                        let temp_array = self.active_options_array;
                                        if (option === 'all') {
                                            temp_active_option = self.arr_options.values[0]
                                            temp_array = [temp_active_option.value];
                                        } else {
                                            if (temp_array[0] === self.arr_options.values[0].value) {
                                                temp_array = [];
                                                $.each(self.arr_options.separate, function(i, opt) {
                                                    temp_array.push(opt.value)
                                                })
                                            }
                                            if (temp_array.includes(option.value)) {
                                                temp_array = temp_array.filter((e) => e !== option.value);
                                            } else {
                                                temp_array.push(option.value);
                                            }
                                            temp_active_option = {value: temp_array.join(' '), type: "separate"}
                                        }
                                        self.active_options_array = temp_array;
                                        self.active_options = temp_active_option;
                                        $.wa.editor._block_settings_drawer_promise.then(function(bs) {
                                            if (!self.selected_element) {
                                                self.block_data.block_props[self.form_type] = temp_active_option;
                                            } else {
                                            if (!self.block_data.block_props) self.block_data.block_props = {};
                                            if (!self.block_data.block_props[self.selected_element]) self.block_data.block_props[self.selected_element] = {};
                                            self.block_data.block_props[self.selected_element][self.form_type] = temp_active_option;
                                            }
                                            bs.saveBlockData(self.block_data);
                                        });
                                        console.log('saveBlockData', self.block_data)
                                    },
                                    changeToggle(option) {
                                        let self = this;
                                        self.active_toggle_option = option.value;
                                        self.active_options.type = option.value;
                                        if (option.value === 'all') {
                                            self.change('all');
                                        }
                                    },
                                },
                            },
                            'CustomButton': that.vue_components['custom-button'],
                        },
                        methods: {
                            toggleChildren() {
                                var self = this;
                                self.showChildren = !self.showChildren;
                                $(self.$el).find(".s-editor-options-body").slideToggle(0);
                            },
                            toggleChildren() {
                                var self = this;
                                $(self.$el).find(".s-editor-options-body").slideToggle(0);
                                if (self.showChildren) { //remove all styles and props
                                    $.each(self.config_array, function(i, config) {
                                        const option = that.storage_data[config.type];
                                        if (!self.selected_element) {
                                            if (self.block_data[config.active_type]?.[option.type]) delete self.block_data[config.active_type][option.type];
                                        } else {
                                            if ( self.block_data[config.active_type]?.[self.selected_element]?.[option.type]) delete  self.block_data[config.active_type][self.selected_element][option.type];
                                        }
                                        delete self.config_array[i]['active_option'];
                                        delete self.config_array[i]['active_type'];
                                    })
                                    self.showChildren = !self.showChildren;

                                    $.wa.editor._block_settings_drawer_promise.then(function(bs) {
                                        bs.saveBlockData(self.block_data);
                                    });
                                    console.log('saveBlockData', self.block_data)
                                    return
                                }

                                //add default styles and props
                                $.each(self.config_array, function(i, config) {
                                    const option = that.storage_data[config.type];
                                    const data_type = option.default_data_type;
                                    if (!self.selected_element) {
                                        if (!self.block_data[data_type]) self.block_data[data_type] = {};
                                        self.block_data[data_type][option.type] = option.values[0];
                                    } else {
                                        if (!self.block_data[data_type]) self.block_data[data_type] = {};
                                        if (!self.block_data[data_type][self.selected_element]) self.block_data[data_type][self.selected_element] = {};
                                        self.block_data[data_type][self.selected_element][option.type] = option.values[0];
                                    }
                                    self.config_array[i]['active_option'] = option.values[0];
                                    self.config_array[i]['active_type'] = data_type;
                                })
                                self.showChildren = !self.showChildren;
                                $.wa.editor._block_settings_drawer_promise.then(function(bs) {
                                    bs.saveBlockData(self.block_data);
                                });
                                console.log('saveBlockData', self.block_data)
                            },
                        },
                    },
                    "VisibilityGroup": {
                        props: {
                            group_config: { type: Object },
                            block_data: { type: Object, default: {} },
                            block_id: { type: Number},
                        },
                        data() {
                            const self = this;
                            const form_type = that.storage_data[this.group_config.type].type;
                            let arr_options = that.storage_data[this.group_config.type].values;
                            self.element = that.states.selected_element;
                            let active_options = this.block_data?.block_props?.[form_type] || this.block_data?.block_props?.[self.element]?.[form_type] || null;
                            let showChildren = false;
                            if (active_options) showChildren = true;
                            const header_name = this.group_config.name;

                            return { arr_options, active_options, header_name, form_type, showChildren }
                        },
                        template: that.templates["component_visibility_group"],
                        delimiters: ['{ { ', ' } }'],
                        components: {
                            /*'PaddingDropdown': that.vue_components['component-dropdown'],*/
                          },
                        computed: {
                            formatted_options: function() {
                                const self = this;
                                const result = [];
                                const activeClassesArr = self.active_options?.split(' ') || [];

                                let disabled_check = 4;
                                $.each(self.arr_options, function(i, option) { // перебираем опции, добавляем флаг активного чекбокса
                                    const classPosition = activeClassesArr.indexOf(option.value);
                                    if ( classPosition >= 0 ) {
                                        option.activeOption = false;
                                        disabled_check--;
                                    } else {
                                        option.activeOption = true;
                                    }
                                    result.push(option);
                                });

                                $.each(result, function(i, option) {
                                    if (disabled_check <= 1) { // проверяем если остался последний активный чекбокс
                                        if (option.activeOption === true) option.disabled = true;
                                    } else {
                                        if (option.disabled === true) option.disabled = false;
                                    }
                                });

                                return result;
                            }
                        },
                        methods: {
                            change: function(option) {
                                const self = this;
                                if (option.disabled) { return false; }
                                let activeClassesArr = self.active_options?.split(' ') || [];
                                const classPosition = activeClassesArr.indexOf(option.value);
                                if ( classPosition >= 0 ) { // В данном компоненте инверсия, если галка снята, то класс добавляется и наоборот, если галка ставится, то класс убирается
                                    activeClassesArr.splice(classPosition, 1);
                                    $.each(self.formatted_options, function(i, opt) {
                                        if (opt.value === option.value) self.formatted_options[i].activeOption = false;
                                    });
                                } else {
                                    activeClassesArr.push(option.value);
                                    $.each(self.formatted_options, function(i, opt) {
                                        if (opt.value === option.value) self.formatted_options[i].activeOption = true;
                                    });
                                }
                                self.active_options = activeClassesArr.join(' ');

                                if (self.active_options) {
                                    if (!self.element) {
                                        if (!self.block_data.block_props) self.block_data.block_props = {};
                                        self.block_data.block_props[self.form_type] = activeClassesArr.join(' ');
                                    } else {
                                        if (!self.block_data.block_props) self.block_data.block_props = {};
                                        if (!self.block_data.block_props[self.element]) self.block_data.block_props[self.element] = {};
                                        self.block_data.block_props[self.element][self.form_type] = activeClassesArr.join(' ');
                                    }
                                } else {
                                    if (!self.element) {
                                        delete self.block_data.block_props[self.form_type]
                                    } else {
                                        delete self.block_data.block_props[self.element][self.form_type]
                                    }
                                }
                                $.wa.editor._block_settings_drawer_promise.then(function(bs) {
                                    bs.saveBlockData(self.block_data);
                                });
                                console.log('saveBlockData', self.block_data);
                            },
                            setDefault: function() {
                                const self = this;
                                if (!self.active_options?.length) return;
                                self.active_options = '';
                                $.wa.editor._block_settings_drawer_promise.then(function(bs) {
                                    delete self.block_data.block_props[self.form_type]
                                    bs.saveBlockData(self.block_data);
                                });
                                console.log('saveBlockData', self.block_data);
                            },
                            toggleChildren() {
                                var self = this;
                                if (self.showChildren) self.setDefault();
                                self.showChildren = !self.showChildren;
                                $(self.$el).find(".s-editor-option-body").slideToggle(0);
                            },
                        },
                    },
                    "ImageUploadGroup": {
                        props: {
                            group_config: { type: Object },
                            block_data: { type: Object, default: {} },
                            block_id: { type: Number},
                        },
                        data() {
                            const self = this;
                            const form_type = that.storage_data[this.group_config.type].type;
                            const toggle_options = that.storage_data.image_upload_toggle_data;
                            let image_data = this.block_data?.image ? this.block_data.image : '';
                            let active_toggle_option = image_data?.type ? image_data.type : 'upload';
                            self.colors_variables_data = that.storage_data['colors_variables_data'];
                            const svg_placeholder = '<svg fill="currentColor"></svg>';
                            let svg_html = image_data['svg_html'] || '';
                            let url_text = image_data['url_text'] || '';
                            const wa_url = $.site.backend_url;
                            self.$editable = that.$target_wrapper.eq(0).find('.style-wrapper picture');

                            return { wa_url, svg_html, url_text, form_type, image_data, active_toggle_option, toggle_options, svg_placeholder }
                        },
                        template: `
                        <div class="s-editor-option-wrapper image-group-wrapper">
                            <div class="s-editor-option-body custom-mt-8">
                                <image-data-toggle @changeToggle="changeToggle" :options="toggle_options" :activeOption="active_toggle_option" :with_text="true" form_type="custom"></image-data-toggle>
                            </div>
                            <div class="s-editor-option-body custom-mt-20" v-if="active_toggle_option === 'upload'">
                                <image-upload :block_data="block_data" :block_id="block_id"></image-upload>
                            </div>
                            <div class="s-editor-option-body custom-mt-8" v-if="active_toggle_option === 'svg'">
                                <div class="s-semi-header small custom-pb-10 custom-pt-20" v-html="
                                    $t('custom.Paste the code of your vector image or copy one from **Google Icons**, **FontAwesome**, or another similar library')
                                        .replace('**', '<a href=&quot;https://fonts.google.com/icons&quot; target=&quot;_blank&quot;>')
                                        .replace('**', '</a>')
                                        .replace('**', '<a href=&quot;https://fontawesome.com/&quot; target=&quot;_blank&quot;>')
                                        .replace('**', '</a>')
                                ">
                                </div>
                                <div class="s-semi-header text-gray small">{{$t('custom.Image SVG code')}}</div>
                                <div class="width-100 svg-image-wrapper">
                                    <textarea @input="changeSvg" v-model="svg_html" class="width-100" id="js-svg-image" :placeholder="svg_placeholder"></textarea>
                                    <div style="display: none;" class="state-error-hint" :data-error-tags="$t('custom.Available only iframe tags')" :data-error-open-tag="$t('custom.Need to add the closing tag iframe')"></div>
                                </div>
                                <div class="width-100">
                                    <svg-color-group @changeSvgColor="changeSvgColor" @changeSvgColorPalette="changeSvgColorPalette" class="width-100 custom-mb-8" :block_data="block_data" :block_id="block_id"></svg-color-group>
                                     <div class="text-gray smaller">{{$t('custom.The specified color will update fill attribute')}}</div>
                                </div>
                            </div>
                            <div class="s-editor-option-body custom-mt-8" v-if="active_toggle_option === 'address'">
                                <div class="s-semi-header small custom-pb-10 custom-pt-20" v-html="
                                    $t('custom.Paste the address of an image added in the **Site › Files** section or in another Webasyst app (eg, **Files**, **Photos**), or located on a third-party site **Help**')
                                        .replace('**', '<a href=&quot;' + wa_url + 'site/files/&quot; target=&quot;_blank&quot;>')
                                        .replace('**', '</a>')
                                        .replace('**', '<a href=&quot;' + wa_url + 'installer/store/app/files/&quot; target=&quot;_blank&quot;>')
                                        .replace('**', '</a>')
                                        .replace('**', '<a href=&quot;' + wa_url + 'installer/store/app/photos/&quot; target=&quot;_blank&quot;>')
                                        .replace('**', '</a>')
                                        .replace('**', '<a href=&quot;' + $t('custom.support_url_images') + '&quot; target=&quot;_blank&quot;>')
                                        .replace('**', '</a>')
                                    "
                                ></div>
                                <div class="s-semi-header text-gray small">{{$t('custom.URL of the image')}}</div>
                                <div class="width-100 svg-image-wrapper custom-mb-12">
                                    <textarea @input="changeAddress"  v-model="url_text" class="width-100" id="js-svg-image" :placeholder="$t('custom.url_address_placeholder')"></textarea>
                                    <div style="display: none;" class="state-error-hint" :data-error-tags="$t('custom.Available only iframe tags')" :data-error-open-tag="$t('custom.Need to add the closing tag iframe')"></div>
                                </div>
                            </div>
                            <div class="s-editor-option-body custom-mt-8">
                                <picture-size-group class="width-100" :block_data="block_data" :block_id="block_id"></picture-size-group>
                            </div>
                        </div>`,
                        computed: {
                        },
                        components: {
                            'ImageDataToggle': that.vue_components['component-toggle'],
                            'ImageUpload': {
                                props: {
                                    block_data: { type: Object, default: {} },
                                    block_id: { type: Number},
                                },
                                emits: ["change"],
                                data() {
                                    const form_type = 'image-upload';
                                    let image_data = this.block_data?.image?.type === 'upload' ? this.block_data.image.name : '';
                                    return { form_type, image_data }
                                },
                                template: that.templates["component_image_upload_group"],
                                delimiters: ['{ { ', ' } }'],
                                computed: {
                                },
                                methods: {
                                    change: function(option) {
                                        const self = this;
                                        that.is_new_block = false;
                                        if (!option.target.files?.length) return false;
                                        image_data = option.target.files[0].name
                                        self.temp_image_data = {type: 'upload', 'name': option.target.files[0].name} ;
                                        $.wa.editor._block_settings_drawer_promise.then(function(bs) {
                                            self.block_data.image = self.temp_image_data;
                                            bs.saveBlockData(self.block_data, true);
                                            bs.uploadFile(option.target.files[0], '');
                                        });
                                    },
                                    cancelFile: function(option) {
                                        const self = this;
                                        if (!option.target.value.length) {
                                            if (that.is_new_block) {
                                                that.is_new_block = false;
                                                self.removeBlock();
                                            }
                                            return false;
                                        }
                                    },
                                    drop: function(option) {
                                        const self = this;
                                        const files = $(self.$el).find("#drop-area").data('upload')?.files;
                                        that.is_new_block = false;
                                        //console.log('drop', option.disabled, !files?.length)
                                        if (option.disabled || !files?.length) { return false; }
                                        self.temp_image_data = {type: 'upload', 'name':  files[0].name} ;
                                        self.block_data.image = self.temp_image_data;
                                        $.wa.editor._block_settings_drawer_promise.then(function(bs) {
                                            bs.saveBlockData(self.block_data, true);
                                            bs.uploadFile(files[0], '')
                                        });
                                    },
                                    removeBlock: function(){
                                        const self = this;
                                        const $block_wrapper = that.$target_wrapper.closest('.js-seq-wrapper');
                                        if ($block_wrapper.length) {
                                            $.wa.editor.removeBlock(self.block_id, $block_wrapper);
                                            $.wa.editor._block_settings_drawer_promise.then(function(bs) {
                                                bs.hide();
                                                $.wa.editor.selected_block_id = '';
                                            });
                                        }
                                    },

                                },
                                mounted: function() {
                                    const self = this;
                                    $(self.$el).find("#drop-area").waUpload({
                                        is_uploadbox: true,
                                        show_file_name: true
                                    })

                                    /*if (that.is_new_block) { //Need for open the file menu when adding a block
                                        $(self.$el).find('label').trigger('click')
                                    }*/
                                }
                            },
                            "SvgColorGroup": {
                                props: {
                                    block_data: { type: Object, default: {} },
                                    block_id: { type: Number},
                                },
                                emits: ["changeSvgColor", 'changeSvgColorPalette'],
                                data() {
                                    const form_type = that.storage_data['SvgColorGroup'].type;
                                    const arr_options = that.storage_data['SvgColorGroup'];
                                    let active_option = (this.block_data?.image?.color && typeof this.block_data?.image?.color === 'string') ? { type: 'self_color', value: '#' + this.block_data?.image?.color, name: 'Self color'} : (this.block_data?.image?.color ? this.block_data?.image?.color : {});
                                    //let active_option = this.block_data?.image?.color ? { type: 'self_color', value: '#' + this.block_data?.image?.color, name: 'Self color'} : {};
                                    const semi_header = 'Color';
                                    const active_icon = that.storage_data['SvgColorGroup'].icon;
                                    return { arr_options, semi_header, active_option, form_type, active_icon}
                                  },
                                template: that.templates["component_text_color_group"],
                                delimiters: ['{ { ', ' } }'],
                                components: {
                                    'TextColorDropdown': that.vue_custom_components['component-text-color-dropdown']
                                },
                                methods: {
                                    change: function(option) {
                                        let self = this;
                                        let temp_active_option = { type: 'self_color', value: '#' + option, name: 'Self color'};
                                        self.$emit('changeSvgColor', temp_active_option);
                                        //self.active_option = temp_active_option;
                                    },
                                    changePalette: function(option) {
                                        let self = this;
                                        self.$emit('changeSvgColorPalette', option);
                                        //self.active_option = option;
                                    }
                                },
                                mounted: function() {
                                    let self = this;
                                },
                            },
                            "PictureSizeGroup": {
                                props: {
                                    group_config: { type: Object },
                                    block_data: { type: Object, default: {} },
                                    block_id: { type: Number},
                                },
                                data() {
                                    const form_type = that.storage_data['PictureSizeGroup'].type;
                                    const arr_options = that.storage_data['PictureSizeGroup'].values;
                                    const active_option = (this.block_data?.block_props||{})[form_type];
                                    const semi_header = 'Size';
                                    const active_icon = that.storage_data['PictureSizeGroup'].icon;
                                    return { arr_options, semi_header, active_option, form_type, active_icon }
                                  },
                                template: that.templates["component_line_height_group"],
                                delimiters: ['{ { ', ' } }'],
                                components: {
                                    'LineHeightDropdown': that.vue_components['component-dropdown'],
                                  },
                                methods: {
                                },
                            },
                          },
                        methods: {
                            change: function() {
                                const self = this;
                                $.wa.editor._block_settings_drawer_promise.then(function(bs) {
                                    self.block_data.image = self.temp_image_data;
                                    bs.saveBlockData(self.block_data);
                                });
                            },
                            changeSvg: function() {
                                const self = this;
                                self.temp_image_data = {type: 'svg', 'svg_html': self.svg_html}
                                self.change();
                                self.$editable.html($.wa.editor.sanitizeHTML(self.svg_html));
                            },
                            changeAddress: function() {
                                const self = this;
                                self.temp_image_data = {type: 'address', 'url_text': self.url_text}
                                self.change();
                                self.$editable.html('<img src="' + self.url_text + '" />')
                            },
                            changeToggle: function(option) {
                                const self = this;
                                self.active_toggle_option = option.value;
                            },

                            changeSvgColor: function(color) {
                                const self = this;

                                if (!self.svg_html.length) return;
                                if (!self.temp_image_data?.fill) self.svg_html = self.removeFillAttributes(self.svg_html);
                                const svg_node = $(self.svg_html);
                                svg_node.attr({'fill': color.value})
                                self.svg_html = svg_node[0].outerHTML;
                                self.temp_image_data = {type: 'svg', color: color, 'svg_html': self.svg_html, fill: 'removed'}
                                self.$editable.html(self.svg_html);
                                self.change();
                            },
                            changeSvgColorPalette: function(option) {
                                const self = this;
                                if (!self.svg_html.length) return;
                                if (!self.temp_image_data?.fill) self.svg_html = self.removeFillAttributes(self.svg_html);
                                const svg_node = $(self.svg_html);
                                svg_node.attr({'fill':  'var(' + self.colors_variables_data[option.value] + ')'})
                                self.svg_html = svg_node[0].outerHTML;
                                self.temp_image_data = {type: 'svg', color: option, 'svg_html': self.svg_html, fill: 'removed'}
                                self.$editable.html(self.svg_html);
                                self.change();
                            },
                            removeFillAttributes: function (svgString) {
                                const parser = new DOMParser();
                                const xmlDoc = parser.parseFromString(svgString, 'image/svg+xml');

                                // Рекурсивное удаление fill и очистка style
                                function removeFill(node) {
                                  if (node.nodeType === 1) { // Проверка, что это элемент
                                    // Удаление атрибута fill
                                    if (node.hasAttribute('fill')) {
                                      node.removeAttribute('fill');
                                    }

                                    // Очистка fill из style
                                    if (node.hasAttribute('style')) {
                                      const style = node.getAttribute('style');
                                      const cleanedStyle = style
                                        .split(';')
                                        .map(s => s.trim())
                                        .filter(s => !s.startsWith('fill:'))
                                        .join('; ');

                                      if (cleanedStyle) {
                                        node.setAttribute('style', cleanedStyle);
                                      } else {
                                        node.removeAttribute('style');
                                      }
                                    }
                                  }

                                  // Рекурсивно обрабатываем дочерние узлы
                                  node.childNodes.forEach(removeFill);
                                }

                                removeFill(xmlDoc.documentElement);

                                const serializer = new XMLSerializer();
                                return serializer.serializeToString(xmlDoc);
                                //return svgString.replace(/\s*fill="[^"]*"/g, '').replace(/\s*fill:*"/g, '');
                              }
                        },
                        mounted: function() {
                            const self = this;
                        }
                    },
                    "VideoUploadGroup": {
                        props: {
                            group_config: { type: Object },
                            block_data: { type: Object, default: {} },
                            block_id: { type: Number},
                        },
                        data() {
                            const self = this;
                            const form_type = 'video-upload';
                            const toggle_options = that.storage_data.video_upload_toggle_data;
                            let video_data = this.block_data?.video ? this.block_data.video : '';
                            let active_toggle_option = video_data?.type ? video_data.type : 'code';
                            self.$editable = that.$target_wrapper.eq(0).find('.style-wrapper picture');

                            return { form_type, video_data, active_toggle_option, toggle_options }
                        },
                        template: `
                        <div class="s-editor-option-wrapper video-group-wrapper">
                            <div class="s-editor-option-body custom-mt-8">
                                <video-data-toggle @changeToggle="changeToggle" :options="toggle_options" :activeOption="active_toggle_option" :with_text="true" form_type="custom"></video-data-toggle>
                            </div>
                            <div class="s-editor-option-body custom-mt-20" v-if="active_toggle_option === 'upload'">
                                <video-upload :block_data="block_data" :block_id="block_id"></video-upload>
                            </div>
                            <div class="s-editor-option-body custom-mt-8" v-if="active_toggle_option === 'code'">
                                <custom-video :block_data="block_data" :block_id="block_id"></custom-video>
                            </div>

                        </div>`,
                        computed: {
                        },
                        components: {
                            'VideoDataToggle': that.vue_components['component-toggle'],
                            'VideoUpload': {
                                props: {
                                    block_data: { type: Object, default: {} },
                                    block_id: { type: Number},
                                },
                                emits: ["change"],
                                data() {
                                    const form_type = 'video-upload';
                                    let video_data = this.block_data?.video?.type === 'upload' ? this.block_data.video : {};
                                    let autoPlay = video_data?.auto_play || false;
                                    let autoLoop = video_data?.auto_loop || false;
                                    let muted = video_data?.muted || true;
                                    let switch_disabled = video_data.name ? false : true;
                                    return { muted, autoPlay, autoLoop, form_type, video_data, switch_disabled }
                                },
                                template: that.templates["component_video_upload_group"],
                                delimiters: ['{ { ', ' } }'],
                                components: {
                                    'SwitchToggle': that.vue_components['component-switch'],
                                },
                                methods: {
                                    change: function(option) {
                                        const self = this;
                                        that.is_new_block = false;
                                        if (!option.target.files?.length) return false;
                                        self.video_data = {'type': 'upload', 'name': option.target.files[0].name, 'auto_loop': self.autoLoop, 'auto_play': self.autoPlay, 'muted': self.muted} ;
                                        $.wa.editor._block_settings_drawer_promise.then(function(bs) {
                                            self.block_data.video = self.video_data;
                                            bs.saveBlockData(self.block_data, true);
                                            bs.uploadFile(option.target.files[0], '');
                                            self.switch_disabled = false;
                                        });
                                    },
                                    changeSwitchPlay(option, str) {
                                        let self = this;
                                        if (!self.video_data.name) return false;
                                        self.switch_disabled = true;
                                        //console.log(self.autoPlay, option, str)
                                        //self.autoPlay = !self.autoPlay;

                                        self.video_data[str] = option;
                                        $.wa.editor._block_settings_drawer_promise.then(function(bs) {
                                            self.block_data.video = self.video_data;
                                            bs.saveBlockData(self.block_data, true);
                                            self.switch_disabled = false
                                        });
                                        console.log(self.block_data.video)
                                    },
                                    cancelFile: function(option) {
                                        const self = this;
                                        if (!option.target.value.length) {
                                            if (that.is_new_block) {
                                                that.is_new_block = false;
                                                self.removeBlock();
                                            }
                                            return false;
                                        }
                                    },
                                    drop: function(option) {
                                        const self = this;
                                        const files = $(self.$el).find("#drop-area").data('upload')?.files;
                                        that.is_new_block = false;
                                        //console.log('drop', option.disabled, !files?.length)
                                        if (option.disabled || !files?.length) { return false; }
                                        self.temp_video_data = {type: 'upload', 'name':  files[0].name} ;
                                        self.block_data.video = self.temp_video_data;
                                        $.wa.editor._block_settings_drawer_promise.then(function(bs) {
                                            bs.saveBlockData(self.block_data, true);
                                            bs.uploadFile(files[0], '')
                                            self.switch_disabled = false;
                                        });
                                    },
                                    removeBlock: function(){
                                        const self = this;
                                        const $block_wrapper = that.$target_wrapper.closest('.js-seq-wrapper');
                                        if ($block_wrapper.length) {
                                            $.wa.editor.removeBlock(self.block_id, $block_wrapper);
                                            $.wa.editor._block_settings_drawer_promise.then(function(bs) {
                                                bs.hide();
                                                $.wa.editor.selected_block_id = '';
                                            });
                                        }
                                    },

                                },
                                mounted: function() {
                                    const self = this;
                                    $(self.$el).find("#drop-area").waUpload({
                                        is_uploadbox: true,
                                        show_file_name: true
                                    })
                                }
                            },
                            "CustomVideo": {
                                props: {
                                    group_config: { type: Object },
                                    block_data: { type: Object, default: {} },
                                    block_id: { type: Number},
                                },
                                emits: ["changeElement"],
                                data() {
                                    //const elements = that.states.elements;
                                    const header_name = 'Embed code';
                                    const placeholder = '<iframe src=""></iframe>';
                                    return { header_name, placeholder }
                                  },
                                template: `
                                <div class="s-editor-option-wrapper ">
                                    <div class="video-group-wrapper custom-mb-12 custom-mt-12">
                                        <div class="s-semi-header small custom-pb-20" v-html="
                                            $t('custom.Get the embed code on **YouTube** or **Vimeo**, or another similar service')
                                                .replace('**', '<a href=&quot;https://www.youtube.com/&quot; target=&quot;_blank&quot;>')
                                                .replace('**', '</a>')
                                                .replace('**', '<a href=&quot;https://vimeo.com/&quot; target=&quot;_blank&quot;>')
                                                .replace('**', '</a>')
                                        ">
                                        </div>
                                        <div class="s-semi-header text-gray small">{{$t('custom.' + header_name)}}</div>
                                        <div class="video-group custom-mb-12 custom-mt-8">
                                            <div class="value js-redactor-wrapper">
                                                <textarea class="width-100 js-content-body" id="js-content-body" name="content" :placeholder="placeholder">{{block_data['html']}}</textarea>
                                                <div style="display: none;" class="state-error-hint" :data-error-tags="$t('custom.Available only iframe tags')" :data-error-open-tag="$t('custom.Need to add the closing tag iframe')"></div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="code-group-wrapper custom-mb-12 custom-mt-12">
                                        <span id="wa-editor-status" style="margin-left: 20px; display: none"></span>
                                    </div>
                                </div>
                                `,
                                //delimiters: ['{ { ', ' } }'],
                                components: {
                                    'CustomButton': that.vue_components['custom-button'],
                                  },
                                methods: {},
                                mounted: function() {
                                    const self = this;
                                    $default_picture = that.$target_wrapper.find('.iframe-picture-cover');
                                    $textarea = $(self.$el).find('.js-content-body');

                                    $textarea.on('input', function () {
                                        let editor_val =  $textarea.val();

                                        if (checkTagsErrors(editor_val, $textarea)) return;

                                        self.temp_video_data = {type: 'code', 'html': editor_val}

                                        self.block_data['html'] = editor_val;
                                        //self.block_data['video'] = self.temp_video_data;

                                        if (!editor_val.length) {
                                            $default_picture.show();
                                        } else {
                                            $default_picture.hide()
                                            self.block_data['video'] = self.temp_video_data;
                                        };

                                        $.wa.editor._block_settings_drawer_promise.then(function(bs) {
                                            bs.saveBlockData(self.block_data);
                                            console.log('saveBlockData', self.block_data)
                                        });
                                    });

                                    function checkTagsErrors(inputText, $textarea) {
                                        let error_text = '';
                                        let error_block = $textarea.parent().find('.state-error-hint');
                                        if (checkForHtmlTagsExceptIframe(inputText)) error_text = 'error-tags';
                                        if (checkIframeWithoutClosingTag(inputText)) error_text = 'error-open-tag';
                                        if (error_text) {
                                            error_block.text(error_block.data(error_text)).show();
                                            $textarea.addClass('state-error');
                                            setTimeout(() => { $textarea.removeClass('state-error'); error_block.hide();}, 3000);
                                            return true;
                                        }
                                        error_block.hide();
                                        return false
                                    }
                                }
                            },
                          },
                        methods: {
                            change: function() {
                                const self = this;
                                $.wa.editor._block_settings_drawer_promise.then(function(bs) {
                                    self.block_data.video = self.temp_video_data;
                                    bs.saveBlockData(self.block_data, true);
                                });
                            },
                            changeCode: function() {

                            },
                            changeToggle: function(option) {
                                const self = this;
                                self.active_toggle_option = option.value;
                            },
                        },
                        mounted: function() {
                            const self = this;
                        }
                    },
                    "TagsGroup": {
                        props: {
                            group_config: { type: Object },
                            block_data: { type: Object, default: {} },
                            block_id: { type: Number},
                        },
                        data() {
                            //const _form_type = that.storage_data[this.group_config.type].type;
                            const arr_options = that.storage_data[this.group_config.type].values;
                            const active_option = this.block_data?.tag;
                            const header_name = this.group_config.name;
                            const semi_header = 'Tag';
                            const form_type = 'custom';

                            return { arr_options, header_name, semi_header, active_option, form_type }
                          },
                        template: that.templates["component_tags_group"],
                        delimiters: ['{ { ', ' } }'],
                        components: {
                            'TagsDropdown': that.vue_components['component-dropdown'],
                          },
                        methods: {
                            change(option) {
                                let self = this;
                                $.wa.editor._block_settings_drawer_promise.then(function(bs) {
                                    self.block_data.tag = option.value;
                                    bs.saveBlockData(self.block_data);
                                    console.log('saveBlockData', self.block_data)
                                });
                                self.active_option = option.value;
                                that.$target_wrapper = that.$iframe_wrapper.find('.seq-child [data-block-id=' +  self.block_id + ']');
                            },
                        }
                    },
                    "ImageSeoGroup": {
                        props: {
                            group_config: { type: Object },
                            block_data: { type: Object, default: {} },
                            block_id: { type: Number},
                        },
                        data() {
                            const header_name = this.group_config.name;
                            let active_option_alt = this.block_data?.alt || '';
                            let active_option_title = this.block_data?.title || '';
                            //let showChildren =  active_option_alt || active_option_title ? true : false;
                            return { header_name, active_option_alt, active_option_title}
                          },
                        template: that.templates["component_image_seo_group"],
                        delimiters: ['{ { ', ' } }'],
                        components: {
                            //'TagsDropdown': that.vue_components['component-dropdown'],
                          },
                        methods: {
                            change() {
                                let self = this;
                                self.block_data.alt = self.active_option_alt;
                                self.block_data.title = self.active_option_title;
                                $.wa.editor._block_settings_drawer_promise.then(function(bs) {
                                    bs.saveBlockData(self.block_data);
                                    console.log('saveBlockData', self.block_data)
                                });
                            },
                        },
                        mounted: function() {
                            const self = this;
                            $(self.$el).find("#tooltip-alt").waTooltip();
                            $(self.$el).find("#tooltip-title").waTooltip();
                        }
                    },
                    "IdGroup": {
                        props: {
                            group_config: { type: Object },
                            block_data: { type: Object, default: {} },
                            block_id: { type: Number},
                        },
                        data() {
                            const self = this;
                            const form_type = 'scroll-margin-top';
                            const selected_element = that.states.selected_element;
                            self.active_option_object = self.block_data?.inline_props?.[form_type] || self.block_data?.inline_props?.[selected_element]?.[form_type] || null;
                            self.active_option_id = (self.block_data?.id && typeof self.block_data.id === 'string') ? self.block_data?.id : (self.block_data?.id?.[selected_element]?.id || self.active_option_object?.id || null);
                            //self.active_option_id = self.block_data?.id?.[selected_element]?.id || self.active_option_object?.id || null;

                            let showChildren =  self.active_option_id || self.active_option_object ? true : false;
                            const header_name = self.group_config.name;
                            const semi_header = 'Scroll offset'
                            let unit_options = that.storage_data.border_unit_data;

                            inputId = self.active_option_id ? '#' + self.active_option_id : '';
                            inputUnit = self.active_option_object?.unit || "px";
                            inputSize = self.active_option_object?.value?.split(inputUnit)[0] || '';
                            return { inputId, form_type, inputUnit, inputSize, unit_options, selected_element, header_name,semi_header, showChildren}
                          },
                        template: that.templates["component_id_group"],
                        delimiters: ['{ { ', ' } }'],
                        components: {
                            'WidthUnitDropdown': that.vue_components['component-dropdown'],
                          },
                        methods: {
                            change: function(option) {
                                const self = this;
                                if (option === 'input') {
                                    if (self.inputId !== '#' && self.inputId !== '') {
                                        let hash_pos = self.inputId.indexOf('#');
                                        if (hash_pos >= 0) {
                                            self.inputId = '#' + self.inputId.split('#')[1];
                                        } else {
                                            self.inputId = '#' + self.inputId;
                                        }
                                    }

                                    self.temp_active_option_object = self.temp_active_option_object ? self.temp_active_option_object : {};
                                    self.temp_active_option_object.value = self.inputSize ? self.inputSize+self.inputUnit : '';
                                    self.temp_active_option_object.unit = self.inputUnit;
                                    self.temp_active_option_object.id = self.inputId.split('#')[1] || '';
                                    return
                                }

                                if ((self.active_option_object?.id !== self.temp_active_option_object.id) && self.checkUniqErrors()) return;

                                //self.block_data.id = self.temp_active_option_object.id;
                                self.active_option_object = self.temp_active_option_object;
                                self.active_option_id = self.temp_active_option_object.id;
                                if (!self.selected_element) {
                                    if (!self.block_data.inline_props) self.block_data.inline_props = {};
                                    self.block_data.inline_props[self.form_type] = self.temp_active_option_object;
                                    self.block_data.id = self.active_option_id;
                                } else {
                                    if (!self.block_data.inline_props) self.block_data.inline_props = {};
                                    if (!self.block_data.inline_props[self.selected_element]) self.block_data.inline_props[self.selected_element] = {};
                                    self.block_data.inline_props[self.selected_element][self.form_type] = self.temp_active_option_object;
                                    if (!self.block_data.id || typeof self.block_data.id === 'string') self.block_data.id = {};
                                    if (!self.block_data.id[self.selected_element]) self.block_data.id[self.selected_element] = {};
                                    console.log(self.temp_active_option_object)
                                    self.block_data.id[self.selected_element].id = self.active_option_id;
                                }
                                $.wa.editor._block_settings_drawer_promise.then(function(bs) {
                                    bs.saveBlockData(self.block_data);
                                    console.log('saveBlockData', self.block_data)
                                });

                            },
                            changeInput() {
                                let self = this;
                                self.change('input');
                            },
                            checkUniqErrors() {
                                let self = this;
                                let input = $(self.$el).find('[name="input-id"]');
                                let error_block = input.parent().find('.state-error-hint');

                                let not_uniq = that.$iframe_wrapper.find(self.inputId).length;
                                if (not_uniq) {
                                    if (!input.hasClass('state-error')) input.addClass('state-error');
                                    error_block.show();
                                    setTimeout(() => { input.removeClass('state-error'); error_block.hide();}, 3000);
                                    return true;
                                }
                                input.removeClass('state-error')
                                error_block.hide();
                                return false
                            },
                            changeId(e) {
                                let self = this;
                                self.change('input');
                            },
                            changeUnit(option) {
                                let self = this;
                                self.inputUnit = option.value
                                self.change('input');
                                self.change();
                            },
                            setDefault() {
                                const self = this;
                                //console.log('setDefault')
                                if (self.active_option_object) {
                                    self.active_option_object = null;
                                    self.active_option_id = '';
                                    self.inputUnit = 'px';
                                    self.inputSize = '';
                                    self.inputId = '';
                                    //self.$refs.child.toggleData(self.showChildren, self.arr_options[3]);
                                    $.wa.editor._block_settings_drawer_promise.then(function(bs) {
                                        if (!self.selected_element) {
                                            if (self.block_data.inline_props) delete self.block_data.inline_props[self.form_type];
                                            delete self.block_data.id;
                                        } else {
                                            if (self.block_data.inline_props && self.block_data.inline_props[self.selected_element]) delete self.block_data.inline_props[self.selected_element][self.form_type];
                                            if (self.block_data.id && self.block_data.id[self.selected_element]) delete self.block_data.id[self.selected_element];
                                            if (typeof self.block_data.id === 'string') delete self.block_data.id;
                                        }
                                        bs.saveBlockData(self.block_data);
                                    });
                                    console.log('saveBlockData', self.block_data);
                                }
                            },
                            toggleChildren() {
                                var self = this;
                                self.setDefault();
                                self.showChildren = !self.showChildren;
                            },
                        },
                        mounted: function() {
                            const self = this;
                            $(self.$el).find("#tooltip-identifier").waTooltip();
                        }
                    },
                    "ListStyleGroup": {
                        props: {
                            group_config: { type: Object },
                            block_data: { type: Object, default: {} },
                            block_id: { type: Number},
                        },
                        data() {
                            //const _form_type = that.storage_data[this.group_config.type].type;
                            const arr_options = that.storage_data[this.group_config.type].values;
                            const active_option = this.block_data?.tag;
                            const header_name = this.group_config.name;
                            const semi_header = 'Tag';
                            const form_type = 'custom';

                            return { arr_options, header_name, semi_header, active_option, form_type }
                          },
                        template: that.templates["component_list_style_group"],
                        delimiters: ['{ { ', ' } }'],
                        components: {
                            'TagsDropdown': that.vue_components['component-dropdown'],
                          },
                        methods: {
                            change(option) {
                                let self = this;
                                $.wa.editor._block_settings_drawer_promise.then(function(bs) {
                                    self.block_data.tag = option.value;
                                    bs.saveBlockData(self.block_data);
                                    console.log('saveBlockData', self.block_data)
                                });
                                self.active_option = option.value;
                                that.$target_wrapper = that.$iframe_wrapper.find('.seq-child [data-block-id=' +  self.block_id + ']');
                            },
                        }
                    },
                    "CustomCodeGroup": {
                        props: {
                            group_config: { type: Object },
                            block_data: { type: Object, default: {} },
                            block_id: { type: Number},
                        },
                        emits: ["changeElement"],
                        data() {
                            //const elements = that.states.elements;
                            const arr_options = ['html', 'css', 'js'];
                            //const active_option = this.block_data?.tag;
                            let showAIPanel = false;
                            let prompt_options = that.storage_data['ai_prompt_data']?.[this.group_config.block_type];
                            let aiFacility = prompt_options.facility;

                            const header_name = this.group_config.name;
                            const selected_tab = 'html';
                            this.editor_session = null;

                            return { selected_tab, arr_options, header_name, showAIPanel, aiFacility, prompt_options }
                          },
                        template: `
                        <div class="s-editor-option-wrapper ">
                            <div class="tabs-wrapper-group">
                                <ul class="tabs small" v-if="arr_options">
                                    <li v-for="(item, key) in arr_options"
                                        :class="{ selected: item === selected_tab }"
                                        id="item"
                                        @click="changeTab(item)"
                                        >
                                        <a href="javascript:void(0);" class="align-center">
                                            <span class="tabs-name bold uppercase">{{item}}</span>
                                        </a>
                                    </li>
                                </ul>
                            </div>
                            <div class="code-group-wrapper custom-mb-12 custom-mt-12">
                                <div class="code-group custom-mb-12 custom-mt-12">
                                    <div class="value js-redactor-wrapper">
                                        <textarea style="display:none" class="js-content-body" id="js-content-body" name="content"> {{block_data[selected_tab]}}</textarea>
                                    </div>
                                </div>
                            </div>
                            <div class="code-group-wrapper custom-mb-12 custom-mt-12 sticky">
                                <p class="hint custom-mt-8">{{$t('custom.custom_code_hint')}}</p>
                                <div class="code-group-buttons flexbox">
                                    <custom-button id="js-save-code" :buttonText="$t('custom.Save')" buttonClass="green blue" @click="saveCode"></custom-button>
                                    <span id="wa-editor-status" style="margin-left: 20px; display: none"></span>
                                    <button class="button smaller nobutton gray custom-ml-auto" title="Webasyst AI" @click="showAIPanel = !showAIPanel">
                                        <span class="icon webasyst-ai"></span>
                                    </button>
                                    <custom-button v-show="selected_tab === 'html'" id="js-show-variables" iconClass="fa-dollar-sign" :title="$t('custom.Variables')" buttonClass="gray nobutton" @click="showVariables"></custom-button>
                                    <custom-button v-show="selected_tab === 'html'" id="js-show-cheatsheet" iconClass="fa-code" :title="$t('custom.CheatSheet')" buttonClass="gray nobutton" @click="showCheatSheet"></custom-button>
                                </div>
                                <ai-generator v-if="showAIPanel" :group_config="group_config" :facility="aiFacility" @generate="handleAiAnswer" @undo="undoElement" @closeForm="showAIPanel = !showAIPanel" :block_id="block_id" container_class="custom-code-panel"></ai-generator>
                            </div>
                        </div>
                        `,
                        //delimiters: ['{ { ', ' } }'],
                        components: {
                            'CustomButton': that.vue_components['custom-button'],
                            'AiGenerator': that.vue_components['component-ai-generator'],
                          },
                        methods: {
                            handleAiAnswer: function(data) {
                                let self = this;
                                //let $editable = that.$target_wrapper.find('.style-wrapper');
                                //$editable = $editable.length ? $editable : that.$target_wrapper;
                                let response_type = self.prompt_options?.response_type;
                                if (data?.someData?.[response_type]) {
                                //self.oldElementHtml = $editable.html();
                                self.oldElement = self.editor_session.getValue();
                                self.editor_session.setValue(data?.someData?.[response_type]);
                                self.saveCode();
                                }
                                //session.getValue();

                            },
                            undoElement: function() {
                                let self = this;
                                //let $editable = that.$target_wrapper.find('.style-wrapper');
                                //$editable = $editable.length ? $editable : that.$target_wrapper;
                                if (self.oldElement) {
                                    //self.oldElement = session.getValue();
                                    self.editor_session.setValue(self.oldElement);
                                    self.saveCode();
                                }
                            },
                            changeTab(opt){
                                const self = this;
                                self.selected_tab = opt;
                                self.editor_session.setValue(self.block_data[opt]);
                                if (opt == 'css') {
                                    self.editor_session.setMode("ace/mode/css");
                                } else if (opt == 'js') {
                                    self.editor_session.setMode("ace/mode/javascript");
                                } else {
                                    self.editor_session.setMode("ace/mode/smarty");
                                }
                                $button = $(self.$el).find('#js-save-code');
                                $button.removeClass('yellow').addClass('green');

                            },
                            saveCode(opt){
                                const self = this;
                                const $button = $(self.$el).find('#js-save-code');

                                $.wa.editor._block_settings_drawer_promise.then(function(bs) {
                                    self.block_data[self.selected_tab] = self.editor_session.getValue();
                                    bs.saveBlockData(self.block_data);
                                    $button.removeClass('yellow').addClass('green');
                                    console.log('saveBlockData', self.block_data)
                                });
                                /*if (self.selected_tab === 'css') {
                                    self.$editable = that.$target_wrapper.siblings('#style-tag');
                                    self.$editable.html($.wa.editor.sanitizeHTML(session.getValue()))
                                }*/
                                /*if (self.selected_tab === 'js') {
                                    self.$editable = that.$target_wrapper.siblings('#script-tag');
                                    let script_string = '$(function() { "use strict";' + session.getValue() + '});'
                                    self.$editable.html(script_string)
                                }*/
                            },
                            showVariables(){
                                const self = this;
                                //$button = $(self.$el).find('#js-save-code');
                                $('.site-editor-wa-header-wrapper').find('.js-show-variables').click();
                            },
                            showCheatSheet(){
                                const self = this;
                                //$button = $(self.$el).find('#js-save-code');
                                $('.site-editor-wa-header-wrapper').find('.js-show-cheatsheet').click();
                            }
                        },

                        mounted: function() {
                            const self = this;
                            $html_wrapper = $(self.$el).find('.js-redactor-wrapper');
                            $textarea = $(self.$el).find('.js-content-body');
                            waEditorAceInit({ 'id': 'js-content-body','type': "html", 'save_button': 'js-save-code'});
                            //wa_editor.setOption('fontSize', 14);
                            wa_editor.$blockScrolling = Infinity;
                            wa_editor.setOption('minLines', 15);
                            wa_editor.renderer.setShowGutter(false);
                            self.editor_session = wa_editor.getSession();
                            that.$sidebar.addClass('custom-code-mode');
                        },
                        unmounted: function() {
                            that.$sidebar.removeClass('custom-code-mode');
                        }
                    },
                    "CustomMapGroup": {
                        props: {
                            group_config: { type: Object },
                            block_data: { type: Object, default: {} },
                            block_id: { type: Number},
                        },
                        emits: ["changeElement"],
                        data() {
                            //const elements = that.states.elements;
                            const header_name = this.group_config.name;
                            const placeholder = '<iframe src=""></iframe>';
                            return { header_name, placeholder }
                          },
                        template: `
                        <div class="s-editor-option-wrapper ">
                            <div class="map-group-wrapper custom-mb-12 custom-mt-12">
                                <div class="s-semi-header small custom-pb-20" v-html="
                                    $t('custom.Get the map embed code on **Google Maps**, **Yandex Maps**, or another similar service **Help**')
                                        .replace('**', '<a href=&quot;https://www.google.com/maps&quot; target=&quot;_blank&quot;>')
                                        .replace('**', '</a>')
                                        .replace('**', '<a href=&quot;https://yandex.ru/map-constructor/&quot; target=&quot;_blank&quot;>')
                                        .replace('**', '</a>')
                                        .replace('**', '<a href=&quot;' + $t('custom.Map Help Link') + '&quot; target=&quot;_blank&quot;>')
                                        .replace('**', '</a>')
                                "></div>
                                <div class="s-semi-header text-gray small">{{header_name}}</div>
                                <div class="map-group custom-mb-12 custom-mt-8">
                                    <div class="value js-redactor-wrapper">
                                        <textarea class="width-100 js-content-body" id="js-content-body" name="content" :placeholder="placeholder">{{block_data['html']}}</textarea>
                                        <div style="display: none;" class="state-error-hint" :data-error-tags="$t('custom.Available only iframe tags')" :data-error-open-tag="$t('custom.Need to add the closing tag iframe')"></div>
                                    </div>
                                </div>
                            </div>
                            <div class="map-group-wrapper custom-mb-12 custom-mt-12">
                                <span id="wa-editor-status" style="margin-left: 20px; display: none"></span>
                            </div>
                        </div>
                        `,
                        //delimiters: ['{ { ', ' } }'],
                        components: {
                            'CustomButton': that.vue_components['custom-button'],
                          },
                        methods: {},

                        mounted: function() {
                            const self = this;

                            //div = $('<div></div>');
                           // $html_wrapper = $(self.$el).find('.js-redactor-wrapper');
                            $default_picture = that.$target_wrapper.find('.iframe-picture-cover');
                            $textarea = $(self.$el).find('.js-content-body');
                            //$placeholder = '<iframe src=""></iframe>';
                            // Init Ace
                            //$html_wrapper.prepend($('<div class="ace"></div>').append(div));
                            //$textarea.hide();
                            //var editor = ace.edit(div.get(0));
                            // Set options
                            /*editor.commands.removeCommand('find');
                            ace.config.set("basePath", wa_url + 'wa-content/js/ace/');
                            editor.setTheme("ace/theme/eclipse");
                            editor.renderer.setShowGutter(false);
                            editor.$blockScrolling = Infinity;
                            var session = editor.getSession();
                            session.setMode("ace/mode/smarty");
                            session.setUseWrapMode(true);
                            editor.setFontSize(14);
                            if ($textarea.val().length) {
                                session.setValue($textarea.val());
                            } else {
                                //session.setValue($placeholder);
                            }
                            editor.setOption("minLines", 10);
                            editor.setOption("maxLines", 100);*/
                            $textarea.on('input', function () {
                                let editor_val = $textarea.val();

                                if (checkTagsErrors(editor_val, $textarea)) return;

                                self.block_data['html'] = editor_val;
                                if (!editor_val.length) {
                                    $default_picture.show();
                                } else $default_picture.hide();

                                $.wa.editor._block_settings_drawer_promise.then(function(bs) {
                                    bs.saveBlockData(self.block_data);
                                    console.log('saveBlockData', self.block_data)
                                });
                            });

                            function checkTagsErrors(inputText, $textarea) {
                                let error_text = '';
                                let error_block = $textarea.parent().find('.state-error-hint');
                                if (checkForHtmlTagsExceptIframe(inputText)) error_text = 'error-tags';
                                if (checkIframeWithoutClosingTag(inputText)) error_text = 'error-open-tag';
                                if (error_text) {
                                    error_block.text(error_block.data(error_text)).show();
                                    $textarea.addClass('state-error');
                                    setTimeout(() => { $textarea.removeClass('state-error'); error_block.hide();}, 3000);
                                    return true;
                                }
                                error_block.hide();
                                return false
                            }

                        }
                    },
                    "CustomFormGroup": {
                        props: {
                            group_config: { type: Object },
                            block_data: { type: Object, default: {} },
                            block_id: { type: Number},
                        },
                        emits: ["changeElement"],
                        data() {
                            //const elements = that.states.elements;
                            const header_name = this.group_config.name;
                            let placeholder = `{$wa->crm->form(1)}

                            или {$wa->mailer->form(1)}

                            или {$wa->helpdesk->form(1)}`;
                            const form_type = this.block_data?.form_type;
                            if(form_type) {
                                placeholder = `{$wa->${form_type}->form(1)}`;
                            }
                            const wa_url = $.site.backend_url;
                            return { header_name, placeholder, form_type, wa_url }
                          },
                        template: `
                        <div class="s-editor-option-wrapper ">
                            <div class="form-group-wrapper custom-mb-12 custom-mt-12">
                                <div class="s-semi-header small custom-pb-20">{{$t('custom.Get the embed code in webasyst')}} <a :href="wa_url + form_type + '/'" class="nowrap" target="_blank">{{$t('custom.form_' + form_type, form_type)}} <i class="fas fa-external-link-alt small"></i></a></div>
                                <div class="s-semi-header text-gray small">{{header_name}}</div>
                                <div class="form-group custom-mb-12 custom-mt-8">
                                    <div class="value js-redactor-wrapper">
                                        <textarea class="width-100 js-content-body" id="js-content-body" name="content" :placeholder="placeholder">{{block_data['textarea_html']}}</textarea>
                                        <div style="display: none;" class="state-error-hint" :data-error-tags="$t('custom.Available only iframe tags')" :data-error-open-tag="$t('custom.Need to add the closing tag iframe')"></div>
                                    </div>
                                </div>
                            </div>
                            <div class="code-group-wrapper custom-mb-12 custom-mt-12">
                                <span id="wa-editor-status" style="margin-left: 20px; display: none"></span>
                            </div>
                        </div>
                        `,
                        //delimiters: ['{ { ', ' } }'],
                        components: {
                            'CustomButton': that.vue_components['custom-button'],
                          },
                        methods: {},
                        mounted: function() {
                            const self = this;
                            $default_picture = that.$target_wrapper.find('.iframe-picture-cover');
                            $textarea = $(self.$el).find('.js-content-body');

                            let timer_id = null;
                            $textarea.on('input', () => {
                                if (timer_id) {
                                    clearTimeout(timer_id);
                                }
                                timer_id = setTimeout(() => {
                                    let editor_val =  $textarea.val();
                                    if (typeof editor_val === 'string') {
                                        editor_val = editor_val.trim();
                                    }

                                    if (checkTagsErrors(editor_val, $textarea)) return;

                                    self.block_data['html'] = editor_val;
                                    self.block_data['textarea_html'] = editor_val;
                                    /*if (!editor_val.length) {
                                        $default_picture.show();
                                    } else $default_picture.hide();
                                    */
                                    $.wa.editor._block_settings_drawer_promise.then(function(bs) {
                                        bs.saveBlockData(self.block_data);
                                        console.log('saveBlockData', self.block_data)
                                    });
                                    timer_id = null;
                                }, 350);
                            });

                            function checkTagsErrors(inputText, $textarea) {
                                let error_text = '';
                                let error_block = $textarea.parent().find('.state-error-hint');
                                //if (checkForHtmlTagsExceptIframe(inputText)) error_text = 'error-tags';
                                //if (checkIframeWithoutClosingTag(inputText)) error_text = 'error-open-tag';
                                if (error_text) {
                                    error_block.text(error_block.data(error_text)).show();
                                    $textarea.addClass('state-error');
                                    setTimeout(() => { $textarea.removeClass('state-error'); error_block.hide();}, 3000);
                                    return true;
                                }
                                error_block.hide();
                                return false
                            }
                        }
                    },
                    "CustomFormSelectionGroup": {
                        props: {
                            group_config: { type: Object },
                            block_data: { type: Object, default: {} },
                            block_id: { type: Number},
                        },
                        emits: ["changeElement"],
                        data() {
                            const form_type = this.block_data?.form_type;
                            const app_disabled = this.group_config.app_disabled;
                            const app_url = this.group_config.app_url;
                            const options = Object.values(this.group_config.options || []);
                            const option_value = (this.block_data?.textarea_html || '').trim();

                            return { form_type, app_disabled, app_url, options, option_value }
                          },
                        template: `
                        <div class="s-editor-option-wrapper">
                            <div v-if="app_disabled" class="alert small info">
                                <i class="fas fa-info-circle fa-sm"></i>
                                <span v-html="
                                    $t('custom.To customize the form, install or enable the <a href={url}>{appName} app</a>',
                                        { url: app_url, appName: $t('custom.form_'+form_type) }
                                    )"
                                /> <i class="fas fa-external-link-alt fa-sm"></i>
                            </div>
                            <div v-else class="form-group-wrapper custom-mb-12 custom-mt-12">
                                <div class="form-group custom-mb-8">
                                    <div class="s-editor-option-body value js-redactor-wrapper">
                                        <component-dropdown :options="options" :placeholder="$t('Select form')" :active-option="option_value" :active-bold="true" :enable-icon="true" :form_type="'custom'" @customChange="updateSelectedBlock"></component-dropdown>
                                        <div style="display: none;" class="state-error-hint" :data-error-tags="$t('custom.Available only iframe tags')" :data-error-open-tag="$t('custom.Need to add the closing tag iframe')"></div>
                                    </div>
                                </div>
                                <div class="hint">
                                    <span v-html="
                                        $t('custom.You can edit or create a web form in the <a href={url}>{appName} app</a>',
                                            { url: app_url, appName: $t('custom.form_'+form_type) }
                                        )"
                                    /> <i class="fas fa-external-link-alt fa-sm"></i>
                                </div>
                            </div>
                            <div class="code-group-wrapper custom-mb-12 custom-mt-12">
                                <span id="wa-editor-status" style="margin-left: 20px; display: none"></span>
                            </div>
                        </div>
                        `,
                        components: {
                            'ComponentDropdown': that.vue_components['component-dropdown'],
                        },
                        methods: {
                            updateSelectedBlock: function(option) {
                                if (!option.value || option.value === 'form_add') {
                                    return;
                                }
                                $default_picture = that.$target_wrapper.find('.iframe-picture-cover');
                                $default_picture.remove();
                                this.saveData(option.value);
                            },
                            saveData: function(editor_val) {
                                const self = this;

                                self.block_data['html'] = editor_val;
                                self.block_data['textarea_html'] = editor_val;

                                $.wa.editor._block_settings_drawer_promise.then(function(bs) {
                                    bs.saveBlockData(self.block_data);
                                });
                            }
                        }
                    },
                    "ProductIdGroup": {
                        props: {
                            group_config: { type: Object },
                            block_data: { type: Object, default: {} },
                            block_id: { type: Number},
                        },
                        data() {
                            const form_type = 'product_id';
                            const active_id = this.block_data?.product_id;
                            const productName = active_id ? this.block_data?.additional?.product?.name : '';
                            const productStatus = active_id && this.block_data?.additional?.product?.id ? this.block_data?.additional?.product?.status : null;
                            const header_name = this.group_config.name;
                            const wa_backend_url = $.site.backend_url;
                            //const wa_shop_url =  $.site.shop_url;
                            return { active_id, productName, productStatus, header_name, form_type, wa_backend_url }
                        },
                        template:  that.html_templates['component-product-id-group'],
                        delimiters: ['{ { ', ' } }'],
                        components: {
                        },
                        methods: {
                            inputAutocomplete(input) {
                                let self = this;
                                const link = self.wa_backend_url+'shop/?module=backend&action=autocomplete&type=product';
                                $(self.$el).find("input").autocomplete({
                                    source: function (request, response) {
                                        var data = {
                                            term: input,
                                            with_image: 1,
                                            with_all_skus: 1,
                                            limit: 30,
                                        };
                                        $.post(link, data , function(response_data) {
                                            if (!response_data.length) {
                                            }
                                            //console.log(response_data.data.products)
                                            response(response_data);
                                        }, "json")
                                    },
                                    minLength: 0,
                                    delay: 300,
                                    html: true,
                                    //autoFocus: true,
                                    appendTo: $(self.$el),
                                    select: function (event, ui) {
                                        self.block_data[self.form_type] = ui.item.id;
                                        delete self.block_data.sku_id;
                                        $.wa.editor._block_settings_drawer_promise.then(function(bs) {
                                            bs.saveBlockData(self.block_data)
                                            console.log('saveBlockData', self.block_data);
                                            reloadSettingsDrawerForSelectedBlock();
                                        });

                                    }
                                }).data("ui-autocomplete")._renderItem = function(ul, item) {
                                    var html = "";
                                    if (!item.id) {
                                        html = $_template_autocomplete_product_empty.replace("%name%", item.value);
                                    } else if (item.image_url) {
                                        html = $_template_autocomplete_product_with_image
                                            .replace("%image_url%", item.image_url)
                                            .replace("%name%", item.value)
                                            .replace("%id%", item.id);
                                    } else {
                                        html = $_template_autocomplete_product
                                            .replace("%name%", item.value)
                                            .replace("%id%", item.id);
                                    }
                                    return $("<li />").addClass("ui-menu-item-html").append(html).appendTo(ul);
                                };

                                $_template_autocomplete_product_with_image = `<div class="s-item-product-wrapper flexbox space-8 middle">
                                        <div>
                                            <div class="s-image" style="background-image: url('%image_url%');"></div>
                                        </div>
                                        <div class="middle" style="line-height: 120%;">
                                            <span class="s-name">%name%</span>
                                        </div>
                                    </div>`;
                                $_template_autocomplete_product_empty = `<div class="s-item-product-wrapper">
                                        <div>
                                            <span class="s-name">%name%</span>
                                        </div>
                                    </div>`;
                                $_template_autocomplete_product = `<div class="s-item-product-wrapper flexbox space-8 middle">
                                        <div>
                                            <div class="s-image">
                                                <span class="s-icon icon size-32"><i class="fas fa-image"></i></span>
                                            </div>
                                        </div>
                                        <div class="middle" style="line-height: 120%;">
                                            <span class="s-name">%name%</span>
                                        </div>
                                    </div>`;
                            },
                        }
                    },
                    "ProductInfoGroup": {
                        props: {
                            group_config: { type: Object },
                            block_data: { type: Object, default: {} },
                            block_id: { type: Number},
                        },
                        data() {
                            const form_type = that.storage_data[this.group_config.type].type;
                            let arr_options = that.storage_data[this.group_config.type].values;
                            const active_option = this.block_data?.[form_type];
                            const header_name = this.group_config.name;
                            return { arr_options, header_name, active_option, form_type }
                        },
                        template: that.templates["component_product_info_group"],
                        delimiters: ['{ { ', ' } }'],
                        components: {
                            'ProductInfoDropdown': that.vue_components['component-dropdown'],
                        },
                        methods: {
                            change(option) {
                                let self = this;
                                $.wa.editor._block_settings_drawer_promise.then(function(bs) {
                                    self.block_data[self.form_type] = option.value;
                                    bs.saveBlockData(self.block_data);
                                    console.log('saveBlockData', self.block_data)
                                });
                            },
                        }
                    },
                    "ProductPictureGroup": {
                        props: {
                            group_config: { type: Object },
                            block_data: { type: Object, default: {} },
                            block_id: { type: Number},
                        },
                        data() {
                            const form_type = that.storage_data[this.group_config.type].type;
                            let arr_options = that.storage_data[this.group_config.type].values;
                            const active_option = this.block_data?.[form_type];
                            const header_name = this.group_config.name;
                            return { arr_options, header_name, active_option, form_type }
                        },
                        template: that.templates["component_product_info_group"],
                        delimiters: ['{ { ', ' } }'],
                        components: {
                            'ProductInfoDropdown': that.vue_components['component-dropdown'],
                        },
                        methods: {
                            change(option) {
                                let self = this;
                                $.wa.editor._block_settings_drawer_promise.then(function(bs) {
                                    self.block_data[self.form_type] = option.value;
                                    bs.saveBlockData(self.block_data);
                                    console.log('saveBlockData', self.block_data)
                                });
                            },
                        }
                    },
                    "ProductSkuGroup": {
                        props: {
                            group_config: { type: Object },
                            block_data: { type: Object, default: {} },
                            block_id: { type: Number},
                        },
                        data() {
                            const form_type = 'sku_id';
                            const arr_options = Object.values(this.block_data?.additional?.product?.skus);
                            const formatted_options = [];

                            $.each(arr_options, function(i, option) {
                                formatted_options.push({
                                    name: option.name?.trim() ? option.name : option.sku,
                                    value: option.id
                                });
                            });

                            const active_option = this.block_data?.[form_type];
                            const header_name = this.group_config.name;
                            return { header_name, active_option, form_type, formatted_options }
                        },
                        template: that.templates["component_product_sku_group"],
                        delimiters: ['{ { ', ' } }'],
                        components: {
                            'ProductInfoDropdown': that.vue_components['component-dropdown'],
                        },
                        methods: {
                            change(option) {
                                let self = this;
                                $.wa.editor._block_settings_drawer_promise.then(function(bs) {
                                    self.block_data[self.form_type] = option.value;
                                    bs.saveBlockData(self.block_data);
                                    console.log('saveBlockData', self.block_data)
                                });
                            },
                        }
                    },
                    "ProductSkuElementLayoutGroup": {
                        props: {
                            group_config: { type: Object },
                            block_data: { type: Object, default: {} },
                            block_id: { type: Number},
                        },
                        data() {
                            const form_type = that.storage_data[this.group_config.type].type;
                            let arr_options = that.storage_data[this.group_config.type].values;

                            const active_option = this.block_data?.[form_type];
                            const header_name = this.group_config.name;
                            const form_type_custom = 'custom';
                            const is_visible = Object.values(this.block_data.additional.product.skus || {}).length > 1;

                            return { form_type_custom, arr_options, header_name, active_option, form_type, is_visible }
                        },
                        template: that.templates["component_columns_align_vertical_group"],
                        delimiters: ['{ { ', ' } }'],
                        components: {
                            'ColumnsAlignDropdown': that.vue_components['component-dropdown'],
                          },
                          methods: {
                            change: function(option) {
                                let self = this;
                                $.wa.editor._block_settings_drawer_promise.then(function(bs) {
                                    self.block_data[self.form_type] = option.value;
                                    bs.saveBlockData(self.block_data);
                                });
                                self.active_option = option.value;
                            },
                        }
                    },
                    "TabsWrapperGroup": {
                        props: {
                            group_config: { type: Object },
                            block_data: { type: Object, default: {} },
                            block_id: { type: Number},
                        },
                        emits: ["changeElement"],
                        data() {
                            //const _form_type = that.storage_data[this.group_config.type].type;
                            const elements = that.states.elements;
                            const semi_header = that.states.semi_headers;
                            const selected_element = that.states.selected_element;
                            return { elements, semi_header, selected_element }
                          },
                        template: `
                        <div class="tabs-wrapper-group custom-mb-24 custom-mt-32">
                            <ul class="tabs small" v-if="elements">
                                <li v-for="(item, key) in elements"
                                    :class="{ selected: item === selected_element }"
                                    id="key"
                                    @click="changeTab(key)"
                                    >
                                    <a href="javascript:void(0);" class="align-center">
                                        <i class="fas" :class="[key === 'main' ? 'fa-expand' : 'fa-compress']"></i>
                                        <span class="tabs-name bold">{{semi_header[key]}}</span>
                                    </a>
                                </li>
                            </ul>
                        </div>
                        `,
                        //delimiters: ['{ { ', ' } }'],
                        components: {
                            //'CustomButton': that.vue_components['custom-button'],
                          },
                        methods: {
                            changeTab(opt){
                                const self = this;
                                self.$wrapper = $(self.$el);
                                const new_element = self.elements[opt];
                                if (self.selected_element === new_element) return;
                                self.$root.changeElement(new_element);
                            },
                        },
                    },
                    "MainControlsGroup": {
                        props: {
                            block_id: { type: Number},
                            form_config: { type: Object },
                        },
                        data() {
                            const self = this;
                            const is_broken_block = this.form_config?.type === "site.Broken" || false;
                            self.block_wrapper = that.$target_wrapper.closest('.js-seq-wrapper');
                            const is_hseq = self.block_wrapper.hasClass('hseq-wrapper');
                            const prev_move_icon = is_hseq ? 'fa-arrow-left' : 'fa-arrow-up';
                            const next_move_icon = is_hseq ? 'fa-arrow-right' : 'fa-arrow-down';
                            return { is_broken_block, prev_move_icon, next_move_icon }
                        },
                        template: `
                        <div class="main-button-group custom-mb-32 custom-mt-8">
                            <custom-button buttonClass="light-gray" iconClass="fa-trash-alt" @click="removeBlock" :title="$t('custom.Delete')"></custom-button>
                            <custom-button buttonClass="light-gray" :iconClass="prev_move_icon" @click="reorderBlocks('up')" v-if="!is_broken_block" :title="$t('custom.Move')"></custom-button>
                            <custom-button buttonClass="light-gray" :iconClass="next_move_icon" @click="reorderBlocks('down')" v-if="!is_broken_block" :title="$t('custom.Move')"></custom-button>
                            <custom-button buttonClass="light-gray" iconClass="fa-copy" @click="copyBlock" v-if="!is_broken_block" :title="$t('custom.Copy')"></custom-button>
                        </div>
                        `,
                        //delimiters: ['{ { ', ' } }'],
                        components: {
                            'CustomButton': that.vue_components['custom-button'],
                          },
                        methods: {
                            removeBlock(){
                                const self = this;
                                if (this.is_broken_block) {
                                    self.block_wrapper = that.$iframe_wrapper.find('.seq-child[data-block-id=' + this.block_id + ']').closest('.js-seq-wrapper');
                                }
                                if (self.block_wrapper.length) {
                                    $.wa.editor.removeBlock(this.block_id, self.block_wrapper);
                                    $.wa.editor._block_settings_drawer_promise.then(function(bs) {
                                        bs.hide();
                                        $.wa.editor.selected_block_id = '';
                                    });
                                }
                            },
                            reorderBlocks(order){
                                const self = this;
                                const block_id = self.block_wrapper.data('block-id');
                                const page_id = self.block_wrapper.data('page-id');
                                const $child = that.$target_wrapper.closest('.seq-child');

                                const before_child_ids = $child.parent().children('.seq-child').map(function() {
                                    return $(this).data('block-id');
                                }).get();

                                if (order === 'up') {
                                    if (!$child.prev().is('.seq-child')) {
                                        return;
                                    }
                                    $child.prev().before($child);
                                } else {
                                    if (!$child.next().is('.seq-child')) {
                                        return;
                                    }
                                    $child.next().after($child);
                                }

                                const child_ids = $child.parent().children('.seq-child').map(function() {
                                    return $(this).data('block-id');
                                }).get();

                                $.wa.editor.reorderBlocks(page_id, block_id, before_child_ids, child_ids, $child.parent());
                            },
                            copyBlock(event){
                                const self = this;
                                self.block_wrapper.css('opacity', 0.5);
                                $(event.target).addClass('disabled');
                                $.post('?module=editor&action=addBlock', {
                                    duplicate_block_id: this.block_id
                                }).then(function(new_parent_block_html) {
                                    self.block_wrapper.replaceWith(new_parent_block_html);
                                });
                            },
                        },
                    },
                },
                /*computed: {
                    customKey: function() {
                        const { ref } = Vue;
                        const self = this;
                        const result = ref(self.block_id+self.media_prop+self.selected_element);
                        return result
                    }
                },*/
                template: `
                    <form-header :header="header" :parents="parents" @closeDrawer="close_drawer" @updateDrawer="update_drawer" @updateDrawerHor="update_drawer_hor" @updateDrawerWidth="update_drawer_width" @goToParent="goToParent"></form-header>
                    <main-controls-group :key="block_id+media_prop" :block_id="block_id" :form_config="form_config" v-if="!(block_data?.indestructible || false)"></main-controls-group>
                    <component
                        v-for="(item, key) in settings_array"
                        :is="item.type"
                        :group_config="item"
                        :block_data="block_data"
                        :block_id="block_id"
                        :key="block_id+media_prop+selected_element+force_key+key"
                    >
                    </component>
                    <div v-if="!settings_array.length" class="alert"><span class="">{{$t('custom.The settings will appear later')}}</span></div>
                    `,
                methods: {
                    reset: function(data, force) {
                        const self = this;
                        Object.assign(this.$data, data);
                        if (force) {
                            self.force_key++;
                        }
                    },
                    close_drawer: function() {
                        $.wa.editor._block_settings_drawer_promise.then(function(bs) {
                                bs.hide();
                                $.wa.editor.selected_block_id = '';
                            });
                        that.$target_wrapper.closest('.seq-child.selected-block').removeClass('selected-block');
                    },
                    update_drawer: function() {
                        $.wa.editor._block_settings_drawer_promise.then(function(bs) {
                                bs.updatePosition();
                                //$.wa.editor.selected_block_id = '';
                            });
                    },
                    update_drawer_hor: function() {
                        $.wa.editor._block_settings_drawer_promise.then(function(bs) {
                                bs.updatePositionHorizontal();
                                //$.wa.editor.selected_block_id = '';
                            });
                    },
                    update_drawer_width: function() {
                        $.wa.editor._block_settings_drawer_promise.then(function(bs) {
                                bs.updateWidth();
                                //$.wa.editor.selected_block_id = '';
                            });
                    },
                    changeElement: function(element) {
                        const self = this;
                        self.selected_element = element;
                        that.states.selected_element = element
                    },
                    goToParent: function(parent) {
                        let self = this;
                        updateSelectedBlock(parent.id)
                    }
                },
                created: function () {
                    //$vue_section.css("visibility", "");
                },
                mounted: function() {
                    that.init(this);
                }
            });

            $.vue_app.use(i18n)

            $.vue_app.config.compilerOptions.whitespace = 'preserve';
            return $.vue_app.mount(that.$wrapper[0]);

            function updateSelectedBlock(block_id) {
                $.wa.editor.setSelectedBlock(block_id);
                that.$iframe_wrapper.find('.selected-block').removeClass('selected-block');
                let $wrapper = that.$iframe_wrapper.find('[data-block-id=' + block_id + ']').eq(0);
                //if ($wrapper.is('.seq-child')) {
                    $wrapper.addClass('selected-block');
                //}
            }

            function reloadSettingsDrawerForSelectedBlock() {
                $.wa.editor._save_block_data_promise().then(() => {
                    setTimeout(() => {
                        if ($.wa.editor.selected_block_id) {
                            $.wa.editor.setSelectedBlock($.wa.editor.selected_block_id, false, true);
                        }
                    });
                });
            }

            function checkForHtmlTagsExceptIframe(inputText) {
                // Регулярное выражение для поиска HTML тегов, кроме <iframe>
                const regex = /<(?!iframe\s*[^>]*>)(?!\/iframe\s*>)[^>]+>/g;
                // Проверка на наличие HTML тегов
                return regex.test(inputText);
            }

            function checkIframeWithoutClosingTag(htmlString) {
                // Регулярное выражение для поиска открывающего тега <iframe> без закрывающего </iframe>
                const iframeRegex = /<iframe[^>]*>(?![\s\S]*<\/iframe>)/;
                // Проверяем строку на соответствие регулярному выражению
                if (iframeRegex.test(htmlString)) {
                    return true; // Возвращаем true, если найден iframe без закрывающего тега
                }
            return false; // Возвращаем false, если все iframe имеют закрывающие теги
            }

            /* желательно обновить все блоки с block_settings_drawer_promise на эту фукнцию*/
            function updateBlockData(form_type, type_props = 'inline_props', data) {
                const element = that.states.selected_element || null;
                if (!element) {
                    if (!that.states.block_data[type_props]) that.states.block_data[type_props] = {};
                    that.states.block_data[type_props][form_type] = data;
                } else {
                    if (!that.states.block_data[type_props]) that.states.block_data[type_props] = {};
                    if (!that.states.block_data[type_props][element]) that.states.block_data[type_props][element] = {};
                    that.states.block_data[type_props][element][form_type]  = data;
                }

                $.wa.editor._block_settings_drawer_promise.then(function(bs) {
                    bs.saveBlockData(that.states.block_data);
                    console.log('saveBlockData', that.states.block_data);
                });
            }

            /* желательно обновить все блоки с block_settings_drawer_promise на эту фукнцию*/
            function deleteBlockData(form_type, type_props = 'inline_props') {
                const element = that.states.selected_element || null;
                if (!element) {
                    if (that.states.block_data[type_props]) delete that.states.block_data[type_props][form_type];
                } else {
                    if (that.states.block_data[type_props] && that.states.block_data[type_props][element]) delete that.states.block_data[type_props][element][form_type];
                }

                $.wa.editor._block_settings_drawer_promise.then(function(bs) {
                    bs.saveBlockData(that.states.block_data);
                });
            }

        };

        function appendIframeWrapperStyles(iframe_document) {
            //console.log(Array.from(iframe_document[0].styleSheets));
            const root_style = Array.from(iframe_document[0].styleSheets)
                .filter(
                    sheet =>
                    sheet
                    //sheet.href === null
                )
                .reduce(
                    (acc, sheet) =>
                    (acc = [
                        ...acc,
                        ...Array.from(sheet.cssRules).reduce(
                        (def, rule) =>
                            (def =
                            (rule.selectorText === ":root")
                                ? [
                                    ...def,
                                    rule
                                ]
                                : def),
                        []
                        )
                    ]),
                    []
                );

                if (root_style.length) {
                    const style = document.createElement('style');
                    //console.log(root_style)
                    style.textContent = root_style[0]?.cssText + root_style[1]?.cssText || '';
                    document.head.appendChild(style);
                }
            }

        return FormConstructor;

})(jQuery);
