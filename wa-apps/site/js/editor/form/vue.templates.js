
( function($) {
    $.form_storage = $.form_storage || {};
    $.form_storage.share_parts = {
        option_header: `
        <div class="s-editor-option-header flexbox full-width cursor-pointer" @click.stop="toggleChildren" v-if="header_name">
        <div class="s-header-name semibold small">
        { { header_name } }
        </div>
        <div class="s-caret-wrapper">
            <span v-if="showChildren" class="icon text-light-gray custom-m-0">
                <i class="fas fa-minus"></i>
            </span>
            <span v-else class="icon text-light-gray custom-m-0">
                <i class="fas fa-plus"></i>
            </span>
        </div>
        </div>`,
    }

    $.form_storage.templates = {
        component_dropdown: `
        <div class="dropdown">
        <template v-if="disabled">
            <button class="dropdown-toggle button light-gray" type="button" :class="button_class" disabled>
                <span class="s-icon icon custom-mr-4" v-if="activeIcon"><i class="fas" :class="activeIcon"></i></span>
                <span class="s-name">{ { $t(form_type+'.'+active_option.name, active_option.name) } }</span>
            </button>
        </template>
        <template v-else>
            <button class="dropdown-toggle button light-gray" type="button" :class="button_class">
                <span class="s-icon icon custom-mr-4" v-if="activeIcon"><i class="fas" :class="activeIcon"></i></span>
                <span class="s-name">{ { $t(form_type+'.'+active_option.name, active_option.name) } }</span>
            </button>
            <div class="dropdown-body" :class="body_class">
                <ul class="menu">
                    <template v-for="option in formatted_options">
                        <li class="dropdown-item" v-on:click="change(option)" :class="{ 'selected': (option.value === active_option.value), 'disabled': (option.disabled) }">
                            <a href="javascript:void(0);" data-id="option.value">
                                <span class="s-icon icon custom-mr-4" v-if="option.icon"><i class="fas" :class="option.icon"></i></span>
                                <span class="s-name">{ { $t(form_type+'.'+option.name, option.name) } }</span>
                            </a>
                        </li>
                    </template>
                </ul>
            </div>
        </template>
        </div>
        `,
        component_toggle: `
        <div class="toggle" :class="toggleClass">
            <template v-for="option in options">
                <span class="" v-on:click="change(option)" :class="{ 'selected': (option.value === active_option.value)}" :title="$t(form_type + '.' + option.name, option.name)">
                    <span class="s-icon icon" v-if="option.icon"><i class="fas" :class="option.icon"></i></span>
                    <span class="s-name" v-if="with_text">{ { $t(form_type + '.' + option.name, option.name) } }</span>
                </span>
            </template>
        </div>
        `,
        component_font_group: `
        <div class="s-editor-option-wrapper">
            <div class="s-editor-option-body">
                <font-dropdown :options="arr_options" :activeOption="active_option" :form_type="form_type" :block_data="block_data" :block_id="block_id"></font-dropdown>
            </div>
        </div>
        `,
        component_columns_align_group: `
        <div class="s-editor-option-wrapper">
            <div class="s-semi-header text-gray small">{ { header_name } }</div>
            <div class="s-editor-option-body custom-mt-8 ">
                <columns-align-dropdown :element="element" :options="arr_options" :activeOption="active_option" :form_type="form_type" :block_data="block_data" :block_id="block_id"></columns-align-dropdown>
            </div>
        </div>
        `,
        component_columns_align_vertical_group: `
        <div class="s-editor-option-wrapper">
            <div class="s-semi-header text-gray small">{ { header_name } }</div>
            <div class="s-editor-option-body custom-mt-8">
                <columns-align-dropdown @customChange="change" :options="arr_options" :activeOption="active_option" :form_type="form_type_custom" :block_data="block_data" :block_id="block_id"></columns-align-dropdown>
            </div>
        </div>
        `,
        component_rows_align_group: `
        <div class="s-editor-option-wrapper">
            <div class="s-semi-header text-gray small">{ { header_name } }</div>
            <div class="s-editor-option-body custom-mt-8 ">
                <rows-align-dropdown @customChange="change" :options="arr_options" :activeOption="active_option" :form_type="form_type_custom" :block_data="block_data" :block_id="block_id"></rows-align-dropdown>
            </div>
        </div>
        `,
        component_columns_group: `
        <div class="s-editor-option-wrapper">
        <div v-if="header_name" class="s-header flexbox full-width custom-mb-16">
            <div class="s-header-name semibold">
                { { header_name } }
            </div>
            <div class="s-caret-wrapper flexbox space-8" v-if="!indestructible_cols">
                <span @click="removeColumn" class=" icon text-light-gray custom-m-0" v-if="columns_data.length > 1">
                    <i class="fas fa-minus"></i>
                </span>
                <span @click.once="addColumn" class="icon text-light-gray custom-m-0" v-bind:disabled="addColumn" v-if="columns_data.length < 12">
                    <i class="fas fa-plus"></i>
                </span>
            </div>
        </div>
        <template v-for="(column, key) in columns_data">
            <div class="s-semi-header text-gray small">{ { key + 1 } }-я колонка</div>
            <div class="s-editor-option-body custom-mt-8 custom-mb-16">
                <div class="flexbox width-100 space-8 middle">
                    <column-width-dropdown @customChange="change" :options="arr_options" :activeOption="column" :form_type="form_type" :block_data="block_data" :block_id="key + 1" :media_prop="media_prop"></column-width-dropdown>
                    <span class="s-icon icon text-light-gray" v-if="icon"><i class="fas" :class="icon"></i></span>
                    <span class="s-icon icon text-gray cursor-pointer custom-ml-16 custom-p-4" @click="changeComponent(key)" title="Column settings"><i class="fas fa-angle-right"></i></span>
                    <!--<custom-button buttonClass="button light-gray outlined custom-ml-16" iconClass="fa-angle-right" @click="changeComponent(key)" title="Column settings"></custom-button>-->
                </div>
            </div>
        </template>
        </div>
        `,
        component_form_link_group: `
            <div class="sidebar right width-17rem link-settings-sidebar custom-p-16">
                <form-header :header="link_header" :parents="parents" @closeDrawer="$emit('closeDrawer')" @goToParent="goToParent"></form-header>
                <div class="s-editor-option-wrapper">
                    <div class="s-editor-option-body">
                        <div class="s-semi-header text-gray small">{{$t('custom.Action')}}</div>
                        <link-action-dropdown @customChange="change" :options="arr_options" :activeOption="selection_attr.active_option" :form_type="form_type"></link-action-dropdown>
                    </div>
                </div>
                <div class="s-editor-option-wrapper">
                    <div class="s-editor-option-body">
                        <div class="s-semi-header text-gray small">{{$t('custom.'+active_data.semi_header)}}</div>
                        <input v-if="active_data.value === 'phone-link'" v-model="selection_attr.inputPhone" class="width-100 smaller custom-mr-0" type="text" :name="active_data.value" :placeholder="active_data.placeholder">
                        <input v-else-if="active_data.placeholder" v-model.trim="selection_attr.inputEmail" class="width-100 smaller custom-mr-0" type="text" :name="active_data.value" :placeholder="active_data.placeholder">
                        <link-action-dropdown v-if="active_data.value === 'internal-link'" @customChange="changePagesDropdown" :options="arr_pages_options" :activeOption="selection_attr.active_pages_option" :form_type="form_type"></link-action-dropdown>
                        <link-action-dropdown v-if="active_data.value === 'block-link'" @customChange="changeBlockDropdown" :options="arr_block_options" :activeOption="selection_attr.active_block_option" :form_type="form_type"></link-action-dropdown>
                    </div>
                </div>
                <div class="s-editor-option-wrapper" v-if="active_data.value === 'email-link'">
                    <div class="s-editor-option-body">
                        <div class="s-semi-header text-gray small">{{$t('custom.Email subject')}}</div>
                        <input  v-model.trim="selection_attr.inputSubject" class="width-100 smaller custom-mr-0" type="text" name="selection_attr.inputSubject" placeholder="Например: письмо с сайта">
                    </div>
                </div>

                <div class="s-editor-option-wrapper" v-if="active_data.new_window">
                    <div class="s-editor-option-body">
                        <label>
                            <span class="wa-checkbox small">
                                <input v-model="selection_attr.inputCheckbox" type="checkbox" name="newWindowCheckbox">
                                <span>
                                    <span class="icon">
                                        <i class="fas fa-check"></i>
                                    </span>
                                </span>
                            </span>
                            {{$t('custom.Open in new window')}}
                        </label>
                    </div>
                </div>
                <div class="s-editor-option-wrapper">
                    <div class="s-editor-option-body" style="flex-wrap: nowrap; gap: .25rem;">
                        <custom-button :buttonText="$t('custom.Save')" buttonClass="blue" @click="saveLink"></custom-button>
                        <custom-button :buttonText="$t('custom.Cancel')" buttonClass="light-gray" @click="$emit('closeDrawer')">Cancel</custom-button>
                        <custom-button buttonClass="light-gray" iconClass="fa-trash-alt text-red" @click="deleteLink" title="Delete link"></custom-button>
                    </div>
                </div>
            </div>`,
        component_button_link_group: `
            <div class="flexbox vertical space-12 s-editor-option-wrapper">

                <div class="s-editor-option-body">
                <div class="s-semi-header text-gray small">{{group_header}}</div>
                    <link-action-dropdown @customChange="change" :options="arr_options" button_class="light-gray small" :activeOption="selection_attr.active_option" :form_type="form_type"></link-action-dropdown>
                </div>
                <div class="s-editor-option-body">
                    <input v-if="active_data.value === 'phone-link'" v-model="selection_attr.inputPhone" class="width-100 smaller custom-mr-0" type="text" :name="active_data.value" :placeholder="active_data.placeholder" @input="show_buttons = true">
                    <input v-else-if="active_data.placeholder" v-model.trim="selection_attr.inputEmail" class="width-100 smaller custom-mr-0" type="text" :name="active_data.value" :placeholder="active_data.placeholder" @input="show_buttons = true">
                    <link-action-dropdown v-if="active_data.value === 'internal-link'" @customChange="changePagesDropdown" :options="arr_pages_options" :activeOption="selection_attr.active_pages_option" :form_type="form_type"></link-action-dropdown>
                    <link-action-dropdown v-if="active_data.value === 'block-link'" @customChange="changeBlockDropdown" :options="arr_block_options" :activeOption="selection_attr.active_block_option" :form_type="form_type"></link-action-dropdown>
                </div>
                <div class="s-editor-option-body" v-if="active_data.value === 'email-link'">
                    <input @input="show_buttons = true" v-model.trim="selection_attr.inputSubject" class="width-100 smaller custom-mr-0" type="text" name="selection_attr.inputSubject" :placeholder="$t('custom.For example: letter from the site')">
                </div>
                <div class="s-editor-option-body custom-mt-0" v-if="active_data.new_window">
                    <label>
                        <span class="wa-checkbox small">
                            <input v-model="selection_attr.inputCheckbox" @change="show_buttons = true" type="checkbox" name="newWindowCheckbox">
                            <span>
                                <span class="icon">
                                    <i class="fas fa-check"></i>
                                </span>
                            </span>
                        </span>
                        {{$t('custom.Open in new window')}}
                    </label>
                </div>
                <div class="s-editor-option-body" v-show="show_buttons">
                    <custom-button :buttonText="$t('custom.Save')" buttonClass="blue" @click="saveLink"></custom-button>
                    <!--<custom-button buttonText="Cancel" buttonClass="light-gray" @click="$emit('closeDrawer')">Cancel</custom-button>-->
                    <custom-button buttonClass="light-gray" iconClass="fa-trash-alt text-red" @click="deleteLink" :title="$t('custom.Delete')"></custom-button>
                </div>
            </div>
           `,
        component_button_style_group: `
           <div class="s-editor-option-wrapper">
               <div class="s-editor-option-body">
                <div class="s-semi-header text-gray small">{ { header_name } }</div>
                   <button-style-dropdown @customChange="change" :options="arr_options" :activeOption="active_option" :form_type="form_type_custom" :block_data="block_data" :block_id="block_id"></button-style-dropdown>
               </div>
           </div>
           `,
        component_button_size_group: `
            <div class="s-editor-option-wrapper">
                <div class="s-editor-option-body">
                    <div class="s-semi-header text-gray small">{ { header_name } }</div>
                    <button-size-toggle :options="arr_options" :activeOption="active_option" :form_type="form_type" :with_text="with_text" :block_data="block_data" :block_id="block_id"></button-size-toggle>
                </div>
            </div>
           `,
        component_text_color_group: `
            <div class="s-editor-option-wrapper">
                <div class="s-semi-header text-gray small" v-if="semi_header">{ { semi_header } }</div>
                <div class="s-editor-option-body custom-mt-8">
                    <text-color-dropdown @changeColor="change" :options="arr_options" :activeOption="active_option" :activeIcon="active_icon" :form_type="form_type" :block_data="block_data" :block_id="block_id" :key="active_option"></text-color-dropdown>
                </div>
            </div>
            `,
        component_background_color_group: `
        <div class="s-editor-option-wrapper">
        ${$.form_storage.share_parts.option_header}
            <div class="s-editor-option-body custom-mt-8 width-100">
                <!--<background-color-dropdown :options="arr_options" :activeOption="active_options" :element="element" :activeIcon="active_icon" :form_type="form_type" :block_data="block_data" :block_id="block_id" ref="child"></background-color-dropdown>-->
                <div v-for="(layer, index) in layers" class="width-100 flexbox middle js-sort-toggle background-dropdown" :class="{ disabled: layer.disabled }" :key="layer.uuid">
                    <span class="sort custom-pr-12 custom-pl-4 cursor-move"><i class="fas fa-grip-vertical text-light-gray"></i></span>
                    <background-color-dropdown :element="element" @changeCss="changeCss" :layerIndex="+index" :options="arr_options" :activeOption="layer" :activeIcon="active_icon" :form_type="form_type" :block_data="block_data" :block_id="block_id" ref="child"></background-color-dropdown>
                    <span @click="removeLayer(index)" class="icon text-light-gray custom-pl-12 cursor-pointer">
                        <i class="fas fa-minus"></i>
                    </span>
                </div>
            </div>
        </div>
        `,
        component_shadows_group: `
        <div class="s-editor-option-wrapper">
        ${$.form_storage.share_parts.option_header}
            <div class="s-editor-option-body custom-mt-8 width-100">
                <div v-for="(layer, index) in layers" class="width-100 flexbox middle js-sort-toggle" :class="{ disabled: layer.disabled }" :key="layer.uuid">
                    <span class="sort custom-pr-12 custom-pl-4 cursor-move"><i class="fas fa-grip-vertical text-light-gray"></i></span>
                    <shadows-dropdown @change="change" @changeCss="changeCss" :layerIndex="+index" :options="arr_options" :activeOption="layer" :activeIcon="active_icon" :form_type="form_type" :block_data="block_data" :block_id="block_id" ref="child"></shadows-dropdown>
                    <span @click="removeLayer(index)" class="icon text-light-gray custom-pl-12 cursor-pointer">
                        <i class="fas fa-minus"></i>
                    </span>
                </div>
            </div>
        </div>
        `,
        component_border_group: `
        <div class="s-editor-option-wrapper">
        ${$.form_storage.share_parts.option_header}
            <div class="s-editor-options-body custom-mt-8 width-100" v-show="showChildren">
                <div class="s-editor-option-body width-100" v-for="(item, key) in config_array" v-show="showChildren">
                    <component
                        :is="item.type"
                        :group_config="item"
                        :block_data="block_data"
                        :block_id="block_id"
                        :key="block_id+key+showChildren">
                    </component>
                </div>
                <!--<div class="s-editor-option-body custom-mt-8 width-100" v-if="header_name">
                    <custom-button buttonClass="button light-gray width-100" :iconClass="buttonData.icon" :buttonText="$t('border_group.'+buttonData.name)" @click="toggleData(buttonData.value)" :key="buttonData.value"></custom-button>
                </div>-->
            </div>
        </div>
        `,
        component_border_style_group: `
        <div class="flexbox vertical width-100 s-border-style-wrapper" >
            <div class="s-border-style-toggle">
                <color-toggle @changeToggle="changeToggle" :options="toggle_options" :activeOption="active_toggle_option" :with_text="true" form_type="custom"></color-toggle>
            </div>
            <div class="flexbox full-width custom-mt-8 s-border-style-checkboxes" v-if="active_toggle_option === 'separate'">
            <template v-for="(opt, key) in arr_options.separate">
                <label class="flexbox vertical middle space-4" :title="opt.name">
                    <span class="s-icon" v-if="opt.icon" :class="{ 'text-light-gray': !active_options_array.includes(opt.value) && active_options_array[0] !== 'b-d-a' }"><i class="fas" :class="opt.icon"></i></span>
                    <span class="wa-checkbox">
                        <input type="checkbox" name="opt.name" @change="change(opt)" :checked="active_options_array.includes(opt.value) || active_options_array[0] === 'b-d-a'" v-bind:id="opt.value">
                        <span>
                            <span class="icon">
                                <i class="fas fa-check"></i>
                            </span>
                        </span>
                    </span>
                </label>
            </template>
            </div>
        </div>
        `,
        component_line_height_group: `
        <div class="s-editor-option-wrapper">
        <div class="s-semi-header text-gray small" v-if="semi_header">{ { semi_header } }</div>
            <div class="s-editor-option-body custom-mt-8">
                <line-height-dropdown :options="arr_options" :activeOption="active_option" :activeIcon="active_icon" :form_type="form_type" :block_data="block_data" :block_id="block_id"></line-height-dropdown>
            </div>
        </div>
        `,
        component_border_radius_group: `
        <div class="s-editor-option-wrapper">
            ${$.form_storage.share_parts.option_header}
            <div class="s-editor-option-body custom-mt-8" v-show="showChildren">
                <border-radius-dropdown :options="arr_options" :element="selected_element" :activeOption="active_options?.[form_type]" :form_type="form_type" :block_data="block_data" :block_id="block_id" ref="child"></border-radius-dropdown>
                <border-radius-corners-group
                    :group_config="corners_group_config"
                    :block_data="block_data"
                    :block_id="block_id"
                    ref="childCorner"
                    >
                </border-radius-corners-group>
            </div>
        </div>
        `,
        component_border_radius_corners_group: `
        <div class="flexbox vertical width-100 s-border-radius-corners-wrapper" >
            <div class="s-border-style-toggle">
                <color-toggle @changeToggle="changeToggle" :options="toggle_options" :activeOption="active_toggle_option" :with_text="true" form_type="custom"></color-toggle>
            </div>
            <div class="flexbox full-width custom-mt-8 s-border-radius-corners-checkboxes" v-if="active_toggle_option === 'separate'">
            <template v-for="(opt, key) in arr_options.separate">
                <label class="flexbox vertical middle space-4" :title="opt.name">
                    <span class="s-icon" v-if="opt.icon" :class="{ 'text-light-gray': active_options_array.includes(opt.value) }"><i class="fas" :class="opt.icon" :style="'transform: rotate(' + opt.angle + 'deg)'"></i></span>
                    <span class="wa-checkbox">
                        <input type="checkbox" name="opt.name" @change="change(opt)" :checked="!active_options_array.includes(opt.value)" v-bind:id="opt.value">
                        <span>
                            <span class="icon">
                                <i class="fas fa-check"></i>
                            </span>
                        </span>
                    </span>
                </label>
            </template>
            </div>
        </div>
        `,
        component_padding_group: `
        <div class="s-editor-option-wrapper">
            <div class="s-editor-option-header flexbox full-width cursor-pointer" v-if="header_name">
                <div class="s-header-name semibold small">
                { { header_name } }
                </div>
            </div>
            <div class="s-editor-options-body custom-mt-8">
                <div class="s-editor-option-body margin-group">
                    <padding-dropdown v-for="(opt, key) in arr_options" :key="key" :element="selected_element" :options="opt.values" :activeOption="active_options?.[opt.type]" :activeIcon="opt.icon" :block_data="block_data" :block_id="block_id" :form_type="opt.type"></padding-dropdown>
                </div>
            </div>
        </div>
        `,
        component_margin_group: `
        <div class="s-editor-option-wrapper">
            <div class="s-editor-option-header flexbox full-width cursor-pointer" @click.stop="toggleChildren" v-if="selected_element">
                <div class="s-header-name semibold small">
                    { { header_name } }
                </div>
                <div class="s-caret-wrapper">
                    <span v-if="showChildren" class="icon text-light-gray custom-m-0">
                        <i class="fas fa-minus"></i>
                    </span>
                    <span v-else class="icon text-light-gray custom-m-0">
                        <i class="fas fa-plus"></i>
                    </span>
                </div>
            </div>
            <div class="s-editor-option-header flexbox full-width cursor-pointer" v-else>
                <div class="s-header-name semibold small">
                    { { header_name } }
                </div>
            </div>
            <div class="s-editor-options-body custom-mt-8" v-show="showChildren">
                <div class="s-editor-option-body margin-group">
                    <margin-dropdown v-for="(opt, key) in arr_options" :key="key" :element="selected_element" :options="opt.values" :activeOption="active_options?.[opt.type]" :activeIcon="opt.icon" :block_data="block_data" :block_id="block_id" :form_type="opt.type" ref="child"></margin-dropdown>
                </div>
            </div>
        </div>
        `,
        component_align_group: `
        <div class="s-editor-option-wrapper">
        <div class="s-semi-header text-gray small" v-if="semi_header">{ { semi_header } }</div>
            <div class="s-editor-option-body custom-mt-8">
                <align-toggle :options="arr_options" :activeOption="active_option" :form_type="form_type" :block_data="block_data" :block_id="block_id"></align-toggle>
            </div>
        </div>
        `,
        component_visibility_group: `
        <div class="s-editor-option-wrapper">
            ${$.form_storage.share_parts.option_header}
            <div class="s-editor-option-body custom-mt-8 flexbox full-width" v-show="showChildren">
                <template v-for="(opt, key) in formatted_options">
                    <label class="flexbox vertical middle space-4" :title="opt.name">
                        <span class="s-icon" v-if="opt.icon" :class="{ 'text-light-gray': !opt.activeOption }"><i class="fas" :class="opt.icon"></i></span>
                        <span class="wa-checkbox">
                            <input type="checkbox" name="opt.name" @change="change(opt)" :checked="opt.activeOption" :disabled="opt.disabled" v-bind:id="opt.value">
                            <span>
                                <span class="icon">
                                    <i class="fas fa-check"></i>
                                </span>
                            </span>
                        </span>
                    </label>
                </template>
            </div>
        </div>
        `,
        component_image_upload_group: `
        <div class="s-editor-option-wrapper">
            <div class="s-editor-option-body custom-mt-8 flexbox full-width image-upload">
                <div id="drop-area" @drop.stop.prevent="drop($event)">
                    <div class="upload s-small" >
                        <label class="link">
                            <span class="button width-100 light-gray custom-mr-0 custom-mb-4" ><i class="fas fa-image"></i> { { image_data ? $t('custom.Change image') : $t('custom.Add image') } }</span>
                            <input name="namespace" type="file" autocomplete="off" @change="change($event)" @cancel="cancelFile($event)" accept="image/*">
                        </label>
                        <span v-if="image_data" class="filename hint">{ { image_data } }</span>
                    </div>
                </div>
            </div>
        </div>
        `,
        component_tags_group: `
        <div class="s-editor-option-wrapper">
            <div class="s-editor-option-header flexbox full-width cursor-pointer" v-if="header_name">
                <div class="s-header-name semibold small">
                { { header_name } }
                </div>
            </div>
            <div class="s-editor-option-body custom-mt-8">
                <div class="s-semi-header text-gray small" v-if="semi_header">{ { $t('custom.' + semi_header) } }</div>
                <tags-dropdown :options="arr_options" @customChange="change" :activeOption="active_option" :form_type="form_type" :block_data="block_data" :block_id="block_id"></tags-dropdown>
            </div>
        </div>
        `,

    }


})(jQuery);

