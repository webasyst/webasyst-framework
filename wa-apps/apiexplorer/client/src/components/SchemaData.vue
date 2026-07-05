<template>
    <div v-if="isEmpty(value)">
        <span class="badge squared light-gray small">{{ $t('empty') }}</span>
        <div class="hint description" v-if="description" v-html="descriptionMarkdown"></div>
        <div class="hint description" v-if="effective_schema.type === 'array' && 'items' in effective_schema && effective_schema.items.description" v-html="markdown(effective_schema.items.description)"></div>
        <p v-if="!description && !(effective_schema.type === 'array' && 'items' in effective_schema && effective_schema.items.description)"></p>
    </div>
    <div v-else-if="effective_schema.type === 'string' || effective_schema.type === 'integer' || effective_schema.type === 'number' || effective_schema.type === 'boolean'">
        <span v-if="effective_schema.type === 'string' && effective_schema.format === 'date-time'">
            {{ (new Date(value)).toLocaleString() }}
        </span>
        <span v-else-if="effective_schema.type === 'string' && effective_schema.format === 'date'">
            {{ (new Date(value)).toLocaleDateString() }}
        </span>
        <span v-else-if="effective_schema.type === 'string' && effective_schema.format === 'uri'">
            <a :href="value">{{ value }}</a>
        </span>
        <span v-else :class="(effective_schema.type === 'integer' || effective_schema.type === 'number') 
            ? ['json-number'] : ( effective_schema.type === 'boolean' ? ['json-boolean'] : ['json-string'])">{{ value }}</span>
        <div class="hint description" v-if="description" v-html="descriptionMarkdown"></div>
        <p v-else></p>
    </div>
    <div v-else-if="effective_schema.type === 'object'">
        <div class="hint description" v-if="description" v-html="descriptionMarkdown"></div>
        <div v-if="typeof value === 'object' && !Array.isArray(value)" class="fields-group">
            <div v-for="[param, val] of Object.entries(value)" :key="param">
                <div v-if="(isArrayNested(param) || isObjectNested(param)) && !isEmpty(val)" :class="!collapsed(param) ? ['compressed'] : []">
                    <div class="field">
                        <div class="name">
                            <span :class="!collapsed(param) ? ['button', 'white', 'small', 'label', 'disabled'] : []">{{ param }}:</span>
                        </div>
                        <div class="value">
                            <a href="javascript:void(0);" @click="toggleCollapse(param)" class="button light-gray small">
                                <span v-if="isArrayNested(param)">{{ $t("list") }}</span>
                                <span v-else>{{ $t("data") }}</span>
                                <span class="custom-ml-8 icon" v-if="collapsed(param)"><i class="fas fa-caret-right"></i></span>
                                <span class="custom-ml-8 icon" v-else><i class="fas fa-caret-down"></i></span>
                            </a>
                            <span v-if="isArrayNested(param)">
                                <span v-if="Array.isArray(val)" class="hint">{{ val.length }}</span>
                                <span v-else class="hint">not valid content</span>
                            </span>
                            <div v-if="effective_schema.properties[param].description" 
                                v-html="markdown(effective_schema.properties[param].description)"
                                class="hint description"
                            ></div>
                            <p v-else></p>
                        </div>
                    </div>
                    <div v-if="!collapsed(param)">
                        <schema-data 
                            :schema="effective_schema.properties[param]"
                            :value="val"
                            :deep="new_deep"
                        />
                    </div>
                </div>
                <div v-else class="field">
                    <div class="name">
                        {{ param }}:
                    </div>
                    <div class="value">
                        <schema-data 
                            :schema="effective_schema.properties[param]"
                            :value="val"
                            :deep="new_deep"
                            :description="effective_schema.properties[param].description"
                            v-if="'properties' in effective_schema && param in effective_schema.properties"
                        />
                        <pre v-else class="small" v-html="prettifyJson(val)"></pre>
                    </div>
                </div>
            </div>
        </div>
        <pre v-else class="small" v-html="prettifyJson(value)"></pre>
    </div>
    <div v-else-if="effective_schema.type === 'array'">
        <div class="hint description" v-if="description" v-html="descriptionMarkdown"></div>
        <div class="hint description" v-if="'items' in effective_schema && effective_schema.items.description" v-html="markdown(effective_schema.items.description)"></div>
        <div v-if="Array.isArray(value)">
            <div v-for="(val, index) in value" :key="index">
                <schema-data 
                    :schema="effective_schema.items"
                    :value="val"
                    :deep="new_deep"
                    v-if="'items' in effective_schema"
                />
                <pre v-else class="small" v-html="prettifyJson(val)"></pre>
            </div>
            <span v-if="value === null || value.length === 0" class="badge squared light-gray small">{{ $t('empty') }}</span>
        </div>
        <pre v-else class="small" v-html="prettifyJson(value)"></pre>
    </div>
    <div v-else>
        <p>
            {{ $t('Unknown field type') }} - <strong>{{ effective_schema.type }}</strong>. 
            {{ $t('There is no interface for it yet :(') }}.
        </p>
        <div class="hint description" v-if="description" v-html="descriptionMarkdown"></div>
        <pre class="small" v-html="prettifyJson(value)"></pre>
    </div>
