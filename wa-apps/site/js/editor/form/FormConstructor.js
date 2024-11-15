
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
            //const { ref } = Vue;

            // CONST
            //that.block_id = options["block_id"];
            that.$wrapper = options["$wrapper"];
            that.templates = $.form_storage.templates;
            that.storage_data = $.form_storage.data;
            that.media_prop =  options["media_prop"];

            //that.block_elements = options["form_config"].elements || null;
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
                comp_data.push(form_config.sections[key]);
            }
            that.media_prop = media_prop;
            that.$target_wrapper = that.$iframe_wrapper.find('.seq-child [data-block-id=' + block_id + ']');
            that.is_new_block = is_new_block;
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

            that.states.parents = {}; //{'Columns': 2715, 'Column': 2716};

            return that.states
        };

        FormConstructor.prototype.resetState = function(block_id, form_config, block_data, media_prop, is_new_block, force_update) {
            var that = this;
            that.vue_model.reset(that.initialState({block_id, form_config, block_data, media_prop, is_new_block}), force_update);
        };

        FormConstructor.prototype.initVue = function() {
            var that = this;

            const { reactive } = Vue;

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
           // i18n.global.locale.value = $.site.lang;
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
                            const form_type = that.storage_data[this.group_config.type].type;
                            let arr_options = that.storage_data[this.group_config.type].values;
                            /*for (let key in this.options.values) {
                                arr_options.push(this.options.values[key]);
                            }*/
                            const active_option = this.block_data?.block_props?.[form_type];
                            const header_name = this.group_config.name;

                            return { arr_options, header_name, active_option, form_type }
                          },
                        template: that.templates["component_font_group"],
                        delimiters: ['{ { ', ' } }'],
                        components: {
                            'FontDropdown': that.vue_components['component-dropdown'],
                          },

                    },
                    "ButtonStyleGroup": {
                        props: {
                            group_config: { type: Object },
                            block_data: { type: Object, default: {} },
                            block_id: { type: Number},
                        },
                        data() {
                            const form_type = that.storage_data[this.group_config.type].type;
                            let arr_options = that.storage_data[this.group_config.type].values;
                            /*for (let key in this.options.values) {
                                arr_options.push(this.options.values[key]);
                            }*/
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
                    "ColumnsGroup": {
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
                    },
                    "NewColumnsGroup": {
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
                                const column_data = $.wa.editor.block_storage.getData(column_id);
                                columns_data.push(column_data.new_column);
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
                                const column_data = $.wa.editor.block_storage.getData(column_id);
                                column_data.new_column = temp_active_options.join(' ');
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
                                else column_data = { parent_block_id: $block_wrapper.data('block-id'), type_name: 'site.NewColumn_' };
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
                            const element = that.states.elements['wrapper'];
                            const active_option = this.block_data?.block_props?.[element]?.[form_type];
                            const header_name = this.group_config.name;
                            return { arr_options, header_name, active_option, form_type, element }
                        },
                        template: that.templates["component_columns_align_group"],
                        delimiters: ['{ { ', ' } }'],
                        components: {
                            'ColumnsAlignDropdown': that.vue_components['component-dropdown'],
                          },
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
                                console.log('changeSwitch', option, self.active_option);
                                $.wa.editor._block_settings_drawer_promise.then(function(bs) {
                                    if (!self.block_data.block_props) self.block_data.block_props = {};
                                    if (!self.block_data.block_props[self.element]) self.block_data.block_props[self.element] = {};
                                    self.block_data.block_props[self.element][self.form_type] = result_value;
                                    bs.saveBlockData(self.block_data);
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
                                    $app_template_section.show();
                                    $main_settings_wrapper.children().each(function(i, el){ 
                                        if (i > 2) $(el).hide();
                                    })
                                } else {
                                    $editable.show();
                                    $app_template_section.hide();
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
                            const parentsBlocks = Object.assign(that.states.parents || {}, { [that.states.form_config.type_name]: this.block_id });
                            //console.log(parents, this.block_data, that.states.form_config.type_name)
                            let active_link_options = {};
                            return { active_link_options, parentsBlocks, for_key, selection, arr_options, button_class, form_type, active_options, tagsArr, showLinkModal}
                        },
                        template: `
                            <div class="font-style-group custom-mb-16">
                            <template v-for="(item, key) in arr_options" :key="block_id-key">
                                <custom-button :buttonClass="button_class" :iconClass="item.icon" @click="change(item.value)" :selfOption="item.value" :activeOptions="active_options" :title="$t(form_type+'.'+item.name)"></custom-button>
                            </template>
                            </div>
                            <form-link v-if="showLinkModal" :active_link_options="active_link_options" :parents="parentsBlocks"  @closeDrawer="toggleModal" @goToParent="goToParent" @updateLink="change" :selection="selection" :key="for_key"></form-link>
                        `,
                        //delimiters: ['{ { ', ' } }'],
                        components: {
                            'CustomButton': that.vue_components['custom-button'],
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
                                    const selection_attr = {
                                        active_option: 'external-link',
                                        active_block_option: '1',
                                        active_pages_option: '1',
                                        inputEmail: '',
                                        inputSubject: '',
                                        inputPhone: '',
                                        inputCheckbox: false,
                                    };

                                    const active_data = arr_options.filter( function(option) {
                                        return (option.value === 'external-link');
                                    })[0];
                                    //
                                    return { selection_attr, active_data, link_header, arr_options, arr_block_options, arr_pages_options, form_type}
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
                                            if (value === 'internal-link' ) {
                                                self.selection_attr.active_pages_option = selection_temp.getNamedItem("data-block").value;
                                            }
                                            if (value === 'block-link' ) {
                                                self.selection_attr.active_block_option = selection_temp.getNamedItem("data-block").value;
                                            }
                                            if (selection_temp.getNamedItem("target") ) {
                                                self.selection_attr.inputCheckbox = true;
                                            }
                                        }
                                },
                                methods: {
                                    change: function(option) {
                                        let self = this;
                                        self.active_data = self.arr_options.filter( function(opt) {
                                            return (opt.value === option.value);
                                        })[0];
                                    },
                                    changePagesDropdown: function(option) {
                                        let self = this;
                                        self.selection_attr.active_pages_option = option.value;
                                    },
                                    changeBlockDropdown: function(option) {
                                        let self = this;
                                        self.selection_attr.active_block_option = option.value;
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
                                            new_url = self.arr_pages_options.filter( function(option) {
                                                return (option.value === self.selection_attr.active_pages_option);
                                            })[0]['url'];
                                            active_block_attr = self.selection_attr.active_pages_option;
                                        }
                                        if (active_data.value === 'block-link' ) {
                                            new_url = self.arr_block_options.filter( function(option) {
                                                return (option.value === self.selection_attr.active_block_option);
                                            })[0]['url'];
                                            active_block_attr = self.selection_attr.active_block_option;
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
                                            self.selection.anchorNode.parentElement.setAttribute("data-value", active_data.value)
                                            if (active_block_attr) {
                                                self.selection.anchorNode.parentElement.setAttribute("data-block", active_block_attr)
                                            }
                                            //self.cleanForm();

                                        }
                                    },
                                    deleteLink: function() {
                                        let self = this;
                                        self.$emit('updateLink', 'unlink');
                                        self.$emit('closeDrawer');
                                    },
                                    cleanForm: function() {
                                        let self = this;
                                        /*self.inputEmail = '';
                                        self.inputCheckbox = false;
                                        self.inputPhone = '';
                                        self.inputSubject = '';
                                        self.active_option = 'external-link';
                                        self.active_block_option = '1';
                                        self.active_pages_option = '1';*/
                                        /*self.active_data = self.arr_options.filter( function(option) {
                                            return (option.value === self.active_option);
                                        })[0];*/
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
                                that.$iframe_wrapper[0].execCommand(option, false, url); // сохраняет форму сама по себе, т.к. идет событие input в элементе 
                                
                                //self.block_data.html = $editable.html();
                                $.wa.editor._block_settings_drawer_promise.then(function(bs) {
                                    self.block_data.html = $editable.html();
                                    bs.saveBlockData(self.block_data, false);
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
                            toggleModal() {
                                let self = this;
                                self.showLinkModal = !self.showLinkModal;
                            },
                            goToParent: function(parent_id) {
                                let self = this;
                                console.log(parent_id, self.block_id)
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
                            $editable.on('mouseup', function(e) { // To highlight the buttons on mouseup
                                let targetEl = $(e.target);
                                let tagN = targetEl.prop("tagName");
                                let temp_active_options = [];
                                if (self.showLinkModal) {
                                    self.selection = that.$iframe_wrapper[0].getSelection();
                                    self.for_key = self.for_key + 1;
                                    //console.log(self.selection);
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
                            })
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
                            const group_header = this.group_config.name;
                            const selection_attr = {
                                active_option: 'external-link',
                                active_block_option: '1',
                                active_pages_option: '1',
                                inputEmail: '',
                                inputSubject: '',
                                inputPhone: '',
                                inputCheckbox: false,
                            };
                            const show_buttons = false;

                            const active_data = arr_options.filter( function(option) {
                                return (option.value === 'external-link');
                            })[0];
                            //
                            return { show_buttons, selection_attr, active_data, group_header, arr_options, arr_block_options, arr_pages_options, form_type}
                          },
                        template: that.templates["component_button_link_group"],
                        components: {
                            'LinkActionDropdown': that.vue_components['component-dropdown'],
                            'CustomButton': that.vue_components['custom-button'],
                        },
                        created: function() {
                                let self = this;
                                self.$editable = that.$target_wrapper.find('.style-wrapper');
                                self.$editable = self.$editable.length ? self.$editable : that.$target_wrapper;
                                //let selection = that.$iframe_wrapper[0].getSelection();
                                //let selection_temp = self.selection.anchorNode.parentElement.attributes;
                                //let selection_arr = self.selection_attr;

                                if (self.block_data.link_props) {
                                    let value = self.block_data.link_props['data-value'];
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
                                    if (value === 'internal-link' ) {
                                        self.selection_attr.active_pages_option = self.block_data.link_props["data-block"];
                                    }
                                    if (value === 'block-link' ) {
                                        self.selection_attr.active_block_option = self.block_data.link_props["data-block"];
                                    }
                                    if (self.block_data.link_props["target"] ) {
                                        self.selection_attr.inputCheckbox = true;
                                    }
                                }
                        },
                        methods: {
                            change: function(option) {
                                let self = this;
                                self.active_data = self.arr_options.filter( function(opt) {
                                    return (opt.value === option.value);
                                })[0];
                                self.show_buttons = true;
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
                                    new_url = self.arr_pages_options.filter( function(option) {
                                        return (option.value === self.selection_attr.active_pages_option);
                                    })[0]['url'];
                                    active_block_attr = self.selection_attr.active_pages_option;
                                }
                                if (active_data.value === 'block-link' ) {
                                    new_url = self.arr_block_options.filter( function(option) {
                                        return (option.value === self.selection_attr.active_block_option);
                                    })[0]['url'];
                                    active_block_attr = self.selection_attr.active_block_option;
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
                                    if (active_block_attr) {
                                        //self.$editable.attr("data-block", active_block_attr);
                                        self.block_data.link_props['data-block'] = active_block_attr;
                                    }
                                    else {
                                        //self.$editable.removeAttr("data-block");
                                        delete self.block_data.link_props['data-block'];
                                    }
                                    self.cleanForm();

                                    $.wa.editor._block_settings_drawer_promise.then(function(bs) {
                                        //self.block_data.html = self.$editable.html();
                                        bs.saveBlockData(self.block_data, false);

                                    });
                                    console.log('saveBlockData', self.block_data)
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
                            },
                            cleanForm: function() {
                                let self = this;
                                self.show_buttons = false;
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
                            const arr_options = that.storage_data[this.group_config.type].values;
                            let active_option = this.block_data?.inline_props?.[form_type];
                            const semi_header = this.group_config.name;
                            const active_icon = that.storage_data[this.group_config.type].icon;
                            const tagsArr = ['B', 'I', 'U', 'STRIKE', 'A', 'FONT']
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
                                    //self.block_data.html = $editable.html();
                                }
                                //that.$iframe_wrapper[0].execCommand('styleWithCSS', false, true);
                                that.$iframe_wrapper[0].execCommand('foreColor', false, '#' + option);
                                //that.$iframe_wrapper[0].execCommand('styleWithCSS', false, true);
                                self.block_data.html = $editable.html();

                                self.active_option = { type: 'self_color', value: '#' + option, name: 'Self color'};
                                /*$.wa.editor._block_settings_drawer_promise.then(function(bs) {
                                    bs.saveBlockData(self.block_data);
                                });
                                console.log('saveBlockData', self.block_data)*/
                            }
                        },
                        mounted: function() {
                            let self = this;
                            let $editable = that.$target_wrapper.find('.style-wrapper');
                            $editable = $editable.length ? $editable : that.$target_wrapper;
                            $editable.on('mouseup', function(e) { // To highlight the buttons on mouseup
                                checkTag(e)
                            })

                            function checkTag(el){
                                let targetEl = $(el.target);
                                let tagN = targetEl.prop("tagName");

                                while (self.tagsArr.indexOf(tagN) >= 0 ) {
                                if (tagN === 'FONT') {
                                    let target_color = targetEl[0].attributes.color.value || false;
                                    if (target_color) {
                                        self.active_option = { type: 'self_color', value: target_color, name: 'Self color'};
                                    }
                                    //break;
                                    return;
                                }
                                targetEl = targetEl.parent();
                                tagN = targetEl.prop("tagName");
                               }
                               self.active_option =  self.block_data?.inline_props?.[self.form_type];
                            }
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
                                    const new_layer = Object.assign({}, self.arr_options.palette[0]);
                                    new_layer.uuid = self.last_uuid; //add uniq layer index
                                    self.layers.push(new_layer)
                                }
                                self.changeCss();
                                //self.change();
                            },
                            changeCss(layer, index) {
                                let self = this;
                                self.backgroundCss = '';
                                //console.log('changeCss',layer, index)
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
                                    const manual_layers = self.layers.filter(function(option) { return option.type !== 'palette'})
                                    $.each(manual_layers, function(i, l) {
                                        self.backgroundCss = self.backgroundCss + l.value + (manual_layers.length-1 > i ? ', ' : '');
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
                            const form_type = that.storage_data[this.group_config.type].type;
                            const arr_options = that.storage_data[this.group_config.type];
                            const active_icon = that.storage_data[this.group_config.type].icon;
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
                                    active_options = temp_options;
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
                                //const oldUid = oldLayer.uuid;
                                //const newUid = newLayer.uuid;
                                //oldLayer.uuid = newUid;
                                //newLayer.uuid = oldUid;
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
                            const arr_options = that.storage_data[this.group_config.type];
                            const selected_element = that.states.selected_element;
                            //const form_type = ["padding-top", "padding-bottom"];
                            let active_options = (this.block_data?.block_props||{});
                            if (selected_element) {
                                active_options = this.block_data?.block_props?.[selected_element]
                            };
                            const semi_headers = that.states.semi_headers ? that.states.semi_headers : 'Inner';
                            const header_name = this.group_config.name;
                            const body_class = 'right';

                            return { selected_element, arr_options, header_name, semi_headers, body_class, active_options}
                          },
                        template: that.templates["component_padding_group"],
                        delimiters: ['{ { ', ' } }'],
                        components: {
                            'PaddingDropdown': that.vue_components['component-dropdown'],
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
                            const semi_headers = that.states.semi_headers ? that.states.semi_headers : 'Inner';
                            const header_name = this.group_config.name;
                            return { selected_element, arr_options, header_name, showChildren, semi_headers, body_class: 'right', active_options}
                          },
                        template: that.templates["component_margin_group"],
                        delimiters: ['{ { ', ' } }'],
                        components: {
                            'MarginDropdown': that.vue_components['component-dropdown'],
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
                            const form_type = that.storage_data[this.group_config.type].type;
                            let image_data = this.block_data?.image ? this.block_data.image : '';
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
                                self.image_data = option.target.files[0].name;
                                $.wa.editor._block_settings_drawer_promise.then(function(bs) {
                                    self.block_data.image = self.image_data;
                                    bs.saveBlockData(self.block_data, false);
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
                                if (option.disabled || !files?.length) { return false; }
                                self.image_data = files[0].name;
                                $.wa.editor._block_settings_drawer_promise.then(function(bs) {
                                    self.block_data.image = files[0].name;
                                    bs.saveBlockData(self.block_data, false);
                                    bs.uploadFile(files[0], '');
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
                            if (that.is_new_block) {
                                $(self.$el).find('label').trigger('click')
                                //that.is_new_block = false;
                            }
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
                            const header_name = this.group_config.name;
                            const selected_tab = 'html';
                            return { selected_tab, arr_options, header_name }
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
                            <div class="code-group-wrapper custom-mb-12 custom-mt-12">
                                <custom-button id="js-save-code" :buttonText="$t('custom.Save')" buttonClass="green blue" @click="saveCode"></custom-button>
                                <span id="wa-editor-status" style="margin-left: 20px; display: none"></span>
                            </div>
                        </div>
                        `,
                        //delimiters: ['{ { ', ' } }'],
                        components: {
                            'CustomButton': that.vue_components['custom-button'],
                          },
                        methods: {
                            changeTab(opt){
                                const self = this;
                                self.selected_tab = opt;
                                let session = wa_editor.getSession();
                                session.setValue(self.block_data[opt]);
                                if (opt == 'css') {
                                    session.setMode("ace/mode/css");
                                } else if (opt == 'js') {
                                    session.setMode("ace/mode/javascript");
                                } else {
                                    session.setMode("ace/mode/smarty");
                                }
                                $button = $(self.$el).find('#js-save-code');
                                $button.removeClass('yellow').addClass('green');
                                
                            },
                            saveCode(opt){
                                const self = this;
                                $button = $(self.$el).find('#js-save-code');
                                let session = wa_editor.getSession();

                                $.wa.editor._block_settings_drawer_promise.then(function(bs) {
                                    self.block_data[self.selected_tab] = session.getValue();
                                    bs.saveBlockData(self.block_data);
                                    $button.removeClass('yellow').addClass('green');
                                    console.log('saveBlockData', self.block_data)
                                });
                                if (self.selected_tab === 'css') {
                                    self.$editable = that.$target_wrapper.siblings('#style-tag');
                                    self.$editable.html(session.getValue())
                                }
                                if (self.selected_tab === 'js') {
                                    self.$editable = that.$target_wrapper.siblings('#script-tag');
                                    let script_string = '$(function() { "use strict";' + session.getValue() + '});'
                                    self.$editable.html(script_string)
                                }
                            }
                        },

                        mounted: function() {
                            const self = this;
                            $html_wrapper = $(self.$el).find('.js-redactor-wrapper');
                            $textarea = $(self.$el).find('.js-content-body');
                            waEditorAceInit({ 'id': 'js-content-body','type': "html", 'save_button': 'js-save-code'});
                            //wa_editor.setOption('fontSize', 14);
                            wa_editor.$blockScrolling = Infinity;
                            wa_editor.setOption('minLines', 25);
                            wa_editor.renderer.setShowGutter(false);
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
                        },
                        template: `
                        <div class="main-button-group custom-mb-32 custom-mt-24">
                            <custom-button buttonClass="light-gray" iconClass="fa-trash-alt" @click="removeBlock"></custom-button>
                            <custom-button buttonClass="light-gray" iconClass="fa-arrow-up" @click="reorderBlocks('up')"></custom-button>
                            <custom-button buttonClass="light-gray" iconClass="fa-arrow-down" @click="reorderBlocks('down')"></custom-button>
                            <custom-button buttonClass="light-gray" iconClass="fa-copy" @click="copyBlock"></custom-button>
                        </div>
                        `,
                        //delimiters: ['{ { ', ' } }'],
                        components: {
                            'CustomButton': that.vue_components['custom-button'],
                          },
                        methods: {
                            removeBlock(){
                                const $block_wrapper = that.$target_wrapper.closest('.js-seq-wrapper');
                                if ($block_wrapper.length) {
                                    $.wa.editor.removeBlock(this.block_id, $block_wrapper);
                                    $.wa.editor._block_settings_drawer_promise.then(function(bs) {
                                        bs.hide();
                                        $.wa.editor.selected_block_id = '';
                                    });
                                }
                            },
                            reorderBlocks(order){
                                const $block_wrapper = that.$target_wrapper.closest('.js-seq-wrapper');
                                const block_id = $block_wrapper.data('block-id');
                                const page_id = $block_wrapper.data('page-id');
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
                                const $block_wrapper = that.$target_wrapper.closest('.js-seq-wrapper');
                                $block_wrapper.css('opacity', 0.5);
                                $(event.target).addClass('disabled');
                                console.log(event.target)
                                $.post('?module=editor&action=addBlock', {
                                    duplicate_block_id: this.block_id
                                }).then(function(new_parent_block_html) {
                                    $block_wrapper.replaceWith(new_parent_block_html);
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
                    <form-header :header="header" :parents="parents" @closeDrawer="close_drawer" @goToParent="goToParent"></form-header>
                    <main-controls-group :key="block_id+media_prop" :block_id="block_id" v-if="!block_data.indestructible"></main-controls-group>
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
                        //that.media_prop = data.media_prop;
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
                    changeElement: function(element) {
                        const self = this;
                        self.selected_element = element;
                        that.states.selected_element = element
                    },
                    goToParent: function(parent_id) {
                        let self = this;
                        updateSelectedBlock(parent_id)
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

            /*function saveBlockData(wrapper, old_block_html, block_data, update) {
                $.wa.editor._block_settings_drawer_promise.then(function(bs) {
                    bs.saveBlockData(wrapper, old_block_html, block_data, update);
                    console.log('saveBlockData', block_data)
                });
            }*/
        };

        return FormConstructor;

})(jQuery);
