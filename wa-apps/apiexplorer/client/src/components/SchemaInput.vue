<template>
    <div v-if="schema.enum" class="wa-select">
        <select :id="name" :value="modelValue" @input="updateValue($event.target.value)">
            <option v-if="'nullable' in schema && schema.nullable || !required"></option>
            <option v-for="option in schema.enum" :key="option" :value="option">{{ option }}</option>
        </select>
    </div>
    <span v-else-if="schema.type === 'string'">
        <span v-if="schema.format === 'date'">
            <date-picker v-model="l_value" 
                @click="updateValue(l_value)" @input="updateValue($event.target.value)" 
                :model-config="{ type: 'string', mask: 'YYYY-MM-DD'}"
                :popover="{ visibility: 'focus' }"
            >
                <template v-slot="{ inputValue, inputEvents, togglePopover }">
                    <input type="text" :id="name" :value="inputValue" v-on="inputEvents" />
                    <span class="icon" @click="togglePopover"><i class="fas fa-calendar"></i></span>
                </template>
            </date-picker>
        </span>
        <input v-else-if="schema.format === 'email'" :value="modelValue" @input="updateValue($event.target.value)" :id="name" type="email" />
        <input v-else-if="schema.format === 'password'" :value="modelValue" @input="updateValue($event.target.value)" :id="name" type="password" />
        <div v-else-if="schema.format === 'binary'">
            <div class="upload">
                <label class="link">
                    <i class="fas fa-file-upload"></i>
                    <span v-if="filename" class="custom-mx-8">{{ filename }}</span>
                    <span v-else class="custom-mx-8">{{ $t("Select file") }}</span>
                    <input type="file" autocomplete="off" @change="readFile" :id="name" />
                </label>
            </div>
        </div>
        <input v-else :value="modelValue" @input="updateValue($event.target.value)" :id="name" type="text" class="long" />
    </span>
    <input v-else-if="schema.type === 'integer'" :value="modelValue" @input="updateValue($event.target.value)" :id="name" type="number" />
    <input v-else-if="schema.type === 'number'" :value="modelValue" @input="updateValue($event.target.value)" :id="name" type="text" />
    <label v-else-if="schema.type === 'boolean' /* && required */">
        <span class="wa-checkbox">
            <input type="checkbox" :id="name" v-model="l_value" @input="updateValue(!l_value)" />
            <span>
                <span class="icon">
                    <i class="fas fa-check"></i>
                </span>
            </span>
        </span>
    </label>
    <!--div v-else-if="schema.type === 'boolean' && !required">
        <label>
            <span class="wa-radio">
                <input type="radio" :id="name + '-1'" v-model="l_value" @input="updateValue(null)" />
                <span></span>
            </span>
            Empty
        </label>
        <label>
            <span class="wa-radio">
                <input type="radio" :id="name + '-2'" :value="true" v-model="l_value" @input="updateValue(true)" />
                <span></span>
            </span>
            TRUE
        </label>
        <label>
            <span class="wa-radio">
                <input type="radio" :id="name + '-3'" :value="false" v-model="l_value" @input="updateValue(false)" />
                <span></span>
            </span>
            FALSE
        </label>
    </div-->
    <div v-else-if="schema.type === 'object'" class="fields-group">
        <div v-for="[param, param_schema] of Object.entries(schema.properties)" :key="param" class="field">
            <div class="name for-input">
                <label :for="param">
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
                    @update:modelValue="updateFieldValue($event, param)"
                />
            </div>
        </div>
    </div>
    
    <div v-else-if="schema.type === 'array'">
        <div v-for="(val, index) in l_value" :key="index" class="custom-mb-8 custom-pb-8 flexbox bordered-bottom">
            <div>
                <schema-input 
                    :name="name + '_' + index"
                    :schema="schema.items"
                    :modelValue="val"
                    @update:modelValue="updateArrValue($event, index)"
                />
            </div>
            <div class="icon custom-m-8" @click="removeArrayItem(index)"><i class="fas fa-times"></i></div>
        </div>
        <div>
            <button @click="addRow" class="outlined light-gray small"><i class="fas fa-plus"></i> {{ $t('Add row') }}</button>
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
    <div class="hint custom-mt-0" v-if="description" v-html="descriptionMarkdown"></div>
</template>

<script>
import { marked } from 'marked';
import { /*Calendar,*/ DatePicker } from 'v-calendar';
export default {
    name: "SchemaInput",
    emits: ["update:modelValue", "updated"],
    inheritAttrs: false,
    props: {
        name: String,
        schema: Object,
        modelValue: [String, Object, Array, Boolean, Number],
        required: Boolean,
        description: String
    },
    components: {
        //Calendar,
        DatePicker,
    },
    data() {
        return {
            l_value: {},
            filename: null
        };
    },
    created() {
        this.emitter.on('reset', () => {
            this.l_value = {};
        });
    },
    mounted() {
        if (this.schema.type === 'boolean') {
            //if (this.required) {
                this.l_value = !!this.modelValue;
                this.updateValue(this.l_value);
            //}
        } else if (this.schema.type === 'object') {
            this.l_value = this.modelValue || {};
        } else if (this.schema.type === 'array') {
            this.l_value = this.modelValue || [];
            if (Array.isArray(this.l_value) && this.l_value.length === 0) {
                this.addRow();
            }
        } else {
            this.l_value = this.modelValue;
        }
    },
    computed: {
        descriptionMarkdown() {
            return marked.parse(this.description);
        }
    },
    methods: {
      updateValue: function (val) {
        this.$emit('update:modelValue', val);
        this.$emit('updated');
      },
      readFile: function (event) {
        if (event.target.files.length == 0) {
            return;
        }
        const file = event.target.files[0];
        if (Object.prototype.toString.call(file) !== '[object File]') {
            return;
        }
        const reader = new FileReader();
        reader.onload = e => this.updateValue(e.target.result);
        reader.readAsBinaryString(file);
        this.filename = file.name;
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
        } else if (itemSchema === 'string') {
            this.l_value.push('');
        } else {
            this.l_value.push(null);
        }
      }
      /*
      addArrValue: function (event) {
        this.l_value.push(event.target.value);
        //this.$emit('input', event)
      },
      */
    }
}
</script>

<style scoped>
.hint p, .hint ul { margin: 0; }
</style>