</template>

<script>
import { marked } from 'marked';
import { prettyPrintJson } from 'pretty-print-json';
export default {
    name: "SchemaData",
    props: {
        schema: Object,
        value: [Object, Array, String, Number, Boolean],
        deep: Number,
        description: String
    },
    data() {
        return {
            new_deep: this.deep + 1,
            effective_schema: this.schema,
            collapse: {}
        };
    },
    computed: {
        descriptionMarkdown() {
            return marked.parse(this.description);
        },
        isSimpleItemsArray() {
            return 'items' in this.schema
                && this.schema.items.type !== 'object'
                && this.schema.items.type !== 'array'
        },
    },
    mounted() {
        // handle oneOf case
        if (!('type' in this.schema) && ('oneOf' in this.schema || 'anyOf' in this.schema)) {
            this.effective_schema = this.checkType(this.schema, this.value, true);
        }
    },
    methods: {
        markdown(str) {
            if (!str) {
                return '';
            }
            return marked.parse(str);
        },
        prettifyJson(obj) {
            return prettyPrintJson.toHtml(obj);
        },
        isArrayNested(param) {
            return 'properties' in this.schema 
                && param in this.schema.properties 
                && this.schema.properties[param].type === 'array'
        },
        isObjectNested(param) {
            return 'properties' in this.schema 
                && param in this.schema.properties 
                && this.schema.properties[param].type === 'object'
        },
        isEmpty(val) {
            return val === null
                || val === ''
                || Array.isArray(val) && val.length === 0
                || typeof val === 'object' && Object.entries(val).length === 0
        },
        checkType(schema, val, goRecursive = false) {
            if (!('type' in schema)) {
                const oneOf = 'oneOf' in schema ? schema.oneOf :
                    ('anyOf' in schema ? schema.anyOf : []);
                for (const el of oneOf) {
                    const res = this.checkType(el, val);
                    if (res !== null) {
                        return res;
                    }
                }
                return null;
            }
            if (val === null && (schema.type === 'null' || schema.nullable)) {
                return schema;
            }
            if (typeof val === 'string' && schema.type === 'string') {
                return schema;
            }
            if (typeof val === 'number' && schema.type === 'number') {
                return schema;
            }
            if (typeof val === 'number' && Number.isInteger(val) && schema.type === 'integer') {
                return schema;
            }
            if (Array.isArray(val) && schema.type === 'array') {
                return schema;
            }
            if (typeof val === 'object' && schema.type === 'object') {
                for (const [key, value] of Object.entries(val)) {
                    if (!(key in schema.properties)) {
                        return null;
                    }
                    if (!goRecursive) {
                        continue;
                    }
                    if (this.checkType(schema.properties[key], value) === null) {
                        return null;
                    }
                }
                return schema;
            }
            return null;
        },
        collapsed(param) {
            if (!(param in this.collapse)) {
                this.collapse[param] = true;
            }
            return this.collapse[param];
        },
        toggleCollapse(param) {
            this.collapse[param] = !this.collapsed(param);
        }
    }
}
</script>

<style>
.description {
    margin-bottom: 0.5em;
}
.description p, .description ul {
    margin: 0;
}
.description ul {
    padding-left: 1.25rem;
}
</style>
<style scoped>
.fields-group {
    padding: 1rem 0.75rem;
    border-radius: 0.5rem;
}
.fields-group:not(:first-child) {
    margin-top: 1rem;
}
.compressed {
    color: var(--text-color);
    padding: 1rem 0.75rem;
    background: var(--background-color-blockquote);
    border-radius: 0.5rem;
    margin-bottom: 0.5rem;
}
.hint.description {
    color: var(--text-color-hint-strong);
}
.label {
    text-align: left;
}
</style>