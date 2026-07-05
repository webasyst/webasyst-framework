<template>
    <div v-if="oneOf">
        <ul class="tabs bordered-bottom custom-mb-8" style="margin-top: -0.75em;">
            <li class="grey">{{ $t("One of:") }}</li>
            <li v-for="(el, idx) in oneOf" :key="'tab' + idx" :class="tab_selected == idx ? ['selected'] : []">
                <a href="javascript:void(0);" @click="switchTab(idx)">
                    {{ $t("Case") }} {{ idx+1 }}
                </a>
            </li>
        </ul>
        <div v-for="(el, idx) in oneOf" :key="'tabcontent' + idx" :class="tab_selected == idx ? [] : ['hidden']">
            <schema-input 
                :name="name"
                :schema="el"
                :description="el.description"
                :modelValue="l_value"
                :post_body_type="post_body_type"
                :required="required && isScalar(el)"
                @update:modelValue="updateValue($event)"
            />
        </div>
    </div>

    <div v-else-if="schema.enum" class="dropdown" :id="'dd-' + name">
        <button class="dropdown-toggle button outlined light-gray nowrap selector" type="button">
            <span v-if="typeof l_value === 'undefined'"><i class="fas fa-ellipsis-h"></i></span>
            <span v-else-if="l_value === null" class="smaller">null</span>
            <span v-else-if="l_value === ''" class="smaller">{{ $t("empty string") }}</span>
            <span v-else class="custom-mr-16">{{ l_value + '' }}</span>
            <span v-if="'default' in schema && schema.default === l_value" class="smaller hint-strong custom-ml-4">{{ $t("default") }}</span>
        </button>
        <div class="dropdown-body selector-list">
            <ul class="menu">
                <li v-if="!required">
                    <a href="javascript:void(0);" @click="updateValue(undefined)" class="gray">
                        <i class="fas fa-ellipsis-h"></i>
                    </a>
                </li>
                <li v-if="schema.nullable && !schema.enum.includes(null)">
                    <a href="javascript:void(0);" @click="updateValue(null)" class="nowrap">
                        <span v-if="'default' in schema && schema.default === null" class="count">{{ $t("default") }}</span>
                        <span class="hint">null</span>
                    </a>
                </li>
                <li v-if="!required && schema.type === 'string' && !schema.enum.includes('')">
                    <a href="javascript:void(0);" @click="updateValue('')" class="nowrap">
                        <span v-if="'default' in schema && schema.default === ''" class="count">{{ $t("default") }}</span>
                        <span class="hint">{{ $t("empty string") }}</span>
                    </a>
                </li>
                <li v-for="option in schema.enum" :key="option">
                    <a href="javascript:void(0);" @click="updateValue(option)" class="nowrap">
                        <span v-if="'default' in schema && schema.default === option" class="count">{{ $t("default") }}</span>
                        <span v-if="option === null" class="hint">null</span>
                        <span v-else-if="option === ''" class="hint">{{ $t("empty string") }}</span>
                        <span v-else>{{ option }}</span>
                    </a>
                </li>
            </ul>
        </div>
    </div>

    <span v-else-if="schema.type === 'string'">
        <div v-if="schema.format === 'binary' || schema.format === 'byte'">
            <div class="upload">
                <label class="link">
                    <i class="fas fa-file-upload"></i>
                    <span v-if="filename" class="custom-mx-8">{{ filename }}</span>
                    <span v-else class="custom-mx-8">{{ $t("Select file") }}</span>
                    <input type="file" autocomplete="off" @change="readFile" :id="name" />
                </label>
            </div>
        </div>
        <textarea v-else-if="schema.format === 'text'" :value="modelValue" @input="updateValue($event.target.value)" :id="name" :placeholder="'default' in schema ? schema.default : ''"></textarea>
        <div v-else>
            <div v-if="required || !empty_case" class="flexbox middle space-8">
                <span v-if="schema.format === 'date'">
                    <input :value="modelValue" @input="updateValue($event.target.value)" :id="name" type="date" />
                </span>
                <span v-else-if="schema.format === 'date-time'">
                    <input :value="modelValue" @input="updateValue($event.target.value)" :id="name" type="datetime-local" />
                </span>
                <input v-else-if="schema.format === 'email'" :value="modelValue" @input="updateValue($event.target.value)" :id="name" type="email" />
                <input v-else-if="schema.format === 'password'" :value="modelValue" @input="updateValue($event.target.value)" :id="name" type="password" />
                <input v-else :value="modelValue" @input="updateValue($event.target.value)" :id="name" :placeholder="'default' in schema ? schema.default : ''" type="text" class="long" />
                <button v-if="!required" class="icon" @click="empty_case = true">
                    <span v-if="modelValue === null"><i class="far fa-times-circle"></i></span>
                    <span v-else-if="modelValue === ''"><i class="far fa-circle"></i></span>
                    <span v-else><i class="fas fa-times-circle"></i></span>
                </button>
            </div>
            <div v-else-if="!required" class="flexbox middle space-8 toggle-wrapper">
                <div class="toggle">
                    <span :class="typeof l_value === 'undefined' ? ['selected'] : []" @click="updateValue(undefined)">
                        <i class="fas fa-times-circle" :title="$t('absent')"></i>
                        <span class="toggle-item-text custom-ml-4">{{ $t("absent") }}</span>
                    </span>
                    <span v-if="schema.nullable" :class="l_value === null ? ['selected'] : []" @click="updateValue(null)">
                        <i class="far fa-times-circle" title="null"></i>
                        <span class="toggle-item-text custom-ml-4">null</span>
                    </span>
                    <span :class="l_value === '' ? ['selected'] : []" @click="updateValue('')">
                        <i class="far fa-circle" :title="$t('empty string')"></i>
                        <span class="toggle-item-text custom-ml-4">{{ $t("empty string") }}</span>
                    </span>
                </div>
                <button class="icon" @click="empty_case = false"><i class="fas fa-edit"></i></button>
            </div>
        </div>
    </span>
    <div v-else-if="schema.type === 'integer'">
        <span v-if="'minimum' in schema" class="hint minimum" :title="$t('minimum value')">
            {{ schema.minimum }}
            <i class="fas fa-chevron-left"></i>
        </span>
        <input :value="modelValue" @input="updateValue($event.target.value)" :id="name" type="number" class="shorter" :placeholder="'default' in schema ? schema.default : ''"/>
        <span v-if="'maximum' in schema" class="hint" :title="$t('maximum value')">
            <i class="fas fa-chevron-right"></i>
            {{ schema.maximum }}
        </span>
        <button v-if="!required && typeof modelValue !== 'undefined'" class="icon custom-ml-8" @click="updateValue(undefined)">
            <span><i class="fas fa-times-circle"></i></span>
        </button>
    </div>
    <div v-else-if="schema.type === 'number'">
        <input :value="modelValue" @input="updateValue($event.target.value)" :id="name" type="text" class="shorter" :placeholder="'default' in schema ? schema.default : ''"/>
        <button v-if="!required && typeof modelValue !== 'undefined'" class="icon" @click="updateValue(undefined)">
            <i class="fas fa-times-circle"></i>
        </button>
    </div>
    <!-- label v-else-if="schema.type === 'boolean' /* && required */">
        <span class="wa-checkbox">
            <input type="checkbox" :id="name" v-model="l_value" @input="updateValue(!l_value)" />
            <span>
                <span class="icon">
                    <i class="fas fa-check"></i>
                </span>
            </span>
        </span>
    </label -->
    <div v-else-if="schema.type === 'boolean'" class="toggle-wrapper">
        <div class="toggle">
            <span v-if="!required" :class="typeof modelValue === 'undefined' ? ['selected'] : []" @click="updateValue(undefined)">
                <i class="fas fa-times-circle" :title="$t('absent')"></i>
                <span class="toggle-item-text custom-ml-4">{{ $t("absent") }}</span>
            </span>
            <span v-if="schema.nullable" :class="modelValue === null ? ['selected'] : []" @click="updateValue(null)">
                <i class="far fa-times-circle" title="null"></i>
                <span class="toggle-item-text custom-ml-4">null</span>
            </span>
            <span :class="modelValue ? ['selected'] : []" @click="updateValue(true)">
                <i class="fas fa-check" title="true"></i>
                <span class="toggle-item-text custom-ml-4">true</span>
            </span>
            <span :class="modelValue === false ? ['selected'] : []" @click="updateValue(false)">
                <i class="fas fa-times" title="false"></i>
                <span class="toggle-item-text custom-ml-4">false</span>
            </span>
        </div>
    </div>
    <div v-else-if="schema.type === 'object'" class="fields-group">
        <div v-if="'properties' in schema">
            <div v-for="[param, param_schema] of Object.entries(schema.properties)" :key="param" class="field">
                <div :class="('type' in param_schema && param_schema.type === 'boolean') ? ['name', 'for-checkbox'] : ['name', 'for-input']">
                    <label :for="name + '_' + param">
                        {{ param }}
                        <span v-if="schema.required && schema.required.includes(param)" class="state-caution">*</span>
                    </label>
                </div>
                <div class="value">
                    <schema-input 
                        :name="name + '_' + param"
                        :schema="param_schema"
                        :description="param_schema.description"
                        :required="schema.required && schema.required.includes(param)"
                        :modelValue="l_value[param]"
                        :post_body_type="post_body_type"
                        @update:modelValue="updateFieldValue($event, param)"
                    />
                </div>
            </div>
        </div>
        <div v-if="schema.additionalProperties">
            <div>{{ $t('Additional properties') }}</div>
            <table class="borderless custom-mb-8">
            <tbody>
                <tr>
                    <th>{{ $t('Key') }}</th>
                    <th class="max-width">{{ $t('Value') }}</th>
                    <th class="min-width"></th>
                </tr>
                <tr v-for="(property, index) in additional_properties" :key="index">
                    <td>
                        <input type="text" v-model="property[0]" @input="updateAdditionalProp" class="short" />
                    </td>
                    <td class="max-width">
                        <input type="text" v-model="property[1]" @input="updateAdditionalProp" style="width: 100%" />
                    </td>
                    <td class="min-width">
                        <button class="circle outlined light-gray small" @click="removeAdditionalProp(index)"><i class="fas fa-times"></i></button>
                    </td>
                </tr>
            </tbody>
            </table>
            <button class="outlined light-gray small" @click="additional_properties.push(['', ''])"><i class="fas fa-plus"></i> {{ $t('Add param') }}</button>
        </div>
    </div>
    
    <div v-else-if="schema.type === 'array'">
        <div v-for="(val, index) in l_value" :key="index" class="custom-mb-8 custom-pb-8 flexbox bordered-bottom">
            <div class="wide">
                <schema-input 
                    :name="name + '_' + index"
                    :schema="schema.items"
                    :modelValue="val"
                    :post_body_type="post_body_type"
                    @update:modelValue="updateArrValue($event, index)"
                />
            </div>
            <div class="icon large custom-m-8" @click="removeArrayItem(index)"><i class="fas fa-times"></i></div>
        </div>
        <div :class="{
            flexbox: true,
            middle: true,
            'space-8': true,
            'toggle-wrapper': isEmpty(l_value)
        }">
            <div v-if="isEmpty(l_value)" class="toggle">
                <span :class="typeof l_value === 'undefined' ? ['selected'] : []" @click="updateValue(undefined)">
                    <i class="fas fa-times-circle" :title="$t('absent')"></i>
                    <span class="toggle-item-text custom-ml-4">{{ $t("absent") }}</span>
                </span>
                <span v-if="schema.nullable" :class="l_value === null ? ['selected'] : []" @click="updateValue(null)">
                    <i class="far fa-times-circle" title="null"></i>
                    <span class="toggle-item-text custom-ml-4">null</span>
                </span>
                <span :class="Array.isArray(l_value) && l_value.length === 0 ? ['selected'] : []" @click="updateValue([])">
                    <i class="far fa-circle" :title="$t('empty array')"></i>
                    <span class="toggle-item-text custom-ml-4">{{ $t("empty array") }}</span>
                </span>
            </div>
            <button @click="addRow" class="nobutton smaller" :title="$t('Add row')">
                <i class="fas fa-plus-circle"></i> 
                <span class="button-text custom-ml-4">{{ $t('Add row') }}</span>
            </button>
        </div>
    </div>
    
    <p v-else>
        {{ $t('Unknown field type') }} - <strong>{{ schema.type }}</strong>. 
        {{ $t('There is no interface for it yet :(') }}.
    </p>
    <div>
        <span class="hint" v-if="schema.type === 'integer'">{{ $t('Integer') }}</span>
        <span class="hint" v-else-if="schema.type === 'number'">{{ $t('Number') }}</span>
        <span v-else-if="schema.type === 'string'">
            <span class="hint" v-if="schema.format === 'date'">{{ $t('Date') }}</span>
            <span class="hint" v-if="schema.format === 'date-time'">{{ $t('Date & time') }}</span>
            <span class="hint" v-if="schema.format === 'email'">Email</span>
            <span class="hint" v-if="schema.format === 'uri'">URI</span>
        </span>
    </div>
    <div class="hint description" v-if="description" v-html="descriptionMarkdown"></div>
    <div v-if="schema.example" class="hint description">{{ $t("Example:") }} <strong>{{ schema.example }}</strong></div>
