
var FormComponents = ( function($) {
    function FormComponents(iframe_wrapper) {
        var that = this;
        that.base_components = {
            'form-header': {
                props: ["header", "parents"],
                emits: ['closeDrawer', 'updateDrawer', 'updateDrawerHor', 'goToParent'],
                computed: {
                    isEmpty: function () {
                       return jQuery.isEmptyObject(this.parents)
                    }
                },
                template: `
                <div class="block-settings-header flexbox full-width custom-mb-16" >
                    <div class="flexbox vertical">
                        <div class="form-breadcrumbs flexbox wrap space-8 custom-mb-4" v-if="!isEmpty">
                            <span v-for="(parent, key, i) in parents" class="text-gray small">
                                <span v-if="i > 0" class="custom-mr-4">/</span>
                                <a href="javascript:void(0)" class="text-gray" @click="$emit('goToParent', parent)">
                                    {{key}}
                                </a>
                            </span>
                        </div>
                        <h5 class="custom-mt-0">{{$t('custom.' + header, header)}}</h5>
                    </div>
                    <a href="javascript:void(0)" @click="$emit('updateDrawerHor')" class="drawer-update js-update-drawer-horizontal custom-ml-auto" :title="$t('custom.Toggle panel position')">
                        <i class="fas fa-sort fa-rotate-90 text-gray icon size-16 custom-p-4"></i>
                    </a>
                    <a href="javascript:void(0)" @click="$emit('updateDrawer')" class="drawer-update js-update-drawer custom-ml-8" :title="$t('custom.Toggle panel size')">
                        <i class="fas fa-sort text-gray icon size-16 custom-p-4"></i>
                    </a>
                    <a href="javascript:void(0)" @click="$emit('closeDrawer')" class="drawer-close js-close-drawer custom-ml-8" :title="$t('custom.Close panel')">
                        <i class="fas fa-times text-gray icon size-16 custom-p-4"></i>
                    </a>
                </div>
                `,
                methods: {
                }
            },
            "custom-button": {
                props: {
                buttonClass: { type: String, default: 'light-gray' },
                buttonText: { type: String },
                iconClass: { type: String },
                selfOption: { type: String },
                activeOptions: { type: Array },
                /*form_type: { type: String },
                block_data: { type: Object, default: {} },
                block_id: { type: Number},*/
                },
                //emits: ["click-event"],
                template: `
                <button class="button smaller" :class="formatted_button_class">
                    <i v-if="iconClass" class="fas" :class="iconClass"></i>
                    <span v-if="buttonText">{{buttonText}}</span>
                </button>`,
                computed: {
                    formatted_button_class: function() {
                        let self = this;
                        let new_class = self.buttonClass;
                        if (self.activeOptions) {
                            if (self.activeOptions.includes(self.selfOption)) {
                                new_class += ' ' + 'black';
                            } else {
                                new_class += ' ' + 'gray';
                            }
                        }
                        return new_class;
                    },
                },
            },
            "component-dropdown": {
                props: {
                    options: { type: Array, default: [] },
                    activeOption: { type: String }, // need to make id
                    activeIcon: { type: String },
                    button_class: { type: String, default: "light-gray smaller" },
                    body_width: { type: String, default: "" },
                    body_class: { type: String, default: "" },
                    disabled: { type: Boolean, default: false },
                    //empty_option: { type: Boolean, default: false },
                    form_type: { type: String },
                    block_data: { type: Object, default: {} },
                    block_id: { type: Number},
                    media_prop: { type: String, default: "" },
                    element: { type: String, default: "" }
                    //semi_header: { type: Boolean, default: false },
                },
                emits: ["change", "customChange"],
                data() {
                    let self = this,
                        active_option = self.options[0],
                        formatted_options = [];

                        $.each(self.options, function(i, option) {
                            formatted_options.push({
                                name: option.name,
                                value: (self.media_prop? option[`value_${self.media_prop}`] : option.value)
                            });
                        });

                        if (self.activeOption) {
                            const activeOptionsArr = self.activeOption.split(' ');
                            var filter_search = formatted_options.filter( function(option) {
                                return activeOptionsArr.includes(option.value);
                            });
                            active_option = (filter_search.length ? filter_search[0] : active_option);
                        }
                        self.$target_wrapper = iframe_wrapper.find('.seq-child > [data-block-id=' + self.block_id + ']');
                        //self.$editable = self.$target_wrapper.find('.style-wrapper');
                        //if (self.element) self.$editable = self.$target_wrapper.find('.' + self.element);
                        //self.$editable = self.$editable.length ? self.$editable : self.$target_wrapper;

                    return { active_option, formatted_options }
                },

                template: $.form_storage.templates["component_dropdown"],
                delimiters: ['{ { ', ' } }'],
                methods: {
                    change: function(option) {
                        let self = this;

                        if (self.form_type === 'custom') {
                            self.$emit("customChange", option);
                            self.active_option = option;
                            return false;
                        }
                        if (self.form_type === 'customColumn') {
                            self.$emit("customChange", option, self.block_id, self.active_option);
                            self.active_option = option;
                            return false;
                        }
                        if (option.disabled) { return false; }
                        let temp_active_option = self.active_option;
                        if (temp_active_option !== option) {
                            if (option === 'removeData' || option.value === 'removeData') {
                                self.active_option = option;
                                $.wa.editor._block_settings_drawer_promise.then(function(bs) {
                                    if (!self.element) {
                                        if (self.block_data.block_props) delete self.block_data.block_props[self.form_type];
                                    } else {
                                        if (self.block_data.block_props[self.element]) delete self.block_data.block_props[self.element][self.form_type];
                                    }
                                    bs.saveBlockData(self.block_data);
                                });
                                console.log('saveBlockData', self.block_data);
                                return;
                            }
                            let temp_active_options = self.block_data.block_props?.[self.form_type]?.split(' ') || [];
                            const classPosition = temp_active_options.indexOf(self.active_option.value); //deep check for media prop classes
                            if ( classPosition >= 0 ) {
                                temp_active_options.splice(classPosition, 1);
                            }
                            temp_active_options.push(option.value);
                            self.active_option = option; //update base
                            $.wa.editor._block_settings_drawer_promise.then(function(bs) {
                                if (!self.element) {
                                    self.block_data.block_props[self.form_type] = temp_active_options.join(' ');
                                } else {
                                    if (!self.block_data.block_props[self.element]) self.block_data.block_props[self.element] = {};
                                    self.block_data.block_props[self.element][self.form_type] = temp_active_options.join(' ');
                                }

                                bs.saveBlockData(self.block_data);
                            });
                            console.log('saveBlockData', self.block_data)
                        }

                    },
                    toggleData: function(option, def) {
                        let self = this;
                        if (option) self.change('removeData'); //remove data from block
                        else self.change(def ? def: self.options[0]); //set default to block
                        //console.log('toggleData', option, self.options[0])
                    }
                },

                mounted: function() {
                    let self = this;
                    if (self.disabled) { return false; }
                    self.dropdown = $(self.$el).waDropdown({
                        hover : false,
                        //items: ".menu > .dropdown-item > a",
                        open: function(dropdown_instance) {
                            //set position of the block depending on the position of the window edge
                            const $dropdown_body = $(self.$el).find('.dropdown-body');
                            alignmentDropdown($dropdown_body);
                        },
                    }).waDropdown("dropdown");
                }
            },
            "component-dropdown-removable": {
                props: {
                    options: { type: Array, default: [] },
                    activeOption: { type: String }, // need to make id
                    activeIcon: { type: String },
                    button_class: { type: String, default: "light-gray smaller" },
                    body_width: { type: String, default: "" },
                    body_class: { type: [String, Object], default: "" },
                    removable: { type: Boolean, default: false },
                    //empty_option: { type: Boolean, default: false },
                    form_type: { type: String },
                    block_data: { type: Object, default: {} },
                    block_id: { type: Number},
                    media_prop: { type: String, default: "" },
                    element: { type: String, default: "" }
                    //semi_header: { type: Boolean, default: false },
                },
                emits: ["change", "customChange"],
                data() {
                    let self = this,
                        //active_option = self.options[0],
                        formatted_options = [];

                    self.activeOptionsArr = self.activeOption?.split(' ') || [];
                    self.sizes_data = $.form_storage.data['media_sizes_data'];

                    $.each(self.options, function(i, option) {
                        if (self.media_prop && !option[`value_${self.media_prop}`]) return;
                        formatted_options.push({
                            name: option.name,
                            value: (self.media_prop ? option[`value_${self.media_prop}`] : option.value)
                        });
                    });
                    self.base_value_index = self.sizes_data.indexOf(formatted_options[0].value.slice(-2));

                    self.is_desktop = !self.media_prop || self.media_prop === 'desktop';
                    self.is_inherited_values = false;
                    self.is_default_value = true;

                    let active_option = updateOption();

                    self.$target_wrapper = iframe_wrapper.find('.seq-child > [data-block-id=' + self.block_id + ']');

                    return { active_option, formatted_options }

                    function updateOption() { //Поиск активного класса либо наследуемого значения
                        if (self.activeOptionsArr.length) { //если массив с классами не пустой
                            var filter_search = formatted_options.filter( function(option) { //вычисляем что в массиве есть класс из текущего медиа набора
                                return self.activeOptionsArr.includes(option.value);
                            });
                            if (filter_search.length) { //если есть, присваиваем как активный
                                self.is_default_value = false;
                                return filter_search[0];
                            }
                            if (self.removable && !self.is_desktop) { //если нет, ищем БЛИЖАЙШИЙ СТАРШИЙ активный класс
                                let first_el = self.getClosestOption(self.activeOptionsArr)

                                if (first_el) { //если находим, получаем его полный объект
                                    let filter_search_inherited = self.options.filter( function(option) {
                                        return Object.values(option).includes(first_el);
                                    });
                                    if (filter_search_inherited.length) {
                                        self.is_inherited_values = true;
                                        return filter_search_inherited[0];
                                    }
                                }
                                return self.options[0];
                            }
                            return self.options[0];
                        }
                        return self.options[0];
                    };
                },

                template: $.form_storage.templates["component_dropdown_removable"],
                delimiters: ['{ { ', ' } }'],
                methods: {
                    change: function(option) {
                        let self = this;

                        if (self.form_type === 'custom') {
                            self.$emit("customChange", option);
                            self.active_option = option;
                            self.is_default_value = false;
                            self.is_inherited_values = false;
                            return false;
                        }
                        if (self.form_type === 'customColumn') {
                            self.$emit("customChange", option, self.block_id, self.active_option);
                            self.active_option = option;
                            self.is_default_value = false;
                            self.is_inherited_values = false;
                            return false;
                        }

                        if (self.active_option !== option) {
                            let temp_active_options = self.block_data.block_props?.[self.form_type]?.split(' ') || [];
                            if (self.element) {
                                temp_active_options = self.block_data.block_props?.[self.element]?.[self.form_type]?.split(' ') || temp_active_options;
                            }
                            const active_media_value = self.media_prop && self.active_option[`value_${self.media_prop}`] ? self.active_option[`value_${self.media_prop}`] : self.active_option.value;
                            const classPosition = temp_active_options.indexOf(active_media_value); //deep check for media prop classes

                            if ( classPosition >= 0 ) temp_active_options.splice(classPosition, 1);

                            if (option === 'removeData') {
                                self.changeRemove(temp_active_options);
                                return
                            }

                            temp_active_options.push(option.value);

                            if (!self.element) {
                                self.block_data.block_props[self.form_type] = temp_active_options.join(' ');
                            } else {
                                if (!self.block_data.block_props[self.element]) self.block_data.block_props[self.element] = {};
                                self.block_data.block_props[self.element][self.form_type] = temp_active_options.join(' ');
                            }
                            $.wa.editor._block_settings_drawer_promise.then(function(bs) {
                                bs.saveBlockData(self.block_data);
                            });
                            console.log('saveBlockData', self.block_data)

                            self.active_option = option;
                            self.is_default_value = false;
                            self.is_inherited_values = false;
                            //key_active = self.media_prop? self.active_option[`value_${self.media_prop}`] : self.active_option.value;
                            //console.log('saveBlockData', self.active_option, temp_active_options)
                        }
                    },
                    changeRemove: function(temp_active_options) {
                        let self = this;
                        if (temp_active_options.length) { //Если есть другие активные значение от других разрешений

                            if (self.removable && !self.is_desktop) { //ищем БЛИЖАЙШИЙ СТАРШИЙ активный класс
                                let first_el = self.getClosestOption(temp_active_options);
                                if (first_el) { //если находим, получаем его полный объект
                                    let filter_search_inherited = self.options.filter( function(opt) {
                                        return Object.values(opt).includes(first_el);
                                    });
                                    if (filter_search_inherited.length) {
                                        self.is_inherited_values = true;
                                        self.active_option = filter_search_inherited[0];
                                    }
                                } else {
                                    self.active_option = self.options[0];
                                    self.is_default_value = true;
                                }
                            }  else {
                                self.active_option = self.options[0]; self.is_default_value = true;
                            }

                            if (!self.element) {
                                self.block_data.block_props[self.form_type] = temp_active_options.join(' ');
                            } else {
                                if (!self.block_data.block_props[self.element]) self.block_data.block_props[self.element] = {};
                                self.block_data.block_props[self.element][self.form_type] = temp_active_options.join(' ');
                            }

                        } else {
                            self.active_option = self.options[0];
                            //self.active_option.value = null;
                            if (!self.is_desktop) self.is_inherited_values = true;
                            self.is_default_value = true;
                            if (!self.element) {
                                if (self.block_data.block_props) delete self.block_data.block_props[self.form_type];
                            } else {
                                if (self.block_data.block_props[self.element]) delete self.block_data.block_props[self.element][self.form_type];
                            }
                        }

                        $.wa.editor._block_settings_drawer_promise.then(function(bs) {
                            bs.saveBlockData(self.block_data);
                        });
                        console.log('saveBlockData', self.block_data);
                    },
                    getClosestOption: function(optionsArr) {
                        let base_value_index = null;
                        let result = null;
                        let self = this;
                        $.each(optionsArr, function(i, opt) {
                            let new_value_index = self.sizes_data.indexOf(opt.slice(-2));
                            if (self.base_value_index > new_value_index) {
                                base_value_index = !base_value_index || base_value_index < new_value_index ? new_value_index : base_value_index;
                                result = opt;
                            };
                        })
                        return result;
                    },
                    toggleData: function(option, def) {
                        let self = this;
                        if (option) self.change('removeData'); //remove data from block
                        else self.change(def ? def: self.options[0]); //set default to block
                    }
                },

                mounted: function() {
                    let self = this;
                    self.dropdown = $(self.$el).waDropdown({
                        hover : false,
                        //items: ".menu > .dropdown-item > a",
                        open: function(event, target, dropdown) {
                            const $dropdown_body = $(self.$el).find('.dropdown-body');
                            alignmentDropdown($dropdown_body);
                        },
                        change: function(event, target, dropdown) {
                            //event.preventDefault();
                            //dropdown.$menu.find('.selected').removeClass('selected');
                            //$(target).parent().addClass('selected');
                        }
                    }).waDropdown("dropdown");
                }
            },
            "component-switch": {
                props: ["activeValue", "textValue", "disabled", "switchClass", "styleData", "activeName"],
                emits: ["changeSwitch"],
                template: `
                <div class="switch-with-text" v-if="textValue">
                    <span class="switch" :class="switchClass" :id="activeName" :style="styleData">
                        <input type="checkbox" name="" :id="activeName + '-input'">
                    </span>
                    <label :for="activeName + '-input'">{ { textValue } }</label>
                </div>
                <span v-else class="switch" :class="switchClass" id="switch-custom" style="font-size: .6rem;">
                    <input type="checkbox" name="" id="switch-custom-input">
                </span>
                `,
                delimiters: ['{ { ', ' } }'],
                mounted: function() {
                    var self = this;
                    let $switch_wrapper = $(self.$el).hasClass('switch') ? $(self.$el) : $(self.$el).find('.switch');
                    $.waSwitch({
                        $wrapper: $switch_wrapper,
                        active: !!self.activeValue,
                        disabled: !!self.disabled,
                        change: function(active, wa_switch) {
                            self.$emit("changeSwitch", active);
                        }
                    });
                }
            },
            "component-toggle": {
                props: {
                    options: { type: Array, default: [] },
                    activeOption: { type: String }, // need to make id
                    toggleClass: { type: String, default: 'small' },
                    with_text: { type: Boolean, default: false },
                    form_type: { type: String },
                    block_data: { type: Object, default: {} },
                    block_id: { type: Number},
                },
                emits: ["change", "changeToggle"],
                data() {
                    let self = this,
                        active_option = {};

                        if (self.activeOption) {
                            var filter_search = self.options.filter( function(option) {
                                return (option.value === self.activeOption);
                            });
                            active_option = (filter_search.length ? filter_search[0] : active_option);
                        }

                    return { active_option }
                },
                /*computed: {
                    active_option: function() {
                        const self = this;
                        let result = {};
                        if (self.activeOption) {
                            var filter_search = self.options.filter( function(option) {
                                return (option.value === self.activeOption);
                            });
                            result = (filter_search.length ? filter_search[0] : result);
                        }

                        return result;
                    }
                },*/
                template: $.form_storage.templates["component_toggle"],
                delimiters: ['{ { ', ' } }'],
                mounted: function() {
                    var self = this;
                    $(self.$el).waToggle({
                        use_animation: false,
                        change: function(event, target) {
                            self.$emit("change", $(target).data("id"));
                        }
                    });
                },
                created() {

                  },
                methods: {
                    change: function(option) { //put classes to remove in temp_active_option
                        let self = this;
                        if (self.form_type === 'custom') {
                            self.$emit("changeToggle", option);
                            self.active_option = option;
                            return false;
                        }
                        let temp_active_option = self.active_option;
                        if (temp_active_option !== option) {
                            if (temp_active_option.value) {
                                self.$emit("change", option);
                            }
                            self.active_option = option;
                            //self.$emit("update:activeOption", option);
                            $.wa.editor._block_settings_drawer_promise.then(function(bs) {
                                self.block_data.block_props[self.form_type] = option.value;
                                bs.saveBlockData(self.block_data);
                            });
                            console.log('saveBlockData', self.block_data)
                        }
                    },
                    resetForm: function(opt) {
                        let self = this;
                        self.active_option = self.options.filter( function(option) {
                            return (option.value === opt);
                        })[0];
                      }
                },
            },
            'component-color-picker': {
                props: {
                    defaultColor: { type: String },
                },
                emits: ["change", "changeColor"],
                data() {
                    let self = this;
                    let colorPicker = false;
                    let inputColor = self.defaultColor;
                    return { inputColor, colorPicker }
                },
                template:
                `<div class="manual-color-container">
                    <div class="color-picker-container">
                        <span class="circle s-color-picker js-color-picker size-32" data-color="inputColor">
                            <i class="fas fa-fill-drip"></i>
                        </span>
                    </div>
                    <input type="hidden" name="color-hidden" v-model.trim="inputColor" class="shortest js-color-value">
                </div>
                `,
                delimiters: ['{ { ', ' } }'],
                methods: {
                    change() {
                        const self = this;
                        self.$emit("changeColor", self.inputColor);
                    },
                },
                mounted: function() {
                    const self = this;
                    self.$wrapper = $(self.$el);
                    if (!self.colorPicker) initColorPicker(self);

                }
            },
        };
        that.manual_components = {

            'component-manual-gradient': {
                props: {
                    options: { type: Object, default: {} },
                    //activeOption: { type: Object },
                    //defaultColor: { type: String },
                    //showAddButton: { type: Boolean },
                },
                emits: ["change", "changeColor", "addLayer", "removeLayer"],
                data() {
                    let self = this;
                    let colorPicker = false;

                    let activeUnits = self.options;
                    //let activeStops = self.options.stops;
                    let activeStops = Array.isArray(self.options.stops) ? (self.options.stops).slice(0) : Object.values(self.options.stops);
                    //let inputColor = activeUnits.stops[0].color;
                    let gradientTypeOptions = $.form_storage.data['gradient_type_data'];
                    return { activeStops, colorPicker, activeUnits, gradientTypeOptions }
                },

                template:
                `<div class="manual-color-container">
                    <div class="gradient-container flexbox wrap space-24 full-width custom-pb-0">
                        <div  class="flexbox middle space-4 width-100">
                            <gradient-type-dropdown class="width-60" @customChange="change('type', $event.value)" :options="gradientTypeOptions" :activeOption="activeUnits.type" form_type="custom" button_class="light-gray small" body_class="width-auto"></gradient-type-dropdown>
                            <div class="flexbox middle space-4 width-50" v-show="activeUnits.type === 'linear-gradient'">
                                <input @change="change('degree', $event.target.value)" :value="activeUnits.degree + '°'" class="small width-50 custom-mx-0" type="text" name="input-degree" placeholder="0">
                                <a href="javascript:void(0)" class="button light-gray small width-50" type="button" @click="change('incDegree')" :title="$t('custom.increase degree')">
                                    <span class="s-icon" ><i class="fas fa-retweet"></i></span>
                                </a>
                            </div>
                        </div>
                        <div v-for="(stop, i) in activeStops" key="i" class="stops-container flexbox wrap middle space-8 width-100">
                            <div class="flexbox middle full-width">
                                <span class="s-name text-gray bold">{ { +i + 1 } }{ { $t('custom.-point') } }</span>
                                <span class="width-30 custom-ml-auto">
                                    <input @change="changeStops('stop', $event.target.value, i)" :value="stop.stop + '%'" class="small width-100 custom-mx-0" type="text" name="input-stop" placeholder="0">
                                    <!--<span class="text-gray">%</span>-->
                                </span>
                                <a href="javascript:void(0)" class="button small light-gray custom-ml-8" type="button" @click.stop="removeLayer(i)" v-if="activeStops.length > 2">
                                    <span class="s-icon icon" ><i class="fas fa-trash-alt"></i></span>
                                    <!--<span class="s-name">{ { $t('custom.Delete point') } }</span>-->
                                </a>
                            </div>
                            <manual-color class="width-100" @changeColor="changeStops('color', $event, i)" :defaultColor="stop.color"></manual-color>
                            <div class="button light-gray width-100 custom-mb-4 custom-mx-0" type="button" @click.stop="addLayer" v-if="i === (activeStops.length - 1)">
                                <span class="s-icon icon custom-mr-4" ><i class="fas fa-plus"></i></span>
                                <span class="s-name">{ { $t('custom.Add point') } }</span>
                            </div>
                        </div>
                    </div>
                </div>
                `,
                delimiters: ['{ { ', ' } }'],
                methods: {
                    change(key, option) {
                        const self = this;
                        if(key === 'incDegree') {
                            let new_val = (+self.activeUnits['degree'] + 90) > 360 ? 0 : +self.activeUnits['degree'] + 90;
                            self.activeUnits['degree'] = new_val;
                        } else if (key === 'degree') {
                            let new_val = option.replace(/\D/g, '');
                            self.activeUnits[key] = new_val;
                        }
                        else {self.activeUnits[key] = option;}
                        self.updateLayer();
                    },
                    changeStops(key, option, index) {
                        const self = this;
                        self.activeStops[index][key] = (key === 'color') ? '#' + option : option.replace(/\D/g, '');
                        self.activeUnits.stops = self.activeStops;
                        self.updateLayer();
                    },
                    updateLayer() {
                        const self = this;
                        let stopsCss = '';
                        const stops = Array.isArray(self.activeStops) ? self.activeStops : Object.values(self.activeStops);
                        $.each(stops, function(i, stop) {
                            stopsCss = stopsCss + ' ' + stop.color + ' ' + stop.stop + '%' + (stops.length-1 > i ? ', ' : '');
                        })
                        //gradient: {type: 'linear-gradient', degree: '90', stops: [{color: "#FFFFFF00", stop: "0"}, {color: "#FFFFFF00", stop: "100"}]}
                        const gradientCss = self.activeUnits.type + '(' + ((self.activeUnits.type === 'linear-gradient') ? self.activeUnits.degree + 'deg' : 'circle') + ', ' + stopsCss + ')'
                        self.activeUnits.stops = stops;
                        self.$emit("changeColor", gradientCss, self.activeUnits);
                    },
                    addLayer(option) {
                        const self = this;
                        const new_layer = Object.assign({}, self.activeStops[self.activeStops.length - 1]);
                        //new_layer.uuid = ++self.last_uuid; //add uniq layer index
                        self.activeStops.push(new_layer);
                        self.updateLayer();

                    },
                    removeLayer(index) {
                        const self = this;
                        self.activeStops.splice(index, 1);
                        self.updateLayer();

                    },
                },
                components: {
                    'ManualColor': this.base_components['component-color-picker'],
                    'GradientTypeDropdown': this.base_components['component-dropdown'],
                    'CustomButton': this.base_components['custom-button'],
                },
                /*mounted: function() {
                    const self = this;
                    self.$wrapper = $(self.$el);
                }*/
            },
            'component-manual-image': {
                props: {
                    option: { type: Object, default: {} },
                },
                emits: ["change", "changeImg"],
                data() {
                    let self = this;
                    let active_options = Object.assign({}, self.option);
                    let toggle_options = $.form_storage.data['image_toggle_data'];
                    let image_data = active_options.file_name ? active_options.file_name : '';
                    let toggle_arr = Object.keys(toggle_options);
                    $.each(toggle_arr, function(i, d) {
                        if (!active_options[d]) active_options[d] = toggle_options[d][1].value;
                    })
                    return { image_data, toggle_options, active_options }
                },

                template:
                `<div class="flexbox vertical space-12 manual-image-container">
                    <div class="flexbox vertical space-4 image-upload">
                        <div class="text-gray small">{ { $t('custom.Image') } }</div>
                        <div id="drop-area" @drop.stop.prevent="dropImage($event)">
                            <div class="upload s-small" >
                                <label class="link">
                                    <span class="button width-100 light-gray custom-mr-0 custom-mb-4" ><i class="fas fa-image"></i> { { option.file_name ? $t('custom.Change image') : $t('custom.Add image') } }</span>
                                    <input name="namespace" type="file" autocomplete="off" @change="changeImage($event)" accept="image/*">
                                </label>
                                <span v-if="image_data" class="filename hint kek">{ { image_data } }</span>
                            </div>
                        </div>
                    </div>
                    <div class="flexbox vertical space-4">
                        <div class="text-gray small">{ { $t('custom.Filling the space') } }</div>
                        <option-toggle @changeToggle="changeOption" :options="toggle_options.space" :activeOption="active_options.space" form_type="custom"></option-toggle>
                    </div>
                    <div class="flexbox vertical space-4" v-show="active_options.space !== 'cover'">
                        <div class="text-gray small">{ { $t('custom.X-axis alignment') } }</div>
                        <option-toggle @changeToggle="changeOption" :options="toggle_options.alignmentX" :activeOption="active_options.alignmentX" form_type="custom"></option-toggle>
                    </div>
                    <div class="flexbox vertical space-4" v-show="active_options.space !== 'contain no-repeat'">
                        <div class="text-gray small">{ { $t('custom.Y-axis alignment') } }</div>
                        <option-toggle @changeToggle="changeOption" :options="toggle_options.alignmentY" :activeOption="active_options.alignmentY" form_type="custom"></option-toggle>
                    </div>
                </div>
                `,
                delimiters: ['{ { ', ' } }'],
                components: {
                    'OptionToggle': this.base_components['component-toggle'],
                },
                methods: {
                    changeImage: function(option) {
                        const self = this;
                        if (!self.active_options.file_url) {
                            self.active_options.type = 'image';
                            self.active_options.name = 'Image';
                            self.active_options.css = '';
                        }
                        if (option.target.files?.length) {
                            //let file_url = '';
                            self.active_options.file_name = option.target.files[0].name;
                            $.wa.editor._block_settings_drawer_promise.then(function(bs) {
                                const file_promise = bs.uploadFile(option.target.files[0], '');
                                file_promise.then(function(url) {
                                    self.active_options.file_url = url;
                                    self.active_options.value = self.changeValue();
                                    self.$emit("changeImg", self.active_options);
                                    return
                                });
                            });
                         }
                    },
                    dropImage: function(option) {
                        const self = this;
                        const files = $(self.$el).find("#drop-area").data('upload')?.files;
                        /*
                        if (option.disabled || !files?.length) { return false; }

                        $.wa.editor._block_settings_drawer_promise.then(function(bs) {
                            self.block_data.image = files[0].name;
                            bs.saveBlockData(self.block_data, false);
                            bs.uploadFile(files[0], '');
                        });*/
                    },
                    changeOption(option) {
                        let self = this;
                        self.active_options[option.key] = option.value;

                        if (self.active_options.file_url) {
                            self.active_options.value = self.changeValue();
                            self.$emit("changeImg", self.active_options);
                        }
                        //self.active_toggle_option = option.value;
                    },
                    changeValue() {
                        let self = this;
                        let space_value = self.active_options.space !== 'contain' ? ' / ' + self.active_options.space : '';
                        let temp_value = self.active_options.alignmentX + ' ' + self.active_options.alignmentY + space_value + ' url(' + self.active_options.file_url + ')'
                        return temp_value;
                    },
                },

                mounted: function() {
                    const self = this;
                    self.$wrapper = $(self.$el);

                    self.$wrapper.find("#drop-area").waUpload({
                        is_uploadbox: true,
                        show_file_name: true
                    })
                }
            },
            'component-manual-shadow': {
                props: {
                    unit_options: { type: Object, default: {} },
                    activeOption: { type: Object },
                    option: { type: Object },
                    layerIndex: { type: Number },
                    form_type: { type: String },
                },
                emits: ["change", "changeCssShadow"],
                data() {
                    const self = this;
                    let colorPicker = false;

                    let active_option = $.extend(true, {}, self.activeOption); // Copy object recursively

                    if (active_option.type !== 'self_color') { //set default shadow units
                        active_option = self.option;
                    }

                    let shadowOffset = active_option.offset;
                    let activeUnits = active_option.units;
                    let inputColor = (active_option.css).split('#')[1];
                    const is_box_shadow = self.form_type === 'box-shadow';
                    return { activeUnits, shadowOffset, inputColor, colorPicker, is_box_shadow}
                },

                template:
                `<div class="manual-color-container">
                    <div class="shadow-container flexbox space-12 wrap full-width custom-pb-16">
                        <div class="flexbox space-4 wrap middle">
                            <span class="text-gray" >{ { $t('custom.X axis') } }</span>
                            <div  class="flexbox space-4 middle width-100">
                                <input @input="changeInput('xaxis', $event.target.value)" :value="shadowOffset.xaxis" class="width-40 small" type="text" name="input-x-axis" placeholder="0">
                                <width-unit-dropdown @customChange="changeUnit('xaxis', $event.value)" :options="unit_options" :activeOption="activeUnits.xaxis" form_type="custom" button_class="light-gray small" body_class="right width-auto"></width-unit-dropdown>
                            </div>
                        </div>
                        <div class="flexbox space-4 wrap middle">
                            <span class="text-gray" >{ { $t('custom.Y axis') } }</span>
                            <div  class="flexbox space-4 middle width-100">
                                <input @input="changeInput('yaxis', $event.target.value)" :value="shadowOffset.yaxis" class="width-40 small" type="text" name="input-y-axis" placeholder="0">
                                <width-unit-dropdown @customChange="changeUnit('yaxis', $event.value)" :options="unit_options" :activeOption="activeUnits.yaxis" form_type="custom" button_class="light-gray small" body_class="right width-auto"></width-unit-dropdown>
                            </div>
                        </div>
                        <div class="flexbox space-4 wrap middle">
                            <span class="text-gray">{ { $t('custom.Blur') } }</span>
                            <div  class="flexbox middle space-4">
                                <input @input="changeInput('blur', $event.target.value)" :value="shadowOffset.blur" class="width-40 small" type="text" name="input-blur" placeholder="0">
                                <width-unit-dropdown @customChange="changeUnit('blur', $event.value)" :options="unit_options" :activeOption="activeUnits.blur" form_type="custom" button_class="light-gray small" body_class="right width-auto"></width-unit-dropdown>
                            </div>
                        </div>
                        <div class="flexbox space-4 wrap middle" v-if="is_box_shadow">
                            <span class="text-gray">{ { $t('custom.Spread') } }</span>
                            <div  class="flexbox middle space-4 width-100">
                                <input @input="changeInput('spread', $event.target.value)" :value="shadowOffset.spread" class="width-40 small" type="text" name="input-spread" placeholder="0">
                                <width-unit-dropdown @customChange="changeUnit('spread', $event.value)" :options="unit_options" :activeOption="activeUnits.spread" form_type="custom" button_class="light-gray small" body_class="right width-auto"></width-unit-dropdown>
                            </div>
                        </div>
                    </div>
                    <div class="color-picker-container">
                        <div class="text-gray custom-mb-8">{ { $t('custom.ColorAndTransition') } }</div>
                        <span class="circle s-color-picker js-color-picker size-32 " data-color="inputColor">
                            <i class="fas fa-fill-drip"></i>
                        </span>
                    </div>
                    <input type="hidden" name="color-hidden" v-model.trim="inputColor" class="shortest js-color-value">
                </div>
                `,
                components: {
                    'WidthUnitDropdown': this.base_components['component-dropdown'],
                },
                delimiters: ['{ { ', ' } }'],
                methods: {
                    updateLayer() {
                        const self = this;
                        let offset = self.shadowOffset;
                        let unit = self.activeUnits;
                        let spreadValue = self.is_box_shadow ? ' ' + offset.spread + unit.spread : '';
                        const shadowCss = offset.xaxis + unit.xaxis + ' ' + offset.yaxis + unit.yaxis + ' ' +  offset.blur + unit.blur + spreadValue + ' #' + self.inputColor;
                        self.layer = { type: 'self_color', value: shadowCss, css: '#' + self.inputColor, units: unit, offset: offset};
                        self.$emit("changeCssShadow", self.layer, +self.layerIndex)
                    },
                    change() {
                        const self = this;
                        self.updateLayer();
                    },
                    changeUnit(key, value) {
                        const self = this;
                        self.activeUnits[key] = value;
                        self.updateLayer();
                    },
                    changeInput(key, value) {
                        const self = this;
                        self.shadowOffset[key] = value;
                        self.updateLayer();
                    }
                },

                mounted: function() {
                    const self = this;
                    self.$wrapper = $(self.$el);
                    if (!self.colorPicker) initColorPicker(self);
                }
            },
        }
        that.custom_components = {
            'component-text-color-dropdown': {
                props: {
                    options: { type: Object, default: {} },
                    activeOption: { type: Object, default: {} },
                    activeIcon: { type: String },
                    button_class: { type: String, default: "" },
                    body_class: { type: String, default: "" },
                    form_type: { type: String },
                    block_data: { type: Object, default: {} },
                    block_id: { type: Number},
                },
                emits: ["change", "focus", "blur", "changeColor", "changePalette"],
                data() {
                    let self = this,
                    active_option = self.options.values[0],
                    palette_options = Object.assign({}, self.options['palette']),
                    active_toggle_option = 'self_color',
                    scheme_options = Object.assign({}, palette_options['scheme']),
                    colors_variables_data = $.form_storage.data['colors_variables_data'];
                    delete palette_options['scheme'];
                    if (!jQuery.isEmptyObject(palette_options)) active_toggle_option = 'palette';
                    let active_scheme = 'complementary'
                    let colorPicker = false;
                    if (self.activeOption.value) {
                        active_option = self.activeOption;
                        active_toggle_option = self.activeOption.type;
                        active_scheme = active_option.scheme ? active_option.scheme : active_scheme;
                    } else {
                        let selection = iframe_wrapper[0].getSelection();
                        //console.log(selection.baseNode.parentElement.nodeName, selection.baseNode.parentElement.color)
                        if (selection.baseNode?.parentElement?.nodeName === 'FONT') {
                            let color = selection.baseNode.parentElement.color;
                            if (color) {
                                active_option = { type: 'self_color', value: color, name: 'Self color'};
                            }
                        }
                    }
                    let style_t = active_toggle_option === 'self_color' ? 'color:' + active_option.value : 'color:' + 'var(' + colors_variables_data[active_option.value] + ')';
                    //console.log('complementary', active_toggle_option, active_option, style_t)

                    let toggle_options = $.form_storage.data.color_toggle_data;

                    let inputColor = (active_option.value).split('#')[1] || '000';
                    return { active_scheme, colors_variables_data, palette_options, scheme_options, active_option, inputColor, toggle_options, active_toggle_option, style_t, colorPicker}
                },
                computed: {
                    isEmptyPalette: function () {
                       return jQuery.isEmptyObject(this.palette_options)
                    }
                },
                template:
                `<div class="dropdown color-dropdown">
                    <button class="dropdown-toggle button light-gray smaller" type="button" :class="button_class">
                        <span class="s-icon icon custom-mr-4" :key="active_option.value"><i class="s-bordered-icon fas" :class="activeIcon" :style="style_t" ></i></span>
                        <span class="s-name">{ { $t('custom.'+active_option.name, active_option.name) } }{ { active_option.type ? ': '+active_option.value : '' } }</span>
                    </button>
                    <div class="dropdown-body" :class="body_class">
                        <div class="box custom-px-16 custom-py-12">
                            <ul class="menu custom-my-4">
                                <li>
                                    <color-toggle v-if="!isEmptyPalette" @changeToggle="changeToggle" :options="toggle_options" :activeOption="active_toggle_option" :with_text="true" form_type="custom"></color-toggle>
                                    <br>
                                    <div class="flexbox space-12 vertical" v-show="active_toggle_option == 'palette'">
                                        <div class="flexbox space-8 wrap custom-pb-4" >
                                            <div class="width-100 flexbox full-width custom-pb-4 middle">
                                                <span class="s-name text-gray">
                                                    { { $t('custom.' + active_scheme, active_scheme) } }
                                                </span>
                                                <div class="s-controls flexbox">
                                                    <span @click="changeScheme(0)" class="icon text-gray custom-pl-12 cursor-pointer">
                                                        <i class="fas fa-arrow-circle-left"></i>
                                                    </span>
                                                    <span @click="changeScheme(1)" class="icon text-gray custom-pl-12 cursor-pointer">
                                                        <i class="fas fa-arrow-circle-right"></i>
                                                    </span>
                                                </div>
                                            </div>
                                            <span class="s-icon-wrapper" v-for="color in scheme_options[active_scheme]" @click="changePalette(color, active_scheme, true)" :key="color">
                                                <span class="s-icon icon size-32" :class="{'selected': active_option.value === color}"><i class=" s-bordered-icon fas" :class="activeIcon" :style="{ color: 'var(' + colors_variables_data[color] + ')' }"></i></span>
                                                <span class="s-selected-icon icon size-10 text-white" v-if="active_option.value === color"><i class="fas fa-check"></i></span>
                                            </span>
                                        </div>
                                        <div v-for="(palette, key) in palette_options" class="flexbox space-8 wrap">
                                            <div class="s-name width-100 text-gray custom-pb-4 ">{ { $t('custom.' + key, key) } }</div>
                                            <span class="s-icon-wrapper" v-for="color in palette" @click="changePalette(color, key)">
                                                <span class="s-icon icon size-32" :class="{'selected': active_option.value === color}"><i class="s-bordered-icon fas" :class="activeIcon" :style="{ color: 'var(' + colors_variables_data[color] + ')' }"></i></span>
                                                <span class="s-selected-icon icon size-10 text-white" v-if="active_option.value === color"><i class="fas fa-check"></i></span>
                                            </span>
                                        </div>
                                    </div>
                                    <div v-show="active_toggle_option !== 'palette'">
                                        <div class="gradient-container flexbox full-width custom-pb-16">
                                            <span class="text-gray" >{ { $t('custom.Self color') } }</span>
                                            <!--<switch-toggle @changeSwitch="changeSwitch" :activeName="form_type" :textValue="$t('custom.Gradient')" switchClass="smaller" styleData="font-size: .6rem;"></switch-toggle>-->
                                        </div>
                                        <div class="color-picker-container">
                                            <span class="circle s-color-picker js-color-picker size-32 " data-color="inputColor">
                                                <i class="fas fa-fill-drip"></i>
                                            </span>
                                        </div>
                                        <input type="hidden" name="color-hidden" v-model.trim="inputColor" class="shortest js-color-value">

                                        <!--<input v-model.trim="inputColor" class="width-100" type="text" name="color">
                                        <br>
                                        <br>
                                        <button class="button gray small save-button" type="button" v-on:click="change">
                                            <span class="s-name">Save</span>
                                        </button>
                                        <span class="button light-gray small custom-ml-4"><i class="fas fa-times"></i></span>-->
                                    </div>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
                `,
                delimiters: ['{ { ', ' } }'],
                methods: {
                    change(option) {
                        let self = this;

                        let temp_active_option = { type: 'self_color', value: '#' + self.inputColor, name: 'Self color'};
                        self.active_option = temp_active_option;
                        self.style_t = temp_active_option.type === 'self_color' ? 'color:' + temp_active_option.value : 'color:' + 'var(' + self.colors_variables_data[temp_active_option.value] + ')';

                        self.$emit("changeColor", self.inputColor);
                    },
                    changeScheme(option) {
                        let self = this;
                        const scheme_options_arr = Object.keys(self.scheme_options);
                        const arr_ln = scheme_options_arr.length;
                        let active_index = scheme_options_arr.indexOf(self.active_scheme);
                        let new_active_scheme_index = option ? active_index + 1 : active_index - 1;
                        self.active_scheme = scheme_options_arr[(new_active_scheme_index % arr_ln + arr_ln) % arr_ln];
                    },
                    changePalette(option, category, scheme = false) {
                        let self = this;
                        const $dropdown = $(self.$el);
                        const $dropdown_toggle = $dropdown.find('> .dropdown-toggle');
                        let temp_active_option = {name: 'Palette', value: option, type: 'palette'};
                        if (scheme) temp_active_option.scheme = category;
                        if (self.active_option === temp_active_option) return;
                        self.active_option = temp_active_option;
                        self.style_t = temp_active_option.type === 'self_color' ? 'color:' + temp_active_option.value : 'color:' + 'var(' + self.colors_variables_data[temp_active_option.value] + ')';
                        self.$emit("changePalette", temp_active_option);
                    },
                    changeToggle(option) {
                        let self = this;
                        self.active_toggle_option = option.value;
                        if (option.value !== 'palette') {
                        }
                    },
                    changeSwitch(option) {
                        let self = this;
                        console.log('change gradient: ', option)
                    },
                },
                components: {
                    'ColorToggle': that.base_components['component-toggle'],
                    'SwitchToggle': that.base_components['component-switch'],
                },
                mounted: function() {
                    let self = this;
                    self.$wrapper = $(self.$el);

                    self.dropdown = self.$wrapper.waDropdown({
                        hover : false,
                        items: ".menu > li > .save-button",
                        update_title: false,
                        open: function(event, target, dropdown) {
                            if (!self.colorPicker) initColorPicker(self);
                            const $dropdown_body = $(self.$el).find('.dropdown-body');
                            alignmentDropdown($dropdown_body);
                        },

                        change: function(event, target, dropdown) {
                            event.preventDefault();
                        }
                    }).waDropdown("dropdown");
                }
            },
            'component-button-color-dropdown': {
                props: {
                    options: { type: Object, default: {} },
                    activeOption: { type: Object, default: {} },
                    activeIcon: { type: String, default: 'fa-circle' },
                    button_class: { type: String, default: "" },
                    body_class: { type: String, default: "" },
                    form_type: { type: String },
                    block_data: { type: Object, default: {} },
                    block_id: { type: Number},
                },
                emits: ["change", "changeColor", "changePalette"],
                data() {
                    let self = this,
                    active_option = self.options.values[0],
                    palette_options = Object.assign({}, self.options['palette']),
                    active_toggle_option = 'self_color',
                    scheme_options = Object.assign({}, palette_options['scheme']),
                    colors_variables_data = $.form_storage.data['colors_variables_data'];
                    delete palette_options['scheme'];
                    if (!jQuery.isEmptyObject(palette_options)) active_toggle_option = 'palette';
                    let active_scheme = 'complementary'
                    let colorPicker = false;
                    if (self.activeOption.value) {
                        active_option = self.activeOption;
                        active_toggle_option = self.activeOption.type;
                        active_scheme = active_option.scheme ? active_option.scheme : active_scheme;
                    }
                    //console.log('complementary', active_toggle_option, palette_options, palette_options.length)
                    //let style_t = active_toggle_option === 'self_color' ? 'color:' + active_option.value : 'color:' + 'var(' + colors_variables_data[active_option.value] + ')';
                    let toggle_options = $.form_storage.data.color_toggle_data;

                    let inputColor = (active_option.value).split('#')[1] || '000';
                    return { active_scheme, colors_variables_data, palette_options, scheme_options, active_option, inputColor, toggle_options, active_toggle_option, colorPicker}
                },
                computed: {
                    isEmptyPalette: function () {
                       return jQuery.isEmptyObject(this.palette_options)
                    }
                },
                template:
                `<div class="dropdown color-dropdown">
                    <button class="dropdown-toggle s-toggle-icon-wrapper button light-gray smaller" type="button" :class="button_class">
                        <span class="s-icon icon custom-mr-4" ><i class="s-bordered-icon fas" :class="[activeIcon, active_option.value]"></i></span>
                        <span class="s-name">{ { $t('custom.'+active_option.name, active_option.name) } }{ { active_option.type ? ': '+active_option.value : '' } }</span>
                    </button>
                    <div class="dropdown-body" :class="body_class">
                        <div class="box custom-px-16 custom-py-12">
                            <ul class="menu custom-my-4">
                                <li>
                                    <color-toggle v-if="!isEmptyPalette && 0" @changeToggle="changeToggle" :options="toggle_options" :activeOption="active_toggle_option" :with_text="true" form_type="custom"></color-toggle>
                                    <br>
                                    <div class="flexbox space-12 vertical" v-show="active_toggle_option == 'palette'">
                                        <div class="flexbox space-8 wrap custom-pb-4" >
                                            <div class="width-100 flexbox full-width custom-pb-4 middle">
                                                <span class="s-name text-gray">
                                                    { { $t('custom.' + active_scheme, active_scheme) } }
                                                </span>
                                                <div class="s-controls flexbox">
                                                    <span @click="changeScheme(0)" class="icon text-gray custom-pl-12 cursor-pointer">
                                                        <i class="fas fa-arrow-circle-left"></i>
                                                    </span>
                                                    <span @click="changeScheme(1)" class="icon text-gray custom-pl-12 cursor-pointer">
                                                        <i class="fas fa-arrow-circle-right"></i>
                                                    </span>
                                                </div>
                                            </div>
                                            <span class="s-icon-wrapper" v-for="color in scheme_options[active_scheme]" @click="changePalette(color, active_scheme, true)" :key="color">
                                                <span class="s-icon icon size-32" :class="{'selected': active_option.value === color}"><i class="s-bordered-icon fas" :class="[activeIcon, color]"></i></span>
                                                <span class="s-selected-icon icon size-10 text-white" v-if="active_option.value === color"><i class="fas fa-check"></i></span>
                                            </span>
                                        </div>
                                        <div v-for="(palette, key) in palette_options" class="flexbox space-8 wrap">
                                            <div class="s-name width-100 text-gray custom-pb-4 ">{ { $t('custom.' + key, key) } }</div>
                                            <span class="s-icon-wrapper" v-for="color in palette" @click="changePalette(color, key)">
                                                <span class="s-icon icon size-32" :class="{'selected': active_option.value === color}"><i class="s-bordered-icon fas" :class="[activeIcon, color]" ></i></span>
                                                <span class="s-selected-icon icon size-10 text-white" v-if="active_option.value === color"><i class="fas fa-check"></i></span>
                                            </span>
                                        </div>
                                    </div>
                                    <div v-show="active_toggle_option !== 'palette'">
                                        <div class="gradient-container flexbox full-width custom-pb-16">
                                            <span class="text-gray" >{ { $t('custom.Self color') } }</span>
                                            <!--<switch-toggle @changeSwitch="changeSwitch" :activeName="form_type" :textValue="$t('custom.Gradient')" switchClass="smaller" styleData="font-size: .6rem;"></switch-toggle>-->
                                        </div>
                                        <div class="color-picker-container">
                                            <span class="circle s-color-picker js-color-picker size-32 " data-color="inputColor">
                                                <i class="fas fa-fill-drip"></i>
                                            </span>
                                        </div>
                                        <input type="hidden" name="color-hidden" v-model.trim="inputColor" class="shortest js-color-value">

                                        <!--<input v-model.trim="inputColor" class="width-100" type="text" name="color">
                                        <br>
                                        <br>
                                        <button class="button gray small save-button" type="button" v-on:click="change">
                                            <span class="s-name">Save</span>
                                        </button>
                                        <span class="button light-gray small custom-ml-4"><i class="fas fa-times"></i></span>-->
                                    </div>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
                `,
                delimiters: ['{ { ', ' } }'],
                methods: {
                    change(option) {
                        let self = this;
                        self.$emit("changeColor", self.inputColor);
                    },
                    changeScheme(option) {
                        let self = this;
                        const scheme_options_arr = Object.keys(self.scheme_options);
                        const arr_ln = scheme_options_arr.length;
                        let active_index = scheme_options_arr.indexOf(self.active_scheme);
                        let new_active_scheme_index = option ? active_index + 1 : active_index - 1;
                        self.active_scheme = scheme_options_arr[(new_active_scheme_index % arr_ln + arr_ln) % arr_ln];
                    },
                    changePalette(option, category, scheme = false) {
                        let self = this;
                        const $dropdown = $(self.$el);
                        const $dropdown_toggle = $dropdown.find('> .dropdown-toggle');
                        let temp_active_option = {name: 'Palette', value: option, type: 'palette'};
                        if (scheme) temp_active_option.scheme = category;
                        if (self.active_option === temp_active_option) return;
                        self.active_option = temp_active_option;
                        self.$emit("changePalette", temp_active_option);
                    },
                    changeToggle(option) {
                        let self = this;
                        self.active_toggle_option = option.value;
                        if (option.value !== 'palette') {
                        }
                    },
                    changeSwitch(option) {
                        let self = this;
                        //console.log('change gradient: ', option)
                    },
                },
                components: {
                    'ColorToggle': that.base_components['component-toggle'],
                    'SwitchToggle': that.base_components['component-switch'],
                },
                mounted: function() {
                    let self = this;
                    self.$wrapper = $(self.$el);

                    self.dropdown = self.$wrapper.waDropdown({
                        hover : false,
                        items: ".menu > li > .save-button",
                        update_title: false,
                        open: function(event, target, dropdown) {
                            if (!self.colorPicker) initColorPicker(self);
                            const $dropdown_body = $(self.$el).find('.dropdown-body');
                            alignmentDropdown($dropdown_body);
                        },

                        change: function(event, target, dropdown) {
                            event.preventDefault();
                        }
                    }).waDropdown("dropdown");
                }
            },
            'component-color-dropdown': {
                props: {
                    options: { type: Object, default: {} },
                    activeOption: { type: Object },
                    activeIcon: { type: String },
                    button_class: { type: String, default: "" },
                    body_class: { type: String, default: "" },
                    form_type: { type: String },
                    block_data: { type: Object, default: {} },
                    block_id: { type: Number},
                    element: { type: String },
                    layerIndex: { type: Number, default: 0 },
                },
                emits: ["changeCss"],
                data() {
                    let self = this;
                    let active_toggle_option = 'palette';
                    let palette_options = Object.assign({}, self.options[active_toggle_option]);
                    let scheme_options = Object.assign({}, palette_options['scheme']);
                    let colors_variables_data = $.form_storage.data['colors_variables_data'];
                    delete palette_options['scheme'];
                    let active_option = self.options.values[0];
                    //let layers = [active_option]
                    let activeGradient = false;
                    let gradientOptions = active_option.gradient;
                    let active_scheme = 'complementary'
                    if (self.activeOption) {
                        active_option = self.activeOption;
                        active_toggle_option = active_option.type;
                        if (active_option.gradient) {
                            activeGradient = true
                            gradientOptions = active_option.gradient
                        };
                        active_scheme = active_option.scheme ? active_option.scheme : active_scheme;
                    }
                    //console.log(scheme_options)
                    let style_t = active_option.css === 'gradient' ? ('color: transparent; background:'+ active_option.value) : 'color:' + 'var(' + colors_variables_data[active_option.value] + ')';
                    let toggle_options = $.form_storage.data[this.form_type + '_toggle_data'];
                    let availableGradient = toggle_options.filter( function(option) {
                        return option.value === 'image';
                    }).length;
                    let defaultColor = (active_option.css && active_option.css !== 'gradient') ? (active_option.css).split('#')[1] : (self.options.values[0].css).split('#')[1];

                    return { active_scheme, availableGradient, activeGradient, gradientOptions, palette_options, scheme_options, colors_variables_data, active_option, defaultColor, toggle_options, active_toggle_option, style_t}
                },

                template:
                `<div class="dropdown color-dropdown" :class="element">
                    <button class="dropdown-toggle button light-gray smaller" type="button" :class="button_class">
                        <span class="s-icon icon custom-mr-4" v-if="active_option.type === 'image'"><i class="fas fa-image" ></i></span>
                        <span class="s-icon icon custom-mr-4" v-else><i class="fas" :class="activeIcon" :style="style_t"></i></span>
                        <span v-if="active_option.type === 'image'" class="s-name">{ { active_option.file_name  } }</span>
                        <span v-else class="s-name">{ { active_option.type !== 'palette' ? $t('custom.' + active_option.name) : $t('custom.Palette')  } }{ { active_option.type !== 'self_color' ? ': ' + $t('custom.' + active_option.name) : ': '+active_option.css } }</span>
                    </button>
                    <div class="dropdown-body" :class="body_class">
                        <div class="box custom-px-16 custom-py-12">
                            <ul class="menu custom-my-4">
                                <li>
                                    <color-toggle @changeToggle="changeToggle" :options="toggle_options" :activeOption="active_toggle_option" form_type="custom" ref="childComponent"></color-toggle>
                                    <br>
                                    <div class="flexbox space-12 vertical" v-show="active_toggle_option == 'palette'">
                                        <div class="flexbox space-8 wrap custom-pb-4">
                                            <div class="width-100 flexbox full-width custom-pb-4 middle">
                                                <span class="s-name text-gray">
                                                    { { $t('custom.' + active_scheme, active_scheme) } }
                                                </span>
                                                <div class="s-controls flexbox">
                                                    <span @click="changeScheme(0)" class="icon text-gray custom-pl-12 cursor-pointer">
                                                        <i class="fas fa-arrow-circle-left"></i>
                                                    </span>
                                                    <span @click="changeScheme(1)" class="icon text-gray custom-pl-12 cursor-pointer">
                                                        <i class="fas fa-arrow-circle-right"></i>
                                                    </span>
                                                </div>
                                            </div>
                                            <span class="s-icon-wrapper" v-for="color in scheme_options[active_scheme]" @click="changePalette(color, active_scheme, true)" :key="color">
                                                <span class="s-icon icon size-32" :class="{'selected': active_option.value === color}"><i class="s-bordered-icon fas" :class="activeIcon" :style="{ color: 'var(' + colors_variables_data[color] + ')' }"></i></span>
                                                <span class="s-selected-icon icon size-10 text-white" v-if="active_option.value === color"><i class="fas fa-check"></i></span>
                                            </span>
                                        </div>
                                        <div v-for="(palette, key) in palette_options" class="flexbox space-8 wrap">
                                            <div class="s-name width-100 text-gray custom-pb-4 ">{ { $t('custom.' + key, key) } }</div>
                                            <span class="s-icon-wrapper" v-for="color in palette" @click="changePalette(color, key)">
                                                <span class="s-icon icon size-32" :class="{'selected': active_option.value === color}"><i class="s-bordered-icon fas" :class="activeIcon" :style="{ color: 'var(' + colors_variables_data[color] + ')' }"></i></span>
                                                <span class="s-selected-icon icon size-10 text-white" v-if="active_option.value === color"><i class="fas fa-check"></i></span>
                                            </span>
                                        </div>
                                    </div>
                                    <div class="flexbox vertical space-16" v-show="active_toggle_option === 'self_color'">
                                        <div class="gradient-container flexbox full-width custom-pb-0" v-if="availableGradient">
                                            <span class="text-gray" >{ { $t('custom.Self color') } }</span>
                                            <switch-toggle @changeSwitch="changeSwitch" :activeValue="activeGradient" :activeName="form_type" :textValue="$t('custom.Gradient')" switchClass="smaller" styleData="font-size: .6rem;"></switch-toggle>
                                        </div>
                                        <manual-color @changeColor="change" :defaultColor="defaultColor" v-if="!activeGradient"></manual-color>
                                        <manual-gradient @changeColor="change" :options="gradientOptions" v-else></manual-gradient>
                                    </div>
                                    <div class="flexbox vertical space-16" v-show="active_toggle_option === 'image'">
                                        <manual-image @changeImg="changeImage" :option="active_option" :key="active_option.type"></manual-image>
                                    </div>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
                `,
                delimiters: ['{ { ', ' } }'],
                methods: {
                    change(optionColor, gradient) {
                        let self = this;
                        const $dropdown = $(self.$el);
                        const $dropdown_toggle = $dropdown.find('> .dropdown-toggle');
                        let gradient_css = 'linear-gradient(#' + optionColor + ', #' + optionColor + ')';
                        let temp_active_option = {}
                        //console.log($dropdown_toggle.find('.s-icon svg'), gradient_css)
                        if (gradient) {
                            temp_active_option = { type: 'self_color', value: optionColor, name: 'Self color', css: 'gradient', gradient: gradient};
                            $dropdown_toggle.find('.s-icon svg').css('color', 'transparent').css('background', optionColor);
                        }
                        else {
                            temp_active_option = { type: 'self_color', value: gradient_css, name: 'Self color', css: '#' + optionColor};
                            self.defaultColor = optionColor;
                            $dropdown_toggle.find('.s-icon svg').css('color', '#' + optionColor)
                        }
                        //if (self.active_option === temp_active_option) return;

                        self.active_option = temp_active_option;
                        $dropdown.find('.filename.hint').remove();
                        self.$emit("changeCss", temp_active_option, self.layerIndex);
                    },
                    changeToggle(option) {
                        let self = this;
                        self.active_toggle_option = option.value;
                    },
                    changeScheme(option) {
                        let self = this;
                        const scheme_options_arr = Object.keys(self.scheme_options);
                        const arr_ln = scheme_options_arr.length;
                        let active_index = scheme_options_arr.indexOf(self.active_scheme);
                        let new_active_scheme_index = option ? active_index + 1 : active_index - 1;
                        self.active_scheme = scheme_options_arr[(new_active_scheme_index % arr_ln + arr_ln) % arr_ln];
                        //self.active_scheme = option ? scheme_options_arr[active_index + 1] : scheme_options_arr[active_index - 1]

                        //array[i % n]
                    },
                    changePalette(option, category, scheme = false) {
                        let self = this;
                        const $dropdown = $(self.$el);
                        const $dropdown_toggle = $dropdown.find('> .dropdown-toggle');
                        let temp_active_option = {name: category, value: option, type: 'palette'};
                        if (scheme) temp_active_option.scheme = category;
                        if (self.active_option === temp_active_option) return;
                        $dropdown_toggle.find('.s-icon svg').css('color', 'var(' + self.colors_variables_data[option] + ')');
                        $dropdown_toggle.find('.s-name').html(self.active_option.type + ' #' + temp_active_option.name);
                        self.active_option = temp_active_option;
                        $dropdown.find('.filename.hint').remove();
                        self.$emit("changeCss", temp_active_option, self.layerIndex);
                    },

                    changeImage(option) {
                        const self = this;
                        const $dropdown = $(self.$el);
                        const $dropdown_toggle = $dropdown.find('> .dropdown-toggle');
                        const temp_active_option = option;
                        $dropdown_toggle.find('.s-icon svg').css('color', temp_active_option.css);
                        $dropdown_toggle.find('.s-name').html(temp_active_option.file_name);
                        self.active_option = temp_active_option;
                        self.$emit("changeCss", temp_active_option, self.layerIndex);
                    },
                    changeSwitch(active) {
                        const self = this;
                        if (active) {
                            let stopsCss = '';
                            const stops = Array.isArray(self.gradientOptions.stops) ? self.gradientOptions.stops : Object.values(self.gradientOptions.stops);
                            $.each(stops, function(i, stop) {
                                stopsCss = stopsCss + ' ' + stop.color + ' ' + stop.stop + '%' + (stops.length-1 > i ? ', ' : '');
                            })
                            const gradientCss = self.gradientOptions.type + '(' + ((self.gradientOptions.type === 'linear-gradient') ? self.gradientOptions.degree + 'deg' : 'circle') + ', ' + stopsCss + ')'
                            self.change(gradientCss, self.gradientOptions)
                        } else {
                            self.change(self.defaultColor)
                        }
                        self.activeGradient = !self.activeGradient;
                    },
                },
                components: {
                    'ColorToggle': this.base_components['component-toggle'],
                    'SwitchToggle': that.base_components['component-switch'],
                    'ManualColor': this.base_components['component-color-picker'],
                    'ManualImage': this.manual_components['component-manual-image'],
                    'ManualGradient': this.manual_components['component-manual-gradient'],
                },
                mounted: function() {
                    let self = this;
                    self.$wrapper = $(self.$el);

                    self.dropdown = self.$wrapper.waDropdown({
                        hover : false,
                        items: ".menu > li > .save-button",
                        update_title: false,
                        hide: false,
                        open: function(event, target, dropdown) {
                            //if (!self.colorPicker) that.initColorPicker(self);
                            const $dropdown_body = $(self.$el).find('.dropdown-body');
                            alignmentDropdown($dropdown_body);
                        },

                        close: function(event, target, dropdown) {
                            //console.log('close: ', event, target)
                        },
                        change: function(event, target, dropdown) {
                            event.preventDefault();
                        }
                    }).waDropdown("dropdown");

                }
            },
            'component-width-dropdown': {
                props: {
                    options: { type: Object, default: {} },
                    activeOption: { type: Object },
                    activeIcon: { type: String },
                    button_class: { type: String, default: "" },
                    body_class: { type: String, default: "" },
                    form_type: { type: String },
                    block_data: { type: Object, default: {} },
                    block_id: { type: Number},
                    element: { type: String },
                },
                emits: ["change"],
                data() {
                    let self = this;
                    let active_toggle_option = 'library';
                    let library_options = self.options[active_toggle_option]
                    let active_option = library_options[0];
                    let show_delete_button = false;
                    if (self.activeOption) {
                        active_option = self.activeOption;
                        active_toggle_option = active_option.type;
                        show_delete_button = true;
                    }
                    let toggle_options = $.form_storage.data.border_size_toggle_data;
                    let inputUnit = active_option.unit;
                    let unit_options = $.form_storage.data.border_unit_data;
                    let inputSize = 0;
                    if (active_toggle_option !== 'library') inputSize = active_option.value.split(active_option.unit)[0];
                    //self.$target_wrapper = iframe_wrapper.find('.seq-child > [data-block-id=' + self.block_id + ']');
                    //self.$editable = self.$target_wrapper.find('.style-wrapper');
                    //if (self.element) self.$editable = self.$target_wrapper.find('.' + self.element);
                    //self.$editable = self.$editable.length ? self.$editable : self.$target_wrapper;

                    return { unit_options, library_options, active_option, inputSize, inputUnit, toggle_options, active_toggle_option, show_delete_button}
                },

                template:
                `<div class="dropdown width-dropdown" :class="element">
                    <button class="dropdown-toggle button light-gray smaller" type="button" :class="button_class">
                        <span class="s-name">{ { $t(form_type+'.'+active_option.name, active_option.name) } }{ { active_option.type === 'library' ? '' : ': '+active_option.value } }</span>
                    </button>
                    <div class="dropdown-body" :class="body_class" style="overflow: visible;">
                        <div class="box custom-px-16 custom-py-12">
                            <ul class="menu custom-my-4">
                                <li>
                                    <color-toggle @changeToggle="changeToggle" :options="toggle_options" :activeOption="active_toggle_option" :with_text="true" form_type="custom"></color-toggle>
                                    <br>
                                    <div v-show="active_toggle_option == 'library'">
                                        <ul class="menu library">
                                            <li class=""  v-for="opt in library_options" :class="{selected: active_option.value === opt.value}" :title="opt.name" @click="changeLibrary(opt)">
                                                <a href="javascript:void(0);">
                                                    <span>{ { $t(form_type+'.'+opt.name, opt.name) } }</span>
                                                </a>
                                            </li>
                                        </ul>
                                    </div>
                                    <div v-show="active_toggle_option !== 'library'" class="flexbox middle space-8 width-100">
                                        <input @input="changeInput" @change="change" v-model.number="inputSize" class="width-50 small" type="number" name="input-size" placeholder="0">
                                        <width-unit-dropdown class="width-50" @customChange="changeUnit" :options="unit_options" :activeOption="inputUnit" form_type="custom" button_class="light-gray small" body_class="right width-auto"></width-unit-dropdown>
                                    </div>
                                </li>
                                <!--<li v-show="show_delete_button">
                                <hr class="hr-delete custom-mt-16 custom-mb-0">
                                <a href="javascript:void(0)" class="button-delete-bg" type="button" @click="change('default')">
                                    <span class="s-icon icon custom-mr-4 text-gray" ><i class="fas fa-times-circle"></i></span>
                                    <span class="s-name text-gray">Delete color</span>
                                </a>
                                </li>-->
                            </ul>
                        </div>
                    </div>
                </div>
                `,
                delimiters: ['{ { ', ' } }'],
                methods: {
                    changeInput() {
                        let self = this;
                        const $dropdown = $(self.$el);
                        const $dropdown_toggle = $dropdown.find('> .dropdown-toggle');
                        $dropdown_toggle.find('.s-name').html('Self size: ' + self.inputSize);
                    },
                    change(option) {
                        let self = this;
                        if (self.active_option.type === 'library') {
                            if (!self.element) {
                                if (self.block_data.block_props) delete self.block_data.block_props[self.form_type];
                            } else {
                                if (self.block_data.block_props && self.block_data.block_props[self.element]) delete self.block_data.block_props[self.element][self.form_type];
                            }
                        }
                        let temp_active_option = { type: 'self_size', value: self.inputSize+self.inputUnit, name: 'Self size', unit: self.inputUnit };

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
                    },
                    changeToggle(option) {
                        let self = this;
                        self.active_toggle_option = option.value;
                    },
                    changeLibrary(option) {
                        let self = this;
                        const $dropdown = $(self.$el);
                        const $dropdown_toggle = $dropdown.find('> .dropdown-toggle');
                        const temp_active_option = option;
                        if (self.active_option.type === 'self_size') {
                            if (!self.element) {
                                if (self.block_data.inline_props) delete self.block_data.inline_props[self.form_type];
                            } else {
                                if (self.block_data.inline_props && self.block_data.inline_props[self.element]) delete self.block_data.inline_props[self.element][self.form_type];
                            }
                        }

                        $dropdown_toggle.find('.s-name').html(temp_active_option.name);
                        self.active_option = temp_active_option;
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
                    changeUnit(option) {
                        let self = this;
                        self.inputUnit = option.value
                        self.changeInput();
                        self.change();
                    },
                },
                components: {
                    'ColorToggle': this.base_components['component-toggle'],
                    'WidthUnitDropdown': this.base_components['component-dropdown'],
                },
                mounted: function() {
                    let self = this;
                    self.$wrapper = $(self.$el);


                    self.dropdown = self.$wrapper.waDropdown({
                        hover : false,
                        items: ".menu > li > .save-button",
                        update_title: false,
                        open: function(dropdown_instance) {
                            //set position of the block depending on the position of the window edge
                            const $dropdown_body = $(self.$el).find('.dropdown-body');
                            alignmentDropdown($dropdown_body);
                        },
                        change: function(event, target, dropdown) {
                            event.preventDefault();
                        }
                    }).waDropdown("dropdown");


                }
            },
            'component-shadow-dropdown': {
                props: {
                    options: { type: Object, default: {} },
                    activeOption: { type: Object },
                    activeIcon: { type: String },
                    button_class: { type: String, default: "" },
                    body_class: { type: String, default: "" },
                    form_type: { type: String },
                    block_data: { type: Object, default: {} },
                    block_id: { type: Number},
                    element: { type: String },
                    layerIndex: { type: Number },
                },
                emits: ["change", "changeCss"],
                data() {
                    let self = this;
                    let active_toggle_option = 'palette';
                    let palette_options = self.options[active_toggle_option]
                    let active_option = self.options.values[0];
                    let default_manual_option = self.options.values[0];
                    if (self.activeOption) {
                        active_option = self.activeOption;
                        active_toggle_option = active_option.type;
                    }
                    let toggle_options = $.form_storage.data.shadow_toggle_data;
                    let unit_options = $.form_storage.data.border_unit_data;

                    return { unit_options, palette_options, default_manual_option, active_option, toggle_options, active_toggle_option}
                },

                template:
                `<div class="dropdown shadow-component" :class="element">
                        <button class="dropdown-toggle button light-gray smaller" type="button" :class="button_class">
                            <span class="s-name">{ { active_option.type === 'palette' ? '' : $t('custom.Manually') } }{ { active_option.type === 'palette' ? $t(form_type+'.'+active_option.name, active_option.name) : ': ' + active_option.value } }</span>
                        </button>
                        <div class="dropdown-body" :class="body_class">
                            <div class="box custom-px-16 custom-py-12">
                                <ul class="menu custom-my-4">
                                    <li>
                                        <color-toggle @changeToggle="changeToggle" :options="toggle_options" :activeOption="active_toggle_option" :with_text="true" form_type="custom" ref="childComponent"></color-toggle>
                                        <br>
                                        <div v-show="active_toggle_option == 'palette'">
                                            <ul class="menu library">
                                                <li class="" v-for="opt in palette_options" :class="{selected: active_option.value === opt.value}" :title="opt.name" @click="changePaletteShadow(opt, layerIndex)">
                                                    <a href="javascript:void(0);">
                                                        <span>{ { $t(form_type+'.'+opt.name, opt.name) } }</span>
                                                    </a>
                                                </li>
                                            </ul>
                                        </div>
                                        <div class="flexbox vertical space-16" v-show="active_toggle_option !== 'palette'">
                                            <manual-shadow :activeOption="active_option" :option="default_manual_option" :layerIndex="layerIndex" @change="change" @changeCssShadow="changeCssShadow" :unit_options="unit_options" :form_type="form_type"></manual-shadow>
                                        </div>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                `,
                delimiters: ['{ { ', ' } }'],
                methods: {
                    changeCssShadow(layer, index) {
                        let self = this;
                        const $dropdown = $(self.$el);
                        const $dropdown_toggle = $dropdown.find('> .dropdown-toggle');
                        $dropdown_toggle.find('.s-name').html('Self size: ' + layer.value);
                        self.active_option = layer;
                        self.$emit("changeCss", layer, index);
                    },
                    change(option) {
                        let self = this;
                        //console.log('change', option)
                        //const temp_active_option = { type: 'self_color', value: self.active_option.value, name: 'Manually'};
                        //self.active_option = temp_active_option;
                        //self.active_toggle_option = temp_active_option.type;

                    },
                    changeToggle(option) {
                        let self = this;
                        self.active_toggle_option = option.value;
                    },
                    changePaletteShadow(option, index) {
                        let self = this;
                        const $dropdown = $(self.$el);
                        const $dropdown_toggle = $dropdown.find('> .dropdown-toggle');
                        const temp_active_option = option;
                        $dropdown_toggle.find('.s-name').html(temp_active_option.name);
                        self.active_option = temp_active_option;

                        self.$emit("changeCss", option, index);
                    },

                },
                components: {
                    'ColorToggle': this.base_components['component-toggle'],
                    'ManualShadow': this.manual_components['component-manual-shadow'],
                },
                mounted: function() {
                    let self = this;
                    self.$wrapper = $(self.$el);
                    self.$wrapper.waDropdown({
                            hover : false,
                            items: ".menu > li > .save-button",
                            update_title: false,
                            open: function(event, target, dropdown) {
                                //if (!self.colorPicker) that.initColorPicker(self);
                                const $dropdown_body = $(self.$el).find('.dropdown-body');
                                alignmentDropdown($dropdown_body);
                            },
                            change: function(event, target, dropdown) {
                                event.preventDefault();
                            }
                        }).waDropdown("dropdown");
                }
            },
        }
    }
    initColorPicker = function(obj) {
        let self = obj;
        const $block = $(self.$el);
        const el = $block.find('.js-color-picker')[0];
        const el_container = $block.find('.color-picker-container')[0];
        const $colorPicker = $(el);

        const getColorPicker = (pickr) => {
          if (pickr.hasOwnProperty('toHEXA')) {
            return pickr.toHEXA().toString(0);
          }
          return pickr.target.value;
        }
          const defaultColor = self.inputColor;
          self.colorPicker = Pickr.create({
            el: el,
            theme: 'classic',
            /*swatches: [
              '#fff',
              '#000',
              'rgba(0, 20, 65, 0.2)',
            ],*/
            appClass: 'block-picker',
            //lockOpacity: false,
            position: 'top-start',
            showAlways: true,
            useAsButton: true,
            inline: true,
            default: defaultColor,
            container: el_container,
            //defaultRepresentation: 'HSLA',
            //adjustableNumbers: true,
            outputPrecision: 1,
            components: {
            //preview: true,
            //palette: true,
            opacity: true,
            hue: true,
            interaction: {
                input: true,
                //hex: true,
                //hsla: true,
                //save: true,
                //cancel: true
              }
            },
            i18n: {
              'btn:save': 'Save',
              'btn:cancel': 'Cancel',
            }
          }).on('change', (pickr) => {
            const color_hex = getColorPicker(pickr);
            //const new_color = color_hex.toLowerCase().slice(1);
            $colorPicker.css('background-color', color_hex);
            $colorPicker.attr('data-color', color_hex);
            //console.log(pickr)
            //self.inputColor = new_color;
            //self.change();
          }).on('changestop', (event, pickr) => {
            const color_hex = getColorPicker(pickr._color);
            const new_color = color_hex.toLowerCase().slice(1);
            //$colorPicker.css('background-color', color_hex);
            //$colorPicker.attr('data-color', color_hex);
            //console.log(pickr._color.toRGBA().toString())
            self.inputColor = new_color;
            self.change();

          }).on('save', (color, pickr) => {
            let new_color = getColorPicker(color).toLowerCase().slice(1);
            //$colorPicker.next('.js-color-value').val(new_color);
            //console.log(new_color);
            //self.inputColor = new_color;
            //self.change();
            //pickr.hide();
            //formChanged();

          }).on('hide', (pickr) => {
            const color_hex = '#' + $colorPicker.next('.js-color-value').val();
            $colorPicker.css('background-color', color_hex);
            $colorPicker.attr('data-color', color_hex);

            pickr.setColor(color_hex);
            pickr.destroyAndRemove();
          });

          self.colorPicker.show();

          //return colorPicker;
    }

    function alignmentDropdown($dropdown_body) {
        self.sidebar = $('.block-settings-sidebar');
        if (!self.sidebar.hasClass('overflow-mode')) return;
        let box = $dropdown_body[0].getBoundingClientRect();
        let sidebar_box = self.sidebar[0].getBoundingClientRect();
        let dropdown_box = $dropdown_body.closest('.dropdown')[0].getBoundingClientRect();
        if ((dropdown_box.top - sidebar_box.top) < box.height) {
            $dropdown_body.removeClass('bottom');
        } else {
            //$dropdown_body.addClass('bottom');
        }
    }

return FormComponents;

})(jQuery);

