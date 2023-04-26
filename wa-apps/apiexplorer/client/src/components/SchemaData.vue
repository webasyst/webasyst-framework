<template>
    <div v-if="isEmpty(value)">
        <em class="grey">
            &lt;{{ $t('empty') }}&gt;
        </em>
        <div class="hint" v-if="description" v-html="descriptionMarkdown"></div>
        <div class="hint" v-if="schema.type === 'array' && 'items' in schema && schema.items.description" v-html="markdown(schema.items.description)"></div>
    </div>
    <div v-else-if="schema.type === 'string' || schema.type === 'integer' || schema.type === 'number' || schema.type === 'boolean'">
        <span v-if="schema.type === 'string' && schema.format === 'date-time'">
            {{ (new Date(value)).toLocaleString() }}
        </span>
        <span v-else-if="schema.type === 'string' && schema.format === 'date'">
            {{ (new Date(value)).toLocaleDateString() }}
        </span>
        <span v-else-if="schema.type === 'string' && schema.format === 'uri'">
            <a :href="value">{{ value }}</a>
        </span>
        <span v-else>{{ value }}</span>
        <div class="hint" v-if="description" v-html="descriptionMarkdown"></div>
    </div>
    <div v-else-if="schema.type === 'object'">
        <div class="hint custom-pt-4" v-if="description" v-html="descriptionMarkdown"></div>
        <div v-if="typeof value === 'object' && !Array.isArray(value)" class="fields-group">
            <div v-for="[param, val] of Object.entries(value)" :key="param">
                <div v-if="doCollapseNestedArrayDeep(param, val)">
                    <h5 class="custom-mb-4">{{ param }}</h5>
                    <div class="hint custom-mb-4" v-if="schema.properties[param].description" v-html="markdown(schema.properties[param].description)"></div>
                    <div class="custom-ml-32 custom-pb-32">
                        <schema-data 
                            :schema="schema.properties[param]"
                            :value="val"
                            :deep="new_deep"
                        />
                        <hr class="custom-mt-16"/>
                    </div>
                </div>
                <div v-else class="field">
                    <div class="name">
                        {{ param }}:
                        <div class="hint" v-if="isArrayNested(param) && schema.properties[param].description" v-html="markdown(schema.properties[param].description)"></div>
                    </div>
                    <div class="value">
                        <schema-data 
                            :schema="schema.properties[param]"
                            :value="val"
                            :deep="new_deep"
                            :description="isArrayNested(param) ? '' : schema.properties[param].description"
                            v-if="'properties' in schema && param in schema.properties"
                        />
                        <pre v-else class="small" v-html="prettifyJson(val)"></pre>
                    </div>
                </div>
            </div>
        </div>
        <pre v-else class="small" v-html="prettifyJson(value)"></pre>
    </div>
    <div v-else-if="schema.type === 'array'">
        <div class="hint custom-pb-8 custom-pt-4" v-if="description" v-html="descriptionMarkdown"></div>
        <div class="hint custom-pb-8 custom-pt-4" v-if="'items' in schema && schema.items.description" v-html="markdown(schema.items.description)"></div>
        <div v-if="Array.isArray(value)">
            <div v-for="(val, index) in value" :key="index">
                <schema-data 
                    :schema="schema.items"
                    :value="val"
                    :deep="new_deep"
                    v-if="'items' in schema"
                />
                <pre v-else class="small" v-html="prettifyJson(val)"></pre>
                <hr v-if="index < value.length - 1" :class="[isSimpleItemsArray ? 'custom-my-4' : 'custom-my-16']"/>
                <div v-else class="custom-my-16"></div>
            </div>
            <em v-if="value === null || value.length === 0" class="grey">
                &lt;{{ $t('empty') }}&gt;
            </em>
        </div>
        <pre v-else class="small" v-html="prettifyJson(value)"></pre>
    </div>
    <div v-else>
        <p>
            {{ $t('Unknown field type') }} - <strong>{{ schema.type }}</strong>. 
            {{ $t('There is no interface for it yet :(') }}.
        </p>
        <div class="hint" v-if="description" v-html="descriptionMarkdown"></div>
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
            new_deep: this.deep + 1
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
        doCollapseNestedArrayDeep(param, val) {
            return this.isArrayNested(param)
                && 'items' in this.schema.properties[param]
                && this.schema.properties[param].items.type === 'object'
                && !this.isEmpty(val)
        },
        isArrayNested(param) {
            return 'properties' in this.schema 
                && param in this.schema.properties 
                && this.schema.properties[param].type === 'array'
        },
        isEmpty(val) {
            return val === null
                || val === ''
                || Array.isArray(val) && val.length === 0
                || typeof val === 'object' && Object.entries(val).length === 0
        }
    }
}
</script>

<style scoped>
.hint p, .hint ul { margin: 0; }
</style>