</template>

<script>
import { marked } from 'marked';
//import { /*Calendar,*/ DatePicker } from 'v-calendar';
//import Datepicker from '@vuepic/vue-datepicker';
//import '@vuepic/vue-datepicker/dist/main.css';
export default {
    name: "SchemaInput",
    emits: ["update:modelValue", "updated"],
    inheritAttrs: false,
    props: {
        name: String,
        schema: Object,
        modelValue: [String, Object, Array, Boolean, Number],
        required: Boolean,
        description: String,
        post_body_type: String
    },
    components: {
        //Calendar,
        //DatePicker,
        //Datepicker,
    },
    data() {
        return {
            l_value: {},
            filename: null,
            tab_selected: 0,
            oneOf: null,
            dropdown: false,
            empty_case: false,
            additional_properties: [['', '']]
        };
    },
    created() {
        this.emitter.on('reset', () => {
            this.l_value = this.isScalar(this.schema) ? undefined : {};
        });
    },
    mounted() {
        this.l_value = this.modelValue;
        if ('enum' in this.schema) {
            this.doDropdown();
        }
        if ('type' in this.schema) {
            if (this.schema.type === 'boolean') {
                setTimeout(() => {
                    if (this.isEmpty(this.modelValue)) {
                        if (this.required) {
                            this.l_value =  false;
                            this.updateValue(this.l_value);
                        } else if (typeof this.modelValue !== 'undefined') {
                            this.l_value = null;
                            this.updateValue(this.l_value);
                        }
                    } else {
                        this.l_value =  !!this.modelValue;
                        this.updateValue(this.l_value);
                    }
                }, 100);
            } else if (this.isScalar(this.schema)) {
                setTimeout(() => { 
                    this.l_value = this.modelValue;
                    if (this.modelValue === null || this.modelValue === "") {
                        this.empty_case = true;
                    }
                }, 100);
            } else if (this.schema.type === 'object') {
                this.l_value = this.modelValue || {};
                if (this.schema.additionalProperties) {
                    this.additional_properties = Object.keys(this.modelValue)
                        .filter(prop => !('properties' in this.schema) || !(prop in this.schema.properties))
                        .map(prop => [prop, this.modelValue[prop]]);
                }
            } else if (this.schema.type === 'array') {
                this.l_value = this.modelValue || [];
                if (Array.isArray(this.l_value) && this.l_value.length === 0) {
                    this.addRow();
                }
            }
        } else if ('oneOf' in this.schema) {
            this.oneOf = this.schema.oneOf;
        } else if ('anyOf' in this.schema) {
            this.oneOf = this.schema.anyOf;
        }
        const tab_selected = localStorage.getItem(this.name + '_oneOfTab');
        if (tab_selected) {
            this.tab_selected = tab_selected;
        }
    },
    computed: {
        descriptionMarkdown() {
            return marked.parse(this.description);
        }
    },
    methods: {
      updateValue: function (val) {
        if (this.isScalar(this.schema)) {
            if (['integer', 'number'].includes(this.schema.type)) {
                if (val === '') {
                    val = undefined;
                } else {
                    val = 1 * val;
                    if (isNaN(val)) {
                        val = undefined;
                    }
                }
            }
            this.l_value = val;
        } else if (this.schema.type === 'array' && this.isEmpty(val)) {
            this.l_value = val;
        }
        this.$emit('update:modelValue', val);
        this.$emit('updated');
      },
      readFile: function (event) {
        if (event.target.files.length == 0) {
            return;
        }
        const file = event.target.files[0];
        if (!(file instanceof File)) {
            return;
        }
        this.filename = file.name;
        if (this.post_body_type === 'multipart/form-data') {
            this.updateValue(file);
        } else {
            const reader = new FileReader();
            if (this.schema.format === 'byte') {
                reader.onload = e => {
                    let result = e.target.result;
                    const p = result.indexOf(";base64,");
                    if (p != -1) {
                        result = result.substring(p + 8);
                    }
                    this.updateValue(result);
                }
                reader.readAsDataURL(file);
            } else {
                reader.onload = e => {
                    const bin_result = [...new Uint8Array(e.target.result)].map(v => String.fromCharCode(v)).join('');
                    this.updateValue(bin_result);
                }
                reader.readAsArrayBuffer(file);
            }
        }
      },
      updateArrValue: function (val, index) {
        this.l_value[index] = val;
        this.$emit('update:modelValue', Object.values(this.l_value));
        this.$emit('updated');
        //this.$emit('input', event)
      },
      updateFieldValue: function (val, param) {
        this.l_value[param] = val;
        this.$emit('update:modelValue', this.l_value);
        this.$emit('updated');
        //this.$emit('input', event)
      },
      updateAdditionalProp: function () {
        Object.keys(this.l_value)
            .filter(prop => !('properties' in this.schema) || !(prop in this.schema.properties))
            .forEach(prop => delete this.l_value[prop]);
        this.additional_properties.forEach(el => this.l_value[el[0]] = el[1]);
        this.$emit('update:modelValue', this.l_value);
        this.$emit('updated');
      },
      removeAdditionalProp: function (index) {
        this.additional_properties.splice(index, 1);
        this.updateAdditionalProp();
      },
      removeArrayItem: function (index) {
        this.l_value.splice(index, 1);
        this.$emit('update:modelValue', Object.values(this.l_value));
        this.$emit('updated');
        //this.$emit('input', event)
      },
      addRow: function() {
        if (this.schema.type !== 'array' || !('items' in this.schema)) {
            return;
        }
        if (this.l_value === null || typeof this.l_value !== 'object' || !Array.isArray(this.l_value)) {
            this.l_value = [];
        }
        const itemSchema = this.schema.items.type;
        if (itemSchema === 'object') {
            this.l_value.push({});
        } else if (itemSchema === 'array') {
            this.l_value.push([]);
        } else {
            this.l_value.push(undefined);
        }
      },
      isEmpty(val) {
        return typeof val === 'undefined'
            || val === null
            || val === ''
            || Array.isArray(val) && val.length === 0
            || typeof val === 'object' && Object.entries(val).length === 0
      },
      isScalar(schema) {
        return 'type' in schema && (schema.type === 'string' || schema.type === 'integer' || schema.type === 'number' || schema.type === 'boolean');
      },
      switchTab: function(tab) {
        this.tab_selected = tab;
        localStorage.setItem(this.name + '_oneOfTab', tab);
      },
      doDropdown() {
        if (this.dropdown) {
          return;
        }
        let dd = window.jQuery("#dd-" + this.name.replace("[", "\\[").replace("]", "\\]"));
        if (!dd) {
          setTimeout(() => {this.doDropdown()}, 1000);
          return;
        }
        this.dropdown = dd.waDropdown({
            hover: false
        });
      },
      /*
      addArrValue: function (event) {
        this.l_value.push(event.target.value);
        //this.$emit('input', event)
      },
      */
    }
}
</script>

<style>
.description {
    margin-top: 0.25em !important;
    margin-bottom: 0.5em !important;
}
.description p, .description ul {
    margin: 0;
}
.description ul {
    padding-left: 1.25rem;
}
textarea {
    width: 100%;
}
</style>
<style scoped>
.hint p, .hint ul { margin: 0; }
.selector {
    background: var(--background-color-input);
    border: 0.125em solid var(--border-color-input) !important;
    min-width: 80px;
}
.selector-list {
    width: auto !important;
}
.hint-strong {
    font-weight: normal;
}
.minimum {
    position: relative; 
    right: 0.25em; 
    display: inline-block; 
    text-align: right; 
    margin-left: -100px; 
    width: 100px;
}
.toggle-wrapper {
    container-name: toggle;
    container-type: inline-size;
}
@container toggle (width < 350px) {
  .toggle-item-text {
    display: none;
  }
}
@container toggle (width < 450px) {
  .button-text {
    display: none;
  }
}
.fields-group {
  container-name: field;
  container-type: inline-size;
}
@container field (width < 400px) {
    .fields:not(.vertical) .field > .name {
    width: 100px;
  }
}
</style>